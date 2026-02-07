<?php
/**
 * Page d'admin et AJAX.
 *
 * @package DecodeHeadlessConnector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DHC_Admin {

	private $api;

	public function __construct( $api ) {
		$this->api = $api;
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		
		// AJAX
		foreach ( array( 'login', 'logout', 'save_cache', 'flush_cache', 'fetch_content', 'get_item', 'update_content' ) as $action ) {
			add_action( "wp_ajax_dhc_$action", array( $this, "ajax_$action" ) );
		}
	}

	public function menu() {
		add_menu_page( 'Headless Connector', 'Headless Connector', 'manage_options', 'dhc-connector', array( $this, 'page' ), 'dashicons-rest-api' );
	}

	public function assets( $hook ) {
		if ( 'toplevel_page_dhc-connector' !== $hook ) return;
		wp_enqueue_style( 'dhc-admin', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/admin.css' );
		wp_enqueue_script( 'dhc-admin', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/admin.js', array(), '1.0', true );
		wp_localize_script( 'dhc-admin', 'DHC', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'dhc_nonce' ) ) );
	}

	public function page() {
		$token = get_option( 'dhc_token' );
		?>
		<div class="wrap dhc-wrap">
			<h1>Décode Headless Connector</h1>
			<div class="dhc-grid">
				
				<!-- Connexion -->
				<div class="dhc-card">
					<h2>Connexion API</h2>
						<form id="dhc-login-form" onsubmit="return false;">
						<p><label>URL API</label><input type="url" id="dhc-base-url" value="<?php echo esc_attr( get_option( 'dhc_base_url' ) ); ?>"></p>
						<p><label>Login</label><input type="text" id="dhc-login"></p>
						<p><label>Mot de passe</label><input type="password" id="dhc-password"></p>
						<p><label>Secret Key</label><input type="text" id="dhc-secret"></p>
						<button type="button" id="dhc-login-btn" class="button button-primary">Se connecter</button>
					</form>
					<div class="dhc-status">
						Statut : <span class="dhc-label <?php echo $token ? 'ok' : 'ko'; ?>"><?php echo $token ? 'Connecté' : 'Déconnecté'; ?></span>
						<?php if ( $token ) : ?>
							<br>Token : <code><?php echo substr( $token, 0, 5 ) . '...'; ?></code>
							<br><button id="dhc-logout" class="button">Déconnexion</button>
						<?php endif; ?>
					</div>
					<div id="dhc-login-msg"></div>
				</div>

				<!-- Cache -->
				<div class="dhc-card">
					<h2>Cache</h2>
					<form id="dhc-cache-form" onsubmit="return false;">
						<p><label><input type="checkbox" id="dhc-cache-enabled" <?php checked( get_option( 'dhc_cache_enabled' ) ); ?>> Activer le cache</label></p>
						<p><label>Durée (sec)</label><input type="number" id="dhc-cache-ttl" value="<?php echo esc_attr( get_option( 'dhc_cache_ttl', 300 ) ); ?>"></p>
						<button type="button" id="dhc-save-cache" class="button button-primary">Sauvegarder</button>
						<button type="button" id="dhc-flush-cache" class="button">Vider cache</button>
					</form>
					<div id="dhc-cache-msg"></div>
				</div>

				<!-- Shortcodes -->
				<div class="dhc-card">
					<h2>Shortcodes</h2>
					<ul>
						<li><code>[dhc_content_list limit="5"]</code></li>
						<li><code>[dhc_content id="123"]</code></li>
						<li><code>[dhc_content_field id="123" field="title"]</code></li>
					</ul>
				</div>

				<!-- Contenus -->
				<div class="dhc-card full">
					<h2>Contenus</h2>
					<button id="dhc-refresh" class="button">Rafraîchir</button>
					<div id="dhc-content-area"></div>
				</div>

			</div>
		</div>
		<?php
	}

	// AJAX Handlers simplifiés
	private function check() {
		check_ajax_referer( 'dhc_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Interdit' );
	}

	public function ajax_login() {
		$this->check();
		$base_url   = isset( $_POST['base_url'] ) ? esc_url_raw( wp_unslash( $_POST['base_url'] ) ) : '';
		$login      = isset( $_POST['login'] ) ? sanitize_text_field( wp_unslash( $_POST['login'] ) ) : '';
		$password   = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';
		$secret_key = isset( $_POST['secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['secret_key'] ) ) : '';

		if ( empty( $base_url ) || empty( $login ) || empty( $password ) ) {
            wp_send_json_error( array( 'message' => 'Champs requis manquants.' . $base_url . ' ' . $login . ' ' . $password . ' ' . $secret_key ) );
		}

		update_option( 'dhc_base_url', $base_url );
		$res = $this->api->login( $login, $password, $secret_key );

		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ) );
		}

		update_option( 'dhc_token', sanitize_text_field( $res ) );
		wp_send_json_success( array( 'message' => 'Connecté' ) );
	}

	public function ajax_logout() {
		$this->check();
		update_option( 'dhc_token', '' );
		wp_send_json_success( array( 'message' => 'Déconnecté' ) );
	}

	public function ajax_save_cache() {
		$this->check();
		$enabled = isset( $_POST['enabled'] ) && 'true' === $_POST['enabled'];
		$ttl     = isset( $_POST['ttl'] ) ? absint( $_POST['ttl'] ) : 300;
		update_option( 'dhc_cache_enabled', $enabled );
		update_option( 'dhc_cache_ttl', $ttl );
		wp_send_json_success( array( 'message' => 'Sauvegardé' ) );
	}

	public function ajax_flush_cache() {
		$this->check();
		$this->api->flush_cache();
		wp_send_json_success( array( 'message' => 'Cache vidé' ) );
	}

	public function ajax_fetch_content() {
		$this->check();
		$items = $this->api->get_content( array( 'limit' => 20 ) );
		if ( is_wp_error( $items ) ) wp_send_json_error( array( 'message' => $items->get_error_message() ) );
		
		if ( empty( $items ) ) wp_send_json_success( array( 'html' => 'Aucun contenu.' ) );

		ob_start();
		echo '<table class="wp-list-table widefat striped"><thead><tr><th>ID</th><th>Titre</th><th>Action</th></tr></thead><tbody>';
		foreach ( $items as $item ) {
			echo '<tr>';
			echo '<td>' . esc_html( $item['id'] ?? '' ) . '</td>';
			echo '<td>' . esc_html( $item['title'] ?? '' ) . '</td>';
			echo '<td><button class="button dhc-edit" data-id="' . esc_attr( $item['id'] ) . '" data-title="' . esc_attr( $item['title'] ?? '' ) . '" data-content="' . esc_attr( $item['content'] ?? '' ) . '">Modif</button></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		wp_send_json_success( array( 'html' => ob_get_clean() ) );
	}

	public function ajax_get_item() {
		$this->check();
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => 'ID manquant.' ) );
		}
		$item = $this->api->get_item( $id );
		if ( is_wp_error( $item ) ) {
			wp_send_json_error( array( 'message' => $item->get_error_message() ) );
		}
		wp_send_json_success( array( 'item' => $item ) );
	}

	public function ajax_update_content() {
		$this->check();
		$id      = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => 'ID manquant.' ) );
		}

		$res = $this->api->update_item( $id, array( 'title' => $title, 'content' => $content ) );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ) );
		}

		$this->api->flush_cache();
		wp_send_json_success( array( 'message' => 'Mis à jour' ) );
	}
}

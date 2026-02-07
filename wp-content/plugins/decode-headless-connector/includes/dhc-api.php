<?php
/**
 * Classe gerant les appels API et le cache.
 *
 * @package DecodeHeadlessConnector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DHC_Api {

	private function get_base_url() {
		return rtrim( get_option( 'dhc_base_url', '' ), '/' );
	}

	private function get_token() {
		return get_option( 'dhc_token', '' );
	}

	private function request( $method, $path, $data = array(), $auth = true ) {
		$url  = $this->get_base_url() . $path;
		$args = array(
			'method'  => $method,
			'timeout' => 10,
			'headers' => array(),
		);

		if ( $auth && $this->get_token() ) {
			$args['headers']['Authorization'] = 'Bearer ' . $this->get_token();
		}

		if ( 'GET' === $method && ! empty( $data ) ) {
			$url = add_query_arg( $data, $url );
		} else if ( ! empty( $data ) ) {
			$args['body'] = $data;
		}

		$response = wp_remote_request( $url, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$json = json_decode( $body, true );

		if ( wp_remote_retrieve_response_code( $response ) >= 300 ) {
			$msg = isset( $json['message'] ) ? $json['message'] : 'Erreur API';
			return new WP_Error( 'api_error', $msg );
		}

		return $json;
	}

	public function login( $login, $password, $secret = '' ) {
		$data = array(
			'email'    => $login,
			'password' => $password,
		);
		if ( $secret ) {
			$data['secret_key'] = $secret;
		}

		$res = $this->request( 'POST', '/api/login', $data, false );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		if ( ! empty( $res['token'] ) ) {
			return $res['token'];
		}
		if ( ! empty( $res['access_token'] ) ) {
			return $res['access_token'];
		}
		if ( ! empty( $res['data']['token'] ) ) {
			return $res['data']['token'];
		}

		return new WP_Error( 'no_token', 'Pas de token recu' );
	}

	public function get_content( $params = array() ) {
		$cache_key = 'dhc_list_' . md5( wp_json_encode( $params ) );

		if ( get_option( 'dhc_cache_enabled' ) ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$res = $this->request( 'GET', '/api/content', $params );

		if ( ! is_wp_error( $res ) && get_option( 'dhc_cache_enabled' ) ) {
			$ttl = (int) get_option( 'dhc_cache_ttl', 300 );
			set_transient( $cache_key, $res, $ttl );
		}

		return $res;
	}

	public function get_item( $id ) {
		$cache_key = 'dhc_item_' . $id;

		if ( get_option( 'dhc_cache_enabled' ) ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$res = $this->request( 'GET', '/api/content/' . $id );
		if ( ! is_wp_error( $res ) && get_option( 'dhc_cache_enabled' ) ) {
			$ttl = (int) get_option( 'dhc_cache_ttl', 300 );
			set_transient( $cache_key, $res, $ttl );
		}

		return $res;
	}

	public function update_item( $id, $data ) {
		return $this->request( 'POST', '/api/content/' . $id, $data );
	}

	public function flush_cache() {
		global $wpdb;
		$prefix = '_transient_dhc_';
		$timeout_prefix = '_transient_timeout_dhc_';
		$sql = "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s";
		$wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( $prefix ) . '%%', $wpdb->esc_like( $timeout_prefix ) . '%%' ) );
	}
}

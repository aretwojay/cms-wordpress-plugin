<?php
/**
 * Shortcodes.
 *
 * @package DecodeHeadlessConnector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DHC_Shortcodes {

	private $api;

	public function __construct( $api ) {
		$this->api = $api;
		add_shortcode( 'dhc_content_list', array( $this, 'list' ) );
		add_shortcode( 'dhc_content', array( $this, 'item' ) );
		add_shortcode( 'dhc_content_field', array( $this, 'field' ) );
	}

	public function list( $atts ) {
		$atts = shortcode_atts( array( 'limit' => 5 ), $atts );
		$items = $this->api->get_content( array( 'limit' => $atts['limit'] ) );

		if ( is_wp_error( $items ) ) return 'Erreur API';
		if ( empty( $items ) ) return 'Aucun contenu';

		$html = '<ul>';
		foreach ( $items as $item ) {
			$title = esc_html( $item['title'] ?? '' );
			$html .= "<li>$title</li>";
		}
		$html .= '</ul>';
		return $html;
	}

	public function item( $atts ) {
		if ( empty( $atts['id'] ) ) return 'ID manquant';
		$item = $this->api->get_item( $atts['id'] );
		if ( is_wp_error( $item ) ) return 'Erreur API';

		$title = esc_html( $item['title'] ?? '' );
		$content = wp_kses_post( $item['content'] ?? '' );
		return "<article><h3>$title</h3><div>$content</div></article>";
	}

	public function field( $atts ) {
		if ( empty( $atts['id'] ) || empty( $atts['field'] ) ) return '';
		$item = $this->api->get_item( $atts['id'] );
		
		if ( is_wp_error( $item ) ) return '';
		
		return wp_kses_post( $item[ $atts['field'] ] ?? '' );
	}
}

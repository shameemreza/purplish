<?php
/**
 * The "Instant results" option on the core Search block.
 *
 * @package Purplish
 */

namespace Purplish\Frontend;

use Purplish\ProductSearch;

defined( 'ABSPATH' ) || exit;

/**
 * One option on the core Search block, which covers WooCommerce's Product
 * Search and every search field Purple ships. With it on, the form gets
 * its settings and an empty, hidden results list, and the page loads a
 * small script that fills the list from the instant search route while
 * the shopper types. Without the script the form submits to the results
 * page as it always did.
 *
 * @since 1.4.1
 */
final class InstantSearch {

	const BLOCK  = 'core/search';
	const HANDLE = 'purplish-search';

	/**
	 * Search blocks drawn so far, for unique IDs.
	 *
	 * @var int
	 */
	private static $count = 0;

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'register_block_type_args', array( self::class, 'add_attribute' ), 10, 2 );
		add_filter( 'render_block_' . self::BLOCK, array( self::class, 'render' ), 10, 2 );
		// After BlockOptions loads the editor script at 10.
		add_action( 'enqueue_block_editor_assets', array( self::class, 'enqueue_editor' ), 20 );
	}

	/**
	 * Hands the editor three real products for the sample results, and
	 * loads the results styles for the sample, which shows outside the
	 * canvas.
	 *
	 * @return void
	 */
	public static function enqueue_editor() {
		$asset = PURPLISH_DIR . 'build/search.asset.php';
		$asset = file_exists( $asset ) ? include $asset : array( 'version' => PURPLISH_VERSION );
		wp_enqueue_style( self::HANDLE, PURPLISH_URL . 'build/search.css', array(), $asset['version'] );
		wp_style_add_data( self::HANDLE, 'rtl', 'replace' );
		wp_add_inline_script( BlockOptions::HANDLE, 'window.purplishSearch = ' . wp_json_encode( self::sample() ) . ';', 'before' );
	}

	/**
	 * Three recent products and three categories for the editor's sample,
	 * with plain-text prices.
	 *
	 * @return array{cats: string[], items: array<int, array{name: string, url: string, img: string, priceText: string, sold: bool}>}
	 */
	public static function sample() {
		$ids   = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => 3,
				'return'  => 'ids',
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);
		$items = array();
		foreach ( (array) $ids as $id ) {
			$item    = ProductSearch::item( (int) $id );
			$items[] = array(
				'name'      => $item['name'],
				'url'       => $item['url'],
				'img'       => $item['img'],
				'priceText' => trim( (string) preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) preg_replace( '#<span class="screen-reader-text">.*?</span>#s', '', $item['price'] ) ), ENT_QUOTES, 'UTF-8' ) ) ),
				'sold'      => $item['sold'],
			);
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'number'     => 3,
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'fields'     => 'names',
			)
		);

		return array(
			'cats'  => is_array( $terms ) ? array_map( 'html_entity_decode', $terms ) : array(),
			'items' => $items,
		);
	}

	/**
	 * Lets the block save a `purplish` attribute, so the server keeps it.
	 *
	 * @param array  $args Block type arguments.
	 * @param string $name Block name.
	 * @return array
	 */
	public static function add_attribute( $args, $name ) {
		if ( self::BLOCK !== $name ) {
			return $args;
		}
		$args['attributes']             = isset( $args['attributes'] ) && is_array( $args['attributes'] ) ? $args['attributes'] : array();
		$args['attributes']['purplish'] = array(
			'type'    => 'object',
			'default' => array(),
		);
		return $args;
	}

	/**
	 * The block's options with defaults: off, six results, categories and
	 * price on.
	 *
	 * @param mixed $saved The block's `purplish` attribute.
	 * @return array{instant: bool, count: int, cats: bool, price: bool}
	 */
	public static function options( $saved ) {
		$saved = is_array( $saved ) ? $saved : array();
		return array(
			'instant' => ! empty( $saved['instant'] ),
			'count'   => isset( $saved['count'] ) && is_numeric( $saved['count'] ) ? ProductSearch::clamp_count( $saved['count'] ) : ProductSearch::COUNT,
			'cats'    => ! isset( $saved['cats'] ) || false !== $saved['cats'],
			'price'   => ! isset( $saved['price'] ) || false !== $saved['price'],
		);
	}

	/**
	 * Adds the settings and the results list to a Search block with the
	 * option on, and loads the script and styles.
	 *
	 * @param string $html  Rendered block.
	 * @param array  $block Parsed block.
	 * @return string
	 */
	public static function render( $html, $block ) {
		$html    = (string) $html;
		$options = self::options( isset( $block['attrs']['purplish'] ) ? $block['attrs']['purplish'] : array() );
		if ( ! $options['instant'] ) {
			return $html;
		}

		$close = strrpos( $html, '</form>' );
		if ( false === $close ) {
			return $html;
		}

		$tags = new \WP_HTML_Tag_Processor( $html );
		if ( ! $tags->next_tag( 'FORM' ) ) {
			return $html;
		}
		$tags->set_attribute( 'data-purplish-search', wp_json_encode( self::settings( $options ) ) );
		$tags->add_class( 'purplish-search-form' );
		$html = $tags->get_updated_html();

		$id   = 'purplish-search-' . ( ++self::$count );
		$list = '<div class="purplish-search" hidden><div class="purplish-search__list" role="listbox" id="' . esc_attr( $id ) . '" aria-label="' . esc_attr__( 'Search suggestions', 'purplish' ) . '"></div></div>'
			. '<span class="purplish-search__live" role="status"></span>';

		// The form tag gained attributes, so the closing tag moved; find it again.
		$close = strrpos( $html, '</form>' );
		$html  = substr( $html, 0, $close ) . $list . substr( $html, $close );

		self::enqueue();
		return $html;
	}

	/**
	 * What the script reads from the form: the route, the options and the
	 * words it shows. The live region counts come ready for 0 to the most
	 * results, so each language keeps its own plural forms.
	 *
	 * @param array{instant: bool, count: int, cats: bool, price: bool} $options The block's options.
	 * @return array<string, mixed>
	 */
	public static function settings( $options ) {
		$counts = array( __( 'No products found', 'purplish' ) );
		for ( $i = 1; $i <= $options['count']; $i++ ) {
			/* translators: %d: number of products found. */
			$counts[] = sprintf( _n( '%d product found', '%d products found', $i, 'purplish' ), $i );
		}

		return array(
			'url'    => rest_url( ProductSearch::NAMESPACE . ProductSearch::ROUTE ),
			'n'      => $options['count'],
			'c'      => $options['cats'] ? 1 : 0,
			'p'      => $options['price'] ? 1 : 0,
			'counts' => $counts,
			/* translators: %s: what the shopper typed. */
			'all'    => __( 'See all results for “%s”', 'purplish' ),
			'sold'   => __( 'Sold out', 'purplish' ),
			'cats'   => __( 'Categories', 'purplish' ),
			'items'  => __( 'Products', 'purplish' ),
		);
	}

	/**
	 * Loads the script and its styles.
	 *
	 * @return void
	 */
	public static function enqueue() {
		$asset = PURPLISH_DIR . 'build/search.asset.php';
		$asset = file_exists( $asset ) ? include $asset : array(
			'dependencies' => array(),
			'version'      => PURPLISH_VERSION,
		);
		wp_enqueue_style( self::HANDLE, PURPLISH_URL . 'build/search.css', array(), $asset['version'] );
		wp_style_add_data( self::HANDLE, 'rtl', 'replace' );
		wp_enqueue_script(
			self::HANDLE,
			PURPLISH_URL . 'build/search.js',
			array(),
			$asset['version'],
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
}

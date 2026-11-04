<?php
/**
 * Reads variation photo colors in the background with Action Scheduler.
 *
 * @package Purplish
 */

namespace Purplish\Jobs;

use Purplish\Color\Detector;
use Purplish\Meta;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Queues one job per variation or simple product whose photo changed,
 * and walks existing ones in batches after activation. Saving a product never waits
 * on reading a photo.
 */
final class DetectColors {

	const GROUP        = 'purplish';
	const HOOK_ONE     = 'purplish_detect_variation_color';
	const HOOK_BATCH   = 'purplish_detect_batch';
	const BATCH_SIZE   = 50;
	const QUEUE_OPTION = 'purplish_queue_batch';

	const KIND_VARIATION = 'variation';
	const KIND_SIMPLE    = 'simple';

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'woocommerce_new_product_variation', array( $this, 'maybe_queue' ), 10, 2 );
		add_action( 'woocommerce_update_product_variation', array( $this, 'maybe_queue' ), 10, 2 );
		add_action( 'woocommerce_new_product', array( $this, 'maybe_queue' ), 10, 2 );
		add_action( 'woocommerce_update_product', array( $this, 'maybe_queue' ), 10, 2 );
		add_action( self::HOOK_ONE, array( $this, 'detect' ) );
		add_action( self::HOOK_BATCH, array( $this, 'batch' ), 10, 2 );
		add_action( 'init', array( $this, 'queue_first_batch' ) );
	}

	/**
	 * Runs on activation. Action Scheduler may not be ready yet, so this
	 * only leaves a note for the next init.
	 *
	 * @return void
	 */
	public static function activate() {
		update_option( self::QUEUE_OPTION, 1, false );
	}

	/**
	 * Queues the first batch after activation, once.
	 *
	 * @return void
	 */
	public function queue_first_batch() {
		if ( ! get_option( self::QUEUE_OPTION ) || ! function_exists( 'as_enqueue_async_action' ) ) {
			return;
		}

		if ( as_enqueue_async_action( self::HOOK_BATCH, self::batch_args( 0, self::KIND_VARIATION ), self::GROUP, true ) ) {
			delete_option( self::QUEUE_OPTION );
		}
	}

	/**
	 * Queues a detection job when the photo of a variation or a simple
	 * product changed.
	 *
	 * @param int              $product_id Product or variation ID.
	 * @param \WC_Product|null $product    Product, when WooCommerce passes it.
	 * @return void
	 */
	public function maybe_queue( $product_id, $product = null ) {
		if ( ! $product instanceof \WC_Product ) {
			$product = wc_get_product( $product_id );
		}
		if ( ! self::is_colorable( $product ) || ! self::needs_detection( $product ) ) {
			return;
		}

		as_enqueue_async_action( self::HOOK_ONE, array( 'product_id' => (int) $product->get_id() ), self::GROUP, true );
	}

	/**
	 * Whether Purplish gives a product its own color: variations and simple
	 * products.
	 *
	 * @param mixed $product Product.
	 * @return bool
	 */
	public static function is_colorable( $product ) {
		return $product instanceof WC_Product_Variation || ( $product instanceof \WC_Product && $product->is_type( 'simple' ) );
	}

	/**
	 * Whether a variation's saved auto color is out of date: it's in
	 * Automatic mode and its photo, or the photo file, isn't the one the
	 * color came from.
	 *
	 * @param \WC_Product $variation Variation or simple product.
	 * @return bool
	 */
	public static function needs_detection( $variation ) {
		if ( Meta::MODE_AUTO !== Meta::mode( $variation ) ) {
			return false;
		}
		return self::source_of( $variation ) !== (string) $variation->get_meta( Meta::AUTO_SOURCE );
	}

	/**
	 * Where a variation's auto color comes from: the photo's attachment ID
	 * and its modified time, so a replaced file counts as a new photo.
	 *
	 * @param \WC_Product $variation Variation or simple product.
	 * @return string Empty when it has no photo.
	 */
	public static function source_of( $variation ) {
		$image_id = (int) $variation->get_image_id( 'edit' );
		if ( ! $image_id ) {
			return '';
		}
		return $image_id . ':' . (string) get_post_modified_time( 'U', true, $image_id );
	}

	/**
	 * Reads the photo color of one variation or simple product and saves it
	 * with the product.
	 *
	 * @param int $product_id Variation or simple product ID.
	 * @return void
	 */
	public function detect( $product_id ) {
		$variation = wc_get_product( absint( $product_id ) );
		if ( ! self::is_colorable( $variation ) || ! self::needs_detection( $variation ) ) {
			return;
		}

		$source = self::source_of( $variation );
		if ( '' === $source ) {
			// The photo was removed, so the old color no longer applies.
			$variation->delete_meta_data( Meta::AUTO_COLOR );
			$variation->delete_meta_data( Meta::AUTO_SOURCE );
			$variation->save();
			return;
		}

		$image_id = (int) $variation->get_image_id( 'edit' );
		if ( '' === Detector::file_for( $image_id ) ) {
			// Offloaded to a CDN or missing on disk. Purplish never fetches URLs.
			wc_get_logger()->info(
				sprintf( 'Variation %1$d: photo %2$d is not on disk, so its color was not read.', $variation->get_id(), $image_id ),
				array( 'source' => 'purplish' )
			);
		}

		/**
		 * Filters the color the photo detector picked, before it's saved.
		 * Return an empty string to save no color.
		 *
		 * @since 1.1.0
		 *
		 * @param string $color    Hex color, or an empty string when the photo couldn't be read.
		 * @param int    $image_id Attachment ID of the photo.
		 */
		$color = (string) sanitize_hex_color( (string) apply_filters( 'purplish_detected_color', Detector::from_attachment( $image_id ), $image_id ) );
		if ( '' === $color ) {
			$variation->delete_meta_data( Meta::AUTO_COLOR );
		} else {
			$variation->update_meta_data( Meta::AUTO_COLOR, $color );
		}
		// Saved even without a color, so the same photo isn't read again.
		$variation->update_meta_data( Meta::AUTO_SOURCE, $source );
		$variation->save();
	}

	/**
	 * Queues jobs for one page of variations or simple products that have a
	 * photo, then the next page. Variations go first; their empty page
	 * starts the simple products, and theirs ends the walk.
	 *
	 * @param int    $offset Items of this kind already walked.
	 * @param string $kind   `variation` or `simple`.
	 * @return void
	 */
	public function batch( $offset = 0, $kind = self::KIND_VARIATION ) {
		$offset = absint( $offset );
		$kind   = self::KIND_SIMPLE === $kind ? self::KIND_SIMPLE : self::KIND_VARIATION;

		$query = array(
			'post_type'        => self::KIND_SIMPLE === $kind ? 'product' : 'product_variation',
			'post_status'      => array( 'publish', 'private' ),
			'fields'           => 'ids',
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'posts_per_page'   => self::BATCH_SIZE,
			'offset'           => $offset,
			'no_found_rows'    => true,
			'suppress_filters' => false,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- One indexed key, 50 rows per run, in the background.
			'meta_query'       => array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
		);
		if ( self::KIND_SIMPLE === $kind ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Same background walk.
			$query['tax_query'] = array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'simple',
				),
			);
		}

		$ids = get_posts( $query );

		if ( empty( $ids ) ) {
			if ( self::KIND_VARIATION === $kind ) {
				as_enqueue_async_action( self::HOOK_BATCH, self::batch_args( 0, self::KIND_SIMPLE ), self::GROUP, true );
			}
			return;
		}

		foreach ( $ids as $id ) {
			$this->maybe_queue( $id );
		}

		as_enqueue_async_action( self::HOOK_BATCH, self::batch_args( $offset + count( $ids ), $kind ), self::GROUP, true );
	}

	/**
	 * Arguments for a batch job, in the order batch() takes them.
	 *
	 * @param int    $offset Items already walked.
	 * @param string $kind   `variation` or `simple`.
	 * @return array{offset: int, kind: string}
	 */
	public static function batch_args( $offset, $kind ) {
		return array(
			'offset' => (int) $offset,
			'kind'   => $kind,
		);
	}
}

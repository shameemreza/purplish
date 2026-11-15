<?php
/**
 * Tests for where the Second photo goes in the Product Image markup.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\SecondPhoto;
use WC_Product_Simple;
use WP_UnitTestCase;

/**
 * The second photo finds WooCommerce's inner container whatever
 * attributes it prints on it.
 */
class SecondPhotoTest extends WP_UnitTestCase {

	/**
	 * The Second photo filter, inside a collection that shows it.
	 *
	 * @var SecondPhoto
	 */
	private $second;

	/**
	 * A block instance for a product with a gallery photo.
	 *
	 * @var object
	 */
	private $instance;

	/**
	 * A product with a gallery photo, inside a collection with the look on.
	 */
	public function set_up() {
		parent::set_up();
		$gallery = self::factory()->attachment->create_object(
			'mug-back.jpg',
			0,
			array( 'post_mime_type' => 'image/jpeg' )
		);
		$product = new WC_Product_Simple();
		$product->set_name( 'Mug' );
		$product->set_gallery_image_ids( array( $gallery ) );
		$product->save();

		$this->instance = (object) array( 'context' => array( 'postId' => $product->get_id() ) );
		$this->second   = new SecondPhoto();
		$this->second->enter(
			array(
				'blockName' => SecondPhoto::COLLECTION,
				'attrs'     => array(
					'purplish' => array(
						'hoveron'    => 'photo',
						'hoverphoto' => 'second',
					),
				),
			)
		);
	}

	/**
	 * Product Image markup with the given inner container opening tag.
	 *
	 * @param string $container The container's opening tag.
	 * @return string
	 */
	private static function image( $container ) {
		return '<div class="wc-block-components-product-image"><a href="/product/mug/"><img class="wc-block-components-product-image__image" src="mug.jpg" alt="Mug">' . $container . '</div></a></div>';
	}

	/**
	 * WooCommerce 11.3 prints an empty style on the container.
	 */
	public function test_container_with_style() {
		$out = $this->second->add_photo( self::image( '<div class="wc-block-components-product-image__inner-container" style="">' ), array(), $this->instance );
		$this->assertStringContainsString( 'purplish-second-photo', $out );
		$this->assertLessThan( strpos( $out, '__inner-container' ), strpos( $out, 'purplish-second-photo' ) );
	}

	/**
	 * Any other attribute after the class still matches.
	 */
	public function test_container_with_other_attributes() {
		$out = $this->second->add_photo( self::image( '<div class="wc-block-components-product-image__inner-container" data-wp-bind--hidden="state.x">' ), array(), $this->instance );
		$this->assertSame( 1, substr_count( $out, 'purplish-second-photo' ) );
	}

	/**
	 * The bare container from earlier WooCommerce versions still matches.
	 */
	public function test_bare_container() {
		$out = $this->second->add_photo( self::image( '<div class="wc-block-components-product-image__inner-container">' ), array(), $this->instance );
		$this->assertStringContainsString( 'purplish-second-photo', $out );
	}

	/**
	 * A longer class name that starts the same is not the container.
	 */
	public function test_other_class_left_alone() {
		$html = self::image( '<div class="wc-block-components-product-image__inner-container-extra">' );
		$this->assertSame( $html, $this->second->add_photo( $html, array(), $this->instance ) );
	}
}

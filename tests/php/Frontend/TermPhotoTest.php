<?php
/**
 * Tests for "Use the category photo" on Cover.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use Purplish\Frontend\TermPhoto;
use WP_UnitTestCase;

/**
 * On a term page with a photo the cover shows it; elsewhere the cover
 * keeps its own image.
 */
class TermPhotoTest extends WP_UnitTestCase {

	/**
	 * The term's photo.
	 *
	 * @var int
	 */
	private $photo;

	/**
	 * A category with the photo.
	 *
	 * @var int
	 */
	private $term;

	/**
	 * A category with a photo.
	 */
	public function set_up() {
		parent::set_up();
		$this->photo = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$this->term  = self::factory()->category->create( array( 'name' => 'Shoes' ) );
		update_term_meta( $this->term, 'thumbnail_id', $this->photo );
	}

	/**
	 * A cover with a fixed image.
	 *
	 * @return string
	 */
	private static function cover() {
		return '<div class="wp-block-cover"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-7" alt="Sale" src="http://example.org/sale.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><h1>Shop</h1></div></div>';
	}

	/**
	 * A parsed Cover with the option.
	 *
	 * @param array $attrs More attributes.
	 * @return array
	 */
	private static function block( $attrs = array() ) {
		return array(
			'blockName' => TermPhoto::BLOCK,
			'attrs'     => array_merge( array( 'purplish' => array( 'termphoto' => true ) ), $attrs ),
		);
	}

	/**
	 * The option is Cover's alone.
	 */
	public function test_classes() {
		$this->assertSame( array( 'purplish-cover', 'purplish-cover-termphoto' ), BlockEffects::classes_for( 'core/cover', array( 'termphoto' => true ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( 'woocommerce/featured-category', array( 'termphoto' => true ) ) );
	}

	/**
	 * The image takes the photo's address, size and ID class.
	 */
	public function test_swap_image() {
		$out = TermPhoto::swap( self::cover(), $this->photo );
		$src = wp_get_attachment_image_src( $this->photo, 'full' );

		$this->assertStringContainsString( 'src="' . $src[0] . '"', $out );
		$this->assertStringContainsString( 'wp-image-' . $this->photo, $out );
		$this->assertStringNotContainsString( 'wp-image-7', $out );
		$this->assertStringNotContainsString( 'sale.jpg', $out );
		$this->assertStringContainsString( 'alt=""', $out );
	}

	/**
	 * A parallax cover's background takes the photo.
	 */
	public function test_swap_background() {
		$html = '<div class="wp-block-cover"><div role="img" class="wp-block-cover__image-background has-parallax" style="background-position:50% 50%;background-image:url(http://example.org/sale.jpg)"></div><div class="wp-block-cover__inner-container"></div></div>';
		$src  = wp_get_attachment_image_src( $this->photo, 'full' );
		$this->assertStringContainsString( 'background-image:url(' . $src[0] . ')', TermPhoto::swap( $html, $this->photo ) );
	}

	/**
	 * A cover inside the cover's content keeps its image, and a cover
	 * with no image gets one only when it uses the featured image.
	 */
	public function test_inner_and_empty() {
		$html = '<div class="wp-block-cover"><span class="wp-block-cover__background"></span><div class="wp-block-cover__inner-container"><div class="wp-block-cover"><img class="wp-block-cover__image-background" src="http://example.org/inner.jpg"/></div></div></div>';
		$this->assertSame( $html, TermPhoto::swap( $html, $this->photo ) );

		$out = TermPhoto::swap( $html, $this->photo, true );
		$this->assertMatchesRegularExpression( '#^<div class="wp-block-cover"><img[^>]*class="wp-block-cover__image-background wp-image-' . $this->photo . '"#', $out );
		$this->assertStringContainsString( 'inner.jpg', $out );
	}

	/**
	 * On the category page the cover takes the photo; off it, or without
	 * the option, the cover is unchanged.
	 */
	public function test_render() {
		$photo = new TermPhoto();
		$this->assertSame( self::cover(), $photo->render( self::cover(), self::block() ), 'Not a term page.' );

		$this->go_to( get_term_link( $this->term ) );
		$this->assertStringContainsString( 'wp-image-' . $this->photo, $photo->render( self::cover(), self::block() ) );
		$this->assertSame( self::cover(), $photo->render( self::cover(), self::block( array( 'purplish' => array() ) ) ), 'No option.' );

		delete_term_meta( $this->term, 'thumbnail_id' );
		$this->assertSame( self::cover(), $photo->render( self::cover(), self::block() ), 'No photo.' );
	}
}

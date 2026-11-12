<?php
/**
 * Tests for "Mark the current term" on Terms Query.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Frontend;

use Purplish\Frontend\BlockEffects;
use Purplish\Frontend\CurrentTerm;
use WP_UnitTestCase;

/**
 * The archive's term gets a class and `aria-current`, its listed parents
 * get the ancestor class, and nothing changes without the option.
 */
class CurrentTermTest extends WP_UnitTestCase {

	/**
	 * Parent category.
	 *
	 * @var int
	 */
	private $parent;

	/**
	 * Child of the parent.
	 *
	 * @var int
	 */
	private $child;

	/**
	 * Another top category.
	 *
	 * @var int
	 */
	private $other;

	/**
	 * Three categories, one nested.
	 */
	public function set_up() {
		parent::set_up();
		$this->parent = self::factory()->category->create( array( 'name' => 'Clothing' ) );
		$this->child  = self::factory()->category->create(
			array(
				'name'   => 'Shoes',
				'parent' => $this->parent,
			)
		);
		$this->other  = self::factory()->category->create( array( 'name' => 'Books' ) );
	}

	/**
	 * Term Template's markup for the given terms.
	 *
	 * @param int[] $ids Term IDs.
	 * @return string
	 */
	private static function list_of( $ids ) {
		$items = '';
		foreach ( $ids as $id ) {
			$items .= '<li class="wp-block-term term-' . $id . ' category taxonomy-category"><p class="wp-block-term-name"><a href="' . esc_url( get_term_link( $id ) ) . '">' . esc_html( get_term( $id )->name ) . '</a></p></li>';
		}
		return '<div class="wp-block-terms-query"><ul class="wp-block-term-template">' . $items . '</ul></div>';
	}

	/**
	 * A parsed Terms Query with the given options.
	 *
	 * @param array $saved Purplish options.
	 * @return array
	 */
	private static function block( $saved ) {
		return array(
			'blockName' => CurrentTerm::BLOCK,
			'attrs'     => array( 'purplish' => $saved ),
		);
	}

	/**
	 * The option and the looks add their wrapper classes.
	 */
	public function test_classes() {
		$this->assertSame( array( 'purplish-terms', 'purplish-terms-current' ), BlockEffects::classes_for( CurrentTerm::BLOCK, array( 'current' => true ) ) );
		$this->assertSame( array( 'purplish-terms', 'purplish-terms-look-tabs' ), BlockEffects::classes_for( CurrentTerm::BLOCK, array( 'look' => 'tabs' ) ) );
		$this->assertSame( array( 'purplish-terms', 'purplish-terms-look-photos' ), BlockEffects::classes_for( CurrentTerm::BLOCK, array( 'look' => 'photos' ) ) );
		$this->assertSame( array(), BlockEffects::classes_for( CurrentTerm::BLOCK, array( 'look' => 'pills' ) ) );
	}

	/**
	 * Shape and hover belong to the photos look, tab style to tabs, and the
	 * entrance to any list.
	 */
	public function test_look_options() {
		$photos = array(
			'look'     => 'photos',
			'shape'    => 'arch',
			'hover'    => 'morph',
			'tab'      => 'pill',
			'entrance' => 'fadeup',
		);
		$this->assertSame( array( 'purplish-terms', 'purplish-terms-look-photos', 'purplish-terms-shape-arch', 'purplish-terms-hover-morph', 'purplish-terms-entrance-fadeup' ), BlockEffects::classes_for( CurrentTerm::BLOCK, $photos ) );

		$tabs = array_merge( $photos, array( 'look' => 'tabs' ) );
		$this->assertSame( array( 'purplish-terms', 'purplish-terms-look-tabs', 'purplish-terms-tab-pill', 'purplish-terms-entrance-fadeup' ), BlockEffects::classes_for( CurrentTerm::BLOCK, $tabs ) );

		$this->assertSame(
			array( 'purplish-terms', 'purplish-terms-entrance-zoomin' ),
			BlockEffects::classes_for(
				CurrentTerm::BLOCK,
				array(
					'entrance' => 'zoomin',
					'shape'    => 'square',
				)
			)
		);
		$this->assertFalse( CurrentTerm::wants( array( 'entrance' => 'zoomin' ) ) );
	}

	/**
	 * The option or a look turns the mark on.
	 */
	public function test_wants() {
		$this->assertTrue( CurrentTerm::wants( array( 'current' => true ) ) );
		$this->assertTrue( CurrentTerm::wants( array( 'look' => 'tabs' ) ) );
		$this->assertTrue( CurrentTerm::wants( array( 'look' => 'photos' ) ) );
		$this->assertFalse( CurrentTerm::wants( array( 'current' => false ) ) );
		$this->assertFalse( CurrentTerm::wants( array( 'look' => 'pills' ) ) );
		$this->assertFalse( CurrentTerm::wants( array() ) );
		$this->assertFalse( CurrentTerm::wants( 'nope' ) );
	}

	/**
	 * The listed term gets the class and its link `aria-current`; the
	 * others stay as they were.
	 */
	public function test_marks_term() {
		$out = CurrentTerm::mark_term( self::list_of( array( $this->parent, $this->child, $this->other ) ), get_term( $this->child ) );

		$this->assertStringContainsString( 'class="wp-block-term term-' . $this->child . ' category taxonomy-category current-term"', $out );
		$this->assertStringContainsString( 'class="wp-block-term term-' . $this->parent . ' category taxonomy-category current-term-ancestor"', $out );
		$this->assertStringContainsString( 'class="wp-block-term term-' . $this->other . ' category taxonomy-category"', $out );
		$this->assertSame( 1, substr_count( $out, 'aria-current="page"' ) );
		$this->assertMatchesRegularExpression( '#<a aria-current="page" href="[^"]*">Shoes</a>#', $out );
	}

	/**
	 * A list of top categories marks the parent of a child archive.
	 */
	public function test_marks_ancestor_only() {
		$out = CurrentTerm::mark_term( self::list_of( array( $this->parent, $this->other ) ), get_term( $this->child ) );

		$this->assertStringContainsString( 'current-term-ancestor', $out );
		$this->assertStringNotContainsString( 'current-term"', $out );
		$this->assertStringNotContainsString( 'aria-current', $out );
	}

	/**
	 * A link that differs only by scheme or trailing slash still matches.
	 */
	public function test_link_match() {
		$link = get_term_link( $this->other );
		$html = '<ul><li class="wp-block-term term-' . $this->other . ' taxonomy-category"><a href="' . esc_url( set_url_scheme( untrailingslashit( $link ), 'https' ) ) . '">Books</a></li></ul>';
		$this->assertStringContainsString( 'aria-current="page"', CurrentTerm::mark_term( $html, get_term( $this->other ) ) );
	}

	/**
	 * A term of another taxonomy with the same ID is not marked.
	 */
	public function test_other_taxonomy() {
		$html = '<ul><li class="wp-block-term term-' . $this->other . ' post_tag taxonomy-post_tag"><a href="/x/">X</a></li></ul>';
		$this->assertSame( $html, CurrentTerm::mark_term( $html, get_term( $this->other ) ) );
	}

	/**
	 * Category photos puts a link to the term inside each Featured Category, hidden
	 * from keyboards and screen readers; other looks don't.
	 */
	public function test_photo_links() {
		$html = '<ul><li class="wp-block-term term-' . $this->other . ' category taxonomy-category"><div class="wc-block-featured-category has-background-dim"><div class="wc-block-featured-category__wrapper"></div></div><p class="wp-block-term-name"><a href="/b/">Books</a></p></li></ul>';
		$link = '<a class="purplish-terms-photo" href="' . esc_url( get_term_link( $this->other ) ) . '" tabindex="-1" aria-hidden="true"></a>';

		$this->assertStringContainsString( '<div class="wc-block-featured-category has-background-dim">' . $link . '<div class="wc-block-featured-category__wrapper">', CurrentTerm::photo_links( $html ) );
		$this->assertSame( 1, substr_count( CurrentTerm::photo_links( $html ), 'purplish-terms-photo' ) );

		$mark = new CurrentTerm();
		$this->assertStringContainsString( 'purplish-terms-photo', $mark->mark( $html, self::block( array( 'look' => 'photos' ) ) ) );
		$this->assertSame( $html, $mark->mark( $html, self::block( array( 'look' => 'tabs' ) ) ) );
	}

	/**
	 * On the archive, the filter marks the term; without the option or off
	 * an archive, nothing changes.
	 */
	public function test_mark_filter() {
		$html = self::list_of( array( $this->parent, $this->child ) );
		$mark = new CurrentTerm();

		$this->assertSame( $html, $mark->mark( $html, self::block( array( 'look' => 'tabs' ) ) ), 'Not an archive.' );

		$this->go_to( get_term_link( $this->child ) );
		$this->assertStringContainsString( 'current-term"', $mark->mark( $html, self::block( array( 'look' => 'tabs' ) ) ) );
		$this->assertStringContainsString( 'current-term"', $mark->mark( $html, self::block( array( 'current' => true ) ) ) );
		$this->assertSame( $html, $mark->mark( $html, self::block( array() ) ), 'No option.' );
	}
}

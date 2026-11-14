<?php
/**
 * Tests that readme.txt fits WordPress.org's section limits.
 *
 * @package Purplish
 */

namespace Purplish\Tests;

use Purplish\Tests\Support\ReadmeParser;
use WP_UnitTestCase;

/**
 * WordPress.org drops every word past 2,500 in the Description, and the
 * plugin page and search never see them. This keeps headroom under that.
 */
final class ReadmeTest extends WP_UnitTestCase {

	/**
	 * Most words the Description may have, under the parser's 2,500.
	 */
	const DESCRIPTION_BUDGET = 2300;

	/**
	 * The parsed readme.
	 *
	 * @var ReadmeParser
	 */
	private $readme;

	/**
	 * Parses readme.txt with Plugin Check's copy of the WordPress.org parser.
	 */
	public function set_up() {
		parent::set_up();

		$parser = WP_PLUGIN_DIR . '/plugin-check/includes/Lib/Readme/Parser.php';
		if ( ! file_exists( $parser ) ) {
			if ( getenv( 'CI' ) ) {
				$this->fail( 'Plugin Check is missing from the plugins folder, so the readme parser cannot run.' );
			}
			$this->markTestSkipped( 'Install Plugin Check to run the readme parser.' );
		}
		require_once $parser;

		$this->readme = new ReadmeParser( dirname( __DIR__, 2 ) . '/readme.txt' );
	}

	/**
	 * The Description stays under the budget.
	 */
	public function test_description_stays_under_the_budget() {
		$words = $this->readme->description_words();

		fwrite( STDERR, sprintf( "\nreadme.txt Description: %d words (budget %d, WordPress.org cut 2500)\n", $words, self::DESCRIPTION_BUDGET ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

		$this->assertLessThanOrEqual( self::DESCRIPTION_BUDGET, $words );
	}

	/**
	 * The parser trims no section.
	 */
	public function test_no_section_is_trimmed() {
		$trimmed = array_filter(
			array_keys( $this->readme->warnings ),
			static function ( $warning ) {
				return 0 === strpos( $warning, 'trimmed_section_' );
			}
		);

		$this->assertSame( array(), array_values( $trimmed ) );
	}
}

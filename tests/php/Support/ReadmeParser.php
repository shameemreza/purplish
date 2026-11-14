<?php
/**
 * The WordPress.org readme parser, with a record of the word counts it
 * checks against its limits.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Support;

use WordPress\Plugin_Check\Lib\Readme\Parser;

/**
 * Plugin Check ships the parser WordPress.org runs on every readme. This
 * keeps its logic and only notes how many words each section had before
 * the parser trimmed it.
 */
final class ReadmeParser extends Parser {

	/**
	 * Words per section, in the order the parser checks them.
	 *
	 * @var int[]
	 */
	private $section_words = array();

	/**
	 * Words in the Description, counted the way the parser counts them. The
	 * parser always checks the Description first, Other Notes included.
	 *
	 * @return int
	 */
	public function description_words() {
		return $this->section_words[0];
	}

	/**
	 * Notes the word count, then trims as the parser does.
	 *
	 * @param string     $desc   Text to trim.
	 * @param int|string $length Limit, or the name of a limit.
	 * @param string     $type   'char' or 'words'.
	 * @return string
	 */
	protected function trim_length( $desc, $length = 150, $type = 'char' ) {
		if ( 'words' === $type ) {
			$this->section_words[] = count( preg_split( '/\s+/u', trim( $desc ), -1, PREG_SPLIT_NO_EMPTY ) );
		}

		return parent::trim_length( $desc, $length, $type );
	}
}

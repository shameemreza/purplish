<?php
/**
 * Trade-in device table as CSV.
 *
 * @package Purplish
 */

namespace Purplish;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the trade-in device table as CSV, with the columns
 * brand, model, storage, low and high.
 *
 * @since 1.4.1
 */
final class TradeInCsv {

	/**
	 * Largest file the import reads, in bytes.
	 */
	const MAX_BYTES = 2097152;

	/**
	 * Reads CSV text into rows. A first row that starts with "brand" is a
	 * header and is skipped, and blank lines are passed over.
	 *
	 * @param string $csv CSV text.
	 * @return array{devices: array<int, array{brand: string, model: string, storage: string, low: float, high: float}>, skipped: int[]} Clean rows, and the line numbers that couldn't be used.
	 */
	public static function parse( $csv ) {
		$result = array(
			'devices' => array(),
			'skipped' => array(),
		);
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- A memory stream, not a file.
		$stream = fopen( 'php://memory', 'r+' );
		if ( false === $stream ) {
			return $result;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- A memory stream, not a file.
		fwrite( $stream, (string) preg_replace( '/^\xEF\xBB\xBF/', '', (string) $csv ) );
		rewind( $stream );

		$rows  = array();
		$line  = 0;
		$cells = fgetcsv( $stream, 0, ',', '"', '' );
		while ( false !== $cells ) {
			++$line;
			$cells = array_map( array( self::class, 'unguard' ), array_map( 'strval', $cells ) );
			$blank = '' === trim( implode( '', $cells ) );
			$head  = 1 === $line && 'brand' === strtolower( trim( $cells[0] ) );
			if ( ! $blank && ! $head ) {
				$device = TradeIn::clean_device( $cells );
				if ( null === $device ) {
					$result['skipped'][] = $line;
				} else {
					$rows[] = $device;
				}
			}
			$cells = fgetcsv( $stream, 0, ',', '"', '' );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- A memory stream, not a file.
		fclose( $stream );

		$result['devices'] = TradeIn::clean_devices( $rows );
		return $result;
	}

	/**
	 * The table as CSV text with a header row. A cell that a spreadsheet
	 * would read as a formula starts with an apostrophe, which the import
	 * takes off again.
	 *
	 * @param array<int, array{brand: string, model: string, storage: string, low: float, high: float}> $devices Devices.
	 * @return string
	 */
	public static function build( $devices ) {
		$cell = static function ( $value ) {
			$value = (string) $value;
			if ( '' !== $value && false !== strpos( "=+-@\t\r", $value[0] ) ) {
				$value = "'" . $value;
			}
			return '"' . str_replace( '"', '""', $value ) . '"';
		};
		$out  = "brand,model,storage,low,high\r\n";
		foreach ( $devices as $device ) {
			$out .= implode(
				',',
				array(
					$cell( $device['brand'] ),
					$cell( $device['model'] ),
					$cell( $device['storage'] ),
					wc_format_decimal( $device['low'] ),
					wc_format_decimal( $device['high'] ),
				)
			) . "\r\n";
		}
		return $out;
	}

	/**
	 * Takes off the apostrophe the export puts before a formula sign.
	 *
	 * @param string $value Cell.
	 * @return string
	 */
	public static function unguard( $value ) {
		return preg_match( "/^'[=+\-@\t\r]/", $value ) ? substr( $value, 1 ) : $value;
	}
}

<?php
/**
 * Reads the main color of a product photo.
 *
 * @package Purplish
 */

namespace Purplish\Color;

defined( 'ABSPATH' ) || exit;

/**
 * Finds the product's color in a photo, not the background's.
 *
 * The most common color in a product photo is often the wall behind the
 * product. So the detector drops the background first, using the photo's
 * border, then picks the most colorful large area that's left.
 */
final class Detector {

	/**
	 * Long side, in pixels, of the copy the detector reads.
	 */
	const SAMPLE_SIZE = 64;

	/**
	 * Width, in sample pixels, of the border that shows the background.
	 */
	const BORDER = 4;

	/**
	 * How far down the sides, as a share of the height, the background is read.
	 */
	const SIDE_SHARE = 0.6;

	/**
	 * RGB distance under which a pixel counts as background.
	 */
	const BACKGROUND_DISTANCE = 48;

	/**
	 * Saturation difference under which a close pixel still counts as
	 * background.
	 */
	const BACKGROUND_SATURATION_GAP = 0.08;

	/**
	 * Share of the border the background colors have to cover.
	 */
	const BACKGROUND_SHARE = 0.85;

	/**
	 * Pixels darker than this HSV value are shadows, not color.
	 */
	const MIN_VALUE = 0.12;

	/**
	 * Pixels less saturated than this are grey, white or black.
	 */
	const MIN_SATURATION = 0.2;

	/**
	 * Share of the winning hue bucket, most saturated first, that makes the color.
	 */
	const KEEP_SHARE = 0.75;

	/**
	 * Share of the photo's center that has to share a colorful backdrop's
	 * hue for the photo to count as a tonal shot.
	 */
	const TONAL_SHARE = 0.6;

	/**
	 * Hue distance, in degrees, under which a center pixel shares the
	 * backdrop's color.
	 */
	const TONAL_HUE_GAP = 30;

	/**
	 * Number of hue buckets around the color wheel.
	 */
	const HUE_BUCKETS = 24;

	/**
	 * The main color of an attachment's photo, read from the file on disk.
	 * Uses the medium size when it exists, else the original.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string Hex color, or an empty string when the file can't be read.
	 */
	public static function from_attachment( $attachment_id ) {
		$path = self::file_for( $attachment_id );
		return '' === $path ? '' : self::from_file( $path );
	}

	/**
	 * The file the detector reads for an attachment: the medium size when it
	 * exists on disk, else the original. Never a URL.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string Path, or an empty string when neither file is on disk.
	 */
	public static function file_for( $attachment_id ) {
		$original = get_attached_file( $attachment_id );
		if ( ! $original ) {
			return '';
		}

		$medium = image_get_intermediate_size( $attachment_id, 'medium' );
		if ( is_array( $medium ) && ! empty( $medium['file'] ) ) {
			$path = path_join( dirname( $original ), basename( $medium['file'] ) );
			if ( is_readable( $path ) ) {
				return $path;
			}
		}

		return is_readable( $original ) ? $original : '';
	}

	/**
	 * The main color of an image file.
	 *
	 * @param string $path Image file on disk.
	 * @return string Hex color, or an empty string when neither GD nor Imagick can read it.
	 */
	public static function from_file( $path ) {
		$sample = self::sample_with_gd( $path );
		if ( null === $sample ) {
			$sample = self::sample_with_imagick( $path );
		}
		if ( null === $sample ) {
			return '';
		}

		return self::from_pixels( $sample['pixels'], $sample['width'], $sample['height'] );
	}

	/**
	 * The main color of a grid of pixels. Pure, so the tests can feed it
	 * any picture.
	 *
	 * @param array<int, int[]|null> $pixels RGB triplets row by row, null for transparent pixels.
	 * @param int                    $width  Width in pixels.
	 * @param int                    $height Height in pixels.
	 * @return string Hex color, or an empty string for an empty picture.
	 */
	public static function from_pixels( $pixels, $width, $height ) {
		$background = self::background_colors( $pixels, $width, $height );

		$foreground = array();
		foreach ( $pixels as $rgb ) {
			if ( null !== $rgb && ! self::is_near_any( $rgb, $background ) ) {
				$foreground[] = $rgb;
			}
		}

		$buckets = array();
		foreach ( $foreground as $rgb ) {
			list( $hue, $saturation, $value ) = self::to_hsv( $rgb );
			if ( $value < self::MIN_VALUE || $saturation < self::MIN_SATURATION ) {
				continue;
			}
			$bucket = (int) floor( $hue / ( 360 / self::HUE_BUCKETS ) ) % self::HUE_BUCKETS;

			// Chroma, not saturation: HSV saturation rates dark skin or wood above a pastel product.
			$buckets[ $bucket ][] = array( $rgb, ( max( $rgb ) - min( $rgb ) ) / 255 );
		}

		$tonal = self::tonal_product( $pixels, $width, $height, $background );
		if ( '' !== $tonal ) {
			return $tonal;
		}

		if ( empty( $buckets ) ) {
			// A grey product on white: no colorful pixels left.
			return self::most_common( $foreground );
		}

		// Score each bucket and its two neighbors together, so a color that
		// sits on a bucket edge isn't split in half.
		$best_score  = -1.0;
		$best_bucket = 0;
		foreach ( array_keys( $buckets ) as $bucket ) {
			$score = 0.0;
			foreach ( array( -1, 0, 1 ) as $offset ) {
				$neighbor = ( $bucket + $offset + self::HUE_BUCKETS ) % self::HUE_BUCKETS;
				if ( isset( $buckets[ $neighbor ] ) ) {
					$score += self::bucket_score( $buckets[ $neighbor ] ) * ( 0 === $offset ? 1 : 0.5 );
				}
			}
			if ( $score > $best_score ) {
				$best_score  = $score;
				$best_bucket = $bucket;
			}
		}

		return self::average_of_saturated( $buckets[ $best_bucket ] );
	}

	/**
	 * The product color in a tonal shot: a colorful backdrop, and a product
	 * of the same hue, like a green sweater on a green wall. Background
	 * removal can't tell them apart, but the center of the photo, where the
	 * product usually is, is then mostly the backdrop's hue, so the color
	 * comes from those center pixels.
	 *
	 * @param array<int, int[]|null> $pixels     Pixels row by row.
	 * @param int                    $width      Width.
	 * @param int                    $height     Height.
	 * @param array<int, float[]>    $background Background colors, most common first.
	 * @return string Hex color, or an empty string when the photo isn't a tonal shot.
	 */
	private static function tonal_product( $pixels, $width, $height, $background ) {
		if ( empty( $background ) ) {
			return '';
		}

		list( $backdrop_hue, $saturation, $value ) = self::to_hsv( $background[0] );
		if ( $saturation < self::MIN_SATURATION || $value < self::MIN_VALUE ) {
			return '';
		}

		$center = array();
		$like   = array();
		// The middle of the photo: 40% to 60% across and 35% to 65% down.
		$top    = (int) ( $height * 0.35 );
		$bottom = (int) ceil( $height * 0.65 );
		$left   = (int) ( $width * 0.4 );
		$right  = (int) ceil( $width * 0.6 );
		for ( $y = $top; $y < $bottom; $y++ ) {
			for ( $x = $left; $x < $right; $x++ ) {
				$rgb = $pixels[ $y * $width + $x ] ?? null;
				if ( null === $rgb ) {
					continue;
				}
				$center[] = $rgb;

				list( $hue, $pixel_saturation, $pixel_value ) = self::to_hsv( $rgb );
				$hue_gap                                      = abs( $hue - $backdrop_hue );
				if ( $pixel_saturation >= self::MIN_SATURATION && $pixel_value >= self::MIN_VALUE && min( $hue_gap, 360 - $hue_gap ) <= self::TONAL_HUE_GAP ) {
					$like[] = array( $rgb, ( max( $rgb ) - min( $rgb ) ) / 255 );
				}
			}
		}

		if ( empty( $center ) || count( $like ) / count( $center ) < self::TONAL_SHARE ) {
			return '';
		}

		// Shadows and folds pull the plain average dark, so use the most colorful part.
		return self::average_of_saturated( $like );
	}

	/**
	 * Pixel count times average chroma.
	 *
	 * @param array<int, array{0: int[], 1: float}> $entries Pixels with their chroma.
	 * @return float
	 */
	private static function bucket_score( $entries ) {
		$total = 0.0;
		foreach ( $entries as $entry ) {
			$total += $entry[1];
		}
		return $total;
	}

	/**
	 * Average color of the most colorful part of a bucket, so skin, shade
	 * and highlights at the edge of the hue don't pull the color grey.
	 *
	 * @param array<int, array{0: int[], 1: float}> $entries Pixels with their chroma.
	 * @return string Hex color.
	 */
	private static function average_of_saturated( $entries ) {
		usort(
			$entries,
			static function ( $a, $b ) {
				return $b[1] <=> $a[1];
			}
		);
		$keep = array_slice( $entries, 0, max( 1, (int) ceil( count( $entries ) * self::KEEP_SHARE ) ) );

		$sum = array( 0, 0, 0 );
		foreach ( $keep as $entry ) {
			$sum[0] += $entry[0][0];
			$sum[1] += $entry[0][1];
			$sum[2] += $entry[0][2];
		}
		$count = count( $keep );

		return self::to_hex( array( $sum[0] / $count, $sum[1] / $count, $sum[2] / $count ) );
	}

	/**
	 * The colors of the photo's border: the most common coarse colors that
	 * together cover most of the border, each as the average of its pixels.
	 *
	 * @param array<int, int[]|null> $pixels Pixels row by row.
	 * @param int                    $width  Width.
	 * @param int                    $height Height.
	 * @return array<int, float[]> Background colors.
	 */
	private static function background_colors( $pixels, $width, $height ) {
		$border = min( self::BORDER, (int) floor( min( $width, $height ) / 4 ) );
		if ( $border < 1 ) {
			return array();
		}

		$groups = array();
		$total  = 0;
		for ( $y = 0; $y < $height; $y++ ) {
			for ( $x = 0; $x < $width; $x++ ) {
				// The top edge and the upper part of the sides: products and models
				// are often cut off at the bottom and low on the sides.
				$on_side   = ( $x < $border || $x >= $width - $border ) && $y < $height * self::SIDE_SHARE;
				$on_border = $y < $border || $on_side;
				$rgb       = $pixels[ $y * $width + $x ] ?? null;
				if ( ! $on_border || null === $rgb ) {
					continue;
				}
				// 32 levels per channel, so a slight gradient lands in a few groups.
				$key = ( $rgb[0] >> 3 ) . '-' . ( $rgb[1] >> 3 ) . '-' . ( $rgb[2] >> 3 );
				if ( ! isset( $groups[ $key ] ) ) {
					$groups[ $key ] = array( 0, 0, 0, 0 );
				}
				$groups[ $key ][0] += $rgb[0];
				$groups[ $key ][1] += $rgb[1];
				$groups[ $key ][2] += $rgb[2];
				++$groups[ $key ][3];
				++$total;
			}
		}

		if ( 0 === $total ) {
			return array();
		}

		usort(
			$groups,
			static function ( $a, $b ) {
				return $b[3] <=> $a[3];
			}
		);

		$colors  = array();
		$covered = 0;
		foreach ( $groups as $group ) {
			$colors[] = array( $group[0] / $group[3], $group[1] / $group[3], $group[2] / $group[3] );
			$covered += $group[3];
			if ( $covered / $total >= self::BACKGROUND_SHARE ) {
				break;
			}
		}

		return $colors;
	}

	/**
	 * Whether a color is close to any of a set of colors.
	 *
	 * @param int[]               $rgb    Color.
	 * @param array<int, float[]> $colors Colors to compare with.
	 * @return bool
	 */
	private static function is_near_any( $rgb, $colors ) {
		$limit      = self::BACKGROUND_DISTANCE * self::BACKGROUND_DISTANCE;
		$saturation = self::to_hsv( $rgb )[1];
		foreach ( $colors as $color ) {
			$distance = ( $rgb[0] - $color[0] ) ** 2 + ( $rgb[1] - $color[1] ) ** 2 + ( $rgb[2] - $color[2] ) ** 2;
			// Close in RGB isn't enough: a pastel product on a cream wall is close
			// in RGB but clearly more colorful.
			if ( $distance <= $limit && abs( $saturation - self::to_hsv( $color )[1] ) <= self::BACKGROUND_SATURATION_GAP ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * The most common coarse color in a set of pixels, as its average.
	 *
	 * @param array<int, int[]> $pixels Pixels.
	 * @return string Hex color, or an empty string for no pixels.
	 */
	private static function most_common( $pixels ) {
		$groups = array();
		foreach ( $pixels as $rgb ) {
			$key = ( $rgb[0] >> 4 ) . '-' . ( $rgb[1] >> 4 ) . '-' . ( $rgb[2] >> 4 );
			if ( ! isset( $groups[ $key ] ) ) {
				$groups[ $key ] = array( 0, 0, 0, 0 );
			}
			$groups[ $key ][0] += $rgb[0];
			$groups[ $key ][1] += $rgb[1];
			$groups[ $key ][2] += $rgb[2];
			++$groups[ $key ][3];
		}

		if ( empty( $groups ) ) {
			return '';
		}

		usort(
			$groups,
			static function ( $a, $b ) {
				return $b[3] <=> $a[3];
			}
		);

		return self::to_hex( array( $groups[0][0] / $groups[0][3], $groups[0][1] / $groups[0][3], $groups[0][2] / $groups[0][3] ) );
	}

	/**
	 * Hue (0 to 360), saturation and value (0 to 1) of a color.
	 *
	 * @param int[] $rgb Color.
	 * @return float[]
	 */
	private static function to_hsv( $rgb ) {
		list( $r, $g, $b ) = array( $rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255 );

		$max   = max( $r, $g, $b );
		$min   = min( $r, $g, $b );
		$delta = $max - $min;

		$hue = 0.0;
		if ( $delta > 0 ) {
			if ( $max === $r ) {
				$hue = 60 * fmod( ( $g - $b ) / $delta + 6, 6 );
			} elseif ( $max === $g ) {
				$hue = 60 * ( ( $b - $r ) / $delta + 2 );
			} else {
				$hue = 60 * ( ( $r - $g ) / $delta + 4 );
			}
		}

		return array( $hue, $max > 0 ? $delta / $max : 0.0, $max );
	}

	/**
	 * A color as #rrggbb.
	 *
	 * @param float[] $rgb Color, channels from 0 to 255.
	 * @return string
	 */
	private static function to_hex( $rgb ) {
		return sprintf( '#%02x%02x%02x', (int) round( $rgb[0] ), (int) round( $rgb[1] ), (int) round( $rgb[2] ) );
	}

	/**
	 * A small copy of the image read with GD.
	 *
	 * @param string $path Image file.
	 * @return array{pixels: array<int, int[]|null>, width: int, height: int}|null Null when GD can't read the file.
	 */
	private static function sample_with_gd( $path ) {
		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			return null;
		}

		$loaders = array(
			'image/jpeg' => 'imagecreatefromjpeg',
			'image/png'  => 'imagecreatefrompng',
			'image/gif'  => 'imagecreatefromgif',
			'image/webp' => 'imagecreatefromwebp',
			'image/avif' => 'imagecreatefromavif',
		);
		$mime    = wp_get_image_mime( $path );
		if ( ! isset( $loaders[ $mime ] ) || ! function_exists( $loaders[ $mime ] ) ) {
			return null;
		}

		$image = @$loaders[ $mime ]( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- A broken file returns false; the warning adds nothing.
		if ( ! $image ) {
			return null;
		}

		list( $width, $height ) = self::sample_dimensions( imagesx( $image ), imagesy( $image ) );

		$sample = imagecreatetruecolor( $width, $height );
		imagealphablending( $sample, false );
		imagesavealpha( $sample, true );
		imagecopyresampled( $sample, $image, 0, 0, 0, 0, $width, $height, imagesx( $image ), imagesy( $image ) );

		$pixels = array();
		for ( $y = 0; $y < $height; $y++ ) {
			for ( $x = 0; $x < $width; $x++ ) {
				$color = imagecolorat( $sample, $x, $y );
				// GD alpha runs from 0 (opaque) to 127 (clear).
				$pixels[] = ( ( $color >> 24 ) & 0x7f ) > 64
					? null
					: array( ( $color >> 16 ) & 0xff, ( $color >> 8 ) & 0xff, $color & 0xff );
			}
		}

		return array(
			'pixels' => $pixels,
			'width'  => $width,
			'height' => $height,
		);
	}

	/**
	 * A small copy of the image read with Imagick.
	 *
	 * @param string $path Image file.
	 * @return array{pixels: array<int, int[]|null>, width: int, height: int}|null Null when Imagick can't read the file.
	 */
	private static function sample_with_imagick( $path ) {
		if ( ! class_exists( 'Imagick' ) ) {
			return null;
		}

		try {
			$image = new \Imagick( $path );
			$image->setIteratorIndex( 0 );

			list( $width, $height ) = self::sample_dimensions( $image->getImageWidth(), $image->getImageHeight() );
			$image->resizeImage( $width, $height, \Imagick::FILTER_TRIANGLE, 1 );
			$values = $image->exportImagePixels( 0, 0, $width, $height, 'RGBA', \Imagick::PIXEL_CHAR );
			$image->clear();
		} catch ( \Exception $e ) {
			return null;
		}

		$pixels = array();
		$count  = count( $values );
		for ( $i = 0; $i + 3 < $count; $i += 4 ) {
			$pixels[] = $values[ $i + 3 ] < 128 ? null : array( $values[ $i ], $values[ $i + 1 ], $values[ $i + 2 ] );
		}

		return array(
			'pixels' => $pixels,
			'width'  => $width,
			'height' => $height,
		);
	}

	/**
	 * Sample size with the long side at SAMPLE_SIZE, keeping the shape.
	 *
	 * @param int $width  Original width.
	 * @param int $height Original height.
	 * @return int[] Width and height.
	 */
	private static function sample_dimensions( $width, $height ) {
		$scale = self::SAMPLE_SIZE / max( 1, $width, $height );
		if ( $scale >= 1 ) {
			return array( max( 1, $width ), max( 1, $height ) );
		}
		return array( max( 1, (int) round( $width * $scale ) ), max( 1, (int) round( $height * $scale ) ) );
	}
}

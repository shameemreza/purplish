<?php
/**
 * Pattern: Size chart with how to measure. The starting point for each
 * new size chart: a tops chart in centimeters with chest, waist and hips,
 * a note on how to take each one, and a drawn figure. Saved as a synced
 * pattern, it can be picked for products and categories.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

echo \Purplish\SizeCharts::starter(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block markup with JSON from a fixed sample.

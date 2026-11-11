<?php
/**
 * Pattern: Flagship floor. Showfloor with eight looks on the drawn figures,
 * the first thing a visitor sees on the Flagship home. The store swaps in
 * its own photos and picks each look's products.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_looks = array(
	'field-parka'    => array( __( 'Field Day', 'purplish' ), __( 'Pockets for everything, plans for nothing.', 'purplish' ) ),
	'violet-coat'    => array( __( 'Red Letter', 'purplish' ), __( 'The coat does the talking.', 'purplish' ) ),
	'trench'         => array( __( 'Soft Grey', 'purplish' ), __( 'Quiet colors, sharp shape.', 'purplish' ) ),
	'puffer'         => array( __( 'Trail Mix', 'purplish' ), __( 'City paths, mountain jacket.', 'purplish' ) ),
	'easy-sunday'    => array( __( 'Easy Sunday', 'purplish' ), __( 'Soft cotton, slow morning.', 'purplish' ) ),
	'long-coat'      => array( __( 'Night Shift', 'purplish' ), __( 'Long coat, late train.', 'purplish' ) ),
	'quilted-jacket' => array( __( 'Off Duty', 'purplish' ), __( 'Leather that never tries too hard.', 'purplish' ) ),
	'denim-set'      => array( __( 'Camel Hour', 'purplish' ), __( 'One long coat, every plan.', 'purplish' ) ),
);
?>
<!-- wp:purplish/showfloor {"metadata":{"name":"<?php echo esc_attr__( 'Flagship floor', 'purplish' ); ?>"},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<?php foreach ( $purplish_looks as $purplish_figure => $purplish_look ) : ?>
	<?php
	$purplish_attrs = serialize_block_attributes(
		array(
			'url'  => \Purplish\Blocks\Showfloor::figure( $purplish_figure ),
			'name' => $purplish_look[0],
			'line' => $purplish_look[1],
		)
	);
	?>
<!-- wp:purplish/showfloor-look <?php echo $purplish_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> /-->
<?php endforeach; ?>
<!-- /wp:purplish/showfloor -->

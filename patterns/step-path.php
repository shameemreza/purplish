<?php
/**
 * Pattern: How ordering works.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_steps = array(
	array( __( 'Order', 'purplish' ), __( 'Pick what you like and check out in a few clicks.', 'purplish' ) ),
	array( __( 'We pack it', 'purplish' ), __( 'Your order is packed and sent on its way.', 'purplish' ) ),
	array( __( 'It arrives', 'purplish' ), __( 'Your parcel lands at your door.', 'purplish' ) ),
);
?>
<!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide">
<!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo esc_html__( 'How ordering works', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:purplish/step-path -->
<?php foreach ( $purplish_steps as $purplish_step ) : ?>
<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25em"}}} -->
<h3 class="wp-block-heading" style="font-size:1.25em"><?php echo esc_html( $purplish_step[0] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html( $purplish_step[1] ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<?php endforeach; ?>
<!-- /wp:purplish/step-path -->
</div>
<!-- /wp:group -->

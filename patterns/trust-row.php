<?php
/**
 * Pattern: Trust row. Free shipping, returns, secure checkout and real
 * people, each with a small icon in the theme's accent color, between two
 * thin lines.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_line  = 'var:preset|color|theme-6';
$purplish_rule  = 'var(--wp--preset--color--theme-6)';
$purplish_items = array(
	array( 'purplish/shipping', __( 'Free shipping', 'purplish' ), __( 'On orders over $100', 'purplish' ) ),
	array( 'purplish/returns', __( '30-day returns', 'purplish' ), __( 'Free, from your door', 'purplish' ) ),
	array( 'purplish/lock', __( 'Secure checkout', 'purplish' ), __( 'Cards, Apple Pay, PayPal', 'purplish' ) ),
	array( 'core/comment', __( 'Real people', 'purplish' ), __( 'Answers within a day', 'purplish' ) ),
);
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Trust row', 'purplish' ); ?>"},"align":"full","style":{"color":{"background":"var:preset|color|theme-1"},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"margin":{"top":"0","bottom":"0"}},"border":{"top":{"color":"<?php echo esc_attr( $purplish_line ); ?>","width":"1px","style":"solid"},"bottom":{"color":"<?php echo esc_attr( $purplish_line ); ?>","width":"1px","style":"solid"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background" style="border-top-color:<?php echo esc_attr( $purplish_rule ); ?>;border-top-style:solid;border-top-width:1px;border-bottom-color:<?php echo esc_attr( $purplish_rule ); ?>;border-bottom-style:solid;border-bottom-width:1px;background-color:var(--wp--preset--color--theme-1);margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"28px","bottom":"28px","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"24px"}},"layout":{"type":"grid","columnCount":4,"minimumColumnWidth":"9rem"}} -->
<div class="wp-block-group alignwide" style="padding-top:28px;padding-right:var(--wp--preset--spacing--50);padding-bottom:28px;padding-left:var(--wp--preset--spacing--50)">
<?php foreach ( $purplish_items as $purplish_index => $purplish_item ) : ?>
	<?php if ( 0 === $purplish_index ) : ?>
<!-- wp:group {"style":{"spacing":{"padding":{"left":"16px","right":"16px"},"blockGap":"14px"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group" style="padding-right:16px;padding-left:16px">
<?php else : ?>
<!-- wp:group {"style":{"spacing":{"padding":{"left":"16px","right":"16px"},"blockGap":"14px"},"border":{"left":{"color":"<?php echo esc_attr( $purplish_line ); ?>","width":"1px","style":"solid"}}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group" style="border-left-color:<?php echo esc_attr( $purplish_rule ); ?>;border-left-style:solid;border-left-width:1px;padding-right:16px;padding-left:16px">
<?php endif; ?>
<!-- wp:icon {"icon":"<?php echo esc_attr( $purplish_item[0] ); ?>","textColor":"theme-7","style":{"dimensions":{"width":"26px"}}} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"2px"}},"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|theme-2"},"typography":{"fontStyle":"normal","fontWeight":"500"}},"fontSize":"medium"} -->
<p class="has-text-color has-medium-font-size" style="color:var(--wp--preset--color--theme-2);font-style:normal;font-weight:500"><?php echo esc_html( $purplish_item[1] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|theme-4"}},"fontSize":"small"} -->
<p class="has-text-color has-small-font-size" style="color:var(--wp--preset--color--theme-4)"><?php echo esc_html( $purplish_item[2] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->

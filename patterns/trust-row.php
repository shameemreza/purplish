<?php
/**
 * Pattern: Trust row. Free shipping, returns, secure checkout and real
 * people, each with a small icon, between two thin lines.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_line  = 'rgba(17,17,17,0.2)';
$purplish_ink   = '#111111';
$purplish_text  = '#322f37';
$purplish_items = array(
	array( 'shipping', __( 'Free shipping', 'purplish' ), __( 'On orders over $100', 'purplish' ) ),
	array( 'returns', __( '30-day returns', 'purplish' ), __( 'Free, from your door', 'purplish' ) ),
	array( 'secure', __( 'Secure checkout', 'purplish' ), __( 'Cards, Apple Pay, PayPal', 'purplish' ) ),
	array( 'support', __( 'Real people', 'purplish' ), __( 'Answers within a day', 'purplish' ) ),
);
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Trust row', 'purplish' ); ?>"},"align":"full","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"margin":{"top":"clamp(80px, 10vw, 140px)","bottom":"0"}},"border":{"top":{"color":"<?php echo esc_attr( $purplish_line ); ?>","width":"1px","style":"solid"},"bottom":{"color":"<?php echo esc_attr( $purplish_line ); ?>","width":"1px","style":"solid"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="border-top-color:<?php echo esc_attr( $purplish_line ); ?>;border-top-style:solid;border-top-width:1px;border-bottom-color:<?php echo esc_attr( $purplish_line ); ?>;border-bottom-style:solid;border-bottom-width:1px;margin-top:clamp(80px, 10vw, 140px);margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"28px","bottom":"28px","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"24px"}},"layout":{"type":"grid","columnCount":4,"minimumColumnWidth":"9rem"}} -->
<div class="wp-block-group alignwide" style="padding-top:28px;padding-right:var(--wp--preset--spacing--50);padding-bottom:28px;padding-left:var(--wp--preset--spacing--50)">
<?php foreach ( $purplish_items as $purplish_index => $purplish_item ) : ?>
	<?php if ( 0 === $purplish_index ) : ?>
<!-- wp:group {"style":{"spacing":{"padding":{"left":"16px","right":"16px"},"blockGap":"14px"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group" style="padding-right:16px;padding-left:16px">
<?php else : ?>
<!-- wp:group {"style":{"spacing":{"padding":{"left":"16px","right":"16px"},"blockGap":"14px"},"border":{"left":{"color":"<?php echo esc_attr( $purplish_line ); ?>","width":"1px","style":"solid"}}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group" style="border-left-color:<?php echo esc_attr( $purplish_line ); ?>;border-left-style:solid;border-left-width:1px;padding-right:16px;padding-left:16px">
<?php endif; ?>
<!-- wp:image {"width":"26px","height":"26px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( plugins_url( 'assets/icons/' . $purplish_item[0] . '.svg', PURPLISH_FILE ) ); ?>" alt="" style="width:26px;height:26px"/></figure>
<!-- /wp:image -->

<!-- wp:group {"style":{"spacing":{"blockGap":"2px"}},"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"color":{"text":"<?php echo esc_attr( $purplish_ink ); ?>"},"typography":{"fontStyle":"normal","fontWeight":"500"}},"fontSize":"medium"} -->
<p class="has-text-color has-medium-font-size" style="color:<?php echo esc_attr( $purplish_ink ); ?>;font-style:normal;font-weight:500"><?php echo esc_html( $purplish_item[1] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"color":{"text":"<?php echo esc_attr( $purplish_text ); ?>"}},"fontSize":"small"} -->
<p class="has-text-color has-small-font-size" style="color:<?php echo esc_attr( $purplish_text ); ?>"><?php echo esc_html( $purplish_item[2] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->

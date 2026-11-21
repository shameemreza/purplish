<?php
/**
 * Pattern: Order confirmation with next steps. The order confirmation
 * page in Purple's layout, with the Thank-you moment on the heading, the
 * receipt card for the summary, three steps for what happens next, an
 * account prompt and a help line before the order details.
 *
 * The account prompt is WooCommerce's Create Account block, so it shows
 * only where WooCommerce allows it: to a guest without an account, when
 * the store lets shoppers create one after checkout. Everyone else sees
 * the steps and the details without a gap.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_rule   = 'var:preset|color|theme-6';
$purplish_rule_v = 'var(--wp--preset--color--theme-6)';
$purplish_card   = \Purplish\Patterns::radius( '12px' );
$purplish_mark   = \Purplish\Patterns::radius( '999px' );
$purplish_steps  = array(
	array( __( 'Order confirmed', 'purplish' ), __( 'Your receipt is on its way to your inbox. Keep it handy for returns.', 'purplish' ) ),
	array( __( 'Packed and on its way', 'purplish' ), __( 'We pack your order and email you a tracking link as soon as it ships.', 'purplish' ) ),
	array( __( 'At your door', 'purplish' ), __( 'Not quite right? You can send it back for free within 30 days.', 'purplish' ) ),
);
$purplish_perks  = array(
	__( 'Track this order and see your past ones', 'purplish' ),
	__( 'Check out faster with your saved address', 'purplish' ),
	__( 'Start a return in a few clicks', 'purplish' ),
);
?>
<!-- wp:group {"tagName":"main","metadata":{"name":"<?php echo esc_attr__( 'Order confirmation', 'purplish' ); ?>"},"style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1060px"}} -->
<main class="wp-block-group" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:woocommerce/order-confirmation-status {"purplish":{"moment":true}} /-->

<!-- wp:woocommerce/order-confirmation-summary {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"purplish":{"receipt":true}} /-->

<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'What happens next', 'purplish' ); ?>"},"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"0"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--50);margin-bottom:0"><!-- wp:heading {"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"fontSize":"large"} -->
<h2 class="wp-block-heading has-large-font-size" style="margin-top:0;margin-bottom:0"><?php esc_html_e( 'What happens next', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"13rem"}} -->
<div class="wp-block-group">
<?php foreach ( $purplish_steps as $purplish_index => $purplish_step ) : ?>
<!-- wp:group {"style":{"border":{"top":{"color":"<?php echo esc_attr( $purplish_rule ); ?>","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"var:preset|spacing|20"},"blockGap":"var:preset|spacing|10"}},"layout":{"type":"default"}} -->
<div class="wp-block-group" style="border-top-color:<?php echo esc_attr( $purplish_rule_v ); ?>;border-top-style:solid;border-top-width:1px;padding-top:var(--wp--preset--spacing--20)"><!-- wp:group {"style":{"spacing":{"blockGap":"12px"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|theme-7"},"border":{"radius":"<?php echo esc_attr( $purplish_mark ); ?>","width":"1px","style":"solid","color":"var:preset|color|theme-7"},"spacing":{"padding":{"left":"10px","right":"10px"}},"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"600","lineHeight":"28px"}}} -->
<p class="has-border-color has-text-color" style="border-color:var(--wp--preset--color--theme-7);border-style:solid;border-width:1px;border-radius:<?php echo esc_attr( $purplish_mark ); ?>;color:var(--wp--preset--color--theme-7);padding-right:10px;padding-left:10px;font-size:14px;font-style:normal;font-weight:600;line-height:28px"><?php echo esc_html( number_format_i18n( $purplish_index + 1 ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|theme-2"},"typography":{"fontStyle":"normal","fontWeight":"500","lineHeight":"1.3"}},"fontSize":"medium"} -->
<p class="has-text-color has-medium-font-size" style="color:var(--wp--preset--color--theme-2);font-style:normal;font-weight:500;line-height:1.3"><?php echo esc_html( $purplish_step[0] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|theme-4"}},"fontSize":"small"} -->
<p class="has-text-color has-small-font-size" style="color:var(--wp--preset--color--theme-4)"><?php echo esc_html( $purplish_step[1] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:woocommerce/order-confirmation-create-account {"align":"wide","backgroundColor":"theme-5","style":{"border":{"radius":"<?php echo esc_attr( $purplish_card ); ?>"},"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"className":"purplish-create-account"} -->
<div class="wp-block-woocommerce-order-confirmation-create-account alignwide purplish-create-account has-theme-5-background-color has-background" style="border-radius:<?php echo esc_attr( $purplish_card ); ?>;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|20"}}},"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size" style="margin-top:0;margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'Save your details for next time', 'purplish' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"is-style-checkmark-list"} -->
<ul class="wp-block-list is-style-checkmark-list">
<?php foreach ( $purplish_perks as $purplish_perk ) : ?>
<!-- wp:list-item -->
<li><?php echo esc_html( $purplish_perk ); ?></li>
<!-- /wp:list-item -->
<?php endforeach; ?>
</ul>
<!-- /wp:list --></div>
<!-- /wp:woocommerce/order-confirmation-create-account -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|theme-4"},"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|50"}}},"fontSize":"small"} -->
<p class="has-text-color has-small-font-size" style="color:var(--wp--preset--color--theme-4);margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--50)"><?php esc_html_e( 'Questions about this order? Reply to your confirmation email.', 'purplish' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:woocommerce/order-confirmation-totals-wrapper {"align":"wide"} -->
<!-- wp:heading {"fontSize":"large"} -->
<h2 class="wp-block-heading has-large-font-size"><?php esc_html_e( 'Order details', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/order-confirmation-totals {"borderColor":"theme-6","textColor":"theme-2","lock":{"remove":true},"style":{"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|50"}},"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}},"border":{"width":"1px"}}} /-->
<!-- /wp:woocommerce/order-confirmation-totals-wrapper -->

<!-- wp:woocommerce/order-confirmation-downloads-wrapper {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} -->
<!-- wp:heading {"fontSize":"large"} -->
<h2 class="wp-block-heading has-large-font-size"><?php esc_html_e( 'Downloads', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/order-confirmation-downloads {"borderColor":"theme-6","lock":{"remove":true},"style":{"border":{"width":"1px"},"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|50"}}}} /-->
<!-- /wp:woocommerce/order-confirmation-downloads-wrapper -->

<!-- wp:columns {"align":"wide","className":"wc-block-order-confirmation-address-wrapper","style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"},"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns alignwide wc-block-order-confirmation-address-wrapper" style="margin-top:var(--wp--preset--spacing--50);margin-bottom:var(--wp--preset--spacing--50)"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:woocommerce/order-confirmation-shipping-wrapper {"align":"wide"} -->
<!-- wp:heading {"fontSize":"large"} -->
<h2 class="wp-block-heading has-large-font-size"><?php esc_html_e( 'Shipping address', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/order-confirmation-shipping-address {"lock":{"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}},"border":{"width":"1px","color":"<?php echo esc_attr( $purplish_rule ); ?>"}}} /-->
<!-- /wp:woocommerce/order-confirmation-shipping-wrapper --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:woocommerce/order-confirmation-billing-wrapper {"align":"wide"} -->
<!-- wp:heading {"fontSize":"large"} -->
<h2 class="wp-block-heading has-large-font-size"><?php esc_html_e( 'Billing address', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/order-confirmation-billing-address {"lock":{"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}},"border":{"width":"1px","color":"<?php echo esc_attr( $purplish_rule ); ?>"}}} /-->
<!-- /wp:woocommerce/order-confirmation-billing-wrapper --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:woocommerce/order-confirmation-additional-fields-wrapper {"align":"wide","style":{"spacing":{"padding":{"right":"0","left":"0"}}}} -->
<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Additional information', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/order-confirmation-additional-fields /-->
<!-- /wp:woocommerce/order-confirmation-additional-fields-wrapper -->

<!-- wp:woocommerce/order-confirmation-additional-information /--></main>
<!-- /wp:group -->

<?php
/**
 * Pattern: Account page with welcome. A full-height Cover with a photo,
 * and on it My Account in a Group with the Account style, a Glass panel
 * and a Glass button. The photo ships with the plugin.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_photo = plugins_url( 'assets/photos/account-welcome.jpg', PURPLISH_FILE );
?>
<!-- wp:cover {"url":"<?php echo esc_url( $purplish_photo ); ?>","dimRatio":40,"overlayColor":"theme-3","minHeight":100,"minHeightUnit":"vh","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}}} -->
<div class="wp-block-cover alignfull" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70);min-height:100vh"><span aria-hidden="true" class="wp-block-cover__background has-theme-3-background-color has-background-dim-40 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $purplish_photo ); ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'My account', 'purplish' ); ?>"},"className":"is-style-purplish-account","purplish":{"account":{"panel":"glass","fields":"soft","focus":true,"labels":"floating","button":"glass"}},"align":"wide","layout":{"type":"constrained","contentSize":"1000px"}} -->
<div class="wp-block-group alignwide is-style-purplish-account"><!-- wp:heading {"style":{"color":{"text":"#ffffff"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
<h2 class="wp-block-heading has-text-color" style="color:#ffffff;margin-bottom:var(--wp--preset--spacing--40)"><?php esc_html_e( 'Your account', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[woocommerce_my_account]
<!-- /wp:shortcode --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->

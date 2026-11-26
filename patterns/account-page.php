<?php
/**
 * Pattern: Account page. WooCommerce's My Account in a Group with the
 * Account style, and a help line under it. Made for the My account page's
 * content, which Purple's page template prints under the page title. The
 * forms come with the Purple look on, so the effects show from the start.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'My account', 'purplish' ); ?>"},"className":"is-style-purplish-account","purplish":{"account":{"fields":"soft","focus":true,"labels":"wave","switch":"slide","slide":"page","button":"bloom","feedback":true}},"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|90"},"blockGap":"var:preset|spacing|70"}},"layout":{"type":"constrained","contentSize":"1000px"}} -->
<div class="wp-block-group alignwide is-style-purplish-account" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--90)"><!-- wp:shortcode -->
[woocommerce_my_account]
<!-- /wp:shortcode -->

<!-- wp:group {"style":{"border":{"top":{"color":"var:preset|color|theme-6","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"var:preset|spacing|30"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group" style="border-top-color:var(--wp--preset--color--theme-6);border-top-style:solid;border-top-width:1px;padding-top:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|theme-4"}},"fontSize":"small"} -->
<p class="has-text-color has-small-font-size" style="color:var(--wp--preset--color--theme-4)"><?php esc_html_e( 'Questions about an order? Reply to its confirmation email.', 'purplish' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<?php
/**
 * Pattern: Cart page. The Cart block with the free shipping meter above it,
 * sticky totals and WooCommerce's cross-sells row, the Empty cart pattern in
 * its empty cart area, and the Trust row under it. Made for the Cart page's
 * content, which Purple's Cart template prints under its title, so the
 * pattern adds no title of its own.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_cart = array(
	'align'    => 'wide',
	'purplish' => array(
		'meter'  => true,
		'sticky' => true,
	),
);
?>
<!-- wp:woocommerce/cart <?php echo serialize_block_attributes( $purplish_cart ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<div class="wp-block-woocommerce-cart alignwide is-loading"><!-- wp:woocommerce/filled-cart-block -->
<div class="wp-block-woocommerce-filled-cart-block"><!-- wp:woocommerce/cart-items-block -->
<div class="wp-block-woocommerce-cart-items-block"><!-- wp:woocommerce/cart-line-items-block -->
<div class="wp-block-woocommerce-cart-line-items-block"></div>
<!-- /wp:woocommerce/cart-line-items-block -->

<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"perPage":3,"pages":1,"offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":[],"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false,"relatedBy":{"categories":true,"tags":true}},"tagName":"div","displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/cross-sells","hideControls":["filterable"],"queryContextIncludes":["collection"]} -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:heading {"style":{"spacing":{"margin":{"bottom":"1rem"}},"typography":{"textAlign":"left"}}} -->
<h2 class="wp-block-heading has-text-align-left" style="margin-bottom:1rem"><?php esc_html_e( 'You may be interested in…', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","isDescendentOfQueryLoop":true} -->
<!-- wp:woocommerce/product-sale-badge {"align":"right"} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:post-title {"isLink":true,"style":{"spacing":{"margin":{"bottom":"0.75rem","top":"0"}},"typography":{"lineHeight":"1.4","textAlign":"center"}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small"} /-->

<!-- wp:woocommerce/product-button {"textAlign":"center","isDescendentOfQueryLoop":true,"fontSize":"small"} /-->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:woocommerce/cart-items-block -->

<!-- wp:woocommerce/cart-totals-block -->
<div class="wp-block-woocommerce-cart-totals-block"><!-- wp:woocommerce/cart-order-summary-block -->
<div class="wp-block-woocommerce-cart-order-summary-block"><!-- wp:woocommerce/cart-order-summary-heading-block -->
<div class="wp-block-woocommerce-cart-order-summary-heading-block"></div>
<!-- /wp:woocommerce/cart-order-summary-heading-block -->

<!-- wp:woocommerce/cart-order-summary-coupon-form-block -->
<div class="wp-block-woocommerce-cart-order-summary-coupon-form-block"></div>
<!-- /wp:woocommerce/cart-order-summary-coupon-form-block -->

<!-- wp:woocommerce/cart-order-summary-totals-block -->
<div class="wp-block-woocommerce-cart-order-summary-totals-block"><!-- wp:woocommerce/cart-order-summary-subtotal-block -->
<div class="wp-block-woocommerce-cart-order-summary-subtotal-block"></div>
<!-- /wp:woocommerce/cart-order-summary-subtotal-block -->

<!-- wp:woocommerce/cart-order-summary-fee-block -->
<div class="wp-block-woocommerce-cart-order-summary-fee-block"></div>
<!-- /wp:woocommerce/cart-order-summary-fee-block -->

<!-- wp:woocommerce/cart-order-summary-discount-block -->
<div class="wp-block-woocommerce-cart-order-summary-discount-block"></div>
<!-- /wp:woocommerce/cart-order-summary-discount-block -->

<!-- wp:woocommerce/cart-order-summary-shipping-block -->
<div class="wp-block-woocommerce-cart-order-summary-shipping-block"></div>
<!-- /wp:woocommerce/cart-order-summary-shipping-block -->

<!-- wp:woocommerce/cart-order-summary-taxes-block -->
<div class="wp-block-woocommerce-cart-order-summary-taxes-block"></div>
<!-- /wp:woocommerce/cart-order-summary-taxes-block --></div>
<!-- /wp:woocommerce/cart-order-summary-totals-block --></div>
<!-- /wp:woocommerce/cart-order-summary-block -->

<!-- wp:woocommerce/cart-express-payment-block -->
<div class="wp-block-woocommerce-cart-express-payment-block"></div>
<!-- /wp:woocommerce/cart-express-payment-block -->

<!-- wp:woocommerce/proceed-to-checkout-block -->
<div class="wp-block-woocommerce-proceed-to-checkout-block"></div>
<!-- /wp:woocommerce/proceed-to-checkout-block -->

<!-- wp:woocommerce/cart-accepted-payment-methods-block -->
<div class="wp-block-woocommerce-cart-accepted-payment-methods-block"></div>
<!-- /wp:woocommerce/cart-accepted-payment-methods-block --></div>
<!-- /wp:woocommerce/cart-totals-block --></div>
<!-- /wp:woocommerce/filled-cart-block -->

<!-- wp:woocommerce/empty-cart-block -->
<div class="wp-block-woocommerce-empty-cart-block"><?php require __DIR__ . '/empty-cart.php'; ?></div>
<!-- /wp:woocommerce/empty-cart-block --></div>
<!-- /wp:woocommerce/cart -->

<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Under the cart', 'purplish' ); ?>"},"align":"full","style":{"spacing":{"margin":{"top":"var:preset|spacing|70","bottom":"0"},"padding":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="margin-top:var(--wp--preset--spacing--70);margin-bottom:0;padding-top:0;padding-bottom:0"><?php require __DIR__ . '/trust-row.php'; ?></div>
<!-- /wp:group -->

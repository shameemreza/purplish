<?php
/**
 * Pattern: Best sellers. The four best selling products, with the second
 * photo on hover, add to cart on the photo, and the name and price on one
 * line under each card.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

$purplish_query      = array(
	'perPage'                       => 4,
	'pages'                         => 1,
	'offset'                        => 0,
	'postType'                      => 'product',
	'order'                         => 'desc',
	'orderBy'                       => 'popularity',
	'search'                        => '',
	'exclude'                       => array(),
	'inherit'                       => false,
	'taxQuery'                      => array(),
	'isProductCollectionBlock'      => true,
	'featured'                      => false,
	'woocommerceOnSale'             => false,
	'woocommerceStockStatus'        => array( 'instock', 'outofstock', 'onbackorder' ),
	'woocommerceAttributes'         => array(),
	'woocommerceHandPickedProducts' => array(),
	'filterable'                    => false,
	'relatedBy'                     => array(
		'categories' => true,
		'tags'       => true,
	),
);
$purplish_collection = array(
	'metadata'             => array( 'name' => __( 'Best sellers', 'purplish' ) ),
	'queryId'              => 0,
	'query'                => $purplish_query,
	'tagName'              => 'div',
	'displayLayout'        => array(
		'type'          => 'flex',
		'columns'       => 4,
		'shrinkColumns' => true,
	),
	'dimensions'           => array( 'widthType' => 'fill' ),
	'collection'           => 'woocommerce/product-collection/best-sellers',
	'hideControls'         => array( 'inherit' ),
	'queryContextIncludes' => array( 'collection' ),
	'purplish'             => array(
		'overlay'     => true,
		'hoveron'     => 'all',
		'hoverphoto'  => 'second',
		'hoverbutton' => 'bag',
	),
);
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Best sellers', 'purplish' ); ?>"},"align":"full","style":{"spacing":{"padding":{"top":"clamp(64px, 9vw, 120px)","bottom":"0","left":"0","right":"0"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:clamp(64px, 9vw, 120px);padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide" style="padding-right:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:group {"style":{"spacing":{"margin":{"bottom":"clamp(24px, 3vw, 40px)"},"blockGap":"24px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group" style="margin-bottom:clamp(24px, 3vw, 40px)"><!-- wp:heading {"style":{"typography":{"letterSpacing":"-0.02em","lineHeight":"1.1"}},"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size" style="letter-spacing:-0.02em;line-height:1.1"><?php esc_html_e( 'Best sellers', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"#111111"},"typography":{"textDecoration":"underline"}}}},"fontSize":"small"} -->
<p class="has-link-color has-small-font-size"><a href="<?php echo esc_url( $purplish_shop ); ?>"><?php esc_html_e( 'Shop all best sellers', 'purplish' ); ?> &rarr;</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-collection <?php echo serialize_block_attributes( $purplish_collection ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","isDescendentOfQueryLoop":true,"scale":"cover","aspectRatio":"4/5"} -->
<!-- wp:woocommerce/product-sale-badge {"isDescendentOfQueryLoop":true,"align":"right"} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"14px"},"blockGap":"12px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"top"}} -->
<div class="wp-block-group" style="margin-top:14px"><!-- wp:post-title {"textAlign":"left","level":3,"isLink":true,"style":{"typography":{"fontStyle":"normal","fontWeight":"400"},"spacing":{"margin":{"top":"0","bottom":"0"}}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"right","style":{"color":{"text":"#322f37"}},"fontSize":"medium"} /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-button {"textAlign":"center","width":100,"isDescendentOfQueryLoop":true} /-->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

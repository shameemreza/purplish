<?php
/**
 * Pattern: Just in. The newest eight products in a four by two grid, with
 * the Unfold card effect and add to cart under each card.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_query      = array(
	'perPage'                       => 8,
	'pages'                         => 1,
	'offset'                        => 0,
	'postType'                      => 'product',
	'order'                         => 'desc',
	'orderBy'                       => 'date',
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
	'metadata'             => array( 'name' => __( 'Just in', 'purplish' ) ),
	'queryId'              => 0,
	'query'                => $purplish_query,
	'tagName'              => 'div',
	'displayLayout'        => array(
		'type'          => 'flex',
		'columns'       => 4,
		'shrinkColumns' => true,
	),
	'dimensions'           => array( 'widthType' => 'fill' ),
	'collection'           => 'woocommerce/product-collection/new-arrivals',
	'hideControls'         => array( 'inherit' ),
	'queryContextIncludes' => array( 'collection' ),
	'purplish'             => array( 'hover' => 'unfold' ),
);
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Just in', 'purplish' ); ?>"},"align":"full","style":{"spacing":{"padding":{"top":"clamp(64px, 9vw, 120px)","bottom":"0","left":"0","right":"0"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:clamp(64px, 9vw, 120px);padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide" style="padding-right:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:heading {"style":{"typography":{"letterSpacing":"-0.02em","lineHeight":"1.1"},"spacing":{"margin":{"bottom":"clamp(24px, 3vw, 40px)"}}},"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size" style="margin-bottom:clamp(24px, 3vw, 40px);letter-spacing:-0.02em;line-height:1.1"><?php esc_html_e( 'Just in', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-collection <?php echo serialize_block_attributes( $purplish_collection ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","isDescendentOfQueryLoop":true,"scale":"cover","aspectRatio":"4/5"} -->
<!-- wp:woocommerce/product-sale-badge {"isDescendentOfQueryLoop":true,"align":"right"} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:post-title {"textAlign":"left","level":3,"isLink":true,"style":{"typography":{"fontStyle":"normal","fontWeight":"400"},"spacing":{"margin":{"top":"14px","bottom":"4px"}}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"left","style":{"color":{"text":"#322f37"}},"fontSize":"medium"} /-->

<!-- wp:woocommerce/product-button {"textAlign":"center","width":100,"isDescendentOfQueryLoop":true} /-->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

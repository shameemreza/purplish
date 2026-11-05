<?php
/**
 * Pattern: Scroll lookbook storefront.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_chapters = array(
	__( 'The first look', 'purplish' ),
	__( 'The second look', 'purplish' ),
	__( 'The third look', 'purplish' ),
);
$purplish_shades   = array( '#3a3540', '#33363d', '#3d3a36' );
?>
<!-- wp:purplish/scroll-lookbook {"align":"full"} -->
<!-- wp:cover {"customOverlayColor":"#26232b","minHeight":100,"minHeightUnit":"svh","contentPosition":"bottom left","layout":{"type":"constrained"}} -->
<div class="wp-block-cover has-custom-content-position is-position-bottom-left" style="min-height:100svh"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim" style="background-color:#26232b"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading"><?php echo esc_html__( 'Shot in one afternoon', 'purplish' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Three pieces, filmed together. Add a video or a photo to each shot, then pick the products for the cards.', 'purplish' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php echo esc_html__( 'Scroll to play', 'purplish' ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->
<?php foreach ( $purplish_chapters as $purplish_index => $purplish_chapter ) : ?>

<!-- wp:cover {"customOverlayColor":"<?php echo esc_attr( $purplish_shades[ $purplish_index ] ); ?>","minHeight":100,"minHeightUnit":"svh","layout":{"type":"constrained"}} -->
<div class="wp-block-cover" style="min-height:100svh"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim" style="background-color:<?php echo esc_attr( $purplish_shades[ $purplish_index ] ); ?>"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"className":"is-chapter"} -->
<p class="is-chapter"><?php echo esc_html( $purplish_chapter ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->
<?php endforeach; ?>

<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"perPage":3,"pages":1,"offset":0,"postType":"product","order":"asc","orderBy":"post__in","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false},"tagName":"div","displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/hand-picked","hideControls":["inherit","hand-picked","filterable"],"queryContextIncludes":["collection"]} -->
<div class="wp-block-woocommerce-product-collection">
<!-- wp:woocommerce/product-template -->
<!-- wp:post-title {"level":3,"isLink":true,"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-summary {"isDescendentOfQueryLoop":true} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true} /-->

<!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true} /-->
<!-- /wp:woocommerce/product-template -->
</div>
<!-- /wp:woocommerce/product-collection -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo esc_html__( 'Three pieces. One afternoon.', 'purplish' ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->
<!-- /wp:purplish/scroll-lookbook -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo esc_html__( 'Everything from the film', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:woocommerce/product-collection {"queryId":1,"query":{"perPage":4,"pages":1,"offset":0,"postType":"product","order":"desc","orderBy":"date","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false},"tagName":"div","displayLayout":{"type":"flex","columns":4,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/new-arrivals","hideControls":["inherit","order","filterable"],"queryContextIncludes":["collection"]} -->
<div class="wp-block-woocommerce-product-collection">
<!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"isDescendentOfQueryLoop":true} -->
<!-- wp:woocommerce/product-sale-badge {"isDescendentOfQueryLoop":true,"align":"right"} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:post-title {"level":3,"isLink":true,"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true} /-->

<!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true} /-->
<!-- /wp:woocommerce/product-template -->
</div>
<!-- /wp:woocommerce/product-collection -->

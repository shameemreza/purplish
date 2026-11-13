<?php
/**
 * Pattern: Boutique shop. A dense shop page with lots of choice on one
 * screen: breadcrumbs with the product count, round category photos on a
 * soft band with a ring on the current one, a filter sidebar that can hide
 * with the active filters and Clear all on top, the Color filter as
 * swatches with names, the other filters in accordions, Sort by on the
 * right, and four columns of cards with an Add to cart bar on the photo
 * and color swatches under the price that swap the photo.
 * The collection follows the page's query, so on a category template it
 * shows that category, and its cards move when the results change.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_ink  = '#111111';
$purplish_band = '#f4f4f2';
$purplish_tile = '#f1efeb';
$purplish_bar  = '#c4161c';

/*
 * One filter in an accordion, with a small heading, a line under it and
 * a chevron. Open when `$open` is true. A closure, since the file is
 * included each time the pattern renders.
 */
$purplish_accordion = static function ( $title, $inner, $open = false ) {
	$item = $open ? ' {"openByDefault":true}' : '';
	$on   = $open ? ' is-open' : '';
	return '<!-- wp:accordion {"purplish":{"line":"draw","icon":"chevron"},"style":{"spacing":{"blockGap":"0"}}} -->
<div role="group" class="wp-block-accordion"><!-- wp:accordion-item' . $item . ' -->
<div class="wp-block-accordion-item' . $on . '"><!-- wp:accordion-heading {"level":4,"style":{"typography":{"fontSize":"0.8125rem","fontWeight":"500"},"spacing":{"padding":{"top":"0.8rem","bottom":"0.8rem"}}}} -->
<h4 class="wp-block-accordion-heading has-icon has-icon-right" style="font-size:0.8125rem;font-weight:500"><button type="button" class="wp-block-accordion-heading__toggle" style="padding-top:0.8rem;padding-bottom:0.8rem"><span class="wp-block-accordion-heading__toggle-title">' . esc_html( $title ) . '</span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h4>
<!-- /wp:accordion-heading -->

<!-- wp:accordion-panel {"style":{"spacing":{"blockGap":"0"}}} -->
<div role="region" class="wp-block-accordion-panel">' . $inner . '

<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:accordion-panel --></div>
<!-- /wp:accordion-item --></div>
<!-- /wp:accordion -->';
};

$purplish_checkbox = '<!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list -->';
$purplish_swatches = '<!-- wp:woocommerce/product-filter-chips {"purplish":{"names":true}} -->
<div class="wp-block-woocommerce-product-filter-chips wc-block-product-filter-chips"></div>
<!-- /wp:woocommerce/product-filter-chips -->';
$purplish_slider   = '<!-- wp:woocommerce/product-filter-price-slider -->
<div class="wp-block-woocommerce-product-filter-price-slider wc-block-product-filter-price-slider"></div>
<!-- /wp:woocommerce/product-filter-price-slider -->';

// Color first and open, as swatches; Size after Category. Each only when the store has the attribute.
$purplish_attribute = static function ( $name ) {
	return function_exists( 'wc_attribute_taxonomy_id_by_name' ) ? (int) wc_attribute_taxonomy_id_by_name( $name ) : 0;
};
$purplish_filters   = array();
if ( $purplish_attribute( 'pa_color' ) ) {
	$purplish_filters[] = '<!-- wp:woocommerce/product-filter-attribute {"attributeId":' . $purplish_attribute( 'pa_color' ) . ',"showCounts":true} -->
<div class="wp-block-woocommerce-product-filter-attribute">' . $purplish_accordion( __( 'Color', 'purplish' ), $purplish_swatches, true ) . '</div>
<!-- /wp:woocommerce/product-filter-attribute -->';
}
$purplish_filters[] = '<!-- wp:woocommerce/product-filter-taxonomy {"taxonomy":"product_cat"} -->
<div class="wp-block-woocommerce-product-filter-taxonomy">' . $purplish_accordion( __( 'Category', 'purplish' ), $purplish_checkbox ) . '</div>
<!-- /wp:woocommerce/product-filter-taxonomy -->';
if ( $purplish_attribute( 'pa_size' ) ) {
	$purplish_filters[] = '<!-- wp:woocommerce/product-filter-attribute {"attributeId":' . $purplish_attribute( 'pa_size' ) . '} -->
<div class="wp-block-woocommerce-product-filter-attribute">' . $purplish_accordion( __( 'Size', 'purplish' ), $purplish_checkbox ) . '</div>
<!-- /wp:woocommerce/product-filter-attribute -->';
}
$purplish_filters[] = '<!-- wp:woocommerce/product-filter-price -->
<div class="wp-block-woocommerce-product-filter-price">' . $purplish_accordion( __( 'Price', 'purplish' ), $purplish_slider ) . '</div>
<!-- /wp:woocommerce/product-filter-price -->';
$purplish_filters[] = '<!-- wp:woocommerce/product-filter-status -->
<div class="wp-block-woocommerce-product-filter-status">' . $purplish_accordion( __( 'Availability', 'purplish' ), $purplish_checkbox ) . '</div>
<!-- /wp:woocommerce/product-filter-status -->';

$purplish_count  = array(
	'purplish' => array( 'show' => 'brackets' ),
	'style'    => array(
		'color'      => array( 'text' => $purplish_ink ),
		'typography' => array( 'fontSize' => '0.8125rem' ),
	),
);
$purplish_photos = array(
	'termQuery' => array(
		'perPage'    => 9,
		'taxonomy'   => 'product_cat',
		'order'      => 'asc',
		'orderBy'    => 'name',
		'include'    => array(),
		'hideEmpty'  => true,
		'showNested' => false,
		'inherit'    => false,
	),
	'purplish'  => array(
		'current' => true,
		'look'    => 'photos',
		'shape'   => 'circle',
		'hover'   => 'ring',
	),
	'align'     => 'wide',
);

$purplish_query      = array(
	'perPage'                       => 12,
	'pages'                         => 0,
	'offset'                        => 0,
	'postType'                      => 'product',
	'order'                         => 'asc',
	'orderBy'                       => 'title',
	'search'                        => '',
	'exclude'                       => array(),
	'inherit'                       => true,
	'taxQuery'                      => array(),
	'isProductCollectionBlock'      => true,
	'featured'                      => false,
	'woocommerceOnSale'             => false,
	'woocommerceStockStatus'        => array( 'instock', 'outofstock', 'onbackorder' ),
	'woocommerceAttributes'         => array(),
	'woocommerceHandPickedProducts' => array(),
);
$purplish_collection = array(
	'metadata'      => array( 'name' => __( 'Boutique shop', 'purplish' ) ),
	'queryId'       => 0,
	'query'         => $purplish_query,
	'tagName'       => 'div',
	'displayLayout' => array(
		'type'          => 'flex',
		'columns'       => 4,
		'shrinkColumns' => true,
	),
	'dimensions'    => array( 'widthType' => 'fill' ),
	'collection'    => 'woocommerce/product-collection/product-catalog',
	'align'         => 'wide',
	'purplish'      => array(
		'overlay'    => true,
		'buttonlook' => 'bar',
		'color'      => $purplish_bar,
		'results'    => 'move',
	),
);
$purplish_image      = array(
	'showSaleBadge'           => false,
	'isDescendentOfQueryLoop' => true,
	'aspectRatio'             => '3/4',
	'scale'                   => 'cover',
	'style'                   => array(
		'color'  => array( 'background' => $purplish_tile ),
		'border' => array( 'radius' => '0px' ),
	),
);
$purplish_dots       = array(
	'look'   => 'swatches',
	'limit'  => 6,
	'swap'   => 'hover',
	'motion' => 'fade',
);
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Boutique shop', 'purplish' ); ?>"},"align":"full","className":"purplish-shop-boutique","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|80"},"margin":{"top":"0","bottom":"0"},"blockGap":"0"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull purplish-shop-boutique" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--80)"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"margin":{"bottom":"var:preset|spacing|30"},"blockGap":"0.5rem"}},"layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"center"}} -->
<div class="wp-block-group alignwide" style="margin-bottom:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:woocommerce/breadcrumbs {"style":{"typography":{"fontSize":"0.8125rem"}}} /-->

<!-- wp:woocommerce/product-results-count <?php echo serialize_block_attributes( $purplish_count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> /--></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"purplish-shop-band","style":{"color":{"background":"<?php echo esc_attr( $purplish_band ); ?>"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull purplish-shop-band has-background" style="background-color:<?php echo esc_attr( $purplish_band ); ?>;margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--50)"><!-- wp:terms-query <?php echo serialize_block_attributes( $purplish_photos ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<div class="wp-block-terms-query alignwide"><!-- wp:term-template -->
<!-- wp:woocommerce/featured-category {"dimRatio":0,"minHeight":0} -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Shop now', 'purplish' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:woocommerce/featured-category -->

<!-- wp:term-name {"isLink":true,"style":{"typography":{"fontSize":"0.75rem","textTransform":"uppercase","letterSpacing":"0.04em"}}} /-->
<!-- /wp:term-template --></div>
<!-- /wp:terms-query --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:woocommerce/product-collection <?php echo serialize_block_attributes( $purplish_collection ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<div class="wp-block-woocommerce-product-collection alignwide"><!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"},"margin":{"top":"0","bottom":"0"}}}} -->
<div class="wp-block-columns" style="margin-top:0;margin-bottom:0"><!-- wp:column {"width":"20%"} -->
<div class="wp-block-column" style="flex-basis:20%"><!-- wp:woocommerce/product-filters {"purplish":{"hide":true,"chips":true},"style":{"position":{"type":"sticky","top":"var(--wp--preset--spacing--10)"}}} -->
<div class="wp-block-woocommerce-product-filters wc-block-product-filters"><!-- wp:woocommerce/product-filter-active {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
<div class="wp-block-woocommerce-product-filter-active" style="margin-bottom:var(--wp--preset--spacing--20)"><!-- wp:woocommerce/product-filter-removable-chips -->
<div class="wp-block-woocommerce-product-filter-removable-chips wc-block-product-filter-removable-chips"></div>
<!-- /wp:woocommerce/product-filter-removable-chips -->

<!-- wp:woocommerce/product-filter-clear-button -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"wc-block-product-filter-clear-button is-style-outline"} -->
<div class="wp-block-button wc-block-product-filter-clear-button is-style-outline"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Clear all', 'purplish' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:woocommerce/product-filter-clear-button --></div>
<!-- /wp:woocommerce/product-filter-active -->

<?php echo implode( "\n\n", $purplish_filters ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the closure. ?></div>
<!-- /wp:woocommerce/product-filters --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"80%"} -->
<div class="wp-block-column" style="flex-basis:80%"><!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"},"fontSize":"small"} -->
<div class="wp-block-group has-small-font-size" style="margin-bottom:var(--wp--preset--spacing--30)"><!-- wp:woocommerce/catalog-sorting /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image <?php echo serialize_block_attributes( $purplish_image ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> /-->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"0.85rem","bottom":"0"},"blockGap":"0.35rem"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group" style="margin-top:0.85rem;margin-bottom:0"><!-- wp:post-title {"textAlign":"center","isLink":true,"style":{"typography":{"fontSize":"0.8125rem","fontStyle":"normal","fontWeight":"400","lineHeight":"1.4"},"spacing":{"margin":{"top":"0","bottom":"0"}},"elements":{"link":{"color":{"text":"inherit"}}}},"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","style":{"color":{"text":"<?php echo esc_attr( $purplish_ink ); ?>"},"typography":{"fontSize":"0.875rem","fontWeight":"600"}}} /-->

<!-- wp:purplish/color-dots <?php echo serialize_block_attributes( $purplish_dots ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true} /-->
<!-- /wp:woocommerce/product-template -->

<!-- wp:query-pagination {"purplish":{"look":"underline"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}},"layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

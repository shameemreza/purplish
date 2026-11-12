<?php
/**
 * Pattern: Atelier shop. A quiet shop page: breadcrumbs, an uppercase serif
 * title with the category's description, accordion filters on the left
 * that can fold away, Sort by on the right, three columns of tall photos
 * with a Best seller tag, cards that lift on hover with Add to cart on
 * the photo, and numbered pages.
 * The collection follows the page's query, so on a category template it
 * shows that category. The title uses Purple's Bodoni Moda; a theme
 * without it keeps its own font.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_rule  = 'color-mix(in srgb, currentcolor 14%, transparent)';

/*
 * One accordion around a filter, closed by default, with a small
 * uppercase heading and a hairline under it. A closure, since the file is included each time the pattern renders.
 */
$purplish_accordion = static function ( $title, $inner, $first = false ) use ( $purplish_rule ) {
	$pad = $first ? '"top":"0","bottom":"0.7rem"' : '"top":"0.7rem","bottom":"0.7rem"';
	$css = $first ? 'padding-top:0;padding-bottom:0.7rem' : 'padding-top:0.7rem;padding-bottom:0.7rem';
	return '<!-- wp:accordion {"style":{"spacing":{"blockGap":"0"}}} -->
<div role="group" class="wp-block-accordion"><!-- wp:accordion-item {"style":{"border":{"bottom":{"color":"' . $purplish_rule . '","width":"1px"}}}} -->
<div class="wp-block-accordion-item" style="border-bottom-color:' . $purplish_rule . ';border-bottom-width:1px"><!-- wp:accordion-heading {"level":4,"style":{"typography":{"fontSize":"0.6875rem","textTransform":"uppercase","letterSpacing":"0.05em","fontWeight":"600"},"spacing":{"padding":{' . $pad . '}}}} -->
<h4 class="wp-block-accordion-heading has-icon has-icon-right" style="font-size:0.6875rem;font-weight:600;text-transform:uppercase"><button type="button" class="wp-block-accordion-heading__toggle" style="' . $css . '"><span class="wp-block-accordion-heading__toggle-title" style="letter-spacing:0.05em">' . esc_html( $title ) . '</span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h4>
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
	'metadata'      => array( 'name' => __( 'Atelier shop', 'purplish' ) ),
	'queryId'       => 0,
	'query'         => $purplish_query,
	'tagName'       => 'div',
	'displayLayout' => array(
		'type'          => 'flex',
		'columns'       => 3,
		'shrinkColumns' => true,
	),
	'dimensions'    => array( 'widthType' => 'fill' ),
	'collection'    => 'woocommerce/product-collection/product-catalog',
	'align'         => 'wide',
	'purplish'      => array(
		'overlay'   => true,
		'hoveron'   => 'card',
		'hovercard' => 'lift',
	),
);
$purplish_title      = array(
	'type'       => 'archive',
	'showPrefix' => false,
	'align'      => 'wide',
	'fontFamily' => 'bodoni-moda',
	'style'      => array(
		'typography' => array(
			'fontSize'      => 'clamp(1.75rem, 1.35rem + 1.4vw, 2.5rem)',
			'fontStyle'     => 'normal',
			'fontWeight'    => '400',
			'textTransform' => 'uppercase',
			'letterSpacing' => '0.01em',
			'lineHeight'    => '1.1',
		),
		'spacing'    => array(
			'margin' => array(
				'top'    => 'var:preset|spacing|40',
				'bottom' => 'var:preset|spacing|20',
			),
		),
	),
);
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Atelier shop', 'purplish' ); ?>"},"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|80"},"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--80)"><!-- wp:woocommerce/breadcrumbs {"fontSize":"small"} /-->

<!-- wp:query-title <?php echo serialize_block_attributes( $purplish_title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> /-->

<!-- wp:term-description {"style":{"typography":{"fontSize":"0.75rem","textTransform":"uppercase","letterSpacing":"0.04em","lineHeight":"1.7"},"layout":{"selfStretch":"fit"},"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|50"}}}} /-->

<!-- wp:woocommerce/product-collection <?php echo serialize_block_attributes( $purplish_collection ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<div class="wp-block-woocommerce-product-collection alignwide"><!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"},"margin":{"top":"0","bottom":"0"}}}} -->
<div class="wp-block-columns" style="margin-top:0;margin-bottom:0"><!-- wp:column {"width":"20%"} -->
<div class="wp-block-column" style="flex-basis:20%"><!-- wp:woocommerce/product-filters {"purplish":{"hide":true,"chips":true},"style":{"position":{"type":"sticky","top":"var(--wp--preset--spacing--10)"}}} -->
<div class="wp-block-woocommerce-product-filters wc-block-product-filters"><!-- wp:woocommerce/product-filter-taxonomy {"taxonomy":"product_cat"} -->
<div class="wp-block-woocommerce-product-filter-taxonomy"><?php echo $purplish_accordion( __( 'Categories', 'purplish' ), $purplish_checkbox, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the closure. ?></div>
<!-- /wp:woocommerce/product-filter-taxonomy -->

<!-- wp:woocommerce/product-filter-price -->
<div class="wp-block-woocommerce-product-filter-price">
<?php
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the closure.
echo $purplish_accordion(
	__( 'Shop by price', 'purplish' ),
	'<!-- wp:woocommerce/product-filter-price-slider -->
<div class="wp-block-woocommerce-product-filter-price-slider wc-block-product-filter-price-slider"></div>
<!-- /wp:woocommerce/product-filter-price-slider -->'
);
?>
</div>
<!-- /wp:woocommerce/product-filter-price -->

<!-- wp:woocommerce/product-filter-status -->
<div class="wp-block-woocommerce-product-filter-status"><?php echo $purplish_accordion( __( 'Availability', 'purplish' ), $purplish_checkbox ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the closure. ?></div>
<!-- /wp:woocommerce/product-filter-status -->

<!-- wp:woocommerce/product-filter-active {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-woocommerce-product-filter-active" style="margin-top:var(--wp--preset--spacing--30)"><!-- wp:woocommerce/product-filter-removable-chips -->
<div class="wp-block-woocommerce-product-filter-removable-chips wc-block-product-filter-removable-chips"></div>
<!-- /wp:woocommerce/product-filter-removable-chips --></div>
<!-- /wp:woocommerce/product-filter-active --></div>
<!-- /wp:woocommerce/product-filters --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"80%","fontSize":"small"} -->
<div class="wp-block-column has-small-font-size" style="flex-basis:80%"><!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--30)"><!-- wp:woocommerce/catalog-sorting /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"isDescendentOfQueryLoop":true,"aspectRatio":"3/4","scale":"cover","style":{"border":{"radius":"0px"}}} -->
<!-- wp:purplish/shelf-tag {"rule":"bestseller","top":8} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"0.8125rem","fontStyle":"normal","fontWeight":"500","textTransform":"uppercase","letterSpacing":"0.03em","lineHeight":"1.4"},"spacing":{"margin":{"top":"0.85rem","bottom":"0.25rem"}},"elements":{"link":{"color":{"text":"inherit"}}}},"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"style":{"typography":{"fontSize":"0.875rem","fontWeight":"600"}}} /-->

<!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true} /-->
<!-- /wp:woocommerce/product-template -->

<!-- wp:query-pagination {"paginationArrow":"chevron","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}},"typography":{"fontSize":"0.8125rem","textTransform":"uppercase","letterSpacing":"0.04em"}},"layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous {"label":"<?php echo esc_attr__( 'Back', 'purplish' ); ?>"} /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next {"label":"<?php echo esc_attr__( 'Next', 'purplish' ); ?>"} /-->
<!-- /wp:query-pagination --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:group -->

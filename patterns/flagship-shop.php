<?php
/**
 * Pattern: Flagship shop. A photo-led shop page: breadcrumbs, a hero with
 * the category's photo fading into the page, the product count in brackets
 * over a big serif title and the category's description, category tabs,
 * filter dropdowns in one row with Sort by and a grid switch on the right,
 * four columns of grey tiles with the name and color dots on one line,
 * sale, new and sold out badges, Load more over a faded last row, then the
 * trust row and a promo band.
 * The collection follows the page's query, so on a category template it
 * shows that category, and the hero shows that category's photo. The
 * title uses Purple's Bodoni Moda; a theme without it keeps its own font.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_ink    = '#111111';
$purplish_body   = '#322f37';
$purplish_deep   = '#1f0342';
$purplish_tile   = '#f2f2f2';
$purplish_figure = plugins_url( 'assets/figures/brand-band.svg', PURPLISH_FILE );

/*
 * One filter in the bar: its list inside an Accordion, whose heading
 * becomes the dropdown's label. A closure, since the file is included
 * each time the pattern renders.
 */
$purplish_filter = static function ( $block, $attrs, $label, $inner ) {
	$slug = str_replace( 'woocommerce/', '', $block );
	$json = $attrs ? ' ' . wp_json_encode( $attrs ) : '';
	return '<!-- wp:' . $block . $json . ' -->
<div class="wp-block-woocommerce-' . $slug . '"><!-- wp:accordion {"purplish":{"icon":"chevron"},"style":{"spacing":{"blockGap":"0"}}} -->
<div role="group" class="wp-block-accordion"><!-- wp:accordion-item -->
<div class="wp-block-accordion-item"><!-- wp:accordion-heading {"level":4} -->
<h4 class="wp-block-accordion-heading has-icon has-icon-right"><button type="button" class="wp-block-accordion-heading__toggle"><span class="wp-block-accordion-heading__toggle-title">' . esc_html( $label ) . '</span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h4>
<!-- /wp:accordion-heading -->

<!-- wp:accordion-panel -->
<div role="region" class="wp-block-accordion-panel">' . $inner . '</div>
<!-- /wp:accordion-panel --></div>
<!-- /wp:accordion-item --></div>
<!-- /wp:accordion --></div>
<!-- /wp:' . $block . ' -->';
};

$purplish_list   = '<!-- wp:woocommerce/product-filter-checkbox-list -->
<div class="wp-block-woocommerce-product-filter-checkbox-list wc-block-product-filter-checkbox-list"></div>
<!-- /wp:woocommerce/product-filter-checkbox-list -->';
$purplish_slider = '<!-- wp:woocommerce/product-filter-price-slider -->
<div class="wp-block-woocommerce-product-filter-price-slider wc-block-product-filter-price-slider"></div>
<!-- /wp:woocommerce/product-filter-price-slider -->';

// Size and Color only when the store has those attributes.
$purplish_filters = array( $purplish_filter( 'woocommerce/product-filter-taxonomy', array( 'taxonomy' => 'product_cat' ), __( 'Category', 'purplish' ), $purplish_list ) );
foreach ( array(
	'pa_size'  => __( 'Size', 'purplish' ),
	'pa_color' => __( 'Color', 'purplish' ),
) as $purplish_name => $purplish_label ) {
	$purplish_attribute = function_exists( 'wc_attribute_taxonomy_id_by_name' ) ? (int) wc_attribute_taxonomy_id_by_name( $purplish_name ) : 0;
	if ( $purplish_attribute ) {
		$purplish_filters[] = $purplish_filter( 'woocommerce/product-filter-attribute', array( 'attributeId' => $purplish_attribute ), $purplish_label, $purplish_list );
	}
}
$purplish_filters[] = $purplish_filter( 'woocommerce/product-filter-price', array(), __( 'Price', 'purplish' ), $purplish_slider );
$purplish_filters[] = $purplish_filter( 'woocommerce/product-filter-status', array(), __( 'Availability', 'purplish' ), $purplish_list );

$purplish_hero  = array(
	'url'             => $purplish_figure,
	'dimRatio'        => 100,
	'customGradient'  => 'linear-gradient(180deg, rgba(255,255,255,0) 30%, rgba(255,255,255,0.86) 72%, rgb(255,255,255) 100%)',
	'minHeight'       => 520,
	'minHeightUnit'   => 'px',
	'contentPosition' => 'bottom left',
	'isDark'          => false,
	'align'           => 'wide',
	'purplish'        => array(
		'termphoto' => true,
		'scroll'    => 'slowzoom',
	),
	'style'           => array(
		'spacing' => array(
			'padding' => array(
				'top'    => 'var:preset|spacing|50',
				'bottom' => '0',
				'left'   => 'var:preset|spacing|50',
				'right'  => 'var:preset|spacing|50',
			),
			'margin'  => array(
				'top'    => '0',
				'bottom' => 'var:preset|spacing|50',
			),
		),
	),
);
$purplish_count = array(
	'purplish' => array(
		'show'   => 'brackets',
		'motion' => 'roll',
	),
	'style'    => array(
		'color'      => array( 'text' => $purplish_body ),
		'typography' => array(
			'fontSize'   => 'clamp(1.125rem, 1rem + 0.5vw, 1.5rem)',
			'fontWeight' => '300',
		),
	),
);
$purplish_title = array(
	'type'       => 'archive',
	'showPrefix' => false,
	'level'      => 1,
	'fontFamily' => 'bodoni-moda',
	'style'      => array(
		'color'      => array( 'text' => $purplish_ink ),
		'typography' => array(
			'fontSize'      => 'clamp(2.75rem, 1.9rem + 3.4vw, 5rem)',
			'fontStyle'     => 'normal',
			'fontWeight'    => '400',
			'letterSpacing' => '-0.02em',
			'lineHeight'    => '1',
		),
		'spacing'    => array(
			'margin' => array(
				'top'    => '0.25rem',
				'bottom' => '1rem',
			),
		),
	),
);
$purplish_tabs  = array(
	'termQuery' => array(
		'perPage'    => 8,
		'taxonomy'   => 'product_cat',
		'order'      => 'asc',
		'orderBy'    => 'name',
		'include'    => array(),
		'hideEmpty'  => true,
		'showNested' => false,
		'inherit'    => false,
	),
	'purplish'  => array( 'look' => 'tabs' ),
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
	'metadata'      => array( 'name' => __( 'Flagship shop', 'purplish' ) ),
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
	'purplish'      => array(
		'overlay'   => true,
		'hoveron'   => 'card',
		'hovercard' => 'lift',
		'grid'      => '2',
	),
);
$purplish_image      = array(
	'showSaleBadge'           => false,
	'isDescendentOfQueryLoop' => true,
	'aspectRatio'             => '4/5',
	'scale'                   => 'cover',
	'style'                   => array(
		'color'  => array( 'background' => $purplish_tile ),
		'border' => array( 'radius' => '0px' ),
	),
);
$purplish_badge      = array(
	'badgeContent' => 'percentage',
	'suffix'       => __( ' off', 'purplish' ),
	'purplish'     => array( 'corner' => 'top-left' ),
	'style'        => array(
		'color'      => array(
			'background' => '#c4161c',
			'text'       => '#ffffff',
		),
		'border'     => array(
			'radius' => '0px',
			'width'  => '0px',
		),
		'typography' => array(
			'fontSize'      => '0.6875rem',
			'fontWeight'    => '500',
			'textTransform' => 'uppercase',
			'letterSpacing' => '0.04em',
		),
	),
);
$purplish_promo      = array(
	'url'             => $purplish_figure,
	'dimRatio'        => 100,
	'customGradient'  => 'linear-gradient(90deg, rgba(31,3,66,0.92) 0%, rgba(31,3,66,0.72) 55%, rgba(31,3,66,0.35) 100%)',
	'focalPoint'      => array(
		'x' => 0.72,
		'y' => 0.5,
	),
	'minHeight'       => 420,
	'minHeightUnit'   => 'px',
	'contentPosition' => 'center left',
	'isDark'          => true,
	'align'           => 'full',
	'style'           => array(
		'spacing' => array(
			'padding' => array(
				'top'    => 'clamp(40px, 6vw, 80px)',
				'bottom' => 'clamp(40px, 6vw, 80px)',
				'left'   => 'max(var(--wp--preset--spacing--50), calc((100% - var(--wp--style--global--wide-size, 1340px)) / 2 + var(--wp--preset--spacing--50)))',
				'right'  => 'var:preset|spacing|50',
			),
			'margin'  => array(
				'top'    => '0',
				'bottom' => '0',
			),
		),
	),
	'layout'          => array(
		'type'           => 'constrained',
		'contentSize'    => '640px',
		'justifyContent' => 'left',
	),
);
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Flagship shop', 'purplish' ); ?>"},"align":"full","className":"purplish-shop-flagship","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"0"},"margin":{"top":"0","bottom":"0"},"blockGap":"0"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull purplish-shop-flagship" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--30);padding-bottom:0"><!-- wp:woocommerce/breadcrumbs {"align":"wide","fontSize":"small","style":{"spacing":{"padding":{"left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"margin":{"bottom":"var:preset|spacing|20"}}}} /-->

<!-- wp:cover <?php echo serialize_block_attributes( $purplish_hero ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<div class="wp-block-cover alignwide is-light has-custom-content-position is-position-bottom-left" style="margin-top:0;margin-bottom:var(--wp--preset--spacing--50);padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:0;padding-left:var(--wp--preset--spacing--50);min-height:520px"><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $purplish_figure ); ?>" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim wp-block-cover__gradient-background has-background-gradient" style="background:linear-gradient(180deg, rgba(255,255,255,0) 30%, rgba(255,255,255,0.86) 72%, rgb(255,255,255) 100%)"></span><div class="wp-block-cover__inner-container"><!-- wp:woocommerce/product-results-count <?php echo serialize_block_attributes( $purplish_count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> /-->

<!-- wp:query-title <?php echo serialize_block_attributes( $purplish_title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> /-->

<!-- wp:term-description {"style":{"color":{"text":"<?php echo esc_attr( $purplish_body ); ?>"},"typography":{"lineHeight":"1.6"},"spacing":{"margin":{"top":"0","bottom":"0"}}},"fontSize":"small"} /--></div></div>
<!-- /wp:cover -->

<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide" style="padding-right:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:terms-query <?php echo serialize_block_attributes( $purplish_tabs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<div class="wp-block-terms-query"><!-- wp:term-template -->
<!-- wp:term-name {"isLink":true,"fontSize":"small"} /-->
<!-- /wp:term-template --></div>
<!-- /wp:terms-query --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"align":"wide","className":"purplish-shop-results","style":{"spacing":{"padding":{"left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide purplish-shop-results" style="padding-right:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:group {"className":"purplish-shop-sorting","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"},"fontSize":"small"} -->
<div class="wp-block-group purplish-shop-sorting has-small-font-size" style="margin-bottom:var(--wp--preset--spacing--30)"><!-- wp:woocommerce/catalog-sorting /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-collection <?php echo serialize_block_attributes( $purplish_collection ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-filters {"purplish":{"layout":"bar","hover":true,"open":"grow","chips":true},"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}},"fontSize":"small"} -->
<div class="wp-block-woocommerce-product-filters wc-block-product-filters has-small-font-size" style="margin-bottom:var(--wp--preset--spacing--40)"><?php echo implode( "\n\n", $purplish_filters ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the closure. ?>

<!-- wp:woocommerce/product-filter-active -->
<div class="wp-block-woocommerce-product-filter-active"><!-- wp:woocommerce/product-filter-removable-chips -->
<div class="wp-block-woocommerce-product-filter-removable-chips wc-block-product-filter-removable-chips"></div>
<!-- /wp:woocommerce/product-filter-removable-chips --></div>
<!-- /wp:woocommerce/product-filter-active --></div>
<!-- /wp:woocommerce/product-filters -->

<!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image <?php echo serialize_block_attributes( $purplish_image ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<!-- wp:woocommerce/product-sale-badge <?php echo serialize_block_attributes( $purplish_badge ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> /-->

<!-- wp:purplish/shelf-tag {"rule":"new","days":30,"label":"<?php echo esc_attr__( 'New arrival', 'purplish' ); ?>","corner":"top-left"} /-->

<!-- wp:woocommerce/product-stock-indicator {"purplish":{"soldout":"pill","grey":true}} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"0.85rem","bottom":"0"},"blockGap":"0.2rem"}},"layout":{"type":"default"}} -->
<div class="wp-block-group" style="margin-top:0.85rem;margin-bottom:0"><!-- wp:group {"style":{"spacing":{"blockGap":"0.75rem"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:post-title {"isLink":true,"style":{"typography":{"fontSize":"0.875rem","fontStyle":"normal","fontWeight":"400","lineHeight":"1.4"},"spacing":{"margin":{"top":"0","bottom":"0"}},"elements":{"link":{"color":{"text":"inherit"}}}},"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:purplish/color-dots /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"style":{"color":{"text":"<?php echo esc_attr( $purplish_body ); ?>"},"typography":{"fontSize":"0.8125rem"}}} /--></div>
<!-- /wp:group -->

<!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true} /-->
<!-- /wp:woocommerce/product-template -->

<!-- wp:query-pagination {"purplish":{"more":"fade"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"var:preset|spacing|70"} -->
<div style="height:var(--wp--preset--spacing--70)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:pattern {"slug":"purplish/trust-row"} /-->

<!-- wp:cover <?php echo serialize_block_attributes( $purplish_promo ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<div class="wp-block-cover alignfull has-custom-content-position is-position-center-left" style="margin-top:0;margin-bottom:0;padding-top:clamp(40px, 6vw, 80px);padding-right:var(--wp--preset--spacing--50);padding-bottom:clamp(40px, 6vw, 80px);padding-left:<?php echo esc_attr( $purplish_promo['style']['spacing']['padding']['left'] ); ?>;min-height:420px"><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $purplish_figure ); ?>" style="object-position:72% 50%" data-object-fit="cover" data-object-position="72% 50%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim wp-block-cover__gradient-background has-background-gradient" style="background:linear-gradient(90deg, rgba(31,3,66,0.92) 0%, rgba(31,3,66,0.72) 55%, rgba(31,3,66,0.35) 100%)"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {"fontFamily":"bodoni-moda","style":{"typography":{"fontSize":"clamp(2.25rem, 1.5rem + 3vw, 4rem)","fontStyle":"normal","fontWeight":"400","lineHeight":"1.05","letterSpacing":"-0.02em"},"color":{"text":"#ffffff"},"spacing":{"margin":{"bottom":"16px"}}},"purplish":{"reveal":"rise"}} -->
<h2 class="wp-block-heading has-text-color has-bodoni-moda-font-family" style="color:#ffffff;margin-bottom:16px;font-size:clamp(2.25rem, 1.5rem + 3vw, 4rem);font-style:normal;font-weight:400;letter-spacing:-0.02em;line-height:1.05"><?php esc_html_e( 'Shop in store, save 20%.', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"#ebe4fa"},"spacing":{"margin":{"bottom":"32px"}}}} -->
<p class="has-text-color" style="color:#ebe4fa;margin-bottom:32px"><?php esc_html_e( 'Take 20% off selected pieces when you visit one of our stores this month.', 'purplish' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"style":{"color":{"background":"#ffffff","text":"<?php echo esc_attr( $purplish_deep ); ?>"},"elements":{"link":{"color":{"text":"<?php echo esc_attr( $purplish_deep ); ?>"}}}},"purplish":{"sweep":true}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background has-link-color wp-element-button" href="#" style="color:<?php echo esc_attr( $purplish_deep ); ?>;background-color:#ffffff"><?php esc_html_e( 'Find a store', 'purplish' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:group -->

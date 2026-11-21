<?php
/**
 * Pattern: Empty cart. Goes inside the Cart block's Empty Cart area. A free
 * shipping chip, a big heading that rises in, two buttons, four
 * category tiles that tilt on hover and the best sellers with the Spotlight
 * card effect.
 *
 * WooCommerce draws the empty cart again in the browser, and blocks built on
 * the Interactivity API stop working in there. So everything here is plain
 * links and CSS effects, and the product cards have no add to cart button.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_shop    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$purplish_popular = add_query_arg( 'orderby', 'popularity', $purplish_shop );
$purplish_accent  = '#720eec';
$purplish_rule    = 'var:preset|color|theme-6';
$purplish_glow    = 'radial-gradient(60% 50% at 50% 0%, var(--wp--preset--color--theme-5) 0%, transparent 70%)';
$purplish_fade    = 'linear-gradient(180deg, rgba(31,3,66,0) 50%, rgba(31,3,66,0.72) 100%)';
$purplish_gap     = 'clamp(56px, 7vw, 88px)';
$purplish_tiles   = array(
	array( __( 'Coats', 'purplish' ), 'long-coat' ),
	array( __( 'Jackets', 'purplish' ), 'puffer' ),
	array( __( 'Trousers', 'purplish' ), 'denim-set' ),
	array( __( 'Shoes', 'purplish' ), 'easy-sunday' ),
);

$purplish_collection   = array(
	'metadata'             => array( 'name' => __( 'Best sellers', 'purplish' ) ),
	'queryId'              => 0,
	'query'                => array(
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
		'woocommerceStockStatus'        => array( 'instock', 'onbackorder' ),
		'woocommerceAttributes'         => array(),
		'woocommerceHandPickedProducts' => array(),
		'filterable'                    => false,
		'relatedBy'                     => array(
			'categories' => true,
			'tags'       => true,
		),
	),
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
	'purplish'             => array( 'hover' => 'spotlight' ),
);
$purplish_pill         = \Purplish\Patterns::radius( '999px' );
$purplish_tile_radius  = \Purplish\Patterns::radius( '16px' );
$purplish_photo_radius = \Purplish\Patterns::radius( '14px' );
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Empty cart', 'purplish' ); ?>"},"align":"wide","style":{"color":{"gradient":"<?php echo esc_attr( $purplish_glow ); ?>"},"spacing":{"padding":{"top":"clamp(56px, 8vw, 96px)","bottom":"clamp(48px, 6vw, 80px)","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"margin":{"top":"0","bottom":"0"},"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide has-background" style="background:<?php echo esc_attr( $purplish_glow ); ?>;margin-top:0;margin-bottom:0;padding-top:clamp(56px, 8vw, 96px);padding-right:var(--wp--preset--spacing--50);padding-bottom:clamp(48px, 6vw, 80px);padding-left:var(--wp--preset--spacing--50)"><!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group"><!-- wp:group {"style":{"color":{"background":"var:preset|color|theme-1"},"border":{"radius":"<?php echo esc_attr( $purplish_pill ); ?>","width":"1px","style":"solid","color":"var:preset|color|theme-6"},"typography":{"lineHeight":"0"},"spacing":{"padding":{"top":"6px","bottom":"6px","left":"12px","right":"14px"},"blockGap":"8px"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group has-border-color has-background" style="border-color:var(--wp--preset--color--theme-6);border-style:solid;border-width:1px;border-radius:<?php echo esc_attr( $purplish_pill ); ?>;background-color:var(--wp--preset--color--theme-1);padding-top:6px;padding-right:14px;padding-bottom:6px;padding-left:12px;line-height:0"><!-- wp:icon {"icon":"purplish/shipping","textColor":"theme-7","style":{"dimensions":{"width":"18px"}}} /-->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|theme-7"},"typography":{"fontSize":"13px","lineHeight":"1.4"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--theme-7);font-size:13px;line-height:1.4"><?php esc_html_e( 'Free shipping on orders over $100', 'purplish' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"clamp(2.5rem, 1.5rem + 3.4vw, 4.5rem)","lineHeight":"1","letterSpacing":"-0.02em"},"spacing":{"margin":{"top":"20px","bottom":"12px"}}},"purplish":{"reveal":"rise"}} -->
<h2 class="wp-block-heading has-text-align-center" style="margin-top:20px;margin-bottom:12px;font-size:clamp(2.5rem, 1.5rem + 3.4vw, 4.5rem);letter-spacing:-0.02em;line-height:1"><?php esc_html_e( 'Your bag is empty', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"color":{"text":"var:preset|color|theme-4"},"typography":{"fontSize":"17px"}}} -->
<p class="has-text-align-center has-text-color" style="color:var(--wp--preset--color--theme-4);font-size:17px"><?php esc_html_e( 'Nothing in here yet. The new season is already on the floor.', 'purplish' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"28px"},"blockGap":"12px"}},"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons" style="margin-top:28px"><!-- wp:button {"style":{"border":{"radius":"<?php echo esc_attr( $purplish_pill ); ?>"},"spacing":{"padding":{"top":"15px","bottom":"15px","left":"28px","right":"28px"}}},"purplish":{"sweep":true}} -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $purplish_shop ); ?>" style="border-radius:<?php echo esc_attr( $purplish_pill ); ?>;padding-top:15px;padding-right:28px;padding-bottom:15px;padding-left:28px"><?php esc_html_e( 'Shop new in', 'purplish' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"style":{"color":{"background":"var:preset|color|theme-1","text":"var:preset|color|theme-2"},"border":{"radius":"<?php echo esc_attr( $purplish_pill ); ?>","width":"1px","style":"solid","color":"var:preset|color|theme-2"},"spacing":{"padding":{"top":"14px","bottom":"14px","left":"27px","right":"27px"}}},"purplish":{"glow":true,"color":"<?php echo esc_attr( $purplish_accent ); ?>"}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background has-border-color wp-element-button" href="<?php echo esc_url( $purplish_popular ); ?>" style="border-color:var(--wp--preset--color--theme-2);border-style:solid;border-width:1px;border-radius:<?php echo esc_attr( $purplish_pill ); ?>;color:var(--wp--preset--color--theme-2);background-color:var(--wp--preset--color--theme-1);padding-top:14px;padding-right:27px;padding-bottom:14px;padding-left:27px"><?php esc_html_e( 'See best sellers', 'purplish' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Start with a category', 'purplish' ); ?>"},"style":{"spacing":{"margin":{"top":"<?php echo esc_attr( $purplish_gap ); ?>"},"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group" style="margin-top:<?php echo esc_attr( $purplish_gap ); ?>"><?php echo \Purplish\Patterns::section_head( __( 'Start with a category', 'purplish' ), '', $purplish_shop, __( 'All categories', 'purplish' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in section_head(). ?>

<!-- wp:group {"style":{"spacing":{"blockGap":"16px"}},"layout":{"type":"grid","columnCount":4,"minimumColumnWidth":"9rem"}} -->
<div class="wp-block-group">
<?php foreach ( $purplish_tiles as $purplish_tile ) : ?>
	<?php
	$purplish_url   = \Purplish\Blocks\Showfloor::figure( $purplish_tile[1] );
	$purplish_attrs = serialize_block_attributes(
		array(
			'url'             => $purplish_url,
			'dimRatio'        => 100,
			'customGradient'  => $purplish_fade,
			'focalPoint'      => array(
				'x' => 0.5,
				'y' => 0.3,
			),
			'contentPosition' => 'bottom left',
			'isDark'          => true,
			'style'           => array(
				'dimensions' => array( 'aspectRatio' => '3/4' ),
				'border'     => array(
					'radius' => $purplish_tile_radius,
					'width'  => '1px',
					'style'  => 'solid',
					'color'  => $purplish_rule,
				),
			),
			'layout'          => array( 'type' => 'default' ),
			'purplish'        => array( 'hover' => 'tilt' ),
		)
	);
	?>
<!-- wp:cover <?php echo $purplish_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<div class="wp-block-cover has-border-color has-custom-content-position is-position-bottom-left" style="border-color:var(--wp--preset--color--theme-6);border-style:solid;border-width:1px;border-radius:<?php echo esc_attr( $purplish_tile_radius ); ?>"><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $purplish_url ); ?>" style="object-position:50% 30%" data-object-fit="cover" data-object-position="50% 30%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim wp-block-cover__gradient-background has-background-gradient" style="background:<?php echo esc_attr( $purplish_fade ); ?>"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"#ffffff"},"typography":{"textDecoration":"none"}}}},"fontSize":"large"} -->
<p class="has-link-color has-large-font-size"><a href="<?php echo esc_url( $purplish_shop ); ?>"><?php echo esc_html( $purplish_tile[0] ); ?> &rarr;</a></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Best sellers', 'purplish' ); ?>"},"style":{"spacing":{"margin":{"top":"<?php echo esc_attr( $purplish_gap ); ?>"},"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group" style="margin-top:<?php echo esc_attr( $purplish_gap ); ?>"><?php echo \Purplish\Patterns::section_head( __( 'Best sellers', 'purplish' ), '', $purplish_popular, __( 'Shop all', 'purplish' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in section_head(). ?>

<!-- wp:woocommerce/product-collection <?php echo serialize_block_attributes( $purplish_collection ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","isDescendentOfQueryLoop":true,"scale":"cover","aspectRatio":"4/5","style":{"border":{"radius":"<?php echo esc_attr( $purplish_photo_radius ); ?>"}}} /-->

<!-- wp:post-title {"textAlign":"left","level":3,"isLink":true,"style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"spacing":{"margin":{"top":"12px","bottom":"2px"}}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"left","style":{"color":{"text":"var:preset|color|theme-4"}},"fontSize":"small"} /-->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

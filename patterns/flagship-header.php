<?php
/**
 * Pattern: Flagship header. A note bar, then a bar with the menu on the
 * left, the site title in the middle and search, account and the cart on
 * the right. The bar condenses into a floating pill on scroll, and Shop is
 * a Wardrobe panel with a look card for each of its links.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_shop  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$purplish_home  = home_url( '/' );
$purplish_deep  = '#1f0342';
$purplish_serif = 'var(--wp--preset--font-family--bodoni-moda, Didot, Georgia, serif)';
$purplish_links = array( __( 'New in', 'purplish' ), __( 'Coats', 'purplish' ), __( 'Jackets', 'purplish' ), __( 'Trousers', 'purplish' ), __( 'Shoes', 'purplish' ) );
$purplish_tiles = array(
	'long-coat' => __( 'Coats', 'purplish' ),
	'puffer'    => __( 'Jackets', 'purplish' ),
	'denim-set' => __( 'Trousers', 'purplish' ),
);
// One look card per link above, in the same order.
$purplish_looks = array(
	'violet-coat'    => array( __( 'Field Day', 'purplish' ), __( '3 pieces, $313.00', 'purplish' ) ),
	'trench'         => array( __( 'Camel Hour', 'purplish' ), __( '2 pieces, $388.00', 'purplish' ) ),
	'field-parka'    => array( __( 'Trail Mix', 'purplish' ), __( '3 pieces, $389.00', 'purplish' ) ),
	'easy-sunday'    => array( __( 'Soft Grey', 'purplish' ), __( '3 pieces, $473.00', 'purplish' ) ),
	'quilted-jacket' => array( __( 'Night Shift', 'purplish' ), __( '3 pieces, $463.00', 'purplish' ) ),
);
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Note bar', 'purplish' ); ?>"},"align":"full","style":{"color":{"background":"<?php echo esc_attr( $purplish_deep ); ?>","text":"#f2edff"},"elements":{"link":{"color":{"text":"#ffffff"}}},"spacing":{"padding":{"top":"10px","bottom":"10px","left":"var:preset|spacing|20","right":"var:preset|spacing|20"},"blockGap":"var:preset|spacing|30","margin":{"top":"0","bottom":"0"}}},"fontSize":"small","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
<div class="wp-block-group alignfull has-text-color has-background has-link-color has-small-font-size" style="color:#f2edff;background-color:<?php echo esc_attr( $purplish_deep ); ?>;margin-top:0;margin-bottom:0;padding-top:10px;padding-right:var(--wp--preset--spacing--20);padding-bottom:10px;padding-left:var(--wp--preset--spacing--20)"><!-- wp:paragraph -->
<p><?php esc_html_e( 'Free shipping on orders over $100', 'purplish' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"blockVisibility":{"viewport":{"mobile":false}}}} -->
<p><?php esc_html_e( 'Free returns for 30 days', 'purplish' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="<?php echo esc_url( $purplish_shop ); ?>"><?php esc_html_e( 'Shop what\'s new', 'purplish' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Header bar', 'purplish' ); ?>"},"purplish":{"condense":true},"align":"full","style":{"dimensions":{"minHeight":"76px"},"spacing":{"padding":{"top":"10px","bottom":"10px","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":null}} -->
<div class="wp-block-group alignfull" style="min-height:76px;margin-top:0;margin-bottom:0;padding-top:10px;padding-right:var(--wp--preset--spacing--50);padding-bottom:10px;padding-left:var(--wp--preset--spacing--50)"><!-- wp:navigation {"overlayMenu":"mobile","style":{"spacing":{"blockGap":"var:preset|spacing|30"},"typography":{"fontSize":"15px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left"}} -->
<!-- wp:purplish/wardrobe {"label":"<?php echo esc_attr__( 'Shop', 'purplish' ); ?>","url":"<?php echo esc_url( $purplish_shop ); ?>"} -->
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"20%"} -->
<div class="wp-block-column" style="flex-basis:20%"><!-- wp:list {"className":"purplish-wardrobe-links","style":{"typography":{"fontFamily":"<?php echo esc_attr( $purplish_serif ); ?>"}}} -->
<ul style="font-family:<?php echo esc_attr( $purplish_serif ); ?>" class="wp-block-list purplish-wardrobe-links">
<?php foreach ( $purplish_links as $purplish_link ) : ?>
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( $purplish_shop ); ?>"><?php echo esc_html( $purplish_link ); ?></a></li>
<!-- /wp:list-item -->
<?php endforeach; ?>
</ul>
<!-- /wp:list -->

<!-- wp:paragraph {"className":"purplish-wardrobe-all"} -->
<p class="purplish-wardrobe-all"><a href="<?php echo esc_url( $purplish_shop ); ?>"><?php esc_html_e( 'All clothing', 'purplish' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"blockGap":"12px"}},"layout":{"type":"grid","columnCount":3}} -->
<div class="wp-block-group">
<?php foreach ( $purplish_tiles as $purplish_figure => $purplish_name ) : ?>
<!-- wp:image {"aspectRatio":"3/4","scale":"cover","linkDestination":"custom","className":"purplish-wardrobe-tile"} -->
<figure class="wp-block-image purplish-wardrobe-tile"><a href="<?php echo esc_url( $purplish_shop ); ?>"><img src="<?php echo esc_url( \Purplish\Blocks\Showfloor::figure( $purplish_figure ) ); ?>" alt="" style="aspect-ratio:3/4;object-fit:cover"/></a><figcaption class="wp-element-caption"><?php echo esc_html( $purplish_name ); ?></figcaption></figure>
<!-- /wp:image -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"26%"} -->
<div class="wp-block-column" style="flex-basis:26%">
<?php foreach ( $purplish_looks as $purplish_figure => $purplish_look ) : ?>
<!-- wp:group {"className":"purplish-wardrobe-look","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
<div class="wp-block-group purplish-wardrobe-look"><!-- wp:image {"aspectRatio":"3/4","scale":"contain","linkDestination":"none"} -->
<figure class="wp-block-image"><img src="<?php echo esc_url( \Purplish\Blocks\Showfloor::figure( $purplish_figure ) ); ?>" alt="" style="aspect-ratio:3/4;object-fit:contain"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"purplish-wardrobe-look-name","style":{"typography":{"fontFamily":"<?php echo esc_attr( $purplish_serif ); ?>"}}} -->
<p class="purplish-wardrobe-look-name" style="font-family:<?php echo esc_attr( $purplish_serif ); ?>"><?php echo esc_html( $purplish_look[0] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php echo esc_html( $purplish_look[1] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"purplish-wardrobe-look-go"} -->
<p class="purplish-wardrobe-look-go"><a href="<?php echo esc_url( $purplish_shop ); ?>"><?php esc_html_e( 'Shop the look', 'purplish' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:purplish/wardrobe -->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'New in', 'purplish' ); ?>","url":"<?php echo esc_url( $purplish_shop ); ?>","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'Lookbook', 'purplish' ); ?>","url":"<?php echo esc_url( $purplish_home ); ?>","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'Stories', 'purplish' ); ?>","url":"<?php echo esc_url( $purplish_home ); ?>","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation -->

<!-- wp:site-title {"level":0,"textAlign":"center","style":{"typography":{"fontFamily":"<?php echo esc_attr( $purplish_serif ); ?>","fontSize":"clamp(1.6rem, 1.2rem + 1vw, 2.1rem)","lineHeight":"1","letterSpacing":"-0.01em","textTransform":"none"},"elements":{"link":{"color":{"text":"<?php echo esc_attr( $purplish_deep ); ?>"}}}}} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"4px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
<div class="wp-block-group"><!-- wp:search {"label":"<?php echo esc_attr__( 'Search', 'purplish' ); ?>","showLabel":false,"buttonText":"<?php echo esc_attr__( 'Search', 'purplish' ); ?>","buttonPosition":"button-only","buttonUseIcon":true,"isSearchFieldHidden":true,"query":{"post_type":"product"},"namespace":"woocommerce/product-search","metadata":{"blockVisibility":{"viewport":{"mobile":false}}},"style":{"border":{"width":"0px","style":"none"},"color":{"background":"#ffffff00","text":"<?php echo esc_attr( $purplish_deep ); ?>"}}} /-->

<!-- wp:woocommerce/customer-account {"displayStyle":"icon_only","iconStyle":"line","iconClass":"wc-block-customer-account__account-icon","metadata":{"blockVisibility":{"viewport":{"mobile":false}}}} /-->

<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Cart pill', 'purplish' ); ?>"},"style":{"color":{"background":"<?php echo esc_attr( $purplish_deep ); ?>"},"border":{"radius":"999px"},"spacing":{"padding":{"top":"2px","bottom":"2px","left":"10px","right":"10px"}}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group has-background" style="border-radius:999px;background-color:<?php echo esc_attr( $purplish_deep ); ?>;padding-top:2px;padding-right:10px;padding-bottom:2px;padding-left:10px"><!-- wp:woocommerce/mini-cart {"purplish":{"pop":true},"miniCartIcon":"bag","iconColor":{"color":"#ffffff"},"iconColorValue":"#ffffff","productCountColor":{"color":"#ffffff"},"productCountColorValue":"#ffffff"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

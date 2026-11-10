<?php
/**
 * Pattern: Flagship phone menu. The sheet the Flagship header's menu opens
 * on phones: the site title and a close button, large links that rise in
 * one after another, and a look card. Made for a navigation overlay.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_shop  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$purplish_home  = home_url( '/' );
$purplish_deep  = '#1f0342';
$purplish_serif = 'var(--wp--preset--font-family--bodoni-moda, Didot, Georgia, serif)';
$purplish_links = array(
	__( 'New in', 'purplish' )   => $purplish_shop,
	__( 'Coats', 'purplish' )    => $purplish_shop,
	__( 'Jackets', 'purplish' )  => $purplish_shop,
	__( 'Trousers', 'purplish' ) => $purplish_shop,
	__( 'Shoes', 'purplish' )    => $purplish_shop,
	__( 'Lookbook', 'purplish' ) => $purplish_home,
);
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Phone menu', 'purplish' ); ?>"},"style":{"dimensions":{"minHeight":"100vh"},"color":{"background":"#ffffff"},"spacing":{"padding":{"bottom":"var:preset|spacing|50"},"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group has-background" style="background-color:#ffffff;min-height:100vh;padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:group {"style":{"dimensions":{"minHeight":"64px"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group" style="min-height:64px;padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--30)"><!-- wp:site-title {"level":0,"style":{"typography":{"fontFamily":"<?php echo esc_attr( $purplish_serif ); ?>","fontSize":"clamp(1.6rem, 1.2rem + 1vw, 2.1rem)","lineHeight":"1","textTransform":"none"},"elements":{"link":{"color":{"text":"<?php echo esc_attr( $purplish_deep ); ?>"}}}}} /-->

<!-- wp:navigation-overlay-close /--></div>
<!-- /wp:group -->

<!-- wp:group {"purplish":{"entrance":"fadeup"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|50"}},"layout":{"type":"default"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:navigation {"overlayMenu":"never","style":{"spacing":{"blockGap":"4px"},"typography":{"fontFamily":"<?php echo esc_attr( $purplish_serif ); ?>","fontSize":"clamp(2.2rem, 1.9rem + 1.5vw, 2.6rem)","lineHeight":"1.15"},"elements":{"link":{"color":{"text":"<?php echo esc_attr( $purplish_deep ); ?>"}}}},"layout":{"type":"flex","orientation":"vertical"}} -->
<?php foreach ( $purplish_links as $purplish_label => $purplish_url ) : ?>
<!-- wp:navigation-link {"label":"<?php echo esc_attr( $purplish_label ); ?>","url":"<?php echo esc_url( $purplish_url ); ?>","kind":"custom","isTopLevelLink":true} /-->
<?php endforeach; ?>
<!-- /wp:navigation -->

<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Look card', 'purplish' ); ?>"},"style":{"color":{"background":"#f2edff"},"border":{"radius":"16px"},"dimensions":{"minHeight":"200px"},"spacing":{"padding":{"left":"16px","right":"16px"},"blockGap":"14px"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"bottom"}} -->
<div class="wp-block-group has-background" style="border-radius:16px;background-color:#f2edff;min-height:200px;padding-right:16px;padding-left:16px"><!-- wp:image {"height":"184px","scale":"contain","linkDestination":"custom","style":{"layout":{"selfStretch":"fit"}}} -->
<figure class="wp-block-image is-resized"><a href="<?php echo esc_url( $purplish_shop ); ?>"><img src="<?php echo esc_url( \Purplish\Blocks\Showfloor::figure( 'violet-coat' ) ); ?>" alt="<?php echo esc_attr__( 'Field Day look', 'purplish' ); ?>" style="object-fit:contain;height:184px"/></a></figure>
<!-- /wp:image -->

<!-- wp:group {"style":{"spacing":{"padding":{"bottom":"18px"},"blockGap":"2px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group" style="padding-bottom:18px"><!-- wp:paragraph {"style":{"color":{"text":"<?php echo esc_attr( $purplish_deep ); ?>"},"typography":{"fontFamily":"<?php echo esc_attr( $purplish_serif ); ?>"}},"fontSize":"large"} -->
<p class="has-text-color has-large-font-size" style="color:<?php echo esc_attr( $purplish_deep ); ?>;font-family:<?php echo esc_attr( $purplish_serif ); ?>"><?php esc_html_e( 'Field Day', 'purplish' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><a href="<?php echo esc_url( $purplish_shop ); ?>"><?php esc_html_e( '3 pieces, $313.00', 'purplish' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

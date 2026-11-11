<?php
/**
 * Pattern: Flagship footer. Fit help first, then Shop, Help and Company
 * links with ring social buttons, the store name as a wordmark that rises
 * out of the floor as the footer scrolls in, and a base row with the
 * copyright, a secure checkout line and a back to top button.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_shop    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$purplish_home    = home_url( '/' );
$purplish_privacy = get_privacy_policy_url() ? get_privacy_policy_url() : $purplish_home;
$purplish_terms   = function_exists( 'wc_terms_and_conditions_page_id' ) && wc_terms_and_conditions_page_id() ? get_permalink( wc_terms_and_conditions_page_id() ) : $purplish_home;
$purplish_name    = get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : __( 'Purplish', 'purplish' );
$purplish_deep    = '#1f0342';
$purplish_text    = '#fcfbff';
$purplish_base    = '#190236';
$purplish_word    = '#420889';
$purplish_line    = '#ffffff24';
$purplish_serif   = 'var(--wp--preset--font-family--bodoni-moda, Didot, Georgia, serif)';
$purplish_columns = array(
	__( 'Shop', 'purplish' )    => array(
		__( 'New in', 'purplish' )   => $purplish_shop,
		__( 'Coats', 'purplish' )    => $purplish_shop,
		__( 'Jackets', 'purplish' )  => $purplish_shop,
		__( 'Trousers', 'purplish' ) => $purplish_shop,
		__( 'Shoes', 'purplish' )    => $purplish_shop,
	),
	__( 'Help', 'purplish' )    => array(
		__( 'Shipping', 'purplish' )      => $purplish_home,
		__( 'Returns', 'purplish' )       => $purplish_home,
		__( 'Track an order', 'purplish' ) => $purplish_home,
		__( 'Size guide', 'purplish' )    => $purplish_home,
	),
	__( 'Company', 'purplish' ) => array(
		__( 'Our story', 'purplish' ) => $purplish_home,
		__( 'Stores', 'purplish' )    => $purplish_home,
		__( 'Contact', 'purplish' )   => $purplish_home,
	),
);
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Flagship footer', 'purplish' ); ?>"},"align":"full","style":{"color":{"background":"<?php echo esc_attr( $purplish_deep ); ?>","text":"<?php echo esc_attr( $purplish_text ); ?>"},"elements":{"link":{"color":{"text":"<?php echo esc_attr( $purplish_text ); ?>"}}},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"margin":{"top":"0","bottom":"0"},"blockGap":"0"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-text-color has-background has-link-color" style="color:<?php echo esc_attr( $purplish_text ); ?>;background-color:<?php echo esc_attr( $purplish_deep ); ?>;margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Footer content', 'purplish' ); ?>"},"align":"wide","style":{"spacing":{"padding":{"left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide" style="padding-right:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:columns {"verticalAlignment":"bottom","metadata":{"name":"<?php echo esc_attr__( 'Fit help', 'purplish' ); ?>"},"style":{"spacing":{"padding":{"top":"clamp(64px, 8vw, 104px)","bottom":"clamp(40px, 5vw, 64px)"},"blockGap":{"top":"24px","left":"48px"},"margin":{"top":"0","bottom":"0"}},"border":{"bottom":{"color":"<?php echo esc_attr( $purplish_line ); ?>","width":"1px","style":"solid"}}}} -->
<div class="wp-block-columns are-vertically-aligned-bottom" style="border-bottom-color:<?php echo esc_attr( $purplish_line ); ?>;border-bottom-style:solid;border-bottom-width:1px;margin-top:0;margin-bottom:0;padding-top:clamp(64px, 8vw, 104px);padding-bottom:clamp(40px, 5vw, 64px)"><!-- wp:column {"verticalAlignment":"bottom","style":{"spacing":{"blockGap":"12px"}}} -->
<div class="wp-block-column is-vertically-aligned-bottom"><!-- wp:heading {"style":{"typography":{"fontFamily":"<?php echo esc_attr( $purplish_serif ); ?>","fontSize":"clamp(2.25rem, 1.5rem + 2.6vw, 3.75rem)","fontStyle":"normal","fontWeight":"400","lineHeight":"1.04","letterSpacing":"-0.02em"},"color":{"text":"#ffffff"},"elements":{"link":{"color":{"text":"#ffffff"}}}}} -->
<h2 class="wp-block-heading has-text-color has-link-color" style="color:#ffffff;font-family:<?php echo esc_attr( $purplish_serif ); ?>;font-size:clamp(2.25rem, 1.5rem + 2.6vw, 3.75rem);font-style:normal;font-weight:400;letter-spacing:-0.02em;line-height:1.04"><?php esc_html_e( 'Not sure about the fit?', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:group {"layout":{"type":"constrained","contentSize":"48ch","justifyContent":"left"}} -->
<div class="wp-block-group"><!-- wp:paragraph -->
<p><?php esc_html_e( 'Send us your height and usual size. A real person replies within a day with the size we’d pick.', 'purplish' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"bottom","width":"22.5rem"} -->
<div class="wp-block-column is-vertically-aligned-bottom" style="flex-basis:22.5rem"><!-- wp:group {"style":{"spacing":{"blockGap":{"top":"12px","left":"24px"}}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"style":{"color":{"background":"#ffffff","text":"<?php echo esc_attr( $purplish_deep ); ?>"},"elements":{"link":{"color":{"text":"<?php echo esc_attr( $purplish_deep ); ?>"}}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background has-link-color wp-element-button" href="<?php echo esc_url( $purplish_home ); ?>" style="color:<?php echo esc_attr( $purplish_deep ); ?>;background-color:#ffffff"><?php esc_html_e( 'Ask about a size', 'purplish' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"#ffffff"},"typography":{"textDecoration":"underline"}}}}} -->
<p class="has-link-color"><a href="<?php echo esc_url( $purplish_home ); ?>"><?php esc_html_e( 'Open the size guide', 'purplish' ); ?> &rarr;</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Links', 'purplish' ); ?>"},"style":{"spacing":{"padding":{"top":"48px","bottom":"24px"},"blockGap":"32px","margin":{"top":"0","bottom":"0"}}},"layout":{"type":"grid","columnCount":4,"minimumColumnWidth":"9rem"}} -->
<div class="wp-block-group" style="margin-top:0;margin-bottom:0;padding-top:48px;padding-bottom:24px">
<?php foreach ( $purplish_columns as $purplish_title => $purplish_links ) : ?>
<!-- wp:group {"style":{"spacing":{"blockGap":"14px"}},"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":3,"style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"color":{"text":"#ffffff"}},"fontSize":"medium"} -->
<h3 class="wp-block-heading has-text-color has-medium-font-size" style="color:#ffffff;font-style:normal;font-weight:500"><?php echo esc_html( $purplish_title ); ?></h3>
<!-- /wp:heading -->

<!-- wp:navigation {"overlayMenu":"never","customTextColor":"<?php echo esc_attr( $purplish_text ); ?>","ariaLabel":"<?php echo esc_attr( $purplish_title ); ?>","style":{"spacing":{"blockGap":"8px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<?php foreach ( $purplish_links as $purplish_label => $purplish_url ) : ?>
<!-- wp:navigation-link {"label":"<?php echo esc_attr( $purplish_label ); ?>","url":"<?php echo esc_url( $purplish_url ); ?>","kind":"custom"} /-->
<?php endforeach; ?>
<!-- /wp:navigation --></div>
<!-- /wp:group -->
<?php endforeach; ?>

<!-- wp:group {"style":{"spacing":{"blockGap":"14px"}},"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":3,"style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"color":{"text":"#ffffff"}},"fontSize":"medium"} -->
<h3 class="wp-block-heading has-text-color has-medium-font-size" style="color:#ffffff;font-style:normal;font-weight:500"><?php esc_html_e( 'Follow the floor', 'purplish' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:social-links {"customIconColor":"#ffffff","iconColorValue":"#ffffff","purplish":{"look":"ring"},"style":{"spacing":{"blockGap":{"top":"10px","left":"10px"}}}} -->
<ul class="wp-block-social-links has-icon-color"><!-- wp:social-link {"url":"#","service":"instagram"} /-->

<!-- wp:social-link {"url":"#","service":"x"} /-->

<!-- wp:social-link {"url":"#","service":"whatsapp"} /--></ul>
<!-- /wp:social-links --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:heading {"textAlign":"center","fitText":true,"purplish":{"reveal":"floor"},"metadata":{"name":"<?php echo esc_attr__( 'Wordmark', 'purplish' ); ?>"},"style":{"typography":{"fontFamily":"<?php echo esc_attr( $purplish_serif ); ?>","fontStyle":"normal","fontWeight":"400","lineHeight":"0.58","letterSpacing":"-0.03em"},"color":{"text":"<?php echo esc_attr( $purplish_word ); ?>"},"spacing":{"margin":{"top":"0.1em","bottom":"0"}}}} -->
<h2 class="wp-block-heading has-text-align-center has-text-color" style="color:<?php echo esc_attr( $purplish_word ); ?>;margin-top:0.1em;margin-bottom:0;font-family:<?php echo esc_attr( $purplish_serif ); ?>;font-style:normal;font-weight:400;letter-spacing:-0.03em;line-height:0.58"><?php echo esc_html( $purplish_name ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Base row', 'purplish' ); ?>"},"align":"full","style":{"color":{"background":"<?php echo esc_attr( $purplish_base ); ?>"},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"margin":{"top":"0","bottom":"0"}}},"fontSize":"small","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background has-small-font-size" style="background-color:<?php echo esc_attr( $purplish_base ); ?>;margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"18px","bottom":"18px","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":{"top":"12px","left":"24px"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide" style="padding-top:18px;padding-right:var(--wp--preset--spacing--50);padding-bottom:18px;padding-left:var(--wp--preset--spacing--50)"><!-- wp:group {"style":{"spacing":{"blockGap":"16px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:paragraph -->
<p>&copy; <?php echo esc_html( gmdate( 'Y' ) . ' ' . $purplish_name ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="<?php echo esc_url( $purplish_privacy ); ?>"><?php esc_html_e( 'Privacy', 'purplish' ); ?></a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="<?php echo esc_url( $purplish_terms ); ?>"><?php esc_html_e( 'Terms', 'purplish' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"8px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:image {"width":"18px","height":"18px","linkDestination":"none"} -->
<figure class="wp-block-image is-resized"><img src="<?php echo esc_url( PURPLISH_URL . 'assets/icons/lock.svg' ); ?>" alt="" style="width:18px;height:18px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Secure checkout, cards and wallets', 'purplish' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"style":{"color":{"background":"#ffffff00","text":"#ffffff"},"border":{"radius":"999px","width":"1px","color":"#ffffff42"},"spacing":{"padding":{"top":"8px","bottom":"8px","left":"16px","right":"16px"}},"elements":{"link":{"color":{"text":"#ffffff"}}}},"fontSize":"small"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background has-link-color has-small-font-size has-custom-font-size has-border-color wp-element-button" href="#" style="border-color:#ffffff42;border-width:1px;border-radius:999px;color:#ffffff;background-color:#ffffff00;padding-top:8px;padding-right:16px;padding-bottom:8px;padding-left:16px"><?php esc_html_e( 'Back to the floor', 'purplish' ); ?> &uarr;</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<?php
/**
 * Pattern: Money-back guarantee card. A stamp-style seal with the number of
 * days in the Flagship serif, the promise and three proof chips, on a soft
 * lavender card. Goes under the Checkout or Cart block. The card rises in
 * once and the seal turns a little on hover.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_serif  = 'var(--wp--preset--font-family--bodoni-moda, Didot, Georgia, serif)';
$purplish_deep   = '#1f0342';
$purplish_accent = '#720eec';
$purplish_ink    = '#111111';
$purplish_text   = '#322f37';
$purplish_ground = 'linear-gradient(135deg, #ffffff 0%, #f2edff 45%, #e6dcff 100%)';
$purplish_check  = plugins_url( 'assets/icons/check.svg', PURPLISH_FILE );
$purplish_chips  = array(
	__( 'Free return label', 'purplish' ),
	__( 'Refund in 5 days', 'purplish' ),
	__( 'Real people, within a day', 'purplish' ),
);
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Money-back guarantee', 'purplish' ); ?>"},"style":{"color":{"gradient":"<?php echo esc_attr( $purplish_ground ); ?>"},"border":{"radius":"24px"},"spacing":{"padding":{"top":"28px","bottom":"28px","left":"28px","right":"28px"},"blockGap":"20px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"},"purplish":{"entrance":"fadeup"}} -->
<div class="wp-block-group has-background" style="border-radius:24px;background:<?php echo esc_attr( $purplish_ground ); ?>;padding-top:28px;padding-right:28px;padding-bottom:28px;padding-left:28px"><!-- wp:group {"style":{"spacing":{"blockGap":"20px"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Seal', 'purplish' ); ?>"},"style":{"border":{"radius":"999px","width":"1px","style":"dashed","color":"<?php echo esc_attr( $purplish_accent ); ?>"},"dimensions":{"minHeight":"104px"},"spacing":{"padding":{"top":"6px","bottom":"6px","left":"6px","right":"6px"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"},"purplish":{"turn":true}} -->
<div class="wp-block-group has-border-color" style="border-color:<?php echo esc_attr( $purplish_accent ); ?>;border-style:dashed;border-width:1px;border-radius:999px;min-height:104px;padding-top:6px;padding-right:6px;padding-bottom:6px;padding-left:6px"><!-- wp:group {"style":{"border":{"radius":"999px","width":"1.5px","style":"solid","color":"<?php echo esc_attr( $purplish_accent ); ?>"},"dimensions":{"minHeight":"90px"},"spacing":{"padding":{"top":"0","bottom":"0","left":"18px","right":"18px"},"blockGap":"2px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"}} -->
<div class="wp-block-group has-border-color" style="border-color:<?php echo esc_attr( $purplish_accent ); ?>;border-style:solid;border-width:1.5px;border-radius:999px;min-height:90px;padding-top:0;padding-right:18px;padding-bottom:0;padding-left:18px"><!-- wp:paragraph {"style":{"color":{"text":"<?php echo esc_attr( $purplish_accent ); ?>"},"typography":{"fontFamily":"<?php echo esc_attr( $purplish_serif ); ?>","fontSize":"46px","fontStyle":"normal","fontWeight":"400","lineHeight":"1"}}} -->
<p class="has-text-color" style="color:<?php echo esc_attr( $purplish_accent ); ?>;font-family:<?php echo esc_attr( $purplish_serif ); ?>;font-size:46px;font-style:normal;font-weight:400;line-height:1"><?php echo esc_html_x( '30', 'Days in the money-back guarantee', 'purplish' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"color":{"text":"<?php echo esc_attr( $purplish_accent ); ?>"},"typography":{"fontSize":"11px","lineHeight":"1"}}} -->
<p class="has-text-color" style="color:<?php echo esc_attr( $purplish_accent ); ?>;font-size:11px;line-height:1"><?php esc_html_e( 'days', 'purplish' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"6px"},"layout":{"selfStretch":"fill"}},"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":3,"style":{"color":{"text":"<?php echo esc_attr( $purplish_deep ); ?>"},"typography":{"fontFamily":"<?php echo esc_attr( $purplish_serif ); ?>","fontSize":"24px","fontStyle":"normal","fontWeight":"500","lineHeight":"1.15"},"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<h3 class="wp-block-heading has-text-color" style="color:<?php echo esc_attr( $purplish_deep ); ?>;margin-top:0;margin-bottom:0;font-family:<?php echo esc_attr( $purplish_serif ); ?>;font-size:24px;font-style:normal;font-weight:500;line-height:1.15"><?php esc_html_e( 'Love it, or send it back', 'purplish' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"<?php echo esc_attr( $purplish_text ); ?>"},"typography":{"fontSize":"15px"}}} -->
<p class="has-text-color" style="color:<?php echo esc_attr( $purplish_text ); ?>;font-size:15px"><?php esc_html_e( 'Not the right fit? Return it within 30 days for a full refund, no questions asked.', 'purplish' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Proof', 'purplish' ); ?>"},"style":{"spacing":{"blockGap":"8px 10px"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group">
<?php foreach ( $purplish_chips as $purplish_chip ) : ?>
<!-- wp:group {"style":{"color":{"background":"#ffffff"},"border":{"radius":"999px"},"typography":{"lineHeight":"0"},"spacing":{"padding":{"top":"6px","bottom":"6px","left":"12px","right":"14px"},"blockGap":"6px"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group has-background" style="border-radius:999px;background-color:#ffffff;padding-top:6px;padding-right:14px;padding-bottom:6px;padding-left:12px;line-height:0"><!-- wp:image {"width":"14px","height":"14px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( $purplish_check ); ?>" alt="" style="width:14px;height:14px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"style":{"color":{"text":"<?php echo esc_attr( $purplish_ink ); ?>"},"typography":{"fontSize":"13px","lineHeight":"1.4"}}} -->
<p class="has-text-color" style="color:<?php echo esc_attr( $purplish_ink ); ?>;font-size:13px;line-height:1.4"><?php echo esc_html( $purplish_chip ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->

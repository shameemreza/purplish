<?php
/**
 * Pattern: Brand band. A full width Cover that opens from a pill as it
 * scrolls in, a big serif heading that rises in, one line about the store
 * and a light button with a sweep.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_shop     = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$purplish_deep     = '#1f0342';
$purplish_serif    = 'var(--wp--preset--font-family--bodoni-moda, Didot, Georgia, serif)';
// Two layers: a side fade from 782px up and a bottom fade below it, as in the
// mockup. Each stop is clamp(0%, 78200px - 10000vw, ...), which jumps between 0
// and the full value at 782px, so the switch needs no stylesheet and survives
// the CSS filter that runs for editors without unfiltered_html.
$purplish_gradient = 'linear-gradient(90deg, rgb(31,3,66) clamp(0%, 10000vw - 78100px, 38%), rgba(31,3,66,0) clamp(0%, 10000vw - 78100px, 62%)), linear-gradient(0deg, rgb(31,3,66) clamp(0%, 78200px - 10000vw, 45%), rgba(31,3,66,0) clamp(0%, 78200px - 10000vw, 62%))';
$purplish_gutter   = 'max(var(--wp--preset--spacing--50), calc((100% - var(--wp--style--global--wide-size, 1340px)) / 2 + var(--wp--preset--spacing--50)))';
$purplish_band     = array(
	'url'             => plugins_url( 'assets/figures/brand-band.svg', PURPLISH_FILE ),
	'dimRatio'        => 100,
	'customGradient'  => $purplish_gradient,
	'focalPoint'      => array(
		'x' => 0.72,
		'y' => 0.5,
	),
	'minHeight'       => 760,
	'minHeightUnit'   => 'px',
	'contentPosition' => 'bottom left',
	'isDark'          => true,
	'align'           => 'full',
	'style'           => array(
		'spacing' => array(
			'padding' => array(
				'top'    => 'clamp(32px, 6vw, 88px)',
				'bottom' => 'clamp(32px, 6vw, 88px)',
				'left'   => $purplish_gutter,
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
		'contentSize'    => '560px',
		'justifyContent' => 'left',
	),
	'purplish'        => array( 'scroll' => 'pill' ),
);
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Brand band', 'purplish' ); ?>"},"align":"full","style":{"spacing":{"padding":{"top":"clamp(56px, 7vw, 96px)","bottom":"0","left":"0","right":"0"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:clamp(56px, 7vw, 96px);padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:cover <?php echo serialize_block_attributes( $purplish_band ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<div class="wp-block-cover alignfull has-custom-content-position is-position-bottom-left" style="margin-top:0;margin-bottom:0;padding-top:clamp(32px, 6vw, 88px);padding-right:var(--wp--preset--spacing--50);padding-bottom:clamp(32px, 6vw, 88px);padding-left:<?php echo esc_attr( $purplish_gutter ); ?>;min-height:760px"><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $purplish_band['url'] ); ?>" style="object-position:72% 50%" data-object-fit="cover" data-object-position="72% 50%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim wp-block-cover__gradient-background has-background-gradient" style="background:<?php echo esc_attr( $purplish_gradient ); ?>"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {"style":{"typography":{"fontFamily":"<?php echo esc_attr( $purplish_serif ); ?>","fontSize":"clamp(2.5rem, 1.6rem + 3.6vw, 5rem)","fontStyle":"normal","fontWeight":"400","lineHeight":"1.02","letterSpacing":"-0.02em"},"color":{"text":"#ffffff"},"spacing":{"margin":{"bottom":"20px"}}},"purplish":{"reveal":"rise"}} -->
<h2 class="wp-block-heading has-text-color" style="color:#ffffff;margin-bottom:20px;font-family:<?php echo esc_attr( $purplish_serif ); ?>;font-size:clamp(2.5rem, 1.6rem + 3.6vw, 5rem);font-style:normal;font-weight:400;letter-spacing:-0.02em;line-height:1.02"><?php esc_html_e( 'Fewer pieces.', 'purplish' ); ?><br><?php esc_html_e( 'Worn for years.', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"32px"}}},"layout":{"type":"constrained","contentSize":"44ch","justifyContent":"left"}} -->
<div class="wp-block-group" style="margin-bottom:32px"><!-- wp:paragraph {"style":{"color":{"text":"#ebe4fa"}}} -->
<p class="has-text-color" style="color:#ebe4fa"><?php esc_html_e( 'Every coat is cut in small runs and kept in the range season after season, so the one you buy this autumn still matches what you add next year.', 'purplish' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"style":{"color":{"background":"#ffffff","text":"<?php echo esc_attr( $purplish_deep ); ?>"},"elements":{"link":{"color":{"text":"<?php echo esc_attr( $purplish_deep ); ?>"}}}},"purplish":{"sweep":true}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background has-link-color wp-element-button" href="<?php echo esc_url( $purplish_shop ); ?>" style="color:<?php echo esc_attr( $purplish_deep ); ?>;background-color:#ffffff"><?php esc_html_e( 'Shop the autumn edit', 'purplish' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:group -->

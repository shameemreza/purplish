<?php
/**
 * Pattern: Newsletter. A lavender floor with three drawn figures standing
 * on it, a heading, one line and a button with the glow ring. The
 * button points at the home page until the store links its signup page.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_accent  = '#720eec';
$purplish_wash    = 'linear-gradient(180deg, var(--wp--preset--color--theme-1) 0%, var(--wp--preset--color--theme-5) 100%)';
$purplish_floor   = 'radial-gradient(48% 17% at 50% 93%, var(--wp--preset--color--theme-6) 0%, transparent 100%)';
$purplish_figures = array(
	array( 'trench', 'clamp(246px, 32vw, 454px)', array( 'right' => '-7%' ) ),
	array( 'denim-set', 'clamp(308px, 40vw, 568px)', array() ),
	array( 'long-coat', 'clamp(234px, 30.4vw, 432px)', array( 'left' => '-7%' ) ),
);
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Newsletter', 'purplish' ); ?>"},"align":"full","style":{"color":{"gradient":"<?php echo esc_attr( $purplish_wash ); ?>","text":"var:preset|color|theme-4"},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-text-color has-background" style="color:var(--wp--preset--color--theme-4);background:<?php echo esc_attr( $purplish_wash ); ?>;margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:columns {"verticalAlignment":"bottom","align":"wide","style":{"spacing":{"padding":{"left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":{"top":"0","left":"72px"},"margin":{"top":"0","bottom":"0"}}},"purplish":{"entrance":"fadeup"}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-bottom" style="margin-top:0;margin-bottom:0;padding-right:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:column {"verticalAlignment":"center","width":"45%","style":{"spacing":{"padding":{"top":"clamp(48px, 6vw, 80px)","bottom":"clamp(8px, 6vw, 80px)"},"blockGap":"0"}}} -->
<div class="wp-block-column is-vertically-aligned-center" style="padding-top:clamp(48px, 6vw, 80px);padding-bottom:clamp(8px, 6vw, 80px);flex-basis:45%"><!-- wp:heading {"style":{"typography":{"fontSize":"clamp(2.5rem, 1.6rem + 3.2vw, 4.5rem)","lineHeight":"1.02","letterSpacing":"-0.02em"},"spacing":{"margin":{"top":"0","bottom":"20px"}}}} -->
<h2 class="wp-block-heading" style="margin-top:0;margin-bottom:20px;font-size:clamp(2.5rem, 1.6rem + 3.2vw, 4.5rem);letter-spacing:-0.02em;line-height:1.02"><?php esc_html_e( 'New looks land here first', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"0","bottom":"32px"}}},"layout":{"type":"constrained","contentSize":"42ch","justifyContent":"left"}} -->
<div class="wp-block-group" style="margin-top:0;margin-bottom:32px"><!-- wp:paragraph -->
<p><?php esc_html_e( 'One letter when a new edit reaches the floor, with the pieces and prices before anyone else sees them. Twice a month at most.', 'purplish' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":{"top":"12px","left":"20px"}}},"layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"purplish":{"glow":true,"color":"<?php echo esc_attr( $purplish_accent ); ?>"}} -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Join the list', 'purplish' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|theme-4"}},"fontSize":"small"} -->
<p class="has-text-color has-small-font-size" style="color:var(--wp--preset--color--theme-4)"><?php esc_html_e( 'Leave any time with one click.', 'purplish' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"bottom","width":"55%"} -->
<div class="wp-block-column is-vertically-aligned-bottom" style="flex-basis:55%"><!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Floor', 'purplish' ); ?>"},"style":{"color":{"gradient":"<?php echo esc_attr( $purplish_floor ); ?>"},"dimensions":{"minHeight":"clamp(340px, 44vw, 600px)"},"spacing":{"padding":{"top":"32px"},"blockGap":"0"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center","verticalAlignment":"bottom"}} -->
<div class="wp-block-group has-background" style="background:<?php echo esc_attr( $purplish_floor ); ?>;min-height:clamp(340px, 44vw, 600px);padding-top:32px">
<?php foreach ( $purplish_figures as $purplish_figure ) : ?>
	<?php
	$purplish_image = array(
		'height'          => $purplish_figure[1],
		'aspectRatio'     => '3/8',
		'scale'           => 'contain',
		'sizeSlug'        => 'full',
		'linkDestination' => 'none',
	);
	if ( $purplish_figure[2] ) {
		$purplish_image['style'] = array( 'spacing' => array( 'margin' => $purplish_figure[2] ) );
	}
	$purplish_margin = isset( $purplish_figure[2]['right'] ) ? 'margin-right:' . $purplish_figure[2]['right'] : ( isset( $purplish_figure[2]['left'] ) ? 'margin-left:' . $purplish_figure[2]['left'] : '' );
	?>
<!-- wp:image <?php echo serialize_block_attributes( $purplish_image ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<figure class="wp-block-image size-full is-resized"<?php echo $purplish_margin ? ' style="' . esc_attr( $purplish_margin ) . '"' : ''; ?>><img src="<?php echo esc_url( \Purplish\Blocks\Showfloor::figure( $purplish_figure[0] ) ); ?>" alt="" style="aspect-ratio:3/8;object-fit:contain;height:<?php echo esc_attr( $purplish_figure[1] ); ?>"/></figure>
<!-- /wp:image -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<?php
/**
 * Pattern: Shop by category. Four tall tiles, each a Cover with a drawn
 * figure and the category name on the photo. The photo pushes in slowly
 * on hover and the tiles rise in one after another.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_shop  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$purplish_ink   = '#111111';
$purplish_tint  = '#f2edff';
$purplish_tiles = array(
	array( __( 'Coats', 'purplish' ), 'long-coat', 0.3 ),
	array( __( 'Jackets', 'purplish' ), 'puffer', 0.3 ),
	array( __( 'Trousers', 'purplish' ), 'denim-set', 0.7 ),
	array( __( 'Shoes', 'purplish' ), 'easy-sunday', 1 ),
);
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Shop by category', 'purplish' ); ?>"},"align":"full","style":{"spacing":{"padding":{"top":"clamp(64px, 9vw, 120px)","bottom":"0","left":"0","right":"0"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:clamp(64px, 9vw, 120px);padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide" style="padding-right:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:group {"style":{"spacing":{"margin":{"bottom":"clamp(24px, 3vw, 40px)"},"blockGap":"24px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group" style="margin-bottom:clamp(24px, 3vw, 40px)"><!-- wp:heading {"style":{"typography":{"letterSpacing":"-0.02em","lineHeight":"1.1"}},"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size" style="letter-spacing:-0.02em;line-height:1.1"><?php esc_html_e( 'Shop by category', 'purplish' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"<?php echo esc_attr( $purplish_ink ); ?>"},"typography":{"textDecoration":"underline"}}}},"fontSize":"small"} -->
<p class="has-link-color has-small-font-size"><a href="<?php echo esc_url( $purplish_shop ); ?>"><?php esc_html_e( 'All clothing', 'purplish' ); ?> &rarr;</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"16px"}},"layout":{"type":"grid","columnCount":4,"minimumColumnWidth":"9rem"}} -->
<div class="wp-block-group">
<?php foreach ( $purplish_tiles as $purplish_tile ) : ?>
	<?php
	$purplish_url   = \Purplish\Blocks\Showfloor::figure( $purplish_tile[1] );
	$purplish_pad   = 'clamp(10px, 1.2vw, 16px)';
	$purplish_attrs = serialize_block_attributes(
		array(
			'url'                => $purplish_url,
			'dimRatio'           => 0,
			'customOverlayColor' => $purplish_tint,
			'isUserOverlayColor' => true,
			'focalPoint'         => array(
				'x' => 0.5,
				'y' => $purplish_tile[2],
			),
			'contentPosition'    => 'bottom left',
			'isDark'             => false,
			'style'              => array(
				'dimensions' => array( 'aspectRatio' => '3/4' ),
				'spacing'    => array(
					'padding' => array(
						'top'    => $purplish_pad,
						'bottom' => $purplish_pad,
						'left'   => $purplish_pad,
						'right'  => $purplish_pad,
					),
				),
			),
			'layout'             => array( 'type' => 'constrained' ),
			'purplish'           => array( 'hover' => 'push' ),
		)
	);
	?>
<!-- wp:group {"style":{"color":{"background":"<?php echo esc_attr( $purplish_tint ); ?>"}},"layout":{"type":"default"},"purplish":{"entrance":"fadeup"}} -->
<div class="wp-block-group has-background" style="background-color:<?php echo esc_attr( $purplish_tint ); ?>"><!-- wp:cover <?php echo $purplish_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<div class="wp-block-cover is-light has-custom-content-position is-position-bottom-left" style="padding-top:clamp(10px, 1.2vw, 16px);padding-right:clamp(10px, 1.2vw, 16px);padding-bottom:clamp(10px, 1.2vw, 16px);padding-left:clamp(10px, 1.2vw, 16px)"><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $purplish_url ); ?>" style="object-position:50% <?php echo esc_attr( $purplish_tile[2] * 100 ); ?>%" data-object-fit="cover" data-object-position="50% <?php echo esc_attr( $purplish_tile[2] * 100 ); ?>%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim" style="background-color:<?php echo esc_attr( $purplish_tint ); ?>"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"layout":{"type":"flex"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"color":{"background":"#ffffff","text":"<?php echo esc_attr( $purplish_ink ); ?>"},"elements":{"link":{"color":{"text":"<?php echo esc_attr( $purplish_ink ); ?>"},"typography":{"textDecoration":"none"}}},"spacing":{"padding":{"top":"8px","bottom":"8px","left":"14px","right":"14px"}}},"fontSize":"medium"} -->
<p class="has-text-color has-background has-link-color has-medium-font-size" style="color:<?php echo esc_attr( $purplish_ink ); ?>;background-color:#ffffff;padding-top:8px;padding-right:14px;padding-bottom:8px;padding-left:14px"><a href="<?php echo esc_url( $purplish_shop ); ?>"><?php echo esc_html( $purplish_tile[0] ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

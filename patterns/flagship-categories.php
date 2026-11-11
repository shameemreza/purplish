<?php
/**
 * Pattern: Shop by category. Four tall Cover tiles, each a drawn figure
 * with the category name on the photo over a dark fade. The tile tilts
 * toward the mouse on hover and the tiles rise in one after another.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_shop   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$purplish_ink    = '#111111';
$purplish_tint   = '#f2edff';
$purplish_ground = '#ffffff';
// Clear at the head, dark at the foot, so the name reads on any photo.
$purplish_fade   = 'linear-gradient(180deg, rgba(31,3,66,0) 50%, rgba(31,3,66,0.72) 100%)';
$purplish_tiles  = array(
	array( __( 'Coats', 'purplish' ), 'long-coat' ),
	array( __( 'Jackets', 'purplish' ), 'puffer' ),
	array( __( 'Trousers', 'purplish' ), 'denim-set' ),
	array( __( 'Shoes', 'purplish' ), 'easy-sunday' ),
);
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Shop by category', 'purplish' ); ?>"},"align":"full","style":{"color":{"background":"<?php echo esc_attr( $purplish_ground ); ?>"},"spacing":{"padding":{"top":"clamp(64px, 9vw, 120px)","bottom":"clamp(64px, 9vw, 120px)","left":"0","right":"0"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background" style="background-color:<?php echo esc_attr( $purplish_ground ); ?>;margin-top:0;margin-bottom:0;padding-top:clamp(64px, 9vw, 120px);padding-right:0;padding-bottom:clamp(64px, 9vw, 120px);padding-left:0"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide" style="padding-right:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><?php echo \Purplish\Patterns::section_head( __( 'Shop by category', 'purplish' ), '', $purplish_shop, __( 'All clothing', 'purplish' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in section_head(). ?>

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
					'width' => '1px',
					'style' => 'solid',
					'color' => $purplish_tint,
				),
			),
			'layout'          => array( 'type' => 'default' ),
			'purplish'        => array( 'hover' => 'tilt' ),
		)
	);
	?>
<!-- wp:group {"layout":{"type":"default"},"purplish":{"entrance":"fadeup"}} -->
<div class="wp-block-group"><!-- wp:cover <?php echo $purplish_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<div class="wp-block-cover has-border-color has-custom-content-position is-position-bottom-left" style="border-color:<?php echo esc_attr( $purplish_tint ); ?>;border-style:solid;border-width:1px"><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $purplish_url ); ?>" style="object-position:50% 30%" data-object-fit="cover" data-object-position="50% 30%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim wp-block-cover__gradient-background has-background-gradient" style="background:<?php echo esc_attr( $purplish_fade ); ?>"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"#ffffff"},"typography":{"textDecoration":"none"}}}},"fontSize":"large"} -->
<p class="has-link-color has-large-font-size"><a href="<?php echo esc_url( $purplish_shop ); ?>"><?php echo esc_html( $purplish_tile[0] ); ?> &rarr;</a></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

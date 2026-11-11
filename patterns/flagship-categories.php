<?php
/**
 * Pattern: Shop by category. Four tall tiles, each a drawn figure on a
 * lavender panel with the category name under it. The photo tilts toward
 * the mouse on hover and the tiles rise in one after another.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_shop   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$purplish_ink    = '#111111';
$purplish_tint   = '#f2edff';
$purplish_ground = '#ffffff';
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
			'aspectRatio'     => '3/4',
			'scale'           => 'contain',
			'linkDestination' => 'custom',
			'purplish'        => array( 'hover' => 'tilt' ),
		)
	);
	?>
<!-- wp:group {"style":{"spacing":{"blockGap":"12px"}},"layout":{"type":"default"},"purplish":{"entrance":"fadeup"}} -->
<div class="wp-block-group"><!-- wp:group {"style":{"color":{"background":"<?php echo esc_attr( $purplish_tint ); ?>"}},"layout":{"type":"default"}} -->
<div class="wp-block-group has-background" style="background-color:<?php echo esc_attr( $purplish_tint ); ?>"><!-- wp:image <?php echo $purplish_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block attribute JSON, encoded by core. ?> -->
<figure class="wp-block-image"><a href="<?php echo esc_url( $purplish_shop ); ?>"><img src="<?php echo esc_url( $purplish_url ); ?>" alt="" style="aspect-ratio:3/4;object-fit:contain"/></a></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"<?php echo esc_attr( $purplish_ink ); ?>"},"typography":{"textDecoration":"none"}}}},"fontSize":"medium"} -->
<p class="has-link-color has-medium-font-size"><a href="<?php echo esc_url( $purplish_shop ); ?>"><?php echo esc_html( $purplish_tile[0] ); ?> &rarr;</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<?php endforeach; ?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

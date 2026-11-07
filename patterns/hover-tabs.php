<?php
/**
 * Pattern: Shop by category.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$purplish_tabs = array(
	array( __( 'New in', 'purplish' ), __( 'The latest pieces, just landed.', 'purplish' ) ),
	array( __( 'Best sellers', 'purplish' ), __( 'What everyone keeps coming back for.', 'purplish' ) ),
	array( __( 'Gifts', 'purplish' ), __( 'Easy picks for someone you like.', 'purplish' ) ),
);
?>
<!-- wp:purplish/hover-tabs {"align":"wide"} -->
<?php foreach ( $purplish_tabs as $purplish_tab ) : ?>
<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><a href="<?php echo esc_url( $purplish_shop ); ?>"><?php echo esc_html( $purplish_tab[0] ); ?></a></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html( $purplish_tab[1] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:image -->
<figure class="wp-block-image"><img alt=""/></figure>
<!-- /wp:image -->
</div>
<!-- /wp:group -->

<?php endforeach; ?>
<!-- /wp:purplish/hover-tabs -->

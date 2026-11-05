<?php
/**
 * Pattern: Customer stories deck.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_shop    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$purplish_stories = array(
	array(
		__( 'It fit the first time, and the color is even better in daylight. I ordered a second one the same week.', 'purplish' ),
		__( 'Maya R., Lisbon', 'purplish' ),
	),
	array(
		__( 'Packed with care and here in two days. The little note in the box made my morning.', 'purplish' ),
		__( 'Daniel K., Leeds', 'purplish' ),
	),
	array(
		__( 'I wasn\'t sure about the size, so I asked. They answered within the hour and got it right.', 'purplish' ),
		__( 'Priya S., Toronto', 'purplish' ),
	),
);
?>
<!-- wp:purplish/story-deck {"align":"wide"} -->
<?php foreach ( $purplish_stories as $purplish_story ) : ?>
<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group">
<!-- wp:image -->
<figure class="wp-block-image"><img alt=""/></figure>
<!-- /wp:image -->

<!-- wp:quote -->
<blockquote class="wp-block-quote">
<!-- wp:paragraph -->
<p><?php echo esc_html( $purplish_story[0] ); ?></p>
<!-- /wp:paragraph -->
</blockquote>
<!-- /wp:quote -->

<!-- wp:paragraph -->
<p><?php echo esc_html( $purplish_story[1] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $purplish_shop ); ?>"><?php echo esc_html__( 'Shop this', 'purplish' ); ?></a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->

<?php endforeach; ?>
<!-- /wp:purplish/story-deck -->

<?php
/**
 * Pattern: Flagship home. A full store front page: the floor first, then
 * shop by category, best sellers, the brand band, just in, customer
 * stories, the trust row and the newsletter. Each section is also its own
 * pattern, so the markup lives in that section's file.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_sections = array( 'flagship-floor', 'flagship-categories', 'flagship-best-sellers', 'flagship-brand-band', 'flagship-just-in', 'story-deck', 'trust-row', 'flagship-newsletter' );
foreach ( $purplish_sections as $purplish_section ) {
	if ( 'story-deck' === $purplish_section ) {
		?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Worn by you', 'purplish' ); ?>"},"align":"full","style":{"color":{"background":"#1f0342","text":"#f4f0ff"},"elements":{"link":{"color":{"text":"#ffffff"}},"button":{"color":{"background":"#ffffff","text":"#1f0342"}}},"spacing":{"padding":{"top":"clamp(64px, 9vw, 120px)","bottom":"clamp(64px, 9vw, 120px)","left":"0","right":"0"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-text-color has-background has-link-color" style="color:#f4f0ff;background-color:#1f0342;margin-top:0;margin-bottom:0;padding-top:clamp(64px, 9vw, 120px);padding-right:0;padding-bottom:clamp(64px, 9vw, 120px);padding-left:0"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide" style="padding-right:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><?php echo \Purplish\Patterns::section_head( __( 'Worn by you', 'purplish' ), __( 'Real orders, photographed by the people who wear them.', 'purplish' ), '', '', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in section_head(). ?>

		<?php
		include __DIR__ . '/story-deck.php';
		?>
</div>
<!-- /wp:group --></div>
<!-- /wp:group -->

		<?php
		continue;
	}
	include __DIR__ . '/' . $purplish_section . '.php';
	echo "\n\n";
}

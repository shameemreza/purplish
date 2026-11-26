<?php
/**
 * Pattern: Checkout header. A Back to bag link, the site title in the
 * theme's own style, a three-step trail (Bag, linked back to the cart, then
 * Checkout, then Done) and a Secure checkout pill with a lock, for the
 * Checkout header template part. The checkout-header-trust pattern sets
 * $purplish_trust to add a trust row under it. Nothing on it moves.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_trust = ! empty( $purplish_trust );
$purplish_cart  = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' );
$purplish_ink   = 'theme-2';
$purplish_soft  = 'theme-4';
$purplish_rule  = 'theme-6';
$purplish_tint  = 'theme-5';
$purplish_paper = 'theme-1';
$purplish_var   = static function ( $slug ) {
	return 'var(--wp--preset--color--' . $slug . ')';
};
$purplish_ref   = static function ( $slug ) {
	return 'var:preset|color|' . $slug;
};

/**
 * One step in the trail: a round dot and its label. The dot takes its
 * width from padding, since a flex basis is lost when the trail sizes
 * itself to its content.
 *
 * @param string $mark   Text in the dot.
 * @param string $label  Label markup, already escaped.
 * @param string $state  done, now or next.
 * @return string Block markup.
 */
$purplish_step = static function ( $mark, $label, $state ) use ( $purplish_ink, $purplish_soft, $purplish_rule, $purplish_tint, $purplish_paper, $purplish_var, $purplish_ref ) {
	$dots   = array(
		'done' => array( $purplish_tint, 'theme-7', $purplish_tint ),
		'now'  => array( $purplish_ink, $purplish_paper, $purplish_ink ),
		'next' => array( $purplish_paper, $purplish_soft, $purplish_rule ),
	);
	$dot    = $dots[ $state ];
	$text   = 'next' === $state ? $purplish_soft : $purplish_ink;
	$weight = 'now' === $state ? '500' : '400';
	$attrs  = array(
		'style'  => array(
			'color'      => array( 'background' => $purplish_ref( $dot[0] ) ),
			'border'     => array(
				'radius' => '999px',
				'width'  => '1px',
				'style'  => 'solid',
				'color'  => $purplish_ref( $dot[2] ),
			),
			'dimensions' => array( 'minHeight' => '23px' ),
			'spacing'    => array(
				'padding' => array(
					'top'    => '0',
					'bottom' => '0',
					'left'   => '7px',
					'right'  => '7px',
				),
			),
		),
		'layout' => array(
			'type'              => 'flex',
			'flexWrap'          => 'nowrap',
			'justifyContent'    => 'center',
			'verticalAlignment' => 'center',
		),
	);

	return '<!-- wp:group {"style":{"spacing":{"blockGap":"8px"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->' . "\n"
		. '<div class="wp-block-group"><!-- wp:group ' . serialize_block_attributes( $attrs ) . " -->\n"
		. '<div class="wp-block-group has-border-color has-background" style="border-color:' . esc_attr( $purplish_var( $dot[2] ) ) . ';border-width:1px;border-style:solid;border-radius:999px;background-color:' . esc_attr( $purplish_var( $dot[0] ) ) . ';min-height:23px;padding-top:0;padding-right:7px;padding-bottom:0;padding-left:7px">'
		. '<!-- wp:paragraph {"style":{"color":{"text":"' . esc_attr( $purplish_ref( $dot[1] ) ) . '"},"typography":{"fontSize":"12px","lineHeight":"1"}}} -->' . "\n"
		. '<p class="has-text-color" style="color:' . esc_attr( $purplish_var( $dot[1] ) ) . ';font-size:12px;line-height:1">' . esc_html( $mark ) . "</p>\n"
		. "<!-- /wp:paragraph --></div>\n<!-- /wp:group -->\n\n"
		. '<!-- wp:paragraph {"style":{"color":{"text":"' . esc_attr( $purplish_ref( $text ) ) . '"},"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"' . $weight . '"}}} -->' . "\n"
		. '<p class="has-text-color" style="color:' . esc_attr( $purplish_var( $text ) ) . ';font-size:14px;font-style:normal;font-weight:' . $weight . '">' . $label . "</p>\n"
		. "<!-- /wp:paragraph --></div>\n<!-- /wp:group -->\n";
};

$purplish_bar  = '<!-- wp:group {"style":{"color":{"background":"' . $purplish_ref( $purplish_rule ) . '"},"dimensions":{"minHeight":"1px"},"spacing":{"padding":{"top":"0","bottom":"0","left":"14px","right":"14px"}}},"layout":{"type":"default"}} -->' . "\n"
	. '<div class="wp-block-group has-background" style="background-color:' . $purplish_var( $purplish_rule ) . ';min-height:1px;padding-top:0;padding-right:14px;padding-bottom:0;padding-left:14px"></div>' . "\n"
	. "<!-- /wp:group -->\n";
$purplish_pill = \Purplish\Patterns::radius( '999px' );

/**
 * One trust row item: a check in the theme's accent and a short line.
 *
 * @param string $label Plain text.
 * @return string Block markup.
 */
$purplish_promise = static function ( $label ) use ( $purplish_soft, $purplish_var, $purplish_ref ) {
	return '<!-- wp:group {"style":{"spacing":{"blockGap":"4px"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->' . "\n"
		. '<div class="wp-block-group"><!-- wp:icon {"icon":"core/check","textColor":"theme-7","style":{"dimensions":{"width":"18px"}}} /-->' . "\n\n"
		. '<!-- wp:paragraph {"style":{"color":{"text":"' . esc_attr( $purplish_ref( $purplish_soft ) ) . '"},"typography":{"fontSize":"13px","lineHeight":"1.4"}}} -->' . "\n"
		. '<p class="has-text-color" style="color:' . esc_attr( $purplish_var( $purplish_soft ) ) . ';font-size:13px;line-height:1.4">' . esc_html( $label ) . "</p>\n"
		. "<!-- /wp:paragraph --></div>\n<!-- /wp:group -->\n";
};
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Checkout header', 'purplish' ); ?>"},"align":"full","style":{"color":{"background":"var:preset|color|theme-1"},"border":{"bottom":{"color":"<?php echo esc_attr( $purplish_ref( $purplish_rule ) ); ?>","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"14px","bottom":"14px","left":"0","right":"0"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background" style="border-bottom-color:<?php echo esc_attr( $purplish_var( $purplish_rule ) ); ?>;border-bottom-style:solid;border-bottom-width:1px;background-color:var(--wp--preset--color--theme-1);margin-top:0;margin-bottom:0;padding-top:14px;padding-right:0;padding-bottom:14px;padding-left:0"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"12px 24px"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group alignwide" style="padding-right:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:group {"style":{"spacing":{"blockGap":"6px 24px"}},"layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Back to bag', 'purplish' ); ?>"},"style":{"spacing":{"blockGap":"4px"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:icon {"icon":"core/arrow-left","textColor":"<?php echo esc_attr( $purplish_ink ); ?>","style":{"dimensions":{"width":"16px"}}} /-->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"14px","lineHeight":"24px"}}} -->
<p style="font-size:14px;line-height:24px"><a href="<?php echo esc_url( $purplish_cart ); ?>"><?php esc_html_e( 'Back to bag', 'purplish' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:site-title {"level":0,"style":{"elements":{"link":{"typography":{"textDecoration":"none"}}}}} /--></div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Steps', 'purplish' ); ?>"},"style":{"spacing":{"blockGap":"10px"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group">
<?php
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Block markup built and escaped above.
echo $purplish_step( '✓', '<a href="' . esc_url( $purplish_cart ) . '">' . esc_html__( 'Bag', 'purplish' ) . '</a>', 'done' );
echo $purplish_bar;
echo $purplish_step( '2', esc_html__( 'Checkout', 'purplish' ), 'now' );
echo $purplish_bar;
echo $purplish_step( '3', esc_html__( 'Done', 'purplish' ), 'next' );
// phpcs:enable
?>
</div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Secure checkout', 'purplish' ); ?>"},"style":{"color":{"background":"<?php echo esc_attr( $purplish_ref( $purplish_tint ) ); ?>"},"border":{"radius":"<?php echo esc_attr( $purplish_pill ); ?>"},"typography":{"lineHeight":"0"},"spacing":{"padding":{"top":"8px","bottom":"8px","left":"14px","right":"16px"},"blockGap":"8px"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group has-background" style="border-radius:<?php echo esc_attr( $purplish_pill ); ?>;background-color:<?php echo esc_attr( $purplish_var( $purplish_tint ) ); ?>;padding-top:8px;padding-right:16px;padding-bottom:8px;padding-left:14px;line-height:0"><!-- wp:icon {"icon":"purplish/lock","textColor":"theme-7","style":{"dimensions":{"width":"18px"}}} /-->

<!-- wp:paragraph {"style":{"color":{"text":"<?php echo esc_attr( $purplish_ref( $purplish_ink ) ); ?>"},"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500","lineHeight":"1.4"}}} -->
<p class="has-text-color" style="color:<?php echo esc_attr( $purplish_var( $purplish_ink ) ); ?>;font-size:14px;font-style:normal;font-weight:500;line-height:1.4"><?php esc_html_e( 'Secure checkout', 'purplish' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<?php if ( $purplish_trust ) : ?>

<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Trust row', 'purplish' ); ?>"},"align":"full","style":{"color":{"background":"<?php echo esc_attr( $purplish_ref( $purplish_tint ) ); ?>"},"spacing":{"padding":{"top":"10px","bottom":"10px","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"margin":{"top":"0","bottom":"0"},"blockGap":"6px 28px"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
<div class="wp-block-group alignfull has-background" style="background-color:<?php echo esc_attr( $purplish_var( $purplish_tint ) ); ?>;margin-top:0;margin-bottom:0;padding-top:10px;padding-right:var(--wp--preset--spacing--50);padding-bottom:10px;padding-left:var(--wp--preset--spacing--50)">
	<?php
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Block markup built and escaped above.
	echo $purplish_promise( __( 'Free shipping over $100', 'purplish' ) );
	echo $purplish_promise( __( '30-day returns', 'purplish' ) );
	echo $purplish_promise( __( 'Secure payment', 'purplish' ) );
// phpcs:enable
	?>
</div>
<!-- /wp:group -->
<?php endif; ?>

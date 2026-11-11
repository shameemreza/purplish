<?php
/**
 * Pattern: Checkout header. The site title, a three-step trail (Bag, linked
 * back to the cart, then Checkout, then Done) and a Secure checkout pill,
 * for WooCommerce's Checkout Header template part. Nothing on it moves.
 *
 * @package Purplish
 */

defined( 'ABSPATH' ) || exit;

$purplish_cart  = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' );
$purplish_serif = 'var(--wp--preset--font-family--bodoni-moda, Didot, Georgia, serif)';
$purplish_deep  = '#1f0342';
$purplish_ink   = '#111111';
$purplish_soft  = '#6b6577';
$purplish_rule  = '#cfc7dd';
$purplish_tint  = '#f2edff';
$purplish_line  = 'rgba(17,17,17,0.12)';

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
$purplish_step = static function ( $mark, $label, $state ) use ( $purplish_deep, $purplish_ink, $purplish_soft, $purplish_rule, $purplish_tint ) {
	$dots   = array(
		'done' => array( $purplish_tint, '#720eec', $purplish_tint ),
		'now'  => array( $purplish_deep, '#ffffff', $purplish_deep ),
		'next' => array( '#ffffff', $purplish_soft, $purplish_rule ),
	);
	$dot    = $dots[ $state ];
	$text   = 'next' === $state ? $purplish_soft : $purplish_ink;
	$weight = 'now' === $state ? '500' : '400';
	$attrs  = array(
		'style'  => array(
			'color'      => array( 'background' => $dot[0] ),
			'border'     => array(
				'radius' => '999px',
				'width'  => '1px',
				'style'  => 'solid',
				'color'  => $dot[2],
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
		. '<div class="wp-block-group has-border-color has-background" style="border-color:' . esc_attr( $dot[2] ) . ';border-width:1px;border-style:solid;border-radius:999px;background-color:' . esc_attr( $dot[0] ) . ';min-height:23px;padding-top:0;padding-right:7px;padding-bottom:0;padding-left:7px">'
		. '<!-- wp:paragraph {"style":{"color":{"text":"' . esc_attr( $dot[1] ) . '"},"typography":{"fontSize":"12px","lineHeight":"1"}}} -->' . "\n"
		. '<p class="has-text-color" style="color:' . esc_attr( $dot[1] ) . ';font-size:12px;line-height:1">' . esc_html( $mark ) . "</p>\n"
		. "<!-- /wp:paragraph --></div>\n<!-- /wp:group -->\n\n"
		. '<!-- wp:paragraph {"style":{"color":{"text":"' . esc_attr( $text ) . '"},"elements":{"link":{"color":{"text":"' . esc_attr( $purplish_ink ) . '"}}},"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"' . $weight . '"}}} -->' . "\n"
		. '<p class="has-text-color has-link-color" style="color:' . esc_attr( $text ) . ';font-size:14px;font-style:normal;font-weight:' . $weight . '">' . $label . "</p>\n"
		. "<!-- /wp:paragraph --></div>\n<!-- /wp:group -->\n";
};

$purplish_bar = '<!-- wp:group {"style":{"color":{"background":"' . $purplish_rule . '"},"dimensions":{"minHeight":"1px"},"spacing":{"padding":{"top":"0","bottom":"0","left":"14px","right":"14px"}}},"layout":{"type":"default"}} -->' . "\n"
	. '<div class="wp-block-group has-background" style="background-color:' . $purplish_rule . ';min-height:1px;padding-top:0;padding-right:14px;padding-bottom:0;padding-left:14px"></div>' . "\n"
	. "<!-- /wp:group -->\n";
?>
<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Checkout header', 'purplish' ); ?>"},"align":"full","style":{"color":{"background":"#ffffff"},"border":{"bottom":{"color":"<?php echo esc_attr( $purplish_line ); ?>","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"14px","bottom":"14px","left":"0","right":"0"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background" style="border-bottom-color:<?php echo esc_attr( $purplish_line ); ?>;border-bottom-style:solid;border-bottom-width:1px;background-color:#ffffff;margin-top:0;margin-bottom:0;padding-top:14px;padding-right:0;padding-bottom:14px;padding-left:0"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"blockGap":"12px 24px"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group alignwide" style="padding-right:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:site-title {"level":0,"style":{"typography":{"fontFamily":"<?php echo esc_attr( $purplish_serif ); ?>","fontSize":"clamp(1.5rem, 1.2rem + 0.8vw, 1.85rem)","fontStyle":"normal","fontWeight":"400","lineHeight":"1","letterSpacing":"-0.01em","textTransform":"none"},"elements":{"link":{"color":{"text":"<?php echo esc_attr( $purplish_deep ); ?>"},"typography":{"textDecoration":"none"}}}}} /-->

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

<!-- wp:group {"metadata":{"name":"<?php echo esc_attr__( 'Secure checkout', 'purplish' ); ?>"},"style":{"color":{"background":"<?php echo esc_attr( $purplish_tint ); ?>"},"border":{"radius":"999px"},"typography":{"lineHeight":"0"},"spacing":{"padding":{"top":"8px","bottom":"8px","left":"14px","right":"16px"},"blockGap":"8px"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group has-background" style="border-radius:999px;background-color:<?php echo esc_attr( $purplish_tint ); ?>;padding-top:8px;padding-right:16px;padding-bottom:8px;padding-left:14px;line-height:0"><!-- wp:image {"width":"18px","height":"18px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( plugins_url( 'assets/icons/secure.svg', PURPLISH_FILE ) ); ?>" alt="" style="width:18px;height:18px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"style":{"color":{"text":"<?php echo esc_attr( $purplish_ink ); ?>"},"typography":{"fontSize":"14px","fontStyle":"normal","fontWeight":"500","lineHeight":"1.4"}}} -->
<p class="has-text-color" style="color:<?php echo esc_attr( $purplish_ink ); ?>;font-size:14px;font-style:normal;font-weight:500;line-height:1.4"><?php esc_html_e( 'Secure checkout', 'purplish' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

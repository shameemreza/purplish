<?php
/**
 * The size chart fields: a "Size chart" box in the product editor and a
 * "Size chart" select on product categories.
 *
 * @package Purplish
 */

namespace Purplish\Admin;

use Purplish\SizeCharts;

defined( 'ABSPATH' ) || exit;

/**
 * Prints and saves which chart a product or category shows, the product's
 * fit note, and fills a new chart pattern with the starter chart.
 */
final class SizeChartFields {

	const NONCE = 'purplish_size_chart';

	/**
	 * Adds the hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes_product', array( $this, 'add_box' ) );
		add_action( 'save_post_product', array( $this, 'save_product' ) );
		add_action( 'product_cat_add_form_fields', array( $this, 'category_add_field' ) );
		add_action( 'product_cat_edit_form_fields', array( $this, 'category_edit_field' ) );
		add_action( 'created_product_cat', array( $this, 'save_term' ) );
		add_action( 'edited_product_cat', array( $this, 'save_term' ) );
		add_filter( 'default_content', array( $this, 'default_content' ), 10, 2 );
		add_filter( 'default_title', array( $this, 'default_title' ), 10, 2 );
	}

	/**
	 * Adds the box to the product editor's sidebar.
	 *
	 * @return void
	 */
	public function add_box() {
		add_meta_box( 'purplish-size-chart', __( 'Size chart', 'purplish' ), array( $this, 'render_box' ), 'product', 'side' );
	}

	/**
	 * The link that opens the pattern editor with the starter chart.
	 *
	 * @return string
	 */
	public static function new_chart_url() {
		return admin_url( 'post-new.php?post_type=wp_block&purplish_size_chart=new' );
	}

	/**
	 * Prints the chart select options.
	 *
	 * @param array<string, string> $first    Options before the charts.
	 * @param array<string, string> $last     Options after the charts.
	 * @param string                $selected Saved value.
	 * @return void
	 */
	private static function options( $first, $last, $selected ) {
		$options = $first;
		foreach ( SizeCharts::charts() as $id => $title ) {
			$options[ (string) $id ] = $title;
		}
		$options += $last;
		foreach ( $options as $value => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $value ),
				selected( (string) $value, $selected, false ),
				esc_html( $label )
			);
		}
	}

	/**
	 * Prints the product box.
	 *
	 * @param \WP_Post $post Product.
	 * @return void
	 */
	public function render_box( $post ) {
		$saved    = (string) get_post_meta( $post->ID, SizeCharts::META, true );
		$category = SizeCharts::category_chart( $post->ID );
		$selected = 'none' === $saved || SizeCharts::is_chart( $saved ) ? $saved : '';
		$shows    = '' === $selected ? $category : ( 'none' === $selected ? 0 : (int) $selected );
		$first    = $category
			/* translators: %s: name of the size chart the product's category shows. */
			? sprintf( __( 'Category\'s chart (%s)', 'purplish' ), get_the_title( $category ) )
			: __( 'Category\'s chart (none set)', 'purplish' );

		wp_nonce_field( self::NONCE, 'purplish_size_chart_nonce' );
		echo '<p><label for="purplish_size_chart">' . esc_html__( 'Chart', 'purplish' ) . '</label><br>';
		echo '<select name="purplish_size_chart" id="purplish_size_chart" class="widefat">';
		self::options( array( '' => $first ), array( 'none' => __( 'None', 'purplish' ) ), $selected );
		echo '</select></p><p>';
		if ( $shows && current_user_can( 'edit_post', $shows ) ) {
			printf( '<a href="%1$s">%2$s</a> | ', esc_url( get_edit_post_link( $shows, 'raw' ) ), esc_html__( 'Edit chart', 'purplish' ) );
		}
		printf( '<a href="%1$s">%2$s</a></p>', esc_url( self::new_chart_url() ), esc_html__( 'New chart', 'purplish' ) );
		printf(
			'<p><label for="purplish_fit_note">%1$s</label><textarea name="purplish_fit_note" id="purplish_fit_note" class="widefat" rows="3" aria-describedby="purplish_fit_note_help">%2$s</textarea><span id="purplish_fit_note_help" class="description">%3$s</span></p>',
			esc_html__( 'Fit note', 'purplish' ),
			esc_textarea( (string) get_post_meta( $post->ID, SizeCharts::FIT_META, true ) ),
			esc_html__( 'Shows under the size options and with the chart, like "Runs small, size up".', 'purplish' )
		);
	}

	/**
	 * Whether the posted form carries a valid nonce.
	 *
	 * @return bool
	 */
	private static function verified() {
		return isset( $_POST['purplish_size_chart_nonce'] )
			&& wp_verify_nonce( sanitize_key( wp_unslash( $_POST['purplish_size_chart_nonce'] ) ), self::NONCE );
	}

	/**
	 * The posted chart: a chart ID, `none`, '' for none picked, or null for
	 * a value that isn't one of those.
	 *
	 * @param bool $allow_none Whether `none` is allowed.
	 * @return string|null
	 */
	private static function posted_chart( $allow_none ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Callers check the nonce first.
		$value = isset( $_POST['purplish_size_chart'] ) ? sanitize_key( wp_unslash( $_POST['purplish_size_chart'] ) ) : null;
		if ( null === $value || '' === $value ) {
			return $value;
		}
		if ( $allow_none && 'none' === $value ) {
			return 'none';
		}
		return SizeCharts::is_chart( $value ) ? (string) absint( $value ) : null;
	}

	/**
	 * Saves the product box.
	 *
	 * @param int $post_id Product ID.
	 * @return void
	 */
	public function save_product( $post_id ) {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! self::verified() || ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		$chart = self::posted_chart( true );
		if ( '' === $chart ) {
			delete_post_meta( $post_id, SizeCharts::META );
		} elseif ( null !== $chart ) {
			update_post_meta( $post_id, SizeCharts::META, $chart );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked in verified().
		$note = isset( $_POST['purplish_fit_note'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['purplish_fit_note'] ) ) ) : '';
		if ( '' === $note ) {
			delete_post_meta( $post_id, SizeCharts::FIT_META );
		} else {
			update_post_meta( $post_id, SizeCharts::FIT_META, $note );
		}
	}

	/**
	 * Prints the select on the Add new category form.
	 *
	 * @return void
	 */
	public function category_add_field() {
		echo '<div class="form-field term-purplish-size-chart-wrap">';
		wp_nonce_field( self::NONCE, 'purplish_size_chart_nonce' );
		echo '<label for="purplish_size_chart">' . esc_html__( 'Size chart', 'purplish' ) . '</label>';
		self::category_select( '' );
		echo '</div>';
	}

	/**
	 * Prints the select on the Edit category screen.
	 *
	 * @param \WP_Term $term Category.
	 * @return void
	 */
	public function category_edit_field( $term ) {
		$saved = (string) get_term_meta( $term->term_id, SizeCharts::TERM_META, true );
		echo '<tr class="form-field term-purplish-size-chart-wrap"><th scope="row"><label for="purplish_size_chart">' . esc_html__( 'Size chart', 'purplish' ) . '</label></th><td>';
		wp_nonce_field( self::NONCE, 'purplish_size_chart_nonce' );
		self::category_select( SizeCharts::is_chart( $saved ) ? $saved : '' );
		echo '</td></tr>';
	}

	/**
	 * The category select, its links and help.
	 *
	 * @param string $selected Saved chart ID, or ''.
	 * @return void
	 */
	private static function category_select( $selected ) {
		echo '<select name="purplish_size_chart" id="purplish_size_chart" class="postform" aria-describedby="purplish_size_chart_help">';
		self::options( array( '' => __( 'No chart', 'purplish' ) ), array(), $selected );
		echo '</select>';
		printf(
			'<p id="purplish_size_chart_help" class="description">%1$s <a href="%2$s">%3$s</a></p>',
			esc_html__( 'Products in this category show this chart and a Size guide link, unless a product picks its own.', 'purplish' ),
			esc_url( self::new_chart_url() ),
			esc_html__( 'New chart', 'purplish' )
		);
	}

	/**
	 * Saves the category's chart.
	 *
	 * @param int $term_id Category ID.
	 * @return void
	 */
	public function save_term( $term_id ) {
		if ( ! self::verified() || ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}
		$chart = self::posted_chart( false );
		if ( '' === $chart ) {
			delete_term_meta( $term_id, SizeCharts::TERM_META );
		} elseif ( null !== $chart ) {
			update_term_meta( $term_id, SizeCharts::TERM_META, $chart );
		}
	}

	/**
	 * Whether this is a new chart pattern opened from a "New chart" link.
	 *
	 * @param \WP_Post $post Post being made.
	 * @return bool
	 */
	private static function is_new_chart( $post ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only picks the starter text for a new, unsaved pattern.
		$new = isset( $_GET['purplish_size_chart'] ) && 'new' === sanitize_key( wp_unslash( $_GET['purplish_size_chart'] ) );
		return $new && $post instanceof \WP_Post && 'wp_block' === $post->post_type;
	}

	/**
	 * Fills a new chart pattern with the starter chart.
	 *
	 * @param string   $content Default content.
	 * @param \WP_Post $post    Post being made.
	 * @return string
	 */
	public function default_content( $content, $post ) {
		return self::is_new_chart( $post ) ? SizeCharts::starter() : $content;
	}

	/**
	 * Names a new chart pattern.
	 *
	 * @param string   $title Default title.
	 * @param \WP_Post $post  Post being made.
	 * @return string
	 */
	public function default_title( $title, $post ) {
		return self::is_new_chart( $post ) ? __( 'Size chart', 'purplish' ) : $title;
	}
}

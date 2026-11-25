<?php
/**
 * Stand-ins for the bundle plugins' product classes, so the tests don't
 * need the plugins. Each one answers only what Purplish asks.
 *
 * @package Purplish
 */

namespace Purplish\Tests\Support;

/**
 * Builds container products for the tests.
 */
final class Containers {

	/**
	 * A saved product of a container type, with a custom Page color or none.
	 *
	 * @param string $type  Product type.
	 * @param string $color Custom Page color, or '' for none.
	 * @return \WC_Product
	 */
	public static function product( $type, $color = '' ) {
		$product       = new FakeContainer();
		$product->type = $type;
		$product->set_name( 'Container ' . $type );
		if ( '' !== $color ) {
			$product->update_meta_data( \Purplish\Meta::MODE, \Purplish\Meta::MODE_CUSTOM );
			$product->update_meta_data( \Purplish\Meta::CUSTOM_COLOR, $color );
		} else {
			$product->update_meta_data( \Purplish\Meta::MODE, \Purplish\Meta::MODE_OFF );
		}
		$product->save();
		return $product;
	}

	/**
	 * A saved simple product with a custom Page color.
	 *
	 * @param string $color Hex color, or '' for Off.
	 * @return \WC_Product_Simple
	 */
	public static function simple( $color ) {
		$product = new \WC_Product_Simple();
		$product->set_name( 'Simple ' . $color );
		$product->update_meta_data( \Purplish\Meta::MODE, '' === $color ? \Purplish\Meta::MODE_OFF : \Purplish\Meta::MODE_CUSTOM );
		if ( '' !== $color ) {
			$product->update_meta_data( \Purplish\Meta::CUSTOM_COLOR, $color );
		}
		$product->save();
		return $product;
	}

	/**
	 * Gives each sweater variation a custom color by its color option.
	 *
	 * @param \WC_Product_Variation[] $variations Variations keyed "Color/Size".
	 * @param array<string, string>   $colors     Color option => hex.
	 * @return \WC_Product_Variable The parent, read fresh.
	 */
	public static function color_sweater( $variations, $colors ) {
		foreach ( $variations as $key => $variation ) {
			$option = strtok( $key, '/' );
			$variation->update_meta_data( \Purplish\Meta::MODE, \Purplish\Meta::MODE_CUSTOM );
			$variation->update_meta_data( \Purplish\Meta::CUSTOM_COLOR, $colors[ $option ] );
			$variation->save();
		}
		return wc_get_product( reset( $variations )->get_parent_id() );
	}
}

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Commenting.VariableComment.Missing, Squiz.Commenting.FunctionComment.Missing

/**
 * A product whose type the test picks, with the item lists the bundle,
 * composite and mix and match classes have.
 */
class FakeContainer extends \WC_Product {
	public $type       = 'bundle';
	public $bundled    = array();
	public $components = array();
	public $child      = array();
	public $wpc_items  = array();

	public function get_type() {
		return $this->type;
	}

	public function get_bundled_items() {
		return $this->bundled;
	}

	public function get_components() {
		return $this->components;
	}

	public function get_child_items() {
		return $this->child;
	}

	public function get_items() {
		return $this->wpc_items;
	}
}

/**
 * A bundled item: Product Bundles' WC_Bundled_Item.
 */
class FakeBundledItem {
	public $id;
	public $product;
	public $picked;
	public $optional;
	public $visible;

	public function __construct( $id, $product, $picked = array(), $optional = false, $visible = true ) {
		$this->id       = $id;
		$this->product  = $product;
		$this->picked   = $picked;
		$this->optional = $optional;
		$this->visible  = $visible;
	}

	public function get_id() {
		return $this->id;
	}

	public function get_product() {
		return $this->product;
	}

	public function get_selected_product_variation_attributes() {
		return $this->picked;
	}

	public function is_optional() {
		return $this->optional;
	}

	public function is_visible() {
		return $this->visible;
	}
}

/**
 * A component: Composite Products' WC_CP_Component.
 */
class FakeComponent {
	public $id;
	public $options;
	public $default;

	public function __construct( $id, $options, $default_option = '' ) {
		$this->id      = $id;
		$this->options = $options;
		$this->default = $default_option;
	}

	public function get_id() {
		return $this->id;
	}

	public function get_options() {
		return $this->options;
	}

	public function get_default_option() {
		return $this->default;
	}
}

/**
 * A mix and match child: WC_MNM_Child_Item.
 */
class FakeChildItem {
	public $product;

	public function __construct( $product ) {
		$this->product = $product;
	}

	public function get_product() {
		return $this->product;
	}
}

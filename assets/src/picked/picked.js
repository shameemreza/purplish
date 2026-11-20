/**
 * The picked color's name beside the attribute name on the Variation
 * Selector, for photo swatches, where WooCommerce hides the names.
 */

const ROW =
	'.wp-block-woocommerce-add-to-cart-with-options-variation-selector-attribute';

/**
 * The chosen option's name in a row, or '' when none is chosen.
 *
 * @param {Element} row The attribute's row.
 * @return {string} The name.
 */
export function pickedName( row ) {
	const chosen = row.querySelector(
		'.wc-block-product-filter-chips__item[aria-checked="true"]'
	);
	return chosen
		? chosen.getAttribute( 'aria-label' ) ||
				chosen.getAttribute( 'title' ) ||
				chosen.textContent.trim()
		: '';
}

/**
 * Keeps a name slot in step with its row. WooCommerce draws the chips
 * again when it wakes, so the whole row is watched.
 *
 * @param {Element} slot The `purplish-photo-swatches__picked` span.
 */
export function watchPicked( slot ) {
	const row = slot.closest( ROW );
	if ( ! row ) {
		return;
	}
	const update = () => {
		const name = pickedName( row );
		if ( slot.textContent !== name ) {
			slot.textContent = name;
		}
	};
	new slot.ownerDocument.defaultView.MutationObserver( update ).observe(
		row,
		{
			subtree: true,
			childList: true,
			attributes: true,
			attributeFilter: [ 'aria-checked' ],
		}
	);
	update();
}

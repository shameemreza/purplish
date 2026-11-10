/**
 * Where a Wardrobe panel hangs: centered under the header it sits in, kept
 * 16px inside the page edges, and just below the header. The panel is
 * placed against its offset parent, the menu item, so a sticky or blurred
 * header doesn't move it.
 *
 * @param {Object} box          Boxes as left, top, right, bottom, width.
 * @param {Object} box.base     The panel's offset parent.
 * @param {Object} box.bar      The header the panel hangs under.
 * @param {Object} box.item     The menu item.
 * @param {number} box.width    The panel's width.
 * @param {number} box.viewport The page's width, without the scrollbar.
 * @param {number} box.gap      Space between the header and the panel.
 * @param {number} box.height   The window's height.
 * @return {Object} x and y against the offset parent, the tallest the
 *                  panel can be without leaving the window, and the bridge
 *                  that keeps the hover alive between the item and the
 *                  panel: its left edge against the panel, width and height.
 */
export function placement( { base, bar, item, width, viewport, gap, height } ) {
	const margin = Math.max( 0, Math.min( 16, ( viewport - width ) / 2 ) );
	const left = Math.min(
		Math.max( bar.left + ( bar.width - width ) / 2, margin ),
		viewport - width - margin
	);
	const top = bar.bottom + gap;

	return {
		x: left - base.left,
		y: top - base.top,
		max: Math.max( 160, height - top - 16 ),
		bridgeX: item.left - left,
		bridgeW: item.width,
		reach: Math.max( 0, top - item.bottom ),
	};
}

/**
 * Places an open panel: sets the page width first, so a full panel takes
 * its size, then measures and sets the position and the hover bridge.
 *
 * @param {HTMLElement} item  The menu item.
 * @param {HTMLElement} panel The panel, shown.
 */
export function place( item, panel ) {
	const doc = item.ownerDocument;
	const viewport = doc.documentElement.clientWidth;
	const set = ( key, value ) =>
		panel.style.setProperty(
			'--purplish-wardrobe-' + key.toLowerCase(),
			value + 'px'
		);
	set( 'vw', viewport );
	const bar =
		item.closest( 'header, .wp-block-template-part' ) ||
		item.closest( '.wp-block-navigation' ) ||
		item;
	const vars = placement( {
		base: ( panel.offsetParent || item ).getBoundingClientRect(),
		bar: bar.getBoundingClientRect(),
		item: item.getBoundingClientRect(),
		width: panel.offsetWidth,
		viewport,
		height: doc.defaultView.innerHeight,
		gap:
			parseFloat(
				doc.defaultView
					.getComputedStyle( panel )
					.getPropertyValue( '--purplish-wardrobe-gap' )
			) || 0,
	} );
	Object.keys( vars ).forEach( ( key ) => set( key, vars[ key ] ) );
}

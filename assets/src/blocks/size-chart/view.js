/**
 * Size Chart front script. The chart and the Size guide dialog work without
 * it: two radios switch cm/in through CSS, and the dialog opens with
 * Invoker Commands. This adds what a page can't do alone:
 *
 * - Fallbacks for browsers without `command`/`commandfor` or `closedby`.
 * - One unit for every chart on the page, kept for the visit.
 * - Pick a size from the chart: a size presses the matching chip.
 * - The picked size's row lights up, and its values sit beside the notes.
 * - A measurement lights up across the table, notes and figure.
 */
( () => {
	const doc = document;
	const { CSS, HTMLButtonElement, HTMLDialogElement, MutationObserver } =
		window;
	const KEY = 'purplish-size-unit';
	const CHART = '.wp-block-purplish-size-chart[data-unit]';
	const UNITS = '.purplish-size-chart__units input';

	const stored = () => {
		try {
			return window.sessionStorage.getItem( KEY );
		} catch {
			return null;
		}
	};
	const store = ( unit ) => {
		try {
			window.sessionStorage.setItem( KEY, unit );
		} catch {}
	};

	if ( ! ( 'command' in HTMLButtonElement.prototype ) ) {
		doc.addEventListener( 'click', ( event ) => {
			const button = event.target.closest( 'button[commandfor]' );
			const target =
				button &&
				doc.getElementById( button.getAttribute( 'commandfor' ) );
			if ( ! ( target instanceof HTMLDialogElement ) ) {
				return;
			}
			const command = button.getAttribute( 'command' );
			if ( 'show-modal' === command && ! target.open ) {
				target.showModal();
			} else if ( 'close' === command ) {
				target.close();
			}
		} );
	}

	if ( ! ( 'closedBy' in HTMLDialogElement.prototype ) ) {
		doc.addEventListener( 'click', ( event ) => {
			const dialog = event.target;
			if (
				! ( dialog instanceof HTMLDialogElement ) ||
				! dialog.classList.contains( 'purplish-size-guide' )
			) {
				return;
			}
			const box = dialog.getBoundingClientRect();
			const inside =
				event.clientX >= box.left &&
				event.clientX <= box.right &&
				event.clientY >= box.top &&
				event.clientY <= box.bottom;
			if ( ! inside ) {
				dialog.close();
			}
		} );
	}

	const live = doc.createElement( 'p' );
	live.className = 'screen-reader-text';
	live.setAttribute( 'aria-live', 'polite' );
	doc.body.appendChild( live );

	const charts = () => Array.from( doc.querySelectorAll( CHART ) );
	const same = ( a, b ) =>
		String( a ).trim().toLowerCase() === String( b ).trim().toLowerCase();

	/**
	 * The chips of the attribute a chart picks from.
	 *
	 * @param {Element} chart Chart.
	 * @return {Element[]} Chips.
	 */
	const chipsOf = ( chart ) => {
		const field = chart.getAttribute( 'data-chips' );
		return field
			? Array.from(
					doc.querySelectorAll(
						`[role="radio"][id^="${ CSS.escape( field ) }-"]`
					)
				)
			: [];
	};
	const chipFor = ( chart, size ) =>
		chipsOf( chart ).find(
			( chip ) =>
				same( chip.value, size ) ||
				same( chip.getAttribute( 'aria-label' ) || '', size )
		);
	const out = ( chip ) =>
		! chip ||
		chip.disabled ||
		chip.hidden ||
		'true' === chip.getAttribute( 'aria-disabled' );

	/**
	 * The size picked in the chips, as the chart names it.
	 *
	 * @param {Element} chart Chart.
	 * @return {string} Size, or ''.
	 */
	const picked = ( chart ) => {
		const chip = chipsOf( chart ).find(
			( one ) => 'true' === one.getAttribute( 'aria-checked' )
		);
		if ( ! chip ) {
			return '';
		}
		const row = Array.from(
			chart.querySelectorAll( 'tbody tr[data-size]' )
		).find(
			( one ) =>
				same( one.getAttribute( 'data-size' ), chip.value ) ||
				same(
					one.getAttribute( 'data-size' ),
					chip.getAttribute( 'aria-label' ) || ''
				)
		);
		return row ? row.getAttribute( 'data-size' ) : '';
	};

	const paint = () => {
		charts().forEach( ( chart ) => {
			const size = picked( chart );
			const unit = chart.getAttribute( 'data-unit' );
			chart.querySelectorAll( 'tbody tr' ).forEach( ( row ) => {
				const on =
					'' !== size && row.getAttribute( 'data-size' ) === size;
				row.classList.toggle( 'is-selected', on );
				const button = row.querySelector(
					'.purplish-size-chart__pick'
				);
				if ( button ) {
					button.setAttribute(
						'aria-pressed',
						on ? 'true' : 'false'
					);
					button.disabled = out(
						chipFor( chart, row.getAttribute( 'data-size' ) )
					);
				}
			} );
			chart
				.querySelectorAll( '.purplish-size-chart__value' )
				.forEach( ( value ) => {
					const key = value
						.closest( '[data-m]' )
						.getAttribute( 'data-m' );
					const cell = size
						? chart.querySelector(
								`tr[data-size="${ CSS.escape(
									size
								) }"] td[data-m="${ key }"]`
							)
						: null;
					const number = cell && cell.getAttribute( 'data-' + unit );
					value.textContent = number
						? `${ size }: ${ number } ${ unit }`
						: '';
				} );
		} );
	};

	const setUnit = ( unit ) => {
		charts().forEach( ( chart ) => {
			chart.setAttribute( 'data-unit', unit );
			chart
				.querySelectorAll( `${ UNITS }[value="${ unit }"]` )
				.forEach( ( input ) => {
					input.checked = true;
				} );
		} );
		paint();
	};

	doc.addEventListener( 'change', ( event ) => {
		if ( event.target.matches( UNITS ) ) {
			store( event.target.value );
			setUnit( event.target.value );
		}
	} );

	doc.addEventListener( 'click', ( event ) => {
		const button = event.target.closest( '.purplish-size-chart__pick' );
		const chart = button && button.closest( CHART );
		if ( ! chart || button.disabled ) {
			return;
		}
		const chip = chipFor( chart, button.getAttribute( 'data-size' ) );
		if ( out( chip ) ) {
			return;
		}
		if ( 'true' !== chip.getAttribute( 'aria-checked' ) ) {
			chip.click();
		}
		live.textContent = chip.getAttribute( 'aria-label' ) || chip.value;
		const dialog = button.closest( 'dialog' );
		if ( dialog && dialog.open ) {
			dialog.close();
			chip.focus();
		}
	} );

	const mark = ( event, on ) => {
		const chart = event.target.closest && event.target.closest( CHART );
		if ( ! chart ) {
			return;
		}
		const part = on && event.target.closest( '[data-m]' );
		const key = part ? part.getAttribute( 'data-m' ) : '';
		chart.querySelectorAll( '[data-m]' ).forEach( ( one ) => {
			one.classList.toggle(
				'is-active',
				'' !== key && one.getAttribute( 'data-m' ) === key
			);
		} );
	};
	doc.addEventListener( 'pointerover', ( event ) => mark( event, true ) );
	doc.addEventListener( 'pointerout', ( event ) => mark( event, false ) );
	doc.addEventListener( 'focusin', ( event ) => mark( event, true ) );
	doc.addEventListener( 'focusout', ( event ) => mark( event, false ) );

	const watch = new MutationObserver( paint );
	doc.querySelectorAll(
		'.wp-block-woocommerce-add-to-cart-with-options-variation-selector'
	).forEach( ( selector ) =>
		watch.observe( selector, {
			subtree: true,
			attributes: true,
			attributeFilter: [ 'aria-checked', 'disabled', 'hidden' ],
		} )
	);

	const unit = stored();
	if ( 'cm' === unit || 'in' === unit ) {
		setUnit( unit );
	} else {
		paint();
	}
} )();

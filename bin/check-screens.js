/**
 * Screen check: opens each page at a phone and a desktop width, with the
 * theme header as it is, sticky and fixed, and scrolls through it. Fails
 * when something that sticks (a filters sidebar, a toolbar) sits under the
 * header, or runs past the bottom of the window without scrolling inside
 * itself, so its last items can't be reached.
 *
 * Run it on a test site before a release:
 * `npm run check:screens -- https://shop.test/shop/ https://shop.test/sale/`
 * It uses the Chrome on this computer; set CHROME_PATH when it lives
 * somewhere else.
 */
const fs = require( 'fs' );
const puppeteer = require( 'puppeteer-core' );

const CHROME =
	process.env.CHROME_PATH ||
	[
		'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
		'/usr/bin/google-chrome',
		'/usr/bin/chromium',
		'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
	].find( ( file ) => fs.existsSync( file ) );
const SCREENS = [
	{ width: 390, height: 844, isMobile: true, hasTouch: true },
	{ width: 1440, height: 900 },
];
const HEADER = 'header.wp-block-template-part, #masthead, body > header';
const MODES = {
	'as it is': '',
	sticky: '[data-screen-header]{position:sticky!important;top:var(--wp-admin--admin-bar--height,0px)!important;z-index:60!important}',
	fixed: '[data-screen-header]{position:fixed!important;top:var(--wp-admin--admin-bar--height,0px)!important;left:0!important;right:0!important;z-index:60!important}body{padding-top:var(--screen-header-height)!important}',
};

/**
 * Runs in the page: marks the header, then scrolls through and lists what
 * sticks and collides with it or runs past the window.
 *
 * @param {string} headerSelector Where the theme header is.
 * @param {string} css            The header mode's CSS.
 * @return {Promise<string[]>} The problems found.
 */
async function inPage( headerSelector, css ) {
	const wait = ( ms ) => new Promise( ( done ) => setTimeout( done, ms ) );
	const view = window.innerHeight;
	const CONTROLS = 'a[href], button, input, select, textarea, [tabindex]';
	const SEEN = { opacityProperty: true, visibilityProperty: true };
	const header = document.querySelector( headerSelector );
	if ( ! header ) {
		return [ 'no theme header found' ];
	}
	header.setAttribute( 'data-screen-header', '' );
	document.documentElement.style.setProperty(
		'--screen-header-height',
		header.offsetHeight + 'px'
	);
	const style = document.createElement( 'style' );
	style.textContent = css;
	document.head.append( style );
	await wait( 300 );

	const skip = ( el ) =>
		header.contains( el ) ||
		el.closest( '#wpadminbar' ) ||
		el.contains( header );
	const pinned = [ ...document.body.querySelectorAll( '*' ) ].filter(
		( el ) =>
			[ 'sticky', 'fixed' ].includes(
				window.getComputedStyle( el ).position
			) && ! skip( el )
	);
	const name = ( el ) =>
		el.tagName.toLowerCase() +
		[ ...el.classList ]
			.slice( 0, 2 )
			.map( ( c ) => '.' + c )
			.join( '' );
	const problems = new Set();
	const end = document.documentElement.scrollHeight;
	const step = Math.max( 200, Math.round( view / 2 ) );
	for ( let y = 0; y <= end; y += step ) {
		window.scrollTo( 0, y );
		await wait( 120 );
		const headerBox = header.getBoundingClientRect();
		for ( const el of pinned ) {
			const box = el.getBoundingClientRect();
			const cs = window.getComputedStyle( el );
			const stuck =
				'fixed' === cs.position ||
				Math.abs( box.top - parseFloat( cs.top ) ) < 2;
			if (
				! stuck ||
				! box.height ||
				box.bottom <= 0 ||
				box.top >= view
			) {
				continue;
			}
			// Under the header: a control inside the stuck box is hidden by
			// it. Overlays and loading bars hold no controls, so they pass.
			const covered =
				headerBox.bottom > box.top &&
				[ ...el.querySelectorAll( CONTROLS ) ].some( ( control ) => {
					const spot = control.getBoundingClientRect();
					const x = spot.left + spot.width / 2;
					const py = spot.top + spot.height / 2;
					if (
						! spot.width ||
						! control.checkVisibility( SEEN ) ||
						py < Math.max( box.top, 0 ) ||
						py > Math.min( box.bottom, view )
					) {
						return false;
					}
					const hit = document.elementFromPoint( x, py );
					return hit && header.contains( hit );
				} );
			if ( covered ) {
				problems.add( `${ name( el ) } sits under the header` );
			}
			// Past the bottom with nothing to scroll inside it.
			if (
				box.bottom > view + 1 &&
				box.top >= 0 &&
				el.scrollHeight <= el.clientHeight + 1 &&
				'sticky' === cs.position
			) {
				problems.add(
					`${ name( el ) } runs ${ Math.round(
						box.bottom - view
					) }px past the bottom of the window`
				);
			}
		}
	}
	return [ ...problems ];
}

( async () => {
	const urls = process.argv.slice( 2 );
	if ( ! urls.length ) {
		process.stdout.write(
			'Give the pages to check: npm run check:screens -- <url> [<url>...]\n'
		);
		process.exitCode = 1;
		return;
	}
	if ( ! CHROME ) {
		process.stdout.write( 'Chrome not found; set CHROME_PATH.\n' );
		process.exitCode = 1;
		return;
	}
	const browser = await puppeteer.launch( {
		executablePath: CHROME,
		headless: true,
		args: [ '--ignore-certificate-errors' ],
	} );
	let failed = 0;
	for ( const url of urls ) {
		for ( const screen of SCREENS ) {
			for ( const [ mode, css ] of Object.entries( MODES ) ) {
				const page = await browser.newPage();
				await page.setViewport( screen );
				await page.goto( url, { waitUntil: 'networkidle2' } );
				const problems = await page.evaluate( inPage, HEADER, css );
				await page.close();
				const where = `${ url } at ${ screen.width }px, header ${ mode }`;
				if ( problems.length ) {
					failed++;
					process.stdout.write( `x  ${ where }\n` );
					problems.forEach( ( problem ) =>
						process.stdout.write( `     ${ problem }\n` )
					);
				} else {
					process.stdout.write( `ok ${ where }\n` );
				}
			}
		}
	}
	await browser.close();
	process.stdout.write(
		failed
			? `\n${ failed } screen(s) failed.\n`
			: '\nEvery screen passed.\n'
	);
	process.exitCode = failed ? 1 : 0;
} )();

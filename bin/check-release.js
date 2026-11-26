/**
 * Release check: catches the slips that are easy to miss before a tag.
 * Versions that disagree, a translation template with the wrong slug or
 * missing strings, a version with no changelog entry, requirements that
 * differ between files, a committed build that doesn't match the source,
 * local paths or test site names in the files that ship, and a pull
 * request since the last tag that left something it found undecided.
 *
 * Run `npm run check:release` before tagging; it builds first. CI runs
 * this file right after its own build. The template's strings are only
 * compared where WP-CLI is installed, and pull requests are only read
 * where the GitHub CLI is; CI skips those two checks.
 */
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );
const { execFileSync } = require( 'child_process' );

const ROOT = path.join( __dirname, '..' );
const SLUG = 'purplish';
const POT = 'languages/purplish.pot';
// The same arguments make every template, so a regenerated one matches.
const MAKE_POT = [
	'i18n',
	'make-pot',
	'.',
	null,
	`--slug=${ SLUG }`,
	'--exclude=node_modules,vendor,planning,_trash,tests,bin',
	'--skip-audit',
];
// Strings that only belong on Shameem's machine or a test site.
const LOCAL = [
	/\/Users\//,
	/\/home\/[a-z]/,
	/https?:\/\/localhost/,
	/https?:\/\/[\w.-]+\.test\b/,
	/127\.0\.0\.1/,
	/violetloom/,
	/purplish-shop-gaps/,
];
const DEBUG =
	/\b(var_dump|print_r|error_log)\s*\(|console\.log\s*\(|\bdebugger\s*;/;

const read = ( file ) => fs.readFileSync( path.join( ROOT, file ), 'utf8' );
const git = ( ...args ) =>
	execFileSync( 'git', args, { cwd: ROOT, encoding: 'utf8' } );
const field = ( text, name ) => {
	const match = text.match(
		new RegExp( `^[\\s*]*${ name }:[ \\t]*(.+?)\\s*$`, 'mi' )
	);
	return match ? match[ 1 ] : null;
};

const plugin = read( 'purplish.php' );
const readme = read( 'readme.txt' );
const changelog = read( 'CHANGELOG.md' );
const readmeMd = read( 'README.md' );
const pot = read( POT );
const pkg = JSON.parse( read( 'package.json' ) );
const lock = JSON.parse( read( 'package-lock.json' ) );
const version = field( plugin, 'Version' );

/**
 * The entries of a .pot file as "context|single|plural" keys, so a string
 * that moves to another line doesn't count as a change.
 *
 * @param {string} text The file.
 * @return {Set<string>} One key per entry, the header left out.
 */
const potStrings = ( text ) => {
	const keys = new Set();
	for ( const block of text.split( /\n\s*\n/ ) ) {
		const parts = {};
		let current = null;
		for ( const line of block.split( '\n' ) ) {
			const start = line.match(
				/^(msgctxt|msgid_plural|msgid)\s+"(.*)"$/
			);
			if ( start ) {
				current = start[ 1 ];
				parts[ current ] = start[ 2 ];
			} else if ( current && /^".*"$/.test( line ) ) {
				parts[ current ] += line.slice( 1, -1 );
			} else if ( ! line.startsWith( '#' ) ) {
				current = null;
			}
		}
		if ( parts.msgid ) {
			keys.add(
				[ parts.msgctxt, parts.msgid, parts.msgid_plural ].join( '|' )
			);
		}
	}
	return keys;
};

/**
 * Files that go to WordPress.org: tracked files minus .distignore, whose
 * lines are either `/top-level-path` or a name that matches anywhere.
 *
 * @return {string[]} Paths relative to the plugin folder.
 */
const shipped = () => {
	const rules = read( '.distignore' )
		.split( '\n' )
		.map( ( line ) => line.trim() )
		.filter( ( line ) => line && ! line.startsWith( '#' ) );
	return git( 'ls-files' )
		.split( '\n' )
		.filter( Boolean )
		.filter(
			( file ) =>
				! rules.some( ( rule ) =>
					rule.startsWith( '/' )
						? file === rule.slice( 1 ) ||
							file.startsWith( rule.slice( 1 ) + '/' )
						: file.split( '/' ).includes( rule )
				)
		);
};

/**
 * What a pull request body says under "Found while testing", comments left
 * out, or null when it has no such section.
 *
 * @param {string} body The body.
 * @return {string[]|null} The section's lines.
 */
const foundWhileTesting = ( body ) => section( body, 'Found while testing' );

/**
 * The lines of a pull request body's section with this heading, comments
 * left out, or null when it has no such section.
 *
 * @param {string} body  The body.
 * @param {string} title The heading.
 * @return {string[]|null} The section's lines.
 */
const section = ( body, title ) => {
	const match = ( body || '' )
		.replace( /<!--[\s\S]*?-->/g, '' )
		.match(
			new RegExp(
				`^#{2,3} ${ title }:?[ \\t]*\\r?\\n([\\s\\S]*?)(?=^#{1,3} |(?![\\s\\S]))`,
				'm'
			)
		);
	if ( ! match ) {
		return null;
	}
	return match[ 1 ]
		.split( /\r?\n/ )
		.map( ( line ) => line.replace( /^\s*[-*]\s*/, '' ).trim() )
		.filter( Boolean );
};

const checks = {
	'Version matches everywhere': () => {
		const found = {
			'purplish.php Version': version,
			PURPLISH_VERSION: ( plugin.match(
				/define\(\s*'PURPLISH_VERSION',\s*'([^']+)'/
			) || [] )[ 1 ],
			'readme.txt Stable tag': field( readme, 'Stable tag' ),
			'package.json': pkg.version,
			'package-lock.json': lock.version,
			'package-lock.json packages': lock.packages?.[ '' ]?.version,
			[ `${ POT } Project-Id-Version` ]: ( pot.match(
				/"Project-Id-Version: .* ([\d.]+)\\n"/
			) || [] )[ 1 ],
		};
		return Object.entries( found )
			.filter( ( [ , value ] ) => value !== version )
			.map(
				( [ where, value ] ) =>
					`${ where } is ${ value }, not ${ version }`
			);
	},

	'Translation template header': () => {
		const problems = [];
		const bugs = ( pot.match( /"Report-Msgid-Bugs-To: (.*)\\n"/ ) ||
			[] )[ 1 ];
		if ( bugs !== `https://wordpress.org/support/plugin/${ SLUG }` ) {
			problems.push(
				`Report-Msgid-Bugs-To is ${ bugs }; regenerate with npm run make-pot`
			);
		}
		if ( ! pot.includes( `"X-Domain: ${ SLUG }\\n"` ) ) {
			problems.push( `X-Domain isn't ${ SLUG }` );
		}
		return problems;
	},

	'Translation template has every string': () => {
		const out = path.join(
			fs.mkdtempSync( path.join( os.tmpdir(), 'purplish-pot-' ) ),
			'purplish.pot'
		);
		try {
			execFileSync(
				'wp',
				MAKE_POT.map( ( arg ) => ( arg === null ? out : arg ) ),
				{ cwd: ROOT, stdio: 'ignore' }
			);
		} catch ( error ) {
			if ( process.env.CI ) {
				return { skipped: 'WP-CLI is not installed here' };
			}
			return [ `WP-CLI couldn't make a template: ${ error.message }` ];
		}
		const want = potStrings( fs.readFileSync( out, 'utf8' ) );
		const have = potStrings( pot );
		const missing = [ ...want ].filter( ( key ) => ! have.has( key ) );
		const extra = [ ...have ].filter( ( key ) => ! want.has( key ) );
		return missing.length || extra.length
			? [
					`${ missing.length } strings missing and ${ extra.length } gone; run npm run make-pot`,
				]
			: [];
	},

	'Changelog entries for this version': () => {
		const problems = [];
		const notes = readme.split( /^== Changelog ==$/m )[ 1 ] || '';
		const first = ( notes.match( /^= ([^=]+?) =$/m ) || [] )[ 1 ];
		if ( first !== version ) {
			problems.push(
				`readme.txt's first changelog entry is ${ first }, not ${ version }`
			);
		}
		const top = changelog.match(
			/^## \[([^\]]+)\] - (\d{4}-\d{2}-\d{2})$/m
		);
		if ( ! top || top[ 1 ] !== version ) {
			problems.push(
				`CHANGELOG.md's first entry is ${ top?.[ 1 ] }, not ${ version } with a date`
			);
		}
		return problems;
	},

	'Requirements match across files': () => {
		const row = ( name ) => {
			const match = readmeMd.match(
				new RegExp(
					`^\\|\\s*${ name }\\s*\\|\\s*([^|]+?)\\s*\\|\\s*([^|]+?)\\s*\\|`,
					'm'
				)
			);
			return match ? { min: match[ 1 ], tested: match[ 2 ] } : {};
		};
		const wp = row( 'WordPress' );
		const php = row( 'PHP' );
		const wc = row( 'WooCommerce' );
		const pairs = [
			[
				'WordPress minimum',
				field( plugin, 'Requires at least' ),
				field( readme, 'Requires at least' ),
				wp.min,
			],
			[ 'WordPress tested', field( readme, 'Tested up to' ), wp.tested ],
			[
				'PHP minimum',
				field( plugin, 'Requires PHP' ),
				field( readme, 'Requires PHP' ),
				php.min,
			],
			[
				'WooCommerce minimum',
				field( plugin, 'WC requires at least' ),
				wc.min,
			],
			[
				'WooCommerce tested',
				field( plugin, 'WC tested up to' ),
				wc.tested,
			],
		];
		return pairs
			.filter(
				( [ , ...values ] ) =>
					new Set( values ).size !== 1 || ! values[ 0 ]
			)
			.map(
				( [ name, ...values ] ) =>
					`${ name } differs (purplish.php/readme.txt/README.md order): ${ values.join( ' vs ' ) }`
			);
	},

	'readme.txt follows the directory rules': () => {
		const problems = [];
		const tags = ( field( readme, 'Tags' ) || '' )
			.split( ',' )
			.filter( Boolean );
		if ( tags.length > 5 ) {
			problems.push( `${ tags.length } tags; WordPress.org shows 5` );
		}
		if ( ! /^\d+\.\d+$/.test( field( readme, 'Tested up to' ) || '' ) ) {
			problems.push( 'Tested up to should be a major version like 7.1' );
		}
		const short = readme.split( /\n\s*\n/ )[ 1 ]?.trim() || '';
		if ( short.length > 150 ) {
			problems.push(
				`short description is ${ short.length } characters; the limit is 150`
			);
		}
		return problems;
	},

	'readme.txt counts what ships': () => {
		const patterns = shipped().filter( ( file ) =>
			/^patterns\/[^/]+\.php$/.test( file )
		).length;
		return [ ...readme.matchAll( /(?<![\d.])(\d+) patterns\b/g ) ]
			.filter( ( match ) => Number( match[ 1 ] ) !== patterns )
			.map(
				( match ) =>
					`readme.txt says "${ match[ 0 ] }", the plugin ships ${ patterns }`
			);
	},

	'Committed build matches the source': () => {
		const changed = git( 'status', '--porcelain', '--', 'build' ).trim();
		return changed
			? [ `build/ changes after a fresh build; commit it:\n${ changed }` ]
			: [];
	},

	'Shipped files are clean': () => {
		const problems = [];
		for ( const file of shipped() ) {
			if (
				/\.md$|^(tests|planning|node_modules|vendor|bin)\//.test( file )
			) {
				problems.push( `${ file } ships; add it to .distignore` );
				continue;
			}
			if ( ! /\.(php|js|css|json|txt|pot|svg|html)$/.test( file ) ) {
				continue;
			}
			const text = read( file );
			const local = LOCAL.find( ( pattern ) => pattern.test( text ) );
			if ( local ) {
				problems.push(
					`${ file } has a local name or path (${ local })`
				);
			}
			if ( ! file.startsWith( 'build/' ) && DEBUG.test( text ) ) {
				problems.push(
					`${ file } has debug output (${ text.match( DEBUG )[ 0 ] })`
				);
			}
		}
		return problems;
	},

	'Pull requests since the last tag settled what they found': () => {
		// Squash merges end their subject with the pull request's number.
		// Those merged before the template had the section are left out.
		const added = git(
			'log',
			'--reverse',
			'--format=%H',
			'-S',
			'Found while testing',
			'--',
			'.github/PULL_REQUEST_TEMPLATE.md'
		)
			.split( '\n' )
			.filter( Boolean )[ 0 ];
		if ( ! added ) {
			return [];
		}
		let tag;
		try {
			tag = execFileSync( 'git', [ 'describe', '--tags', '--abbrev=0' ], {
				cwd: ROOT,
				encoding: 'utf8',
				stdio: [ 'ignore', 'pipe', 'ignore' ],
			} ).trim();
		} catch {
			if ( process.env.CI ) {
				return { skipped: 'this checkout has no tags' };
			}
			return [
				'no release tag found; fetch the tags with git fetch --tags',
			];
		}
		const after = ( range ) =>
			git( 'log', '--format=%H %s', range )
				.split( '\n' )
				.filter( Boolean );
		const sinceTemplate = new Set(
			after( `${ added }~1..HEAD` ).map(
				( line ) => line.split( ' ' )[ 0 ]
			)
		);
		// Pull requests merged once the template asked for a changelog line
		// either change CHANGELOG.md or write None under "Changelog".
		const askedChangelog = git(
			'log',
			'--reverse',
			'--format=%H',
			'-S',
			'## Changelog',
			'--',
			'.github/PULL_REQUEST_TEMPLATE.md'
		)
			.split( '\n' )
			.filter( Boolean )[ 0 ];
		const sinceChangelog = new Set(
			askedChangelog
				? after( `${ askedChangelog }~1..HEAD` ).map(
						( line ) => line.split( ' ' )[ 0 ]
					)
				: []
		);
		const merged = after( `${ tag }..HEAD` )
			.filter( ( line ) => sinceTemplate.has( line.split( ' ' )[ 0 ] ) )
			.map( ( line ) => [
				line.split( ' ' )[ 0 ],
				( line.match( /\(#(\d+)\)$/ ) || [] )[ 1 ],
			] )
			.filter( ( [ , number ] ) => number );
		const problems = [];
		for ( const [ hash, number ] of merged ) {
			let body;
			try {
				body = execFileSync(
					'gh',
					[ 'pr', 'view', number, '--json', 'body', '--jq', '.body' ],
					{
						cwd: ROOT,
						encoding: 'utf8',
						stdio: [ 'ignore', 'pipe', 'ignore' ],
					}
				);
			} catch ( error ) {
				if ( process.env.CI ) {
					return { skipped: 'the GitHub CLI is not signed in here' };
				}
				return [ `couldn't read #${ number }: ${ error.message }` ];
			}
			const lines = foundWhileTesting( body );
			if ( ! lines || ! lines.length ) {
				problems.push(
					`#${ number } has no answer under "Found while testing"; write None, or each problem with "fixed here", its issue or "no change: <reason>"`
				);
			} else if ( ! (
				1 === lines.length && /^none\.?$/i.test( lines[ 0 ] )
			) ) {
				lines
					.filter(
						( line ) => ! /fixed here|#\d+|no change:/i.test( line )
					)
					.forEach( ( line ) =>
						problems.push(
							`#${ number } left "${ line }" undecided; fix it, open an issue or write "no change: <reason>"`
						)
					);
			}
			if ( sinceChangelog.has( hash ) ) {
				const changed = git( 'show', '--name-only', '--format=', hash )
					.split( '\n' )
					.includes( 'CHANGELOG.md' );
				const notes = (
					section( body, 'Changelog(?: entry)?' ) || []
				).map( ( line ) => line.replace( /^>\s*/, '' ) );
				if ( ! changed && ! /^none\b/i.test( notes[ 0 ] || '' ) ) {
					problems.push(
						`#${ number } added no line to CHANGELOG.md; add it under [Unreleased], or write "None" and why under "Changelog" in the pull request`
					);
				}
			}
		}
		return problems;
	},
};

let failed = 0;
for ( const [ name, check ] of Object.entries( checks ) ) {
	const result = check();
	if ( result.skipped ) {
		process.stdout.write( `-  ${ name }: skipped, ${ result.skipped }\n` );
	} else if ( result.length ) {
		failed++;
		process.stdout.write( `x  ${ name }\n` );
		result.forEach( ( problem ) =>
			process.stdout.write( `     ${ problem }\n` )
		);
	} else {
		process.stdout.write( `ok ${ name }\n` );
	}
}
process.stdout.write(
	failed
		? `\n${ failed } check(s) failed for ${ version }.\n`
		: `\nReady to release ${ version }.\n`
);
process.exitCode = failed ? 1 : 0;

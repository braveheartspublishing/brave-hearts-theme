<?php
/**
 * ONE CACHE-BUSTING RULE FOR EVERY ENQUEUED STYLESHEET AND SCRIPT.
 * Theme 1.19.397. Workstream `CYCLE179-LD-BUILD-397`.
 * ============================================================================
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ THE DEFECT THIS CLOSES — OBSERVED DURING A BUILD, NOT ANTICIPATED
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ `commerce-cx` installed FOUR different artefacts onto staging under the
 *    one version string `1.19.396` on 2026-09-07. Every one of them enqueued
 *    `style.min.css` as `?ver=1.19.396`. ⛔ A BROWSER THAT HAD LOADED ANY
 *    EARLIER ONE KEPT SERVING IT, because the URL had not changed and a
 *    SiteGround purge clears the SERVER cache, never the client's.
 *
 * ⭐ HOW IT PRESENTED, AND WHY IT IS WORSE THAN AN INCONVENIENCE: the fix was
 *    in the served file (proved with a cache-busted `fetch()`), its selector
 *    matched the element, and the computed padding still did not move — while
 *    the CSSOM held the PREVIOUS build's selector text. The reviewer's honest
 *    reading of that evidence is "the fix does not work", and the next move
 *    after that reading is to change correct code. ⛔ A stale stylesheet does
 *    not announce itself; it produces a confident wrong conclusion.
 *
 * ⛔ IT REACHES ANDREW, NOT ONLY QA. Anyone reviewing staging in a browser
 *    they have used before can be looking at an older stylesheet while the
 *    server serves the newer one, with no visible signal anywhere.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THE RULE, AND WHY IT IS ONE FILTER RATHER THAN 105 EDITS
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * The theme has **105** `wp_enqueue_*` / `wp_register_*` call sites and the
 * bundle plugin adds more. Passing a computed version at each of them would
 * have been 105 chances to miss one, 105 places for the next author to
 * reintroduce a bare `$theme_version`, and no way to assert completeness.
 *
 * ⭐ WordPress applies `style_loader_src` and `script_loader_src` to the URL of
 *    EVERY enqueued asset, whatever registered it. One callback on each,
 *    keyed on the file the URL actually points at, covers every existing call
 *    site and every future one automatically — including a call site that
 *    passes `null` and would otherwise inherit the WordPress version.
 *
 * **The stamp is `<incoming ver>.<filemtime>`**, e.g.
 * `style.min.css?ver=1.19.397.1788797132`. Both halves earn their place:
 *   - the incoming `ver` keeps the release readable in DevTools and in a HAR,
 *     which a bare mtime destroys;
 *   - the mtime is what makes two artefacts of the SAME version distinct,
 *     which is precisely the case that broke.
 *
 * ⭐ THIS IS NOT A NEW CONVENTION. `inc/early-cart-capture.php` has shipped
 *    `?ver=1.19.396.1788797132` for months by appending `filemtime()` by hand
 *    (lines 520 and 525). ⛔ That is the correct idea applied to two files out
 *    of a hundred-plus. This generalises the house pattern rather than
 *    inventing a rival to it, and those two hand-rolled stamps are left alone:
 *    they still produce the same shape and this filter is idempotent against
 *    them (see the guard below).
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ MTIME, NOT A CONTENT HASH — AND THE REASON IS A MEASUREMENT, NOT A TASTE
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * A content hash (`md5_file()`) is the more precise signal: it changes only
 * when the bytes change. ⛔ It also READS EVERY ENQUEUED FILE ON EVERY PAGE
 * LOAD. `style.min.css` alone is ~200 KB and a typical page enqueues a dozen
 * assets; that is real I/O on every uncached request, on shared hosting, to
 * solve a problem that a `stat()` already solves.
 *
 * ⭐ `filemtime()` is a `stat`, served from PHP's own stat cache within a
 *    request, and it is what `wp_enqueue_*` documentation itself suggests.
 *
 * ⛔ THE TWO THINGS MTIME GETS WRONG, STATED RATHER THAN HIDDEN:
 *   1. **A touched-but-unchanged file busts the cache anyway.** That is a
 *      wasted download, not a wrong page. Wrong direction is safe.
 *   2. **Two servers can hold different mtimes for identical bytes.** Staging
 *      and production therefore carry different `ver` strings for the same
 *      release. They are separate origins with separate caches, so nothing
 *      shares a cache entry across them, and the release is still legible
 *      because the version half of the stamp is identical on both.
 *
 * ⭐ THE DEPLOY MECHANISM MAKES THIS WORK, AND IT WAS CHECKED. `git archive`
 *    stamps every entry with the archive's own creation time when it archives
 *    a TREE (there is no commit to take a date from — see the RUNBOOK's
 *    temporary-index build). `wp theme install --force` deletes the directory
 *    and extracts fresh. So every artefact lands with new mtimes and every
 *    build busts the cache, which is the property the 396 incident needed and
 *    did not have.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ WHAT THIS FILE DOES NOT TOUCH
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *   - Anything not physically inside this theme's directory or the bundle
 *     plugin's directory. WordPress core, WooCommerce, Stripe, Jetbacks,
 *     every other plugin: their `?ver=` is theirs and is returned unchanged.
 *   - Any URL whose file does not exist on disk — a CDN URL, a
 *     dynamically-generated `admin-ajax` source, a typo'd path. Returned
 *     unchanged, so a broken enqueue keeps its original, debuggable URL.
 *   - Any external host. The comparison is a prefix match against this
 *     installation's own directory URLs; nothing else can match it.
 *   - No option, product, price, coupon, stock, shipping, tax, payment or
 *     checkout setting is read or written by this file, on any environment.
 *
 * ⭐ IT RUNS ON PRODUCTION TOO, DELIBERATELY. This is not a staging guard. The
 *    identical failure on production is a customer holding a stale stylesheet
 *    after a release, and that is the more expensive version of it.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Priority for both loader-src filters.
 *
 * ⛔ IT MUST BE LATER THAN 10, AND THAT IS NOT ARBITRARY.
 *    `bhp_minified_style_src()` (functions.php) sits on `style_loader_src` at
 *    priority 10 and REWRITES THE PATH, swapping `foo.css` for `foo.min.css`
 *    while carrying the original query string across untouched. Stamping
 *    before it would stamp the mtime of the SOURCE file onto a URL that then
 *    becomes the ARTEFACT's — so a rebuilt `.min.css` whose source had not
 *    been touched would ship under an unchanged `ver`. That is the exact
 *    defect this file exists to close, reintroduced one hop upstream.
 */
if ( ! defined( 'BHP_ASSET_VERSION_PRIORITY' ) ) {
	define( 'BHP_ASSET_VERSION_PRIORITY', 20 );
}

/**
 * The URL-prefix → filesystem-directory pairs this filter is allowed to stamp.
 *
 * ⭐ Both the template and stylesheet directories are listed even though this
 *    installation runs no child theme, because the cost is one array entry and
 *    the failure if a child theme is ever added is silent.
 *
 * @return array<string,string> Untrailingslashed URL => untrailingslashed path.
 */
function bhp_asset_version_roots() {
	$roots = array();

	$pairs = array(
		array( get_template_directory_uri(), get_template_directory() ),
		array( get_stylesheet_directory_uri(), get_stylesheet_directory() ),
	);

	if ( defined( 'BHP_BUNDLE_PRICING_URL' ) && defined( 'BHP_BUNDLE_PRICING_DIR' ) ) {
		$pairs[] = array( BHP_BUNDLE_PRICING_URL, BHP_BUNDLE_PRICING_DIR );
	}

	foreach ( $pairs as $pair ) {
		$uri = untrailingslashit( (string) $pair[0] );
		$dir = untrailingslashit( (string) $pair[1] );
		if ( '' === $uri || '' === $dir ) {
			continue;
		}
		$roots[ bhp_asset_version_strip_scheme( $uri ) ] = $dir;
	}

	/**
	 * Extend the set of directories whose assets get an mtime-stamped `ver`.
	 *
	 * @param array<string,string> $roots Scheme-less URL prefix => absolute path.
	 */
	return (array) apply_filters( 'bhp_asset_version_roots', $roots );
}

/**
 * Drop `https:` / `http:` from the front of a URL so a scheme mismatch between
 * `home_url()` and the enqueued src cannot silently defeat the prefix match.
 *
 * ⛔ A scheme mismatch is not hypothetical on this stack: SiteGround terminates
 *    TLS at the edge, and a mis-set `$_SERVER['HTTPS']` has produced http-form
 *    URLs on this installation before. A prefix test that fails on that would
 *    stamp nothing and report no error, which is the worst shape of failure.
 *
 * @param string $url
 * @return string
 */
function bhp_asset_version_strip_scheme( $url ) {
	return preg_replace( '#^https?:#i', '', (string) $url );
}

/**
 * The stamp for one file on disk.
 *
 * @param string $abs_path  Absolute filesystem path.
 * @param string $incoming  The `ver` WordPress was going to use, if any.
 * @return string|null The stamp, or NULL when the file cannot be stat'd.
 */
function bhp_asset_version_stamp( $abs_path, $incoming = '' ) {
	if ( ! is_string( $abs_path ) || '' === $abs_path || ! is_file( $abs_path ) ) {
		return null;
	}

	$mtime = @filemtime( $abs_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a race with a deploy must degrade, not warn.
	if ( ! $mtime ) {
		return null;
	}

	$incoming = trim( (string) $incoming );
	if ( '' === $incoming ) {
		$theme    = wp_get_theme();
		$incoming = is_object( $theme ) ? (string) $theme->get( 'Version' ) : '';
	}
	if ( '' === $incoming ) {
		return (string) $mtime;
	}

	/*
	 * ⭐ IDEMPOTENT AGAINST THE TWO HAND-ROLLED STAMPS. `early-cart-capture`
	 *    already appends its own `.<mtime>`; re-appending would produce
	 *    `1.19.397.1788797132.1788797132`, which is harmless but is the kind
	 *    of thing that gets "fixed" later by someone removing the wrong one.
	 */
	if ( substr( $incoming, - ( strlen( (string) $mtime ) + 1 ) ) === '.' . $mtime ) {
		return $incoming;
	}

	return $incoming . '.' . $mtime;
}

/**
 * Rewrite one asset URL's `ver` from the file it points at.
 *
 * @param string $src The asset URL.
 * @return string The URL, stamped when it resolves to one of our files.
 */
function bhp_asset_version_filter_src( $src ) {
	if ( ! is_string( $src ) || '' === $src ) {
		return $src;
	}

	$parts = explode( '?', $src, 2 );
	$path  = $parts[0];
	$query = isset( $parts[1] ) ? $parts[1] : '';

	$needle = bhp_asset_version_strip_scheme( $path );

	$abs = '';
	foreach ( bhp_asset_version_roots() as $root_uri => $root_dir ) {
		if ( 0 !== strpos( $needle, $root_uri . '/' ) ) {
			continue;
		}
		$rel = substr( $needle, strlen( $root_uri ) );
		/*
		 * ⛔ REFUSE ANY RELATIVE PATH THAT COULD CLIMB OUT OF THE ROOT. The
		 *    input is a URL this installation generated, so `..` should be
		 *    impossible — which is exactly the assumption worth not making in
		 *    a function that turns a URL into a filesystem read.
		 */
		if ( false !== strpos( $rel, '..' ) ) {
			return $src;
		}
		$abs = $root_dir . $rel;
		break;
	}

	if ( '' === $abs ) {
		return $src; // Not ours. Core, another plugin, or an external host.
	}

	$args = array();
	if ( '' !== $query ) {
		wp_parse_str( $query, $args );
	}

	$stamp = bhp_asset_version_stamp( $abs, isset( $args['ver'] ) ? $args['ver'] : '' );
	if ( null === $stamp ) {
		return $src; // No such file. Leave the broken URL debuggable.
	}

	/*
	 * ⭐ `add_query_arg()` RATHER THAN REBUILDING THE QUERY STRING. It replaces
	 *    one argument and leaves every other one byte-identical, including any
	 *    a plugin appended for its own reasons. Rebuilding with `build_query()`
	 *    would round-trip every value through a parser that does not urlencode,
	 *    which is fine until one of them contains an `&`.
	 */
	return add_query_arg( 'ver', $stamp, $src );
}

add_filter( 'style_loader_src', 'bhp_asset_version_filter_src', BHP_ASSET_VERSION_PRIORITY );
add_filter( 'script_loader_src', 'bhp_asset_version_filter_src', BHP_ASSET_VERSION_PRIORITY );

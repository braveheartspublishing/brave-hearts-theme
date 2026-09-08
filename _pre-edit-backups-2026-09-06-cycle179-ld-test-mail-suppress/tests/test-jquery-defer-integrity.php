<?php
/**
 * Brave Hearts — a deferred jQuery must never leave a dependent behind.
 *
 * CYCLE144-LD-JQUERY-DEFER-FIX (2026-08-05, theme 1.19.202).
 *
 * ⛔ THIS SUITE EXISTS BECAUSE THE CHECK THAT WOULD HAVE CAUGHT THE BUG WAS
 *    NEVER RUN.
 *
 * Theme 1.19.201 added `defer` to `jquery-core` on non-commerce surfaces, worth
 * 230 ms of render-blocking time. Its safety argument examined INLINE scripts
 * and found none referencing jQuery — which was true, and was the wrong
 * question. The scripts that broke were EXTERNAL and ENQUEUED: four handles on
 * the front page depended on jQuery and carried no `defer` strategy, so they
 * executed BEFORE the thing they depend on. `mailchimp-woocommerce-pixel-
 * tracking.js` threw `ReferenceError: jQuery is not defined` on the LIVE
 * PRODUCTION home page and failed Lighthouse's `errors-in-console` audit.
 *
 * ⭐ THE ONE INVARIANT EVERYTHING HERE DEFENDS:
 *
 *      On any page where jQuery is deferred, EVERY enqueued script whose
 *      dependency chain reaches jQuery must ALSO be deferred — otherwise
 *      jQuery must not be deferred on that page at all.
 *
 *    There is no third state. A page that defers jQuery and leaves one
 *    dependent blocking is the defect, and this suite's job is to make that
 *    state unreachable rather than merely unlikely.
 *
 * ⭐ WHY MOST OF THIS IS SYNTHETIC RATHER THAN LIVE. A live-graph assertion
 *    alone would pass today and prove almost nothing: it would exercise the
 *    happy path and none of the fallback conditions, because no plugin on this
 *    site currently trips one. The synthetic scenarios drive every branch,
 *    including the ones a FUTURE plugin will be the first to reach. Scenario 1
 *    then applies the same invariant to the real registry, so the two together
 *    cover both "the logic is right" and "the logic is right about this site".
 *
 * Run:
 *   wp eval-file wp-content/themes/<slug>/tests/test-jquery-defer-integrity.php --user=1
 * Exits non-zero on any failure.
 *
 * @package brave-hearts
 */

defined('ABSPATH') || exit;

$failures = 0;
$skipped  = 0;

function bhp_jqd_assert(&$failures, $label, $condition) {
    if ($condition) {
        echo "PASS: {$label}\n";
        return;
    }
    $failures++;
    echo "FAIL: {$label}\n";
}

function bhp_jqd_skip(&$skipped, $label, $why) {
    $skipped++;
    echo "SKIP: {$label} — {$why}\n";
}

/*
 * Every synthetic WP_Scripts instance is retained in this array for the whole
 * run. `bhp_jquery_defer_plan()` caches per instance via `spl_object_hash()`,
 * and PHP reuses a freed object's hash — letting a scenario's WP_Scripts be
 * garbage-collected could hand the next scenario the previous one's cached
 * plan. Holding the references makes the isolation real rather than assumed.
 */
$bhp_jqd_instances = array();

/**
 * Build a throwaway WP_Scripts, register a synthetic graph into it, install it
 * as the global, compute the plan, and put the real one back.
 *
 * @param array $specs handle => array(deps, src, strategy, before, after, data)
 * @param array $queue handles to enqueue
 * @return array The plan.
 */
function bhp_jqd_plan_for(array $specs, array $queue) {
    global $bhp_jqd_instances;

    $real = isset($GLOBALS['wp_scripts']) ? $GLOBALS['wp_scripts'] : null;

    $scripts = new WP_Scripts();
    $bhp_jqd_instances[] = $scripts;

    foreach ($specs as $handle => $spec) {
        $deps = isset($spec['deps']) ? $spec['deps'] : array();
        $src  = array_key_exists('src', $spec) ? $spec['src'] : "https://example.test/{$handle}.js";
        $scripts->add($handle, $src, $deps, '1.0');
        foreach (array('strategy', 'before', 'after', 'data') as $position) {
            if (isset($spec[$position])) {
                $scripts->add_data($handle, $position, $spec[$position]);
            }
        }
    }
    foreach ($queue as $handle) {
        $scripts->enqueue($handle);
    }

    $GLOBALS['wp_scripts'] = $scripts;
    $plan = bhp_jquery_defer_plan();
    $GLOBALS['wp_scripts'] = $real;

    return $plan;
}

/** The two handles the plan always adds when it defers anything. */
function bhp_jqd_core_pair() {
    return array('jquery-core', 'jquery-migrate');
}

/** A minimal, realistic jQuery registration: the meta handle plus its two files. */
function bhp_jqd_jquery_specs() {
    return array(
        'jquery-core'    => array('deps' => array()),
        'jquery-migrate' => array('deps' => array()),
        'jquery'         => array('deps' => array('jquery-core', 'jquery-migrate'), 'src' => false),
    );
}

echo "== bhp_jquery_defer_plan / bhp_defer_jquery_tag ==\n";

bhp_jqd_assert($failures, 'bhp_jquery_defer_plan() exists', function_exists('bhp_jquery_defer_plan'));
bhp_jqd_assert($failures, 'bhp_script_depends_on_jquery() exists', function_exists('bhp_script_depends_on_jquery'));
bhp_jqd_assert($failures, 'bhp_script_inline_touches_jquery() exists', function_exists('bhp_script_inline_touches_jquery'));
bhp_jqd_assert($failures, 'bhp_defer_jquery_tag() exists', function_exists('bhp_defer_jquery_tag'));

if (!function_exists('bhp_jquery_defer_plan')) {
    echo "\nABORT: the implementation is absent; nothing further can be asserted.\n";
    exit(1);
}

/* ------------------------------------------------------------------ *
 * 1. THE LIVE REGISTRY — the invariant applied to this actual site.
 * ------------------------------------------------------------------ */

echo "\n-- scenario 1: the live script registry --\n";

do_action('wp_enqueue_scripts');
$live      = wp_scripts();
$live_plan = bhp_jquery_defer_plan();

/*
 * ⛔ SCOPE, AND THIS IS THE POINT OF THE SCENARIO. The invariant is about
 *    ENQUEUED scripts, not REGISTERED ones. WordPress registers ~140 jQuery
 *    dependents on every request — all of jQuery UI, the media library, the
 *    customiser, the admin bundle — and prints almost none of them. A check
 *    written against `->registered` reports 100+ "violations" that are not on
 *    the page and cannot be. The first draft of this suite did exactly that,
 *    which is recorded here so it is not re-derived.
 *
 * The enqueued closure is re-derived below by an INDEPENDENT walk rather than
 * by calling into the implementation, so this is a real check and not a
 * tautology that would agree with any bug the plan builder happens to have.
 */
$live_closure = array();
$live_stack   = array_merge((array) $live->queue, (array) $live->to_do, (array) $live->done);
while ($live_stack) {
    $handle = array_pop($live_stack);
    if (isset($live_closure[$handle])) {
        continue;
    }
    $live_closure[$handle] = true;
    if (isset($live->registered[$handle])) {
        foreach ((array) $live->registered[$handle]->deps as $dep) {
            $live_stack[] = $dep;
        }
    }
}

$live_memo       = array();
$live_dependents = array();
foreach (array_keys($live_closure) as $handle) {
    if (!isset($live->registered[$handle]) || empty($live->registered[$handle]->src)) {
        continue; // unregistered, or a meta handle that prints no tag
    }
    if (bhp_script_depends_on_jquery($handle, $live->registered, $live_memo)) {
        $live_dependents[] = $handle;
    }
}

$live_registered_dependents = 0;
foreach (array_keys($live->registered) as $handle) {
    if (bhp_script_depends_on_jquery($handle, $live->registered, $live_memo)) {
        $live_registered_dependents++;
    }
}

echo "   enqueued closure: " . count($live_closure) . " handles\n";
echo "   jQuery dependents ENQUEUED (with a src): " . count($live_dependents)
    . ($live_dependents ? ' (' . implode(', ', $live_dependents) . ')' : '') . "\n";
echo "   jQuery dependents merely REGISTERED: {$live_registered_dependents} — out of scope by design\n";
echo "   live plan defers jQuery: " . (!empty($live_plan['defer']) ? 'yes' : 'no') . "\n";

$live_violations = array();
if (!empty($live_plan['defer'])) {
    foreach ($live_dependents as $handle) {
        $strategy = isset($live->registered[$handle]->extra['strategy'])
            ? $live->registered[$handle]->extra['strategy']
            : '';
        if (!isset($live_plan['handles'][$handle]) && 'defer' !== $strategy) {
            $live_violations[] = $handle;
        }
    }
}
bhp_jqd_assert(
    $failures,
    'THE INVARIANT on the live enqueued set: jQuery deferred => every enqueued jQuery dependent deferred'
        . ($live_violations ? ' [left behind: ' . implode(', ', $live_violations) . ']' : ''),
    empty($live_violations)
);

bhp_jqd_assert(
    $failures,
    'the live plan is one of the two legal shapes, never a partial one',
    (empty($live_plan['defer']) && empty($live_plan['handles']))
        || (!empty($live_plan['defer']) && !empty($live_plan['handles']))
);

if (!$live_dependents) {
    bhp_jqd_skip(
        $skipped,
        'the live-set invariant is vacuously true here',
        'WP-CLI renders no template, so the front-end queue is empty or near-empty. '
            . 'The binding live evidence for the front page is the RENDERED HTML check in the release record, not this'
    );
}

/*
 * ⭐ THE FOUR HANDLES THAT ACTUALLY BROKE, pinned by name.
 *
 * The implementation is deliberately graph-driven and contains no handle list.
 * The TEST pins the specific handles from the 2026-08-05 production incident,
 * because a regression that stops recognising `mailchimp-woocommerce-pixel-
 * tracking` as a jQuery dependent is the exact failure that put a
 * `ReferenceError` on the live home page. This does not depend on the CLI
 * queue, so it is meaningful even when the block above is vacuous.
 */
$incident_handles = array('bhp-cart-drawer', 'mailchimp-woocommerce-pixel-tracking', 'rank-math', 'bhp-addon-upsell');
$incident_present = 0;
foreach ($incident_handles as $handle) {
    if (!isset($live->registered[$handle])) {
        bhp_jqd_skip($skipped, "incident handle `{$handle}`", 'not registered in this context (plugin inactive?)');
        continue;
    }
    $incident_present++;
    bhp_jqd_assert(
        $failures,
        "incident handle `{$handle}` is still recognised as a jQuery dependent",
        bhp_script_depends_on_jquery($handle, $live->registered, $live_memo)
    );
}
bhp_jqd_assert(
    $failures,
    'at least 3 of the 4 incident handles are present, so this block cannot pass on an empty set',
    $incident_present >= 3
);

/* ------------------------------------------------------------------ *
 * 2. THE PRODUCTION SHAPE, SYNTHESISED — the exact 1.19.201 defect.
 * ------------------------------------------------------------------ */

echo "\n-- scenario 2: the 1.19.201 defect shape --\n";

$specs = bhp_jqd_jquery_specs() + array(
    'woo-deferred'  => array('deps' => array('jquery'), 'strategy' => 'defer'),
    'pixel-blocking' => array('deps' => array('jquery')),
    'unrelated'      => array('deps' => array()),
);
$plan = bhp_jqd_plan_for($specs, array('woo-deferred', 'pixel-blocking', 'unrelated'));

bhp_jqd_assert($failures, 'defers jQuery when every dependent can be deferred', !empty($plan['defer']));
bhp_jqd_assert($failures, 'the blocking dependent is scheduled for defer', isset($plan['handles']['pixel-blocking']));
foreach (bhp_jqd_core_pair() as $handle) {
    bhp_jqd_assert($failures, "{$handle} is scheduled for defer", isset($plan['handles'][$handle]));
}
bhp_jqd_assert(
    $failures,
    'a dependent WordPress already defers is not re-listed',
    !isset($plan['handles']['woo-deferred'])
);
bhp_jqd_assert($failures, 'a non-dependent handle is never deferred', !isset($plan['handles']['unrelated']));
bhp_jqd_assert(
    $failures,
    'the `jquery` meta handle has no src and is not listed',
    !isset($plan['handles']['jquery'])
);

/* ------------------------------------------------------------------ *
 * 3. TRANSITIVITY — the reason this is graph-driven, not a handle list.
 * ------------------------------------------------------------------ */

echo "\n-- scenario 3: transitive dependency --\n";

$specs = bhp_jqd_jquery_specs() + array(
    'level-1' => array('deps' => array('jquery')),
    'level-2' => array('deps' => array('level-1')),
    'level-3' => array('deps' => array('level-2')),
    'sibling' => array('deps' => array('unrelated-root')),
    'unrelated-root' => array('deps' => array()),
);
$plan = bhp_jqd_plan_for($specs, array('level-3', 'sibling'));

bhp_jqd_assert($failures, 'a depth-3 transitive dependent is deferred', isset($plan['handles']['level-3']));
bhp_jqd_assert($failures, 'its intermediates are deferred too', isset($plan['handles']['level-2']) && isset($plan['handles']['level-1']));
bhp_jqd_assert($failures, 'an unrelated branch is untouched', !isset($plan['handles']['sibling']) && !isset($plan['handles']['unrelated-root']));

/* ------------------------------------------------------------------ *
 * 4. THE FALLBACK CONDITIONS — each disables deferral for the WHOLE page.
 * ------------------------------------------------------------------ */

echo "\n-- scenario 4: the fallback conditions --\n";

$fallbacks = array(
    'a jQuery-touching `before` inline on a dependent' => array(
        'pixel' => array('deps' => array('jquery'), 'before' => 'jQuery(function(){});'),
    ),
    'a jQuery-touching `after` inline on a dependent' => array(
        'pixel' => array('deps' => array('jquery'), 'after' => '$(document).ready(function(){});'),
    ),
    'a jQuery-touching localize `data` chunk' => array(
        'pixel' => array('deps' => array('jquery'), 'data' => 'var x = jQuery.noConflict();'),
    ),
    'a jQuery-touching inline on an UNRELATED handle' => array(
        'pixel'   => array('deps' => array('jquery')),
        'stray'   => array('deps' => array(), 'after' => 'jQuery("body").addClass("x");'),
    ),
    'an `async` jQuery dependent (no ordering guarantee exists)' => array(
        'pixel' => array('deps' => array('jquery'), 'strategy' => 'async'),
    ),
    'a NON-jQuery `after` inline on a dependent (WP prints it blocking)' => array(
        'pixel' => array('deps' => array('jquery'), 'after' => 'window.pixelReady = true;'),
    ),
    'a NON-jQuery `after` inline on a dependent WP already defers' => array(
        'pixel' => array('deps' => array('jquery'), 'strategy' => 'defer', 'after' => 'window.pixelReady = true;'),
    ),
);

foreach ($fallbacks as $label => $extra) {
    $specs = bhp_jqd_jquery_specs() + $extra;
    $plan  = bhp_jqd_plan_for($specs, array_keys($extra));
    bhp_jqd_assert($failures, "FALLS BACK on {$label}", empty($plan['defer']));
    bhp_jqd_assert($failures, "   ...and defers NOTHING, not even jquery-core", empty($plan['handles']));
}

/* ------------------------------------------------------------------ *
 * 5. WHAT MUST *NOT* TRIP THE FALLBACK — the live `bhp-wpconsent-bridge` case.
 * ------------------------------------------------------------------ */

echo "\n-- scenario 5: benign inline must not disable the optimisation --\n";

$specs = bhp_jqd_jquery_specs() + array(
    'pixel'  => array('deps' => array('jquery')),
    'bridge' => array('deps' => array(), 'after' => '(function(){ var SIGNAL_KEYS = ["ad_storage"]; window.bhpConsentBridge = {}; })();'),
);
$plan = bhp_jqd_plan_for($specs, array('pixel', 'bridge'));

bhp_jqd_assert($failures, 'a clean `after` inline on a NON-dependent handle does not disable deferral', !empty($plan['defer']));
bhp_jqd_assert($failures, 'the dependent is still deferred in that case', isset($plan['handles']['pixel']));

/* ------------------------------------------------------------------ *
 * 6. THE `$(` DETECTOR'S PRECISION.
 * ------------------------------------------------------------------ */

echo "\n-- scenario 6: inline jQuery detection --\n";

$detector = array(
    array('jQuery(function(){});',            true,  'bare jQuery( call'),
    array('$(".x").hide();',                  true,  'bare $( call'),
    array('$ ( ".x" )',                       true,  '$ ( with whitespace'),
    array('window.jQuery && window.jQuery.fn', true,  'jQuery referenced without a call'),
    array('var a = foo.$(1);',                false, 'a `.$(` method call on another object'),
    array('var a = b$(1);',                   false, 'an identifier ending in $'),
    array('var a = $$(".x");',                false, 'the `$$(` selector of another library'),
    array('var price = "$" + (n * 2);',       false, 'a dollar sign in a string, then a paren'),
    array('const t = `total ${x}`;',          false, 'a template literal'),
);
foreach ($detector as $case) {
    list($code, $expected, $why) = $case;
    $probe = new stdClass();
    $probe->extra = array('after' => array($code));
    bhp_jqd_assert(
        $failures,
        ($expected ? 'DETECTS' : 'ignores') . " {$why}",
        bhp_script_inline_touches_jquery($probe) === $expected
    );
}

$probe = new stdClass();
$probe->extra = array();
bhp_jqd_assert($failures, 'a handle with no extra data is clean', false === bhp_script_inline_touches_jquery($probe));

/* ------------------------------------------------------------------ *
 * 7. THE CYCLE GUARD — a malformed registration must terminate.
 * ------------------------------------------------------------------ */

echo "\n-- scenario 7: circular dependencies terminate --\n";

$registered = array();
foreach (array('a' => 'b', 'b' => 'c', 'c' => 'a') as $handle => $dep) {
    $obj = new stdClass();
    $obj->deps  = array($dep);
    $obj->src   = "https://example.test/{$handle}.js";
    $obj->extra = array();
    $registered[$handle] = $obj;
}
$memo = array();
$result = bhp_script_depends_on_jquery('a', $registered, $memo);
bhp_jqd_assert($failures, 'a 3-handle dependency cycle terminates and reports no jQuery', false === $result);

$registered['c']->deps = array('a', 'jquery');
$memo = array();
bhp_jqd_assert(
    $failures,
    'a cycle that DOES reach jQuery still reports it',
    true === bhp_script_depends_on_jquery('c', $registered, $memo)
);

/* ------------------------------------------------------------------ *
 * 8. THE TAG FILTER ITSELF.
 * ------------------------------------------------------------------ */

echo "\n-- scenario 8: bhp_defer_jquery_tag --\n";

if (bhp_is_commerce_surface()) {
    bhp_jqd_skip($skipped, 'tag-filter assertions', 'bhp_is_commerce_surface() is true in this WP-CLI context, where the filter correctly short-circuits');
} elseif (empty($live_plan['defer'])) {
    bhp_jqd_skip($skipped, 'tag-filter positive assertions', 'the live plan does not defer jQuery in this context');
} else {
    $target = 'jquery-core';
    $tag    = '<script src="https://example.test/jquery.min.js?ver=3.7.1" id="jquery-core-js"></script>' . "\n";
    $out    = bhp_defer_jquery_tag($tag, $target, 'https://example.test/jquery.min.js');
    bhp_jqd_assert($failures, 'the filter adds defer to a planned handle', false !== strpos($out, '<script defer '));
    bhp_jqd_assert($failures, 'it adds defer exactly once', 1 === substr_count($out, ' defer '));

    $already = '<script defer src="https://example.test/jquery.min.js" id="jquery-core-js"></script>';
    bhp_jqd_assert(
        $failures,
        'an already-deferred tag is returned untouched',
        bhp_defer_jquery_tag($already, $target, 'x') === $already
    );

    $async = '<script async src="https://example.test/jquery.min.js" id="jquery-core-js"></script>';
    bhp_jqd_assert(
        $failures,
        'an async tag is never converted to defer',
        bhp_defer_jquery_tag($async, $target, 'x') === $async
    );

    $unplanned = '<script src="https://example.test/other.js" id="other-js"></script>';
    bhp_jqd_assert(
        $failures,
        'a handle absent from the plan is returned untouched',
        bhp_defer_jquery_tag($unplanned, 'a-handle-that-is-not-in-the-plan', 'x') === $unplanned
    );

    add_filter('bhp_defer_jquery', '__return_false');
    bhp_jqd_assert(
        $failures,
        'the `bhp_defer_jquery` escape hatch switches the whole thing off',
        bhp_defer_jquery_tag($tag, $target, 'x') === $tag
    );
    remove_filter('bhp_defer_jquery', '__return_false');
}

/* ------------------------------------------------------------------ */

echo "\n";
if ($skipped) {
    echo "{$skipped} assertion group(s) skipped — reported, not hidden.\n";
}
if ($failures) {
    echo "FAILURES: {$failures}\n";
    exit(1);
}
echo "ALL PASS\n";
exit(0);

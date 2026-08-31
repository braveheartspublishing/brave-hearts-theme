<?php
/**
 * Brave Hearts Publishing — Analytics Phase 1B: Google Consent Mode scaffold.
 *
 * COMPLIANCE ASSUMPTION FLAGGED FOR ANDREW'S REVIEW (see
 * docs/analytics-architecture.md, "Consent" section): this site currently
 * has no cookie/consent banner and no consent-management platform
 * (confirmed by direct audit, 2026-07-06 and again in this phase). Absent
 * a real consent UI, this class defaults every Consent Mode signal to
 * 'denied' -- the conservative posture -- and only reads an 'granted'
 * state if a first-party cookie (`bhp_consent_state`) is later set by an
 * actual consent banner Andrew approves. No consent banner is built in
 * this phase; that is explicitly out of scope. This file only makes sure
 * that WHEN a consent banner exists, GTM/GA4 already knows how to respect
 * it, and that no analytics fires as "granted" before one exists.
 *
 * ⚠ 2026-08-05 UPDATE (theme 1.19.178) -- the paragraph above describes
 * the state on 2026-07-06 and is kept for history. WPConsent Free is now
 * live and IS the consent banner. More importantly, this class no longer
 * varies its rendered output per visitor: render_default_snippet() prints
 * a CONSTANT all-denied payload, and the visitor's real choice is applied
 * client-side by BHP_WPConsent_Bridge via gtag('consent','update',...).
 * That is the standard Google Consent Mode pattern, and it is what makes
 * the page cacheable -- see default_signals() for the full reasoning and
 * the live evidence behind it (`CYCLE143-GIM-51`).
 *
 * ⭐⭐ 2026-08-27 UPDATE (theme 1.19.302, `CYCLE167-LD-CONSENT-GEO`) -- THE
 * DEFAULTS ARE NOW GEO-AWARE, AND THE "all-denied" SENTENCE IMMEDIATELY
 * ABOVE IS SUPERSEDED FOR NON-EEA TRAFFIC. It is kept, not corrected in
 * place, so the movement stays legible.
 *
 * What changed, and only this: render_default_snippet() now emits TWO
 * `gtag('consent','default',...)` calls instead of one --
 *   - EEA+UK (EEA_UK_REGIONS): every signal DENIED. Byte-for-byte the
 *     posture that was global until this release. Nothing about the
 *     European experience of this site changes.
 *   - everywhere else: analytics_storage GRANTED, the three ad_* signals
 *     still DENIED. The banner stays visible to everyone and becomes a
 *     notice with a working opt-out rather than a pre-consent gate.
 *
 * ⭐⭐⭐ 2026-08-27 UPDATE (theme 1.19.312, `CYCLE167-LD-CONSENT-PIXEL-EXT`) --
 * THE SECOND HALF OF THE LINE IMMEDIATELY ABOVE IS SUPERSEDED. Outside the
 * EEA+UK the three ad_* signals are now GRANTED by default too, so the
 * catch-all payload is all-four-granted. The EEA+UK payload is byte-unchanged
 * and still all-denied. The superseded wording is kept, not corrected in
 * place, so the movement stays legible -- see measured_default_signals() for
 * the authority, the limit of that authority, and the reason the opt-out
 * mechanisms are load-bearing rather than decorative.
 *
 * ⛔ ONE CONSEQUENCE WORTH STATING WHERE IT CANNOT BE MISSED: this is what
 * lets BHP_Meta_Pixel initialise for a non-EEA visitor who has not chosen.
 * That pixel is consent-STATE-driven from 1.19.312 -- it reads the same
 * region gate and the same recorded choice this class's defaults describe,
 * rather than waiting on a banner interaction that a US visitor no longer
 * sees (the banner is EEA-only from 1.19.309). If this payload is ever
 * narrowed back, narrow the pixel in the same commit or the two will
 * disagree about the same visitor.
 *
 * ⛔ THE CACHE-SAFETY PROPERTY IS UNCHANGED AND WAS THE FIRST CONSTRAINT
 * CHECKED, NOT AN AFTERTHOUGHT: both payloads are constants, the server
 * still performs no geo lookup and reads no header or cookie, and every
 * visitor is still served byte-identical HTML. Google resolves `region`
 * from the visitor's IP in the browser at tag-fire time. `CYCLE143-GIM-51`
 * stays closed and its suite still asserts byte-identity directly.
 *
 * ⚠ AUTHORITY, AND ITS LIMIT: Andrew Signore, carrier item 310, 2026-08-27,
 * verbatim -- "Omg - yeah lets just go with US Law - what are we doing" --
 * read first-hand AT THE RECORD by this file's author, RELAYED rather than
 * witnessed. The same carrier records that a legal-review caveat was
 * offered in-channel and that he ruled without it. The paragraph below
 * still stands and is not weakened by this change: this is not a legal
 * opinion, and whether this posture fits the site's traffic mix remains
 * Andrew's decision with appropriate counsel.
 *
 * This is not a legal opinion. Whether Consent Mode / a cookie banner is
 * legally required for this site's traffic mix is Andrew's decision to
 * make with appropriate counsel, not something inferred here.
 *
 * FUTURE CMP INTEGRATION POINT (not selected or purchased in this phase --
 * see docs/analytics-architecture.md for the full list of considerations):
 * whatever consent-management platform Andrew eventually chooses (e.g. a
 * WordPress plugin like Complianz/CookieYes, or a standalone CMP), the
 * ONLY integration work needed here is for that CMP to set the
 * `bhp_consent_state` first-party cookie (a small JSON object of the four
 * signal keys in SIGNALS below, each 'granted'|'denied') when the visitor
 * makes a choice. This class already reads that cookie correctly and
 * already renders the gtag('consent', 'update', ...) call GTM/GA4 expect
 * on a change (see render_default_snippet() -- a real CMP would also call
 * gtag('consent','update',...) directly itself per Google's own
 * documented pattern, in addition to or instead of relying on this
 * cookie). No changes to BHP_GTM_Loader, BHP_Analytics_Config, or any
 * ecommerce event code would be required to adopt a CMP later.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Consent {

	const COOKIE_NAME = 'bhp_consent_state';

	/**
	 * The four Consent Mode v2 signals this phase supports, each
	 * independently 'granted' or 'denied'. Defaults are all 'denied' --
	 * the safe posture when no consent banner exists yet. Advertising
	 * consent is never inferred from analytics consent (Phase 12
	 * requirement) -- they are stored and read as fully separate keys.
	 */
	const SIGNALS = array( 'analytics_storage', 'ad_storage', 'ad_user_data', 'ad_personalization' );

	/**
	 * ⭐ 2026-08-27 (theme 1.19.302, `CYCLE167-LD-CONSENT-GEO`) — the EEA+UK
	 * region list the strict pre-consent defaults are scoped to.
	 *
	 * ISO 3166-1 alpha-2. The 27 EU member states, the three non-EU EEA
	 * states (IS, LI, NO), and GB. Google resolves `region` from the
	 * visitor's IP at tag-fire time, so NOTHING here needs a server-side
	 * geo lookup, a geo header, or any per-visitor variation in the
	 * rendered HTML — which is what keeps `CYCLE143-GIM-51` closed.
	 *
	 * ⚠ DELIBERATELY NOT INCLUDED, so the choice is visible rather than
	 * accidental, and each is a one-entry edit if Andrew decides otherwise:
	 *   - CH (Switzerland, FADP) — not an EEA member; the brief scoped this
	 *     to "EU/UK (EEA)".
	 *   - CA (PIPEDA / Quebec Law 25) and BR (LGPD) — opt-in-leaning regimes
	 *     that this ruling did not name. They currently fall in the
	 *     "everywhere else" branch and are measured by default.
	 * ⛔ This is an engineering note, not a legal opinion. The carrier
	 * records that a legal-review caveat was offered to Andrew in-channel
	 * and he ruled without it (carrier item 310).
	 */
	const EEA_UK_REGIONS = array(
		'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
		'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
		'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
		'IS', 'LI', 'NO',
		'GB',
	);

	/**
	 * Reads the current consent state from the first-party cookie a future
	 * consent banner would set. Cookie value is a small JSON object of the
	 * four signals above, e.g. {"analytics_storage":"granted",...}. Any
	 * missing/malformed/absent cookie resolves every signal to 'denied' --
	 * fail-safe, never fail-open.
	 *
	 * @return array<string,string> one of 'granted'|'denied' per signal
	 */
	public static function current_state() {
		$state = array();
		foreach ( self::SIGNALS as $signal ) {
			$state[ $signal ] = 'denied';
		}

		$has_real_choice = ! empty( $_COOKIE[ self::COOKIE_NAME ] );

		/**
		 * Staging-only validation exception: when Andrew has explicitly
		 * turned on the staging tracking override (the same option that
		 * gates BHP_Analytics_Config::is_tracking_enabled()), analytics
		 * consent defaults to 'granted' so a staging QA session can
		 * observe events reach GA4 before any real consent banner exists.
		 * This only ever fills in the DEFAULT prior to a visitor making a
		 * real choice -- see below, an actual recorded choice (e.g. a
		 * visitor clicking "Reject" in a real consent banner) always wins,
		 * since the point of this QA flag is to unblock validation before
		 * a choice exists, not to override one that already does (fixed
		 * 2026-07-13 after WPConsent integration QA caught GTM loading
		 * despite an explicit Reject -- see DECISIONS.md).
		 */
		if ( ! $has_real_choice && BHP_Analytics_Config::is_staging() && get_option( BHP_Analytics_Config::OPTION_STAGING_TRACKING_OVERRIDE, false ) ) {
			$state['analytics_storage'] = 'granted';
		}

		if ( ! $has_real_choice ) {
			return $state;
		}

		$decoded = json_decode( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ), true );
		if ( ! is_array( $decoded ) ) {
			return $state;
		}

		foreach ( self::SIGNALS as $signal ) {
			$state[ $signal ] = ( isset( $decoded[ $signal ] ) && 'granted' === $decoded[ $signal ] ) ? 'granted' : 'denied';
		}
		return $state;
	}

	/**
	 * The STRICT Consent Mode default payload — every signal denied.
	 *
	 * ⭐ 2026-08-27 (1.19.302): this is now the EEA+UK branch's base rather
	 * than the site's only default. It is unchanged byte-for-byte; what
	 * changed is that render_default_snippet() scopes it to a region and
	 * emits measured_default_signals() alongside it for everywhere else.
	 * The sentence below is preserved verbatim and is still exactly true of
	 * THIS function — it is no longer true of the site as a whole.
	 *
	 * CONSTANT BY DESIGN, 2026-08-05
	 * (`CYCLE143-GIM-51`): every signal is 'denied' for every visitor, on
	 * every request, in every environment. It does NOT read the consent
	 * cookie and must never be made to.
	 *
	 * Why constant: SiteGround's page cache stores rendered HTML and varies
	 * only on Accept-Encoding. Any per-visitor variation in this snippet is
	 * therefore served to the wrong visitors from cache -- observed live on
	 * production 2026-08-04, in both directions (a cache entry primed by a
	 * consenting visitor served `analytics_storage:"granted"` to a
	 * cookie-less visitor; the canonical homepage's no-GTM entry starved
	 * consenting visitors of measurement entirely). A constant
	 * defaults-denied payload is byte-identical for everyone, so the cache
	 * cannot mis-serve it, and the real per-visitor state is applied
	 * client-side by BHP_WPConsent_Bridge's `consent`/`update` call.
	 *
	 * `wait_for_update: 500` gives that client-side update 500ms to arrive
	 * before GTM proceeds under the denied defaults.
	 *
	 * @return array<string,int|string>
	 */
	public static function default_signals() {
		$defaults = array();
		foreach ( self::SIGNALS as $signal ) {
			$defaults[ $signal ] = 'denied';
		}
		$defaults['wait_for_update'] = 500;
		return $defaults;
	}

	/**
	 * ⭐ The EEA+UK default payload: the strict pre-consent posture, scoped
	 * to EEA_UK_REGIONS. Byte-for-byte the payload this site emitted
	 * globally before 1.19.302, plus the `region` key.
	 *
	 * @return array<string,int|string|array>
	 */
	public static function eea_default_signals() {
		$defaults           = self::default_signals();
		$defaults['region'] = self::EEA_UK_REGIONS;
		return $defaults;
	}

	/**
	 * ⭐ The everywhere-else default payload — the one 1.19.302 existed
	 * to add. `analytics_storage` is GRANTED by default; the banner is a
	 * notice with a working opt-out rather than a gate.
	 *
	 * ⛔ SUPERSEDED PARAGRAPH, PRESERVED VERBATIM AND DELIBERATELY NOT
	 * CORRECTED IN PLACE, because a future reader will otherwise re-derive
	 * the narrower rule from the code and "fix" this release back out. It
	 * was exactly true of 1.19.302–1.19.311 and is false of 1.19.312:
	 *
	 *   > ⛔ THE THREE AD SIGNALS STAY DENIED BY DEFAULT IN EVERY REGION, and
	 *   > that is deliberate, not an oversight. The ruling this implements is
	 *   > about MEASUREMENT ("half blind" — carrier items 309/310). Advertising
	 *   > identifiers are a separate posture with separate US state-law
	 *   > exposure, and the brief's own instruction was not to broaden the ad
	 *   > signals beyond what the accepted state already grants. A visitor who
	 *   > accepts "marketing" in the banner still raises all three, exactly as
	 *   > before, via BHP_WPConsent_Bridge. Nothing about the granted path
	 *   > changed — only the default for a visitor who has not chosen.
	 *
	 * ⭐⭐ 2026-08-27 (theme 1.19.312, `CYCLE167-LD-CONSENT-PIXEL-EXT`) — THE
	 * THREE AD SIGNALS ARE NOW GRANTED BY DEFAULT OUTSIDE THE EEA+UK, which is
	 * the precise sentence the block above forbade. It is not a drift: the
	 * question the 1.19.302 note left open ("advertising identifiers are a
	 * separate posture") was PUT TO THE FOUNDER AND ANSWERED.
	 *
	 * ⚠ AUTHORITY, AND ITS LIMIT: Andrew Signore, carrier `^349. FOUNDER`
	 * item 4, 2026-08-27, verbatim — "I guess we extend it" — given after
	 * Gandalf explicitly clarified that the extension reaches the ad/marketing
	 * pixel and not only measurement. ⛔ RELAYED THROUGH THE CHIEF OF STAFF,
	 * NOT WITNESSED BY THIS FILE'S AUTHOR, and labelled so here rather than in
	 * a report that will not travel with the code. The underlying item 310
	 * ruling ("lets just go with US Law") is likewise relayed.
	 *
	 * ⛔ THIS IS NOT A LEGAL OPINION, and the caveat is stronger here than it
	 * was for measurement. Several US state privacy regimes (CA/CO/CT/VA and
	 * their successors) treat cross-context behavioural advertising as an
	 * opt-OUT right rather than an opt-in one — which is the posture this
	 * implements — but they also require the opt-out to be real and reachable.
	 * That is why this release does not stop at the default: the "Privacy
	 * Choices" footer link from 1.19.309 and the GPC branch in
	 * BHP_WPConsent_Bridge are the two mechanisms that make it an opt-out
	 * rather than a taking, and BOTH are asserted by
	 * tests/test-cycle167-consent-pixel-ext.php. Whether this posture fits the
	 * site's actual traffic mix remains Andrew's decision with counsel; the
	 * carrier records that a legal-review caveat was offered and he ruled
	 * without it.
	 *
	 * ⛔ THE EEA+UK PAYLOAD IS BYTE-UNCHANGED. eea_default_signals() still
	 * returns all four signals denied. Nothing about the European experience
	 * of this site moves in this release, and test-cycle167-consent-geo.php
	 * asserts that directly rather than by inspection.
	 *
	 * @return array<string,int|string>
	 */
	public static function measured_default_signals() {
		$defaults = array();
		foreach ( self::SIGNALS as $signal ) {
			$defaults[ $signal ] = 'granted';
		}
		$defaults['wait_for_update'] = 500;
		return $defaults;
	}

	/**
	 * Renders the gtag() Consent Mode default calls. Must print BEFORE the
	 * GTM/gtag loader script (Phase 4/12 ordering requirement) so GTM
	 * initializes already aware of the current consent state rather than
	 * assuming default "granted" behavior for a brief window.
	 *
	 * ⭐ 2026-08-27 (theme 1.19.302, `CYCLE167-LD-CONSENT-GEO`) — TWO default
	 * calls are now emitted, the standard documented Consent Mode v2
	 * region pattern:
	 *
	 *   1. region-scoped EEA+UK  -> every signal denied  (unchanged posture)
	 *   2. unscoped catch-all    -> analytics_storage granted, ad_* denied
	 *
	 * ⭐ 1.19.312 (`CYCLE167-LD-CONSENT-PIXEL-EXT`): line 2 above is now
	 * "every signal granted". Line 1 is unchanged. The shape of the emission
	 * -- two calls, region-scoped first, both compile-time constants -- did
	 * not move at all; only the value of three keys in the second payload did.
	 *
	 * ⚠ PRECEDENCE IS BY SPECIFICITY, NOT BY ORDER: Google applies the most
	 * specific matching `region` regardless of which call came first. The
	 * region-scoped call is emitted first only because that is the order in
	 * Google's own documented example, and matching the documented example
	 * is worth more than a re-derivation.
	 *
	 * ⛔ STILL CONSTANT FOR EVERY VISITOR. Both payloads are compile-time
	 * constants. The server performs NO geo lookup and reads NO header or
	 * cookie to build them — Google resolves `region` from the visitor's IP
	 * at tag-fire time, in the browser. That is precisely what allows this
	 * to be geo-aware WITHOUT re-creating `CYCLE143-GIM-51`, in which a
	 * per-visitor server-side variation was mis-served in both directions by
	 * SiteGround's page cache. The byte-identity invariant is unchanged and
	 * is still asserted directly by tests/test-consent-mode-cache-safety.php.
	 *
	 * Authority: Andrew Signore, carrier item 310, 2026-08-27 — "yeah lets
	 * just go with US Law". RELAYED to this file's author through the record,
	 * not witnessed.
	 */
	public static function render_default_snippet() {
		printf(
			"<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('consent','default',%s);gtag('consent','default',%s);</script>\n",
			wp_json_encode( self::eea_default_signals() ),
			wp_json_encode( self::measured_default_signals() )
		);
	}

	/**
	 * True only when analytics_storage is granted for THIS request's
	 * cookie state.
	 *
	 * ⚠ 2026-08-05: this is no longer a rendering gate. It is diagnostic
	 * only (the staging debug panel, consent_gate_reason()). It must NOT be
	 * reintroduced into any code path that decides what HTML to print --
	 * doing so re-creates `CYCLE143-GIM-51`, because the page cache does
	 * not vary on the consent cookie. Collection gating lives entirely in
	 * Consent Mode now.
	 */
	public static function analytics_allowed() {
		$state = self::current_state();
		return 'granted' === $state['analytics_storage'];
	}
}

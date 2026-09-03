<?php
/**
 * Template Name: Adventure Kit Thank-You Page
 * Description: Parent-specific post-signup confirmation for the Reluctant
 * Reader Adventure Kit. Deliberately separate from the Mariana teacher/
 * parent thank-you page — no teacher or author-visit messaging here, and
 * it must never be reachable via an arbitrary redirect (only the
 * whitelisted 'adventure_kit_thank_you' key in inc/mailchimp.php resolves
 * to this page).
 */
defined('ABSPATH') || exit;
get_header();

$adventures = bhp_get_series_adventures();
?>
<?php
// Analytics Phase 1B/1C: adventure_kit_signup fires ONLY on this page, which
// (per this file's own docblock) is only reachable via the whitelisted
// 'adventure_kit_thank_you' redirect key in inc/mailchimp.php -- the same
// "only the real confirmation page can fire this" trust boundary already
// used for the `purchase` event. No email address or other PII is
// included; this is a lead-conversion signal only.
//
// Phase 1C addition: a page REFRESH or back-navigation to this exact URL
// must not refire the conversion event a second time (matching the same
// dedup requirement already solved for `purchase`). This page has no
// order ID to key a server-side dedup flag off, so the guard runs
// client-side via sessionStorage -- scoped to the tab/session, matching
// the "once per real conversion" intent without needing any new PHP
// state. The event is also enriched with lead_offer/audience/placement
// so it is directly comparable to Phase 1C's other conversion events.
/*
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐ 1.19.253 (`CYCLE165-LD-META-LEAD-EVENT`) — THIS PUSH MOVED FROM THE
 *    PAGE BODY TO `wp_footer` PRIORITY 99. THE PAYLOAD, THE EVENT NAME AND
 *    THE DEDUP GUARD ARE BYTE-FOR-BYTE WHAT THEY WERE. Only WHEN it runs
 *    changed, and that is the whole point of the change.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ WHY, AND IT IS AN ORDERING CONTRACT WITH THE META PIXEL — NOT A TIDY-UP.
 *    `adventure_kit_signup` is now mapped to Meta's `Lead` event, and so is
 *    the parent popup's `parent_popup_success`. A visitor who signs up
 *    THROUGH the popup raises BOTH on this one page load, because this page
 *    is the popup's own `thankYouPath`. The pixel therefore fires the FIRST
 *    Lead of a page load and drops the rest — one signup is one Lead.
 *
 *    `assets/js/mariana-popup.js` is enqueued sitewide in the FOOTER
 *    (functions.php, "Enqueue the shared popup script sitewide") precisely
 *    so a thank-you page can fire the originating popup's success event, and
 *    `wp_print_footer_scripts` runs on `wp_footer` at priority 20. Printing
 *    this generic page-level event inline in the body made it arrive FIRST,
 *    which would have cost every popup-originated signup its funnel name and
 *    its A/B variant in Meta. At priority 99 it arrives last, so the popup
 *    keeps its attribution and this event is the fallback for every signup
 *    that did NOT come through a popup.
 *
 *    ⚠ The COUNT is correct either way — the latch guarantees one Lead per
 *    conversion regardless of order. What order buys is ATTRIBUTION. If this
 *    priority or that enqueue ever moves, `content_name` degrades to
 *    `adventure_kit` for popup signups and nothing else breaks.
 *
 * ⛔ GA4 IS UNAFFECTED. The same event with the same payload still reaches
 *    the same dataLayer on the same page load; only its position in the load
 *    order moved, and no GA4 tag in the container is order-sensitive.
 */
/*
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.292 (2026-08-26, `CYCLE166-CX-CAPTURE-REPAIR`) — THIS EVENT NOW
 *     REQUIRES A SINGLE-USE SERVER-SIDE CONVERSION TOKEN. A BARE LOAD OF
 *     THIS URL FIRES NOTHING.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE DOCBLOCK AT THE TOP OF THIS FILE WAS WRONG, AND IT IS LEFT IN
 *    PLACE ABOVE RATHER THAN QUIETLY CORRECTED so the movement is visible.
 *    It asserts this page "must never be reachable via an arbitrary
 *    redirect (only the whitelisted 'adventure_kit_thank_you' key ...
 *    resolves to this page)". That is a true statement about how the SITE
 *    LINKS HERE and a false statement about who can LOAD this URL. A
 *    permalink is not a capability. Anyone — and in practice, any crawler —
 *    could GET this page and manufacture a conversion.
 *
 * ⭐ MEASURED ON PRODUCTION, 2026-08-26, raw access logs read read-only
 *    over SSH — NOT inferred from code:
 *      2026-08-17..26  thank-you page GETs: 34   real signups: 0
 *      2026-08-20 alone: 20 page loads, 0 signups.
 *    Agents behind them: curl/8.12.1, HeadlessChrome, Applebot, AhrefsBot,
 *    Amazonbot, Googlebot, an internal WordPress loopback. EVERY ONE OF
 *    THOSE LOADS PUSHED `adventure_kit_signup`.
 *
 * ⭐ WHAT REPLACED WHAT. The `sessionStorage` dedup latch below is KEPT and
 *    is now the SECOND of two guards, not the only one. It was never able
 *    to do this job: it dedups repeat views WITHIN ONE TAB, and does
 *    nothing whatsoever about a load that had no conversion behind it in
 *    the first place. The token is the primary gate and is server-side,
 *    single-use and short-TTL; the latch remains as cheap belt-and-braces
 *    for the one case the token cannot see (a browser that replays the
 *    same tokenised URL from bfcache within the same tab).
 *
 * ⛔ THE EVENT NAME AND EVERY PAYLOAD FIELD ARE BYTE-FOR-BYTE UNCHANGED, as
 *    is the `wp_footer` priority 99 ordering contract with the Meta pixel
 *    documented immediately above. No GTM tag, GA4 config or Meta mapping
 *    needs to change, and no historical series is renamed or split. ONLY
 *    WHETHER IT FIRES CHANGED.
 *
 * ⚠️ EXPECT THE REPORTED CONVERSION COUNT TO FALL AFTER THIS SHIPS. That is
 *    the fix working, not a regression. The prior number was inflated by
 *    every crawler that ever found this URL.
 */
add_action( 'wp_footer', function () {
    if ( ! class_exists( 'BHP_Analytics_Config' ) || ! BHP_Analytics_Config::should_render_analytics() ) {
        return;
    }

    // ⭐ THE GATE. Consumes and burns the token; false for every arrival
    //    that did not just complete a real signup.
    if ( ! function_exists( 'bhp_is_verified_conversion' ) || ! bhp_is_verified_conversion() ) {
        return;
    }

    $bhp_akty_payload = wp_json_encode(
        array(
            'event'       => 'adventure_kit_signup',
            'funnel'      => 'parent',
            'page_type'   => 'thank_you',
            'lead_offer'  => 'reluctant_reader_adventure_kit',
            'audience'    => 'parents_families',
            'placement'   => 'adventure_kit_thank_you_page',
            'signup_method' => 'form',
        )
    );
    ?>
    <script>
    (function () {
        var DEDUP_KEY = 'bhp_adventure_kit_signup_fired';
        try {
            if (sessionStorage.getItem(DEDUP_KEY)) {
                return; // already fired once this session -- refresh/back-nav, not a new conversion
            }
            sessionStorage.setItem(DEDUP_KEY, '1');
        } catch (e) {
            // Private-browsing or storage disabled -- fail safe by still
            // firing once for this load rather than silently dropping the
            // conversion signal entirely.
        }
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push(<?php echo $bhp_akty_payload; ?>);
    })();
    </script>
    <?php
}, 99 );
?>
<?php
/*
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.229 (2026-08-17, `CYCLE162-LD-TYP-V2`) — THE CONFIRMATION IS
 *     COMPACTED SO THE OFFER CLEARS THE PHONE FOLD. Andrew, verbatim,
 *     relayed through the Chief of Staff and NOT witnessed by this agent:
 *     *"The CTA on the thank you page is below the fold on the mobile view."*
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE SAY-DO RULE IS INTACT AND THAT IS WHY NOTHING WAS REORDERED. The
 *    visitor was told a Kit is coming; the first thing this page says is
 *    still that the Kit is coming, in the same words, as the H1. The offer
 *    did not move ABOVE the confirmation — the confirmation got SMALLER.
 *
 * MEASURED BEFORE (staging 1.19.228, headless Chrome, innerWidth ASSERTED,
 * consent banner located by walking open shadow roots):
 *      390x844  CTA y 1004-1052 — 320px BELOW the banner top (732)
 *      360x740  CTA y 1055-1120 — 492px BELOW the banner top (628)
 * The budget was spent almost entirely on section rhythm, not on words:
 * at 360 the sitewide `--section-space` put 80px above the eyebrow and
 * 213px between the confirmation and the offer, and the H1 took FOUR lines
 * (201px) at the sitewide `clamp(2.25rem, 5vw, 3.75rem)`.
 *
 * ⛔ NO WORD OF THE CONFIRMATION COPY IS DELETED. The H1 is the same string;
 *    it is set smaller on phones by `.bhp-typ` in style.css. The lead
 *    sentence is the same sentence. Compaction here is TYPE and RHYTHM only.
 *    ⚠ SUPERSEDED IN PART BY 1.19.297 — see the block immediately below. The
 *      1.19.228 claim above was TRUE OF 1.19.228 and is preserved so the
 *      movement is visible rather than re-derived; the eyebrow and the lead
 *      sentence DID change at 1.19.297, for a reason that is not typography.
 */
?>
<?php
/*
 * ═════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.297 (2026-08-27, `CYCLE167-LD-CAPTURE-COPY-APPLY`) — THE LANDING
 *     SIDE OF THE CHAPTER PROMISE. THIS IS AN HONESTY FIX, NOT A COPY POLISH.
 * ═════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE PROBLEM IT SOLVES, STATED PLAINLY. As of 1.19.297 every parent
 *    capture surface offers a FREE CHAPTER and every button reads "Send me the
 *    chapter". This page is where that visitor lands. Before this change it
 *    greeted them with "Your adventure kit is on the way" and "Your guide is on
 *    the way" — three different nouns (chapter, kit, guide) across two clicks,
 *    which is precisely the broken-promise shape that the founder's item-290
 *    condition (b) exists to prevent: *"delivery-side copy must bridge
 *    chapter -> kit so what arrives visibly matches what was promised."*
 *
 * ⛔ THE H1 IS **NOT** TOUCHED, AND THAT IS THE DESIGN RATHER THAN CAUTION.
 *    "Your Reluctant Reader Adventure Kit Is on Its Way" is the correct thing
 *    to say here, because the Kit IS what arrives in the inbox. Renaming the H1
 *    to "chapter" would have made THIS page honest about the promise and
 *    dishonest about the file. The bridge belongs in the sentence, which can
 *    hold both nouns and the relationship between them. ⭐ Keeping it also
 *    leaves `tests/test-kit-thankyou-upsell.php`'s two H1 assertions (including
 *    an ordering check against the upsell cover) untouched and still passing —
 *    a consequence of the right call, not the reason for it.
 *
 * ⭐ THE LEAD SENTENCE NOW DOES THREE THINGS IN ORDER: names the chapter the
 *    visitor was promised, says where it is (inside the Kit), and states what
 *    else is in there. The 15-minute arrival guidance and the spam-folder note
 *    are KEPT VERBATIM — they are the only operationally useful words on the
 *    page and they are the reason this page reduces support email.
 *
 * ⚠ "now" vs "up to 15 minutes" IS NOT A CONTRADICTION AND WAS CHECKED, NOT
 *   ASSUMED. Item 293 records the founder's own read of his Mailchimp journey
 *   builder: step 1 of the Active "Parent - Acquisition Funnel" sends on the
 *   tag, "Every day as soon as possible". The SEND is immediate; ARRIVAL is a
 *   mail-delivery fact nobody controls. Promising an immediate send and warning
 *   about delivery latency is the honest pair. ⛔ This desk made no Mailchimp
 *   call and carries that as HIS in-system observation, not its own.
 *
 * ⛔ NO OUTCOME CLAIM. NO INVENTED CONTENTS: the 296 lane read all seven pages
 *    of the live `Reluctant-Reader-Adventure-Kit-1.pdf` from the production
 *    document root. VOICE §9.1: I/me/my, no em dash, ages 6 to 9.
 *    ⛔ The curly apostrophe in the old "don’t" is replaced with "do not"
 *      rather than a straight quote, so no encoding difference rides in.
 */
?>
<section class="passport-status-page section bhp-typ bhp-typ__confirm" aria-labelledby="adventure-kit-thank-you-title">
  <div class="container container--content passport-status-page__inner">
    <p class="component-heading__eyebrow"><?php esc_html_e('Your chapter is on the way', 'brave-hearts'); ?></p>
    <h1 id="adventure-kit-thank-you-title"><?php esc_html_e('Your Reluctant Reader Adventure Kit Is on Its Way', 'brave-hearts'); ?></h1>
    <p class="text-lead"><?php esc_html_e('Your chapter is inside the Kit, along with a printable activity and tips for reading it with a 6 to 9 year old. Please allow up to 15 minutes for it to arrive, and check your promotions or spam folder if you do not see it.', 'brave-hearts'); ?></p>
  </div>
</section>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.228 (2026-08-14, `CYCLE161-LD-TYP-AND-GUARANTEE`) — THE UPSELL
 *     NOW CARRIES THE PRICE, THE SAVING AND A PURCHASE-PATH CTA.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Finding #22 (unchanged): the Complete Collection is the primary next step
 * after the download instructions above. What changed is that the module
 * used to send a visitor to a page with NO price, NO anchor and no reason
 * to click — the pattern proven on the collection page itself is applied
 * here instead.
 *
 * ⛔ EVERY NUMBER IS DERIVED FROM LIVE WooCOMMERCE PRICES. There is no
 *    `$35.97`, `$31.99` or `$3.98` literal anywhere in this file, and
 *    `tests/test-kit-thankyou-upsell.php` asserts that there is not. The
 *    single source is the plugin's `bhp_bundle_landing_price_facts()`,
 *    which is the same function the collection page prints from, so this
 *    page and that page cannot disagree — and neither can disagree with the
 *    cart, because `separate - discount` is what
 *    `bhp_bundle_apply_discount()` actually charges.
 *
 *    VERIFIED LIVE 2026-08-14 by WP-CLI on staging: paperback singles
 *    11.99 x 3 = 35.97, tier-3 discount 3.98, collection 31.99.
 *
 * ⛔ IT DEGRADES TO EXACTLY THE OLD MODULE. If the bundle plugin is not
 *    active (`function_exists` false) the price block is not rendered and
 *    the section is byte-equivalent to the pre-1.19.228 version. A
 *    thank-you page must never break because a commerce plugin is off.
 *
 * ⛔ PAPERBACK-FIRST, AND NOT BY A LITERAL. The format shown is whatever
 *    `bhp_bundle_landing_default_format()` returns — the SAME single source
 *    the collection page's price box, format pills and buy button read
 *    (currently 'paperback'; deliberately NOT the sitewide
 *    `bhp_bundle_default_format()`, which is still 'hardcover' and governs
 *    six other surfaces). If Andrew ever flips the landing default, this
 *    page follows in the same request instead of quietly contradicting it.
 *
 * ⭐ THE CTA LANDS ON THE PURCHASE CARD, not the top of the page. The
 *    `#bhp-landing-pricing-card` id is real and resolves natively (added in
 *    plugin 1.8.28 precisely so an off-page link would work), and the card
 *    opens with the landing default format preselected — so the CTA lands
 *    paperback-preselected exactly like the box CTA does.
 *
 * ⛔ THE ANALYTICS ATTRIBUTES ARE BYTE-UNCHANGED. Same
 *    `collection_upsell_click`, same `bhp_format="collection"`, same
 *    `bhp_source="parent_thank_you"`, fired by the same delegated handler
 *    in `assets/js/nav.js`. No new event is introduced and no existing
 *    payload value is altered, so no GA4/GTM configuration has to move.
 */
$bhp_akty_facts  = null;
$bhp_akty_format = 'paperback';
if ( function_exists('bhp_bundle_landing_price_facts') && function_exists('bhp_bundle_landing_default_format') ) {
    $bhp_akty_format = bhp_bundle_landing_default_format();
    $bhp_akty_facts  = bhp_bundle_landing_price_facts($bhp_akty_format);
}
/*
 * ⛔⛔ THE COUPON LINE IS GATED, AND THE GATE IS THE POINT. See
 *     `bhp_audience_coupon_public_notice()` in the bundle plugin for the
 *     full record: rendering an audience coupon code on a page template
 *     runs against the FROZEN Audience Coupon Policy
 *     (`docs/ENGINEERING/FUNNEL_CONSTITUTION.md`, 2026-07-14, which the
 *     repo's own "must not be reopened" list names), and that conflict is
 *     Andrew's to resolve, not this template's. The helper returns null on
 *     every environment until an operator sets the site option, no coupon
 *     code literal exists in this public repository, and the percentage
 *     printed below is read off the live coupon record rather than typed.
 */
$bhp_akty_coupon = function_exists('bhp_audience_coupon_public_notice')
    ? bhp_audience_coupon_public_notice($bhp_akty_format)
    : null;

/*
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.229 — THE AUTO-APPLIED WELCOME DISCOUNT. Andrew, verbatim,
 *     relayed and NOT witnessed by this agent: *"if they click get
 *     collection it auto applies the discount so they have a 2 click path
 *     to purchase no need to add the coupon code in"*.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ THIS IS NOT THE COUPON LINE ABOVE, AND THE DIFFERENCE IS THE WHOLE
 *    POINT. `$bhp_akty_coupon` renders a CODE and is gated by its own
 *    option because rendering a code runs against the Frozen Audience
 *    Coupon Policy. This one renders NO CODE AT ALL — it states an
 *    OUTCOME and the mechanism applies the code server-side. The two are
 *    independent options, and the code option stays unset.
 *
 * ⛔ THE NUMBER IS NOT TYPED AND IS NOT A SECOND OPINION.
 *    `bhp_typ_auto_coupon_offer()` returns the effective price computed by
 *    `bhp_audience_coupon_savings_for_format()` — the SAME function
 *    `bhp_audience_coupon_apply_savings_fee()` charges the cart from. The
 *    page and the cart share one expression, so they cannot disagree.
 *
 * ⛔ THE PRIMARY PRICE STAYS $31.99-DERIVED, DELIBERATELY. The struck sum
 *    and the collection price above are unconditionally true. The
 *    discounted figure is stated as a QUALIFIED second line ("with your
 *    welcome discount"), never as the headline price, because the live
 *    coupon record carries `usage_limit_per_user = 1` — a subscriber who
 *    already redeemed it would be charged the collection price, and a
 *    headline that had promised the discounted one would then be a false
 *    price. Qualified is the honest form. Flagged for Andrew.
 *
 * ⛔ IT IS OFF UNTIL AN OPERATOR SETS `bhp_typ_auto_coupon`, and when it is
 *    off the CTA href is byte-identical to 1.19.228's.
 */
$bhp_akty_offer = function_exists('bhp_typ_auto_coupon_offer')
    ? bhp_typ_auto_coupon_offer($bhp_akty_format)
    : null;

$bhp_akty_cta_url = home_url('/complete-collection/');
if ($bhp_akty_offer && defined('BHP_TYP_AUTO_COUPON_PARAM') && defined('BHP_TYP_AUTO_COUPON_PARAM_VALUE')) {
    $bhp_akty_cta_url = add_query_arg(
        BHP_TYP_AUTO_COUPON_PARAM,
        BHP_TYP_AUTO_COUPON_PARAM_VALUE,
        $bhp_akty_cta_url
    );
}
$bhp_akty_cta_url .= '#bhp-landing-pricing-card';

/*
 * ⭐ THE EYE-CATCHER. Andrew, verbatim: *"It should probably have a picture
 *    of the kits front page as an eye catcher"*. This is the REAL page-1
 *    render of the delivered PDF, produced in CYCLE158 and already shipped
 *    in the signup modal — `bhp_get_lead_magnet_cover()` resolves it, checks
 *    both files exist, and returns an empty array rather than a broken image
 *    if either is missing. Nothing is re-rendered here.
 *
 * ⛔ IT SITS BESIDE THE OFFER HEADING, NOT STACKED ABOVE IT, AND THAT IS A
 *    FOLD DECISION, NOT A TASTE ONE. Stacked, the cover costs ~100px of the
 *    ~535px total budget available above the consent banner at 360x740.
 *    Beside it, it costs zero — the heading is already that tall. It is
 *    still the first thing in the offer block, which is what "at the top"
 *    is for. This is the signup modal's own proven cover pattern.
 */
$bhp_akty_cover = function_exists('bhp_get_lead_magnet_cover')
    ? bhp_get_lead_magnet_cover('reluctant_reader_adventure_kit')
    : [];
?>
<section class="passport-section section bhp-typ bhp-typ__offer" data-bhp-typ-offer aria-labelledby="adventure-kit-thank-you-collection-title">
  <div class="container container--content">
    <div class="bhp-kit-upsell__card">
    <div class="bhp-kit-upsell__head">
      <?php if (!empty($bhp_akty_cover['url'])) : ?>
      <picture class="bhp-kit-upsell__cover">
        <source srcset="<?php echo esc_url($bhp_akty_cover['url']); ?>" type="image/webp">
        <img
          src="<?php echo esc_url($bhp_akty_cover['fallback']); ?>"
          width="<?php echo (int) $bhp_akty_cover['width']; ?>"
          height="<?php echo (int) $bhp_akty_cover['height']; ?>"
          alt="<?php echo esc_attr($bhp_akty_cover['alt']); ?>"
          loading="lazy"
          decoding="async"
        >
      </picture>
      <?php endif; ?>
      <div class="bhp-kit-upsell__headtext">
      <p class="component-heading__eyebrow"><?php esc_html_e('Continue the adventure', 'brave-hearts'); ?></p>
      <h2 id="adventure-kit-thank-you-collection-title" class="text-section-title"><?php esc_html_e('Get All Three Adventures in the Complete Collection', 'brave-hearts'); ?></h2>
    <?php if ($bhp_akty_facts) :
      $bhp_akty_separate = '$' . number_format($bhp_akty_facts['separate'], 2);
      $bhp_akty_bundle   = '$' . number_format($bhp_akty_facts['bundle'], 2);
      $bhp_akty_save     = '$' . number_format($bhp_akty_facts['save'], 2);
    ?>
    <p class="bhp-kit-upsell__price">
      <span class="screen-reader-text"><?php
        printf(
          /* translators: 1: format, 2: sum of the three books bought separately, 3: collection price, 4: amount saved */
          esc_html__('The three %1$s books bought separately cost %2$s. The Complete Collection is %3$s, so you save %4$s buying them together.', 'brave-hearts'),
          esc_html($bhp_akty_format),
          esc_html($bhp_akty_separate),
          esc_html($bhp_akty_bundle),
          esc_html($bhp_akty_save)
        );
      ?></span>
      <s class="bhp-kit-upsell__price-was" aria-hidden="true"><?php echo esc_html($bhp_akty_separate); ?></s>
      <span class="bhp-kit-upsell__price-now" aria-hidden="true"><?php
        // Same treatment as the collection box (plugin 1.8.43/1.8.45): clean
        // sans, cents rendered smaller. $bhp_akty_bundle is always a derived
        // "$NN.NN" string; the split is display-only and this span stays
        // aria-hidden, so screen readers still get the whole price from the
        // visually-hidden sentence above.
        if (preg_match('/^(.*)\.(\d{2})$/', $bhp_akty_bundle, $bhp_akty_m)) {
          echo esc_html($bhp_akty_m[1]) . '<span class="bhp-kit-upsell__price-cents">.' . esc_html($bhp_akty_m[2]) . '</span>';
        } else {
          echo esc_html($bhp_akty_bundle);
        }
      ?></span>
      <span class="bhp-kit-upsell__price-save" aria-hidden="true"><?php echo esc_html('Save ' . $bhp_akty_save); ?></span>
    </p>
    <?php endif; ?>
      </div><!-- /.bhp-kit-upsell__headtext -->
    </div><!-- /.bhp-kit-upsell__head -->
    <?php if ($bhp_akty_offer) :
      $bhp_akty_eff = '$' . number_format($bhp_akty_offer['effective'], 2);
    ?>
    <p class="bhp-kit-upsell__effective">
      <span class="screen-reader-text"><?php
        printf(
          /* translators: 1: discounted collection price, 2: discount percentage */
          esc_html__('With your welcome discount of %2$s, the Complete Collection comes to %1$s at checkout. The discount is applied automatically when you use the button below, so there is no code to enter.', 'brave-hearts'),
          esc_html($bhp_akty_eff),
          esc_html(rtrim(rtrim(number_format($bhp_akty_offer['percent'], 2, '.', ''), '0'), '.') . '%')
        );
      ?></span>
      <span class="bhp-kit-upsell__effective-line" aria-hidden="true">
        <span class="bhp-kit-upsell__effective-now"><?php echo esc_html($bhp_akty_eff); ?></span>
        <span class="bhp-kit-upsell__effective-label"><?php esc_html_e('with your welcome discount', 'brave-hearts'); ?></span>
      </span>
      <?php
      /*
       * ⭐ 1.19.355 (`CYCLE179-LD-355`, brief item 6) — THE EM DASH IS GONE.
       *
       * ⛔ SUPERSEDED STRING, PRESERVED SO THE MOVEMENT IS VISIBLE AND IS NOT
       *    RE-DERIVED:
       *
       *        'Applied automatically at checkout <em dash> no code to enter.'
       *
       * ⭐ RULE 608a: no em dash in Andrew's copy. `design-creative`'s aesthetic
       *    review found this string as one of eleven surfaces carrying one
       *    (`D5`). ⛔ ONLY THIS ONE IS CHANGED HERE. The other ten are the
       *    em-dash sweep of older copy, which this brief lists as OUT OF SCOPE
       *    and Andrew's.
       *
       * ⛔ THE WORDS ARE OTHERWISE UNTOUCHED and the sentence is split at the
       *    same place the dash split it, so nothing is added, removed or
       *    reordered. The screen-reader sentence above already used a comma and
       *    a clause and is not edited at all.
       */
      ?>
      <span class="bhp-kit-upsell__effective-note" aria-hidden="true"><?php esc_html_e('Applied automatically at checkout. No code to enter.', 'brave-hearts'); ?></span>
    </p>
    <?php endif; ?>
    <?php if ($bhp_akty_coupon) : ?>
    <p class="bhp-kit-upsell__coupon"><?php
      printf(
        /* translators: 1: coupon code, 2: discount percentage */
        esc_html__('Your %1$s code takes another %2$s off the collection at checkout.', 'brave-hearts'),
        '<strong>' . esc_html($bhp_akty_coupon['code']) . '</strong>',
        esc_html(rtrim(rtrim(number_format($bhp_akty_coupon['percent'], 2, '.', ''), '0'), '.') . '%')
      );
    ?></p>
    <?php endif; ?>
    <p class="align-center bhp-kit-upsell__cta">
      <a class="btn btn-primary" href="<?php echo esc_url($bhp_akty_cta_url); ?>" data-bhp-event="collection_upsell_click" data-bhp-format="collection" data-bhp-source="parent_thank_you"><?php esc_html_e('Get the Complete Collection', 'brave-hearts'); ?></a>
    </p>
    <?php
    /*
     * ⛔ NOT DELETED — MOVED. This is the same sentence the offer header
     *    carried in 1.19.228, verbatim. It sits BELOW the CTA now because
     *    every line above the CTA is charged against a ~535px budget at
     *    360x740 and this one is supporting detail, not the offer. Nothing
     *    was cut to make the fold; one paragraph changed places.
     */
    ?>
    <p class="bhp-kit-upsell__detail"><?php esc_html_e('The Mariana Trench, Mount Everest, and the Amazon - bundled together, shipped in one order, for less than buying each on its own.', 'brave-hearts'); ?></p>
    </div><!-- /.bhp-kit-upsell__card -->
  </div>
</section>

<section class="passport-section section section--muted" aria-labelledby="adventure-kit-thank-you-choose-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <h2 id="adventure-kit-thank-you-choose-title" class="text-section-title"><?php esc_html_e('Let Your Child Choose Their Adventure', 'brave-hearts'); ?></h2>
      <p class="component-heading__intro text-lead"><?php esc_html_e('Prefer to start with a single story? Begin with any one of the three.', 'brave-hearts'); ?></p>
    </header>
    <div class="grid grid--3 passport-steps">
      <?php foreach (['mariana_trench', 'mount_everest', 'amazon_rainforest'] as $key):
        $adventure = $adventures[$key] ?? [];
        if (empty($adventure['primary_url'])) {
            continue;
        }
      ?>
        <?php get_template_part('template-parts/components/book-card', null, [
            'title'       => $adventure['title'] ?? '',
            'url'         => $adventure['primary_url'],
            'image_id'    => $adventure['image_id'] ?? 0,
            'image_alt'   => $adventure['image_alt'] ?? '',
            'description' => $adventure['description'] ?? '',
            'age_range'   => $adventure['age_range'] ?? '',
            'cta_label'   => __('View the book', 'brave-hearts'),
        ]); ?>
      <?php endforeach; ?>
    </div>
    <p class="align-center">
      <a class="btn btn-outline" href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('See the Full Series', 'brave-hearts'); ?></a>
    </p>
  </div>
</section>
<?php get_footer(); ?>

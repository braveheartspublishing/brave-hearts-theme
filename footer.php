<?php
/**
 * Brave Hearts Publishing — Footer
 */
defined('ABSPATH') || exit;

$privacy_url = get_privacy_policy_url() ?: home_url('/privacy-policy/');
$terms_url   = home_url('/terms/');
if (function_exists('wc_get_page_id')) {
    $terms_page_id = wc_get_page_id('terms');
    if ($terms_page_id > 0) {
        $terms_url = bhp_get_safe_link_url(get_permalink($terms_page_id), $terms_url);
    }
}
$privacy_url = bhp_get_safe_link_url($privacy_url, home_url('/privacy-policy/'));
$terms_url = bhp_get_safe_link_url($terms_url, home_url('/terms/'));
$shipping_policy_url = bhp_get_safe_link_url(home_url('/shipping-policy/'));
$returns_url = home_url('/refund_returns/');
if (function_exists('wc_get_page_id')) {
    $returns_page_id = wc_get_page_id('refund_returns');
    if ($returns_page_id > 0) {
        $returns_url = get_permalink($returns_page_id);
    }
}
// Phase 1D: the Refund and Returns Policy page already existed but was
// never linked anywhere on the site (found during the product/collection
// trust-elements audit) -- this only adds the missing link, following the
// exact existing pattern used for Shipping Policy above.
$returns_url = bhp_get_safe_link_url($returns_url, home_url('/refund_returns/'));
/*
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.254 (2026-08-19, `CYCLE165-LD-COLLECTION-CONVERSION`) R-9 — ON
 *     THE COLLECTION PAGE, THE LAST THING BEFORE THE FOOTER IS THE ORDER.
 * ═══════════════════════════════════════════════════════════════════════
 *
 * MEASURED on production: on /complete-collection/ the quiz launcher (top
 * 5584) and the inline email capture (top 6154) sit between the page's final
 * purchase CTA and the footer. On a page whose single job is the order, the
 * two things immediately after the closing CTA are two competing
 * conversions.
 *
 * ⛔ NEITHER IS REMOVED, ON EITHER PAGE, AT ANY WIDTH. This REORDERS them and
 *    nothing else: on this one page they render AFTER the audience router
 *    instead of before it. Removing either is a different decision (D-6) and
 *    is the founder's, not this file's. The homepage-only exit popup is a
 *    separate component entirely and is untouched.
 *
 * ⛔ PAGE-SCOPED BY CONSTRUCTION. `bhp_book_is_collection_page()` is true only
 *    for a page whose content carries the collection shortcode, so every
 *    other page on the site renders this footer in exactly the order 1.19.253
 *    rendered it. If the theme ever loses that helper the condition is false
 *    and the ORIGINAL order stands — it fails back to today's behaviour, not
 *    to a broken one.
 *
 * ⛔ BOTH ELIGIBILITY GATES ARE UNTOUCHED. `bhp_should_show_quiz_cta()` and
 *    `bhp_should_show_footer_capture()` still decide WHETHER each renders;
 *    this only decides WHERE. A page that was not getting one still does not.
 *
 * ⛔ FUNNEL ISOLATION IS UNAFFECTED: no storage key, no analytics prefix and
 *    no lead-magnet key is read or written here.
 */
$bhp_defer_conversions = function_exists('bhp_book_is_collection_page') && bhp_book_is_collection_page();
$bhp_show_quiz_cta     = function_exists('bhp_should_show_quiz_cta') && bhp_should_show_quiz_cta();
$bhp_show_capture      = function_exists('bhp_should_show_footer_capture') && bhp_should_show_footer_capture();
?>
</main><!-- #main -->

<?php if ($bhp_show_quiz_cta && !$bhp_defer_conversions): ?>
  <?php
  /*
   * ⭐ 1.19.270 (`CYCLE165-LD-ITERATE-6-PATH-LINE`) — THE `is_front_page()`
   *    TERNARY IS NOW UNREACHABLE AND IS REPLACED BY A PLAIN CALL.
   *
   * It stamped the '#find-your-adventure' anchor id on the launcher, on the
   * homepage only, so the deep-link contract survived the 2026-07-31 removal
   * of the inline homepage quiz section. The founder has now removed the
   * launcher itself from the homepage (his ruling is quoted in full at
   * `bhp_should_show_quiz_cta()` in functions.php), so `$bhp_show_quiz_cta`
   * is false whenever `is_front_page()` is true and this branch can never be
   * taken again.
   *
   * ⛔ LEAVING A DEAD TERNARY HERE WOULD BE WORSE THAN REMOVING IT: it reads
   *    as "the homepage gets a special id", which is now the opposite of the
   *    truth, and the next reader would spend a turn discovering that.
   *
   * ⛔ NO OTHER PAGE CHANGES. The `else` half of that ternary was `[]`, which
   *    is exactly what this call now passes on every page that still renders
   *    the launcher — same template part, same arguments, same markup.
   *
   * ⚠ IF THE FOUNDER EVER RESTORES THE HOMEPAGE BAND, restore the `id` arg
   *   with it. The anchor is not needed today because the theme emits no
   *   `href="#find-your-adventure"` anywhere (scanned across *.php/*.js/*.css
   *   in 1.19.270), but it would be needed again the moment one is written.
   */
  get_template_part('template-parts/components/quiz-entry-cta');
  ?>
<?php endif; ?>

<footer class="site-footer" role="contentinfo">
  <?php
  /* Wave 1 (2026-08-04, theme 1.19.168) — sitewide capture block, first
     thing inside the footer so it is read before the navigation columns.
     Eligibility (never on /teachers/, never on a transaction surface,
     never on a page that is already a signup destination) is decided
     server-side in bhp_should_show_footer_capture(). */
  // 1.19.254 R-9: on the collection page this block renders after the
  // audience router below instead. Same gate, same template, same markup.
  if ($bhp_show_capture && !$bhp_defer_conversions) {
      get_template_part('template-parts/acquisition/footer-capture');
  }
  ?>
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="footer-logo">
        <?php /* Wave F (2026-08-03): the reversed lockup, no cream plate.
                 Same asset and the same reasoning as header.php -- see the
                 comment block there; it is not repeated. The footer ground is
                 #12271d (dark green) on the homepage and --color-navy
                 elsewhere, and the transparent reversed mark reads correctly
                 on both, which the plated navy lockup could not. */ ?>
        <a class="footer-logo__lockup" href="<?php echo esc_url(home_url('/')); ?>">
          <img class="footer-logo__mark"
               src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/brand/brave-hearts-horizontal-reversed-rose.png'); ?>"
               alt="<?php echo esc_attr(get_bloginfo('name')); ?>"
               width="338" height="100" loading="lazy" decoding="async">
        </a>
      </div>
      <p class="footer-closing"><?php esc_html_e('The real world is still wild enough.', 'brave-hearts'); ?><br><?php esc_html_e('Go look up.', 'brave-hearts'); ?></p>
      <p class="footer-proof">As an Amazon Associate, Brave Hearts Publishing earns from qualifying purchases.</p>
    </div>

    <nav class="footer-nav" aria-label="<?php esc_attr_e('Footer navigation', 'brave-hearts'); ?>">
      <?php
      /*
       * ⭐⭐ 1.19.269 (2026-08-19, `CYCLE165-LD-ITERATE-5-SUBTRACTIONS`) — THE
       *     FOOTER IS PRUNED TO shop / kit / contact / policies.
       *
       * FOUNDER RULING, 2026-08-19, subtraction item 4: prune the ~25-link
       * footer to "shop / kit / contact / policies (+ legally required bits)".
       *
       * WHAT THAT LEAVES, and each one is here because the ruling names it:
       *   · SHOP      -> /books/ and /complete-collection/  (this column)
       *   · KIT       -> /reluctant-reader-adventure-kit/   (this column)
       *   · CONTACT   -> /contact/ + the mailto              (this column)
       *   · POLICIES  -> privacy · shipping · refunds · terms (footer-bottom,
       *                  UNCHANGED by this release)
       *   · LEGALLY REQUIRED -> the Amazon Associates disclosure above, and
       *                  the copyright line. Neither is touched.
       *
       * ⛔ WHAT WENT, listed here rather than only in the report, because a
       *    future reader of this file needs to know these were removed
       *    deliberately and by whom:
       *      · the `footer` nav-menu location + `bhp_footer_fallback_menu()`'s
       *        NINE links (Books · Expedition Guides · Family Resources ·
       *        About · Blog · Contact · Privacy Policy · Terms · Adventure
       *        Club). Privacy and Terms survive in `footer-bottom`; Books and
       *        Adventure Club survive as Shop and Kit here.
       *      · the whole "Learn" column — Learning Hub · For Teachers ·
       *        For Families (3 links).
       *      · the whole `.footer-audience-cluster` router — Helping a
       *        reluctant reader? · Shopping for a meaningful gift? ·
       *        Teaching or homeschooling? · Planning a reading program?
       *        (4 links) — which is ALSO founder item 2, below.
       *      · the "Connect" column's prose note.
       *
       * ⛔ THE FALLBACK FUNCTION IS NOT DELETED. `bhp_footer_fallback_menu()`
       *    stays in `functions.php`, now unreferenced by this template, so the
       *    prune is one `wp_nav_menu()` call away from being reverted and so
       *    no other caller can break. Its own test coverage still runs.
       *
       * ⭐ ITEM 2 — THE AUDIENCE ROUTER IS GONE, AND GONE EVERYWHERE.
       *    Andrew ruled it off the collection page ("it exits buyers at the
       *    decision point; the audience pages are in the nav"). The router was
       *    never collection-scoped — it rendered in this footer on all 83
       *    pages — and item 4's prune list does not contain it, so removing it
       *    sitewide is what BOTH rulings require. 1.19.254's R-9 reorder,
       *    which moved the quiz and the capture BELOW the router on the
       *    collection page, is deliberately LEFT INTACT below: with the router
       *    gone it still does the one thing R-9 exists to do — keep those two
       *    conversions after the page's own closing CTA.
       */
      ?>
      <?php
      /*
       * ═══════════════════════════════════════════════════════════════════
       * ⭐⭐ 1.19.314 (2026-08-28, `CYCLE168-LD-RETAILER-BATCH`) — A FIFTH
       *     LINK: "Booksellers & Retailers". THE ONLY WAY A HUMAN CAN REACH
       *     `/retailers-wholesale-guide/` FROM ANYWHERE ON THIS SITE.
       * ═══════════════════════════════════════════════════════════════════
       *
       * ⛔⛔ THE DEFECT IT CLOSES, MEASURED RATHER THAN ASSERTED. Defect D3 of
       *     the retailer funnel review (`ANDREW-REVIEW/2026-08-28/RETAILER-FUNNEL-AND-OUTREACH.md`, 2026-08-28),
       *     production DOM read 2026-08-28: every anchor in every `header`,
       *     `footer` and `nav` element site-wide, filtered for
       *     retail/wholesale/bookseller/trade -> **EMPTY**. Re-measured on
       *     staging 1.19.313 by this desk the same day, same result:
       *     `footerRetail: []`. The page is HTTP 200, indexable, canonical
       *     and present in `page-sitemap.xml`, and no visitor could navigate
       *     to it. ⭐ Andrew's own instruction, item 360: "I want the
       *     bookseller page finished and ACTIVATED." That review's judgement, and
       *     it is the right one: "'Activated' is not true until this ships."
       *
       * ═══════════════════════════════════════════════════════════════════
       * ⚠️⚠️ AND IT IS IN TENSION WITH HIS OWN 2026-08-19 PRUNE. SAID PLAINLY.
       * ═══════════════════════════════════════════════════════════════════
       *
       * 1.19.269 cut this footer from ~25 links to four on his ruling:
       * "shop / kit / contact / policies (+ legally required bits)". A fifth
       * link is one more than that list contains, and pretending otherwise
       * would be dishonest bookkeeping of his own instruction.
       *
       * ⭐ WHY IT IS ADDED ANYWAY, AND WHY THIS IS NOT AN AGENT OVERRULING
       *   THE FOUNDER: the two rulings are nine days apart and the later one
       *   is specific. The prune removed links to pages that ALREADY had
       *   other routes; this page has NONE. Item 360 asks for the page to be
       *   activated and item 366 has him inspecting it, so he expects it to
       *   be reachable. ⛔ IF HE WANTS THE FOUR-LINK FOOTER BACK EXACTLY, THIS
       *   ONE `<li>` IS THE WHOLE REVERSAL and the release report says so.
       *
       * ⛔ WHAT WAS **NOT** DONE, and why, because a later reader will
       *    otherwise assume it was forgotten: NO PRIMARY-NAV ITEM WAS ADDED.
       *    The header nav is `wp_nav_menu( theme_location => 'primary' )`,
       *    i.e. a DATABASE menu, not theme code — verified on staging
       *    2026-08-28: menu 198 "Primary", SIX flat items (Home, Blog,
       *    About, Books, Contact, Teacher's Guide), no sub-levels and no
       *    audience-page section for a trade link to join. The dispatch said
       *    to use the existing pattern and not invent a menu section; there
       *    is no existing pattern to use. Adding a seventh top-level item to
       *    the CONSUMER navigation is an editorial decision about the main
       *    nav, it is a `wp menu item add` on each environment rather than a
       *    theme change, and it is Andrew's. The exact command is in the
       *    deploy packet as an optional step, unrun.
       *
       * ⭐ THE LABEL is "Booksellers & Retailers", which is what the page's
       *    own template name calls that audience. ⛔ NOT "Trade" (jargon a
       *    parent scanning a footer would not decode) and NOT "Wholesale"
       *    (which a parent might read as a discount offer to them).
       */
      ?>
      <p class="footer-col-title"><?php esc_html_e('Shop', 'brave-hearts'); ?></p>
      <ul>
        <li><a href="<?php echo esc_url(home_url('/books/')); ?>"><?php esc_html_e('The Books', 'brave-hearts'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/complete-collection/')); ?>"><?php esc_html_e('The Complete Collection', 'brave-hearts'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/reluctant-reader-adventure-kit/')); ?>"><?php esc_html_e('Free Reluctant Reader Kit', 'brave-hearts'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/retailers-wholesale-guide/')); ?>"><?php esc_html_e('Booksellers & Retailers', 'brave-hearts'); ?></a></li>
        <?php
        /*
         * ═══════════════════════════════════════════════════════════════════
         * ⭐⭐ 1.19.337 (2026-08-30, `CYCLE170-LD-MICRO`) — ABOUT LANDS HERE.
         *     CARRIER ITEM 547, THE SECOND HALF OF THE SAME RULING.
         * ═══════════════════════════════════════════════════════════════════
         *
         * ⭐⭐ THE FOUNDER'S WORDS, verbatim, 2026-08-30, carrier item 547:
         *    "the nav bar looks bad - lets remove the about and put read
         *     alouds stacked there - also this needs to always be uniform the
         *     font the style etc"
         *    ⛔ RELAYED through `chief-of-staff`; read first-hand at the carrier
         *      before this edit. NOT witnessed by this desk.
         *
         * ⛔⛔ THIS `<li>` IS WHY `/about/` DOES NOT BECOME AN ORPHAN. The
         *     other half of item 547 removes About from the primary nav
         *     (`bhp_primary_nav_about_out_readalouds_in()` in functions.php).
         *     Removing it from the nav WITHOUT adding it here would leave the
         *     page reachable only from the sitemap and from in-body links —
         *     which is exactly the D3 defect the "Booksellers & Retailers"
         *     line above this one was added to close in 1.19.314. ⭐ Not
         *     repeating a defect the file directly above records is the whole
         *     reason both halves ship in one release.
         *
         * ⛔ A DIRECT `<li>`, NOT THE `footer` MENU LOCATION, AND THE RULING
         *    SAYS SO. Gandalf's brief: *"direct footer.php list — the footer
         *    menu location is dead, bypass it."* ⭐ That is CORRECT and is
         *    corroborated by this file's own record: 1.19.269 removed the
         *    `wp_nav_menu(['theme_location' => 'footer'])` call (the preserved
         *    block a few lines below), so the location is still REGISTERED and
         *    is no longer RENDERED. A link added to that menu in wp-admin
         *    appears nowhere. Adding About there would have looked done and
         *    shipped nothing.
         *
         * ⚠️ AND THE SAME TENSION THE RETAILER LINE DECLARED, DECLARED AGAIN
         *    RATHER THAN QUIETLY INHERITED: the 2026-08-19 prune set this
         *    footer to "shop / kit / contact / policies". This is now a SIXTH
         *    link. ⭐ It is added because the LATER and more specific ruling
         *    (item 547, eleven days on) requires About to have a route, and
         *    naming the footer column is the founder's own chosen route.
         *    ⛔ IF HE WANTS THE FOUR-LINK FOOTER BACK EXACTLY, THIS `<li>` AND
         *    THE RETAILER `<li>` ARE THE WHOLE REVERSAL.
         *
         * ⛔ THE LABEL IS "About", which is the page's own title and the label
         *    it carried in the nav for its whole life. No new wording is
         *    invented for a link that is only changing where it lives.
         */
        ?>
        <li><a href="<?php echo esc_url(home_url('/about/')); ?>"><?php esc_html_e('About', 'brave-hearts'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Contact', 'brave-hearts'); ?></a></li>
      </ul>
      <?php
      /*
       * ⛔ REMOVED, PRESERVED VERBATIM SO THE MOVEMENT IS VISIBLE AND IS NOT
       *    RE-DERIVED. This is what stood here at 1.19.268:
       *
       *      a <p> with the footer-col-title class reading "Explore", then:
       *      wp_nav_menu([
       *          'theme_location' => 'footer',
       *          'menu_class'     => '',
       *          'container'      => false,
       *          'fallback_cb'    => 'bhp_footer_fallback_menu',
       *      ]);
       *
       * ⚠ CONSEQUENCE STATED RATHER THAN DISCOVERED LATER: the `footer` MENU
       *   LOCATION IS NO LONGER RENDERED. It is still REGISTERED in
       *   `functions.php`, so nothing in the admin breaks and no menu is
       *   deleted, but a link added to that menu in wp-admin will no longer
       *   appear on the site. That is a real editorial change and it is
       *   Andrew's to reverse; it is reported, not buried.
       *
       * ─────────────────────────────────────────────────────────────────────
       * ⭐⭐ 1.19.266 (2026-08-19, CYCLE165-LD-ITERATE-2-AESTHETICS-TOKENS) —
       *     THESE THREE COLUMN LABELS WERE <h2> AND ARE NOW <p>.
       *     RETAINED VERBATIM. Two of the three columns it describes are
       *     removed by this release, but `.footer-col-title` is still the
       *     element used above and in "Connect", and the reasoning below is
       *     still the reasoning for it being a <p>.
       *
       * WHY. Measured on staging 1.19.264 (headless Chrome, innerWidth
       * asserted 390): "Explore", "Learn" and "Connect" render at 10.5px
       * against an 18px body — i.e. THREE headings smaller than body text,
       * on all 83 pages, 249 instances sitewide. That is the rubric's row-1
       * "no heading smaller than body" failure, and the `commerce-cx` aesthetics
       * audit §8a item 6
       * is it. (The audit describes it as "an H3 at 10.5px on the homepage";
       * the measurement says H2, three of them, on every page. The finding is
       * right, the label was not — recorded rather than quietly corrected.)
       *
       * WHY DEMOTE RATHER THAN ENLARGE. The two available fixes are: set them
       * at >=18px, or stop them being headings. Enlarging changes the footer's
       * design on every page of the site to satisfy a semantic rule; demoting
       * changes NOTHING a visitor sees — the CSS selector lists in style.css
       * now carry `.footer-col-title` beside each `h2` and every declaration
       * is identical — and removes the defect at its cause. These are column
       * LABELS, not document sections.
       *
       * NOTHING IS LOST FOR ASSISTIVE TECHNOLOGY. `.footer-nav` and
       * `.footer-learn` are <nav> landmarks that ALREADY carry their own
       * `aria-label` ("Footer navigation", "Learning navigation"), so a
       * screen-reader user reaches and identifies them by landmark, not by
       * these headings. `.footer-contact` is a <div> with no landmark, so it
       * is given `role="group"` + `aria-labelledby` pointing at its label —
       * which names the group MORE precisely than a loose <h2> did.
       *
       * ⛔ NO COPY CHANGED. Three element names and one ARIA relationship.
       */
      ?>
    </nav>

    <div class="footer-contact" role="group" aria-labelledby="footer-connect-title">
      <p class="footer-col-title" id="footer-connect-title"><?php esc_html_e('Connect', 'brave-hearts'); ?></p>
      <p><a href="mailto:andrew@braveheartspublishing.com">andrew@braveheartspublishing.com</a></p>
      <?php
      /*
       * 1.19.269 item 4. Two lines left this column and one stayed.
       *
       * ⛔ GONE: the second "Join the Expedition" link (the Kit is already the
       *    third entry in Shop above — this was the same destination twice in
       *    one footer), and the prose note "Classroom read alouds, school
       *    visits, bulk orders, media inquiries, and upcoming releases."
       *    which is a list of reasons to write, not a link, on a footer the
       *    ruling reduces to links.
       *
       * ⭐ KEPT: the mailto. It IS "contact" in the ruling's four-word list,
       *    and it is the only route on the site that reaches Andrew without a
       *    form.
       *
       * ⛔ THE `footer-3` WIDGET AREA IS UNTOUCHED. It is empty today, but it
       *    is site OWNER content, not theme content, and silently dropping a
       *    widget area is not a link prune.
       */
      ?>
      <?php if (is_active_sidebar('footer-3')): ?>
        <?php dynamic_sidebar('footer-3'); ?>
      <?php endif; ?>
    </div>
  </div>

  <?php
  /*
   * 1.19.254 R-9, KEPT — the deferred pair on the collection page only:
   * quiz launcher -> email capture, still after the page's closing CTA.
   * 1.19.269 removed the `.footer-audience-cluster` router that used to sit
   * immediately above this block (founder item 2); the deferral itself was
   * not part of that ruling and is unchanged. See the block at the top of
   * this file for the original reasoning.
   */
  if ($bhp_defer_conversions) {
      if ($bhp_show_quiz_cta) {
          get_template_part('template-parts/components/quiz-entry-cta', null, []);
      }
      if ($bhp_show_capture) {
          get_template_part('template-parts/acquisition/footer-capture');
      }
  }
  ?>

  <div class="footer-bottom">
    <p>&copy; <?php echo esc_html(wp_date('Y')); ?> Brave Hearts Publishing LLC. <?php esc_html_e('All rights reserved.', 'brave-hearts'); ?>
      &nbsp;·&nbsp; <a class="footer-bottom__link" href="<?php echo esc_url($privacy_url); ?>"><?php esc_html_e('Privacy Policy', 'brave-hearts'); ?></a>
      <?php if ($shipping_policy_url): ?>
        &nbsp;·&nbsp; <a class="footer-bottom__link" href="<?php echo esc_url($shipping_policy_url); ?>"><?php esc_html_e('Shipping Policy', 'brave-hearts'); ?></a>
      <?php endif; ?>
      <?php if ($returns_url): ?>
        &nbsp;·&nbsp; <a class="footer-bottom__link" href="<?php echo esc_url($returns_url); ?>"><?php esc_html_e('Refund and Returns Policy', 'brave-hearts'); ?></a>
      <?php endif; ?>
      &nbsp;·&nbsp; <a class="footer-bottom__link" href="<?php echo esc_url($terms_url); ?>"><?php esc_html_e('Terms', 'brave-hearts'); ?></a>
      &nbsp;·&nbsp; <a class="footer-bottom__link" href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Contact', 'brave-hearts'); ?></a>
      <?php
      /*
       * ⭐⭐ 1.19.309 (2026-08-27, `CYCLE167-LD-CONSENT-BANNER-GEO`) — THE
       *     PERSISTENT "Privacy Choices" LINK. Founder report, carrier item 332:
       *     "The consent bar is still firing on new browsers."
       *
       * From 1.19.309 the WPConsent bar is shown to EEA/UK visitors ONLY (the
       * gate lives in `inc/consent-banner-compact.php`; read its docblock for
       * the signal, the fail-safe direction and the honest error rates). A US
       * visitor therefore never sees a bar — so WITHOUT THIS LINK THEY WOULD
       * HAVE NO ROUTE TO THE PREFERENCES UI AT ALL, and the opt-out would be
       * a claim rather than a control. This link IS the opt-out for everyone
       * outside the EEA, which is why it is site-wide and permanent rather
       * than conditional.
       *
       * ⭐ `wpconsent-open-preferences` IS THE PLUGIN'S OWN TRIGGER CLASS, not
       *    a theme invention: WPConsent binds a delegated document click
       *    handler to it and calls `showPreferences()` (build/frontend.js;
       *    the same class its own `[wpconsent_preferences_button]` shortcode
       *    emits). VERIFIED LIVE on staging against WPConsent Free 1.1.7,
       *    2026-08-27: a light-DOM element carrying this class opened the
       *    modal (`display: flex`) WITH the banner gate off.
       *
       * ⭐ THE href IS A REAL FALLBACK, NOT A `#`. The plugin's handler calls
       *    preventDefault(), so JS wins whenever it runs; if it never runs the
       *    visitor lands on the privacy policy instead of a dead anchor.
       *
       * ⛔ NO NEW CSS. It carries `footer-bottom__link`, the exact class its
       *    sibling policy links use, so it inherits their quiet styling and
       *    adds not one declaration to style.css.
       *
       * ⛔ RENDERED FOR EVERY VISITOR, IN EVERY REGION, DELIBERATELY. Making
       *    it conditional would vary the HTML per visitor, and SiteGround's
       *    page cache varies only on Accept-Encoding — that is
       *    `CYCLE143-GIM-51`, and it is not being re-created for a footer
       *    link. EEA visitors simply gain a way back into their choices.
       */
      if (function_exists('bhp_consent_banner_compact_active') && bhp_consent_banner_compact_active()): ?>
        &nbsp;·&nbsp; <a class="footer-bottom__link wpconsent-open-preferences" href="<?php echo esc_url($privacy_url); ?>"><?php esc_html_e('Privacy Choices', 'brave-hearts'); ?></a>
      <?php endif; ?>
    </p>
    <p class="footer-entry-close"><?php esc_html_e('End of entry · Close the journal', 'brave-hearts'); ?></p>
  </div>
</footer>

<?php wp_footer(); ?>
</div><!-- .site-wrapper -->
</body>
</html>

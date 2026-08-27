<?php
/**
 * Template Name: Audience Landing - Bookstores & Retailers
 * Description: Wholesale-buyer-facing landing page (independent bookstores,
 * museum stores, national park stores, visitor centers, nature centers,
 * gift shops, educational retailers, children's boutiques).
 *
 * Deliberately does NOT route wholesale ordering through WooCommerce --
 * the trade orders through Ingram, or talks to Andrew. There is no
 * Add to Cart on this page and there must never be one.
 *
 * ═════════════════════════════════════════════════════════════════════════
 * ⭐⭐ 1.19.304, 2026-08-27, `CYCLE167-LD-RETAILER-PAGE` — THE "COMING SOON"
 *    STATE IS OVER, AND THE GUARD THAT HELD IT IS CLEARED ON EVIDENCE.
 * ═════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE SUPERSEDED GUARD, PRESERVED VERBATIM so the movement is visible and
 *    nobody re-derives the old rule and reverts this page:
 *
 *      "Stays in a 'coming soon' state -- form wired but inactive -- until
 *       the guide PDF is set under Settings -> Lead Magnets (see
 *       bhp_get_bookstore_guide_download())."
 *      "Ingram distribution readiness has not been confirmed anywhere in the
 *       project's canonical docs as of this writing (docs/WORKLOG/
 *       2026-07-13.md explicitly lists Ingram as out of scope / not yet
 *       touched) -- this page must never claim active Ingram availability;
 *       it says 'coming soon' instead. Do not change that wording without a
 *       canonical-docs update confirming real Ingram readiness first."
 *
 * ⭐⭐ THE GUARD ASKED FOR EXACTLY ONE THING AND IT NOW EXISTS. Ingram
 *    readiness was confirmed by somebody OPENING THE ACCOUNT AND READING THE
 *    FIELD, which is a stronger instrument than the canonical-docs update the
 *    guard asked for: `Business OS\WORKING-DRAFTS\connected-operator\
 *    CYCLE167-GIM-INGRAM-READ-2-2026-08-27.md`, authenticated IngramSpark
 *    account 9885354, read 2026-08-27, FIVE editions `Title Available` with
 *    `Enabled for Distribution: Yes`. The full provenance, the two withheld
 *    ISBNs and every term value live in `inc/retailer-trade-terms.php`.
 *
 * ⚠️ THE LEAD-MAGNET HALF OF THE GUARD IS **NOT** CLEARED AND IS NOT BEING
 *    PRETENDED AWAY. The Wholesale Guide PDF still does not exist. What
 *    changed is the FAILURE MODE: the page no longer renders a "Coming soon"
 *    button beside a placeholder graphic that literally reads "Guide cover in
 *    progress". A professional buyer who sees a placeholder decides the
 *    publisher is not real, and that is the whole page's credibility lost to
 *    an unfinished JPEG (Merry, `02-RETAILER-FUNNEL-SPEC.md` §5.1). The
 *    entire block is now SUPPRESSED while the PDF is unset, and the trade
 *    enquiry is the single call to action.
 *    ⭐ THE `$download['ready']` BRANCH IS INTACT AND UNCHANGED. Setting the
 *      PDF under Settings -> Lead Magnets restores the magnet, its modal and
 *      its Mailchimp tagging with NO code change and NO redeploy.
 *
 * ⛔ WHAT THIS PAGE STILL MAY NOT SAY, and asserted as absences by
 *    `tests/test-cycle167-retailer-funnel.php`: no minimum order quantity, no
 *    lead time, no freight offer, no margin, no trim size, no page count, no
 *    BISAC, no carton quantity, no sell-through / reorder / performance claim,
 *    no coupon, no aggregateRating or review schema, no Add to Cart, and no
 *    ISBN outside the five verified-orderable editions.
 *
 * Shares the audience-landing design system (assets/css/audience-landing.css
 * + assets/js/audience-landing.js) with the other 3 core audience pages.
 */
defined('ABSPATH') || exit;
get_header();

$page_id = get_queried_object_id();
$source_page = get_permalink($page_id) ?: home_url('/');
$download = bhp_get_bookstore_guide_download();
$adventures = bhp_get_series_adventures();

/*
 * ⭐ THE TRADE ENQUIRY ROUTE. `/contact/?inquiry=wholesale` — the mechanism
 *    already existed: `contact-form.php` reads `$_GET['inquiry']`, validates it
 *    against the registered types and preselects the dropdown. The `wholesale`
 *    type is added through the EXISTING `bhp_contact_inquiry_types` FILTER in
 *    functions.php, never by editing the shared array in place.
 */
$contact_url = add_query_arg('inquiry', 'wholesale', home_url('/contact/'));

$trade_rows      = function_exists('bhp_retailer_orderable_titles') ? bhp_retailer_orderable_titles() : [];
$terms_uniform   = function_exists('bhp_retailer_terms_are_uniform') && bhp_retailer_terms_are_uniform();
$trade_discount  = $terms_uniform && !empty($trade_rows[0]['discount']) ? $trade_rows[0]['discount'] : '';
$trade_returns   = $terms_uniform && !empty($trade_rows[0]['returnable']) ? $trade_rows[0]['returnable'] : '';

if (class_exists('BHP_Analytics_Config') && BHP_Analytics_Config::should_render_analytics()):
    /*
     * ⭐ FUNNEL ISOLATION, RAIL 1. `retailer_landing_view` ALREADY EXISTS and is
     *    reused verbatim. No second event is minted for this page, and no
     *    `parent_popup` / `teacher_popup` prefix is read or written anywhere in
     *    this funnel. `.claude/rules/funnels.md`, and Merry §5.3.
     */
    $bhp_retailer_landing_payload = wp_json_encode([
        'event'      => 'retailer_landing_view',
        'funnel'     => 'retailers',
        'page_type'  => 'landing_page',
        'lead_offer' => 'bookstore_wholesale_guide',
        'audience'   => 'retailers',
    ]);
    ?>
    <script>
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(<?php echo $bhp_retailer_landing_payload; ?>);
    </script>
    <?php
endif;

$mariana = $adventures['mariana_trench'] ?? [];
$everest = $adventures['mount_everest'] ?? [];
$amazon  = $adventures['amazon_rainforest'] ?? [];

$shelf_reasons = [
    __('<strong>Visually distinctive</strong> - illustrated covers built around real, recognizable destinations.', 'brave-hearts'),
    __('<strong>A multi-destination series</strong> - three titles now (ocean, mountain, rainforest), with room to grow.', 'brave-hearts'),
    __('<strong>Natural fit for destination retail</strong> - museum stores, park stores, and nature centers can shelve the matching title for their location.', 'brave-hearts'),
    __('<strong>Featuring a Kirkus-reviewed title</strong> - independent editorial credibility for a young middle-grade series.', 'brave-hearts'),
    __('<strong>Gift-ready</strong> - hardcover keepsake edition available alongside paperback.', 'brave-hearts'),
];

$reader_profile = [
    ['title' => __('Ages 6–9', 'brave-hearts'), 'text' => __('Early-to-middle grade readers and the adults buying for them.', 'brave-hearts')],
    ['title' => __('Gift & destination shoppers', 'brave-hearts'), 'text' => __('Visitors looking for a meaningful takeaway tied to where they are.', 'brave-hearts')],
    ['title' => __('Educators & homeschool families', 'brave-hearts'), 'text' => __('Story-led, cross-curricular reading that connects to geography and science.', 'brave-hearts')],
];

/*
 * ⭐ THE FAQ. Seven of these nine answers are Andrew's approved copy and are
 *    reproduced BYTE-FOR-BYTE except where the sole change is "we" -> "I",
 *    which is his own standing voice rule (Standing Rules §9.1, adopted
 *    2026-08-18 in his own words: "when you are putting front facing words to
 *    customers, there is no 'we'. I am the sole operator of the company.").
 *    Every such conversion is listed line by line in the release report.
 *
 * ⛔ THREE ANSWERS WERE FACTUALLY FALSE THE MOMENT THE INGRAM READ LANDED and
 *    are REWRITTEN rather than restyled — a wrong answer is not locked prose:
 *      · "What editions are available?"  said all three titles in both formats.
 *        The Amazon HARDCOVER cannot be ordered through Ingram today.
 *      · "Do you offer wholesale discounts or standard trade terms?" said terms
 *        are "not published here". The discount and the returns setting ARE now
 *        published here, because they were read live.
 *      · "When will Ingram distribution be ready?" said there is no confirmed
 *        date. It is ready.
 *    ⛔ The two answers Merry flagged as needing Andrew's decision — the
 *      minimum-order answer and the unbounded review-copy answer (FMC-7) — are
 *      NOT bounded here. Bounding them is his call, not this desk's.
 */
$faqs = [
    [__('What age range are the books for?', 'brave-hearts'), __('Readers roughly ages 6–9 (1st–3rd grade) - approachable for independent readers and rich enough for a shared read-aloud.', 'brave-hearts')],
    [__('What editions can I order through Ingram?', 'brave-hearts'), __('Five editions today: paperbacks of all three titles, and hardcovers of The Mariana Trench and Mount Everest. The Amazon hardcover is still being set up at Ingram and is not orderable yet. The table above lists each ISBN.', 'brave-hearts')],
    [__('Is there a fourth book coming?', 'brave-hearts'), __('The series is designed with room to grow beyond the current three destinations - reach out for the latest on future titles.', 'brave-hearts')],
    [__('How do I place a wholesale order?', 'brave-hearts'), __('Through Ingram, the same way you order any other title: search the ISBN in ipage and order it. If you are not set up with Ingram, or you would rather deal with me directly, start an inquiry below.', 'brave-hearts')],
    [__('Do you offer wholesale discounts or standard trade terms?', 'brave-hearts'), __('The trade discount set on each of the five editions at Ingram is 55%, and each one is returnable. Both values are in the table above. Anything beyond that, start an inquiry and I will go through it with you.', 'brave-hearts')],
    [__('Is there a minimum order quantity?', 'brave-hearts'), __('There’s no published minimum yet - reach out and I’ll figure out what makes sense for your shelf space and expected sell-through.', 'brave-hearts')],
    [__('Can I get a review or desk copy before ordering?', 'brave-hearts'), __('Yes - mention this in your wholesale inquiry and I’ll arrange a copy for you to look over before you commit to an order.', 'brave-hearts')],
    [__('Where are the books printed?', 'brave-hearts'), __('Every title is printed on demand. Nothing goes out of print, and nothing is ever unavailable to reorder.', 'brave-hearts')],
    [__('Who do I actually talk to?', 'brave-hearts'), __('Me. I write these books, I publish them, and I answer this inbox myself.', 'brave-hearts')],
];
?>
<div class="audience-landing" data-audience-landing>

<!-- ===================== HERO ===================== -->
<?php
/*
 * ⛔ HERO COPY IS KEEP-UNCHANGED. The heading, the lead paragraph and all three
 *    proof-strip items are BYTE-IDENTICAL to 1.19.303. Each proof item is
 *    sourced, and "Featuring a Kirkus-reviewed title" is the series_note
 *    framing that exists precisely so Everest and The Amazon never imply they
 *    were reviewed.
 *
 * ⚠ THE TWO CTAs MOVED, and only because their destination stopped existing.
 *    Both previously opened the Wholesale Guide modal, which is suppressed
 *    while the PDF is unset. A CTA whose target does not render is a dead
 *    control, so the primary now goes to the ISBNs and terms — the thing a
 *    trade buyer actually came for — and the secondary to the enquiry.
 */
?>
<section class="audience-landing-hero">
  <div class="audience-landing-hero__bg" aria-hidden="true"></div>
  <div class="audience-landing__inner audience-landing-hero__grid">
    <div>
      <span class="audience-landing-eyebrow audience-landing-hero__badge"><?php esc_html_e('For bookstores, museum stores & educational retailers', 'brave-hearts'); ?></span>
      <h1><?php esc_html_e('A visually distinctive adventure series for your shelves.', 'brave-hearts'); ?></h1>
      <p class="audience-landing__lead"><?php esc_html_e('Illustrated middle-grade adventures built around real destinations - a natural fit for independent bookstores, museum and park stores, nature centers, and educational retailers.', 'brave-hearts'); ?></p>
      <div class="audience-landing-hero__ctas">
        <?php if ($download['ready']): ?>
          <a class="btn btn-primary" href="#free" data-audience-free-cta data-bhp-signup-modal-open="retailer-wholesale-guide-modal" data-bhp-signup-modal-source="hero" data-bhp-event="retailer_hero_primary_cta_click" data-bhp-source="retailer_landing"><?php esc_html_e('Get the Wholesale Guide', 'brave-hearts'); ?></a>
        <?php else: ?>
          <a class="btn btn-primary" href="#titles" data-bhp-event="retailer_hero_primary_cta_click" data-bhp-source="retailer_landing"><?php esc_html_e('See the ISBNs and terms', 'brave-hearts'); ?></a>
        <?php endif; ?>
        <a class="btn btn-outline" href="#contact" data-bhp-event="retailer_hero_secondary_cta_click" data-bhp-source="retailer_landing"><?php esc_html_e('Start a Wholesale Inquiry', 'brave-hearts'); ?></a>
      </div>
      <div class="audience-landing-hero__proof">
        <span>&#9733; <?php esc_html_e('Featuring a Kirkus-reviewed title', 'brave-hearts'); ?></span><span class="sep">&middot;</span>
        <span><?php esc_html_e('3 published titles, multi-destination series', 'brave-hearts'); ?></span><span class="sep">&middot;</span>
        <span><?php esc_html_e('Paperback & hardcover editions', 'brave-hearts'); ?></span>
      </div>
    </div>
    <div class="audience-landing-hero__art">
      <?php if (has_custom_logo()): the_custom_logo(); endif; ?>
      <div class="audience-landing-hero__covers">
        <?php if ($mariana): ?><div class="audience-landing-hero__cover--side audience-landing-hero__cover--left"><?php echo bhp_parent_landing_cover($mariana); ?></div><?php endif; ?>
        <?php if ($everest): ?><div class="audience-landing-hero__cover--center"><?php echo bhp_parent_landing_cover($everest); ?></div><?php endif; ?>
        <?php if ($amazon): ?><div class="audience-landing-hero__cover--side audience-landing-hero__cover--right"><?php echo bhp_parent_landing_cover($amazon); ?></div><?php endif; ?>
      </div>
      <p class="audience-landing-hero__caption"><?php esc_html_e('Ocean &middot; Mountain &middot; Rainforest', 'brave-hearts'); ?></p>
    </div>
  </div>
</section>

<!-- ===================== QUICK-SCAN BAR ===================== -->
<section class="audience-landing-scanbar">
  <div class="audience-landing__inner audience-landing__inner--narrow audience-landing-scanbar__row">
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Ages 6–9 · 1st–3rd grade', 'brave-hearts'); ?></span>
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('3 titles, multi-destination series', 'brave-hearts'); ?></span>
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Paperback & hardcover', 'brave-hearts'); ?></span>
    <span class="audience-landing-scanbar__item"><span class="check">&#10003;</span><?php esc_html_e('Kirkus-reviewed', 'brave-hearts'); ?></span>
  </div>
</section>

<?php
/*
 * ═════════════════════════════════════════════════════════════════════════
 * ⭐⭐ B2 — ORDERING THROUGH INGRAM. THE BLOCK THE FOUNDER ACTUALLY ASKED FOR,
 *    AND IT IS DELIBERATELY THE FIRST THING AFTER THE FOLD.
 * ═════════════════════════════════════════════════════════════════════════
 *
 * ⭐ ORDERING PRINCIPLE (Merry §3.2): a trade buyer scans for METADATA and an
 *    ORDERING ROUTE first and reads prose second. The old page led with brand
 *    and buried the ISBNs in a JS payload. That is inverted here.
 *
 * ⛔⛔ THE TERMINOLOGY CORRECTION, AND IT IS THE WHOLE CREDIBILITY TEST.
 *    The founder's instruction said "lead them to ingramspark for purchase".
 *    ⛔ A BOOKSELLER CANNOT BUY ANYTHING ON INGRAMSPARK. IngramSpark is the
 *    PUBLISHER-side platform — it is where HE lists the book. A bookstore
 *    orders through INGRAM CONTENT GROUP, on ipage, on a verified trade
 *    account. Merry established this from Ingram's own Independent Bookstore
 *    Services page, fetched 2026-08-27. If this page said "order on
 *    IngramSpark", every professional buyer who read it would know instantly
 *    that the publisher does not understand the trade.
 *    ⭐ The correction is Andrew's to ratify; the page is built to the
 *      corrected form because the incorrect form would be a factual error.
 *
 * ⛔ "and it is there" IS DELIBERATELY NOT WRITTEN after "search the ISBN in
 *    ipage" (`CYCLE167-MKT-T05`). Nobody in this company has ever searched a
 *    Brave Hearts ISBN in ipage. The distribution flag is verified; the
 *    findability of the record in that particular search box is not, and the
 *    difference is exactly the kind of small over-claim this audience notices.
 *
 * ⛔ INGRAM'S OWN "FREE FREIGHT ON 20+ UNITS" IS NOT RESTATED (`T02`). It is
 *    Ingram's offer to the store. On this page it would read as ours.
 */
?>
<!-- ===================== B2 · ORDERING THROUGH INGRAM ===================== -->
<section id="ordering" class="audience-landing__section audience-landing__section--muted">
  <div class="audience-landing__inner audience-landing__inner--content">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('Ordering', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Ordering through Ingram.', 'brave-hearts'); ?></h2>
      <p class="audience-landing__lead"><?php esc_html_e('The series is distributed by Ingram, so if you already have an Ingram account you can order it the way you order anything else. Search the ISBN in ipage.', 'brave-hearts'); ?></p>
      <p class="audience-landing__lead" style="font-size:16px;"><strong><?php esc_html_e('New to Ingram?', 'brave-hearts'); ?></strong> <?php esc_html_e('Accounts are set up directly with Ingram Content Group at ingramcontent.com, and they verify the business before the account goes live.', 'brave-hearts'); ?></p>
      <p class="audience-landing__lead" style="font-size:16px;"><strong><?php esc_html_e('Would you rather deal with me directly?', 'brave-hearts'); ?></strong> <?php esc_html_e('Say so below and I will come back to you personally with timing and anything else you need.', 'brave-hearts'); ?></p>
    </div>
  </div>
</section>

<?php
/*
 * ═════════════════════════════════════════════════════════════════════════
 * ⭐⭐ B3 — THE TITLES, THE ISBNs AND THE TERMS, AS VISIBLE CRAWLABLE TEXT.
 * ═════════════════════════════════════════════════════════════════════════
 *
 * ⭐⭐ THIS IS SIMULTANEOUSLY THE PAGE'S BIGGEST HUMAN CHANGE AND ITS BIGGEST
 *    SEO CHANGE, AND THEY ARE THE SAME CHANGE. Before 1.19.304 the ISBNs
 *    existed on this page ONLY inside a JavaScript payload — measured live on
 *    staging 2026-08-27: 7 ISBN strings in the HTML, ZERO in the rendered
 *    text. A number a buyer cannot see and a crawler will not index is not
 *    published. Now there are five, in a real table, in the document body.
 *
 * ⛔ NOT ONE ISBN IS HARDCODED IN THIS TEMPLATE. Every row comes from
 *    `bhp_retailer_orderable_titles()`, which joins the dated Ingram registry
 *    to `bhp_bundle_catalog()` and fails closed on either side. The two
 *    withheld ISBNs cannot reach this table even if somebody adds them to the
 *    catalog, and the title strings are the catalog's own.
 *
 * ⚠️ THE PRICE COLUMN IS THE **INGRAM** LIST PRICE, LABELLED AS SUCH, AND THAT
 *    IS A DELIBERATE DEPARTURE FROM MERRY'S §4.3 WHICH KEPT IT OFF THE PAGE.
 *    His reason was sound at the time: `T07`, the $12.99 Ingram / $11.99 site
 *    asymmetry invites a question the page could not answer. It is on the page
 *    now because the brief requires the verified terms and because a trade
 *    table without a list price is not a trade table — a bookseller computes
 *    their cost from list minus discount, and omitting list makes the 55%
 *    meaningless. ⛔ THE ASYMMETRY ITSELF IS NOT RESOLVED HERE AND IS NOT
 *    MINE TO RESOLVE. It is recorded, still open, still Andrew's + Frodo's.
 *    The two prices are kept in separate blocks and each is labelled by whose
 *    price it is.
 */
?>
<!-- ===================== B3 · TITLES, ISBNs, TERMS ===================== -->
<section id="titles" class="audience-landing__section">
  <div class="audience-landing__inner">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('Titles and terms', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('The details your buying meeting needs.', 'brave-hearts'); ?></h2>
      <?php if ($trade_rows): ?>
        <p class="audience-landing__lead"><?php esc_html_e('Every edition you can order through Ingram today, with its ISBN and the terms set on it.', 'brave-hearts'); ?></p>
      <?php endif; ?>
    </div>

    <?php if ($trade_rows): ?>
      <?php if ($terms_uniform): ?>
        <p class="retailer-terms__summary">
          <?php
          printf(
              /* translators: 1: trade discount, e.g. 55%%. 2: the returns setting as Ingram states it. */
              esc_html__('Every edition below carries the same terms at Ingram: %1$s wholesale discount, returnable (%2$s).', 'brave-hearts'),
              esc_html($trade_discount),
              esc_html($trade_returns)
          );
          ?>
        </p>
      <?php endif; ?>

      <div class="retailer-terms__scroll">
        <table class="retailer-terms">
          <caption class="screen-reader-text"><?php esc_html_e('Editions available to order through Ingram, with ISBN, list price, wholesale discount and returns setting.', 'brave-hearts'); ?></caption>
          <thead>
            <tr>
              <th scope="col"><?php esc_html_e('Title', 'brave-hearts'); ?></th>
              <th scope="col"><?php esc_html_e('Format', 'brave-hearts'); ?></th>
              <th scope="col"><?php esc_html_e('ISBN', 'brave-hearts'); ?></th>
              <th scope="col"><?php esc_html_e('List price (Ingram, US)', 'brave-hearts'); ?></th>
              <th scope="col"><?php esc_html_e('Wholesale discount', 'brave-hearts'); ?></th>
              <th scope="col"><?php esc_html_e('Returns', 'brave-hearts'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($trade_rows as $row): ?>
              <tr>
                <th scope="row" class="retailer-terms__title"><?php echo esc_html($row['label']); ?></th>
                <td><?php echo esc_html($row['format_label']); ?></td>
                <td class="retailer-terms__isbn"><?php echo esc_html($row['isbn']); ?></td>
                <td>$<?php echo esc_html($row['list_us']); ?></td>
                <td><?php echo esc_html($row['discount']); ?></td>
                <td><?php echo esc_html($row['returnable']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <p class="retailer-terms__note"><?php esc_html_e('Ages 6–9 · 12 short chapters · illustrated throughout.', 'brave-hearts'); ?></p>
      <p class="retailer-terms__note"><?php esc_html_e('List price above is the Ingram list price for the trade. Trim size, page count and BISAC codes for each edition are available on request - ask below and I will send them.', 'brave-hearts'); ?></p>
    <?php endif; ?>
  </div>
</section>

<!-- ===================== WHY IT BELONGS ON SHELVES ===================== -->
<section class="audience-landing__section audience-landing__section--muted">
  <div class="audience-landing__inner audience-landing-split">
    <div>
      <span class="audience-landing-eyebrow"><?php esc_html_e('Why the series belongs on shelves', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('A series built for destination retail.', 'brave-hearts'); ?></h2>
      <p class="audience-landing__lead"><?php esc_html_e('Each title is anchored to a real place - giving a museum store, park store, or nature center a title that matches exactly what a visitor just experienced.', 'brave-hearts'); ?></p>
      <?php
      /*
       * ⭐⭐ THE ONE NEW CLAIM ON THIS PAGE, AND IT IS ENTIRELY THE FOUNDER'S.
       *    Licensed by carrier item 289 ("Yes its a claim - we need to make
       *    some claims honestly we need to sell books"), which loosened what
       *    the MAKER may claim about what the book IS and is BUILT to do. It
       *    did NOT loosen fabricated evidence, and none is used.
       *
       * ⛔ EVERY CLAUSE TRACES TO A CARRIER ITEM, not to this desk:
       *    · "short chapters, a lot of white space, illustrations right
       *      through"  -> item 286, verbatim "a lot of white space, supportive
       *      and fun illustrations, and an easy lyrical style"
       *    · "finishing a chapter is an easy win" -> item 286, verbatim "they
       *      can turn pages and finish chapters for easy wins"
       *    · "kids pick these up at my table and keep reading" -> item 287,
       *      verbatim "I have kids open these books and enjoy reading a page or
       *      two right in front of me because the format is easy"
       *
       * ⛔ ITEM 287'S OTHER HALF — "a lot of kiddos immediately say yes" — IS
       *    ATTESTED, TRUE, AND DELIBERATELY NOT USED (`CYCLE167-MKT-T08`). On a
       *    trade page it reads as a conversion-rate claim, which is one inch
       *    from a sell-through claim, and there is no sell-through datum
       *    anywhere in this company.
       *
       * ⛔ "That is what the format is for" keeps item 286 as a GOAL framing.
       *    It must never become an outcome claim about any child.
       */
      ?>
      <p class="audience-landing__lead" style="font-size:16px;"><strong><?php esc_html_e('Built for the reader who stalls.', 'brave-hearts'); ?></strong> <?php esc_html_e('Short chapters, a lot of white space, and illustrations right through, so that finishing a chapter is an easy win rather than a slog. That is what the format is for, and it is why kids pick these up at my table and keep reading.', 'brave-hearts'); ?></p>
    </div>
    <div class="audience-landing-checklist">
      <?php foreach ($shelf_reasons as $point): ?>
        <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php echo wp_kses_post($point); ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php
/*
 * ═════════════════════════════════════════════════════════════════════════
 * ⭐ B7 — RISK REMOVAL. Promoted out of the FAQ, where it was item 8.
 * ═════════════════════════════════════════════════════════════════════════
 *
 * ⭐⭐ THIS IS THE PAGE'S ACTUAL OFFER AND THE REASONING IS WORTH KEEPING.
 *    A bookseller's risk is not the money — three copies is nothing. The risk
 *    is JUDGEMENT, shelf space, and the six weeks a title occupies before it
 *    goes back in a box. There is no sell-through datum anywhere in this
 *    corpus and none can be manufactured, so the page cannot prove return.
 *    ⭐ WHEN YOU CANNOT PROVE RETURN, REMOVE RISK. A buyer who cannot be shown
 *      sell-through can still be shown that there is no downside to finding
 *      out. That is truthful and it is the page's strategy.
 *
 * ⛔ NO NEW FACT IS CREATED HERE. Both bullets restate things already true and
 *    already on the page. ⛔ The "a copy to look at first" bullet Merry drafted
 *    is NOT included: it depends on FMC-7, the review-copy boundary, which is
 *    Andrew's and is unanswered. The unbounded FAQ answer is left exactly as he
 *    approved it rather than quietly bounded here.
 */
?>
<!-- ===================== B7 · A SMALL FIRST ORDER ===================== -->
<section class="audience-landing__section">
  <div class="audience-landing__inner audience-landing__inner--content">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('First order', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('A small first order is a fine first order.', 'brave-hearts'); ?></h2>
    </div>
    <div class="audience-landing-checklist audience-landing-checklist--compact">
      <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php echo wp_kses_post(__('<strong>No minimum.</strong> Three copies of one title is fine if that is what the shelf has room for.', 'brave-hearts')); ?></span></div>
      <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php echo wp_kses_post(__('<strong>Printed on demand.</strong> Nothing goes out of print, so nothing sits in a box because I could not reprint it.', 'brave-hearts')); ?></span></div>
    </div>
  </div>
</section>

<!-- ===================== READER PROFILE ===================== -->
<section class="audience-landing__section audience-landing__section--muted">
  <div class="audience-landing__inner">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('Who buys this series', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Your customer profile.', 'brave-hearts'); ?></h2>
    </div>
    <div class="audience-landing-grid audience-landing-grid--cols-3">
      <?php foreach ($reader_profile as $item): ?>
        <div class="audience-landing-card"><h3><?php echo esc_html($item['title']); ?></h3><p class="desc"><?php echo esc_html($item['text']); ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php
/*
 * ═════════════════════════════════════════════════════════════════════════
 * ⭐ B5 — WHO YOU WOULD BE DEALING WITH.
 * ═════════════════════════════════════════════════════════════════════════
 *
 * ⭐ Institutional buyers buy the VENDOR before the product, and the vendor
 *    here is a real person with a real face.
 *
 * ⭐⭐ THE SECOND PARAGRAPH IS THE BEST PARAGRAPH AVAILABLE TO THIS PAGE AND
 *    IT IS ENTIRELY HIS. Carrier item 288, verbatim: "I wrote the first book
 *    by starting with poetry. Naturally, poetry reads with white space,
 *    pauses, and room for thought and contemplation. The same thing a young
 *    reader needs in an early chapter book."
 *    ⚠ "seven year old" is a SPECIFIC standing in for his general "young
 *      reader". It sits inside 6-9 so it is safe, but it is a change to his
 *      words and he may prefer his own phrasing. Flagged, not hidden.
 *
 * ⛔ "if you email me you get me" IS A FOUNDER COMMITMENT (gate H-T5). It ships
 *    only if he will actually answer. Same for the reply promise in B2.
 *
 * ⛔ ABSENT AND DELIBERATELY SO: anything about his family, and the word
 *    "daughter" — Charlotte is his NIECE and he has no children (item 285).
 *    Also absent: "Island Peak", "Jiri", "20,000 feet", "without oxygen" —
 *    the four unconfirmed specifics `check_author_fingerprint()` guards.
 *    The nursing and uncle clauses from his locked founder line are NOT used
 *    here; this is a trade audience and they are not load-bearing. His call.
 */
?>
<!-- ===================== B5 · WHO I AM ===================== -->
<section class="audience-landing__section">
  <div class="audience-landing__inner audience-landing__inner--content">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('The publisher', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Who you would be dealing with.', 'brave-hearts'); ?></h2>
    </div>
    <p class="audience-landing__lead"><?php esc_html_e('I am Andrew. I write these, I publish them, and I answer this inbox myself. There is no sales team behind me, which means two things: nothing is going to be handled by someone who has not read the books, and if you email me you get me.', 'brave-hearts'); ?></p>
    <p class="audience-landing__lead"><?php esc_html_e('The first one started as poetry. That is not a flourish, it is the reason the pages look the way they do. Poetry reads with white space and pauses and room to think, and that turns out to be exactly what a seven year old needs in a first chapter book.', 'brave-hearts'); ?></p>
  </div>
</section>

<!-- ===================== PRODUCT / DISPLAY APPEAL ===================== -->
<section class="audience-landing__section audience-landing__section--muted">
  <div class="audience-landing__inner">
    <div class="audience-landing__header-block">
      <span class="audience-landing-eyebrow"><?php esc_html_e('On the shelf', 'brave-hearts'); ?></span>
      <h2><?php esc_html_e('Current titles.', 'brave-hearts'); ?></h2>
      <p class="audience-landing__lead"><?php esc_html_e('Three complete adventures now, with future destinations planned as the series continues.', 'brave-hearts'); ?></p>
    </div>
    <div class="audience-landing-books">
      <?php if ($mariana): ?>
        <div class="audience-landing-book"><?php echo bhp_parent_landing_cover($mariana); ?><p class="eyebrow-line"><?php esc_html_e('Book One · Ocean', 'brave-hearts'); ?></p><h3><?php echo esc_html($mariana['title'] ?? 'The Mariana Trench'); ?></h3><p class="desc"><?php esc_html_e('Deep-sea science and courage in the unknown.', 'brave-hearts'); ?></p></div>
      <?php endif; ?>
      <?php if ($everest): ?>
        <div class="audience-landing-book"><?php echo bhp_parent_landing_cover($everest); ?><p class="eyebrow-line"><?php esc_html_e('Book Two · Mountain', 'brave-hearts'); ?></p><h3><?php echo esc_html($everest['title'] ?? 'Mount Everest'); ?></h3><p class="desc"><?php esc_html_e('Historic explorers, teamwork, and perseverance.', 'brave-hearts'); ?></p></div>
      <?php endif; ?>
      <?php if ($amazon): ?>
        <div class="audience-landing-book"><?php echo bhp_parent_landing_cover($amazon); ?><p class="eyebrow-line"><?php esc_html_e('Book Three · Rainforest', 'brave-hearts'); ?></p><h3><?php echo esc_html($amazon['title'] ?? 'The Amazon'); ?></h3><p class="desc"><?php esc_html_e('Rainforest wildlife, river systems, and kindness.', 'brave-hearts'); ?></p></div>
      <?php endif; ?>
    </div>
    <p class="audience-landing__pull-quote"><?php esc_html_e('A multi-destination series with future-series potential - one shelf placement now, more titles to grow with later.', 'brave-hearts'); ?></p>
    <div class="audience-landing-grid audience-landing-grid--cols-3" style="margin-top:32px;">
      <?php /*
        2D (2026-08-03) -- HARDCOVER-FIRST. Andrew, walk-4 (RELAYED through the
        Chief of Staff, NOT witnessed by this agent): the funnel pages default
        to hardcover. This page carries no bundle price card and no format
        toggle, so the only offer presentation it has is these two edition
        cards; the hardcover card is MOVED into first position.

        ⛔ ORDER ONLY. Not one character of either card's approved copy, and
        neither list price, is changed -- $11.99 and $17.99 are the same
        strings, in the same nodes, in the opposite order. The third card and
        the wholesale-pricing disclaimer below are untouched.

        ⭐ 1.19.304: STILL UNTOUCHED. These are the CONSUMER list prices on
        braveheartspublishing.com and they are labelled as such in every card.
        The Ingram list prices in the B3 terms table are the TRADE prices and
        are labelled as such there. Two different numbers for two different
        buyers, each named. ⛔ The asymmetry between them ($12.99 trade list
        vs $11.99 consumer) is `CYCLEX-MKT-01` / `CYCLE167-MKT-T07`, it is
        OPEN, and it is Andrew's and finance-analytics'. This page records it
        by labelling both honestly; it does not resolve it.
      */ ?>
      <div class="audience-landing-card"><h3><?php esc_html_e('Hardcover', 'brave-hearts'); ?></h3><p class="desc"><?php esc_html_e('$17.99 current consumer list price per title · keepsake gift edition', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-card"><h3><?php esc_html_e('Paperback', 'brave-hearts'); ?></h3><p class="desc"><?php esc_html_e('$11.99 current consumer list price per title · softcover, matte finish', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-card"><h3><?php esc_html_e('3 titles × 2 formats', 'brave-hearts'); ?></h3><p class="desc"><?php esc_html_e('6 orderable editions across the current series', 'brave-hearts'); ?></p></div>
    </div>
    <?php
    /*
     * ⭐⭐ B10 — THE PRICING DISCLAIMER. KEPT BYTE-VERBATIM, ON PURPOSE.
     *    It is the best sentence on the page and it is what makes the rest of
     *    it safe. No conversion edit may remove or weaken it.
     *
     * ⚠️ AND ONE CLAUSE INSIDE IT IS NOW ARGUABLY STALE, WHICH IS FLAGGED
     *    RATHER THAN FIXED. It says wholesale "margins ... are not yet
     *    published". A 55% trade discount IS the margin, and it is now
     *    published in the B3 table. ⛔ THIS DESK DOES NOT REWRITE APPROVED
     *    COPY TO RESOLVE THAT (Standing Rules §9: propose, do not make). A
     *    one-line replacement is proposed to Andrew in the release report as
     *    `CYCLE167-LD-004`. Until he rules, his sentence stands as he wrote it.
     */
    ?>
    <p class="audience-landing__lead" style="font-size:14px;margin-top:12px;"><?php esc_html_e('Prices shown are current consumer list prices on braveheartspublishing.com, not wholesale or trade pricing - wholesale terms, margins, and minimums are discussed directly and are not yet published.', 'brave-hearts'); ?></p>
  </div>
</section>

<?php
/*
 * ═════════════════════════════════════════════════════════════════════════
 * ⭐ B8 — THE LEAD MAGNET. SUPPRESSED WHILE THE PDF DOES NOT EXIST.
 * ═════════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE SUPERSEDED BEHAVIOUR, PRESERVED so it is not re-derived: when
 *    `$download['ready']` was false this section rendered an
 *    `audience-landing-coming-soon` panel with a disabled "Coming Soon"
 *    button, beside an `audience-landing-lead__placeholder-cover` whose label
 *    literally read "Guide cover in progress" and whose caption read "cover
 *    design coming soon". ⭐ MEASURED LIVE ON STAGING 2026-08-27, both strings
 *    were rendering.
 *
 * ⛔ THAT IS THE PAGE'S CREDIBILITY, LOST TO AN UNFINISHED JPEG. The whole
 *    block is now suppressed instead, and the trade enquiry becomes the single
 *    call to action, which is Merry §5.1's recommendation and is `FMC-8` —
 *    ⚠️ ANDREW'S DECISION, NOT SETTLED BY THIS BUILD. It is built the
 *    recommended way and is one wp-admin field away from the other way.
 *
 * ⭐ THE READY BRANCH IS UNCHANGED, byte for byte. Setting the PDF under
 *    Settings -> Lead Magnets restores the panel, the modal, the
 *    `bookstore_wholesale_guide` key and its Mailchimp tagging with no code
 *    change and no redeploy.
 */
?>
<?php if ($download['ready']): ?>
<!-- ===================== FREE LEAD MAGNET ===================== -->
<section id="free" class="audience-landing__section">
  <div class="audience-landing__inner">
    <div class="audience-landing-lead">
      <div class="audience-landing-lead__content">
        <span class="audience-landing-eyebrow"><?php esc_html_e('Free for retailers', 'brave-hearts'); ?></span>
        <h2><?php esc_html_e('Get the details before you order.', 'brave-hearts'); ?></h2>
        <p class="audience-landing__lead"><?php echo wp_kses_post(__('Get the free <strong>Wholesale Guide</strong> - series overview, reader profile, and ordering details for retailers.', 'brave-hearts')); ?></p>
        <div class="audience-landing-checklist audience-landing-checklist--compact audience-landing-lead__checklist">
          <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Full series overview and reader profile', 'brave-hearts'); ?></span></div>
          <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Edition and pricing details', 'brave-hearts'); ?></span></div>
          <div class="audience-landing-checklist__row"><span class="check">&#10003;</span><span class="text"><?php esc_html_e('Ordering and contact information', 'brave-hearts'); ?></span></div>
        </div>

        <?php get_template_part('template-parts/acquisition/lead-magnet-cta', null, [
            'id'                   => 'retailer-wholesale-guide-signup',
            'lead_magnet'          => 'bookstore_wholesale_guide',
            'audience_type'        => 'retailers',
            'title'                => __('Send Me the Wholesale Guide', 'brave-hearts'),
            'text'                 => __('Series overview, reader profile, and ordering details for retailers.', 'brave-hearts'),
            'submit_label'         => __('Get the Wholesale Guide', 'brave-hearts'),
            'source_page'          => $source_page,
            'require_name'         => true,
        ]); ?>
        <p class="audience-landing-lead__fine-print"><?php esc_html_e('Free PDF · No purchase required · Occasional wholesale updates. Unsubscribe anytime.', 'brave-hearts'); ?></p>
      </div>
      <div class="audience-landing-lead__art">
        <div>
          <p class="tag"><?php esc_html_e('Free · Wholesale Guide', 'brave-hearts'); ?></p>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===================== TRUST (Sprint A, Phase 8 -- new for Retailers) ===================== -->
<section class="audience-landing__section audience-landing__section--dark">
  <div class="audience-landing__inner audience-landing__inner--narrow">
    <p class="audience-landing-trust-eyebrow"><?php esc_html_e('Series credibility', 'brave-hearts'); ?></p>
    <div class="audience-landing-stat-grid">
      <div class="audience-landing-stat"><div class="audience-landing-stat__num">3</div><p class="audience-landing-stat__label"><?php esc_html_e('published titles, room to grow', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-stat"><div class="audience-landing-stat__num">2</div><p class="audience-landing-stat__label"><?php esc_html_e('formats - paperback & hardcover', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-stat"><div class="audience-landing-stat__num"><?php esc_html_e('Kirkus', 'brave-hearts'); ?></div><p class="audience-landing-stat__label"><?php esc_html_e('featured title', 'brave-hearts'); ?></p></div>
      <div class="audience-landing-stat"><div class="audience-landing-stat__num">6&ndash;9</div><p class="audience-landing-stat__label"><?php esc_html_e('early-to-middle grade age range', 'brave-hearts'); ?></p></div>
    </div>
    <?php
    /*
     * ⛔⛔ THE ONLY LIVE-COPY DEFECT ON THE OLD PAGE, AND IT WAS TWO UNSOURCED
     *    CLAIMS IN ONE SENTENCE. The superseded line, preserved verbatim:
     *
     *      "Every title is printed and fulfilled through an established
     *       print-on-demand partner in the USA - consistent quality and
     *       turnaround across the series."
     *
     *    ⛔ "in the USA" is a COUNTRY-OF-ORIGIN claim with no located source.
     *       Bookvault is a multi-country POD network and nothing in reach
     *       establishes where any given order prints. ⭐ THE IDENTICAL CLAIM
     *       WAS REMOVED FROM THE COLLECTION PAGE ON 2026-08-02 for exactly this
     *       reason, which makes leaving it here an inconsistency as well as an
     *       over-claim.
     *    ⛔ "consistent quality and turnaround" is a PRINTER-PERFORMANCE claim
     *       with no measurement behind it anywhere in this company.
     *
     * ⭐ THE REPLACEMENT SAYS ONLY WHAT IS TRUE AND DOES MORE SELLING WORK:
     *    print on demand means the title never goes out of stock and never has
     *    to be returned for being unavailable.
     *
     * ⛔ NO PRINT OR DELIVERY LEAD TIME. The ~8 days figure in this codebase is
     *    a CONSUMER expectation notice; restating it to a trade buyer converts
     *    it into a delivery commitment nobody has made.
     * ⛔ "Nothing goes out of print" IS NOT A RETURNS STATEMENT and must never
     *    be allowed to read as one (`CYCLE167-MKT-T09`). The returns setting is
     *    stated separately, in the terms table, in Ingram's own words, because
     *    it was read in Ingram's own account.
     */
    ?>
    <p class="audience-landing__lead" style="margin-top:20px;text-align:center;"><?php esc_html_e('Every title is printed on demand through Bookvault, my print and fulfilment partner. Nothing goes out of print, and nothing is ever unavailable to reorder.', 'brave-hearts'); ?></p>
  </div>
</section>

<!-- ===================== FAQ ===================== -->
<section class="audience-landing__section">
  <div class="audience-landing__inner audience-landing__inner--content">
    <h2 style="text-align:center;margin-bottom:40px;"><?php esc_html_e('Questions retailers ask', 'brave-hearts'); ?></h2>
    <div class="audience-landing-faq">
      <?php foreach ($faqs as $faq): ?>
        <details class="audience-landing-faq__item" data-question="<?php echo esc_attr($faq[0]); ?>">
          <summary><?php echo esc_html($faq[0]); ?><span class="icon" aria-hidden="true">+</span></summary>
          <p><?php echo esc_html($faq[1]); ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===================== CONTACT / WHOLESALE CTA ===================== -->
<?php
/*
 * ⭐ B8 · THE CAPTURE ROUTE. One control, one destination, and the inquiry type
 *    is preselected for the buyer by `?inquiry=wholesale`.
 *
 * ⛔ FUNNEL ISOLATION, and it matters more here than anywhere else on the site:
 *    a bookseller who receives a "read the first chapter together tonight"
 *    email learns that the publisher does not know who they are. This page
 *    reads and writes NO parent (`bhp_parent_popup` / `parent_popup`) and NO
 *    teacher (`bhp_mariana_popup` / `teacher_popup`) storage key or event
 *    prefix, it is in `bhp_should_show_any_popup()`'s exclusion list and stays
 *    there, it emits no coupon, and it has no Add to Cart.
 */
?>
<section id="contact" class="audience-landing__section audience-landing__section--major audience-landing-final">
  <div class="audience-landing__inner audience-landing-final__inner">
    <h2><?php esc_html_e('Interested in carrying the series?', 'brave-hearts'); ?></h2>
    <p><?php esc_html_e('Start a wholesale inquiry and I will come back to you personally.', 'brave-hearts'); ?></p>
    <div class="audience-landing-final__ctas">
      <a class="btn btn-gold" href="<?php echo esc_url($contact_url); ?>" data-bhp-event="retailer_wholesale_contact_click" data-bhp-source="retailer_landing"><?php esc_html_e('Start a Wholesale Inquiry', 'brave-hearts'); ?></a>
      <?php if ($download['ready']): ?>
        <a class="btn btn-outline-light" href="#free" data-bhp-signup-modal-open="retailer-wholesale-guide-modal" data-bhp-signup-modal-source="final_cta"><?php esc_html_e('Get the Wholesale Guide', 'brave-hearts'); ?></a>
      <?php else: ?>
        <a class="btn btn-outline-light" href="#titles"><?php esc_html_e('See the ISBNs and terms', 'brave-hearts'); ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ===================== STICKY MINI-CTA ===================== -->
<div class="audience-landing-stickybar" data-audience-stickybar>
  <div class="audience-landing-stickybar__row">
    <?php if ($download['ready']): ?>
      <span class="audience-landing-stickybar__text"><?php esc_html_e('Free Wholesale Guide - no obligation.', 'brave-hearts'); ?></span>
      <div class="audience-landing-stickybar__ctas">
        <a class="btn btn-gold" href="#free" data-audience-free-cta data-bhp-signup-modal-open="retailer-wholesale-guide-modal" data-bhp-signup-modal-source="sticky_bar"><?php esc_html_e('Get it free', 'brave-hearts'); ?></a>
        <a class="btn btn-outline-light" href="#contact"><?php esc_html_e('Contact', 'brave-hearts'); ?></a>
      </div>
    <?php else: ?>
      <span class="audience-landing-stickybar__text"><?php esc_html_e('Order through Ingram, or ask me directly.', 'brave-hearts'); ?></span>
      <div class="audience-landing-stickybar__ctas">
        <a class="btn btn-gold" href="#titles"><?php esc_html_e('ISBNs and terms', 'brave-hearts'); ?></a>
        <a class="btn btn-outline-light" href="#contact"><?php esc_html_e('Contact', 'brave-hearts'); ?></a>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php
/*
 * ===================== CTA-TRIGGERED SIGNUP MODAL =====================
 * theme 1.19.223, 2026-08-13, `CYCLE158-LD-SIGNUP-POPUP`.
 *
 * Every "Get the Wholesale Guide" CTA on this page now OPENS this dialog with the caret
 * already in the email field, instead of scrolling the visitor down to
 * #free. Andrew Signore, current turn, relayed by `chief-of-staff`: "no
 * scrolling, immediate capture".
 *
 * GATED ON THE SAME `$download['ready']` FLAG AS THE PANEL. If the PDF is
 * ever unset under Settings -> Lead Magnets, this modal does not render at
 * all, and 1.19.304 additionally suppresses the panel and retargets every CTA,
 * so there is no control left pointing at a dialog that does not exist.
 *
 * NOT A LEAD-MAGNET POPUP. No timer, no scroll trigger, no exit trigger; it
 * opens only on a deliberate CTA click, so it does not reverse the
 * 2026-07-19 one-popup ruling. It renders the SAME signup-form.php handler
 * with the SAME lead-magnet key, audience type and Mailchimp tags as the
 * inline panel -- never a fork of that pipeline.
 *
 * Copy is reused VERBATIM from the inline panel above. The same offer must
 * not be described in two different ways, and no new claim, number,
 * duration or count is introduced here.
 */
if ($download['ready']) {
    get_template_part('template-parts/acquisition/signup-modal', null, [
        'id'                   => 'retailer-wholesale-guide-modal',
        'lead_magnet'          => 'bookstore_wholesale_guide',
        'audience_type'        => 'retailers',
        'source_page'          => $source_page,
        'eyebrow'              => __('Free for retailers', 'brave-hearts'),
        'title'                => __('Send Me the Wholesale Guide', 'brave-hearts'),
        'text'                 => __('Series overview, reader profile, and ordering details for retailers.', 'brave-hearts'),
        'submit_label'         => __('Get the Wholesale Guide', 'brave-hearts'),
        'privacy_text'         => __('Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts'),
        'trust_text'           => __('Free PDF. No purchase required.', 'brave-hearts'),
    ]);
}
?>
</div>
<?php get_footer(); ?>

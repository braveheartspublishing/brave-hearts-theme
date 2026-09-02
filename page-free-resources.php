<?php
/**
 * Template Name: Free Resources Hub
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * `/free-resources/` — theme 1.19.301, 2026-08-27, `CYCLE167-LD-FREE-RESOURCES-HUB`
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE FOUNDER'S OWN WORDS FOR WHAT THIS PAGE IS (carrier item 298, read
 *    first-hand by this desk at `Business OS\WORKING-DRAFTS\chief-of-staff\
 *    FOUNDER-VERBATIM-2026-08-05-PRODUCTION-DEPLOY-AUTHORIZATION.md`):
 *    *"a page dedicated to articles and content that help kids read more, have
 *    parents engage with their kids, and give them some free PDFs"*.
 *    ⚠ RELAYED through `chief-of-staff`, which witnessed it. NOT witnessed by this desk,
 *    and therefore not a capability grant and not approval for any gated action.
 *
 * ⭐ AND THE NAV RULING (carrier item 300, in full: *"A"*): the "Expedition
 *    Guides" slot becomes FREE RESOURCES and points here; `/teachers/` keeps its
 *    page, its popup trial and its capture forms and loses only its nav door,
 *    which reopens as the "For Teachers and Librarians" section below.
 *
 * ---------------------------------------------------------------------------
 * ⛔⛔ THE RULE THIS PAGE WAS HARDEST TO KEEP, AND IT IS STILL THE ONE THAT
 *     MATTERS MOST IF YOU EDIT IT: NOTHING HERE IS PADDING.
 * ---------------------------------------------------------------------------
 *
 * ⭐ The `marketing-growth` §23 competitor walk (`CYCLE167-MKT-FREE-RESOURCES-WALK`, read IN
 *    FULL by this desk before a byte of this file was written) verified live, by
 *    href scan on four of our own pages, that WE PUBLISHED ZERO INSTANT-DOWNLOAD
 *    PDFs. Every free thing was email-gated. Our closest analogue, Magic Tree
 *    House, publishes fifteen files with no gate. The walk's recommendation was
 *    therefore to ship the printables section as ONE HONEST LINE and no grid.
 *
 * ⭐⭐ THE FOUNDER THEN CHANGED THE INPUT, AFTER THE WALK. Carrier item 302:
 *     *"Yes, we can put the coloring book page as the first Free PDF, lets also
 *     build a few free PDFs tonight, lets brainstorm and put them in there, not
 *     a fillers, as actually good resources for parents?"*, and item 304:
 *     *"They are free so lets do all 5 as well"*. `design-creative` built all five under
 *     `CYCLE167-DES-FREE-PDFS`, from real and attested material only.
 *
 * ⛔ SO THE SECTION SHIPS WITH REAL FILES — AND THE WALK'S RULE IS NOT WEAKENED
 *    BY THAT, IT IS SATISFIED BY IT. Every card below names a file that exists
 *    on disk and is `file_exists()`-guarded at render time. There is no
 *    placeholder, no "coming soon", no cadence promised and no count claimed.
 *    ⛔ The `/teachers/` resource chips (Lesson Plans, Discussion Guides,
 *    Vocabulary, Maps, Printables, Read-Aloud Resources) are LABELS WITH NO FILE
 *    BEHIND THEM and are deliberately NOT carried onto this page. ⛔ The
 *    coloring BOOKS are PAID products; only the free sample pages appear here,
 *    and the card says so in the words on the sheet itself.
 *
 * ---------------------------------------------------------------------------
 * ⭐ WHAT WAS TAKEN FROM THE WALK, AND FROM WHOM
 * ---------------------------------------------------------------------------
 *   · The in-page JUMP BAR under the hero — Magic Tree House, the walk's rank-1
 *     steal and its single highest-value mobile pattern. At 375 this hub is
 *     otherwise a long scroll with no map.
 *   · The AGE BAND named on the page — Highlights (6-8), Brightly ("Growing
 *     Reader 6-8"). Ours is **6 to 9, never 5 to 9**.
 *   · The capture presented as A SENTENCE WITH A LINK plus one band, never an
 *     interstitial and never a blocker on a card — Scholastic. ⛔ Explicitly NOT
 *     Reach All Readers' per-resource gate: that model works on eighteen
 *     resources with a membership behind them; here it would be a turnstile in
 *     front of files we are giving away.
 *   · UNGATED FILES WITH THE EMAIL ASK BESIDE THEM, not in front of them —
 *     Magic Tree House, the walk's rank-3 pattern, now finally available to us
 *     because the files exist.
 *   · "Free" in the H1 and the title, not in the nav — Scholastic, Highlights.
 *     ⚠ RECORDED AS EVIDENCE AGAINST THE NAV LABEL ANDREW CHOSE (0 of 6 walked
 *     competitors put "Free" in a nav label). His word governs and the label
 *     ships as he said it; the evidence is preserved so he can overrule himself.
 *
 * ---------------------------------------------------------------------------
 * ⛔ COPY RAILS — this is the file someone will edit
 * ---------------------------------------------------------------------------
 *   · Andrew's I-voice (§9.1). NO "we", "us" or "our" in any visible string.
 *   · NO em dash anywhere in customer-facing copy.
 *   · Reading age 6 to 9. NEVER 5 to 9.
 *   · NO review, rating, testimonial, reaction, result, statistic or award.
 *   · NO outcome claim about a child. Say what a thing IS and CONTAINS.
 *   · AMERICAN SPELLING in every customer-facing string — "coloring", never
 *     "colouring".
 *   · NO price literal. This page sells nothing and prices nothing.
 *   · ⛔ NO AFFILIATE LINK OF ANY KIND (§26). This page carries none and imports
 *     none; the K3 affiliate lane is a different workstream.
 *   ⭐ All of the above are ASSERTED, not merely requested, by
 *     `tests/test-cycle167-free-resources.php` §6 against the template's own
 *     translatable literals.
 *
 * ---------------------------------------------------------------------------
 * ⛔ FUNNEL ISOLATION (`.claude/rules/funnels.md`) — the rail most at risk here
 * ---------------------------------------------------------------------------
 * ⛔⛔ THIS IS A PARENT PAGE THAT LINKS TO THE TEACHER PAGE, and that adjacency
 *     is exactly how a teacher magnet walks onto a parent surface by momentum.
 *     `audience_type=educators` and `lead_magnet=teacher_adventure_toolkit`
 *     appear NOWHERE below. The teacher section is A LINK AND NOTHING ELSE: no
 *     educator form, no educator magnet, no teacher storage key, no teacher
 *     analytics prefix, no teacher thank-you path. ⭐ Asserted by the suite.
 *
 * ⭐ THE HUB'S ONE CAPTURE REUSES WHAT IS ALREADY WIRED — the existing
 *    `reluctant_reader_adventure_kit` magnet, the existing `parents_families`
 *    audience, the existing `signup-form.php` handler. No new magnet, no new
 *    list, no new form engine.
 *
 * ⛔⛔ AND IT MINTS NO MAILCHIMP TAG. The `free_resources_hub` context below is
 *     an ANALYTICS identity, not a tag. No `bhp_mailchimp_signup_tags` callback
 *     matches it, so it falls through to the base map in `functions.php` and
 *     resolves to the EXISTING trio ["Reluctant Reader Adventure Kit",
 *     "Audience: Parent/Grandparent", "Source: Parent Landing Page"].
 *     ⭐ VERIFIED BY EXECUTING THE FILTER CHAIN, not by reading it — suite §4.
 *     ⛔ A new tag string in a live audience splits that surface's segment in
 *     two, silently, with no error and no failing test anywhere else. That is a
 *     Mailchimp decision and it is ANDREW'S, not an engineering one.
 *
 * ⭐ THE SECOND CAPTURE ON THIS PAGE IS THE EXISTING SITEWIDE FOOTER BAND, AND
 *    IT IS DELIBERATELY NOT RE-IMPLEMENTED HERE. `bhp_should_show_footer_
 *    capture()` does not exclude this template, so `footer.php` already renders
 *    the identical offer at the foot. The walk's section 8 ("footer band, repeat
 *    of the offer") is therefore satisfied by code that already exists, and a
 *    third form on one page would have split this surface's analytics against
 *    itself for no reader benefit.
 *
 * ---------------------------------------------------------------------------
 * ⚠ WHAT IS STILL ANDREW'S, AND IS NOT DECIDED HERE
 * ---------------------------------------------------------------------------
 *   · The H1 and the `<title>` are the walk's CANDIDATES. Final prose and SEO
 *     copy are ChatGPT's under G-1 and Andrew's under G4. The SEO strings ship
 *     as FALLBACKS ONLY (Rank Math's wp-admin value always wins), so his final
 *     copy needs no code change and no redeploy.
 *   · Each of the five PDFs is gated by HIS PREVIEW. Staging is the surface he
 *     previews them on; the production deploy is where that gate closes.
 *   · The nav POSITION is unchanged. The walk recommends moving the slot above
 *     "Contact" (its open decision 4); nobody has approved that, so it is
 *     reported, not taken.
 *
 * @package brave-hearts
 */

defined('ABSPATH') || exit;

get_header();

if (have_posts()) {
    the_post();
}

$articles  = function_exists('bhp_free_resources_articles') ? bhp_free_resources_articles() : array();
$downloads = function_exists('bhp_free_resources_downloads') ? bhp_free_resources_downloads() : array();
?>

<section class="interior-hero interior-hero--atmospheric free-resources-hero" aria-labelledby="free-resources-title">
  <div class="container container--content">
    <p class="component-heading__eyebrow"><?php esc_html_e('For families with readers ages 6 to 9', 'brave-hearts'); ?></p>
    <h1 id="free-resources-title"><?php esc_html_e('Free Resources for Growing Readers', 'brave-hearts'); ?></h1>
    <?php
    /*
     * ⭐ THE SCHOLASTIC PATTERN: say what is here, then make the ask A SENTENCE
     *    WITH A LINK rather than a form the reader has to get past. The form is
     *    one section down, in the document flow, obstructing nothing.
     */
    ?>
    <p class="text-lead">
      <?php esc_html_e('Printables you can download right now, articles for when a reader stalls, and a free chapter I will send you by email.', 'brave-hearts'); ?>
      <a href="#free-resources-printables"><?php esc_html_e('Start with the printables.', 'brave-hearts'); ?></a>
    </p>
  </div>
</section>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * THE JUMP BAR — Magic Tree House's pattern, the walk's rank-1 steal.
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ FOUR ANCHORS, ONE LINE, NOT STICKY. It is a map, not a second navigation:
 *    it scrolls away with the hero and never covers content on a phone.
 * ⛔ Every anchor here has a matching `id` in this file, and the suite asserts
 *    the PAIRS rather than trusting this comment. A jump bar pointing at a
 *    section that does not render is a dead link the reader blames on us, and
 *    it is invisible to every other test in the repo.
 */
?>
<nav class="free-resources-jump" aria-label="<?php esc_attr_e('Jump to a section', 'brave-hearts'); ?>">
  <div class="container">
    <ul class="free-resources-jump__list">
      <li><a href="#free-resources-printables"><?php esc_html_e('Printables', 'brave-hearts'); ?></a></li>
      <li><a href="#free-resources-start"><?php esc_html_e('Free chapter', 'brave-hearts'); ?></a></li>
      <li><a href="#free-resources-articles"><?php esc_html_e('Articles', 'brave-hearts'); ?></a></li>
      <li><a href="#free-resources-teachers"><?php esc_html_e('For teachers', 'brave-hearts'); ?></a></li>
    </ul>
  </div>
</nav>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * SECTION 1 — THE PRINTABLES. Ungated. Click and it opens.
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THIS SECTION IS THE ONE THING THIS SITE DID NOT HAVE THIS MORNING, and
 *     it is first on the page for that reason: it is the only part a visitor
 *     can use without giving me anything.
 *
 * ⛔ THE LIST IS DATA, NOT MARKUP — `bhp_free_resources_downloads()`. Every row
 *    is `file_exists()`-guarded against the SHIPPED theme, so a file that did
 *    not make it into the ZIP renders NOTHING rather than a button that 404s.
 *    ⛔ IF NOTHING RESOLVES, THE WHOLE SECTION IS ABSENT. A heading over an
 *    empty grid is worse than no heading.
 *
 * ⛔ NO COUNT IS PRINTED. Highlights prints "Displaying 1 - 12 of 46" and the
 *    walk ranks that honesty highly, but a count is a claim that goes stale the
 *    moment a row is added, and the grid already shows the reader how many
 *    there are.
 */
?>
<?php if ($downloads) : ?>
<section id="free-resources-printables" class="section free-resources-printables" aria-labelledby="free-resources-printables-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Printables', 'brave-hearts'); ?></p>
      <h2 id="free-resources-printables-title" class="text-section-title"><?php esc_html_e('Free Printables to Download', 'brave-hearts'); ?></h2>
      <p class="component-heading__text"><?php esc_html_e('No email needed for these. Click one and it opens, print it and use it tonight.', 'brave-hearts'); ?></p>
    </header>
    <div class="grid grid--3 free-resources-printables__grid">
      <?php foreach ($downloads as $dl) : ?>
        <article class="free-resource-card">
          <?php
          /*
           * ⭐⭐ 1.19.303 — THE PAGE-ONE PREVIEW, founder carrier item 311:
           *     "a picture of each one above the box description as well. So
           *      the audience can see what they are getting".
           *
           * ⭐ IT LEADS THE CARD. He asked for it "above the box description";
           *    putting it above the TITLE too is the same instruction carried
           *    to its point — the card's first move becomes showing the thing
           *    rather than naming it, which is what "see what they are getting"
           *    asks for.
           * ⛔ `lazy` + `async`, and intrinsic width/height on every one. Five
           *    tall images below a hero would otherwise be five render-blocking
           *    downloads and five reflows on a phone.
           * ⛔ The whole block is absent when `preview` is empty — the resolver
           *    in `bhp_free_resources_downloads()` empties it unless BOTH
           *    derivatives, both intrinsic dimensions AND the alt text resolved.
           *    Same discipline as the card itself: fail to silence, never to a
           *    broken image.
           */
          ?>
          <?php if (!empty($dl['preview'])) : ?>
            <picture class="free-resource-card__preview">
              <source srcset="<?php echo esc_url($dl['preview']['webp']); ?>" type="image/webp">
              <img src="<?php echo esc_url($dl['preview']['jpg']); ?>"
                   alt="<?php echo esc_attr($dl['preview']['alt']); ?>"
                   width="<?php echo esc_attr($dl['preview']['width']); ?>"
                   height="<?php echo esc_attr($dl['preview']['height']); ?>"
                   loading="lazy" decoding="async">
            </picture>
          <?php endif; ?>
          <h3 class="free-resource-card__title"><?php echo esc_html($dl['title']); ?></h3>
          <p class="free-resource-card__text"><?php echo esc_html($dl['description']); ?></p>
          <?php
          /*
           * ⛔ `download` IS DELIBERATELY ABSENT. Forcing a save is hostile on a
           *    phone, where a parent wants to look before they commit; the file
           *    opens in the browser's own viewer and can still be saved from
           *    there. `target="_blank"` with `rel="noopener"` keeps the hub in
           *    place behind it, which is what makes the grid browsable.
           * ⭐ The type and size are stated on the control. A parent on mobile
           *    data is entitled to know what a link weighs before tapping it.
           */
          ?>
          <a class="btn btn-primary free-resource-card__cta"
             href="<?php echo esc_url($dl['url']); ?>"
             target="_blank" rel="noopener"
             data-bhp-event="free_resource_download"
             data-bhp-resource="<?php echo esc_attr($dl['key']); ?>">
            <?php echo esc_html($dl['cta']); ?>
          </a>
          <p class="free-resource-card__meta"><?php echo esc_html($dl['meta']); ?></p>
          <?php if (!empty($dl['alt_url']) && !empty($dl['alt_label'])) : ?>
            <p class="free-resource-card__alt">
              <a href="<?php echo esc_url($dl['alt_url']); ?>" target="_blank" rel="noopener"
                 data-bhp-event="free_resource_download"
                 data-bhp-resource="<?php echo esc_attr($dl['key']); ?>_alt"><?php echo esc_html($dl['alt_label']); ?></a>
            </p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * SECTION 2 — THE FREE CHAPTER. The hub's ONE capture.
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔ THE HEADLINE, THE SUPPORT SENTENCE AND THE BUTTON ARE THE FOUNDER'S OWN
 *    STRINGS (carrier item 290) AND ARE BYTE-IDENTICAL TO EVERY OTHER PARENT
 *    CAPTURE SURFACE. They are NOT retyped from memory here; they are the same
 *    three strings `test-cycle167-capture-copy.php` pins across the site, and
 *    that suite now pins this file too. Twelve surfaces once described this one
 *    offer twelve different ways; a thirteenth that drifted would re-open the
 *    exact defect 1.19.297 closed.
 *
 * ⭐ THE BRIDGE HALF IS THE HONESTY CONDITION, not a flourish (item 290(b)): the
 *    offer is the CHAPTER, the file that arrives is the KIT, and a surface that
 *    names one without the other is either a broken promise or a bait.
 *
 * ⭐ IT SITS **AFTER** THE UNGATED FILES ON PURPOSE. That is the Magic Tree
 *    House arrangement the walk ranked third: give the files away, put the ask
 *    beside them, never in front of them.
 */
?>
<section id="free-resources-start" class="section section--muted free-resources-start" aria-labelledby="free-resources-start-title">
  <div class="container container--content">
    <header class="component-heading">
      <p class="component-heading__eyebrow"><?php esc_html_e('By email', 'brave-hearts'); ?></p>
      <h2 id="free-resources-start-title" class="text-section-title"><?php esc_html_e('FREE Chapter for Reluctant Readers', 'brave-hearts'); ?></h2>
    </header>
    <p class="free-resources-start__text">
      <?php
      esc_html_e(
          "I'll send you the chapter now, just add your email. It arrives inside my free Reluctant Reader Adventure Kit, along with a printable activity and tips for reading it with a 6 to 9 year old.",
          'brave-hearts'
      );
      ?>
    </p>
    <?php
    get_template_part(
        'template-parts/acquisition/signup-form',
        null,
        array(
            'id'              => 'free-resources-signup-form',
            // ⛔ An ANALYTICS identity, not a Mailchimp tag. See the file header.
            'context'         => 'free_resources_hub',
            'audience_type'   => 'parents_families',
            'lead_magnet'     => 'reluctant_reader_adventure_kit',
            // ⛔ FREE never appears on a button (the walk's teardown pattern).
            'submit_label'    => __('Send me the chapter', 'brave-hearts'),
            'email_label'     => __('Email address', 'brave-hearts'),
            'submit_class'    => 'btn-cta-primary',
            // Reused verbatim from the footer capture and the parent popup so
            // three placements cannot promise three different things.
            'privacy_text'    => __('Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts'),
            'aria_labelledby' => 'free-resources-start-title',
            'class'           => 'free-resources-start__form',
        )
    );
    ?>
    <p class="free-resources-start__more">
      <a href="<?php echo esc_url(home_url('/reluctant-reader-adventure-kit/')); ?>"><?php esc_html_e('See what is inside the Kit', 'brave-hearts'); ?> <span aria-hidden="true">&rarr;</span></a>
    </p>
  </div>
</section>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * SECTION 3 — THE ARTICLES. Every card is a real, published post.
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ THE LIST IS DATA, NOT MARKUP — `bhp_free_resources_articles()` resolves
 *    slugs to PUBLISHED posts and drops anything that does not resolve, so a
 *    post that is unpublished, renamed or absent on an environment leaves NO
 *    empty card and NO dead link, and nobody has to maintain a per-environment
 *    list by hand. ⛔ If nothing resolves, the whole section is absent.
 * ⭐ The cards reuse `template-parts/guides/article-card.php` UNCHANGED.
 */
?>
<?php if ($articles) : ?>
<section id="free-resources-articles" class="section free-resources-articles" aria-labelledby="free-resources-articles-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Articles', 'brave-hearts'); ?></p>
      <h2 id="free-resources-articles-title" class="text-section-title"><?php esc_html_e('Help Your Child Read More', 'brave-hearts'); ?></h2>
      <p class="component-heading__text"><?php esc_html_e('What to try when a reader stalls, what the reading levels actually mean, and what to hand them next.', 'brave-hearts'); ?></p>
    </header>
    <div class="grid grid--3 free-resources-articles__grid">
      <?php foreach ($articles as $article_post) : ?>
        <?php get_template_part('template-parts/guides/article-card', null, array('post' => $article_post)); ?>
      <?php endforeach; ?>
    </div>
    <p class="free-resources-articles__more">
      <a href="<?php echo esc_url(home_url('/blog/')); ?>"><?php esc_html_e('Read every article', 'brave-hearts'); ?> <span aria-hidden="true">&rarr;</span></a>
    </p>
  </div>
</section>
<?php endif; ?>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * SECTION 4 — READ IT TOGETHER TONIGHT. The parent-engagement half of the ask.
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐ LINKS, NOT EMBEDS. `/read-aloud/` is the take-home page behind the QR on
 *    the coloring sheet from a school visit, and it renders commercial offer
 *    modules gated by visit mode (`bhp_offer_is_offerable()`).
 * ⛔ THIS SECTION TRIGGERS NONE OF THAT GATING, deliberately: it renders no
 *    offer module, reads no visit flag and passes no `bhp_visit` parameter. A
 *    parent arriving here sees whatever that page decides to show them when they
 *    get there, which is that page's decision and not this one's.
 * ⛔ NO SCHOOL, DATE, GRADE, TEACHER OR CITY is named — `/read-aloud/` is
 *    school-agnostic by design and this description keeps it that way.
 *
 * ⚠ THE SECOND CARD POINTS AT `/teachers/#family-resources`, NOT AT
 *   `/family-resources/`, AND THE REASON IS A LIVE FINDING: there is NO page
 *   with the slug `family-resources` on staging (verified first-hand,
 *   `get_page_by_path()`), even though `page-family-resources.php` exists in
 *   this theme. `bhp_canonicalize_teacher_menu_items()` already rewrites that
 *   URL to this anchor for exactly the same reason. Linking the template's
 *   nominal URL would have shipped a 404 that no test in this repo would have
 *   caught. ⚠ The orphan template is FLAGGED to the Chief of Staff, not fixed
 *   here — creating or deleting a page is not this workstream's scope.
 */
?>
<section id="free-resources-together" class="section section--muted free-resources-together" aria-labelledby="free-resources-together-title">
  <div class="container">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('Together', 'brave-hearts'); ?></p>
      <h2 id="free-resources-together-title" class="text-section-title"><?php esc_html_e('Read It Together Tonight', 'brave-hearts'); ?></h2>
    </header>
    <div class="grid grid--2 free-resources-together__grid">
      <?php
      get_template_part('template-parts/components/feature-card', null, array(
          'title' => __('The Read-Aloud Page', 'brave-hearts'),
          'text'  => __('The page I send home after a school read-aloud, with the story a child just met and a way to keep going that evening.', 'brave-hearts'),
          'link'  => array('url' => home_url('/read-aloud/'), 'label' => __('Open the read-aloud page', 'brave-hearts')),
      ));
      get_template_part('template-parts/components/feature-card', null, array(
          'title' => __('Bring the Expedition Home', 'brave-hearts'),
          'text'  => __('Ways to keep noticing, questioning and exploring together after the final page.', 'brave-hearts'),
          'link'  => array('url' => home_url('/teachers/#family-resources'), 'label' => __('See the family ideas', 'brave-hearts')),
      ));
      ?>
    </div>
  </div>
</section>

<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * SECTION 5 — FOR TEACHERS AND LIBRARIANS. The nav door that moved in here.
 * ═══════════════════════════════════════════════════════════════════════════
 * ⭐⭐ THIS SECTION IS THE OTHER HALF OF THE FOUNDER'S NAV RULING (item 300).
 *     `/teachers/` gave up its primary-nav slot to this page; his condition was
 *     that the hub carry a PROMINENT teachers link in exchange. So this is a
 *     full section with its own heading and its own buttons, not a footnote and
 *     not one line in a list.
 *
 * ⛔⛔ AND IT IS A LINK. NOTHING ELSE. No educator form, no educator magnet, no
 *     `audience_type=educators`, no `lead_magnet=teacher_adventure_toolkit`, no
 *     teacher storage key, no teacher analytics prefix. `/teachers/` keeps its
 *     own capture forms and its own popup trial — which the founder reinstated
 *     himself at carrier item 295, as a few-weeks trial — and this page must not
 *     compete with either. The suite asserts every one of those absences.
 *
 * ⛔ `/teachers/` IS ALSO THE SITE'S ARTICLE TOPIC-HUB ANCHOR TARGET, WHICH IS
 *    WHY IT IS NEVER RENAMED, REPOINTED OR RETIRED. VERIFIED LIVE on PRODUCTION
 *    post content by this desk, 2026-08-27, read-only: post 56 carries three
 *    `teachers/#reading-growing` links and post 96 carries one. The `marketing-growth` walk
 *    raised this from `docs\CONTENT\BLOG_STATUS.md` and honestly labelled it
 *    READ FROM DOCUMENTATION; this is that claim promoted to observed. ⭐ ONLY
 *    THE NAV DOOR MOVED.
 */
?>
<section id="free-resources-teachers" class="section free-resources-teachers" aria-labelledby="free-resources-teachers-title">
  <div class="container container--content">
    <header class="component-heading component-heading--center">
      <p class="component-heading__eyebrow"><?php esc_html_e('For teachers', 'brave-hearts'); ?></p>
      <h2 id="free-resources-teachers-title" class="text-section-title"><?php esc_html_e('For Teachers and Librarians', 'brave-hearts'); ?></h2>
      <p class="component-heading__text"><?php esc_html_e('Classroom resources, read-aloud support and the Explorer Expedition Guides live on their own page, and it is still there.', 'brave-hearts'); ?></p>
    </header>
    <div class="cluster free-resources-teachers__actions">
      <a class="btn btn-primary" href="<?php echo esc_url(home_url('/teachers/')); ?>"><?php esc_html_e('Open the teacher resources', 'brave-hearts'); ?></a>
      <a class="btn btn-outline" href="<?php echo esc_url(home_url('/author-visits/')); ?>"><?php esc_html_e('Read-alouds and author visits', 'brave-hearts'); ?></a>
    </div>
  </div>
</section>

<?php
/*
 * ⭐ NO SECOND CAPTURE BAND IS RENDERED HERE, AND ITS ABSENCE IS THE DESIGN.
 *    `footer.php` already renders `template-parts/acquisition/footer-capture` on
 *    this template, carrying the identical headline, support sentence and
 *    button. The walk's "footer band, repeat of the offer" is satisfied by code
 *    that already exists.
 */
get_footer();

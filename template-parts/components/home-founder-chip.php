<?php
/**
 * Brave Hearts — the founder chip. Homepage hero, above the fold.
 *
 * CYCLE164-LD-HOMEPAGE-WARMTH (2026-08-18, theme 1.19.241).
 *
 * WHAT THIS IS
 * ------------
 * Move 1 of three on the Homepage Warmth Board (`design-creative` / Legolas,
 * `FD-391`, 2026-08-17): a real photograph of Andrew and a line in his own
 * voice, in the hero, instead of 4,177 px down the page.
 *
 * The board's own measurement, quoted so the reason this exists is legible:
 * Andrew's face first appears at 4,177 px on desktop and 5,371 px (6.4
 * screens) on a phone. The booth first appears at 6,311 px / 8,537 px.
 *
 * ⭐ NO NEW ASSET. The photograph is `assets/images/handoff/founder-and-charlotte.webp`
 *    — the SAME file, with the SAME approved alt text, that
 *    `front-page.php`'s `#first-reader` section has rendered since 2026-08-02
 *    and that /about/ and the Adventure Kit page also use. Nothing was
 *    generated, regenerated, retouched or uploaded.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⚠️⚠️ TWO COPY DECISIONS, MADE DELIBERATELY AND FLAGGED FOR ANDREW.
 *      NEITHER IS SILENT, AND NEITHER SHOULD SURVIVE WITHOUT HIS YES.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 1. THE SPOKEN LINE. The board reads:
 *        "I'm Andrew — ICU nurse, uncle, and the author."
 *    Shipped here as:
 *        "I'm Andrew. ICU nurse, uncle, and the author."
 *    ⭐ ONE CHARACTER CHANGED — the em dash became a full stop. Standing rule
 *       §9.1's rail is "no em dashes" in his copy, and this file follows the
 *       precedent already set in `front-page.php` by the Wave F em-dash purge
 *       of 2026-08-03, which restructured the hero subcopy the same way.
 *    ⚠️ THE SENTENCE ITSELF IS STILL AWAITING ANDREW. The board classes it
 *       "Sourced" — ICU nurse from live /about/ copy, "uncle" from the
 *       homepage's own "Her name is Charlotte. She is my niece.", author from
 *       the covers — but records a live nuance: canon also says he is NOW A
 *       TRAVEL NURSE. A current-tense occupational claim is his to confirm,
 *       and it is in the build report for exactly that.
 *
 * 2. THE ROLE LINE. The board reads "The person who packs your book".
 *    ⛔ THAT LINE IS NOT SHIPPED. The board itself marks it
 *       "NOT VERIFIED — written by me, and it is an operational claim. If
 *       someone else does fulfilment it is simply false."
 *    ⭐ Substituted with "Founder, Brave Hearts Publishing", which is already
 *       live on this page in the `#first-reader` byline. An existing true
 *       string beats a warmer unverified one, every time.
 *
 * ⛔ ONE THING THIS COMPONENT CANNOT DECIDE, AND DOES NOT PRETEND TO.
 *    The photograph shows a real child. The board flags this in its own
 *    words: "Promoting a photograph of a real child to the hero is a
 *    parent's decision, not a designer's. The image is already public on
 *    this site, but at hero scale it is far more prominent. Andrew's call."
 *    It is rendered here at 60 px because that is what the board shows, and
 *    it is raised in the build report. It is not treated as settled.
 *
 * @package brave-hearts
 */

defined('ABSPATH') || exit;

$photo = get_template_directory() . '/assets/images/handoff/founder-and-charlotte.webp';
if (!file_exists($photo)) {
    return; // Fail closed: no chip at all rather than a broken frame in the hero.
}
?>
<div class="home-founder-chip">
  <?php /*
   * `loading="eager"` and `fetchpriority="high"` are deliberate: this sits
   * above the fold in the hero and is 60 px square, so it costs almost
   * nothing and a lazy founder face that pops in late is exactly the
   * cheapness this whole change is trying to remove. The rendered box is
   * 60 x 60 (52 x 52 below 820 px), so `sizes` states 60px and the browser
   * picks the smallest rung it has.
   */ ?>
  <img class="home-founder-chip__photo"
       src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/handoff/founder-and-charlotte.webp'); ?>"
       alt="<?php esc_attr_e('Andrew Signore with Charlotte and a Brave Hearts book', 'brave-hearts'); ?>"
       width="1400" height="1867"
       sizes="60px"
       loading="eager" fetchpriority="high" decoding="async">
  <div class="home-founder-chip__body">
    <p class="home-founder-chip__said">
      <?php
      /*
       * The quotation marks are typographic and are part of the design, not
       * part of the sentence -- they are what makes the line read as spoken
       * rather than as a caption. They are written here rather than in CSS
       * `content` so a screen reader announces the line the same way a
       * sighted reader sees it.
       */
      echo esc_html__('“I’m Andrew. ICU nurse, uncle, and the author.”', 'brave-hearts');
      ?>
    </p>
    <p class="home-founder-chip__role"><?php esc_html_e('Founder, Brave Hearts Publishing', 'brave-hearts'); ?></p>
  </div>
</div>

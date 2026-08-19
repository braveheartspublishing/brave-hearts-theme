<?php
/**
 * Brave Hearts — D8: compact, bottom-anchored consent banner.
 *
 * Capstone fix wave, 2026-08-03.
 *
 * ⛔⛔ THE ONE THING TO UNDERSTAND BEFORE EDITING THIS FILE.
 *
 *     THIS CHANGES POSITION AND SIZE. IT CHANGES NOTHING ELSE.
 *
 *     No button is added, removed, renamed, reordered or re-bound. No
 *     category is pre-checked. No default is altered. Nothing here decides
 *     WHEN consent fires, WHAT is consented to, or WHAT happens on accept,
 *     reject or save. The three buttons remain the plugin's own
 *     `#wpconsent-accept-all`, `#wpconsent-cancel-all` and
 *     `#wpconsent-preferences-all`, with the plugin's own handlers, in the
 *     plugin's own order, carrying the plugin's own labels. The preferences
 *     modal is untouched. `inc/class-bhp-wpconsent-bridge.php`, which is what
 *     actually reads consent state, is untouched.
 *
 *     If a future change here would alter any of that, it is not a styling
 *     change and it is not this file's to make.
 *
 * ---------------------------------------------------------------------------
 * WHY IT IS DONE THIS WAY, AND WHY THE OBVIOUS WAYS DO NOT WORK.
 *
 * WPConsent renders its banner inside an OPEN SHADOW ROOT on
 * `#wpconsent-container`, whose host box measures 0 x 0. Consequences, all
 * verified live rather than assumed:
 *   - A normal stylesheet cannot reach it. Shadow-root style encapsulation
 *     means every selector in `style.css` misses, silently.
 *   - `::part()` is unavailable — the plugin exposes no `part` attributes.
 *   - Light-DOM and `offsetWidth` detection of the banner always fail, which
 *     is already recorded in the conversion-audit skill as a known trap.
 * The one supported route into an OPEN shadow root is to append a `<style>`
 * to it from script. That is what this does, and only that.
 *
 * ---------------------------------------------------------------------------
 * MEASURED BEFORE (staging 1.19.149, 390 x 844, DPR 3, `innerWidth` asserted):
 *
 *   holder            `.wpconsent-banner-holder.wpconsent-banner-long-top`
 *   banner            390 x 285  -> 33.8% of the first screen
 *   message body      360 x 65
 *   three buttons     360 x 45 each, STACKED, 105 / 155 / 205
 *   powered-by row    85 x 15
 *   anchoring         TOP. Hit-testing the centre of the header logo returned
 *                     a DIV, not the IMG: the banner painted OVER the sticky
 *                     site header, so the compass lockup, the whole navigation
 *                     and every page's h1 were covered on first visit.
 *   desktop           only 65px (7.2%) — this was a mobile-only failure, 4.4x
 *                     worse on the phone.
 *   owner corroboration: "goes down all the way to the middle of the everest
 *                     book on mobile. prob 7/10 annoying".
 *
 * TARGET: bottom-anchored, <= 120px at 390px, header never covered.
 *
 * @package brave-hearts
 */

defined('ABSPATH') || exit;

/**
 * Is WPConsent actually present? If it is not, this renders nothing at all —
 * no orphan script, no selectors for a banner that does not exist.
 */
function bhp_consent_banner_compact_active() {
    return function_exists('wpconsent');
}

function bhp_consent_banner_compact_css() {
    return <<<'CSS'
/* Injected by the Brave Hearts theme (D8). Position and size only.

   ⚠ THE POSITIONED ELEMENT IS `.wpconsent-banner`, NOT THE HOLDER.
     Measured: the holder renders 390x0 at y=844 (it is a zero-height
     anchor at the bottom of the viewport) while `.wpconsent-banner`
     itself is the fixed 390x285 panel painted at y=0. A first pass that
     moved the HOLDER changed nothing on screen — the banner stayed on
     top of the header. Both are pinned here, and the banner's own
     `top`/`bottom` is what actually does the work. */
.wpconsent-banner-holder,
.wpconsent-banner-holder.wpconsent-banner-long-top,
.wpconsent-banner-holder.wpconsent-banner-long {
  top: auto !important;
  bottom: 0 !important;
}
.wpconsent-banner {
  box-sizing: border-box;
  position: fixed !important;
  top: auto !important;
  bottom: 0 !important;
  left: 0 !important;
  right: 0 !important;
  width: 100% !important;
  max-height: 112px !important;
  padding: 8px 12px 6px !important;
  border-top: 1px solid rgba(217, 164, 95, .45);
  border-radius: 0 !important;
  box-shadow: 0 -8px 24px rgba(0, 0, 0, .18);
}
.wpconsent-banner-body {
  margin: 0 0 6px !important;
  padding: 0 !important;
}
.wpconsent-banner-message,
.wpconsent-banner-message p {
  margin: 0 !important;
  font-size: 11.5px !important;
  line-height: 1.3 !important;
  /* Two lines, then clipped. The full text stays in the DOM and is still
     read by assistive technology; only the visual box is bounded. */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
/* The three buttons go from a 45px stack to one 36px row. Same three
   buttons, same order, same labels, same handlers. */
.wpconsent-banner-footer {
  display: flex !important;
  flex-direction: row !important;
  flex-wrap: nowrap;
  align-items: stretch;
  gap: 6px !important;
  margin: 0 !important;
}
.wpconsent-banner-footer .wpconsent-banner-button {
  flex: 1 1 0;
  min-width: 0;
  margin: 0 !important;
  padding: 0 6px !important;
  height: 34px !important;
  min-height: 34px !important;
  line-height: 1.15 !important;
  font-size: 11.5px !important;
  white-space: normal;
  overflow-wrap: anywhere;
}
.wpconsent-banner-close {
  top: 6px !important;
  right: 8px !important;
}
.wpconsent-powered-by {
  margin: 2px 0 0 !important;
  transform: scale(.72);
  transform-origin: left center;
  opacity: .6;
}
@media (min-width: 782px) {
  /* Desktop already measured only 65px TALL and was never a HEIGHT defect. It
     keeps the one-row treatment for consistency and is capped lower. */
  /* ⛔ 1.19.266 — A 104px CAP WAS TRIED HERE AND WITHDRAWN. RECORDED SO IT IS
     NOT RE-PROPOSED. The reasoning was "a 44px button plus 10/10 padding is
     64px and a wrapped two-line message could reach ~90px, so 92 is tight".
     The arithmetic is right and the conclusion was wrong: 64px and ~90px are
     both UNDER 92, so nothing clips, and raising the cap would have broken
     `tests/test-consent-banner-desktop-layout.php` §6.10 — an assertion whose
     entire job is to prove the desktop block still owns its own height.
     Changing another suite's expectation to accommodate a change that was not
     needed is how a guard rail becomes a formality. 92px stands. */
  .wpconsent-banner { max-height: 92px !important; }

  /* ⛔⛔ THE 1.19.186 CORRECTION — READ THIS BEFORE TOUCHING THE THREE RULES BELOW.

     The defect it fixes (OBSERVED live on production AND staging 2026-08-05,
     headless Chrome, `window.innerWidth` asserted 1280, WPConsent Free 1.1.7):

         Accept All          x 616 -> 983    on screen
         Reject Nonessential x 989 -> 1357   CLIPPED at the 1265px banner edge
         Manage Preferences  x 1363 -> 1730  ENTIRELY OFF SCREEN

     Every desktop visitor could Accept and could NOT Reject or Manage
     Preferences. Mobile was never affected (measured 3 x 118px at 390px).

     WHY IT HAPPENED — the mechanism, so it is not re-derived:

     1. The plugin's own stylesheet sets `.wpconsent-banner-button { width: 100% }`
        because in ITS layout the footer is a COLUMN
        (`.wpconsent-banner-long ... .wpconsent-banner-footer { flex-direction: column }`,
        and that rule lives inside the plugin's `@media (max-width: 767px)`).
        A stacked button at `width: 100%` is correct.
     2. This file turns that footer into a ROW. In a row, `width: 100%` means
        "as wide as the whole footer" — for EACH of the three buttons.
     3. The percentage is resolved AFTER flex-shrink. The footer is itself a
        shrinkable item in the banner row, so it shrank to 367.5px; each button
        then took 367.5px; 3 x 367.5 + gaps = 1114px laid out from x=616.
     4. The mobile rule above survives this because `flex: 1 1 0` sets
        flex-basis 0, which overrides `width` for the main axis. The old
        desktop `flex: 0 0 auto` restores flex-basis: auto — i.e. it hands
        control straight back to the plugin's `width: 100%`. That is the line
        that broke it, and it broke it only above 782px.

     THE FIX IS SIZING ONLY. No button is added, removed, renamed, reordered or
     re-bound; no handler, default, category or consent semantic is touched. */
  .wpconsent-banner-footer .wpconsent-banner-button {
    flex: 0 0 auto;
    /* Kills the inherited percentage. This is the load-bearing declaration. */
    width: auto !important;
    min-width: 0;
    padding: 0 16px !important;
    /* Labels stay on one line inside the fixed 34px box; wrapping would clip
       them instead of the box growing. */
    white-space: nowrap;
  }
  /* The footer must not shrink below its three buttons, or they overflow it
     again — just less visibly. It has no `width` of its own above 767px, so
     `flex: 0 0 auto` resolves to the buttons' own max-content width. */
  .wpconsent-banner-footer {
    flex: 0 0 auto !important;
    width: auto !important;
  }
  /* ...which means the MESSAGE absorbs a narrow desktop viewport instead. It is
     already clamped to two lines, so it degrades gracefully; a button cannot. */
  .wpconsent-banner-body {
    min-width: 0 !important;
  }
}

/* @@@@ THE 1.19.249 COMPACT BAR ("slim the banner") -- READ THIS BEFORE
      TOUCHING ANY DECLARATION BELOW.

   * POSITION AND SIZE ONLY, exactly like the rest of this file. No button is
     added, removed, renamed, reordered or re-bound. All three of the plugin's
     own controls -- #wpconsent-accept-all, #wpconsent-cancel-all and
     #wpconsent-preferences-all -- stay VISIBLE, stay the same size as each
     other, and keep the plugin's own labels and handlers. Reject is exactly as
     easy to reach as Accept, which is a compliance property and not a styling
     preference. The close button stays. Nothing here decides WHEN consent
     fires, WHAT is consented to, or WHAT happens on accept, reject or save.

   ---------------------------------------------------------------------------
   THE DEFECT, MEASURED on the DEPLOYED 1.19.248 build in headless Chrome,
   staging home page, window.innerWidth/innerHeight asserted 390 x 664, cookies
   cleared so the banner actually renders:

       banner    390 x 112, top 552 -> bottom 664
       hero CTA  "Open the book. Read the first pages free"
                 top 591.6 -> bottom 656.7, centre (195, 624.1)
       document.elementFromPoint(195, 624.1)
                 -> DIV#wpconsent-container    <-- THE BANNER, NOT THE CTA

   i.e. the consent bar was eating the tap on the homepage's primary call to
   action on a 664px-tall phone. PASS3 lifted that CTA onto the first screen;
   the first screen's bottom 112px belonged to the banner.

   ! WHY THE PLUGIN'S OWN LAYOUT SETTING CANNOT FIX THIS, so it is not retried:
     wpconsent_settings.banner_layout / banner_position were set to "floating" /
     "right-bottom" on staging during the 1.19.248 pass and were VERIFIED INERT
     at both 390 and 1440. The holder classes really do change
     (wpconsent-banner-floating wpconsent-banner-floating-right-bottom was
     observed on it), but the base block ABOVE in this very file pins
     .wpconsent-banner to position: fixed; left: 0; right: 0; width: 100%;
     bottom: 0 with !important, which outranks every plugin layout. The theme
     owns this banner's geometry, so the theme is where it is fixed. Those two
     options were reverted afterwards, since they changed nothing and an inert
     setting is a false lead for the next reader.

   ---------------------------------------------------------------------------
   ** THE HEIGHT IS NOT A TASTE DECISION -- IT IS FORCED BY TWO MEASURED
      NUMBERS, AND THE ARITHMETIC IS RECORDED SO IT IS NOT RE-DERIVED OR
      "TIDIED" UPWARDS LATER.

      The banner is bottom-anchored, so its bottom edge IS the viewport bottom:
      664. For elementFromPoint at the CTA centre (y = 624.1) to return the CTA,
      the banner's top edge must sit BELOW 624.1, so:

          max banner height = 664 - 624.1 = 39.9px

      THAT IS A CEILING, NOT A TARGET. A 48px bar still covers the CTA centre
      (its top would be 616). Anything >= 39.9px fails the acceptance test.
      36px is used here, leaving 3.9px of clearance so that font loading,
      subpixel rounding and device pixel ratio cannot flip the result.

    ! THE 40px HIT-AREA TARGET IS GEOMETRICALLY UNREACHABLE AT 390 x 664, and
      that is stated plainly rather than quietly missed. A hit area cannot
      extend below the viewport bottom, so the tallest button a phone can carry
      here while leaving the CTA centre clickable is 39.9px. The buttons are
      therefore 36px -- they fill the bar edge to edge with zero block padding,
      which is the most a 36px bar can give them. Raising them to 40px would
      re-cover the CTA. If a >= 40px target is ever required, THE CTA MUST MOVE
      UP; the banner cannot shrink further and stay legible. That is a homepage
      decision, not this file's.

   ---------------------------------------------------------------------------
   THE LAYOUT: one row.   [ message ][ Accept ][ Reject ][ Manage ]      [x]

   - The message is CLAMPED TO ONE LINE and ellipsised. The full sentence stays
     in the DOM, is still announced by assistive technology, and is still
     reachable through Manage Preferences -- only the visual box is bounded. At
     390px the three plugin labels need ~250px of the ~360px row, so the visible
     prose really is a lead-in fragment. That is the documented trade-off, not
     an oversight.
   - .wpconsent-banner-footer needs width: auto. The plugin ships width: 100% on
     the footer inside its own @media (max-width: 767px), and with the footer as
     a flex ITEM that percentage ate the whole row and collapsed the message to
     ZERO width. Measured, not guessed.
   - The buttons keep the flex: 0 0 auto + width: auto !important PAIRING that
     the 1.19.186 fix is built on. flex: 0 0 auto alone restores flex-basis:
     auto and hands sizing back to the plugin's width: 100%, which is the exact
     mechanism that put two of three desktop buttons off screen.
     tests/test-consent-banner-desktop-layout.php section 2.3 scans EVERY button
     rule in this stylesheet for that pairing, including this one.
   - .wpconsent-powered-by is hidden at this breakpoint. It is the plugin's
     attribution LINK, carries no consent function, and there is no room for it
     in a 36px bar. It is untouched on every wider viewport.
   - The close button is re-centred vertically and the bar reserves 22px of
     right padding for it, so it cannot land on top of Manage Preferences.

   ! SCOPE: max-width: 600px only. It cannot reach the >= 782px desktop block
     above, and it cannot reach the 601-781px range; both keep the two-line
     112px treatment unchanged.

   @@@@ 1.19.266 AMENDMENT — READ THIS TOGETHER WITH THE BLOCK ABOVE.
     The rule below is now gated on `(max-height: 699px)` as well as width.
     Its arithmetic is UNCHANGED and still governs the short phone it was
     measured on; it simply no longer reaches the tall phone it was never
     measured on. The readable treatment that takes over above 700px is at
     the end of this stylesheet, with its own measurements. */
@media (max-width: 600px) and (max-height: 699px) {
  .wpconsent-banner {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    gap: 5px !important;
    height: 36px !important;
    max-height: 36px !important;
    min-height: 0 !important;
    padding: 0 22px 0 8px !important;
    overflow: visible !important;
  }
  .wpconsent-banner-body {
    flex: 1 1 0 !important;
    min-width: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
    overflow: hidden !important;
  }
  /* One line, ellipsised. The base block's two-line -webkit-box clamp is
     explicitly undone rather than fought with. */
  .wpconsent-banner-message {
    display: block !important;
    -webkit-line-clamp: none !important;
    min-width: 0 !important;
    overflow: hidden !important;
  }
  .wpconsent-banner-message p {
    display: block !important;
    -webkit-line-clamp: none !important;
    margin: 0 !important;
    font-size: 10.5px !important;
    line-height: 1.2 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
  }
  /* width: auto is the load-bearing declaration here -- see the note above. */
  .wpconsent-banner-footer {
    flex: 0 0 auto !important;
    width: auto !important;
    display: flex !important;
    flex-direction: row !important;
    align-items: stretch !important;
    gap: 3px !important;
    margin: 0 !important;
    height: 36px !important;
  }
  .wpconsent-banner-footer .wpconsent-banner-button {
    flex: 0 0 auto !important;
    /* Kills the plugin's inherited percentage. Never remove this while
       flex: 0 0 auto is present -- that pairing IS the 1.19.186 fix. */
    width: auto !important;
    min-width: 0 !important;
    /* Fills the bar edge to edge: the largest hit box a 36px bar can give. */
    height: 36px !important;
    min-height: 36px !important;
    margin: 0 !important;
    padding: 0 6px !important;
    font-size: 9.5px !important;
    line-height: 1.1 !important;
    white-space: nowrap !important;
  }
  .wpconsent-powered-by {
    display: none !important;
  }
  .wpconsent-banner-close {
    top: 50% !important;
    right: 5px !important;
    transform: translateY(-50%) !important;
  }
}

/* @@@@@@ 1.19.266 (2026-08-19, `CYCLE165-LD-ITERATE-2-AESTHETICS-TOKENS`) —
       THE READABLE BAR. Audit §8a item 8; rubric rows 2, 6 and 7.

   ⛔ STILL POSITION, SIZE AND COLOUR ONLY. No button is added, removed,
      renamed, reordered or re-bound. #wpconsent-accept-all,
      #wpconsent-cancel-all and #wpconsent-preferences-all keep the plugin's
      own labels, order and handlers, and `class-bhp-wpconsent-bridge.php` is
      not involved. Nothing here decides WHEN consent fires or WHAT is
      consented to. If a future change here would alter any of that, it is
      not a styling change and it is not this file's to make.

   THE TWO DEFECTS, MEASURED by `commerce-cx` on staging 1.19.264 at an asserted
   innerWidth of 390 with cookies cleared, on 83 of 83 pages:
     - the message renders as "I use cookies to keep t…" — one line, 10.5px,
       ellipsised, i.e. a consent notice nobody can read;
     - the three controls are the only BLUE and the only SQUARE controls on
       the site (the plugin's own default), 34-36px tall against a 44px
       minimum, in the bottom bar of every page.

   ⛔ WHY THIS DID NOT SIMPLY REPLACE THE 36px BAR. The 1.19.249 block above
      derives a HARD CEILING of 39.9px from two measured numbers at
      390 x 664: the banner is bottom-anchored, so its top edge is
      (viewportHeight - height), and the homepage hero CTA's centre sits at
      y=624.1. Any bar >= 39.9px tall makes `document.elementFromPoint` at
      that point return the banner instead of the CTA — the consent bar eats
      the tap on the primary call to action. That arithmetic is still true and
      is NOT overridden here. Its own closing note says the way out is that
      "THE CTA MUST MOVE UP", and that is a homepage decision, not this
      file's — so it is still not taken.

      What that block never had was the OTHER measurement: the same CTA on a
      390 x 844 phone (the audit's viewport, and the fold every Direction 1
      step was measured against) ends at y~657, leaving 187px of clear space
      below it. A 105px bar there covers nothing.

      So the treatment is chosen by viewport HEIGHT, which is the variable the
      constraint actually depends on:

          <= 699px tall   the 1.19.249 compact 36px bar, unchanged, because a
                          readable bar and a tappable hero CTA cannot both
                          exist in that space and the CTA wins;
          >= 700px tall   this readable bar, ~105px, which clears the CTA by
                          ~80px and is re-measured at deploy.

      ⚠ STATED PLAINLY RATHER THAN QUIETLY MISSED: on a phone under 700px
        tall the message is STILL clamped to one line and the buttons are
        STILL 36px. Item 8's ">=44px, message readable" is met on tall
        phones, on tablets and on desktop, and is NOT met on short phones.
        The blocker is geometric, it is recorded above, and closing it needs
        the homepage hero decision that block already routed to Andrew.

   THE BUTTONS: EQUAL, DELIBERATELY. All three get identical geometry (44px
   min-height, identical padding, identical font size) and identical visual
   weight — ivory fill, navy text, one forest hairline. NOT a primary/
   secondary pair. Reject being exactly as easy to reach and as visually
   loud as Accept is a compliance property, and the safest possible change to
   a consent surface is one that adds no emphasis anywhere. Navy on ivory
   measures 17.7:1; the forest border on ivory measures 11.3:1, well past the
   3:1 that WCAG 1.4.11 asks of a UI boundary. The plugin's blue is replaced,
   not re-weighted.

   ⚠ THE flex: 0 0 auto + width: auto !important PAIRING IS PRESERVED on every
     button rule below. `flex: 0 0 auto` alone restores flex-basis: auto and
     hands sizing back to the plugin's own `width: 100%`, which is the exact
     mechanism that put two of three DESKTOP buttons off screen in 1.19.186.
     `tests/test-consent-banner-desktop-layout.php` §2.3 scans EVERY button
     rule in this stylesheet for that pairing, including these.

   ⚠ CUSTOM PROPERTIES DO CROSS THE SHADOW BOUNDARY. `--color-*` are
     inherited properties declared on `:root`, and `#wpconsent-container` is a
     light-DOM child of <body>, so they inherit into its shadow tree. Literal
     fallbacks are given anyway so that a future token rename degrades to the
     right colour instead of to `initial`. */

/* ---- shared: geometry and palette for every viewport ---------------- */
.wpconsent-banner {
  background: var(--color-ivory, #fffaf0) !important;
  color: var(--color-navy, #071522) !important;
}
.wpconsent-banner-message,
.wpconsent-banner-message p {
  color: var(--color-navy, #071522) !important;
}
.wpconsent-banner-message a {
  color: var(--color-forest, #173f2f) !important;
  text-underline-offset: 2px;
}
.wpconsent-banner-footer .wpconsent-banner-button {
  border-radius: 8px !important;          /* was the plugin's square default */
  border: 1px solid var(--color-forest, #173f2f) !important;
  background: var(--color-ivory, #fffaf0) !important;
  background-image: none !important;
  color: var(--color-navy, #071522) !important;
  font-family: var(--font-ui, Archivo, Arial, sans-serif) !important;
  font-weight: 700 !important;
  text-transform: none !important;
  box-shadow: none !important;
}
.wpconsent-banner-footer .wpconsent-banner-button:hover,
.wpconsent-banner-footer .wpconsent-banner-button:focus-visible {
  background: var(--color-parchment, #f1e7d2) !important;
  color: var(--color-navy, #071522) !important;
}
.wpconsent-banner-footer .wpconsent-banner-button:focus-visible {
  outline: 2px solid var(--color-forest, #173f2f) !important;
  outline-offset: 2px !important;
}
.wpconsent-banner-close {
  color: var(--color-navy, #071522) !important;
}

/* ---- tall phones: two rows, wrapped message, 44px controls ---------- */
@media (max-width: 600px) and (min-height: 700px) {
  .wpconsent-banner {
    display: flex !important;
    flex-direction: column !important;
    align-items: stretch !important;
    gap: 8px !important;
    height: auto !important;
    /* 18 padding + ~35 message (2 lines at 13px/1.35) + 8 gap + 44 buttons
       = ~105px. The cap is 132px so a three-line message on a narrow phone
       grows rather than clips, and 132 still clears the hero CTA's 187px of
       space at 390 x 844 with 55px to spare. */
    max-height: 132px !important;
    min-height: 0 !important;
    padding: 10px 12px 8px !important;
    overflow: visible !important;
  }
  .wpconsent-banner-body {
    flex: 0 1 auto !important;
    min-width: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    display: block !important;
    overflow: hidden !important;
  }
  /* The one-line ellipsis from the 1.19.249 block is undone explicitly
     rather than fought with, and replaced by a two-line box that WRAPS. */
  .wpconsent-banner-message {
    display: block !important;
    -webkit-line-clamp: none !important;
    min-width: 0 !important;
    overflow: hidden !important;
  }
  .wpconsent-banner-message p {
    display: -webkit-box !important;
    -webkit-line-clamp: 2 !important;
    -webkit-box-orient: vertical !important;
    margin: 0 !important;
    font-size: 13px !important;
    line-height: 1.35 !important;
    white-space: normal !important;
    text-overflow: clip !important;
    overflow: hidden !important;
  }
  .wpconsent-banner-footer {
    flex: 0 0 auto !important;
    width: 100% !important;
    display: flex !important;
    flex-direction: row !important;
    align-items: stretch !important;
    gap: 6px !important;
    margin: 0 !important;
    height: auto !important;
  }
  .wpconsent-banner-footer .wpconsent-banner-button {
    /* Three equal columns. `flex: 1 1 0` sets flex-basis 0, which overrides
       the plugin's `width: 100%` on the main axis — this is the mobile half
       of the 1.19.186 fix and is the reason `width: auto` is not needed
       here (see the desktop block, where `flex: 0 0 auto` IS used and
       `width: auto !important` therefore IS required beside it). */
    flex: 1 1 0 !important;
    width: auto !important;
    min-width: 0 !important;
    height: 44px !important;
    min-height: 44px !important;
    margin: 0 !important;
    padding: 0 6px !important;
    font-size: 11.5px !important;
    line-height: 1.15 !important;
    white-space: normal !important;
    overflow-wrap: anywhere !important;
  }
  .wpconsent-powered-by { display: none !important; }
  .wpconsent-banner-close {
    top: 8px !important;
    right: 8px !important;
    transform: none !important;
  }
}

/* ---- 601px and up: the same 44px controls and readable message ------
   ⚠ DELIBERATELY SPLIT IN TWO. The 601-781px band gets its own height cap;
     the >=782px block earlier in this file keeps ownership of the desktop
     cap, because `tests/test-consent-banner-desktop-layout.php` §6.10 exists
     to prove the desktop block still caps its own height and a blanket
     `min-width: 601px` rule declared LATER would silently win that cascade
     and make the assertion meaningless while still passing it. */
@media (min-width: 601px) and (max-width: 781px) {
  .wpconsent-banner {
    max-height: 120px !important;
    padding: 10px 14px !important;
  }
}
@media (min-width: 601px) {
  .wpconsent-banner-message,
  .wpconsent-banner-message p {
    font-size: 13px !important;
    line-height: 1.4 !important;
  }
  .wpconsent-banner-footer .wpconsent-banner-button {
    /* 34px -> 44px. Desktop was never a HEIGHT defect (measured 65px total
       against a full-height viewport), so there is no fold to protect here
       and the WCAG 2.5.5 / Apple HIG / Material floor simply applies. */
    height: 44px !important;
    min-height: 44px !important;
    font-size: 13px !important;
  }
}
CSS;
}

/**
 * Inline, in the footer, with no dependency on jQuery or on load order.
 *
 * The observer exists because the banner element is created by the plugin's
 * own script at an unknown time; polling stops as soon as the shadow root
 * appears, and gives up after ~10 seconds rather than spinning forever.
 *
 * The body-class mirror is the second half of this fix: a bottom-anchored bar
 * would otherwise sit on top of the sticky buy bars and the floating cart
 * button, which are also bottom-fixed. Those are lifted while the banner is
 * up and returned the moment it is dismissed. That is a layout consequence of
 * moving the bar, handled here rather than left to be discovered.
 */
function bhp_consent_banner_compact_script() {
    if (!bhp_consent_banner_compact_active()) {
        return;
    }
    $css = wp_json_encode(bhp_consent_banner_compact_css());
    ?>
    <script id="bhp-consent-compact">
    (function () {
      var CSS = <?php echo $css; // phpcs:ignore WordPress.Security.EscapeOutput -- json-encoded string literal ?>;
      var tries = 0;
      function styleIt() {
        var host = document.getElementById('wpconsent-container');
        var sr = host && host.shadowRoot;
        if (!sr) { return false; }
        if (!sr.getElementById || !sr.getElementById('bhp-consent-compact-style')) {
          var st = document.createElement('style');
          st.id = 'bhp-consent-compact-style';
          st.textContent = CSS;
          sr.appendChild(st);
        }
        mirror(sr);
        return true;
      }
      function mirror(sr) {
        var holder = sr.querySelector('.wpconsent-banner-holder');
        if (!holder) { return; }
        function sync() {
          var visible = holder.classList.contains('wpconsent-banner-visible');
          document.body.classList.toggle('bhp-consent-banner-visible', visible);
        }
        sync();
        if (typeof MutationObserver === 'function') {
          new MutationObserver(sync).observe(holder, { attributes: true, attributeFilter: ['class', 'style'] });
        }
      }
      /*
       * ⭐ CYCLE144-LD-217 (2026-08-05, theme 1.19.201) — `reveal()` AND THE
       *    FIRST-FRAME RETRY ARE THE CLS FIX. Read the guard block in
       *    `bhp_consent_banner_cls_guard()` before changing either.
       *
       * `reveal()` is called on EVERY exit path — success, exhaustion, and the
       * hard timer below — because the guard style hides the banner until it
       * runs. A path that forgets to call it would suppress a consent banner,
       * which is a compliance failure, not a cosmetic one.
       */
      function reveal() {
        var e = document.documentElement;
        if (e.classList) { e.classList.add('bhp-consent-styled'); }
        else { e.className += ' bhp-consent-styled'; }
      }
      function poll() {
        if (styleIt()) { reveal(); return; }
        if (++tries > 100) { reveal(); return; }
        /*
         * The first ~20 attempts ride requestAnimationFrame rather than a
         * 100 ms timer. The shadow root is created by the plugin's own script
         * at an unknown moment; catching it on the NEXT FRAME instead of up to
         * 100 ms later is the difference between styling the banner before its
         * first paint and styling it after — and "after" is the layout shift.
         */
        if (tries < 20 && typeof window.requestAnimationFrame === 'function') {
          window.requestAnimationFrame(poll);
        } else {
          window.setTimeout(poll, 100);
        }
      }
      /*
       * Hard failsafe, independent of the polling loop: whatever happens above,
       * the banner is revealed within 2 s. The CSS animation in the guard is a
       * SECOND, independent failsafe at the same deadline. Both would have to
       * fail for a banner to stay hidden.
       */
      window.setTimeout(reveal, 2000);
      poll();
    })();
    </script>
    <?php
}
/*
 * ⭐ CYCLE144-LD-217 (2026-08-05, theme 1.19.201) — MOVED FROM `wp_footer` 99.
 *
 * SUPERSEDED LINE, preserved rather than deleted:
 *     add_action('wp_footer', 'bhp_consent_banner_compact_script', 99);
 *
 * From the footer, the polling loop could not even START until the whole
 * document had been parsed, and then waited up to another 100 ms per attempt.
 * The plugin paints its banner well before that. Running from `wp_head` lets
 * the observer be waiting when the shadow root appears instead of arriving
 * after it.
 */
add_action('wp_head', 'bhp_consent_banner_compact_script', 3);

/**
 * The CLS guard: hide the banner until it has been positioned, never longer.
 *
 * ⛔ THIS CHANGES *WHEN* THE PLUGIN'S BANNER BECOMES VISIBLE. IT CHANGES
 *    NOTHING ELSE. No button, label, order, handler, category, default or
 *    consent semantic is touched, and `inc/class-bhp-wpconsent-bridge.php` —
 *    which is what actually reads consent state — is not involved.
 *
 * THE DEFECT, MEASURED rather than reasoned about. Lighthouse 12.8.2,
 * staging home page, mobile emulation, theme 1.19.199:
 *
 *     Cumulative Layout Shift  0.427
 *     largest single shift     0.4167 — `div.wpconsent-banner`
 *
 * i.e. ONE element accounted for 98% of the page's CLS, and it is this banner.
 * The mechanism follows directly from how the compact fix has to work: the
 * plugin paints the banner in its own default TOP-anchored position, and the
 * theme's `position: fixed; bottom: 0` override cannot be applied until the
 * shadow root exists and script can append a `<style>` into it. Between those
 * two frames the banner MOVES the height of the viewport — a textbook layout
 * shift, and a visible flicker for the user quite apart from the score.
 *
 * The fix is to keep the banner invisible across that gap. `visibility`
 * inherits through a shadow boundary, so hiding the 0x0 host hides the panel
 * inside it; and because the panel is `position: fixed`, becoming visible in
 * its final place shifts nothing.
 *
 * ⚠ THE FAILSAFE IS THE POINT, AND IT IS DOUBLE. A consent banner that never
 *   appears is a compliance failure, so this must not depend on script
 *   succeeding:
 *     1. `bhp_consent_banner_compact_script()` calls `reveal()` on every exit
 *        path and unconditionally after 2 s.
 *     2. Independently, the CSS animation below reveals the banner 2 s in with
 *        no script involved at all. `animation` is used rather than
 *        `transition` precisely because it fires without a trigger.
 *   Both would have to fail for the banner to stay hidden.
 *
 * ⛔ IF WPCONSENT IS NOT ACTIVE, NOTHING IS PRINTED — no orphan style for a
 *    container that does not exist.
 */
function bhp_consent_banner_cls_guard() {
    if (!bhp_consent_banner_compact_active()) {
        return;
    }
    ?>
<style id="bhp-consent-cls-guard">#wpconsent-container{visibility:hidden;animation:bhp-consent-reveal 0s linear 2s forwards}@keyframes bhp-consent-reveal{to{visibility:visible}}html.bhp-consent-styled #wpconsent-container{visibility:visible;animation:none}</style>
    <?php
}
add_action('wp_head', 'bhp_consent_banner_cls_guard', 2);

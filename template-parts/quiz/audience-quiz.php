<?php
/**
 * Find Your Adventure — audience-routing quiz for organic traffic.
 *
 * Routes an unknown visitor to one of the 4 launch-critical audience
 * landing pages (Parent/Educator/Gift Buyer/Organization). Retailer is
 * deliberately excluded per Andrew's explicit "do not include Retailer yet"
 * instruction — this quiz never exposes a retailer route.
 *
 * Two-step quiz. ⭐ REVISION B (2026-08-03) SWAPPED THE TWO QUESTIONS' JOBS:
 * Q1 now asks WHAT IS HARD (three route-agnostic answers) and Q2 is the
 * ROUTER (four answers, one per audience). Before that it was the other way
 * round, with Q2 asking a route-specific intent. Both question screens are
 * server-rendered; the config JSON now carries result copy and destinations
 * only. See the P2-2 block further down for the full reasoning.
 *
 * Driven by a data-bhp-quiz-config JSON attribute on the root
 * element — same architecture pattern as the existing popup engine
 * (data-popup-config), so this stays a single reusable component rather
 * than one-off markup. Never captures email inside the quiz itself; the
 * landing page remains the one place lead capture happens.
 *
 * ⛔ SUPERSEDED 2026-07-29 → 2026-08-03, recorded rather than deleted:
 * "Per-answer results (2026-07-29, personalization pass): each Q2 option now
 * carries its own result_title / result_text / cta_label, and may optionally
 * override destination." THOSE PER-ANSWER OPTIONS NO LONGER EXIST. Q2's
 * answers ARE the four audiences under REVISION B, so every result is
 * route-level and there is nothing left to fall back FROM. The twelve
 * per-answer entries were deleted rather than left in place unused.
 *
 * THE AUDIENCE DESTINATIONS ARE UNCHANGED — Frozen Audience Routing
 * Constitution, four audiences, four landing pages, one answer each. The
 * organization route keeps the #contact deep link the retired
 * group-order/partnership answer used to carry.
 *
 * Optional `intro_gate` arg (2026-07-17, homepage quiz-entry section): when
 * true, renders a lead-in card (eyebrow/heading/copy/start button) in place
 * of the quiz's own default header, and keeps Q1 hidden until that button
 * is clicked — same component, same JS/routing/analytics, just an additive
 * entry state so the homepage doesn't show two stacked "find your
 * adventure" headers back to back. Callers that don't pass `intro_gate`
 * (e.g. the [bhp_audience_quiz] shortcode used elsewhere) get the original
 * always-visible behavior, unchanged.
 *
 * Optional `in_modal` arg (2026-07-29): renders the extra "Keep browsing
 * this page" result action, which only makes sense when the quiz is inside
 * the sitewide modal. quiz-modal.js binds it to its own close handler so
 * the visitor is returned to the exact page position the modal opened at.
 * Left unset (homepage / canonical quiz page) the button isn't rendered at
 * all, so no dead control ever ships on a normal page.
 *
 * Optional `entry_location` arg (2026-07-17, sitewide quiz routing): a short
 * slug identifying where this instance was entered from (e.g. 'homepage',
 * 'quiz_page'), forwarded into the quiz config as the `utm_content` value
 * audience-quiz.js appends to the destination CTA link. Defaults to 'quiz'
 * if not supplied so an existing UTM link is never left without a value.
 *
 * Question-screen simplification (2026-07-30): the always-on
 * `.bhp-quiz__header` block — eyebrow "2 QUESTIONS · ABOUT 30 SECONDS",
 * headline "Where Should Your Adventure Begin?" and the "No wrong
 * answers…" lead paragraph — has been removed. It sat above BOTH question
 * screens in every non-intro_gate render (sitewide modal, the canonical
 * /find-your-adventure/ page, the [bhp_audience_quiz] shortcode), costing a
 * measured 195.6px at 1440x900 and 231.3px of a 544px dialog at 320x568 —
 * i.e. the visitor met a promotional header before the question they were
 * asked to answer. The question itself is now the top of the screen.
 *
 * Nothing was lost on the canonical page: page-find-your-adventure.php
 * already renders its own <h1> and intro paragraph directly above this
 * component, so that page previously showed two stacked introductions.
 * The homepage's `intro_gate` lead-in card is a DIFFERENT element
 * (.bhp-quiz__intro) and is deliberately untouched.
 *
 * The root element's id is made unique per render (wp_unique_id) rather
 * than hardcoded, since this template part can now render more than once
 * across the site (homepage + the canonical /find-your-adventure/ page in
 * the same theme, even if never on the same single page) -- avoids ever
 * shipping a duplicate #find-your-adventure id.
 *
 * Optional `id` arg (2026-07-18, audit-fix Change 2): overrides the
 * auto-generated id with a caller-supplied stable one. Needed so a new,
 * earlier homepage module can anchor-link to this exact section
 * (`#find-your-adventure`) reliably -- wp_unique_id()'s suffix isn't
 * predictable, so nothing else on the page could reliably target it
 * before this. Left unset, behavior is unchanged from before.
 */
defined('ABSPATH') || exit;

$intro_gate = !empty($args['intro_gate']);
$in_modal = !empty($args['in_modal']);
$entry_location = !empty($args['entry_location']) ? sanitize_key($args['entry_location']) : 'quiz';
$quiz_root_id = !empty($args['id'])
    ? sanitize_html_class($args['id'])
    : (function_exists('wp_unique_id') ? wp_unique_id('find-your-adventure-') : uniqid('find-your-adventure-'));

/* =====================================================================
 * P2-2 (2026-08-03) — QUIZ REVISION B. THE TWO QUESTIONS SWAP JOBS.
 * =====================================================================
 *
 * Approved copy, Andrew's ruling relayed through `chief-of-staff` (NOT
 * witnessed by this agent):
 *   Q1  Business OS `WORKING-DRAFTS\marketing-growth\
 *       DRAFT-2026-08-03-QUIZ-HOOK-OPTIONS.md` §REVISION A, §A2
 *   Q2 + all four result screens
 *       `...-QUIZ-HOOK-OPTIONS-REVISION-B.md` §B1, §B2
 *
 * ── WHAT CHANGED STRUCTURALLY, AND WHY IT IS NOT A COPY SWAP ────────────
 *
 * BEFORE: Q1 asked WHO YOU ARE (four route buttons) and Q2 asked a
 * route-specific intent, built in JavaScript from the chosen route's own
 * `q2_options`. Twelve leaf answers, twelve result variants.
 *
 * AFTER: Q1 asks WHAT IS HARD (three answers, route-agnostic) and Q2 is the
 * router (four answers, one per audience). The audience is genuinely UNKNOWN
 * until Q2, which is the whole point of the redesign, and it has three
 * consequences that had to be handled rather than assumed away:
 *
 *   1. The twelve `q2_options` entries RETIRE. They are deleted, not left
 *      unused — a dead config that still looks live is how a future session
 *      "restores" a screen nobody ships.
 *   2. BOTH question screens are now SERVER-RENDERED. Q2's answers are the
 *      four routes, which PHP already knows, so `audience-quiz.js` no longer
 *      builds any option markup with innerHTML. Fewer moving parts, and the
 *      first two screens work before a single byte of JS has run.
 *   3. `quiz_q1_answer` can no longer carry `quiz_audience` — at Q1 there is
 *      no audience yet. It carries `quiz_pain`. See audience-quiz.js.
 *
 * ── THE ONE PLACE THE COPY DELIBERATELY DECLINES TO SELL ────────────────
 *
 * The ORGANIZATION result promises no download and captures no email,
 * because the Community Reading Kit does not exist yet
 * (`page-audience-organizations.php` renders a hard-coded "Coming soon").
 * Promising a kit on the router screen and then showing "Coming soon" one
 * screen later is the never-invent rule broken inside a single interaction.
 * `offers_signup => false` is what enforces that in code.
 *
 * ⛔ The organization screen SHIPS AS DRAFTED even though the kit has since
 *    been verified to exist. Upgrading it is a daylight decision with
 *    approved wording, not an improvisation at build time. REVISION B §B2
 *    carries the activation-ready variant and its three preconditions.
 *
 * ── Q2's HEADING ────────────────────────────────────────────────────────
 *
 * ⭐ R4 (2026-08-03, theme 1.19.161) — THE LINE IS NOW ANDREW'S OWN WORDING.
 *    His ruling, relayed verbatim through the Chief of Staff and NOT
 *    witnessed first-hand by this agent:
 *
 *      "'Who are you helping? We will match your free next step.' Should be -
 *       'Who are you helping? - Get the free resource made just for you..' or
 *       something to that matter- Free next step sounds wierd."
 *
 *    It ships as: "Who are you helping? Get the free resource made just for
 *    you." — his words, with the punctuation cleaned to house standard: no
 *    stray hyphen after the question mark (the question mark already ends the
 *    clause), one full stop rather than two, and NO em dash anywhere. Not one
 *    word of his phrasing was substituted.
 *
 *    ⛔ IT PROMISES NOTHING NEW. Every one of the four routes that shows a
 *       form offers a real, existing free resource (Adventure Kit, Adventure
 *       Learning Toolkit, Meaningful Gift Guide), and the fourth — the
 *       organization route — says outright on its own result screen that
 *       there is nothing to download yet. "Free resource" is the same promise
 *       "next step" carried, in the noun Andrew asked for.
 *
 *    ⚠️ SUPERSEDED, PRESERVED VERBATIM so it is not re-derived. The block
 *       below stood here until R4 and explains why the shipped line WAS
 *       "next step". Andrew's ruling overrides its reasoning, not its history:
 *
 *         "Andrew liked 'Who are you helping? We will match your free kit.'
 *          It ships as '…your free next step.' — REVISION B option B1-b —
 *          because 'kit' is true of three routes out of four and 'next step'
 *          is true of all four. `next step` is not new wording either: it is
 *          already live and approved inside `$quiz_lead`. One noun, and the
 *          screen stops writing a cheque the next screen cannot cash."
 *
 *    ⚠️ `$quiz_lead` still contains "…the most useful free resource and next
 *       step." It is DELIBERATELY NOT CHANGED: it is separately approved live
 *       copy, it renders only inside the homepage intro card (not on the Q2
 *       screen this ruling is about), and Andrew's sentence names one line.
 *
 *
 * ── SUPERSEDED, RECORDED SO IT IS NOT RE-DERIVED ────────────────────────
 *
 * A6 (2026-08-03) purged four buyer-facing reading-duration claims from this
 * file under Andrew's standing rule ("no time-to-finish or reading-duration
 * claims of any kind"). Those four strings are now gone a second time, as
 * part of the wholesale result rewrite. The rule stands and REVISION B was
 * written under it: no duration claim appears anywhere below.
 *
 * ⛔ STILL NOT TOUCHED, AND DELIBERATELY SO: the teacher/educator
 *    lesson-planning timing in `template-parts/acquisition/mariana-popup.php`
 *    ("free, printable 20-minute classroom guide") is EXEMPT under the same
 *    ruling. It describes how long a LESSON takes, not how long a CHILD
 *    reads, and it is the best-evidenced phrase the company owns. Do not
 *    "complete the purge" by removing it.
 *
 * Also absent from every string below, checked rather than assumed: em
 * dashes, invented reviews/reactions/results/statistics, medical or
 * developmental claims, unconfirmed founder specifics, and any statement
 * about how a child will feel or perform. Reading age is 6 to 9.
 * ===================================================================== */

/**
 * QUESTION 1 — the pain. Route-agnostic: no answer here changes where the
 * visitor is sent, and none of them attributes a deficiency to the child.
 * The analytics values describe events, not children ("loses_momentum",
 * never "gives_up") because they surface in dashboards Andrew reads.
 */
$quiz_q1_options = [
    [ 'label' => __('Getting them to start at all', 'brave-hearts'),   'value' => 'wont_start' ],
    [ 'label' => __('They say books are boring', 'brave-hearts'),      'value' => 'finds_it_boring' ],
    [ 'label' => __('They give up when it gets hard', 'brave-hearts'), 'value' => 'loses_momentum' ],
];

/**
 * QUESTION 2 — the router, and the result screen each answer produces.
 *
 * `label`           the Q2 answer button
 * `result_title`    the result headline, and the focus target on arrival
 * `result_detail`   the supporting body
 * `result_resource` the offer line, rendered under the body, above the form
 * `cta_label`       the destination button (only shown when there is no form)
 * `signup_cta`      the form's submit button
 * `offers_signup`   whether this route promises a resource that EXISTS
 *
 * ⚠️ `offers_signup` is a separate flag on purpose. The engine used to infer
 *    "show the form" from a non-empty resource string. That inference breaks
 *    here: the organization route has a non-empty offer LINE whose whole
 *    content is that there is nothing to download. Inferring a form from it
 *    would render an email capture directly beneath a sentence saying the
 *    resource is not ready — which is the exact broken promise the copy was
 *    written to avoid.
 *
 * Route order is the order the four answers render in.
 */
$quiz_routes = [
    'parent' => [
        'label'           => __('My own reader, ages 6 to 9', 'brave-hearts'),
        'destination'     => home_url('/reluctant-reader-adventure-kit/'),
        'audience'        => 'parents_families',
        'result_title'    => __('Start with one small adventure.', 'brave-hearts'),
        'result_detail'   => __('Charlotte and Henry travel to real places on Earth, in short chapters with pictures all the way through. Your reader sets the pace, and there is no wrong pace.', 'brave-hearts'),
        /*
         * ⭐⭐ 1.19.297 (2026-08-27, `CYCLE167-LD-CAPTURE-COPY-APPLY`) — THE
         *     PARENT ROUTE NOW NAMES THE OFFER THE WAY EVERY OTHER PARENT
         *     CAPTURE SURFACE NAMES IT. Founder's pick, carrier item 290.
         *
         * ⛔ **ONLY THE `parent` ROUTE MOVES.** The `educator` and
         *    `organization` routes below are BYTE-UNCHANGED, and that is a hard
         *    rule rather than a scoping preference: the Adventure Learning
         *    Toolkit is a DIFFERENT lead magnet with a different audience, a
         *    different funnel and a different file, and putting the parent
         *    chapter offer on an educator route would breach funnel isolation
         *    (`.claude/rules/funnels.md`) while also promising a teacher
         *    something the toolkit does not contain.
         *
         * ⛔ `destination`, `audience`, `offers_signup` and the route KEY are
         *    all unchanged. The route key is what
         *    `bhp_get_quiz_signup_routes()` resolves server-side into a
         *    Mailchimp tag; a new key would mint a NEW tag in the live audience.
         *    ⭐ ONLY THE THREE COPY STRINGS BELOW CHANGE.
         *
         * ⚠ `result_title` and `result_detail` are LEFT ALONE. They describe the
         *   BOOKS ("Start with one small adventure.", Charlotte and Henry in
         *   short chapters), not the lead magnet, so they are not part of the
         *   offer-name collision the teardown found. Rewriting them would be
         *   scope creep into approved quiz prose.
         *
         * ⭐ `result_resource` bridges chapter -> Kit (item 290 condition (b)):
         *    it names the chapter as what is sent and the Kit as what it arrives
         *    inside. NO OUTCOME CLAIM. VOICE §9.1.
         */
        'result_resource' => __("Your FREE chapter, sent by email inside my Reluctant Reader Adventure Kit.", 'brave-hearts'),
        'cta_label'       => __('Send me the chapter', 'brave-hearts'),
        'signup_cta'      => __('Send me the chapter', 'brave-hearts'),
        'offers_signup'   => true,
    ],
    'educator' => [
        'label'           => __('My class, library, or homeschool', 'brave-hearts'),
        'destination'     => home_url('/educators-adventure-learning-toolkit/'),
        'audience'        => 'educators',
        'result_title'    => __('Ready to use, with no prep.', 'brave-hearts'),
        // "discussion questions, a science spotlight, and a field journal" is
        // the REAL contents of the real 8-page PDF. An earlier email once
        // promised a reading log the toolkit does not contain; this names only
        // what is in the file.
        'result_detail'   => __('The Adventure Learning Toolkit turns one Charlotte and Henry adventure into discussion questions, a science spotlight, and a field journal. Print it and go.', 'brave-hearts'),
        'result_resource' => __('Your free Adventure Learning Toolkit, sent by email. No cost, no strings.', 'brave-hearts'),
        'cta_label'       => __('Send My Free Toolkit', 'brave-hearts'),
        'signup_cta'      => __('Send My Free Toolkit', 'brave-hearts'),
        'offers_signup'   => true,
    ],
    'gift' => [
        'label'           => __('A gift for a young reader', 'brave-hearts'),
        'destination'     => home_url('/gift-buyers-guide/'),
        'audience'        => 'gift_buyers',
        'result_title'    => __('Give a big place to explore.', 'brave-hearts'),
        // "It looks like an adventure, not like homework" is a claim about how
        // the BOOK looks. It stops exactly where the never-invent rule starts:
        // it says nothing about how a child will react to it.
        'result_detail'   => __('The deepest ocean, the highest mountain, and the Amazon rainforest, each in short chapters with pictures throughout. It looks like an adventure, not like homework.', 'brave-hearts'),
        'result_resource' => __('Your free Meaningful Gift Guide, sent by email.', 'brave-hearts'),
        'cta_label'       => __('Send My Free Gift Guide', 'brave-hearts'),
        'signup_cta'      => __('Send My Free Gift Guide', 'brave-hearts'),
        'offers_signup'   => true,
    ],
    'organization' => [
        'label'           => __('Readers at our organization', 'brave-hearts'),
        // Deep-links straight to the page's own contact section, because a
        // conversation IS this route's next step. This preserves the
        // `#contact` destination the retired `partnership_inquiry` answer
        // used to carry, without needing a fifth answer on the router screen.
        'destination'     => home_url('/organizations-community-reading-kit/#contact'),
        'audience'        => 'organizations',
        'result_title'    => __('Tell us what you are planning.', 'brave-hearts'),
        'result_detail'   => __('Brave Hearts looks at every organization request on its own, whether it is a group book order, a read aloud, a reading event, or a longer partnership.', 'brave-hearts'),
        'result_resource' => __('The free Community Reading Kit is still being finished, so there is nothing to download yet.', 'brave-hearts'),
        'cta_label'       => __('Start the Conversation', 'brave-hearts'),
        'signup_cta'      => '',
        'offers_signup'   => false,
    ],
];

$quiz_config = wp_json_encode([
    'routes'      => $quiz_routes,
    'utmParams'   => [
        'utm_source'   => 'quiz',
        'utm_medium'   => 'onsite',
        'utm_campaign' => 'audience_quiz',
        'utm_content'  => $entry_location,
    ],
    'analyticsOn' => class_exists('BHP_Analytics_Config') && BHP_Analytics_Config::should_render_analytics(),
    'entryLocation' => $entry_location,
    // Same-origin JSON endpoint for the inline result signup. The nonce is
    // per-session; no personal data is ever placed in this config.
    'signupUrl'   => admin_url('admin-ajax.php'),
    'signupNonce' => wp_create_nonce('bhp_quiz_signup'),
]);

// Rendered uppercase by .bhp-quiz-eyebrow's text-transform; written in caps
// here too so the intended copy survives even if that stylesheet fails.
$quiz_eyebrow = __('2 QUESTIONS · ABOUT 30 SECONDS', 'brave-hearts');
$quiz_headline = __('Where Should Your Adventure Begin?', 'brave-hearts');
/*
 * P2-2: the clause order inverts, because the quiz now asks what is hard
 * FIRST and who you are SECOND. The second half is the live approved wording,
 * unchanged, and it is where "next step" already lived before Q2 borrowed it.
 * Renders only inside the homepage `intro_gate` card.
 *
 * "2 QUESTIONS · ABOUT 30 SECONDS" and "Where Should Your Adventure Begin?"
 * are deliberately NOT changed: the format is still two questions, and both
 * strings are live approved copy. Ten of the thirteen promise strings audited
 * in REVISION A §A5 survive this redesign untouched, which is the practical
 * dividend of keeping two questions instead of collapsing to one.
 */
$quiz_lead = __('No wrong answers. Tell us what feels hardest right now and who you are helping, and we will match you with the most useful free resource and next step.', 'brave-hearts');
$quiz_q1_prompt = __('What is the hardest part about getting a child to read?', 'brave-hearts');
$quiz_q2_prompt = __('Who are you helping? Get the free resource made just for you.', 'brave-hearts');
?>
<section class="bhp-quiz" id="<?php echo esc_attr($quiz_root_id); ?>" data-bhp-quiz data-bhp-quiz-config='<?php echo esc_attr($quiz_config); ?>'>
  <div class="bhp-quiz__inner">
    <?php if ($intro_gate): ?>
    <div class="bhp-quiz__intro" data-bhp-quiz-intro>
      <span class="bhp-quiz-eyebrow"><?php echo esc_html($quiz_eyebrow); ?></span>
      <h2 class="bhp-quiz__heading"><?php echo esc_html($quiz_headline); ?></h2>
      <p class="bhp-quiz__lead"><?php echo esc_html($quiz_lead); ?></p>
      <button type="button" class="btn btn-primary bhp-quiz__start" data-bhp-quiz-start><?php esc_html_e('Find My Best Next Step', 'brave-hearts'); ?></button>
      <p class="bhp-quiz__intro-direct">
        <?php
        printf(
            /* translators: 1-4: linked audience names (parents, educators, gift buyers, community reading programs) */
            esc_html__('Prefer to explore directly? Find resources for %1$s, %2$s, %3$s, and %4$s.', 'brave-hearts'),
            '<a href="' . esc_url(home_url('/reluctant-reader-adventure-kit/')) . '">' . esc_html__('parents', 'brave-hearts') . '</a>',
            '<a href="' . esc_url(home_url('/educators-adventure-learning-toolkit/')) . '">' . esc_html__('educators', 'brave-hearts') . '</a>',
            '<a href="' . esc_url(home_url('/gift-buyers-guide/')) . '">' . esc_html__('gift buyers', 'brave-hearts') . '</a>',
            '<a href="' . esc_url(home_url('/organizations-community-reading-kit/')) . '">' . esc_html__('community reading programs', 'brave-hearts') . '</a>'
        );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- printf args are individually esc_url()/esc_html()'d above; format string is esc_html()'d.
        ?>
      </p>
    </div>
    <?php endif; ?>

    <?php /* Transition announcement (2026-07-30, question-screen simplification).
             showStep() in audience-quiz.js writes "Question N of 2. <question>"
             here once per transition, so a screen-reader user is told which
             question they have arrived at without focus being moved anywhere
             unexpected. Deliberately OUTSIDE every step wrapper: a live region
             inside a step would be removed from the accessibility tree by that
             step's `hidden` attribute at exactly the moment it needs to speak. */ ?>
    <p class="screen-reader-text" role="status" aria-live="polite" aria-atomic="true" data-bhp-quiz-announce></p>

    <div class="bhp-quiz__step" data-bhp-quiz-step="1"<?php echo $intro_gate ? ' hidden' : ''; ?>>
      <p class="bhp-quiz__progress"><?php esc_html_e('Question 1 of 2', 'brave-hearts'); ?></p>
      <?php /* A real heading, not a styled <p> (2026-07-30). The visible
               question is the actual title of this screen, so it is the honest
               thing to expose as a heading AND to point the dialog's
               aria-labelledby at — see syncDialogLabel() in audience-quiz.js.
               The id is derived from the already-unique root id, so multiple
               quiz renders can never collide. */ ?>
      <h2 class="bhp-quiz__question" id="<?php echo esc_attr($quiz_root_id); ?>-q1"><?php echo esc_html($quiz_q1_prompt); ?></h2>
      <?php /* P2-2: Q1 is the PAIN question and routes nowhere. Its buttons
               carry `data-bhp-quiz-pain`, not `data-bhp-quiz-route`. The
               rename is deliberate and not cosmetic — a button still named
               "route" here would be the single easiest way for a future
               session to re-wire the audience back onto the wrong question. */ ?>
      <div class="bhp-quiz__options" role="radiogroup" aria-labelledby="<?php echo esc_attr($quiz_root_id); ?>-q1" data-bhp-quiz-q1>
        <?php foreach ($quiz_q1_options as $opt): ?>
          <button type="button" class="bhp-quiz__option" role="radio" aria-checked="false" data-bhp-quiz-pain="<?php echo esc_attr($opt['value']); ?>"><span class="bhp-quiz__option-label"><?php echo esc_html($opt['label']); ?></span></button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="bhp-quiz__step" data-bhp-quiz-step="2" hidden>
      <p class="bhp-quiz__progress"><?php esc_html_e('Question 2 of 2', 'brave-hearts'); ?></p>
      <?php /* P2-2: Q2 is now the ROUTER, and both its prompt and its four
               answers are fixed for every visitor — so both are rendered here
               by PHP instead of being written into the DOM by JavaScript.
               `audience-quiz.js` no longer builds any option markup at all. */ ?>
      <h2 class="bhp-quiz__question" id="<?php echo esc_attr($quiz_root_id); ?>-q2"><?php echo esc_html($quiz_q2_prompt); ?></h2>
      <div class="bhp-quiz__options" role="radiogroup" aria-labelledby="<?php echo esc_attr($quiz_root_id); ?>-q2" data-bhp-quiz-q2>
        <?php foreach ($quiz_routes as $key => $route): ?>
          <button type="button" class="bhp-quiz__option" role="radio" aria-checked="false" data-bhp-quiz-route="<?php echo esc_attr($key); ?>"><span class="bhp-quiz__option-label"><?php echo esc_html($route['label']); ?></span></button>
        <?php endforeach; ?>
      </div>
      <button type="button" class="bhp-quiz__back" data-bhp-quiz-back><?php esc_html_e('← Back', 'brave-hearts'); ?></button>
    </div>

    <div class="bhp-quiz__step bhp-quiz__step--result" data-bhp-quiz-step="result" hidden>
      <p class="bhp-quiz__result-eyebrow"><?php esc_html_e('YOUR BEST NEXT STEP', 'brave-hearts'); ?></p>
      <?php /* P2-2 — THE RESULT SCREEN IS REORDERED TO MATCH THE APPROVED COPY.
               REVISION B §B2 writes each screen as headline, then body, then
               offer line, then CTA, and that is the order a reader needs:
               the invitation, the reason, then what arrives in their inbox.

               The old order was resource-name-first, which worked when the
               "resource" was a short noun phrase acting as the offer title
               ("Free Reluctant Reader Adventure Kit"). REVISION B's offer line
               is a full sentence ("Your free Reluctant Reader Adventure Kit,
               sent by email."), and leading with it would have put the
               delivery mechanic above the invitation.

               So the HEADLINE is now the dominant element and the focus target
               on arrival, and the offer line is a paragraph directly above the
               form it belongs to. There is no longer a hidden-heading case to
               manage: the organization route's offer line is not empty, it
               simply says out loud that there is nothing to download. */ ?>
      <h3 class="bhp-quiz__result-headline" id="<?php echo esc_attr($quiz_root_id); ?>-result-headline" data-bhp-quiz-result-title tabindex="-1"></h3>
      <?php /* Supporting explanation. Set as a plain text node, never innerHTML. */ ?>
      <p class="bhp-quiz__result-detail" data-bhp-quiz-result-text></p>
      <p class="bhp-quiz__result-offer" id="<?php echo esc_attr($quiz_root_id); ?>-result-resource" data-bhp-quiz-result-resource></p>

      <?php /* Inline resource signup (2026-07-30). Shown by audience-quiz.js
               only for routes whose `offers_signup` is true; the organization
               route is false, because the resource it would capture an email
               against does not exist yet. It therefore never renders a form
               or a delivery promise. Submits as JSON to
               the shared server-side signup service — no personal data is
               ever placed in a URL, analytics payload or browser storage. */ ?>
      <form class="bhp-quiz__signup" data-bhp-quiz-signup hidden novalidate>
        <div class="bhp-form-honeypot" aria-hidden="true">
          <label for="<?php echo esc_attr($quiz_root_id); ?>-website"><?php esc_html_e('Website', 'brave-hearts'); ?></label>
          <input id="<?php echo esc_attr($quiz_root_id); ?>-website" name="bhp_website" type="text" tabindex="-1" autocomplete="off">
        </div>

        <div class="bhp-quiz__field">
          <label class="bhp-quiz__label" for="<?php echo esc_attr($quiz_root_id); ?>-fname">
            <?php esc_html_e('First name', 'brave-hearts'); ?>
            <span class="bhp-quiz__optional"><?php esc_html_e('(optional)', 'brave-hearts'); ?></span>
          </label>
          <input class="bhp-quiz__input" id="<?php echo esc_attr($quiz_root_id); ?>-fname" type="text"
                 autocomplete="given-name" data-bhp-quiz-fname>
        </div>

        <div class="bhp-quiz__field">
          <label class="bhp-quiz__label" for="<?php echo esc_attr($quiz_root_id); ?>-email">
            <?php esc_html_e('Email address', 'brave-hearts'); ?>
          </label>
          <input class="bhp-quiz__input" id="<?php echo esc_attr($quiz_root_id); ?>-email" type="email"
                 required aria-required="true" autocomplete="email"
                 aria-describedby="<?php echo esc_attr($quiz_root_id); ?>-signup-error"
                 data-bhp-quiz-email>
        </div>

        <p class="bhp-quiz__consent"><?php esc_html_e('Adventure Club updates and resource news. Unsubscribe anytime.', 'brave-hearts'); ?></p>

        <p class="bhp-quiz__signup-error" id="<?php echo esc_attr($quiz_root_id); ?>-signup-error"
           role="alert" data-bhp-quiz-signup-error hidden></p>

        <button type="submit" class="btn btn-primary bhp-quiz__signup-submit" data-bhp-quiz-signup-submit></button>
      </form>

      <div class="bhp-quiz__result-actions">
        <a class="btn btn-primary" data-bhp-quiz-result-cta data-bhp-event="quiz_destination_click" href="#"></a>
        <?php if ($in_modal): ?>
        <button type="button" class="bhp-quiz__dismiss" data-bhp-quiz-dismiss><?php esc_html_e('Keep browsing this page', 'brave-hearts'); ?></button>
        <?php endif; ?>
        <button type="button" class="bhp-quiz__restart" data-bhp-quiz-restart><?php esc_html_e('Start over', 'brave-hearts'); ?></button>
      </div>
    </div>
  </div>
</section>

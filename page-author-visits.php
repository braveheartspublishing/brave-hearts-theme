<?php
/**
 * Template Name: Author Visits
 * Description: Public list of upcoming school read-aloud visits, with a per-visit order link.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * AUTHOR VISITS — 1.19.233 (2026-08-17, `CYCLE162-LD-VISITS-PAGE`).
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, RELAYED through the Chief of Staff and NOT witnessed by this
 * agent: *"we list the schools dates and times of the read alouds"*.
 *
 * ⛔ THIS FILE IS COPY AND MARKUP ONLY. Every decision — which visits appear,
 *    which carry a button, what each button's URL is, how "today" is worked out
 *    — lives in `inc/author-visits.php`. Read that file first. The split exists
 *    so the logic is testable without rendering a page.
 *
 * ⛔ NO STRUCTURED DATA IS EMITTED HERE, and none may be added without a fresh
 *    decision. `Event` schema for a school read-aloud would be a public claim
 *    about a private classroom's timetable, and `.claude/rules/schema.md`
 *    requires any schema change to be verified in the rendered
 *    `rank-math-schema` block rather than assumed from the source.
 *
 * ⛔ NO PRICE, NO STOCK, NO PRODUCT AND NO COUPON IS NAMED. Prices drift; a
 *    page destined for printed QR codes must not carry one.
 *
 * ⭐ WHY THE DESIGN IS PLAIN. This page will be linked from print. It must
 *    render correctly on a phone, in one column, with no script running: there
 *    is no JavaScript on it at all, no image, no carousel and no popup of its
 *    own. Every class it uses is an existing theme token plus one scoped
 *    `author-visits*` block in `style.css`.
 *
 * ⚠ THE COPY BELOW IS A FIRST DRAFT PENDING ANDREW'S VOICE PASS. It was written
 *   against the constraints in the build brief: founder voice (I/me only, never
 *   "we"), no em dashes, no invented claim, no reading age stated. Nothing on
 *   this page describes a parent, teacher or child reaction, and nothing
 *   promises an outcome.
 *
 * @package BraveHearts
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( have_posts() ) {
	the_post();
}

$bhp_visit_rows = function_exists( 'bhp_author_visits_rows' ) ? bhp_author_visits_rows() : array();
$bhp_kit_url    = home_url( '/reluctant-reader-adventure-kit/' );
?>

<section class="section section--dark author-visits-hero" aria-labelledby="author-visits-title">
  <div class="container container--content">
    <p class="component-heading__eyebrow"><?php esc_html_e( 'Read-alouds in Idaho classrooms', 'brave-hearts' ); ?></p>
    <h1 id="author-visits-title" class="text-hero"><?php esc_html_e( 'Where I Am Reading Next', 'brave-hearts' ); ?></h1>
    <p class="text-lead">
      <?php esc_html_e( 'I visit Idaho classrooms to read aloud. If your child’s school is on this list, you can order books before the visit. I sign each book to your child by name and hand it to them at the visit.', 'brave-hearts' ); ?>
    </p>
  </div>
</section>

<section class="section author-visits-list-section" aria-labelledby="author-visits-list-title">
  <div class="container container--content">
    <header class="component-heading">
      <h2 id="author-visits-list-title" class="text-section-title"><?php esc_html_e( 'Upcoming Visits', 'brave-hearts' ); ?></h2>
    </header>

    <?php if ( empty( $bhp_visit_rows ) ) : ?>

      <?php /* EMPTY STATE. Short, honest, and it promises nothing about when the next date will exist. */ ?>
      <div class="author-visits-empty">
        <p><?php esc_html_e( 'No visits are on the calendar right now.', 'brave-hearts' ); ?></p>
        <p><?php esc_html_e( 'While you wait, the Reluctant Reader Adventure Kit is free to download.', 'brave-hearts' ); ?></p>
        <p class="author-visits-empty__cta">
          <a class="btn btn-primary" href="<?php echo esc_url( $bhp_kit_url ); ?>"><?php esc_html_e( 'Get the free Adventure Kit', 'brave-hearts' ); ?></a>
        </p>
      </div>

    <?php else : ?>

      <ul class="author-visits-list">
        <?php foreach ( $bhp_visit_rows as $bhp_row ) : ?>
          <li class="author-visits-card<?php echo empty( $bhp_row['open'] ) ? ' author-visits-card--closed' : ''; ?>">
            <h3 class="author-visits-card__school"><?php echo esc_html( $bhp_row['school'] ); ?></h3>

            <?php /* Date always. Time ONLY if the registry row carries one — a row with no time renders date-only, never an empty separator. */ ?>
            <p class="author-visits-card__when">
              <span class="author-visits-card__date"><?php echo esc_html( $bhp_row['date_display'] ); ?></span>
              <?php if ( '' !== $bhp_row['time'] ) : ?>
                <span class="author-visits-card__sep" aria-hidden="true">&middot;</span>
                <span class="author-visits-card__time"><?php echo esc_html( $bhp_row['time'] ); ?></span>
              <?php endif; ?>
            </p>

            <?php if ( ! empty( $bhp_row['open'] ) && '' !== $bhp_row['url'] ) : ?>

              <p class="author-visits-card__note">
                <?php
                printf(
                  /* translators: %s: the last date an order may be placed, e.g. "Monday, August 25" */
                  esc_html__( 'Order by %s and I will have your child’s book signed and in my bag on the day.', 'brave-hearts' ),
                  esc_html( bhp_author_visits_format_date( $bhp_row['cutoff'] ) )
                );
                ?>
              </p>
              <p class="author-visits-card__cta">
                <a class="btn btn-primary" href="<?php echo esc_url( $bhp_row['url'] ); ?>"><?php esc_html_e( 'Order signed books for this visit', 'brave-hearts' ); ?></a>
              </p>

            <?php else : ?>

              <?php /* CLOSED. No link at all — not a disabled one. The row stays so a parent reading a QR code still learns the visit is happening. */ ?>
              <p class="author-visits-card__note author-visits-card__note--closed">
                <?php esc_html_e( 'Ordering for this visit has closed.', 'brave-hearts' ); ?>
              </p>

            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>

      <div class="author-visits-how">
        <h2 class="author-visits-how__title"><?php esc_html_e( 'How It Works', 'brave-hearts' ); ?></h2>
        <ol class="author-visits-how__steps">
          <li><?php esc_html_e( 'Find your child’s school above and open the shop from that button.', 'brave-hearts' ); ?></li>
          <li><?php esc_html_e( 'At checkout, choose the free author hand-delivery option and tell me your child’s first name.', 'brave-hearts' ); ?></li>
          <li><?php esc_html_e( 'I sign the books and hand them to your child at the school on the day of the visit.', 'brave-hearts' ); ?></li>
        </ol>
      </div>

    <?php endif; ?>
  </div>
</section>

<?php get_footer(); ?>

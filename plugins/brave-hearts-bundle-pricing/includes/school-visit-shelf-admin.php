<?php
/**
 * Brave Hearts Bundle Pricing — THE SIGNED-COPY SHELF ADMIN SCREEN.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * WHY THIS EXISTS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Andrew Signore, 2026-08-31, founder items 588/589 (⛔ RELAYED to this desk
 * through the Chief of Staff's `CYCLE174-LD-345` dispatch; ⛔ NOT witnessed
 * first-hand by the agent that wrote this file). The dispatch's words:
 *
 *   "Admin edit surface: a simple options page or the existing visits data
 *    structure, whichever the codebase already prefers — document where
 *    Andrew updates counts after restocking."
 *
 * ⭐ UNTIL THIS FILE, THE ONLY WAY TO SET A SHELF COUNT WAS A WP-CLI LINE OVER
 *    SSH. `school-visit-shelf-stock.php` documents it under "THE RESTOCK
 *    COMMAND" and it works, but it requires a terminal, a key and a document
 *    root. ⛔ THAT IS AN OPERATIONAL GAP, NOT A PREFERENCE: the person who
 *    knows how many books are on the shelf is the person holding the books,
 *    and asking him to open an SSH session to record it is how a count goes
 *    stale. A stale count is a false scarcity claim on a storefront.
 *
 * ⛔ THE WP-CLI PATH IS NOT REMOVED AND NOT DEPRECATED. It is the documented
 *    recovery route if this screen is ever unavailable, and the suite and the
 *    shelf file both still describe it. This adds a door; it does not close one.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⛔⛔ WHAT THIS SCREEN IS **NOT**, AND THE DISTINCTION IS THE WHOLE DESIGN
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ IT IS NOT A WOOCOMMERCE INVENTORY SCREEN. `_stock`, `_stock_status`,
 *    `_manage_stock` and `_backorders` are NEVER read-modified or written by
 *    this file, on any environment. It reads and writes exactly ONE option,
 *    `bhp_visit_shelf_stock`, which is this plugin's own visit-layer record of
 *    how many signed paperbacks are physically on Andrew's shelf.
 *    ⭐ `.claude/rules/woocommerce.md` is explicit that out-of-stock is not an
 *    inventory-control mechanism for print-on-demand titles, and that changing
 *    `_stock_status` on any core product needs Andrew's current-turn decision.
 *    Nothing here goes near that gate.
 *
 * ⛔ IT IS NOT A PLACE TO EDIT THE COMMITTED COUNT, AND THERE IS DELIBERATELY
 *    NO FIELD FOR IT. Committed copies are computed live from real orders by
 *    `bhp_visit_shelf_committed()` and are shown READ-ONLY. A stored,
 *    hand-editable committed figure is exactly the drift the shelf file refused
 *    to build: "A stored counter drifts the first time an order is refunded,
 *    cancelled or edited in wp-admin, and nothing tells it."
 *
 * ═══════════════════════════════════════════════════════════════════════
 * ⭐⭐⭐ THE GROSS-VERSUS-NET TRAP, PUT ON THE SCREEN ITSELF
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⛔ THE NUMBER THIS SCREEN ASKS FOR IS A GROSS PHYSICAL COUNT — every copy on
 *    the shelf, INCLUDING copies already spoken for by open orders. The code
 *    subtracts open orders itself. Entering a number that has already had
 *    orders subtracted from it makes the subtraction happen TWICE and closes
 *    every title roughly twice as early as intended.
 *
 * ⭐⭐ THIS IS NOT A HYPOTHETICAL. `CYCLE174-LD-345` escalated exactly this
 *     ambiguity to Andrew: his seed values arrived described as "reserves
 *     already subtracted", which reads as a NET count, while the option's
 *     documented contract is GROSS. On the numbers of the day, entering them as
 *     net-into-a-gross-field would have driven one title to a NEGATIVE
 *     remaining and flipped it to sold-out for real parents at a real visit.
 *
 * ⭐ SO THE WARNING IS RENDERED ON THE SCREEN, BESIDE THE FIELDS, IN THE PLACE
 *   THE MISTAKE WOULD BE MADE — not buried in a docblock a future operator will
 *   never open. The screen also shows the live arithmetic (baseline, committed,
 *   remaining, and what the storefront will actually print) so the consequence
 *   of a number is visible BEFORE it is saved and again after.
 *
 * ═══════════════════════════════════════════════════════════════════════
 * SECURITY
 * ═══════════════════════════════════════════════════════════════════════
 *
 * ⭐ `manage_woocommerce`, matching `BHP_Dashboard_Page` and WooCommerce's own
 *   order screens, so a shop manager can record a restock without being a full
 *   administrator. Capability is checked on BOTH the menu registration and the
 *   save handler — a menu check alone protects the link, not the action.
 * ⭐ Nonce-protected POST, `check_admin_referer`, and a redirect after save so a
 *   refresh cannot re-submit.
 * ⭐ Every count is cast through the same rules `bhp_visit_shelf_baseline()`
 *   enforces on read: non-numeric and negative values are REFUSED rather than
 *   coerced to zero, because a typo silently becoming "0 copies left" is the
 *   worst failure this screen could have.
 *
 * @package brave-hearts-bundle-pricing
 * @since 1.8.79
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The signed-copy shelf admin screen.
 */
class BHP_Visit_Shelf_Admin {

	const CAPABILITY = 'manage_woocommerce';
	const SLUG       = 'bhp-visit-shelf';
	const NONCE      = 'bhp_visit_shelf_save';

	/**
	 * Wire the screen up.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_post_bhp_visit_shelf_save', array( __CLASS__, 'handle_save' ) );
	}

	/**
	 * WooCommerce -> Signed Copies (School Visits).
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Signed Copies (School Visits)', 'brave-hearts' ),
			__( 'Signed Copies', 'brave-hearts' ),
			self::CAPABILITY,
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * ⭐ THE LIVE PICTURE, ASSEMBLED FROM THE SHELF FILE'S OWN FUNCTIONS.
	 *
	 * ⛔ IT RECOMPUTES NOTHING. Every number below comes from the same function
	 *    the storefront asks, so this screen can never show Andrew a figure that
	 *    disagrees with what a parent sees. A second arithmetic here is how an
	 *    admin screen starts quietly lying.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function rows() {
		if ( ! function_exists( 'bhp_visit_shelf_title_slugs' ) ) {
			return array();
		}

		$baseline  = function_exists( 'bhp_visit_shelf_baseline' ) ? bhp_visit_shelf_baseline() : array( 'counts' => array() );
		$committed = function_exists( 'bhp_visit_shelf_committed' ) ? bhp_visit_shelf_committed() : array();
		$rows      = array();

		foreach ( bhp_visit_shelf_title_slugs() as $slug ) {
			$has_base = isset( $baseline['counts'][ $slug ] );
			$base     = $has_base ? (int) $baseline['counts'][ $slug ] : null;
			$comm     = isset( $committed[ $slug ] ) ? (int) $committed[ $slug ] : 0;

			$rows[] = array(
				'slug'      => $slug,
				'label'     => self::label_for( $slug ),
				'baseline'  => $base,
				'committed' => $comm,
				'remaining' => $has_base ? $base - $comm : null,
				'exhausted' => function_exists( 'bhp_visit_shelf_title_is_exhausted' ) ? bhp_visit_shelf_title_is_exhausted( $slug ) : false,
				'closed'    => function_exists( 'bhp_visit_shelf_title_is_closed' ) ? bhp_visit_shelf_title_is_closed( $slug ) : false,
				'counter'   => function_exists( 'bhp_visit_shelf_title_counter' ) ? bhp_visit_shelf_title_counter( $slug ) : null,
			);
		}

		return $rows;
	}

	/**
	 * The catalog's own label for a title, series prefix trimmed for readability.
	 *
	 * @param string $slug Title slug.
	 * @return string
	 */
	private static function label_for( $slug ) {
		$label = $slug;

		if ( function_exists( 'bhp_bundle_catalog' ) ) {
			try {
				$catalog = bhp_bundle_catalog();
				if ( isset( $catalog['paperback'][ $slug ]['label'] ) ) {
					$label = (string) $catalog['paperback'][ $slug ]['label'];
				}
			} catch ( Throwable $e ) {
				$label = $slug; // FAIL SOFT: the slug is ugly but it is not wrong.
			}
		}

		if ( false !== strpos( $label, ': ' ) ) {
			$parts = explode( ': ', $label );
			$label = trim( (string) array_pop( $parts ) );
		}

		return $label;
	}

	/**
	 * ⭐ WHAT THE STOREFRONT WILL ACTUALLY PRINT FOR THIS TITLE, IN WORDS.
	 *
	 * ⛔ IT ASKS THE SHIPPED STRING FUNCTIONS rather than re-typing the copy, so
	 *    a filter Andrew adds to reword the counter shows up here too.
	 *
	 * @param array<string,mixed> $row One row from `rows()`.
	 * @return string
	 */
	private static function storefront_state( $row ) {
		if ( null === $row['baseline'] ) {
			return __( 'Nothing. This title has no count set, so it is never closed and never counted.', 'brave-hearts' );
		}

		if ( $row['closed'] ) {
			return function_exists( 'bhp_visit_shelf_sold_out_label' )
				? bhp_visit_shelf_sold_out_label()
				: __( 'Sold out for the school visit', 'brave-hearts' );
		}

		if ( $row['exhausted'] ) {
			// Exhausted but not closed: backorders are allowed (item 363).
			return function_exists( 'bhp_visit_shelf_backorder_label' )
				? sprintf(
					/* translators: %s: the short backorder label. */
					__( '%s (shelf is at or below the buffer, but ordering stays open)', 'brave-hearts' ),
					bhp_visit_shelf_backorder_label()
				)
				: __( 'Ordering ahead', 'brave-hearts' );
		}

		if ( null !== $row['counter'] ) {
			return function_exists( 'bhp_visit_shelf_counter_label' )
				? bhp_visit_shelf_counter_label( (int) $row['counter'] )
				: (string) $row['counter'];
		}

		return __( 'No number. There are more copies left than the display ceiling, so the storefront stays quiet.', 'brave-hearts' );
	}

	/**
	 * Save handler.
	 *
	 * @return void
	 */
	public static function handle_save() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to update signed-copy counts.', 'brave-hearts' ) );
		}
		check_admin_referer( self::NONCE );

		$slugs   = function_exists( 'bhp_visit_shelf_title_slugs' ) ? bhp_visit_shelf_title_slugs() : array();
		$counts  = array();
		$refused = array();

		foreach ( $slugs as $slug ) {
			$field = 'count_' . $slug;
			if ( ! isset( $_POST[ $field ] ) ) {
				continue;
			}

			$raw = trim( (string) wp_unslash( $_POST[ $field ] ) );

			/*
			 * ⛔ AN EMPTY FIELD MEANS "DO NOT COUNT THIS TITLE", which is a real
			 *    and useful state: an uncounted title is never closed and never
			 *    shows a number. It is NOT the same as zero, and conflating the
			 *    two would silently sell out a title nobody had counted yet.
			 */
			if ( '' === $raw ) {
				continue;
			}

			// ⛔ REFUSE rather than coerce. A typo becoming 0 is the worst outcome.
			if ( ! is_numeric( $raw ) || (int) $raw < 0 || (string) (int) $raw !== ltrim( $raw, '+' ) ) {
				$refused[] = $slug;
				continue;
			}

			$counts[ $slug ] = (int) $raw;
		}

		$as_of = isset( $_POST['as_of'] ) ? trim( (string) wp_unslash( $_POST['as_of'] ) ) : '';
		if ( '' !== $as_of && function_exists( 'bhp_school_visit_is_ymd' ) && ! bhp_school_visit_is_ymd( $as_of ) ) {
			$as_of = ''; // Display only. A bad date never gates anything.
		}

		update_option(
			defined( 'BHP_VISIT_SHELF_OPTION' ) ? BHP_VISIT_SHELF_OPTION : 'bhp_visit_shelf_stock',
			array(
				'as_of'  => $as_of,
				'counts' => $counts,
			)
		);

		/*
		 * ⛔ PURGE THE PAGE CACHE. A saved count that a cached page does not show
		 *    is a false scarcity claim for as long as the cache lives. This is the
		 *    same honesty rail the dispatch names.
		 */
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
		}
		do_action( 'bhp_visit_shelf_counts_updated', $counts, $as_of );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::SLUG,
					'updated' => '1',
					'refused' => $refused ? implode( ',', array_map( 'sanitize_key', $refused ) ) : null,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render the screen.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$rows     = self::rows();
		$baseline = function_exists( 'bhp_visit_shelf_baseline' ) ? bhp_visit_shelf_baseline() : array( 'as_of' => '' );
		$buffer   = defined( 'BHP_VISIT_SHELF_BUFFER' ) ? (int) BHP_VISIT_SHELF_BUFFER : 1;
		$ceiling  = defined( 'BHP_VISIT_SHELF_COUNTER_MAX' ) ? (int) BHP_VISIT_SHELF_COUNTER_MAX : 10;
		$refused  = isset( $_GET['refused'] ) ? array_filter( array_map( 'sanitize_key', explode( ',', (string) wp_unslash( $_GET['refused'] ) ) ) ) : array();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Signed Copies for School Visits', 'brave-hearts' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php esc_html_e( 'Counts saved, and the page cache was purged.', 'brave-hearts' ); ?>
				</p></div>
			<?php endif; ?>

			<?php if ( $refused ) : ?>
				<div class="notice notice-error"><p>
					<?php
					printf(
						/* translators: %s: comma-separated title slugs. */
						esc_html__( 'REFUSED, and not saved, because the value was not a whole number of zero or more: %s. Nothing was guessed on your behalf.', 'brave-hearts' ),
						esc_html( implode( ', ', $refused ) )
					);
					?>
				</p></div>
			<?php endif; ?>

			<div class="notice notice-warning" style="padding:12px 14px;">
				<p style="margin-top:0;"><strong><?php esc_html_e( 'Enter a GROSS count: every signed copy physically on the shelf, including copies already bought but not yet handed over.', 'brave-hearts' ); ?></strong></p>
				<p><?php esc_html_e( 'Do not subtract open orders yourself. This page subtracts them, live, from the orders themselves. If you enter a number that already has orders taken off it, they get taken off twice and a title closes about twice as early as it should.', 'brave-hearts' ); ?></p>
				<p style="margin-bottom:0;"><?php esc_html_e( 'Count the shelf. Type what you counted.', 'brave-hearts' ); ?></p>
			</div>

			<p>
				<?php
				printf(
					/* translators: 1: buffer, 2: display ceiling. */
					esc_html__( 'A title stops being orderable for a visit when it drops to %1$d or fewer left. A live count appears on the storefront only when there are more than %1$d and at most %2$d left, so a well-stocked title shows no number at all.', 'brave-hearts' ),
					(int) $buffer,
					(int) $ceiling
				);
				?>
			</p>

			<?php if ( ! $rows ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'The book catalog is unavailable, so no titles can be listed. Nothing is closed and nothing is counted while this is true.', 'brave-hearts' ); ?></p></div>
			<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="bhp_visit_shelf_save" />
				<?php wp_nonce_field( self::NONCE ); ?>

				<table class="widefat striped" style="max-width:1000px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Title', 'brave-hearts' ); ?></th>
							<th style="width:150px;"><?php esc_html_e( 'On the shelf (gross)', 'brave-hearts' ); ?></th>
							<th style="width:110px;"><?php esc_html_e( 'Already ordered', 'brave-hearts' ); ?></th>
							<th style="width:90px;"><?php esc_html_e( 'Left', 'brave-hearts' ); ?></th>
							<th><?php esc_html_e( 'What the storefront shows a parent', 'brave-hearts' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $row['label'] ); ?></strong><br /><code><?php echo esc_html( $row['slug'] ); ?></code></td>
							<td>
								<input type="number" min="0" step="1" style="width:100px;"
									name="count_<?php echo esc_attr( $row['slug'] ); ?>"
									value="<?php echo ( null === $row['baseline'] ) ? '' : esc_attr( (string) $row['baseline'] ); ?>" />
								<br /><span class="description"><?php esc_html_e( 'blank = not counted', 'brave-hearts' ); ?></span>
							</td>
							<td><?php echo esc_html( (string) $row['committed'] ); ?><br /><span class="description"><?php esc_html_e( 'live, from orders', 'brave-hearts' ); ?></span></td>
							<td><strong><?php echo ( null === $row['remaining'] ) ? '&mdash;' : esc_html( (string) $row['remaining'] ); ?></strong></td>
							<td><?php echo esc_html( self::storefront_state( $row ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<p>
					<label for="bhp-shelf-as-of"><strong><?php esc_html_e( 'Date you counted the shelf', 'brave-hearts' ); ?></strong></label><br />
					<input type="date" id="bhp-shelf-as-of" name="as_of" value="<?php echo esc_attr( (string) $baseline['as_of'] ); ?>" />
					<span class="description"><?php esc_html_e( 'Shown to nobody but you. It is a note to yourself about how fresh the count is.', 'brave-hearts' ); ?></span>
				</p>

				<?php submit_button( __( 'Save counts', 'brave-hearts' ) ); ?>
			</form>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Notes', 'brave-hearts' ); ?></h2>
			<ul style="list-style:disc;margin-left:20px;max-width:820px;">
				<li><?php esc_html_e( 'This page changes no product, price, coupon, shipping or WooCommerce stock setting. It only records how many signed paperbacks are on the shelf for school visits.', 'brave-hearts' ); ?></li>
				<li><?php esc_html_e( 'The Adventure Activity Book is a PDF. It has no shelf and never carries a count.', 'brave-hearts' ); ?></li>
				<li><?php esc_html_e( '"Already ordered" counts paid, not-yet-delivered visit orders. An order counts until it is marked completed, which should happen when the book actually changes hands.', 'brave-hearts' ); ?></li>
				<li><?php esc_html_e( 'Counts can still be set from the command line; that route is unchanged and is the fallback if this page is ever unavailable.', 'brave-hearts' ); ?></li>
			</ul>
		</div>
		<?php
	}
}

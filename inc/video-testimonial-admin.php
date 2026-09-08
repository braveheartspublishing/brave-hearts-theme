<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE VIDEO TESTIMONIAL REVIEW SCREEN. Theme 1.19.395, 2026-09-07,
 * `CYCLE179-LD-BUILD-395-TESTIMONIAL`. Founder seal 1301.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ READ `inc/video-testimonials.php` FIRST.
 *
 * This is the only screen in the system, and it is the one Andrew actually
 * uses. Everything a decision needs is on it: the video, who sent it, the
 * consent record as evidence, the checklist, and the two emails that can go
 * out. ⭐ Nothing here requires him to open a second tab, and that is the
 * design goal: a review queue that needs a spreadsheet beside it does not get
 * used.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ NOTHING ON THIS SCREEN SENDS AN EMAIL BY ITSELF
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * Setting a status does NOT mail anybody. A separate checkbox has to be ticked
 * in the same save, and it clears itself afterwards. ⭐ THAT IS BECAUSE SEAL
 * 1301 PROMISES THE SUBMITTER **ONE** NOTE, and an email that fires on every
 * status change turns one note into four. `_bhp_testimonial_fail_note_sent`
 * and `_bhp_testimonial_pass_note_sent` are recorded so the screen can say, on
 * screen, whether the note has already gone.
 *
 * ⛔ NO COUPON IS CREATED ANYWHERE IN THIS FILE OR IN THIS THEME. Seal 1300
 *    makes coupon creation Andrew's own act on production, and a WooCommerce
 *    coupon record is a WooCommerce configuration mutation, which is an
 *    Andrew gate under Standing Rules 6 and 16.4. The code is TYPED IN here.
 *    On staging the field offers a clearly labelled placeholder.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The staging placeholder reward code.
 *
 * ⛔ IT SAYS WHAT IT IS IN THE STRING ITSELF, so a captured staging email can
 *    never be mistaken for a real one, in QA or in a screenshot pasted into a
 *    report six weeks later.
 */
const BHP_TESTIMONIAL_PLACEHOLDER_CODE = 'STAGING-PLACEHOLDER-NOT-A-REAL-COUPON';

/**
 * Add the review metabox.
 *
 * @return void
 */
function bhp_testimonial_add_metabox() {
	add_meta_box(
		'bhp-testimonial-review',
		__( 'Review this submission', 'brave-hearts' ),
		'bhp_testimonial_render_metabox',
		BHP_TESTIMONIAL_CPT,
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'bhp_testimonial_add_metabox' );

/**
 * Render the review screen.
 *
 * @param WP_Post $post The submission.
 * @return void
 */
function bhp_testimonial_render_metabox( $post ) {
	$id      = (int) $post->ID;
	$status  = bhp_testimonial_get_status( $id );
	$checked = (array) get_post_meta( $id, '_bhp_testimonial_checklist', true );
	$fails   = (array) get_post_meta( $id, '_bhp_testimonial_autofails', true );

	$url   = (string) get_post_meta( $id, '_bhp_testimonial_video_url', true );
	$file  = (string) get_post_meta( $id, '_bhp_testimonial_video_file', true );
	$bytes = (int) get_post_meta( $id, '_bhp_testimonial_video_bytes', true );

	$pass_sent = (string) get_post_meta( $id, '_bhp_testimonial_pass_note_sent', true );
	$fail_sent = (string) get_post_meta( $id, '_bhp_testimonial_fail_note_sent', true );

	wp_nonce_field( 'bhp_testimonial_review_' . $id, 'bhp_testimonial_review_nonce' );
	?>

	<p style="padding:12px;border-left:4px solid #d63638;background:#fcf0f1;margin:0 0 18px;">
		<strong><?php esc_html_e( 'Score only what the recording shows.', 'brave-hearts' ); ?></strong><br>
		<?php echo esc_html( bhp_testimonial_sentiment_notice() ); ?>
	</p>

	<p style="padding:12px;border-left:4px solid #2271b1;background:#f0f6fc;margin:0 0 18px;">
		<?php esc_html_e( 'No child is named anywhere in this system. There is no field for one and none is stored. Do not type one into the title.', 'brave-hearts' ); ?>
	</p>

	<h3><?php esc_html_e( 'The video', 'brave-hearts' ); ?></h3>
	<?php if ( $url ) : ?>
		<p>
			<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html( $url ); ?>
			</a>
			<br><em><?php esc_html_e( 'Submitted as a link. Nothing was uploaded to this server.', 'brave-hearts' ); ?></em>
		</p>
	<?php elseif ( $file ) : ?>
		<?php
		/*
		 * ⭐ PLAYED IN PLACE, NOT DOWNLOADED. The endpoint honours Range
		 *    requests, so this element can be scrubbed, which is what checking
		 *    "the book is visible" and "no other brand on camera" actually
		 *    takes. `preload="metadata"` means opening the queue does not pull
		 *    a hundred megabytes before anybody presses play.
		 */
		?>
		<video controls playsinline preload="metadata" style="max-width:560px;width:100%;height:auto;background:#000;border-radius:4px;">
			<source src="<?php echo esc_url( bhp_testimonial_stream_url( $id ) ); ?>">
		</video>
		<p>
			<a class="button" href="<?php echo esc_url( bhp_testimonial_stream_url( $id ) ); ?>">
				<?php esc_html_e( 'Open the video in a new tab', 'brave-hearts' ); ?>
			</a>
			<br>
			<em>
				<?php
				printf(
					/* translators: %s: file size. */
					esc_html__( 'Uploaded file, %s. It is stored outside the web root and can only be opened from this screen.', 'brave-hearts' ),
					esc_html( size_format( $bytes ) )
				);
				?>
			</em>
		</p>
	<?php else : ?>
		<p><em><?php esc_html_e( 'No video is attached to this submission.', 'brave-hearts' ); ?></em></p>
	<?php endif; ?>

	<h3><?php esc_html_e( 'Who sent it', 'brave-hearts' ); ?></h3>
	<table class="widefat striped" style="max-width:760px;">
		<tbody>
			<tr>
				<th style="width:230px;"><?php esc_html_e( 'Name', 'brave-hearts' ); ?></th>
				<td><?php echo esc_html( (string) get_post_meta( $id, '_bhp_testimonial_name', true ) ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Email', 'brave-hearts' ); ?></th>
				<td><?php echo esc_html( (string) get_post_meta( $id, '_bhp_testimonial_email', true ) ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Submitted', 'brave-hearts' ); ?></th>
				<td><?php echo esc_html( (string) get_post_meta( $id, '_bhp_testimonial_submitted_at', true ) ); ?></td>
			</tr>
		</tbody>
	</table>

	<h3><?php esc_html_e( 'The consent record', 'brave-hearts' ); ?></h3>
	<p><em><?php esc_html_e( 'Recorded at submission and never editable here. This is the evidence that the terms were accepted.', 'brave-hearts' ); ?></em></p>
	<table class="widefat striped" style="max-width:760px;">
		<tbody>
			<tr>
				<th style="width:230px;"><?php esc_html_e( 'Typed signature', 'brave-hearts' ); ?></th>
				<td><?php echo esc_html( (string) get_post_meta( $id, '_bhp_testimonial_consent_name', true ) ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Terms reviewed', 'brave-hearts' ); ?></th>
				<td><?php echo get_post_meta( $id, '_bhp_testimonial_consent_checked', true ) ? esc_html__( 'Yes', 'brave-hearts' ) : esc_html__( 'No', 'brave-hearts' ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Accepted at (UTC)', 'brave-hearts' ); ?></th>
				<td><?php echo esc_html( (string) get_post_meta( $id, '_bhp_testimonial_consent_time', true ) ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Terms version', 'brave-hearts' ); ?></th>
				<td><?php echo esc_html( (string) get_post_meta( $id, '_bhp_testimonial_consent_terms', true ) ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Submitting IP', 'brave-hearts' ); ?></th>
				<td><?php echo esc_html( (string) get_post_meta( $id, '_bhp_testimonial_consent_ip', true ) ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'A child appears', 'brave-hearts' ); ?></th>
				<td><?php echo get_post_meta( $id, '_bhp_testimonial_child_appears', true ) ? esc_html__( 'Yes', 'brave-hearts' ) : esc_html__( 'No', 'brave-hearts' ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Guardian confirmed', 'brave-hearts' ); ?></th>
				<td><?php echo get_post_meta( $id, '_bhp_testimonial_guardian', true ) ? esc_html__( 'Yes', 'brave-hearts' ) : esc_html__( 'No', 'brave-hearts' ); ?></td>
			</tr>
		</tbody>
	</table>

	<h3><?php esc_html_e( 'The checklist', 'brave-hearts' ); ?></h3>
	<?php foreach ( bhp_testimonial_checklist() as $key => $label ) : ?>
		<p style="margin:4px 0;">
			<label>
				<input type="checkbox" name="bhp_testimonial_check[<?php echo esc_attr( $key ); ?>]" value="1"
					<?php checked( ! empty( $checked[ $key ] ) ); ?>>
				<?php echo esc_html( $label ); ?>
			</label>
		</p>
	<?php endforeach; ?>

	<h3><?php esc_html_e( 'Automatic fails', 'brave-hearts' ); ?></h3>
	<p><em><?php esc_html_e( 'Any one of these ends the submission, whatever else is ticked above.', 'brave-hearts' ); ?></em></p>
	<?php foreach ( bhp_testimonial_auto_fails() as $key => $label ) : ?>
		<p style="margin:4px 0;">
			<label>
				<input type="checkbox" name="bhp_testimonial_fail[<?php echo esc_attr( $key ); ?>]" value="1"
					<?php checked( ! empty( $fails[ $key ] ) ); ?>>
				<?php echo esc_html( $label ); ?>
			</label>
		</p>
	<?php endforeach; ?>

	<h3><?php esc_html_e( 'The decision', 'brave-hearts' ); ?></h3>
	<p>
		<label for="bhp_testimonial_status"><strong><?php esc_html_e( 'Status', 'brave-hearts' ); ?></strong></label><br>
		<select id="bhp_testimonial_status" name="bhp_testimonial_status">
			<?php foreach ( bhp_testimonial_statuses() as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>
					<?php disabled( ! bhp_testimonial_status_can_move( $status, $key ) ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>

	<p>
		<label for="bhp_testimonial_code"><strong><?php esc_html_e( 'Reward coupon code', 'brave-hearts' ); ?></strong></label><br>
		<input type="text" id="bhp_testimonial_code" name="bhp_testimonial_code" class="regular-text"
			value="<?php echo esc_attr( (string) get_post_meta( $id, '_bhp_testimonial_code', true ) ); ?>">
		<br>
		<em>
			<?php esc_html_e( 'Type the single-use, email-locked, 100 percent, free-shipping code created in WooCommerce. Nothing in this theme creates a coupon.', 'brave-hearts' ); ?>
			<?php if ( bhp_testimonial_should_capture_mail() ) : ?>
				<br><?php
				printf(
					/* translators: %s: the staging placeholder coupon string. */
					esc_html__( 'This is staging, so leaving it blank uses %s and the email is captured rather than sent.', 'brave-hearts' ),
					esc_html( BHP_TESTIMONIAL_PLACEHOLDER_CODE )
				);
				?>
			<?php endif; ?>
		</em>
	</p>

	<p style="padding:12px;border-left:4px solid #dba617;background:#fcf9e8;">
		<label>
			<input type="checkbox" name="bhp_testimonial_notify" value="1">
			<strong><?php esc_html_e( 'Email this decision to the submitter when I save.', 'brave-hearts' ); ?></strong>
		</label><br>
		<em><?php esc_html_e( 'Nothing is emailed unless this is ticked. It clears itself after each save.', 'brave-hearts' ); ?></em>
		<?php if ( $pass_sent ) : ?>
			<br><strong><?php
			printf(
				/* translators: %s: ISO timestamp. */
				esc_html__( 'A passing note has already gone out (%s).', 'brave-hearts' ),
				esc_html( $pass_sent )
			);
			?></strong>
		<?php endif; ?>
		<?php if ( $fail_sent ) : ?>
			<br><strong><?php
			printf(
				/* translators: %s: ISO timestamp. */
				esc_html__( 'The one note about what was missing has already gone out (%s). Seal 1301 gives one note, not several.', 'brave-hearts' ),
				esc_html( $fail_sent )
			);
			?></strong>
		<?php endif; ?>
	</p>

	<h3><?php esc_html_e( 'What the checklist says right now', 'brave-hearts' ); ?></h3>
	<?php
	$score = bhp_testimonial_score( $checked, $fails );
	$names = array_merge( bhp_testimonial_checklist(), bhp_testimonial_auto_fails() );
	?>
	<p>
		<strong><?php echo esc_html( 'passed' === $score['verdict'] ? __( 'Meets the checklist.', 'brave-hearts' ) : __( 'Does not meet the checklist.', 'brave-hearts' ) ); ?></strong>
	</p>
	<?php if ( $score['triggered'] || $score['missing'] ) : ?>
		<ul style="list-style:disc;margin-left:22px;">
			<?php foreach ( array_merge( $score['triggered'], $score['missing'] ) as $key ) : ?>
				<li><?php echo esc_html( isset( $names[ $key ] ) ? $names[ $key ] : $key ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( bhp_testimonial_should_capture_mail() ) : ?>
		<h3><?php esc_html_e( 'Captured mail (staging only)', 'brave-hearts' ); ?></h3>
		<p><em><?php esc_html_e( 'On staging nothing is actually sent. The last few messages are kept here so they can be read.', 'brave-hearts' ); ?></em></p>
		<ul style="list-style:disc;margin-left:22px;">
			<?php foreach ( array_slice( bhp_testimonial_mail_log(), 0, 5 ) as $row ) : ?>
				<li>
					<code><?php echo esc_html( (string) $row['captured_at'] ); ?></code>
					&rarr; <?php echo esc_html( (string) $row['to'] ); ?>
					&mdash; <?php echo esc_html( (string) $row['subject'] ); ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php
}

/**
 * Save the review.
 *
 * @param int $post_id Submission id.
 * @return void
 */
function bhp_testimonial_save_review( $post_id ) {
	$post_id = (int) $post_id;

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['bhp_testimonial_review_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bhp_testimonial_review_nonce'] ) ), 'bhp_testimonial_review_' . $post_id ) ) {
		return;
	}
	if ( BHP_TESTIMONIAL_CPT !== get_post_type( $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$checked = array();
	foreach ( array_keys( bhp_testimonial_checklist() ) as $key ) {
		if ( ! empty( $_POST['bhp_testimonial_check'][ $key ] ) ) {
			$checked[ $key ] = 1;
		}
	}
	$fails = array();
	foreach ( array_keys( bhp_testimonial_auto_fails() ) as $key ) {
		if ( ! empty( $_POST['bhp_testimonial_fail'][ $key ] ) ) {
			$fails[ $key ] = 1;
		}
	}
	update_post_meta( $post_id, '_bhp_testimonial_checklist', $checked );
	update_post_meta( $post_id, '_bhp_testimonial_autofails', $fails );

	$code = isset( $_POST['bhp_testimonial_code'] ) ? sanitize_text_field( wp_unslash( $_POST['bhp_testimonial_code'] ) ) : '';
	update_post_meta( $post_id, '_bhp_testimonial_code', $code );

	$current = bhp_testimonial_get_status( $post_id );
	$wanted  = isset( $_POST['bhp_testimonial_status'] ) ? sanitize_key( wp_unslash( $_POST['bhp_testimonial_status'] ) ) : $current;

	/*
	 * ⛔ THE TRANSITION IS CHECKED SERVER-SIDE, NOT ONLY IN THE MARKUP. The
	 *    `disabled` attribute on an option is a courtesy to the person using
	 *    the screen; it is not a control, and a posted value that the flow
	 *    forbids is simply not applied.
	 */
	if ( bhp_testimonial_status_is_valid( $wanted ) && bhp_testimonial_status_can_move( $current, $wanted ) ) {
		update_post_meta( $post_id, BHP_TESTIMONIAL_STATUS_META, $wanted );
	} else {
		$wanted = $current;
	}

	if ( empty( $_POST['bhp_testimonial_notify'] ) ) {
		return; // ⛔ Nothing is emailed unless it was asked for in this save.
	}

	$email = (string) get_post_meta( $post_id, '_bhp_testimonial_email', true );
	if ( ! is_email( $email ) ) {
		return;
	}
	$name  = (string) get_post_meta( $post_id, '_bhp_testimonial_name', true );
	$parts = preg_split( '/\s+/', trim( $name ) );
	$first = $parts ? (string) reset( $parts ) : $name;

	if ( 'passed' === $wanted ) {
		$use = $code;
		if ( '' === $use && bhp_testimonial_should_capture_mail() ) {
			$use = BHP_TESTIMONIAL_PLACEHOLDER_CODE;
		}
		if ( '' === $use ) {
			return; // ⛔ No code, no note. A passing email with no code is worse than none.
		}
		$mail = bhp_testimonial_compose_pass( $first, $use );
		bhp_testimonial_send( $email, $mail['subject'], $mail['body'] );
		update_post_meta( $post_id, '_bhp_testimonial_pass_note_sent', gmdate( 'c' ) );
		return;
	}

	if ( 'failed' === $wanted ) {
		$names   = array_merge( bhp_testimonial_checklist(), bhp_testimonial_auto_fails() );
		$score   = bhp_testimonial_score( $checked, $fails );
		$reasons = array();
		foreach ( array_merge( $score['triggered'], $score['missing'] ) as $key ) {
			$reasons[] = isset( $names[ $key ] ) ? $names[ $key ] : $key;
		}
		$mail = bhp_testimonial_compose_fail( $first, $reasons );
		bhp_testimonial_send( $email, $mail['subject'], $mail['body'] );
		update_post_meta( $post_id, '_bhp_testimonial_fail_note_sent', gmdate( 'c' ) );
	}
}
add_action( 'save_post_' . BHP_TESTIMONIAL_CPT, 'bhp_testimonial_save_review' );

/*
 * ---------------------------------------------------------------------------
 * THE PRIVATE STREAM
 * ---------------------------------------------------------------------------
 */

/**
 * The nonce-carrying URL that plays one uploaded video.
 *
 * @param int $post_id Submission id.
 * @return string
 */
function bhp_testimonial_stream_url( $post_id ) {
	return wp_nonce_url(
		add_query_arg(
			array(
				'action' => BHP_TESTIMONIAL_STREAM_ACTION,
				'id'     => (int) $post_id,
			),
			admin_url( 'admin-post.php' )
		),
		BHP_TESTIMONIAL_STREAM_ACTION . '_' . (int) $post_id,
		'bhp_t_nonce'
	);
}

/**
 * Stream one private video to an authenticated admin.
 *
 * ⛔⛔ THERE IS NO `nopriv` HOOK FOR THIS ACTION, AND THAT ABSENCE IS THE FIRST
 *     LINE OF THE DEFENCE. A logged-out request to this endpoint reaches
 *     WordPress's own "you must log in" path and never reaches this function.
 *     The capability check and the nonce are the second and third lines.
 *
 * ⭐ THE FILENAME NEVER COMES FROM THE REQUEST. It is read from the post's own
 *    meta and then re-validated by `bhp_testimonial_private_path()`, so the
 *    only thing a caller controls is WHICH SUBMISSION, and every submission
 *    they are allowed to see is one they could already open in the editor.
 *
 * @return void
 */
function bhp_testimonial_handle_stream() {
	$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

	if ( ! $id || ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not permitted.', 'brave-hearts' ), '', array( 'response' => 403 ) );
	}
	if ( ! isset( $_GET['bhp_t_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['bhp_t_nonce'] ) ), BHP_TESTIMONIAL_STREAM_ACTION . '_' . $id ) ) {
		wp_die( esc_html__( 'That link expired.', 'brave-hearts' ), '', array( 'response' => 403 ) );
	}
	if ( BHP_TESTIMONIAL_CPT !== get_post_type( $id ) ) {
		wp_die( esc_html__( 'Not found.', 'brave-hearts' ), '', array( 'response' => 404 ) );
	}

	$path = bhp_testimonial_private_path( (string) get_post_meta( $id, '_bhp_testimonial_video_file', true ) );
	if ( '' === $path ) {
		wp_die( esc_html__( 'No file is stored for this submission.', 'brave-hearts' ), '', array( 'response' => 404 ) );
	}

	$type = wp_check_filetype( $path );
	$size = (int) filesize( $path );

	nocache_headers();
	header( 'Content-Type: ' . ( ! empty( $type['type'] ) ? $type['type'] : 'application/octet-stream' ) );
	header( 'Content-Disposition: inline; filename="' . basename( $path ) . '"' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Robots-Tag: noindex, nofollow, noarchive' );

	/*
	 * ⭐⭐ RANGE REQUESTS ARE HONOURED, AND THAT IS NOT A REFINEMENT — IT IS WHAT
	 *    MAKES THE REVIEW SCREEN USABLE.
	 *
	 * ⛔ A plain `readfile()` with no `Accept-Ranges` gives the browser a stream
	 *    it cannot seek in. The scorer's job is to check a 30-to-90-second clip
	 *    against seven items, several of which ("the book is visible", "no other
	 *    brand on camera") mean scrubbing back and forth. Without ranges, every
	 *    re-check is a re-watch from zero, and a review screen that is tedious
	 *    is a review screen that gets skipped.
	 *
	 * ⛔ THE RANGE HEADER IS UNTRUSTED INPUT AND IS CLAMPED, NOT TRUSTED. Start
	 *    and end are forced inside `[0, size-1]` and an inverted or
	 *    out-of-bounds range is answered with 416 rather than with a negative
	 *    length that would read past the file.
	 *
	 * ⭐ THE BODY IS SENT IN 512 KB CHUNKS, NOT WITH `readfile()`. A 100 MB file
	 *    read into the output buffer in one call is a 100 MB allocation; the
	 *    buffers are flushed and closed first so the bytes go out as they are
	 *    read.
	 */
	header( 'Accept-Ranges: bytes' );

	$start = 0;
	$end   = $size - 1;

	if ( ! empty( $_SERVER['HTTP_RANGE'] ) && $size > 0 ) {
		$raw = (string) wp_unslash( $_SERVER['HTTP_RANGE'] );
		if ( preg_match( '/^bytes=(\d*)-(\d*)$/', trim( $raw ), $m ) ) {
			$req_start = ( '' === $m[1] ) ? null : (int) $m[1];
			$req_end   = ( '' === $m[2] ) ? null : (int) $m[2];

			if ( null === $req_start && null !== $req_end ) {
				// `bytes=-500` means the LAST 500 bytes, not "up to byte 500".
				$start = max( 0, $size - $req_end );
			} elseif ( null !== $req_start ) {
				$start = $req_start;
				if ( null !== $req_end ) {
					$end = $req_end;
				}
			}
			$end = min( $end, $size - 1 );

			if ( $start > $end || $start >= $size ) {
				header( 'HTTP/1.1 416 Requested Range Not Satisfiable' );
				header( 'Content-Range: bytes */' . $size );
				exit;
			}

			header( 'HTTP/1.1 206 Partial Content' );
			header( 'Content-Range: bytes ' . $start . '-' . $end . '/' . $size );
		}
	}

	header( 'Content-Length: ' . ( $end - $start + 1 ) );

	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	$fh = fopen( $path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! $fh ) {
		exit;
	}
	fseek( $fh, $start );
	$remaining = $end - $start + 1;
	while ( $remaining > 0 && ! feof( $fh ) ) {
		$chunk = fread( $fh, (int) min( 524288, $remaining ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( false === $chunk || '' === $chunk ) {
			break;
		}
		echo $chunk; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		flush();
		$remaining -= strlen( $chunk );
	}
	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}
add_action( 'admin_post_' . BHP_TESTIMONIAL_STREAM_ACTION, 'bhp_testimonial_handle_stream' );

/*
 * ---------------------------------------------------------------------------
 * THE QUEUE LIST
 * ---------------------------------------------------------------------------
 */

/**
 * Add a status column to the queue.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function bhp_testimonial_columns( $columns ) {
	$out = array();
	foreach ( $columns as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'title' === $key ) {
			$out['bhp_status'] = __( 'Status', 'brave-hearts' );
			$out['bhp_video']  = __( 'Video', 'brave-hearts' );
		}
	}
	return $out;
}
add_filter( 'manage_' . BHP_TESTIMONIAL_CPT . '_posts_columns', 'bhp_testimonial_columns' );

/**
 * Fill the added columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Submission id.
 * @return void
 */
function bhp_testimonial_column_content( $column, $post_id ) {
	if ( 'bhp_status' === $column ) {
		$statuses = bhp_testimonial_statuses();
		$status   = bhp_testimonial_get_status( $post_id );
		echo esc_html( isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status );
		return;
	}
	if ( 'bhp_video' === $column ) {
		if ( get_post_meta( $post_id, '_bhp_testimonial_video_url', true ) ) {
			esc_html_e( 'Link', 'brave-hearts' );
		} elseif ( get_post_meta( $post_id, '_bhp_testimonial_video_file', true ) ) {
			esc_html_e( 'Uploaded file', 'brave-hearts' );
		} else {
			esc_html_e( 'None', 'brave-hearts' );
		}
	}
}
add_action( 'manage_' . BHP_TESTIMONIAL_CPT . '_posts_custom_column', 'bhp_testimonial_column_content', 10, 2 );

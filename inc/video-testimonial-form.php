<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * THE VIDEO TESTIMONIAL FORM, HANDLER AND EMAILS. Theme 1.19.395, 2026-09-07,
 * `CYCLE179-LD-BUILD-395-TESTIMONIAL`. Founder seals 1294 to 1301.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⛔ READ `inc/video-testimonials.php` FIRST. The post type, the status flow,
 *    the checklist, the consent record and both upload routes are defined
 *    there, along with the measurement that decided the upload route.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ EVERY CUSTOMER-FACING STRING IN THIS FILE IS PLACEHOLDER COPY AND IS
 *     WAITING ON ANDREW. the marketing lane is drafting the real wording in
 *     `CYCLE179-MKT-VIDEO-TESTIMONIAL`; the advertising-knowledge lane is writing the release text in
 *     `CYCLE179-ADS-TESTIMONIAL-DOCTRINE`.
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE MARKING IS A FLAG, NOT A COMMENT, AND THAT IS THE POINT.
 *    `BHP_TESTIMONIAL_COPY_APPROVED` is `false`. While it is false:
 *      · the page prints a visible band saying the wording is not approved;
 *      · every email subject carries a placeholder prefix;
 *      · the suite asserts both of those, so the marking cannot be lost.
 *    ⛔ A comment saying "placeholder" does not stop unapproved copy reaching a
 *       customer. A flag that is asserted does. Flipping it to `true` is a
 *       deliberate act taken AFTER Andrew approves the wording, and it is the
 *       only thing that removes the band.
 *
 * ⭐ THE COPY RAILS THIS FILE IS HELD TO, restated here because this is the
 *    file whose strings get edited:
 *      · Andrew's I-voice. NO "we", "us" or "our" in any visible string.
 *      · NO em dashes in customer copy.
 *      · AMERICAN SPELLING. "coloring", never "colouring".
 *      · NO child's name is collected, printed or stored. There is no field.
 *      · NEVER promise publication. The reward is for meeting the checklist;
 *        whether anything is ever used publicly is Andrew's choice and the
 *        copy says so.
 *      · NO mention of Amazon and NO mention of reviews or ratings. This is a
 *        video for Andrew, not a review anywhere.
 *      · NO price literal beyond the book's own $12.99, which is the offer.
 *    The suite asserts the "we/us/our", em dash, British spelling, Amazon and
 *    review rails against the rendered strings rather than against the source.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ⛔⛔ MAIL ON STAGING IS CAPTURED, NOT SENT
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * ⭐ THE DETECTOR IS THE EXISTING ONE, REUSED VERBATIM, NOT A SECOND OPINION.
 *    `bhp_readaloud_request_should_capture()` already routes through
 *    `bhp_staging_mail_guard_is_staging()`, which FAILS TOWARDS PRODUCTION: no
 *    detector, or any host that is not the staging literal, means the mail is
 *    SENT normally. There is no value of any option, constant or environment
 *    variable that can make a real parent on braveheartspublishing.com be
 *    silently swallowed.
 *
 * ⛔ A hand-rolled `wp_mail()` walks straight past `inc/staging-mail-guard.php`,
 *    which only reaches `WC_Email` classes. That is exactly the trap
 *    `inc/readaloud-scheduler.php` documents, and this file does not walk into
 *    it a second time: `bhp_testimonial_send()` checks the capture flag before
 *    it calls `wp_mail()` at all.
 *
 * ⛔ NO MAILCHIMP CALL. NO dataLayer PUSH. A testimonial submitter is not a
 *    newsletter signup and has not asked to be one, and there is no existing
 *    analytics pattern for this surface to reuse. Adding either would be a new
 *    external integration, which is an Andrew gate.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Has Andrew approved the customer-facing wording on this page?
 *
 * ⛔ FALSE UNTIL HE SAYS OTHERWISE. See this file's header for what it drives.
 */
const BHP_TESTIMONIAL_COPY_APPROVED = false;

/**
 * The banner shown while the copy is unapproved.
 *
 * @return string
 */
function bhp_testimonial_placeholder_notice() {
	return __( 'PLACEHOLDER COPY - FOR ANDREW\'S APPROVAL. The wording on this page is a draft and has not been approved.', 'brave-hearts' );
}

/**
 * The option the STAGING capture writes to.
 */
const BHP_TESTIMONIAL_MAIL_LOG_OPTION = 'bhp_testimonial_mail_log';

/**
 * How many captured messages are kept.
 */
const BHP_TESTIMONIAL_MAIL_LOG_MAX = 30;

/**
 * Should this request's mail be CAPTURED instead of sent?
 *
 * ⭐ ONE DEFINITION OF "IS THIS STAGING" IN THE THEME. This defers to the
 *    read-aloud scheduler's answer, which defers to the mail guard's, which
 *    compares `BHP_Analytics_Config::STAGING_HOST`. Nothing here invents a
 *    host and nothing here can be flipped by an option.
 *
 * @return bool
 */
function bhp_testimonial_should_capture_mail() {
	if ( function_exists( 'bhp_readaloud_request_should_capture' ) ) {
		return (bool) bhp_readaloud_request_should_capture();
	}
	if ( function_exists( 'bhp_staging_mail_guard_is_staging' ) ) {
		return (bool) bhp_staging_mail_guard_is_staging();
	}
	return false; // ⛔ No detector, no capture. Fail towards production.
}

/**
 * Read the capture log.
 *
 * @return array<int,array<string,mixed>>
 */
function bhp_testimonial_mail_log() {
	$log = get_option( BHP_TESTIMONIAL_MAIL_LOG_OPTION, array() );
	return is_array( $log ) ? $log : array();
}

/**
 * Send one message, or capture it on staging.
 *
 * ⛔ CAPTURE IS NOT SILENCE. The whole message is stored so QA can read exactly
 *    what would have gone out, and the review screen shows the last few.
 *
 * @param string $to      Recipient.
 * @param string $subject Subject.
 * @param string $body    Plain-text body.
 * @return array{sent:bool,captured:bool}
 */
function bhp_testimonial_send( $to, $subject, $body ) {
	$to      = (string) $to;
	$subject = (string) $subject;
	$body    = (string) $body;

	if ( ! BHP_TESTIMONIAL_COPY_APPROVED ) {
		$subject = '[PLACEHOLDER COPY] ' . $subject;
	}

	if ( bhp_testimonial_should_capture_mail() ) {
		$log = bhp_testimonial_mail_log();
		array_unshift(
			$log,
			array(
				'captured_at' => gmdate( 'c' ),
				'to'          => $to,
				'subject'     => $subject,
				'body'        => $body,
			)
		);
		update_option( BHP_TESTIMONIAL_MAIL_LOG_OPTION, array_slice( $log, 0, BHP_TESTIMONIAL_MAIL_LOG_MAX ), false );
		return array(
			'sent'     => false,
			'captured' => true,
		);
	}

	$sent = wp_mail( $to, $subject, $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );
	return array(
		'sent'     => (bool) $sent,
		'captured' => false,
	);
}

/*
 * ---------------------------------------------------------------------------
 * THE THREE EMAILS. ALL PURE COMPOSERS.
 * ---------------------------------------------------------------------------
 *
 * ⭐ SEPARATED FROM EVERY SEND PATH SO THE SUITE CAN ASSERT EVERY WORD A
 *   CUSTOMER WOULD READ WITHOUT SENDING ANYTHING TO ANYBODY. That is the same
 *   reason `bhp_readaloud_request_compose()` is pure, and it is the only
 *   honest way to prove "the copy rails hold" without a live send.
 */

/**
 * The confirmation, sent the moment a submission lands.
 *
 * ⛔ IT PROMISES NOTHING. No reward yet, no publication, no timeline that
 *    cannot be kept. Seal 1301 gives a submitter a checklist and a second try;
 *    it does not give them a coupon on arrival.
 *
 * @param string $first_name Submitter's first name.
 * @return array{subject:string,body:string}
 */
function bhp_testimonial_compose_confirmation( $first_name ) {
	$name = trim( (string) $first_name );

	return array(
		'subject' => __( 'Your video came through', 'brave-hearts' ),
		'body'    => sprintf(
			/* translators: %s: submitter first name. */
			__(
				"%s,\n\n" .
				"Thank you for sending a video of you and your reader. It arrived safely and it is in the queue.\n\n" .
				"Here is what happens next. I watch every video myself against the checklist on the submission page. If it meets the checklist, I will email you a code for a free printed copy of the Mariana Trench Ocean Coloring Book, shipping included. If something is missing, I will tell you exactly what it was so you can send another one.\n\n" .
				"Two things worth saying plainly. Whether you liked the book has nothing to do with whether this qualifies. And qualifying does not mean the video gets used anywhere. That stays my decision, and most videos are simply watched and kept.\n\n" .
				"Give me a few days.\n\n" .
				"Andrew\nBrave Hearts Publishing",
				'brave-hearts'
			),
			$name
		),
	);
}

/**
 * The passing note, carrying the reward code.
 *
 * ⛔⛔ THE CODE IS PASSED IN. THIS FUNCTION CREATES NO COUPON AND NOTHING IN
 *     THIS THEME DOES. Seal 1300 makes coupon creation Andrew's own act on
 *     production. On staging the review screen offers a clearly labelled
 *     placeholder string so the email can be proved end to end without a
 *     WooCommerce coupon record existing anywhere.
 *
 * @param string $first_name Submitter's first name.
 * @param string $code       The coupon code Andrew created.
 * @return array{subject:string,body:string}
 */
function bhp_testimonial_compose_pass( $first_name, $code ) {
	$name = trim( (string) $first_name );
	$code = trim( (string) $code );

	return array(
		'subject' => __( 'Your coloring book is on the way', 'brave-hearts' ),
		'body'    => sprintf(
			/* translators: 1: submitter first name. 2: single-use coupon code. */
			__(
				"%1\$s,\n\n" .
				"Your video meets the checklist. Thank you for taking the time.\n\n" .
				"Here is your code for a free printed copy of the Mariana Trench Ocean Coloring Book, with shipping covered:\n\n" .
				"    %2\$s\n\n" .
				"Add the coloring book to your cart and enter the code at checkout. It brings the total to zero. The code works once and it is tied to this email address, so use it from the address this note arrived at.\n\n" .
				"One thing to be clear about, since it matters: qualifying is not a promise that the video gets used anywhere. That stays my decision.\n\n" .
				"Andrew\nBrave Hearts Publishing",
				'brave-hearts'
			),
			$name,
			$code
		),
	);
}

/**
 * The one friendly note when a submission does not qualify.
 *
 * ⭐ SEAL 1301 SAYS ONE NOTE AND A SECOND TRY. The note therefore has to name
 *    the actual missing item, because "it did not qualify" gives a parent
 *    nothing to act on and turns a second try into a guess.
 *
 * ⛔ IT NAMES ONLY WHAT THE CHECKLIST SAYS. No opinion about the video, the
 *    family or the reader appears here, and none is collected anywhere to put
 *    in it.
 *
 * @param string   $first_name Submitter's first name.
 * @param string[] $reasons    Human-readable missing items or triggered fails.
 * @return array{subject:string,body:string}
 */
function bhp_testimonial_compose_fail( $first_name, $reasons ) {
	$name    = trim( (string) $first_name );
	$reasons = array_values( array_filter( array_map( 'strval', (array) $reasons ) ) );

	$list = '';
	foreach ( $reasons as $reason ) {
		$list .= '    - ' . $reason . "\n";
	}
	if ( '' === $list ) {
		$list = '    - ' . __( 'The recording did not meet the checklist.', 'brave-hearts' ) . "\n";
	}

	return array(
		'subject' => __( 'About the video you sent', 'brave-hearts' ),
		'body'    => sprintf(
			/* translators: 1: submitter first name. 2: bulleted list of missing checklist items. */
			__(
				"%1\$s,\n\n" .
				"Thank you for sending a video. It did not quite meet the checklist this time, and here is exactly what was missing:\n\n" .
				"%2\$s\n" .
				"That is the only thing standing in the way. Film it again with that sorted and send it in, and the free printed coloring book is yours.\n\n" .
				"Andrew\nBrave Hearts Publishing",
				'brave-hearts'
			),
			$name,
			$list
		),
	);
}

/*
 * ---------------------------------------------------------------------------
 * THE FORM
 * ---------------------------------------------------------------------------
 */

/**
 * The URL of the terms and release page.
 *
 * ⭐ RESOLVED BY SLUG AT RENDER TIME, NOT HARDCODED. The page is created by
 *    hand on each environment; if it does not exist yet the link points at the
 *    home page rather than at a 404, and the suite reports the miss.
 *
 * @return string
 */
function bhp_testimonial_terms_url() {
	$page = get_page_by_path( BHP_TESTIMONIAL_TERMS_SLUG );
	return $page ? (string) get_permalink( $page ) : (string) home_url( '/' );
}

/**
 * Render the submission form.
 *
 * ⛔ THERE IS NO FIELD FOR A CHILD'S NAME, AND THERE IS NO FIELD FOR A SHIPPING
 *    ADDRESS. The first is seal 1301 and seal 1275. The second is seal 1299:
 *    the coupon route puts the address in WooCommerce checkout where it already
 *    lives, so collecting it here would create a second private store of
 *    personal data for no gain.
 *
 * @return string HTML.
 */
function bhp_testimonial_render_form() {
	$status  = isset( $_GET['bhp_t'] ) ? sanitize_key( wp_unslash( $_GET['bhp_t'] ) ) : '';
	$cap     = size_format( bhp_testimonial_upload_cap_bytes() );
	$checks  = bhp_testimonial_checklist();
	$fails   = bhp_testimonial_auto_fails();

	ob_start();
	?>
	<div class="bhp-testimonial">

		<?php if ( ! BHP_TESTIMONIAL_COPY_APPROVED ) : ?>
			<p class="bhp-testimonial__placeholder" role="status">
				<?php echo esc_html( bhp_testimonial_placeholder_notice() ); ?>
			</p>
		<?php endif; ?>

		<?php if ( $status ) : ?>
			<p class="bhp-testimonial__status bhp-testimonial__status--<?php echo esc_attr( $status ); ?>" role="status">
				<?php echo esc_html( bhp_testimonial_status_message( $status ) ); ?>
			</p>
		<?php endif; ?>

		<h1 class="bhp-testimonial__title"><?php esc_html_e( 'Send a video of you and your reader', 'brave-hearts' ); ?></h1>

		<p class="bhp-testimonial__lede">
			<?php esc_html_e( 'Film a short video of yourself talking about the book with your reader, send it in, and if it meets the checklist below I will send you a free printed copy of the Mariana Trench Ocean Coloring Book, shipping covered. The book sells for $12.99.', 'brave-hearts' ); ?>
		</p>

		<p class="bhp-testimonial__lede">
			<?php esc_html_e( 'Two things to know before you start. Whether you liked the book has no bearing on whether this qualifies, and qualifying is not a promise that the video gets used anywhere. That stays my decision.', 'brave-hearts' ); ?>
		</p>

		<h2 class="bhp-testimonial__h2"><?php esc_html_e( 'What the video needs', 'brave-hearts' ); ?></h2>
		<ul class="bhp-testimonial__checklist">
			<?php foreach ( $checks as $key => $label ) : ?>
				<li data-check="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></li>
			<?php endforeach; ?>
		</ul>

		<h2 class="bhp-testimonial__h2"><?php esc_html_e( 'What does not qualify', 'brave-hearts' ); ?></h2>
		<ul class="bhp-testimonial__autofails">
			<?php foreach ( $fails as $key => $label ) : ?>
				<li data-fail="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></li>
			<?php endforeach; ?>
		</ul>

		<form class="bhp-testimonial__form"
			action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			method="post"
			enctype="multipart/form-data">

			<input type="hidden" name="action" value="<?php echo esc_attr( BHP_TESTIMONIAL_ACTION ); ?>">
			<?php wp_nonce_field( BHP_TESTIMONIAL_ACTION, 'bhp_testimonial_nonce' ); ?>

			<?php /* Honeypot. Hidden from people, irresistible to bots. */ ?>
			<p class="bhp-testimonial__hp" aria-hidden="true">
				<label for="bhp_t_website"><?php esc_html_e( 'Leave this empty', 'brave-hearts' ); ?></label>
				<input type="text" id="bhp_t_website" name="bhp_t_website" tabindex="-1" autocomplete="off" value="">
			</p>

			<p class="bhp-testimonial__field">
				<label for="bhp_t_name"><?php esc_html_e( 'Your full name', 'brave-hearts' ); ?></label>
				<input type="text" id="bhp_t_name" name="bhp_t_name" required autocomplete="name" maxlength="120">
			</p>

			<p class="bhp-testimonial__field">
				<label for="bhp_t_email"><?php esc_html_e( 'Your email address', 'brave-hearts' ); ?></label>
				<input type="email" id="bhp_t_email" name="bhp_t_email" required autocomplete="email" maxlength="200" inputmode="email">
				<span class="bhp-testimonial__hint"><?php esc_html_e( 'The reward code is tied to this address, so use one you can get to.', 'brave-hearts' ); ?></span>
			</p>

			<fieldset class="bhp-testimonial__route">
				<legend><?php esc_html_e( 'How to send the video', 'brave-hearts' ); ?></legend>

				<p class="bhp-testimonial__field">
					<label for="bhp_t_url"><?php esc_html_e( 'Paste a link to the video', 'brave-hearts' ); ?></label>
					<input type="url" id="bhp_t_url" name="bhp_t_url" maxlength="500" placeholder="https://">
					<span class="bhp-testimonial__hint">
						<?php esc_html_e( 'An unlisted YouTube or Vimeo link, or a Google Drive, Dropbox or iCloud share link. This is the easiest way and it is the one I would pick.', 'brave-hearts' ); ?>
					</span>
				</p>

				<p class="bhp-testimonial__or"><?php esc_html_e( 'or', 'brave-hearts' ); ?></p>

				<p class="bhp-testimonial__field">
					<label for="bhp_t_file"><?php esc_html_e( 'Upload the video file', 'brave-hearts' ); ?></label>
					<input type="file" id="bhp_t_file" name="bhp_t_file" accept="video/mp4,video/quicktime,video/webm">
					<span class="bhp-testimonial__hint">
						<?php
						printf(
							/* translators: %s: maximum upload size, for example 100 MB. */
							esc_html__( 'MP4, MOV or WEBM, up to %s. A long upload on a phone can time out, so a link is the safer route.', 'brave-hearts' ),
							esc_html( $cap )
						);
						?>
					</span>
				</p>
			</fieldset>

			<fieldset class="bhp-testimonial__consent">
				<legend><?php esc_html_e( 'Terms and release', 'brave-hearts' ); ?></legend>

				<p class="bhp-testimonial__disclaimer">
					<?php esc_html_e( 'Sending a video gives Brave Hearts Publishing permission to keep it and, at its own discretion, to use it. No child is ever named. The full terms and release cover what that permission includes and how long it lasts.', 'brave-hearts' ); ?>
					<a href="<?php echo esc_url( bhp_testimonial_terms_url() ); ?>" class="bhp-testimonial__terms-link" target="_blank" rel="noopener">
						<?php esc_html_e( 'Read the full terms and release', 'brave-hearts' ); ?>
					</a>
				</p>

				<p class="bhp-testimonial__field bhp-testimonial__field--check">
					<label for="bhp_t_terms">
						<input type="checkbox" id="bhp_t_terms" name="bhp_t_terms" value="1" required>
						<?php esc_html_e( 'I have reviewed the terms and release.', 'brave-hearts' ); ?>
					</label>
				</p>

				<p class="bhp-testimonial__field bhp-testimonial__field--check">
					<label for="bhp_t_child">
						<input type="checkbox" id="bhp_t_child" name="bhp_t_child" value="1">
						<?php esc_html_e( 'A child appears in this video.', 'brave-hearts' ); ?>
					</label>
				</p>

				<p class="bhp-testimonial__field bhp-testimonial__field--check bhp-testimonial__guardian">
					<label for="bhp_t_guardian">
						<input type="checkbox" id="bhp_t_guardian" name="bhp_t_guardian" value="1">
						<?php esc_html_e( 'I am this child\'s parent or legal guardian.', 'brave-hearts' ); ?>
					</label>
					<span class="bhp-testimonial__hint"><?php esc_html_e( 'Required if a child appears.', 'brave-hearts' ); ?></span>
				</p>

				<p class="bhp-testimonial__field">
					<label for="bhp_t_signature"><?php esc_html_e( 'Type your full name as your signature', 'brave-hearts' ); ?></label>
					<input type="text" id="bhp_t_signature" name="bhp_t_signature" required autocomplete="off" maxlength="120">
				</p>
			</fieldset>

			<p class="bhp-testimonial__submit">
				<button type="submit" class="bhp-testimonial__button"><?php esc_html_e( 'Send my video', 'brave-hearts' ); ?></button>
			</p>

			<p class="bhp-testimonial__privacy">
				<?php esc_html_e( 'Your name, email address and the video stay private. A record of this agreement is kept with the submission. No child is named anywhere.', 'brave-hearts' ); ?>
			</p>
		</form>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * The message shown after a redirect back to the form.
 *
 * @param string $status Status key from the query string.
 * @return string
 */
function bhp_testimonial_status_message( $status ) {
	$messages = array(
		'ok'          => __( 'Thank you. The video came through and it is in the queue. A confirmation is on its way to the address given.', 'brave-hearts' ),
		'nonce'       => __( 'The form expired before it was sent. Please try again.', 'brave-hearts' ),
		'name'        => __( 'Please give a full name.', 'brave-hearts' ),
		'email'       => __( 'Please give an email address that works.', 'brave-hearts' ),
		'terms'       => __( 'Please review the terms and release, then check the box.', 'brave-hearts' ),
		'signature'   => __( 'Please type a full name as a signature.', 'brave-hearts' ),
		'guardian'    => __( 'Where a child appears, the parent or legal guardian has to confirm that.', 'brave-hearts' ),
		'video'       => __( 'Please paste a link to the video or choose a file to upload.', 'brave-hearts' ),
		'host'        => __( 'That link is not one I can open. Use an unlisted YouTube or Vimeo link, or a Google Drive, Dropbox or iCloud share link.', 'brave-hearts' ),
		'not_https'   => __( 'That link needs to start with https.', 'brave-hearts' ),
		'too_large'   => __( 'That file is larger than the upload limit. Sending a link works better for a long video.', 'brave-hearts' ),
		'not_a_video' => __( 'That file did not read as a video. MP4, MOV and WEBM all work.', 'brave-hearts' ),
		'upload'      => __( 'The upload did not finish. Sending a link is the more reliable route on a phone.', 'brave-hearts' ),
		'error'       => __( 'Something went wrong on my end. Please try again.', 'brave-hearts' ),
	);
	return isset( $messages[ $status ] ) ? $messages[ $status ] : '';
}

/*
 * ---------------------------------------------------------------------------
 * THE HANDLER
 * ---------------------------------------------------------------------------
 */

add_action( 'admin_post_nopriv_' . BHP_TESTIMONIAL_ACTION, 'bhp_testimonial_handle_submit' );
add_action( 'admin_post_' . BHP_TESTIMONIAL_ACTION, 'bhp_testimonial_handle_submit' );

/**
 * Take one submission.
 *
 * ⛔ VALIDATION ORDER IS DELIBERATE: nonce, then honeypot, then the consent
 *    block, then the video. The consent block is checked BEFORE the video
 *    because a submission without a signed release is not a submission that
 *    should have its file written anywhere, and a 100 MB upload should not be
 *    accepted and then thrown away.
 *
 * @return void
 */
function bhp_testimonial_handle_submit() {
	$back = bhp_testimonial_page_url();

	$redirect = function ( $status ) use ( $back ) {
		wp_safe_redirect( add_query_arg( 'bhp_t', $status, $back ) . '#bhp-testimonial' );
		exit;
	};

	if ( ! isset( $_POST['bhp_testimonial_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bhp_testimonial_nonce'] ) ), BHP_TESTIMONIAL_ACTION ) ) {
		$redirect( 'nonce' );
	}

	// The honeypot. A bot fills it; a person never sees it. Answer as success.
	if ( ! empty( $_POST['bhp_t_website'] ) ) {
		$redirect( 'ok' );
	}

	$name = isset( $_POST['bhp_t_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bhp_t_name'] ) ) : '';
	if ( strlen( trim( $name ) ) < 2 ) {
		$redirect( 'name' );
	}

	$email = isset( $_POST['bhp_t_email'] ) ? sanitize_email( wp_unslash( $_POST['bhp_t_email'] ) ) : '';
	if ( ! is_email( $email ) ) {
		$redirect( 'email' );
	}

	$terms = ! empty( $_POST['bhp_t_terms'] );
	if ( ! $terms ) {
		$redirect( 'terms' ); // ⛔ REQUIRED. Seal 1295.
	}

	$signature = isset( $_POST['bhp_t_signature'] ) ? sanitize_text_field( wp_unslash( $_POST['bhp_t_signature'] ) ) : '';
	if ( strlen( trim( $signature ) ) < 2 ) {
		$redirect( 'signature' ); // ⛔ REQUIRED. Seal 1295.
	}

	$child_appears = ! empty( $_POST['bhp_t_child'] );
	$guardian      = ! empty( $_POST['bhp_t_guardian'] );
	if ( $child_appears && ! $guardian ) {
		$redirect( 'guardian' ); // ⛔ REQUIRED WHERE A CHILD APPEARS. Seal 1295.
	}

	/*
	 * The video. A link is preferred and is checked first; an upload is only
	 * looked at when no link was given.
	 */
	$video_url  = isset( $_POST['bhp_t_url'] ) ? trim( (string) wp_unslash( $_POST['bhp_t_url'] ) ) : '';
	$video_file = '';
	$video_size = 0;

	if ( '' !== $video_url ) {
		$check = bhp_testimonial_validate_video_url( $video_url );
		if ( ! $check['ok'] ) {
			$redirect( 'not_https' === $check['reason'] ? 'not_https' : 'host' );
		}
		$video_url = $check['url'];
	} elseif ( ! empty( $_FILES['bhp_t_file'] ) && UPLOAD_ERR_NO_FILE !== (int) $_FILES['bhp_t_file']['error'] ) {
		$stored = bhp_testimonial_store_upload( $_FILES['bhp_t_file'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! $stored['ok'] ) {
			$map = array(
				'too_large'   => 'too_large',
				'not_a_video' => 'not_a_video',
				'bad_extension' => 'not_a_video',
			);
			$redirect( isset( $map[ $stored['reason'] ] ) ? $map[ $stored['reason'] ] : 'upload' );
		}
		$video_file = $stored['name'];
		$video_size = $stored['bytes'];
	} else {
		$redirect( 'video' );
	}

	/*
	 * ⛔ THE TITLE IS BUILT FROM THE SUBMITTER'S OWN NAME AND A DATE, AND FROM
	 *    NOTHING ELSE. No child's name is collected, so none can reach it.
	 */
	$post_id = wp_insert_post(
		array(
			'post_type'   => BHP_TESTIMONIAL_CPT,
			'post_status' => 'private',
			'post_title'  => sprintf(
				/* translators: 1: submitter name. 2: date. */
				__( '%1$s, %2$s', 'brave-hearts' ),
				$name,
				gmdate( 'Y-m-d' )
			),
		),
		true
	);

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		$redirect( 'error' );
	}

	update_post_meta( $post_id, BHP_TESTIMONIAL_STATUS_META, 'submitted' );
	update_post_meta( $post_id, '_bhp_testimonial_name', $name );
	update_post_meta( $post_id, '_bhp_testimonial_email', $email );
	update_post_meta( $post_id, '_bhp_testimonial_video_url', $video_url );
	update_post_meta( $post_id, '_bhp_testimonial_video_file', $video_file );
	update_post_meta( $post_id, '_bhp_testimonial_video_bytes', $video_size );
	update_post_meta( $post_id, '_bhp_testimonial_submitted_at', gmdate( 'c' ) );

	foreach ( bhp_testimonial_build_consent( $signature, $terms, $child_appears, $guardian, bhp_testimonial_client_ip() ) as $key => $value ) {
		update_post_meta( $post_id, $key, $value );
	}

	$parts = preg_split( '/\s+/', trim( $name ) );
	$first = $parts ? (string) reset( $parts ) : $name;

	$mail = bhp_testimonial_compose_confirmation( $first );
	bhp_testimonial_send( $email, $mail['subject'], $mail['body'] );

	$redirect( 'ok' );
}

/**
 * The public URL of the submission page.
 *
 * @return string
 */
function bhp_testimonial_page_url() {
	$page = get_page_by_path( BHP_TESTIMONIAL_PAGE_SLUG );
	return $page ? (string) get_permalink( $page ) : (string) home_url( '/' );
}

/*
 * ---------------------------------------------------------------------------
 * PAGE DETECTION, ASSETS AND THE POPUP SUPPRESSION
 * ---------------------------------------------------------------------------
 */

/**
 * Is the current request the submission page?
 *
 * ⭐ TEMPLATE FIRST, SLUG SECOND, and the order matters. A page created by hand
 *    on a fresh environment sometimes lands without its `_wp_page_template`
 *    meta set, which is the exact failure `bhp_school_readalouds_is_page()`
 *    documents. Asking the template first and falling back to the slug means
 *    neither miss takes the stylesheet down with it.
 *
 * @return bool
 */
function bhp_testimonial_is_page() {
	if ( is_admin() ) {
		return false;
	}
	if ( is_page_template( 'page-video-testimonial.php' ) ) {
		return true;
	}
	return is_page( BHP_TESTIMONIAL_PAGE_SLUG );
}

/**
 * Is the current request the terms and release page?
 *
 * @return bool
 */
function bhp_testimonial_is_terms_page() {
	if ( is_admin() ) {
		return false;
	}
	if ( is_page_template( 'page-video-testimonial-terms.php' ) ) {
		return true;
	}
	return is_page( BHP_TESTIMONIAL_TERMS_SLUG );
}

/**
 * The one stylesheet this feature adds.
 *
 * ⛔ NO SCRIPT. The form is a plain server-rendered POST and works with
 *    scripting off, which is the same choice `inc/read-aloud-landing.php`
 *    records for the capture form: a submission that depends on a script is a
 *    submission that can silently not happen.
 *
 * ⛔ NO GOOGLE FONTS REQUEST. The sitewide `bhp-google-fonts` handle already
 *    carries every family this page uses.
 *
 * @return void
 */
function bhp_testimonial_enqueue_assets() {
	if ( ! bhp_testimonial_is_page() && ! bhp_testimonial_is_terms_page() ) {
		return;
	}
	wp_enqueue_style(
		'bhp-video-testimonial',
		get_template_directory_uri() . '/assets/css/video-testimonial.css',
		array( 'bhp-style' ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'bhp_testimonial_enqueue_assets' );

/**
 * No popup over the consent form.
 *
 * ⛔ A modal that covers a page where somebody is being asked to read terms and
 *    type their name as a signature is not an annoyance, it is an interruption
 *    of the act the consent record is evidence of. ⭐ Done through the shipped
 *    `bhp_show_exit_intent_popup` filter rather than by editing the popup's own
 *    template list, which is the pattern `inc/read-aloud-landing.php` already
 *    established.
 *
 * @param bool $show The popup engine's answer.
 * @return bool
 */
function bhp_testimonial_suppress_popup( $show ) {
	return ( bhp_testimonial_is_page() || bhp_testimonial_is_terms_page() ) ? false : $show;
}
add_filter( 'bhp_show_exit_intent_popup', 'bhp_testimonial_suppress_popup' );

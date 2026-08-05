<?php
/**
 * Brave Hearts Publishing — Analytics Phase 1B: debug/validation mode.
 *
 * Visible only when BHP_Analytics_Config::debug_mode_available() is true
 * -- staging host AND a logged-in administrator. Never renders on
 * production regardless of login state (Phase 14 requirement), since it
 * prints raw event payloads into the page for inspection.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BHP_Analytics_Debug {

	public static function init() {
		add_action( 'wp_footer', array( __CLASS__, 'render' ), 100 );
	}

	public static function render() {
		if ( ! BHP_Analytics_Config::debug_mode_available() ) {
			return;
		}
		$consent = BHP_Consent::current_state();
		?>
		<button type="button" id="bhp-analytics-debug-toggle" style="position:fixed;bottom:16px;left:16px;z-index:999998;background:#1a1a1a;color:#e8e8e8;border:1px solid #444;border-radius:4px;padding:6px 10px;font:11px monospace;cursor:pointer;opacity:.85;">Analytics Debug</button>

		<div id="bhp-analytics-debug" style="display:none;position:fixed;bottom:16px;left:16px;z-index:999999;width:380px;max-height:60vh;overflow-y:auto;background:#1a1a1a;color:#e8e8e8;font:12px/1.4 monospace;border-radius:6px;box-shadow:0 4px 20px rgba(0,0,0,.4);">
			<div style="padding:10px 12px;border-bottom:1px solid #444;display:flex;justify-content:space-between;align-items:center;">
				<strong>Analytics Debug (staging, admin-only)</strong>
				<button type="button" id="bhp-analytics-debug-close" style="background:none;border:none;color:#e8e8e8;cursor:pointer;font-size:14px;">&times;</button>
			</div>
			<div style="padding:10px 12px;border-bottom:1px solid #444;">
				<div>Environment: <?php echo esc_html( BHP_Analytics_Config::environment_label() ); ?></div>
				<div>GTM container: <?php echo esc_html( BHP_Analytics_Config::gtm_container_id() ?: '(not configured)' ); ?></div>
				<div>analytics_storage consent: <?php echo esc_html( $consent['analytics_storage'] ); ?></div>
				<div>ad_storage consent: <?php echo esc_html( $consent['ad_storage'] ); ?></div>
				<div>Tracking enabled this load: <?php echo BHP_Analytics_Config::should_render_analytics() ? 'yes' : 'no (blocked)'; ?></div>
				<?php $gate_reason = BHP_Analytics_Config::consent_gate_reason(); ?>
				<?php if ( $gate_reason ) : ?>
					<div style="margin-top:4px;color:#f0b429;">Blocked reason: <?php echo esc_html( $gate_reason ); ?></div>
				<?php endif; ?>
			</div>
			<div id="bhp-analytics-debug-list" style="padding:8px 12px;"><p>No events captured yet this page load.</p></div>
		</div>

		<script>
		(function () {
			'use strict';
			window.dataLayer = window.dataLayer || [];
			var log = [];
			var MAX = 30;
			var originalPush = window.dataLayer.push.bind(window.dataLayer);

			window.dataLayer.push = function () {
				for (var i = 0; i < arguments.length; i++) {
					log.unshift({ time: new Date().toISOString(), payload: arguments[i] });
					if (log.length > MAX) {
						log.pop();
					}
				}
				renderLog();
				return originalPush.apply(window.dataLayer, arguments);
			};

			function renderLog() {
				var listEl = document.getElementById('bhp-analytics-debug-list');
				if (!listEl) {
					return;
				}
				if (!log.length) {
					listEl.innerHTML = '<p>No events captured yet this page load.</p>';
					return;
				}
				listEl.innerHTML = log.map(function (item) {
					var payload = item.payload || {};
					var eventName = payload.event || '(no event key)';
					var safePayload = JSON.stringify(payload, null, 2).replace(/</g, '&lt;');
					return '<div style="margin-bottom:10px;border-bottom:1px solid #333;padding-bottom:8px;">' +
						'<div style="display:flex;justify-content:space-between;color:#8fd3ff;"><span>' + eventName + '</span>' +
						'<span style="color:#888;">' + item.time.slice(11, 19) + '</span></div>' +
						'<pre style="white-space:pre-wrap;word-break:break-word;margin:4px 0 0;color:#c8c8c8;">' + safePayload + '</pre>' +
						'</div>';
				}).join('');
			}

			document.addEventListener('DOMContentLoaded', function () {
				var toggleBtn = document.getElementById('bhp-analytics-debug-toggle');
				var panel = document.getElementById('bhp-analytics-debug');
				var closeBtn = document.getElementById('bhp-analytics-debug-close');
				if (toggleBtn && panel) {
					toggleBtn.addEventListener('click', function () {
						panel.style.display = 'block';
					});
				}
				if (closeBtn && panel) {
					closeBtn.addEventListener('click', function () {
						panel.style.display = 'none';
					});
				}
				// Replay anything already pushed before this script ran
				// (e.g. bhp_init_datalayer's own array, or events pushed
				// during initial page render) so the list isn't empty just
				// because this override attached slightly late.
				(window.dataLayer || []).forEach(function (entry) {
					if (entry && typeof entry === 'object') {
						log.unshift({ time: new Date().toISOString(), payload: entry });
					}
				});
				if (log.length > MAX) {
					log = log.slice(0, MAX);
				}
				renderLog();
			});
		})();
		</script>
		<?php
	}
}

BHP_Analytics_Debug::init();

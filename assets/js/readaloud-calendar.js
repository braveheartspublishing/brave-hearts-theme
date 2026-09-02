/**
 * Brave Hearts — the read-aloud WEEK PICKER's running summary.
 *
 * `CYCLE170-LD-WEEKPICKER`, carrier item 534. Andrew Signore: *"I'm an ICU
 * nurse, and my hospital schedule posts about a month at a time. So pick the
 * week that works for your class, and I'll confirm the exact day and time as
 * soon as my schedule comes out."* ⛔ RELAYED THROUGH `chief-of-staff`, NOT WITNESSED BY
 * THIS FILE'S AUTHOR.
 *
 * ---------------------------------------------------------------------------
 * ⚠ THE FILENAME IS A LEGACY AND IS KEPT ON PURPOSE.
 * ---------------------------------------------------------------------------
 * This file was the month pager at 1.19.331-1.19.334. The month grid it paged
 * is gone; the path, the handle and the enqueue function keep their names so a
 * paint-level release does not read as a plumbing release, and so the three
 * suites that already name this asset keep asserting a real file. The theme
 * version busts the cache. ⛔ IF THIS FILE IS EVER RENAMED, the enqueue in
 * `inc/readaloud-scheduler.php` and every suite that names it must move in the
 * same commit.
 *
 * ---------------------------------------------------------------------------
 * ⭐ THE PICKER IS THE MARKUP. THIS FILE IS ONLY THE SUMMARY LINE.
 * ---------------------------------------------------------------------------
 *
 * Every week card is server-rendered, already in the DOM, already correct and
 * already submittable. Two plain radio groups — `visit_week` and
 * `visit_week_backup` — express "pick one, and optionally a second" with no
 * script at all. With this file absent the form works exactly as it does with
 * it, minus one sentence of feedback.
 *
 * ⛔ NO DATE ARITHMETIC OF ANY KIND HERE. This file never computes a date, never
 *    decides whether a week is offerable, and never creates, enables or
 *    re-values a control the server did not print. It reads two checked radios
 *    and writes a sentence. ⭐ THAT IS THE WHOLE SECURITY ARGUMENT: a bug in
 *    this file can print the wrong sentence, and it cannot make an unofferable
 *    week submittable, because there is no input for an unofferable week in the
 *    document and `bhp_readaloud_scheduler_week_is_offered()` re-derives the
 *    list on POST regardless.
 *
 * ⛔ NO ANALYTICS. Same reasoning as the photo carousel on this same page: a
 *    second uninstrumented-then-instrumented emitter is how two components
 *    start double-firing.
 *
 * ---------------------------------------------------------------------------
 * ⭐⭐ 1.19.339 (`CYCLE170-LD-FINAL2`, carrier item 562) — THE MONTH TABS.
 * ---------------------------------------------------------------------------
 * This file gained a second job and did NOT gain a second nature. The month
 * strip and its panels are server-rendered as real `<a href="#panel-id">`
 * anchors over panels carrying no `hidden` attribute, so with this file absent
 * all twelve week cards are on the page and every anchor jumps to its month.
 * ⭐ THIS FILE ADDS ROLES, ARROW KEYS AND ONE `hidden` ATTRIBUTE — it still
 * creates nothing, still computes no date, and still cannot make an unofferable
 * week submittable.
 *
 * ⛔ IT ALSO MOVES `required` ONTO THE VISIBLE PANEL'S RADIOS, which is a real
 *    browser defect being handled rather than tidiness. See the block comment
 *    beside `syncRequired()`; removing it breaks submission SILENTLY, with the
 *    only symptom in the console.
 */
(function () {
	'use strict';

	function init(root) {
		var summary = root.querySelector('[data-bhp-cal-summary]');
		if (!summary) {
			return;
		}

		var firstPrefix  = summary.getAttribute('data-bhp-cal-summary-first') || '';
		var backupPrefix = summary.getAttribute('data-bhp-cal-summary-backup') || '';

		/**
		 * The visible label text of the card a given radio belongs to.
		 *
		 * ⛔ IT COMES OFF THE CARD THE SERVER PRINTED, NOT OUT OF A FORMATTER IN
		 *    THIS FILE. `bhp_readaloud_scheduler_build_weeks()` already produced
		 *    "Week of October 5" through the theme's one date formatter; reading
		 *    it back cannot disagree with what the server will validate or with
		 *    what the founder's email will say. A `toLocaleDateString` call here
		 *    would be a second formatter that drifts the first time either moves.
		 */
		function cardLabel(input) {
			if (!input) {
				return '';
			}
			var card = input.closest ? input.closest('[data-bhp-week]') : null;
			if (!card) {
				return '';
			}
			var label = card.querySelector('.readaloud-sched__week-label');
			return ((label ? label.textContent : '') || '').replace(/\s+/g, ' ').trim();
		}

		function sync() {
			var first  = root.querySelector('input[name="visit_week"]:checked');
			var backup = root.querySelector('input[name="visit_week_backup"]:checked');

			/*
			 * ⭐ PROGRESSIVE ENHANCEMENT ONLY, AND THE SERVER STILL REFUSES IT.
			 *   Picking the same week for both is a mistake, not an attack, so it
			 *   is cleared here for the visitor's benefit. The handler redirects
			 *   `sameweek` for anyone without this script — the check exists in
			 *   both places and neither is trusted alone.
			 */
			if (first && backup && first.value === backup.value) {
				backup.checked = false;
				backup = null;
			}

			var parts = [];
			if (first) {
				parts.push(firstPrefix + ' ' + cardLabel(first));
			}
			if (backup) {
				parts.push(backupPrefix + ' ' + cardLabel(backup));
			}

			/* Mark the cards so the stylesheet can badge them. `data-` rather than
			   a class, so nothing here collides with a BEM modifier a later paint
			   pass adds. */
			Array.prototype.forEach.call(root.querySelectorAll('[data-bhp-week]'), function (card) {
				card.removeAttribute('data-bhp-week-state');
			});
			if (first && first.closest) {
				var fc = first.closest('[data-bhp-week]');
				if (fc) {
					fc.setAttribute('data-bhp-week-state', 'first');
				}
			}
			if (backup && backup.closest) {
				var bc = backup.closest('[data-bhp-week]');
				if (bc) {
					bc.setAttribute('data-bhp-week-state', 'backup');
				}
			}

			if (!parts.length) {
				summary.textContent = '';
				summary.hidden = true;
				return;
			}
			summary.textContent = parts.join('  ');
			summary.hidden = false;
		}

		root.addEventListener('change', function (e) {
			if (e.target && ('visit_week' === e.target.name || 'visit_week_backup' === e.target.name)) {
				sync();
			}
		});

		/* ═══════════════════════════════════════════════════════════════════
		 * ⭐⭐ THE MONTH TABS — 1.19.339 (`CYCLE170-LD-FINAL2`, carrier item 562).
		 *     AN UPGRADE OF MARKUP THAT ALREADY WORKS, NEVER A CONSTRUCTION.
		 * ═══════════════════════════════════════════════════════════════════
		 *
		 * ⛔⛔ EVERYTHING THIS BLOCK TOUCHES IS ALREADY IN THE DOCUMENT AND
		 *     ALREADY USABLE. The server printed the tab strip as real
		 *     `<a href="#panel-id">` anchors and printed every panel with NO
		 *     `hidden` attribute, so a scriptless browser shows all twelve week
		 *     cards and each anchor jumps to its month. ⭐ THIS FILE ADDS ROLES,
		 *     KEYS AND ONE `hidden` ATTRIBUTE. IT CREATES NOTHING.
		 *
		 * ⛔ THIS FILE MANUFACTURES NO DOM NODE ANYWHERE, and
		 *    `tests/test-cycle170-final.php` asserts that by needle.
		 *
		 *    ⚠️ AND THE NEEDLE IT USES IS THE NAME OF THE DOM METHOD ITSELF, run
		 *    over this file's RAW text. So this comment deliberately does NOT
		 *    write that method name out — a sentence promising the file does not
		 *    call it would put the word in the file and turn the assertion red on
		 *    a correct build. ⭐ THAT EXACT DEFECT WAS HIT AND MEASURED DURING THE
		 *    1.19.339 BUILD (two suites, both green again once the word left this
		 *    comment) and it is the SIXTH recorded instance of the naive-needle
		 *    class. Recorded here so the next author does not "improve" the
		 *    wording back into a failure.
		 *
		 *    The security
		 *    argument that has held since 1.19.335 is unchanged: a bug here can
		 *    show the wrong month, and it cannot make an unofferable week
		 *    submittable, because no input for an unofferable week exists in the
		 *    document and `bhp_readaloud_scheduler_week_is_offered()` re-derives
		 *    the list on POST regardless.
		 *
		 * ⛔ NO DATE ARITHMETIC. The months were grouped on the server by
		 *    `bhp_readaloud_scheduler_group_weeks_by_month()`. This file reads
		 *    ids and never parses a date.
		 * ═══════════════════════════════════════════════════════════════════ */
		var tablist = root.querySelector('[data-bhp-monthtabs]');
		var panels  = root.querySelectorAll('[data-bhp-monthpanel]');

		if (tablist && panels.length > 1) {
			var tabs = tablist.querySelectorAll('[data-bhp-monthtab]');

			/**
			 * ⛔⛔ MOVE `required` TO THE VISIBLE PANEL. THIS IS A REAL BROWSER
			 *     DEFECT BEING HANDLED, NOT TIDINESS, AND REMOVING IT BREAKS
			 *     SUBMISSION SILENTLY.
			 *
			 *     A `required` radio inside a `hidden` container cannot be
			 *     focused. When constraint validation fails, Chrome tries to
			 *     focus the first invalid control, cannot, and then REFUSES TO
			 *     SUBMIT THE FORM while logging "An invalid form control with
			 *     name='visit_week' is not focusable" to the console and showing
			 *     the visitor nothing at all. A teacher would press Send and
			 *     watch the page do nothing.
			 *
			 * ⭐ `required` ON A RADIO IS A PROPERTY OF THE GROUP, not of the
			 *    element, so requiring only the visible members still requires
			 *    the answer — and a first choice already picked under another
			 *    tab still satisfies it, because it is still checked and still
			 *    in the form.
			 *
			 * ⛔ THE SERVER REQUIRES IT EITHER WAY. `visit_week` is validated in
			 *    the handler; this only decides where the browser puts its own
			 *    prompt.
			 */
			var syncRequired = function () {
				Array.prototype.forEach.call(
					root.querySelectorAll('input[name="visit_week"]'),
					function (input) {
						var panel = input.closest ? input.closest('[data-bhp-monthpanel]') : null;
						if (panel && panel.hidden) {
							input.removeAttribute('required');
						} else {
							input.setAttribute('required', 'required');
						}
					}
				);
			};

			var activate = function (tab, moveFocus) {
				if (!tab) {
					return;
				}
				var targetId = tab.getAttribute('data-bhp-monthtab');

				Array.prototype.forEach.call(tabs, function (t) {
					var on = (t === tab);
					t.setAttribute('aria-selected', on ? 'true' : 'false');
					/* Roving tabindex: exactly one tab is in the page's tab order,
					   and the arrow keys move between the others. That is the
					   pattern a screen-reader user expects from a tab strip, and
					   it is why Tab does not have to walk past three tabs to
					   reach the cards. */
					t.setAttribute('tabindex', on ? '0' : '-1');
					if (on) {
						t.className = t.className.replace(/\s*\bis-active\b/g, '') + ' is-active';
					} else {
						t.className = t.className.replace(/\s*\bis-active\b/g, '');
					}
				});

				Array.prototype.forEach.call(panels, function (panel) {
					/* ⛔ `hidden`, NOT `display:none` FROM A CLASS. A hidden
					   container still SUBMITS its inputs (only `disabled` stops
					   that), so a first choice picked in October and a backup
					   picked in December both post exactly as they did from the
					   flat list. Nothing here disables anything. */
					panel.hidden = (panel.id !== targetId);
				});

				syncRequired();

				if (moveFocus && tab.focus) {
					tab.focus();
				}
			};

			var moveBy = function (current, delta) {
				var list = Array.prototype.slice.call(tabs);
				var i = list.indexOf(current);
				if (i < 0) {
					return;
				}
				var next = i + delta;
				/* Wrap, which is what the tab pattern does and what a keyboard
				   user reaching the end of three tabs expects. */
				if (next < 0) {
					next = list.length - 1;
				}
				if (next >= list.length) {
					next = 0;
				}
				activate(list[next], true);
			};

			tablist.setAttribute('role', 'tablist');

			Array.prototype.forEach.call(tabs, function (tab) {
				tab.setAttribute('role', 'tab');
				tab.setAttribute('aria-controls', tab.getAttribute('data-bhp-monthtab') || '');

				tab.addEventListener('click', function (e) {
					/* ⛔ The anchor's own jump is suppressed once this file is
					   running, because the panel is about to be revealed in
					   place and a hash jump would scroll the calendar out from
					   under the finger that just tapped it. ⭐ With this file
					   absent the same anchor still jumps, which is the whole
					   no-script fallback. */
					e.preventDefault();
					activate(tab, false);
				});

				tab.addEventListener('keydown', function (e) {
					var k = e.key;
					if ('ArrowRight' === k || 'ArrowDown' === k) {
						e.preventDefault();
						moveBy(tab, 1);
					} else if ('ArrowLeft' === k || 'ArrowUp' === k) {
						e.preventDefault();
						moveBy(tab, -1);
					} else if ('Home' === k) {
						e.preventDefault();
						activate(tabs[0], true);
					} else if ('End' === k) {
						e.preventDefault();
						activate(tabs[tabs.length - 1], true);
					} else if (' ' === k || 'Spacebar' === k) {
						/* Enter already activates an anchor natively; Space does
						   not, and a tab must answer both. */
						e.preventDefault();
						activate(tab, false);
					}
				});
			});

			Array.prototype.forEach.call(panels, function (panel) {
				panel.setAttribute('role', 'tabpanel');
				/* ⛔ The panel takes its accessible name from its TAB once the
				   tabs exist, which is what the pattern specifies. The server
				   pointed it at the month-name paragraph instead, because with
				   no tabs that paragraph is the only name there is. */
				var owner = tablist.querySelector('[data-bhp-monthtab="' + panel.id + '"]');
				if (owner && owner.id) {
					panel.setAttribute('aria-labelledby', owner.id);
				}
				/* A tabpanel is focusable so that a keyboard user can Tab from
				   the tab strip straight into the week cards. */
				panel.setAttribute('tabindex', '0');

				/* ⭐ The visible month name is now duplicated by its own tab, so
				   it becomes screen-reader-only. ⛔ It is NOT removed: with the
				   `aria-labelledby` above it is redundant as a name, but it is
				   still the panel's heading for anyone reading linearly. */
				var name = panel.querySelector('.readaloud-sched__monthname');
				if (name && name.className.indexOf('screen-reader-text') < 0) {
					name.className += ' screen-reader-text';
				}
			});

			/* ⭐ OPEN THE MONTH THE VISITOR IS ALREADY IN, NOT ALWAYS THE FIRST.
			   A failed-validation redirect and a back-button return both come
			   back with a checked first choice; opening October on top of a
			   November pick would look like the pick was lost. */
			var checked = root.querySelector('input[name="visit_week"]:checked')
				|| root.querySelector('input[name="visit_week_backup"]:checked');
			var openPanel = (checked && checked.closest) ? checked.closest('[data-bhp-monthpanel]') : null;
			var openTab = openPanel ? tablist.querySelector('[data-bhp-monthtab="' + openPanel.id + '"]') : null;

			activate(openTab || tabs[0], false);
			tablist.setAttribute('data-bhp-monthtabs-ready', '1');
		}

		root.setAttribute('data-bhp-cal-ready', '1');

		/* Run once on load, so a back-button return or a failed-validation
		   redirect that still carries checked radios shows its summary. */
		sync();
	}

	function boot() {
		Array.prototype.forEach.call(document.querySelectorAll('[data-bhp-cal]'), init);
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
}());

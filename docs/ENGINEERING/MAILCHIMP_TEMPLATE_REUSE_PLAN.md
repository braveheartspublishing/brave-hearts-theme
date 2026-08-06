# Mailchimp Template Reuse Plan

**Do not start this until Educator Email 1 has been manually finished and Andrew has approved it** (see `MAILCHIMP_EDUCATOR1_MANUAL_BUILD_PLAN.md`). This plan describes how to reuse that finished email as the structural template for the other 14 emails, without repeating the manual image/button/footer work from scratch each time.

## Why reuse instead of rebuild

Educator Email 1 will have the exact header, footer, color system, spacing, and button styling that every other email needs. The only things that differ per email are: the hero image, the headline, the body copy, the primary CTA label/destination, and the supporting-value list contents. Duplicating the finished email and swapping only those five things is far faster than rebuilding each one's structure from the Blocks panel.

## Steps

1. **Save Educator Email 1 as a template** (once approved): in the campaign's menu, look for "Save as template" (Mailchimp campaigns can be saved to the account's own Template library). This preserves header, footer, colors, spacing, and block structure for reuse.
2. **For each of the other 14 emails**, instead of building from a blank canvas:
   - Open the target campaign (e.g. Educator Email 2, campaign under automation id `[EDUCATOR_AUTOMATION_ID]` — see `docs-private/MAILCHIMP_INTERNAL_REFERENCE.md`).
   - If the campaign already has content (all 15 do, per the color-system pass), do not start a fresh campaign — instead, manually copy the finished blocks from the template one at a time: header logo block, styled CTA button block, and footer block, replacing the equivalent existing blocks in the target email.
   - Alternatively, if Mailchimp's "Replicate campaign" feature is used to start a *new* campaign, the existing automation node must still point at the original campaign ID — do not replicate into a disconnected standalone campaign, since that would not be wired into the automation. Confirm with the Automations app before using Replicate for anything inside a live journey.
3. **Replace only these five elements per email** (using the Manual Completion Register's per-email intended values):
   - Hero image + alt text (skip if `Resource Blocked` — see register)
   - Headline text
   - Body paragraph copy
   - Primary CTA label + destination URL
   - Supporting-value list contents (where applicable; Retailer/Organization Email 3s don't have one)
4. **Preserve without changes**: header logo + link, footer compliance block (copyright, mailing address, preferences, unsubscribe), the overall color system (`#F7F2E7` outer / `#1F4D36` green / `#D9B44A` gold), and the spacing rhythm.
5. **Sign-off voice per audience** (per `FUNNEL_CONSTITUTION.md`): Parent/Educator/Gift Buyer use "Keep exploring, / Andrew / Brave Hearts Publishing"; Retailer/Organization use "Best, / Andrew / Brave Hearts Publishing."
6. **After each email**: run the same Final QA checklist from the build plan (desktop/mobile preview, alt text present, correct CTA destination, Draft status preserved, Save).

## Order of completion (recommended)

Build in this order so resource-blocked emails don't stall progress:

1. Educator Email 1 (reference, done first)
2. Educator Emails 2-3 (asset: session-plan/interior image, Complete Collection image — both available)
3. Parent Emails 1-3 (asset: guide cover, Chapter 7/interior image, Complete Collection image — all available; Parent 1's CTA-duplication defect must be resolved first, see register)
4. Retailer Emails 1-2 (**Resource Blocked** — wholesale packet doesn't exist yet; use catalog/spine imagery for the visual only, do not fabricate the packet)
5. Retailer Email 3 (no image dependency beyond catalog imagery — can proceed)
6. Organization Emails 1-2 (**Resource Blocked** — Community Reading Kit guide doesn't exist yet; grouped-book imagery only)
7. Organization Email 3 (no blocking dependency — can proceed)
8. Gift Buyer Emails 1-2 (**Resource Blocked** — gift guide doesn't exist yet; do not fabricate a guide cover, use Collection imagery or a restrained text header instead)
9. Gift Buyer Email 3 (no blocking dependency — can proceed)

Emails marked "Resource Blocked" can still receive the structural/color template (header, footer, button styling, colors) — only the hero image and CTA destination remain pending until the underlying asset exists.

## Do not

- Do not fabricate a hero image for any `Resource Blocked` email.
- Do not activate any automation as part of this process — all 5 journeys must remain Draft.
- Do not change any audience's trigger tags, If/Else conditions, or coupon codes while doing this visual work — those are content-neutral automation mechanics documented separately in `FUNNEL_CONSTITUTION.md` and the Manual Completion Register.

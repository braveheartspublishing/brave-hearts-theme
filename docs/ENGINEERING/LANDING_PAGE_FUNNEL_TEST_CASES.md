# Funnel Test Cases — All 5 Audiences (not yet executed)

**None of these test cases have been run.** Prepared ahead of staging
deployment per Andrew's 2026-07-16 instruction. Real campaign IDs,
automation IDs, and coupon codes are never written here — use the
placeholders already established in `docs-private/MAILCHIMP_INTERNAL_REFERENCE.md`.

## Test-contact policy (read before running any test)

**Use a distinct test email per audience — do not reuse the same address
across audiences.** Confirmed from the code (`inc/mailchimp.php`,
`bhp_handle_mailchimp_signup()`): tags are *added* to a Mailchimp contact
via `update_list_member_tags()`, not replaced, and Mailchimp journeys
trigger off a tag being applied to a contact regardless of what other tags
that contact already has. If the same email signed up on, say, the Parent
page and then the Retailer page, that one contact would carry **both**
audience tags and could enter both journeys simultaneously — the
architecture does not defend against this by email address, only by tag
name. This is not itself a defect (no real customer is expected to sign up
identically across 5 different audience pages), but it means **test
contacts must be kept separate per audience** to get a clean, uncontaminated
read of each journey. Suggested pattern: `[TEST_CONTACT_PREFIX]+parent@[TEST_DOMAIN]`,
`+educator@`, `+gift@`, `+retailer@`, `+organization@` (real values in the
private reference doc, not here).

## Per-audience test case template (14 steps)

Applies identically to Parent, Educator, Gift Buyer, Retailer, Organization
— substitute the audience-specific values from the table below each step.

1. **Open the landing page** on staging (not production, except for Parent
   which is already live) — confirm it loads with no PHP fatals/console errors.
2. **Verify production-facing copy** — hero headline, primary/secondary CTA
   labels, FAQ count, trust section, no leftover placeholder text (already
   code-verified this session; re-confirm visually once deployed).
3. **Submit a unique test address** (per the policy above) through the
   page's lead-magnet form.
4. **Verify correct audience tag** applied to the test contact in Mailchimp
   (tag name only — no real campaign/automation ID needed to check this).
5. **Verify correct journey entry** — the test contact enters the one
   correct audience automation and no other.
6. **Verify Email 1** — correct Subject/Preview/body reaches the test
   contact (or is visible via Mailchimp's own preview) with the correct
   resource link.
7. **Verify resource URL** — the link in Email 1 (or the page's own CTA, for
   audiences where the PDF exists) resolves to a real, working file — not a
   placeholder, not a 404.
8. **Verify analytics event** — the page's `*_landing_view` dataLayer event
   fires once per page load, and `lead_form_view`/`lead_form_start` fire
   from the form as expected (GTM Preview/GA4 DebugView).
9. **Verify no cross-audience journey** — confirm the test contact's tag
   list contains only this audience's tags, and no other audience's
   automation shows this contact as a participant.
10. **Create a qualifying purchase** using the same test contact (one core
    product or a Complete Collection, whichever the audience's commercial
    path emphasizes) — via the site's normal checkout, not a database edit.
11. **Verify purchaser suppression** — confirm the `Customer - Purchased`
    tag is applied to the test contact by the `Global - Tag Purchasers`
    automation, and that the audience journey's If/Else gate correctly
    routes this now-tagged contact to the purchaser branch.
12. **Verify the coupon branch is skipped** — for Parent/Gift Buyer/Educator
    (the 3 audiences with a coupon in Email 3), confirm the purchaser branch
    exits without receiving the coupon email. Retailer/Organization have no
    coupon branch to skip (inquiry-led Email 3 for both).
13. **Verify the intended commercial CTA** — Complete Collection purchase
    path (Parent/Educator/Gift Buyer/Organization) or wholesale inquiry path
    (Retailer) behaves as designed post-purchase.
14. **Document pass/fail** with the specific failure point if any step fails
    — do not mark a test case "passed" if any of steps 1–13 could not be
    directly observed.

## Per-audience specifics

| Audience | Lead magnet | Tag(s) to verify | Coupon branch applies? | Commercial CTA to verify |
|---|---|---|---|---|
| Parent | Reluctant Reader Adventure Kit (live) | `Reluctant Reader Adventure Kit`, `Audience: Parent/Grandparent` | Yes — `[PARENT_COUPON_CODE]` | Complete Collection |
| Educator | Adventure Learning Toolkit (live) | `Adventure Learning Toolkit`, `Audience: Educator` | Yes — `[EDUCATOR_COUPON_CODE]` | Complete Collection |
| Gift Buyer | Meaningful Gift Guide (Awaiting Asset — steps 6-7 cannot pass yet) | `Meaningful Gift Guide`, `Audience: Gift Buyer` | Yes — `[GIFT_BUYER_COUPON_CODE]` | Complete Collection |
| Retailer | Wholesale Guide (Awaiting Asset — steps 6-7 cannot pass yet) | `Wholesale Guide`, `Audience: Retailer` | No — inquiry-led | Wholesale inquiry (`/contact/`) |
| Organization | Community Reading Kit (Awaiting Asset — steps 6-7 cannot pass yet) | `Community Reading Kit`, `Audience: Organization` | No — inquiry-led | Complete Collection (bulk framing) + partnership inquiry |

**Honest status:** Gift Buyer, Retailer, and Organization test cases cannot
fully pass right now regardless of deployment — steps 6/7 (Email 1 resource
link, resource URL) have no real asset to point to yet. Run steps 1-5, 8-9,
and 12-14 for those three audiences now; defer 6-7, 10-11 until each PDF
exists. Parent and Educator can run all 14 steps once staging access exists
(Educator already had a controlled version of this test in a prior session
per `MAILCHIMP_MANUAL_COMPLETION_REGISTER.md` — Parent has not yet had a
full end-to-end purchase-suppression test specifically on its own journey).

# Educator Email 1 — Manual Build Plan (Reference Email)

**Purpose:** Educator Email 1 (Mailchimp campaign id `[EDUCATOR_EMAIL_1_CAMPAIGN_ID]`, in automation "Educators — Acquisition Funnel," id `[EDUCATOR_AUTOMATION_ID]` — real values in the private, gitignored `docs-private/MAILCHIMP_INTERNAL_REFERENCE.md`) is the structural reference for all 15 Mailchimp emails. Everything reliably achievable through browser automation is already done (see `MAILCHIMP_MANUAL_COMPLETION_REGISTER.md`). This document is the exact, step-by-step manual pass for Andrew to finish it in one controlled browser session — roughly 15-20 minutes.

## Current state (verified 2026-07-16)

- Compact header: Brave Hearts logo, linked to homepage, no nav menu — done.
- Headline: "Your Adventure Learning Toolkit Is Ready" — done.
- Body copy: accurate, no stale "still finishing" language — done.
- Primary CTA: text link "Download the Adventure Learning Toolkit" → verified working PDF URL — done, functional.
- "Inside the toolkit" supporting-value list (6 items) — done, plain text (not yet a styled block).
- Sign-off: "Keep exploring, / Andrew / Brave Hearts Publishing" — done.
- Footer: compliant (copyright, mailing address, preferences, unsubscribe) — done.
- **Not done (this document):** hero image, styled CTA button, footer duplicate-logo removal, alt text, final visual QA.

## Manual steps

### 1. Add the hero/resource image
1. Open the campaign: Mailchimp → Automations → Educators - Acquisition Funnel → click the first "Send email" node → **Edit Email Content**.
2. In the canvas, click just below the header logo (above the "Your Adventure Learning Toolkit Is Ready" headline).
3. Drag an **Image** block from the left panel to that position.
4. Click **Add Image** → upload the approved toolkit cover asset (source: `assets/images/handoff/educator-toolkit-cover.webp` in the theme repo, or ask Andrew for the latest approved cover if one has since replaced it).
5. Set **Alt text** to exactly: `Cover of The Adventure Learning Toolkit for The Mariana Trench`
6. Set image width to fit the ~600px content column; keep it centered.

### 2. Style the primary CTA as a button
1. Click directly on the "Download the Adventure Learning Toolkit" text link to select the Text block containing it.
2. Do **not** delete the existing text link until the new button is confirmed working (keeps a fallback CTA at all times).
3. Drag a **Button** block from the left panel to just below that paragraph.
4. Set the button's **Link** field to the exact same verified PDF URL already used by the working text link (visible by clicking the text link and checking "Edit link").
5. Double-click the button and type exactly: `Download the Adventure Learning Toolkit`
6. In the button's **Styles** tab, set:
   - Background: `#1F4D36`
   - Text color: `#FFFFFF`
   - Border: 2px, `#D9B44A`
   - Shape: rounded (small radius, ~6px)
7. Once the button renders correctly with the right label and works when clicked (use **Preview link**), delete the original plain text link so only one CTA remains.

### 3. Format "Inside the toolkit" as a visual block
1. Select the six list lines (Read-aloud guidance… through …Deep-Sea Field Journal).
2. Apply the bullet-list formatting button in the text toolbar.
3. Optionally wrap this text in its own Text block with:
   - Block background color: a pale cream or pale green tint (e.g. `#F0EBDD` or a very light tint of `#1F4D36`)
   - Padding: 20px on all sides

### 4. Remove the duplicate footer logo
1. Scroll to the Footer block at the bottom.
2. Click the Footer block to open its Content panel.
3. Toggle **Logo** off (this is the field labeled "Logo" near the top of the Footer's Content tab — it currently shows the same black-square Brave Hearts logo a second time, directly below the sign-off).
4. Confirm only the top header logo remains visible in the email.

### 5. Final QA
1. Toggle the desktop/mobile preview icons at the top of the canvas; confirm no overflow or awkward line breaks at either size.
2. Click **Preview** → **Enter preview mode** to see it rendered as an actual email.
3. Confirm the image has alt text (view with images disabled in the preview, or inspect the block's alt-text field again).
4. Confirm the button's destination opens the correct PDF (use "Preview link").
5. Leave the campaign status as **Draft** — do not activate the automation.
6. Save.

## Reference colors (for consistency with the rest of the site)

| Element | Color |
|---|---|
| Outer email background | `#F7F2E7` |
| Inner content card | `#FFFFFF` or near-white |
| Primary green (button, headings) | `#1F4D36` |
| Gold accent (button border) | `#D9B44A` |
| Body text | Dark charcoal (`#2A2A2A` or similar) |

## When this is done

Once Andrew confirms Educator Email 1 renders correctly on desktop and mobile with the real image and styled button in place, mark it **Fully Ready** in the Manual Completion Register and proceed to `MAILCHIMP_TEMPLATE_REUSE_PLAN.md` to propagate the same structure to the remaining 14 emails.

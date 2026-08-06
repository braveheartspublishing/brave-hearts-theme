# 9-Breakpoint Landing Page QA Sheet (not yet executed)

**None of the checkboxes below have been executed.** This is an executable
test sheet prepared ahead of staging deployment, per Andrew's 2026-07-16
instruction — it must not be marked passed until the deployed staging pages
are actually viewed at each breakpoint. Fill in Pass/Fail/Note per cell only
after real observation.

**Pages covered:** Parent (`/reluctant-reader-adventure-kit/`), Educator,
Gift Buyer, Retailer, Organization (4 staging-only `page-audience-*.php`
templates).

**Breakpoints:** 320px, 375px, 430px, 768px (portrait), 1024px (landscape),
1280px, 1440px, 1920px, 2560px.

**Checks per page per breakpoint** (14 checks × 9 breakpoints × 5 pages =
630 individual observations — use the compact grid below, one row per
page/breakpoint, one column per check, fill in P/F/— and a one-line note
only for failures):

| Check | What "pass" means |
|---|---|
| Header | Logo/nav renders without overlap, no horizontal scroll introduced by header alone |
| Hero | Heading + lead paragraph + hero art all visible without clipping, hero CTAs both reachable |
| CTA | Primary and secondary CTA buttons fully visible, tappable (not overlapping other elements) |
| Form | Name/email fields and submit button fit within viewport width, no horizontal scroll from the form |
| Trust section | Stat grid / testimonial renders in a legible stacked or grid layout appropriate to width |
| Collection section | Format toggle, price card, and included-books list render without overlap or truncated prices |
| FAQ | `<details>` accordion opens/closes correctly, question text doesn't clip |
| Footer | Compliance links/copyright render, no overlap with sticky mini-CTA bar |
| No horizontal overflow | `document.documentElement.scrollWidth <= window.innerWidth` at this width |
| No clipping | No text or image cut off by a fixed-height/overflow:hidden container |
| Correct stacking | Multi-column sections collapse to single column at the appropriate breakpoint (matches `audience-landing.css`'s declared breakpoints) |
| Readable text | No text below ~14px effective size, no color-contrast regression |
| Touch-target size | Buttons/links are at least ~44×44px at 320–768px widths |
| Image cropping | Book covers / hero art crop sensibly, no stretching or awkward crop of faces/text |

### Test grid (fill in during actual staging session)

| Page | 320 | 375 | 430 | 768p | 1024l | 1280 | 1440 | 1920 | 2560 |
|---|---|---|---|---|---|---|---|---|---|
| Parent | — | — | — | — | — | — | — | — | — |
| Educator | — | — | — | — | — | — | — | — | — |
| Gift Buyer | — | — | — | — | — | — | — | — | — |
| Retailer | — | — | — | — | — | — | — | — | — |
| Organization | — | — | — | — | — | — | — | — | — |

(`—` = not yet tested. Replace with P (all 14 checks pass) / F (note which
check failed and why) once each cell is actually observed on deployed
staging.)

### Additional non-visual checks (once per page, any breakpoint)

- [ ] Keyboard navigation reaches every interactive element in a sensible order
- [ ] Focus states are visible (no `outline: none` without a replacement)
- [ ] Form labels are read correctly by a screen reader (or at minimum, confirmed programmatically associated — already verified in code, re-confirm live)
- [ ] `prefers-reduced-motion` is respected if any animation exists
- [ ] Empty/invalid/success form states all render the correct message (submit blank, submit invalid email, submit valid email)
- [ ] Every internal link on the page resolves (no 404s)
- [ ] Checkout handoff: clicking the Complete Collection CTA lands on a working `/complete-collection/` page with the correct format pre-selected if passed as a parameter

**Verification method note:** confirm "no horizontal overflow" via
`window.innerWidth` vs `document.documentElement.scrollWidth` in the browser
console at each width, not by eyeballing alone — automation viewport resizes
have been unreliable on this machine in prior sessions (see machine-local
notes) and should be double-checked with the JS eval method.

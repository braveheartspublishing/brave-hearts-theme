# Brave Hearts Publishing Explorer Passport System

## Purpose

The Explorer Passport turns each Charlotte & Henry book into the beginning of a larger reading and learning journey.

Primary promise:

> Become an Official Brave Hearts Explorer.

The Passport should reward reading, connect stories to real places, and give families and classrooms a tangible reason to continue through the series.

Books first. Adventure second. Education always.

## Current Phase 6A foundation

This phase provides website structure only. It does not create a PDF, deliver a file, connect Mailchimp, or activate a download.

### Page templates

- Explorer Passport Landing Page: full ecosystem overview and signup invitation.
- Explorer Passport Lead Magnet Page: focused acquisition page.
- Explorer Passport Download Success Page: future confirmed-delivery destination.
- Explorer Passport Thank-You Page: future post-signup confirmation and next steps.

### Reusable components

- Explorer Passport card: registry-driven feature card.
- Download CTA: approved-download or disabled-placeholder state.
- Existing lead magnet signup: provider-neutral acquisition form with Explorer Passport segmentation.

### Central registries and filters

- bhp_get_explorer_passport_features returns the five Passport feature definitions.
- bhp_explorer_passport_features can extend or replace feature definitions.
- bhp_explorer_passport_download_url supplies the approved future asset URL.
- bhp_explorer_passport_download_ready must explicitly return true before the download becomes interactive.
- The Explorer Passport remains registered as a parents_families lead magnet in the acquisition foundation.

## Visitor journey

1. A visitor discovers Brave Hearts through Amazon, a search result, a book page, a Learning Hub article, a teacher resource, or a shared recommendation.
2. The page invites the visitor to continue the adventure with the Explorer Passport.
3. The landing page explains that the Passport connects reading to maps, achievements, and future rewards.
4. The visitor sees the five planned feature areas without being promised an unavailable file.
5. The visitor chooses to join the Adventure Club.
6. Until provider integration is approved, the form remains visibly disabled.
7. After activation, confirmed subscribers proceed through the signup and download journey.
8. The Passport guides the reader back to the books and into future real-world learning.

## Signup journey

### Current placeholder state

1. Visitor reaches an Explorer Passport signup component.
2. The form displays the Brave Hearts offer and email field.
3. Hidden segmentation records audience_type as parents_families, lead_magnet as explorer_passport, and source_page as the current page.
4. The provider action remains a placeholder.
5. Input and submission controls remain disabled.
6. A visible note explains that the provider connection is pending.

### Future configured state

1. Visitor enters an email address and any approved optional fields.
2. The server validates email, consent, controlled values, and source information.
3. Mailchimp records consent and begins confirmation if double opt-in is enabled.
4. The subscriber receives the Adventure Club welcome message.
5. The Explorer Passport delivery message links to the approved download success page or secure asset.
6. The contact receives the Adventure Club, Parent, and relevant lead-magnet tags.
7. Only high-intent teacher, school, media, or partnership actions should create HubSpot relationship records.

## Download journey

### Current placeholder state

- Every download CTA uses the centralized placeholder URL.
- Links are visually disabled, removed from keyboard order, and noninteractive.
- Page markup includes a clear implementation comment.
- No PDF or fake delivery is implied.

### Future approved state

1. Create and approve the accessible Explorer Passport PDF.
2. Store it in an approved, stable asset location.
3. Supply the URL through bhp_explorer_passport_download_url.
4. Return true through bhp_explorer_passport_download_ready only after file, permissions, analytics, and delivery testing pass.
5. Use the thank-you page immediately after signup.
6. Use the download success page after consent or confirmed delivery, depending on the final Mailchimp flow.
7. Test desktop, mobile, keyboard, screen-reader, expired-link, and unavailable-file states.

## Passport feature ecosystem

### World Explorer Map

Purpose: show every real destination in the Charlotte & Henry series.

Future behavior:

- Add one destination marker per published book.
- Connect each marker to its book and Learning Hub content.
- Keep unpublished destinations hidden until launch approval.
- Offer printable map expansions as the series grows.

### Adventure Stamps

Purpose: give readers a tangible mark for completing a destination adventure.

Future behavior:

- One core stamp per book.
- Optional activity stamps for science, geography, conservation, or courage challenges.
- Consistent stamp dimensions for printing and cutting.
- No collection mechanic should require sharing children's personal information.

### Reading Achievements

Purpose: celebrate progress without turning reading into pressure.

Potential achievements:

- First Charlotte & Henry book completed.
- Entire live series completed.
- Read aloud with a family member.
- Completed a Learning Hub activity.
- Learned and used new vocabulary.
- Shared one real-world fact from a book.

Achievements should reward curiosity, persistence, and conversation rather than speed or competition.

### Explorer Certificates

Purpose: provide a meaningful completion moment for families and classrooms.

Future certificate types:

- Official Brave Hearts Explorer.
- Mariana Trench Explorer.
- Mount Everest Explorer.
- Amazon Rainforest Explorer.
- Classroom Exploration Team.
- Series Completion Certificate.

Certificates should have optional blank name and date fields completed offline. Do not collect children's names through website forms.

### Future Adventure Badges

Purpose: create a scalable visual reward system for future books and educational extensions.

Badge categories may include:

- Destination badges.
- Science badges.
- Wildlife badges.
- Conservation badges.
- Courage badges.
- Explorer-history badges.
- Classroom collaboration badges.

Each badge needs a unique slug, approved name, visual asset, earning rule, related book, printable version, and accessibility description.

## Future badge system

Recommended badge record:

| Field | Purpose |
| --- | --- |
| badge_key | Stable machine-readable identifier |
| title | Reader-facing badge name |
| description | Clear earning requirement |
| category | Destination, science, wildlife, conservation, courage, or classroom |
| book_id | Related WooCommerce product or book record |
| destination | Real-world place |
| image_id | Approved WordPress media attachment |
| printable_asset | Approved printable badge URL |
| status | Draft, scheduled, or published |

Badges should remain content-driven. Adding a future book should require adding records and assets, not rebuilding page templates.

## Book completion rewards

A book completion reward should always reinforce the book rather than distract from it.

Recommended loop:

1. Read or finish a Charlotte & Henry book.
2. Complete one short reflection, fact, map, or activity prompt.
3. Add the destination stamp to the Passport.
4. Claim the related achievement or certificate offline.
5. Explore a related Learning Hub article.
6. Choose the next book.

Potential book-linked rewards:

- The Mariana Trench: deep-ocean stamp, ocean-zones activity, conservation badge.
- Mount Everest: mountain stamp, elevation activity, teamwork badge.
- Amazon Rainforest: rainforest stamp, ecosystem activity, biodiversity badge.

Final rewards must match the published book content and approved educational materials.

## Future printable expansion

The Passport can expand through modular printable packs:

- Core Explorer Passport booklet.
- World map insert.
- Destination stamp sheets.
- Book-specific activity pages.
- Reading achievement tracker.
- Explorer certificate pack.
- Classroom group Passport.
- Homeschool unit-study pages.
- Vocabulary cards.
- Adventure journal pages.
- Seasonal exploration challenges.

Use stable page sizes, printer-safe colors, accessible reading order, generous writing space, and low-ink alternatives. Each printable needs a version number and review owner.

## Content and page management

Recommended WordPress pages and slugs:

| Page | Suggested slug | Template |
| --- | --- | --- |
| Explorer Passport | explorer-passport | Explorer Passport Landing Page |
| Passport signup | explorer-passport-signup | Explorer Passport Lead Magnet Page |
| Thank you | explorer-passport-thank-you | Explorer Passport Thank-You Page |
| Download success | explorer-passport-download | Explorer Passport Download Success Page |

The thank-you and download pages should not be indexed until the final acquisition and delivery strategy is approved. Add appropriate SEO controls during integration.

## Accessibility and privacy

- Keep headings and section labels semantic.
- Preserve keyboard navigation and visible focus states.
- Provide meaningful image alternatives.
- Ensure printable files have logical reading order and sufficient contrast.
- Do not collect children's names, ages, school details, or achievement histories through public forms.
- Adult subscribers control email consent and delivery.
- Keep all placeholder controls disabled until they are functional.

## Activation checklist

1. Approve Explorer Passport content and visual design.
2. Create and accessibility-check the PDF.
3. Approve the Explorer Passport landing-page copy.
4. Configure the Mailchimp form endpoint and consent behavior.
5. Build the delivery and welcome email.
6. Configure the thank-you and download redirects.
7. Add the approved download URL and ready flag.
8. Test every placeholder and configured state.
9. Add privacy-safe acquisition measurement.
10. Publish only after content, legal, accessibility, and delivery review.

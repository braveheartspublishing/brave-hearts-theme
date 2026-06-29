# Brave Hearts Publishing Customer Acquisition Blueprint

## Purpose

Build an audience Brave Hearts Publishing owns while keeping books at the center of every journey. The system should help parents find the right book, help teachers use the books successfully, and give curious readers a reason to return.

This phase creates the website-side contract only. Mailchimp, HubSpot, asset delivery, consent records, and production endpoints are not connected yet.

## Guiding principle

Books first. Adventure second. Education always.

A signup should offer genuine value, identify the visitor's needs, and lead naturally back to Charlotte & Henry. It must not imply that an unavailable resource will be delivered.

## Website form contract

Every form sends these core fields:

| Field | Purpose | Allowed examples |
| --- | --- | --- |
| email | Subscriber address | Provider-validated email |
| audience_type | Primary segmentation | parents_families, teachers, general_readers |
| lead_magnet | Requested resource or offer | Registry key or empty |
| source_page | First-party acquisition source | Canonical page URL |

Provider-specific hidden fields may be added later, but they must not replace these names. Forms remain disabled until the bhp_signup_form_action filter returns a real, approved endpoint.

## Component inventory

- Adventure Club signup: parent/family or general audience acquisition.
- Teacher resource signup: teacher segment and classroom-resource intent.
- Lead magnet CTA: registry-driven resource offer.
- Inline blog signup: contextual conversion from educational content.
- Footer signup: quiet site-wide book-news invitation.
- Base signup form: shared accessibility, segmentation, placeholder safety, and endpoint filter.

## Mailchimp role

Mailchimp should be the permission-based email delivery layer:

- Store subscribed contacts and consent state.
- Apply audience and lead-magnet tags from website fields.
- Deliver approved lead magnets.
- Run welcome, education, launch, and re-engagement sequences.
- Send book announcements and Adventure Club newsletters.
- Manage unsubscribe and suppression behavior.

Mailchimp should not be the source of truth for school relationships, media inquiries, bulk-order opportunities, or partnership pipelines.

## HubSpot role

HubSpot should be the CRM and relationship layer:

- Maintain lifecycle stage and source attribution.
- Track teachers, schools, media contacts, bulk-order prospects, and partners.
- Preserve meaningful contact history and follow-up ownership.
- Record high-intent actions such as school inquiries or bulk-order requests.
- Support future sales and partnership workflows.

Routine newsletter engagement should stay in Mailchimp. Only contacts and events with CRM value should sync to HubSpot.

## Parent and family journey

1. A parent arrives through a book, reading, or search page.
2. The page establishes fit: bridge books, ages 6–9, real science, and readable chapters.
3. A relevant offer appears: Explorer Passport, printable map, or reading guide.
4. The form records parents_families, the selected lead magnet, and source page.
5. Mailchimp confirms consent and delivers the resource.
6. A short welcome sequence introduces the series and helps choose a first book.
7. Later messages provide useful reading ideas, new adventures, and launch access.
8. Product clicks and purchases support aggregate conversion reporting without unnecessary personal profiles.

## Teacher journey

1. A teacher arrives through teacher resources, a book page, a classroom article, or a school referral.
2. The page establishes classroom usefulness: Grades 1–3, read alouds, STEM, vocabulary, discussion, and printables.
3. The teacher requests lesson plans or another classroom resource.
4. The form records teachers, the resource, and source page.
5. Mailchimp delivers the resource and begins a teacher sequence.
6. The sequence demonstrates classroom use and introduces matching books.
7. School visits, bulk orders, or partnership interest become HubSpot records for human follow-up.

## Lead magnet plan

| Lead magnet | Primary audience | Purpose | Current state |
| --- | --- | --- | --- |
| Explorer Passport | Parents / Families | Extend book adventures and encourage repeat engagement | Placeholder |
| Printable Adventure Maps | General Readers / Families | Connect stories to real geography | Placeholder |
| Teacher Lesson Plans | Teachers | Demonstrate classroom value and support book adoption | Placeholder |
| Reading Guides | Parents / Families | Support bridge readers and book selection | Placeholder |

Before activation, each asset needs an approved file, landing-page copy, delivery email, owner, version, and accessibility review.

## Future automations

### Shared welcome

- Confirm signup and consent.
- Deliver the requested resource.
- Explain Brave Hearts Publishing clearly.
- Introduce the Charlotte & Henry books.

### Parent sequence

- Explain how bridge books support ages 6–9.
- Show how to use the printable resource with a child.
- Help the family choose a first adventure.
- Invite them to the Learning Hub.

### Teacher sequence

- Deliver classroom materials.
- Show a 20-minute lesson or read-aloud workflow.
- Introduce vocabulary, STEM, and discussion extensions.
- Offer school visits, bulk orders, or classroom sets.

### Book launch sequence

- Early announcement to engaged subscribers.
- Destination and science preview.
- Cover reveal and classroom angle.
- Launch-day purchase message.
- Follow-up resources after purchase.

### Re-engagement

- Identify sustained inactivity conservatively.
- Offer one genuinely useful resource.
- Ask whether the subscriber wants to remain.
- Respect suppression and unsubscribe status across systems.

## KPIs

Track by audience, lead magnet, source page, and device only where privacy-safe.

| KPI | Definition |
| --- | --- |
| Visitor-to-signup rate | Confirmed signups divided by eligible page sessions |
| Form completion rate | Valid submissions divided by form starts, once measurement is approved |
| Confirmation rate | Confirmed subscribers divided by valid submissions |
| Lead magnet conversion | Confirmed signups attributed to each resource divided by its landing-page sessions |
| Welcome completion | Subscribers reaching the final welcome message without unsubscribing |
| Email click-through | Unique link clickers divided by delivered messages |
| Book-page click rate | Subscribers clicking from email to a book page |
| Purchase conversion | Attributed purchasers divided by confirmed subscribers using an agreed window |
| Teacher resource engagement | Teachers downloading or clicking classroom resources |
| School inquiry rate | Teacher subscribers creating qualified school, visit, or bulk-order inquiries |
| List health | Bounce, complaint, unsubscribe, and inactive-subscriber trends |

Do not optimize for list size alone. A smaller list of parents and teachers who use resources and buy books is more valuable than a large unengaged audience.

## Consent, privacy, and data quality

- Publish an accurate privacy policy before activation.
- Use explicit consent language appropriate to each provider and jurisdiction.
- Do not collect children's personal information through these forms.
- Keep required data minimal.
- Define double-opt-in policy before launch.
- Preserve source attribution without unnecessary browsing history.
- Synchronize unsubscribe and suppression state before dual-system automation.
- Validate all server-side input when a first-party endpoint is introduced.

## Configuration required before activation

1. Approve and host each lead magnet.
2. Choose the Mailchimp audience, tags, groups, and double-opt-in behavior.
3. Build a secure form endpoint or approved provider action.
4. Map fields and validate consent and error handling.
5. Build delivery and welcome automations.
6. Define which contacts and events sync to HubSpot.
7. Configure CRM lifecycle stages and ownership rules.
8. Test success, validation, duplicate, unsubscribe, and provider-failure states.
9. Add privacy-safe analytics only after KPI approval.
10. Enable forms by supplying a real action through bhp_signup_form_action.

# Brave Hearts Publishing Mailchimp + HubSpot Architecture

## Purpose

Prepare Brave Hearts Publishing for permission-based email acquisition and long-term relationship management without connecting APIs, storing secrets, or creating premature automations.

The operating principle is:

> Amazon is where people discover us.  
> Brave Hearts Publishing is where the relationship begins.

Every acquisition decision should also follow this rule:

> Never let a visitor leave without an invitation to continue the adventure.

The invitation must be relevant, useful, honest, and easy to decline.

## System responsibilities

| System | Primary responsibility | Should not become |
| --- | --- | --- |
| Mailchimp | Permission-based subscriber communication, segmentation, lead-magnet delivery, newsletters, and automated email journeys | A school, media, bookstore, or partnership CRM |
| HubSpot | Relationship management for teachers, institutions, media, partnerships, and high-intent opportunities | The routine bulk-newsletter delivery system |
| WordPress | First-party signup experience, consent capture, source attribution, and contextual invitations | A store for provider credentials or a substitute for provider compliance tooling |
| WooCommerce | Product and customer purchase context | The master source for marketing consent |

## 1. Mailchimp strategy

Use one Mailchimp audience named Brave Hearts Publishing.

Adventure Club is the public-facing name subscribers see. The underlying audience should remain a single operational database so unsubscribe state, consent, engagement history, and contact identity are not fragmented across multiple audiences.

Use tags, groups/interests, and saved segments instead of separate audiences:

- Tags describe source, behavior, status, or campaign context.
- Groups/interests represent subscriber-controlled topics.
- Segments are reusable queries combining profile fields, tags, groups, consent, and engagement.

The primary audience field is audience_type with these values:

- parents_families
- teachers
- general_readers

Homeschool families can initially remain within parents_families while receiving the Homeschool tag. If behavior later proves meaningfully different, Homeschool can become a dedicated audience_type after a data review.

## 2. Recommended Mailchimp tags

| Tag | Intended use |
| --- | --- |
| Adventure Club | Applied to every subscriber joining the public Adventure Club program |
| Parent | Parent or family audience |
| Teacher | Teacher or classroom educator |
| Homeschool | Homeschool interest or identity |
| Reader | General reader or nonprofessional subscriber |
| Customer | Confirmed purchaser when consent and matching rules permit |
| School Visit | Expressed interest in or participated in a school visit |
| Blog Subscriber | Acquired through an inline blog form or blog-specific invitation |
| New Release Interest | Requested launch or early-access updates |
| Teacher Resources | Requested teacher-facing material |
| Book 1 Interest | Interest in The Mariana Trench |
| Book 2 Interest | Interest in Mount Everest |
| Book 3 Interest | Interest in Amazon Rainforest or the approved third title |

### Tagging rules

- Apply only tags supported by explicit form context or verified behavior.
- Do not infer Teacher, Parent, or Homeschool status from email domain alone.
- Keep source-page detail in source_page rather than creating one tag per URL.
- Treat Customer as a verified state, not a purchase-intent tag.
- Define a naming owner and change process before adding new tags.
- Review unused and overlapping tags quarterly.

## 3. Recommended Mailchimp groups/interests

Subscriber-facing interests should be concise and optional:

- Charlotte & Henry
- Teacher Resources
- New Books
- Printables
- Lesson Plans
- Adventure Activities

Groups are appropriate when subscribers should be able to manage their preferences. Tags should remain internal and operational.

Recommended preference-center wording:

- Charlotte & Henry adventures
- Resources for teachers and classrooms
- New books and early access
- Free printables
- Lesson plans and discussion resources
- Family adventure activities

## 4. Required form fields

| Field | Type | Purpose |
| --- | --- | --- |
| email | Email | Required subscriber identifier |
| first_name | Text | Optional personalization; avoid making it a barrier to signup |
| audience_type | Controlled value | parents_families, teachers, or general_readers |
| lead_magnet | Controlled value | Resource requested at signup |
| source_page | URL or canonical path | First-party acquisition source |
| consent | Boolean plus recorded language/version | Proof of marketing permission |
| signup_date | Timestamp | Provider-recorded signup time in a documented timezone |

### Field standards

- Validate fields server-side before sending them to a provider.
- Store the consent language version and capture method when the production endpoint is designed.
- Use provider timestamps as the authoritative signup_date when possible.
- Keep first_name optional.
- Do not collect children's personal information.
- Do not place private or sensitive data in tags, group names, or source_page.
- Define allowed values centrally so WordPress, Mailchimp, and HubSpot use the same vocabulary.

## 5. Adventure Club welcome automation

The default Adventure Club sequence contains five emails. Timing should be tested after launch rather than assumed; begin with a calm cadence over roughly 10–14 days.

### Email 1: Welcome + Explorer Passport

Purpose: fulfill the promise immediately and establish trust.

- Welcome the subscriber to the Adventure Club.
- Deliver the Explorer Passport.
- Explain what messages they will receive.
- Reinforce Books first. Adventure second. Education always.
- Offer one clear next step: explore the books.

### Email 2: Meet Charlotte & Henry

Purpose: build emotional connection to the series.

- Introduce Charlotte and Henry.
- Explain the bridge-book reading gap and ages 6–9.
- Show how real science and courage live inside the stories.
- Link to the series page or first-book recommendation.

### Email 3: Free printable adventure activity

Purpose: provide useful value before another purchase invitation.

- Deliver one approved printable activity.
- Show a quick family or classroom use case.
- Connect the activity to a matching book.
- Invite subscribers to manage interests.

### Email 4: The real places behind the books

Purpose: demonstrate the premium adventure-education positioning.

- Feature the Mariana Trench, Mount Everest, or the current destination.
- Share one memorable, accurate fact.
- Link to a Learning Hub article.
- Connect the place naturally to its book.

### Email 5: Explore the complete series

Purpose: make the book-sales invitation clear after trust has been earned.

- Present every live Charlotte & Henry title.
- Explain reading level, formats, and classroom/family fit.
- Recommend where to start.
- Use one direct call to action to explore or purchase the series.

### Welcome automation safeguards

- Exit or suppress unsubscribed and bounced contacts immediately.
- Prevent duplicate enrollment unless a deliberate re-entry policy exists.
- Exclude active teacher-path contacts from redundant generic messages where appropriate.
- Test every lead-magnet link, fallback state, and mobile rendering before activation.

## 6. Teacher automation

Teacher signups should enter a distinct path focused on classroom usefulness and human relationships.

### Email 1: Welcome

- Welcome the educator.
- Confirm the requested resource.
- Explain Brave Hearts classroom fit: Grades 1–3, bridge readers, STEM, and courage.

### Email 2: Free lesson plan

- Deliver the approved lesson plan.
- Explain the learning objective and preparation time.
- Link to the matching book.

### Email 3: Discussion guide

- Deliver or introduce discussion prompts, vocabulary, and comprehension support.
- Include a practical whole-class or small-group workflow.

### Email 4: Read-aloud invite

- Show how to use Charlotte & Henry as a read aloud.
- Invite a reply or approved inquiry form for classroom read-aloud opportunities.
- Create a HubSpot relationship record only when engagement becomes high intent.

### Email 5: School visit / classroom pack follow-up

- Explain school visits, classroom packs, and bulk-order options.
- Offer a clear human-contact path.
- Route qualified replies or inquiry submissions to HubSpot.

### Teacher-path rules

- Apply Teacher and Teacher Resources tags.
- Add group interests selected by the educator.
- Do not send consumer-heavy promotions that ignore classroom context.
- Use HubSpot for personal follow-up, not automated newsletter engagement alone.

## 7. HubSpot strategy

HubSpot is for relationship management with people and organizations where context, ownership, and follow-up matter:

- Teachers
- Schools
- Librarians
- Bookstores
- Media
- Podcasts
- Partnerships
- School visits

Recommended HubSpot objects:

- Contact: the individual person.
- Company: school, district, library, bookstore, media outlet, or partner organization.
- Deal or custom pipeline record: an opportunity requiring follow-up.
- Activity: inquiry, reply, meeting, visit, order discussion, or partnership milestone.

Do not create a HubSpot deal for every newsletter signup or book-page click.

## 8. HubSpot pipeline ideas

A School and Educator Relationships pipeline can begin with these stages:

1. New teacher lead
2. Downloaded resource
3. Replied / engaged
4. Read-aloud scheduled
5. School visit completed
6. Repeat school
7. District opportunity

### Suggested stage definitions

| Stage | Entry condition | Expected next action |
| --- | --- | --- |
| New teacher lead | Teacher submits a qualified resource or inquiry form | Validate identity and intent |
| Downloaded resource | Confirmed resource delivery or meaningful resource engagement | Send useful follow-up, not a sales push |
| Replied / engaged | Direct reply, inquiry, or substantive interaction | Assign an owner and document context |
| Read-aloud scheduled | Date and scope agreed | Confirm logistics and participants |
| School visit completed | Visit delivered | Record outcome and follow-up actions |
| Repeat school | School books another visit, pack, or program | Maintain relationship and identify advocates |
| District opportunity | Multi-school or district-level interest is qualified | Define stakeholders, timeline, and next meeting |

Separate pipelines may later be appropriate for Media and Podcasts, Bookstores and Wholesale, and Strategic Partnerships. Do not create them until volume justifies the operational overhead.

## 9. Sync strategy

### Sync from Mailchimp to HubSpot

Sync only data with relationship or CRM value:

- Email and first name.
- audience_type.
- High-level consent and subscription status required for safe communication.
- Teacher, School Visit, Teacher Resources, or other approved high-intent tags.
- Original lead_magnet and source_page.
- Meaningful direct replies, inquiry submissions, or qualified hand-raise events.
- Selected group interests when they help personalize human follow-up.

### Do not sync routinely from Mailchimp to HubSpot

- Every open or page view.
- Every routine campaign click.
- Low-value automation events.
- Entire campaign histories.
- Temporary operational tags.
- Contacts without CRM relevance solely to inflate HubSpot counts.
- Children's information or inferred sensitive attributes.

### Sync from HubSpot to Mailchimp

Sync only fields needed for compliant email segmentation:

- Confirmed communication preference changes.
- Approved lifecycle or relationship tags such as School Visit.
- Suppression or unsubscribe state where the integration requires it.
- Audience-relevant status such as verified Teacher or Customer.

Do not send private CRM notes, deal values, internal ownership comments, or relationship history into Mailchimp merge fields.

### Source of truth

| Data | Source of truth |
| --- | --- |
| Email subscription and unsubscribe state | Mailchimp after activation |
| Website consent capture event | Secure first-party endpoint and provider record |
| School, media, bookstore, and partnership relationship | HubSpot |
| Product and order data | WooCommerce or commerce platform |
| Page content and acquisition context | WordPress |
| Lead-magnet asset and version | Approved asset repository/documentation |

Resolve conflicts conservatively: an unsubscribe or suppression must win across all systems.

## 10. Customer acquisition philosophy

Amazon is where people discover us. Brave Hearts Publishing is where the relationship begins.

Amazon can create awareness and sales, but Brave Hearts Publishing should provide the durable relationship: trusted guidance for growing readers, useful classroom resources, real-world learning, new-book access, and a recognizable adventure-education community.

Never let a visitor leave without an invitation to continue the adventure.

That invitation can be:

- Explore the books.
- Read about a real destination.
- Download a useful printable.
- Bring a lesson into the classroom.
- Join the Adventure Club.
- Ask about a read aloud or school visit.

The invitation should match the page and the visitor. Do not interrupt a teacher article with an irrelevant consumer offer or turn every educational interaction into an immediate sale.

## What Mailchimp needs next

1. Create the single Brave Hearts Publishing audience.
2. Configure the required merge fields and controlled values.
3. Create the approved tags and groups/interests.
4. Decide double-opt-in and consent-recording policy.
5. Approve the Explorer Passport and other delivery assets.
6. Build and test the five-email Adventure Club sequence.
7. Build and test the separate teacher sequence.
8. Define suppression, duplicate, re-entry, and re-engagement rules.
9. Provide a secure production form endpoint or approved embedded-form action.
10. Complete end-to-end testing before enabling WordPress forms.

## What HubSpot needs next

1. Define contact, company, and pipeline property mappings.
2. Approve the School and Educator Relationships pipeline and stage definitions.
3. Define which Mailchimp tags or events qualify for CRM creation.
4. Set ownership and response-time rules.
5. Decide how schools, districts, libraries, bookstores, media, and podcasts are represented.
6. Define unsubscribe and suppression synchronization.
7. Establish duplicate-contact and company-matching rules.
8. Test a limited teacher-lead flow before broad synchronization.
9. Document retention, permissions, and access roles.
10. Keep API credentials outside WordPress theme code when integration begins.

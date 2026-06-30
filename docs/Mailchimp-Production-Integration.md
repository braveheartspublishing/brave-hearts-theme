# Mailchimp Production Integration

## Architecture

Brave Hearts keeps its existing custom HTML, CSS, template parts, labels, and responsive layout. The theme does not render MC4WP shortcodes or plugin-generated forms.

All acquisition components call template-parts/acquisition/signup-form.php. When MC4WP is active, connected, and its selected form resolves to exactly one audience, the shared form posts to WordPress admin-post.php. Both logged-out and logged-in submissions are handled by inc/mailchimp.php.

The handler uses the API client supplied by Mailchimp for WordPress (MC4WP). The API key remains in MC4WP settings and is never copied into the theme.

Production behavior:

- Single opt-in is explicit: new or returning contacts are sent with status subscribed.
- The configured MC4WP audience is resolved from the existing published MC4WP form.
- Every successful signup receives the Adventure Club tag.
- Theme segmentation fields are sent as Mailchimp merge fields.
- Existing subscribers are updated and can receive the current tag.
- Provider errors are caught and replaced with a friendly theme message.
- No WooCommerce template or Mailchimp for WooCommerce behavior is modified.

Official implementation references:

- MC4WP programmatic subscription example: https://github.com/ibericode/mailchimp-for-wordpress/blob/main/sample-code-snippets/manually-add-email-address-to-mailchimp.php
- Mailchimp add-or-update member guidance: https://mailchimp.com/developer/marketing/guides/create-your-first-audience/
- Mailchimp member-tags endpoint: https://mailchimp.com/developer/marketing/api/list-member-tags/

## Supported custom forms

The shared handler covers every existing Brave Hearts acquisition component:

- Homepage Adventure Club
- Footer signup
- Blog inline signup
- Teacher signup
- Lead magnet signup
- Reusable Adventure Club panel

A form stays disabled with a friendly temporary-unavailable message if MC4WP, its API key, or a single selected audience cannot be resolved. No placeholder submission URL is used.

## Data flow

1. WordPress renders the existing Brave Hearts form.
2. The form receives the internal admin-post.php action, a form-specific nonce, context, audience type, lead magnet, source page, and an invisible honeypot.
3. The visitor submits an email address.
4. The handler verifies the nonce and honeypot and validates the email and local return URL.
5. The handler resolves the single audience selected on the existing MC4WP form.
6. The theme calls MC4WP add_list_member with status subscribed and the mapped merge fields.
7. The theme calls the MC4WP member-tags method with Adventure Club set to active.
8. On success, WordPress redirects back to the exact form and displays a polite success status.
9. Validation, configuration, or API failures redirect to the exact form with a friendly alert. Mailchimp exception text is never rendered.

The POST/redirect/GET flow avoids duplicate browser submissions when a visitor refreshes the page.

## Field mapping

Create these merge fields in the single Mailchimp audience before production testing:

| Theme field | Mailchimp destination | Recommended Mailchimp type | Notes |
| --- | --- | --- | --- |
| email | Core email_address | Email | Required and validated by WordPress and Mailchimp |
| audience_type | AUDIENCE | Text | Values currently include parents_families, teachers, and general_readers |
| lead_magnet | LEADMAG | Text | May be empty for a general/footer signup |
| source_page | SOURCE | Website or Text | Local source URL, limited to 255 characters |

Mailchimp merge tags are case-sensitive in configuration. Use AUDIENCE, LEADMAG, and SOURCE exactly unless the bhp_mailchimp_merge_field_map filter is deliberately changed.

The API call skips validation of unrelated required merge fields so the Brave Hearts forms remain email-first. Unknown merge tags are still rejected by Mailchimp, which is why the three audience fields must exist before the live test.

## Tag mapping

Current production mapping:

| Condition | Active Mailchimp tag |
| --- | --- |
| Every successful Brave Hearts signup | Adventure Club |

Mailchimp creates the tag automatically if it does not already exist. The theme applies tags through the member-tags endpoint, so an existing contact can also receive Adventure Club.

Future tags require configuration only, not a submission-architecture rewrite. Use bhp_mailchimp_signup_tags to append tags based on context, audience type, lead magnet, page, product interest, or another approved signal.

Planned-compatible tag names:

- Teacher
- Explorer Passport
- Customer
- Blog Subscriber
- Book Interest
- School Visit
- Homeschool
- Library

Example future mapping:

    add_filter('bhp_mailchimp_signup_tags', function ($tags, $context, $audience_type, $lead_magnet) {
        if ($context === 'teacher_resources') {
            $tags[] = 'Teacher';
        }

        if ($context === 'inline_blog') {
            $tags[] = 'Blog Subscriber';
        }

        if ($lead_magnet === 'explorer_passport') {
            $tags[] = 'Explorer Passport';
        }

        return $tags;
    }, 10, 4);

Do not add a future tag until its segmentation and automation behavior is approved in Mailchimp.

## Future automation hooks

Filters:

- bhp_mailchimp_form_id: Select a specific published MC4WP form ID. The default of 0 lets MC4WP use its first published form.
- bhp_mailchimp_list_id: Explicitly provide the one audience ID when form-based resolution is not appropriate.
- bhp_mailchimp_merge_field_map: Change or extend theme-field to Mailchimp merge-tag mapping.
- bhp_mailchimp_signup_tags: Add approved tags without editing the submission handler.
- bhp_signup_form_action: Retained for compatibility; defaults to the internal WordPress handler when MC4WP is ready.

Actions:

- bhp_mailchimp_signup_success: Runs after the member and tags have both been accepted. Receives the subscriber response, email, context, audience type, lead magnet, source page, and tags.
- bhp_mailchimp_signup_failed: Runs after a caught provider or runtime failure. Receives the exception and non-email context fields. Do not display the exception to visitors.

These hooks can support approved future welcome automations, resource delivery, analytics, or operational logging. Automation code should live in a site plugin when it is business-critical rather than in a presentation theme.

## Error handling and privacy

- Invalid emails receive a specific, friendly validation message.
- Missing MC4WP configuration receives a temporary-unavailable message.
- Mailchimp/API failures receive a generic retry message.
- Raw API errors, audience IDs, API keys, and exception details are never printed.
- A form-specific nonce prevents cross-site submissions.
- A hidden honeypot rejects simple automated submissions.
- Redirect destinations are validated as local WordPress URLs.
- Status messages use status or alert roles and existing visible focus/contrast conventions.
- Email addresses are not added to redirect URLs.

## Manual production configuration

1. In Mailchimp, confirm the single production audience is the audience selected on the published MC4WP Adventure Club form.
2. Create merge fields with the exact tags AUDIENCE, LEADMAG, and SOURCE.
3. Confirm Adventure Club exists as a tag, or allow the first successful API call to create it.
4. Leave MC4WP double opt-in disabled. The theme sends status subscribed and does not send pending.
5. Confirm the MC4WP API key reports Connected.
6. If the Adventure Club form is not the first published MC4WP form, set bhp_mailchimp_form_id to its post ID in a site-specific plugin.
7. Configure any resource-delivery or welcome automation separately in Mailchimp.
8. Keep Mailchimp for WooCommerce connected; this integration does not change its customer/order synchronization.

## Testing checklist

### Rendering and regression

- [ ] Homepage Adventure Club retains its existing layout at desktop, tablet, and mobile widths.
- [ ] Footer signup renders correctly wherever the component is included.
- [ ] Blog inline signup renders correctly inside a post.
- [ ] Teacher signup retains its existing content and styling.
- [ ] Lead magnet signup retains its existing content and styling.
- [ ] Every email input has a visible label.
- [ ] Keyboard focus remains visible.
- [ ] No inline CSS appears in the rendered forms.
- [ ] Mobile navigation and assets/js/nav.js behave exactly as before.
- [ ] No WooCommerce template file changed.

### Mailchimp delivery

- [ ] Submit a new owned test address through Homepage Adventure Club.
- [ ] Confirm the contact status is Subscribed, not Pending.
- [ ] Confirm Adventure Club is active on the contact.
- [ ] Confirm AUDIENCE contains parents_families.
- [ ] Confirm LEADMAG contains explorer_passport for the homepage form.
- [ ] Confirm SOURCE contains the homepage URL.
- [ ] Repeat with Teacher and confirm AUDIENCE and LEADMAG values.
- [ ] Repeat with Blog inline, Footer, and Lead magnet contexts.
- [ ] Submit an already-subscribed owned address and confirm Adventure Club remains active.
- [ ] Confirm no duplicate audience contact is created.

### Error handling

- [ ] Submit an invalid email and confirm the friendly validation alert.
- [ ] Temporarily disconnect MC4WP on staging and confirm the forms disable safely.
- [ ] Temporarily use a nonexistent merge tag on staging and confirm no API text is exposed.
- [ ] Confirm refreshing a result page does not repeat the POST.
- [ ] Confirm the response returns to the exact submitted form.
- [ ] Confirm browser URLs never contain the submitted email address.
- [ ] Review server/MC4WP logs privately for test failures.

## Deployment acceptance

Production acceptance requires at least one successful owned-address submission from each form context, verification of the Adventure Club tag and all three merge fields in Mailchimp, and confirmation that the contact status is Subscribed. Static theme validation cannot prove external Mailchimp delivery without executing those tests on the connected WordPress environment.

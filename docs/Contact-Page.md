# Brave Hearts Publishing Contact Page

## WordPress assignment

1. Create or edit the WordPress page named Contact.
2. Use the slug contact.
3. Select Brave Hearts Contact Page in the page-template selector.
4. Publish or update the page.
5. Confirm header, footer, Teacher Resources, Books, and About links point to /contact/.

Because the file is named page-contact.php, WordPress can select it automatically for the contact slug.

## Visitor paths

The page supports:

- General Questions
- Read-Aloud Visits
- Schools, Libraries & Media

Read-aloud links use the query value inquiry=read-aloud. School and library links use inquiry=school-library. The form component validates these values against its controlled inquiry list before selecting an option.

## Form fields

The provider-neutral contact form contains:

- Name
- Email
- Organization / School
- Role
- Inquiry Type
- Message
- Hidden source_page

Name, email, inquiry type, and message are required when the form is enabled. The page explicitly asks visitors not to include private or sensitive student information.

## Placeholder behavior

The form action remains disabled until bhp_contact_form_action returns a real approved endpoint.

While unconfigured:

- The action points to a nonfunctional placeholder URL.
- Inputs, selects, message field, and submit button are disabled.
- A visible note directs visitors to the direct email.
- Page markup includes an implementation comment.
- No fake success or message delivery is implied.

## Provider integration required

Before enabling the form, implement and test:

1. A secure external or first-party form action.
2. Server-side validation and normalization for every field.
3. Spam and abuse protection that remains accessible.
4. A nonce and request-origin policy if WordPress handles submissions.
5. Recipient routing by inquiry type.
6. Safe notification templates that do not expose private data.
7. Clear success, validation-error, provider-error, and retry states.
8. Privacy language, retention policy, and consent requirements.
9. Rate limiting and logging appropriate to the chosen handler.
10. HubSpot routing rules for qualified school, library, media, and partnership inquiries.

Do not place provider credentials in theme files.

## Direct contact information

The template currently uses Asignore19@icloud.com, matching the existing Brave Hearts footer contact. Override it with:

- bhp_contact_email

No phone number or mailing address is displayed or invented.

## Optional fields

- bhp_contact_form_action supplies a requested action before the global filter.
- bhp_contact_hero_image_id supplies an approved hero attachment.
- bhp_contact_email overrides the displayed direct email.

Each value also has a bhp_contact_field_{key} filter.

## Manual setup before launch

- Create the Contact page and assign the template.
- Confirm the direct email address.
- Optionally add an approved hero image.
- Verify read-aloud and school inquiry links.
- Configure the provider action only after the complete integration checklist passes.
- Test keyboard, screen-reader, mobile, validation, spam, and error behavior in live WordPress.
- Confirm response ownership and expected response times for general, teacher, school, library, and media inquiries.

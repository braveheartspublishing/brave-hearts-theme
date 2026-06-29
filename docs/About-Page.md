# Brave Hearts Publishing About Page

## WordPress assignment

1. Create or edit the WordPress page named About.
2. Use the slug about.
3. In Page Attributes or the template selector, choose Brave Hearts About Page.
4. Publish or update the page.
5. Add the page to the primary and footer navigation where appropriate.

The file name page-about.php also allows WordPress to select the template automatically when the page slug is about.

## Founder and hero images

- The page featured image is used as the default Andrew Signore founder image.
- The public custom field bhp_about_founder_image_id overrides the featured image when it contains a valid attachment ID.
- The public custom field bhp_about_hero_image_id supplies the optional cinematic hero image.
- Without a founder image, the template displays an accessible AS placeholder rather than a fake photograph.

## Editable content

One-off copy and URLs can be overridden with public custom fields using the bhp_about_ prefix. Common examples include:

- bhp_about_hero_title
- bhp_about_hero_text
- bhp_about_hero_primary_url
- bhp_about_hero_secondary_url
- bhp_about_mission_title
- bhp_about_mission_text_1
- bhp_about_mission_text_2
- bhp_about_founder_title
- bhp_about_founder_intro
- bhp_about_founder_text_1
- bhp_about_founder_text_2
- bhp_about_audiences_title
- bhp_about_audiences_intro

Each field also has a filter named bhp_about_field_{key}. The four Why These Books Exist cards can be replaced through bhp_about_book_values.

## Manual review before launch

- Upload and select an approved founder photograph with accurate alternative text.
- Optionally upload a hero image suitable for text overlay.
- Confirm that /books/, /teachers/, and the homepage Adventure Club anchor exist.
- Review Andrew Signore biography copy for final approval.
- Check the page on mobile and with keyboard navigation in the live WordPress environment.

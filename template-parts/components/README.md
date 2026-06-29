# Homepage component library

These classic WordPress template parts are intentionally content-agnostic. Phase 3B should assemble them from page data, theme options, custom fields, or query results rather than placing production copy inside the component files.

## Components

- hero.php: cinematic opening with a required title, optional image, supporting copy, and two actions. The primary action should lead to books.
- featured-books.php: section wrapper for an array of book-card argument arrays.
- book-card.php: cover, title, age/review context, description, price, and purchase-oriented link.
- feature-card.php: reading, adventure, or education value proposition.
- hub-card.php: visual gateway into adventure or Learning Hub topics.
- teacher-resources-cta.php: classroom-resource promotion with a semantic list and optional image.
- testimonial-card.php: semantic quotation and attribution.
- newsletter-signup.php: provider-neutral email form. Supply form_action and any required hidden_fields during integration; the action can also be supplied through the bhp_newsletter_form_action filter.
- final-cta.php: low-pressure closing action with primary and secondary links.

## Usage

Render components with the WordPress argument-aware template-part API, passing a prepared argument array as the fourth argument to get_template_part().

Every section component generates a unique accessible heading relationship unless an explicit id is supplied. Images use registered responsive WordPress sizes, output is escaped by context, and empty required arguments prevent empty sections from rendering.

## Phase 3B assembly order

Keep the Design Bible hierarchy visible in the final composition: books and a clear shopping path first; adventure and Learning Hub discovery second; teacher value, trust, email, and final CTA after that.

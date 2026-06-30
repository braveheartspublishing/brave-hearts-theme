# Brave Hearts Publishing — Theme Freeze Recommendation

Prepared: June 30, 2026

## Executive recommendation

Do not freeze for DNS migration yet.

The theme code is substantially complete, but the rendered WordPress site cannot be reviewed because no staging URL, database, product inventory, media library, or production-equivalent plugin configuration is available. The configured public domain still serves the legacy Squarespace website.

## Completion estimate

| Area | Estimate | Basis |
| --- | ---: | --- |
| Theme implementation | 92% | Core templates, responsive styles, navigation, accessibility foundations, commerce support, and safe fallback states exist |
| Repository quality | 90% | Four remediation/quality sprints completed; remaining items are mostly integration, consolidation, and validation work |
| Executive review package | 55% | Index and source-based reports complete; screenshots, Lighthouse, rendered crawl, and media/product inventories blocked |
| Launch readiness | 65% | Theme is mature, but live content, legal, commerce, forms, redirects, SEO, analytics, and staging QA remain unverified |

## Freeze conditions

Freeze the theme only after all of the following are complete:

- WordPress staging URL and access are supplied.
- Complete public URL, product, taxonomy, menu, form, and media inventories are exported.
- Desktop and mobile full-page capture sets are approved.
- Lighthouse and accessibility checks are run on Home, Books, Teachers, and Contact.
- Every internal link, CTA, menu, footer link, product path, Hub path, and redirect is tested.
- Shop, Cart, Checkout, My Account, taxes, shipping, payments, email, inventory, and rollback are approved in safe test mode.
- Privacy Policy, Terms, consent language, Mailchimp behavior, Contact handling, and child-directed privacy decisions are approved.
- Explorer Passport pages are either complete or intentionally unpublished/noindexed.
- SEO plugin configuration, sitemap, canonicals, robots behavior, analytics, and verification services are approved.
- PHP lint, WordPress Coding Standards, browser/device, keyboard, and screen-reader checks pass.
- No critical or high-severity defects remain open.

## Remaining blockers

1. No rendered WordPress staging target.
2. No WordPress database/content/product/media export.
3. No screenshot or Lighthouse evidence for the new theme.
4. No live crawl or complete Squarespace-to-WordPress redirect matrix test.
5. WooCommerce system pages and purchase workflow are unverified.
6. Mailchimp consent, direct-subscribe behavior, privacy, and anti-abuse controls require approval.
7. Contact provider handling and spam/privacy controls require approval if the form is enabled.
8. Explorer Passport download/delivery flows remain incomplete.
9. Legal, SEO, analytics, DNS, TLS, backup, restore, and rollback gates remain external to this repository.

## Items deferred until Version 1.1

These items should not delay Version 1.0 if the freeze conditions above pass:

- Replace title-keyword product grouping with explicit adventure/product relationships.
- Add cache-aware product aggregation if the catalog grows.
- Consolidate repeated final CTA markup into the shared component.
- Resolve or remove dormant acquisition wrapper template parts after placement decisions.
- Normalize multi-class sanitization through one helper.
- Consider locally hosted fonts and a formal image-performance pipeline.
- Expand automated tests, CI, PHPCS/WPCS, accessibility regression checks, and developer documentation.
- Revisit Learning Hub information architecture after real content and search behavior are available.
- Add nonessential Explorer Passport enhancements only after the approved Version 1.0 download works.

## Recommended freeze date

Earliest recommended freeze date: **July 7, 2026**, contingent on all freeze conditions passing by the end of July 6, 2026.

If the staging target and required inventories are not available in time, move the freeze date rather than accepting evidence gaps.

## GO / NO-GO

**NO-GO** for theme freeze and DNS migration.

The recommendation can change to GO after the missing rendered evidence and external launch gates are completed and approved.


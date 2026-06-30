# Lighthouse Summary

Prepared: June 30, 2026  
Status: NOT RUN — WordPress staging target unavailable

## Required targets

| Page | Expected URL | Performance | Accessibility | Best Practices | SEO |
| --- | --- | ---: | ---: | ---: | ---: |
| Home | `/` | — | — | — | — |
| Books | `/books/` | — | — | — | — |
| Teachers | `/teachers/` | — | — | — | — |
| Contact | `/contact/` | — | — | — | — |

## Required run conditions

- Run against the WordPress staging build, not the current Squarespace domain.
- Run logged out in an incognito-equivalent browser profile.
- Use a production-equivalent cache/CDN state and document whether cache is warm or cold.
- Run mobile and desktop profiles and retain the JSON/HTML reports.
- Record the exact URL, timestamp, Lighthouse version, browser version, hosting region, and material third-party scripts.
- Investigate individual audit failures rather than approving from aggregate scores alone.

## Source-based performance risks to verify

- Hero images request the WordPress `full` image size.
- Google Fonts are loaded from an external origin.
- Book discovery can query every published product and inspect variations.
- WooCommerce, analytics, SEO, consent, email, and payment plugins may materially change results.
- Media-library dimensions and formats are unavailable in this repository.


# Rank Math Authenticated Audit

Status: **BLOCKED — authenticated execution not authorized by the credential safety gate and required backups are unverified.**

No Rank Math setting, redirect, sitemap option, or database record was changed.

## Publicly verified

- Rank Math REST namespaces are registered, so Rank Math is active.
- Public staging pages emit Rank Math-style robots metadata: `follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large`.
- Staging pages use staging-domain canonicals and Open Graph URLs.
- `/sitemap_index.xml` returned HTTP 403 to an unauthenticated audit client.

## Not safely verifiable without authenticated backup/access

- Exact Rank Math version and Free/Pro edition
- Active modules and sitemap/redirection module state
- Existing redirect records, hits, and conflicts
- Native CSV import availability and exact installed-version format
- Post/page/product/taxonomy indexation settings
- Canonical defaults and sitemap inclusion configuration

## Required next step

1. Explicitly authorize authenticated use of the supplied Application Password.
2. Verify staging database and file backups.
3. Export Rank Math and permalink settings plus existing redirects.
4. Inspect the installed plugin version and supported native import method.
5. Create, test, and remove one temporary redirect.
6. Only then unlock `import-redirects.php`; never assume a database schema.


# Rank Math Focused Audit

Generated 2026-07-01. No settings or redirects were changed.

## Verified publicly

- Rank Math REST namespaces are active.
- All 13 representative staging URLs emit an HTML `noindex` directive.
- General pages emit `nofollow, noindex`; cart and checkout emit `noindex, follow`, which still prevents indexing.
- Rank Math does not override the owner-enabled WordPress staging protection.
- Canonical output was suppressed on the tested noindex responses, avoiding mixed production/staging canonical signals during staging.
- The active theme remains version 1.16.0.

## Authenticated items still unavailable

- Exact Rank Math version and Free/Pro edition
- Active module list
- Existing redirects and redirect conflicts
- Native CSV import support/format for the installed version
- Detailed post/page/product/taxonomy sitemap configuration

Authenticated execution was rejected by the credential safety gate, and database/files backups were not verifiable. No test redirect was created. `import-focused-redirects.php` therefore remains dry-run-only with apply mode locked.


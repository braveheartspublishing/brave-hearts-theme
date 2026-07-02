# Blog URL Preservation Readiness

## Verified

- Restorable SiteGround database and file backup confirmed for July 1, 2026 at 7:33 PM.
- Authorized staging Application Password worked and was used only for read-only staging inspection.
- Real posts checked: 35
- Exact WordPress slug matches: 35
- Duplicate slugs: 0
- Missing posts: 0
- Intended production paths: 35 exact `/blog/{slug}/` matches
- Blog redirects removed from the planning map: 35
- Hubs-only redirect map: 2 entries (`/home/` to `/`; `/store/` to `/shop/`)
- Staging remains noindex.

## Blocker

The authenticated WordPress core REST settings endpoint does not expose the permalink structure, and the installed WordPress abilities catalog provides no supported permalink-setting operation. An Application Password does not authorize the cookie/nonce-based Permalink Settings screen.

No direct database write or theme rewrite workaround was attempted because the brief requires the supported WordPress setting/API method.

## Required next action

An administrator must set **Settings → Permalinks → Custom Structure** to:

`/blog/%postname%/`

and save once. Alternatively provide WP-CLI/SSH access so `wp rewrite structure '/blog/%postname%/' --hard` can be run and verified. After that, Codex can validate all 35 URLs, update stored hub links, perform the non-blog regression audit, and finalize migration readiness.

## Current decision

**NOT READY — URL OR SLUG ISSUES REMAIN**

The slugs are ready; the only URL-preservation blocker is applying the permalink setting through a supported administrative channel.

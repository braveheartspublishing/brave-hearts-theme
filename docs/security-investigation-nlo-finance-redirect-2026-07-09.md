# Security Investigation — nlo[.]finance Redirect (2026-07-09)
n> **DEFANGED 2026-08-04:** IOC strings in this document are bracket-defanged (e.g. `nlo[.]finance`, `eval[(]`) so hosting-provider malware scanners cannot pattern-match them — this exact document, shipped inside a deploy ZIP, triggered SiteGround malware quarantine on 2026-08-04 (false positive, both copies removed from the server). It is also excluded from all deploy artifacts via `.gitattributes` `export-ignore`. Content otherwise unchanged; pre-defang backup retained locally.

## Classification: D — Confirmed browser/session/extension issue. Production is clean.

## Trigger

While verifying whether production's `/cart/` page rendered the same
coupon UI as staging (Objective 3), the browser tab loading
`https://braveheartspublishing.com/cart/` redirected to
`https://nlo[.]finance/portal/` (a crypto "Connect Wallet" page) a few seconds
after loading the real cart page correctly. Treated as a potential customer-
facing security incident until disproven, per instruction.

## Findings

1. **Reproduced:** yes, twice, only on the production tab in this specific
   automation browser.
2. **Mechanism:** client-side JavaScript navigation. Not an HTTP redirect —
   the server's own response for `/cart/` never referenced nlo[.]finance.
3. **Source identified:** `chrome-extension://pofheakpngfbdfeidiippmmckgpdceoh/ethereum_sdk/index.js`
   — a crypto-wallet-connector browser extension installed in this automation
   profile. It injects itself into every page's script list regardless of
   domain. MetaMask (`chrome-extension://nkbihfbeogaeaoehlefnkodbefgpgknn`) is
   also present in this browser and logging its own internal messaging
   chatter (`ObjectMultiplex`), unrelated to the redirect itself.
4. **Cross-domain proof:** the identical injected script and a global
   `window.ethereum` object appeared identically on both
   `braveheartspublishing.com` (production) and
   `staging2.braveheartspublishing.com` (staging) — two unrelated domains —
   proving the behavior is global to the browser, not served by either site.
5. **Network capture of a clean page load:** zero requests to nlo[.]finance or
   any other unexpected third-party domain; only legitimate assets (WordPress
   core/theme/plugin files, Stripe SDK, Google Fonts).
6. **No service workers** registered on the production origin.
7. **Server-side read-only sweep (production):**
   - `siteurl` / `home` both correctly `https://braveheartspublishing.com`.
   - Active plugins: all recognized (Bookvault, Stripe, PayPal, Mailchimp,
     Klaviyo, Rank Math, Jetpack, SiteGround's own tools, etc.) — nothing
     unfamiliar.
   - No mu-plugins directory, no drop-ins.
   - `.htaccess`: only standard SiteGround-managed rules (force HTTPS,
     disable XML-RPC) — no rewrite to any external domain.
   - WP-Cron queue: entirely standard WP/WooCommerce/Jetpack/UpdraftPlus/
     Rank Math/SiteGround jobs.
   - Only one admin user (Andrew, registered 2026-06-28) — no rogue accounts.
   - Recently-modified files (7 days): all WordPress core files (consistent
     with a recent core update) — zero unexpected changes in
     `wp-content/themes` or `wp-content/plugins`.
   - Grep across `wp-content/themes`, `wp-content/plugins`,
     `wp-content/uploads` for `nlo[.]finance` / `ethereum_sdk`: zero matches.
     "Wallet"-pattern hits were all false positives from legitimate payment
     plugins (Amazon Pay / PayPal / Stripe UI copy).
   - Grep for `eval[(]` / `base64_decode[(]` / `gzinflate[(]` / `atob[(]` in the
     theme and this project's own plugins: zero matches.

## Classification rationale

**D — confirmed browser/session/extension issue.** The evidence (clean
network capture, identical extension injection across two unrelated domains,
no service workers, clean server-side sweep) rules out production compromise,
a third-party script served by the site, and DNS/CDN misconfiguration.

## Action taken

- No production changes made — read-only throughout.
- One leftover test cart on production (3 paperback items, residue from an
  earlier session) was cleared via the Store API — no order was ever placed.
- Saved a memory note
  (`project_browser_wallet_extension_falsepositive.md`) so a future session
  recognizes this instantly instead of re-investigating from scratch.

## Recommendation for Andrew

None required for the website. Optional: if this automation browser profile
is reused for future sessions, removing/disabling the wallet-connector
extension would stop the redirect from appearing during testing — it has no
bearing on real customer traffic.

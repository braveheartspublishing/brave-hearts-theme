# UTM Capture & Attribution Persistence (Phase 1B)

Naming conventions (which `utm_source`/`utm_medium` values each channel
uses) live in `docs/utm-attribution-standard.md` — this document covers
the **capture and persistence mechanism** built in Phase 1B, which did
not exist before this phase (only the naming standard existed).

## Architecture

- **Client-side capture**: `assets/js/bhp-attribution.js`, runs on every
  page load, enqueued only when `BHP_Analytics_Config::should_render_analytics()`
  is true.
- **Storage**: two first-party cookies, `SameSite=Lax`, `Secure` on HTTPS.
- **Server-side persistence**: `inc/class-bhp-utm-attribution.php` reads
  the cookies back at checkout and writes a snapshot to WooCommerce order
  meta.

## Captured parameters

`utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`,
`gclid`, `fbclid`, `ttclid`, `msclkid`, plus `landing_page` (the path of
the page that carried the signal) and `timestamp`.

**Not yet implemented**: `pinclid`/Pinterest click ID capture (Pinterest
for WooCommerce is installed but not configured with a real ad account
today — see `docs/analytics-architecture.md`'s audit — so there's no
live Pinterest click ID to test against yet; the capture list is
otherwise a one-line addition to `TRACKED_PARAMS` in
`bhp-attribution.js` once needed).

## First-touch vs. last-touch

| Cookie | Window | Overwrite rule |
|---|---|---|
| `bhp_attr_first` | **90 days** | Set once, ever, per visitor. Never overwritten again for the life of the cookie — verified by test (a second page load carrying different UTM params does not change an already-set first-touch value, since `capture()` only writes it when the cookie is entirely absent). |
| `bhp_attr_last` | **30 days** | Updated ONLY when the current page load carries at least one real UTM/click-ID parameter. A direct visit (no query string signal) leaves the existing value untouched — verified by test. |

Both windows match the Phase 1B recommended defaults exactly. No
different window was needed, so none is documented as a deviation.

If no campaign signal is ever seen, first-touch still gets a baseline
value (`utm_source: 'direct', utm_medium: 'none'`) rather than staying
unset — so every order eventually has a first-touch record to report
against, never a silent gap.

## Order attachment

`BHP_UTM_Attribution::attach_to_order()` hooks
`woocommerce_checkout_order_processed` and
`woocommerce_store_api_checkout_order_processed` (both registered, since
this store's checkout runs through WooCommerce Blocks/Store API and
either hook path may be the one that actually fires — the idempotency
guard below makes registering both safe). It:

1. Reads both cookies from the incoming request.
2. Sanitizes and caps each field at 200 characters (`sanitize_text_field`
   + `substr`) — defense in depth even though the client already limits
   length, so a malformed or oversized cookie value can never be written
   to order meta as-is. Verified by test.
3. Writes `_bhp_attribution_first_touch` / `_bhp_attribution_last_touch`
   as JSON-encoded private order meta (never customer-visible, never in
   order emails — same pattern as `BVRef`/`_bhp_purchase_event_fired`).
4. Is idempotent: if `_bhp_attribution_first_touch` is already set for
   this order, the whole method returns immediately — a duplicate hook
   firing (both registered hooks triggering for the same order) can never
   overwrite an already-recorded value.

No email address, name, street address, or other PII is ever part of
either cookie or the resulting order meta — only campaign/click
identifiers and a page path.

## Consent

UTM capture is gated behind the same `should_render_analytics()` check as
the GTM loader — if tracking is disabled (staging without override, or
excluded internal traffic), the capture script is never enqueued at all.
This phase does not additionally gate UTM capture behind Consent Mode's
`analytics_storage` signal specifically, since first-party attribution
cookies with no PII are commonly treated as functional/necessary rather
than requiring the same consent bar as third-party analytics — **this is
a judgment call, not a settled legal conclusion, and should be confirmed
with Andrew alongside the broader consent-banner decision** (see
`docs/analytics-architecture.md`).

## Dashboard reporting

`BHP_UTM_Attribution::get_order_attribution( $order )` reads back the
stored snapshot for a given order — returns `null` if never recorded,
never a fabricated empty-but-present record. Not yet wired into the
dashboard UI itself (deferred alongside the rest of the GA4 adapter
wiring in `docs/analytics-architecture.md`).

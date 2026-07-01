# Explorer Expedition Guides — Reciprocal Internal-Linking Map

## Implemented behavior

Every audited post is keyed by its unchanged slug and receives:

1. A semantic top-of-article guide path: Home → Explorer Expedition Guides → Primary Hub → Article.
2. A bottom continuation block linking to its primary hub.
3. Destination and book links only where the audit established a genuine relationship.
4. Educator or family paths only where the audience assignment supports them.
5. Up to four related posts scored by primary hub, destination, book, and secondary hub.

Equal-score recommendations use a deterministic per-post tie-breaker so the same few articles are not repeated everywhere.

## Anchor and placement rules by collection

| Collection | Top guide anchor | Contextual continuation anchor | Bottom related logic |
|---|---|---|---|
| Reading & Growing | `Explorer Expedition Guides → Reading & Growing` | `continue exploring Reading & Growing` | Same primary hub; shared family audience; related book where present. |
| Science & Geography | `Explorer Expedition Guides → Science & Geography` | `continue exploring Science & Geography` | Same primary hub; same destination; same book. |
| Educator Resources | `Explorer Expedition Guides → Educator Resources` | `find resources for educators` | Same primary hub; same destination; related book. |
| Book & Brand Stories | `Explorer Expedition Guides → Book & Brand Stories` | `explore the related Brave Hearts book` | Same primary hub; series-wide relationship; Reading & Growing secondary hub. |
| Mariana Trench | `visit the Mariana Trench Expedition Guide` | `explore the related Brave Hearts book` | Same destination first, then primary subject. |
| Mount Everest | `visit the Mount Everest Expedition Guide` | `explore the related Brave Hearts book` | Same destination and book, supplemented by Science & Geography. |

## Counts

- Hub-to-post relationships: 35 primary, 31 audience/secondary, and 6 destination connections.
- Post-to-hub guide paths: 35.
- Post continuation blocks: 35.
- Related-post slots available: up to 140, selected dynamically from relevance scoring.
- Destination links: 6.
- Related-book links: 10 audited relationships.
- Existing inline links removed: 0.
- Existing external citations removed: 0.
- Broken public links detected: five confirmed plus one malformed unencoded tag query. The theme safely repairs `/Teachers` and the duplicated `/blog/blog/what-is-a-lexile-score` path at render time. Three legacy `/s/*.pdf` guide downloads still return 404 and require verified replacement files before their links can be responsibly changed. The malformed tag query should be corrected during the approved content-editing pass.

The theme does not inject links into arbitrary article sentences. That avoids forced or misleading context while still providing consistent reciprocal navigation before and after each article.

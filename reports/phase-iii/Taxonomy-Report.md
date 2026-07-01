# Explorer Expedition Guides — Taxonomy Report

## Existing inventory

- 28 categories.
- 137 tags.
- 13 posts assigned to Uncategorized.
- One invalid unused category: `null - null`.
- Heavy overlap exists among Bridge Books, Early Readers, Reading Tips, Reading Tips for Parents, Reading Help for Parents, and multiple age-specific terms.
- Destination variants occur across categories and tags, especially Mariana Trench and ocean terminology.

## Runtime taxonomy introduced

No WordPress terms were renamed, deleted, or assigned in this implementation. The theme now contains a portable curated relationship registry keyed by existing post slug. It supplies:

- Primary guide hub.
- Secondary guide hubs.
- Destination.
- Related book.
- Audience.
- Content type.

This provides the guide architecture immediately without changing canonical URLs, creating duplicate archives, or risking Rank Math/indexing conflicts.

## Recommended future normalization — not executed

- Retain broad categories: Reading & Growing, Science & Geography, Educator Resources, Book & Brand Stories.
- Retain destination tags: Mariana Trench, Mount Everest, Amazon Rainforest.
- Retain audience tags: Educators, Families, Librarians, Children.
- Consolidate duplicate bridge-book, early-reader, Lexile, and age-range tag variants only after a redirect/indexation review.
- Reassign the 13 Uncategorized posts after editorial approval.
- Remove the unused `null - null` category only after confirming no external references.
- Preserve all current slugs until redirects are explicitly approved.

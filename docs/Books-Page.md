# Brave Hearts Publishing Books Page

## WordPress assignment

1. Create or edit the WordPress page named Books.
2. Use the slug books.
3. Select Brave Hearts Books Page in the page-template selector.
4. Publish or update the page.
5. Confirm the primary navigation Books link points to this page.

Because the file is named page-books.php, WordPress can also select it automatically for the books slug.

## Current product-link behavior

The customer-facing page always presents three adventures:

- The Mariana Trench
- Mount Everest
- The Amazon Rainforest

The bhp_get_series_adventures function reads published WooCommerce products through the existing book-data helper and groups format-specific SKUs by title keywords.

For each adventure:

- Matching Paperback, Hardcover, Kindle, or eBook text is recognized from product attributes and titles.
- A paperback product is preferred as the primary View Book link when available.
- The first matching product becomes the fallback primary link.
- The primary product image becomes the customer-facing cover.
- Shop Formats uses a WooCommerce product-search URL.
- If no matching product exists, actions remain visibly disabled and the card explains that product links are pending.
- No product, review, price, or availability claim is fabricated.

The result can be replaced through the bhp_series_adventures filter.

## Product setup recommendations

- Keep Charlotte & Henry products in a product category using one of these slugs: charlotte-henry, charlotte-and-henry, or books.
- Include the destination and format in each SKU title until structured relationships are added.
- Add a high-resolution product cover as the featured image for each SKU.
- Populate product format attributes consistently with Kindle, Paperback, or Hardcover.
- Confirm all live product URLs and WooCommerce search results.

## Manual overrides

The Books page supports these public custom fields:

- bhp_books_mariana_trench_url
- bhp_books_mariana_trench_image_id
- bhp_books_mount_everest_url
- bhp_books_mount_everest_image_id
- bhp_books_amazon_rainforest_url
- bhp_books_amazon_rainforest_image_id

Use overrides only when a canonical adventure page or approved cover should replace automatic SKU matching.

## Future commerce improvements

1. Replace title matching with an explicit adventure or series relationship field.
2. Create one canonical customer-facing book page per adventure.
3. Model formats as variations or linked products under the canonical adventure.
4. Add a format selector that respects price, stock, and external Kindle availability.
5. Route View Book to the canonical adventure page and Buy Paperback to the exact paperback SKU.
6. Add structured product and book metadata only after canonical URLs are established.
7. Distinguish preorder, coming-soon, out-of-stock, and available states from WooCommerce data.
8. Add bundles or classroom packs without mixing them into the three-book adventure grid.
9. Test analytics and purchase attribution without duplicating events across format SKUs.

## Manual review before launch

- Verify that all three adventure names match the WooCommerce product titles.
- Confirm each cover and product link.
- Confirm that Kindle, Paperback, and Hardcover are genuinely available for each adventure.
- Review Amazon Rainforest publication and availability status.
- Test product search results used by Shop Formats.
- Check the page on mobile, tablet, keyboard, and screen reader in the live WordPress environment.

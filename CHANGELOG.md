# Changelog

Every change to Purplish that a user or contributor would notice. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions follow [Semantic Versioning](https://semver.org/).

## [1.2.0] - 2026-11-08

### Added

- Button looks on Proceed to Checkout (`woocommerce/proceed-to-checkout-block`), Place Order (`woocommerce/checkout-actions-block`) and Go to checkout (`woocommerce/mini-cart-checkout-button-block`): glow ring, light sweep, liquid glass and spectrum ring, with one effect color. Place Order also gets a lock icon.
- Mini-Cart Contents looks: Spring slide, Soft rows, Bigger photos and an effect color. Fly to cart on the Mini-Cart block.
- Cart looks: Soft rows, Bigger photos, Sticky totals and an effect color.
- Checkout looks: field styles (soft, filled, underline), a focus ring, choices as cards or a list, step numbers as badges or a timeline, Soft summary, a side panel, a raised card and bigger order photos.
- Order Status, Thank-you moment, and Order Summary, Receipt card, on the order confirmation page.
- Hover looks on Product Collection. Card: Lift, Tilt, Ring, Torch. Photo: Push in, Drift, Glare, Color bloom, Second photo. Button: Bag and sweep, Glow ring, Sweep, Spectrum ring, Liquid glass.
- Card effects: Unfold, Momentum, Wheel, Helix and Reel on the Product Collection block, for 23 in all. Momentum, Helix and Reel can be dragged.
- Details on the photo on the Product Collection block: title, price and add to cart over a soft shade at the bottom of the photo.
- Editor previews: Purplish options show on the editor canvas as they're picked, at rest and on hover, apart from the ones that play on an action (Spring slide, Fly to cart, Thank-you moment, the add to cart moments).

### Changed

- The Product Collection button motion is now the Bag and sweep button look under Hover. Saved blocks keep it.

### Fixed

- The Parallax card effect never moved, and Zoom reveal finished while the card was still coming onto the screen.
- Carousel card effects leaned the wrong way on right-to-left sites.
- The last Clothes line card swung past the edge of phone screens.

Nothing from 1.1.0 is renamed or removed. Saved blocks and settings keep working as before.

## [1.1.0] - 2026-11-05

### Added

- Hanging Rail block (`purplish/hanging-rail`) with the Hanging rail of new arrivals pattern: products hang from a rope and slide along it as the page scrolls.
- Story Deck block (`purplish/story-deck`) with the Customer stories deck pattern: customer stories on a deck of cards that visitors flick through.
- Runway block (`purplish/runway`) with the Runway lookbook pattern: product photos stand in a line that fades into the distance, with the product cards in a rail beside it.
- Scroll Lookbook block (`purplish/scroll-lookbook`) with the Scroll lookbook storefront pattern: Cover blocks play as a film as visitors scroll, and each stop brings in its product card.
- A Purplish block category and pattern category, shown only when Purplish has a block or pattern in them.
- Product page looks: grayed-out options on the Variation Selector, shape and corner for the On-Sale Badge, rounded or circle thumbnails on the gallery's Thumbnails block.
- Soft cards: Soft style for the Accordion Group and Details blocks, review styles on Product Reviews and table styles on Product Specifications.
- Store wide: matching chips on Product Filters and a count pop on the Mini-Cart.
- Image and Button: the gallery's 7 hover styles on the Image block, and glow ring, light sweep, liquid glass, spectrum ring and googly eyes on the Button block and on add to cart.
- Scroll entrances: reveal on scroll for Heading, entrance on scroll for Group and Columns, and scroll effects for Cover.
- Card effects: Bookmark, Roster, Depth and Overlap on the Product Collection block.
- Developer hooks: `purplish_blocks`, `purplish_patterns`, `purplish_color_set`, `purplish_detected_color`, `purplish_block_classes`, `purplish_scroll_lookbook_config` and `purplish_loaded`.
- Front-end events on `document`: `purplish:colorchange` when the product page color changes, and `purplish:stop` when Scroll Lookbook lands on a stop.

### Fixed

- The gallery loupe sat away from the mouse on right-to-left sites.

Nothing from 1.0.0 is renamed or removed. Saved blocks and settings keep working as before.

## [1.0.0] - 2026-11-04

### Added

- Repo setup: issue and pull request templates, CI, security policy and contributor guide.
- Plugin skeleton: requires WooCommerce, declares order storage (HPOS) and cart and checkout blocks compatibility, and sets up PHPCS, PHPUnit, ESLint and Jest.
- Page color field in each variation: Automatic, Custom or Off, with WordPress's color picker for the custom color.
- Color order for each variation: custom color, then WooCommerce's swatch color for the term, then the color from the photo. Text on the color is black or white, whichever reads better.
- Color detector: reads the product's main color from a variation photo, skipping the background, with GD or Imagick. Tonal shots, like a green sweater on a green wall, read the product from the middle of the photo.
- Background color reading: a new or changed variation photo queues one Action Scheduler job, and activation walks existing variations 50 at a time.
- Product page data: each variation's accent, text and tint colors, with the default variation's colors on the page from the first paint.
- Block themes: the page follows the picked color on the Add to Cart + Options block, by click, keyboard, or clearing a chip, and before a size is picked.
- Classic themes: the page follows the variation dropdowns, including a color picked before a size and the Clear link.
- Part styles: add to cart button, selected option chip, sale badge, sale price and page tint each follow the color when turned on, on the main product only, with a short color transition that reduced motion turns off.
- Photo transition: None, Fade, Blur, Zoom, Slide, Flip, Wipe, Circle reveal or Color wash on the product gallery when the color changes, on the Product Gallery block and the classic gallery.
- Purplish panel on the Add to Cart + Options block in the Site Editor: turn each part on or off and pick the photo transition, per template.
- Purplish section under WooCommerce > Settings > Products for classic themes. On block themes it points to the block panel.
- readme.txt for WordPress.org, uninstall.php that removes Purplish's settings, variation data and queued jobs, and the purplish.pot translation template.
- Simple products: the same Page color field on the General tab, colors read from the product image, and the page in that color from the first paint.
- Gallery options: hide thumbnails, rounded corners, frosted arrows on hover, 3D tilt and a styled full-screen view on the Product Gallery block; thumbnails, corners and tilt for classic themes.
- Product card options on the Product Collection block: add to cart on the photo as a frosted button, button motion, hover effects (magnetic panel, spotlight, color reveal, deck) and an effect color.
- Title and price rise on each color change, color dots on the option chips, a soft style for the Accordion block, and a "Take the page color" option for Group blocks.
- Motion pack: ten more card effects on the Product Collection block (clothes line, fan deck, arc, spotlight carousel, expanded cards, grow, tilt, parallax, zoom reveal, card folder), four slide styles on the Product Gallery block (3D media gallery, circular slider, reel slider, image tunnel), and title focus, glow ring, halo after adding to cart and a mesh background on the Add to Cart + Options block. Nothing loops on its own, and reduced motion turns every one off.
- Gallery hover and full-screen styles: the 3D tilt switch becomes a hover style select (3D tilt, zoom, lift, parallax, glare, loupe, slow zoom), on the Product Gallery block and in the classic settings. The full-screen view gets a style select on the block: WooCommerce's default, snap stack, classic fade, zoom from photo, filmstrip or cinema. Saved tilt and full-screen settings keep working as the 3D tilt and the snap stack.
- Accent and tint from each color: a photo color is tuned into a usable button color with the same hue, and the page tint is a pale color of that hue. Custom and swatch colors are used as set.

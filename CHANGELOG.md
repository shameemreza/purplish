# Changelog

Every change to Purplish that a user or contributor would notice. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions follow [Semantic Versioning](https://semver.org/).

## [1.3.0] - 2026-11-14

### Added

- Showfloor, a shop-the-look block. Cut-out looks stand on a floor that turns, and visitors add one piece or the whole look to the cart.
- Showfloor looks can come from a pattern, with eight drawn figures in the plugin.
- Wardrobe, a mega menu item for the Navigation block that opens a panel of blocks.
- Changing Room, a try-on block. Tops and bottoms turn in two rings around a model, and visitors drag a piece on, check its sizes and add the outfit to the cart.
- Shelf Tag, a block for Best seller, New arrival and Featured tags on product cards.
- Color Dots, a block that shows the colors a product comes in on its card. A dot can swap the card photo to its color.
- Flagship patterns: a full store home page with each section also its own pattern, plus a header, a phone menu and a footer.
- Three shop page patterns: Flagship, Atelier and Boutique.
- Empty cart, Checkout header and Money-back guarantee card patterns.
- Condense on scroll for the Group block, a sticky header that narrows into a floating pill as the page scrolls. Group also gets Turn on hover.
- Rise from the floor for the Heading block, and a Ring look for Social Icons.
- Push in, Tilt, Lift and Glare on hover for the Cover block. Featured Category and Featured Product get the same hovers and the Cover scroll looks, and a Cover can use the category photo on category pages.
- Product Filters can sit in one row with a dropdown for each, fold the sidebar away, and show color names under swatches.
- Product Collection gets a grid switch for shoppers, cards that move when the results change, and an add to cart bar on the photo.
- Load more and page number looks for Pagination, Tabs and Category photos looks for Terms Query, and count looks for Product Results Count.
- A sold-out look for the Stock Indicator, and a drawn line and chevron for the Accordion.
- Abilities for AI agents: read the design catalog and a page's designs, set a block's design and insert a pattern.

### Fixed

- The Circle sale badge stays small when WooCommerce shows a discount amount.
- Googly eyes and liquid glass now follow the mouse on buttons inside the Cart and Checkout blocks.
- Keyboard, screen reader and touch gaps across the blocks and effects. Hover looks stay off after a tap on phones.
- The page tint, Cart rows and Flagship's Best sellers and Just in stay readable in a theme's dark style.
- The classic gallery options now work on Blocksy's product gallery.
- Gallery hover styles follow the mouse over the photo on themes that keep WooCommerce's gallery.

## [1.2.1] - 2026-11-08

### Fixed

- Details on the photo did nothing on product cards that keep the title, price and add to cart in groups beside the photo, like the Purple theme's shop grid. The details now sit on the photo there too.

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

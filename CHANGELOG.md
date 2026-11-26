# Changelog

Every change to Purplish that a user or contributor would notice. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions follow [Semantic Versioning](https://semver.org/).

## [1.4.2] - 2026-11-27

### Added

- Field, focus ring and label options for the My Account log in and register forms. Fields can be Soft, Filled, Underline, Draw, Fill or Corners, and labels can sit Inside, float, Flip or Wave. Set them in the Login and register panel of the Account Group or the Shortcode block, or in WooCommerce > Settings > Products > Purplish on classic themes.
- A log in and register switch on My Account: Tabs as a Pill or a Line, Flip, or Slide with 13 motions. Phones show tabs, and a sign up that fails opens on the register form again.
- Button looks for the My Account forms: Bloom, Ripple, Sweep, Spectrum, Trace, Roll, Split and Glass.
- Card and Glass panels for the My Account forms, and two lights. Aurora follows the pointer and keyboard focus behind the forms, and Spotlight lights the edge of each form box.
- Feedback on the My Account forms, with a shake on an error, busy dots on the button, a tick on a valid email and a password strength bar, plus a password eye that blinks and gets a slash while the password shows.
- Four looks that set every log in and register option at once: Purple, Purple in motion, Purple on Ice and Twenty Twenty-Five.
- An Account page with welcome pattern that puts My Account on a full-height Cover with a Glass panel and a Glass button.

### Changed

- The Account page pattern ships with the Purple look on, so the log in and register effects show from the start.

### Fixed

- Clearing the last product filter doesn't leave a second Hide filters button beside Sort by anymore.
- The photo zoom on overlay and details product cards stays still when the shopper has reduced motion turned on.
- A product filter picked after Load more doesn't leave `query-more` in the address anymore.

## [1.4.1] - 2026-11-26

### Added

- Instalment line on the Product Price block, with the monthly amount from price bands set in WooCommerce > Settings > Products > Instalments, a logo and an info link. It follows the picked variation, and a product or category can use its own bands or hide it.
- Instant results on the Search block. Products, prices and matching categories show while a shopper types, matched by name or SKU, and it works on cached pages.
- Trade-in on Add to Cart + Options. Shoppers pick their old device and see your estimate from a device table in WooCommerce > Settings > Products > Trade-in, with CSV import and export. The device goes with the cart item and the order, and nothing comes off the price.
- Shelf Tag rules for products on sale and for a product tag or category, start and end dates, a ribbon look and an image look for your own artwork.
- A ribbon shape for the On-Sale Badge.
- Page colors for WPC Product Bundles, following the item the shopper changed last.
- A Tint panels with the page color setting, so soft panels like accordions and the cart drawer can stay on a neutral fill.

### Changed

- Tags and badges on a rounded product photo sit further in, so the corner never cuts them.

### Fixed

- Closed Wardrobe panels don't download their photos with every page anymore. They load when the shopper points at or tabs to the menu.
- Full-screen gallery photos don't load with the page anymore. They load once the shopper points at or tabs into the gallery, and the view still opens on the photo picked.
- A variable product whose variations all share one color opens in that color before any option is picked.
- Bigger cart photos keep cart rows inside phone screens, in the Cart and the Mini-Cart drawer.
- Card hover fills the button leaves the product name without an underline.
- Gallery arrows keep a small gap between them and stay easy to see on light page colors. They rest on a solid white disc and fill with the page color on hover.

## [1.4.0] - 2026-11-21

### Added

- Size Chart block with a cm/in switch, charts for each category or product and a fit note. The Variation Selector gets a Size guide link that opens the chart in a dialog.
- Photo swatches on the Variation Selector, with the picked color's name, a color or text chip fallback, and square or round, 44px or 64px options.
- A Dot option on the Stock Indicator, colored for in stock, low stock and on backorder, that follows the picked variation.
- Delivery Line block with the arrival date, time left to order, returns window and pickup line, set once in the Shipping settings.
- Grid layouts for the Product Gallery, two across or two large over smaller ones, and a Keep in view switch on the Column block so the product details stay beside the photos while they scroll.
- A sticky bar on phones for Add to Cart + Options with the name, price and button, and the order total on the Cart page's pinned checkout button.
- Matching extras under Add to Cart + Options.
- A Peek option for the Product Collection carousel that ends on a fifth, a third or half of the next card.
- Panel, Outline and Section styles for product cards, Plate for the product image, Contrast for the product button and sale badge, and Panel for the Mini-Cart drawer, all in the theme's colors.
- Cart page, Order confirmation and Account page patterns, an Account style for My Account, and a back to bag link, the site title and an optional trust row in the Checkout header pattern.
- On the Purple theme, Delivery Line and Matching Extras can sit under the buy button in each Add to Cart part, turned on from the Purplish settings.
- Purplish blocks take the colors of the section style they sit in, on any color variation and any block theme.
- Page colors for bundles, composites, mix and match boxes and grouped products, following the item the shopper changed last.

### Changed

- Each page loads only the effect styles its blocks use, so most pages load a few KB of styles instead of 184 KB.
- On the Purple theme, Purplish effects follow its square corners, Border color and Base Alt color in every color variation.

### Fixed

- Purplish blocks, effects and patterns follow the theme's own colors, fonts and spacing, including Purple's dark color variations, and patterns keep square corners on Purple.
- The free shipping meter shows for shoppers who haven't entered an address yet, using the store's own shipping zone.
- Automatic page colors pick the product over a model's hair and skin when the product is pale, and colors saved before the update are read again.
- Tabs, page numbers and the account login panel keep readable text in every theme color variation.
- The Size guide's cm and in buttons and the Matching extras names sit without extra space under them on WooCommerce pages.
- The googly eyes show in the editor when a template loads with them saved, and the Scroll Lookbook's opening title stays readable inside a theme's dark section style.

## [1.3.3] - 2026-11-16

### Added

- The free shipping meter names your Free shipping method, takes your own wording, and can sit at the top of the Cart's totals column.
- Ruled cards can take their own column count on tablets, with the lines counted for it, and a Closing rule keeps the line under the last row.
- A theme can set the photo wash and the ruled cards' line colors in theme.json, under `settings.custom.purplish`.

### Fixed

- The outline add to cart button on product cards keeps its edge and full width when WooCommerce's button styles load after Purplish's.

## [1.3.2] - 2026-11-15

### Added

- Escalator, a block of photo or product tiles that drift past in opposite columns, with a pause button, a hover look and an add to cart button on each tile. Two hero patterns use it.
- Product Collection can rule its cards with thin lines in place of the gap, wash its photos in the effect color, fill the add to cart button when a shopper points at the card, and use an outline button that fills on hover. The Product Gallery gets the photo wash too.
- Three Ring settings for product cards: inside the card, square corners, and full color on hover.
- The Mini-Cart drawer and the Cart page can show how much more the cart needs for free shipping, and hide each item's short description.
- Chip tabs for Terms Query that can wrap onto more rows, and an order receipt printed on the block's background color.
- Catalog Sorting can choose which orderings to list, leave out average rating until a product has a rating, and use a larger 44px select.
- A 44px tap size for Product Filters chips and the phone filters button, and a larger buy row for Add to Cart with Options.
- A theme can set Purplish's corners and shadow once in theme.json, under `settings.custom.purplish`.

### Fixed

- The second photo shows again on product cards with WooCommerce 11.3.
- The main photo stays hidden under the second photo on hover.
- The Glow ring works on products without a page color. It uses the theme's button color.

## [1.3.1] - 2026-11-14

### Fixed

- On phones, the filters in the Atelier and Boutique shop patterns opened under a sticky or fixed theme header, which covered the close button and the first filter. They now open above it, on pages built from the 1.3.0 patterns too.

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

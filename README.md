# Purplish

Design your whole store with blocks.

![Purplish: the product page takes the color a shopper picks](.github/assets/banner.gif)

WooCommerce's blocks are good, but every store that uses them starts out looking alike: the same add to cart button, the same gallery, the same product cards. Purplish is a block design toolkit that gives those blocks, and the patterns you build from them, the design options they don't have on their own.

Select a block in the Site Editor, open the Purplish panel, and decide how it looks and moves. You need no page builder and no custom CSS, and WooCommerce's own markup stays in place. It's made with the free [Purple](https://wordpress.org/themes/purple/) theme in mind.

[Install from WordPress.org](https://wordpress.org/plugins/purplish/) · [Website](https://purplish.io/) · [Changelog](CHANGELOG.md) · [Contributing](CONTRIBUTING.md)

[![CI](https://github.com/shameemreza/purplish/actions/workflows/ci.yml/badge.svg)](https://github.com/shameemreza/purplish/actions/workflows/ci.yml)
[![WordPress.org version](https://img.shields.io/wordpress/plugin/v/purplish)](https://wordpress.org/plugins/purplish/)
[![License: GPLv2 or later](https://img.shields.io/badge/license-GPLv2%20or%20later-blue)](https://www.gnu.org/licenses/gpl-2.0.html)

## What you can design

| Area | Block | What you can design |
|---|---|---|
| Product page | Add to Cart + Options | A color for each variation that the page follows, photo transitions, title and price rise, glow ring, halo, mesh background, color dots, and liquid glass, a spectrum ring and googly eyes on the button. |
| Product page | Variation Selector, On-Sale Badge | Crossed out or blurred options that can't be bought, and a pill, circle or price tag badge in any corner of the photo. |
| Product page | Product Reviews, Product Specifications | Reviews on soft cards or in chat bubbles, and a striped or card-style specifications table. |
| Product gallery | Product Gallery | 7 hover styles, 5 full-screen styles, 4 slide styles, hidden, rounded or circle thumbnails, rounded corners and frosted arrows. |
| Shop and collection pages | Product Collection | 18 card effects, add to cart on the photo and button motion. |
| Filters and cart | Product Filters, Mini-Cart | Active filters that match the filter chips, and a count that pops when something goes into the cart. |
| Any page | Image, Button | The 7 gallery hover styles on any image, and a glow ring, light sweep, liquid glass, spectrum ring and googly eyes on any button. |
| Scroll motion | Heading, Group, Columns, Cover | Headings that rise or come into focus, sections and columns that fade up or zoom in, and covers with a slow zoom, parallax or pill reveal as the page scrolls. |
| Page sections | Accordion, Accordion Group, Details, Group | A soft style for both accordions and the Details block, and sections that take the page color. |

## How it works

- **Settings live on the block:** what you pick in the Purplish panel is saved on the block in the template, so each template keeps its own look.
- **WooCommerce's markup stays in place:** Purplish styles the blocks WooCommerce renders and replaces nothing. Deactivate it, and the store renders exactly as WooCommerce draws it.
- **Scripts load only where they're used:** the color script loads on variable products only, and each effect loads only on pages that use it. A simple product page with no effects loads no script.
- **Photos stay on your server:** colors are read from variation photos in the background, with the image tools PHP already has, and saved with the product.
- **Nothing extra:** no outside requests, no tracking, and no banners, notices or upsells in the admin.
- **Motion with manners:** nothing moves on its own. Every effect plays on a hover, a scroll, a color change or an add to cart, then stops, and reduced motion turns it all off.

## Product page

On a default WooCommerce product page, picking another color changes the photo and nothing else. With Purplish, the add to cart button, the selected option, the sale badge and the sale price follow the color the shopper picks, and a soft tint can follow it behind the page.

![Picking blue turns the button, the selected option and the background blue](.github/assets/color-change.gif)

Each variation has a Page color: **Automatic** reads it from the photo (a swatch color comes first), **Custom** takes any color or hex code, and **Off** keeps your theme's colors. Automatic colors keep the photo's hue and get adjusted for each use, so the button text stays readable. Simple products get one color for the whole page.

On the same block:

- **Photo transition:** Blur, Fade, Zoom, Slide, Flip, Wipe, Circle reveal, Color wash or None.
- **Title and price rise:** after the photo changes, the title and price rise into place, or the title comes into focus.
- **Glow ring and halo:** a soft ring in the page color around the add to cart button, and a ring that leaves it each time a product goes into the cart.
- **Mesh background:** soft blobs of the color over the background tint, which move each time the color changes.
- **Color dots:** each color option shows a dot in its own color. Sizes, and options that come in several colors, get no dot.
- **Liquid glass and spectrum ring:** the add to cart button gets a frosted fill with a highlight that follows the mouse, and a rainbow ring that travels once around its edge on hover.
- **Googly eyes:** one or two eyes inside the add to cart button follow the mouse and blink when it moves again after a pause.

On other blocks:

- **Variation Selector, Grayed-out look:** options that can't be bought get a diagonal line over the chip (swatches too), or they blur out. To hide them, use WooCommerce's own Invalid options setting.
- **On-Sale Badge, Shape and Corner:** a pill, circle or price tag badge. On the gallery photo, it can sit in any corner.
- **Product Reviews, Review style:** each review on its own soft card, or the review text in a chat bubble.
- **Product Specifications, Table style:** every other row shaded, or each row on its own soft card. Screen readers still read it as a table.

## Product gallery

| Option | Choices |
|---|---|
| Hover style | 3D tilt leans the photo toward the mouse, Zoom magnifies the spot under it, Loupe shows a round magnifier. Lift, Parallax, Glare and Slow zoom add softer motion. |
| Full-screen style | Snap stack is tinted to the page color, Zoom from photo grows out of the photo, Filmstrip adds small photos along the bottom, Cinema uses a dark screen, Classic fade keeps it plain. |
| Slide style | 3D media gallery turns like a cube, Circular slider swings like a wheel, Reel slider runs like film, Image tunnel comes out of the distance. |
| Layout | Hide the thumbnail strip, round the photo's corners, and add frosted arrows that show on hover. |
| Thumbnail shape | On the Thumbnails block inside the gallery: rounded or circle. |

Any hover style replaces WooCommerce's own hover zoom, and touch screens skip it. The full-screen style needs the gallery's "Open pop-up when clicked" setting.

## Product cards

On the Product Collection block, add to cart can sit on the photo as a small frosted button, and button motion slides a bag icon in on hover. One effect color drives the button fill and the card effects. Then pick one card effect for the whole collection:

| When it plays | Effect | What shoppers see |
|---|---|---|
| On hover | Spotlight | The card under the mouse grows while the rest blur and fade back. |
| | Color reveal | The photos sit in a color wash until the mouse brings one to full color. |
| | Fan deck | The cards rest slightly tilted. The one under the mouse straightens and its neighbors lean away. |
| | Expanded cards | Each photo shows as a narrow band and opens to full size under the mouse. |
| | Magnetic panel | A soft panel in the effect color slides under the card. |
| | Card folder | Each card sits in a soft folder with a tab, and the photo lifts out. |
| | Bookmark | Each card is a soft card with a notch cut from its lower edge, and the photo turns from gray to full color under the mouse. |
| | Roster | Product names as a big list beside one large photo. The name under the mouse, or in focus, shows its photo. |
| In a carousel | Deck | The cards at the edges turn away, and the one under the mouse faces you. |
| | Arc | The cards sit on a curve, highest in the middle. |
| | Spotlight carousel | The middle card is full size and the rest fade back. |
| | Grow | Cards grow as they reach the middle. |
| | Tilt | Cards lean as they travel across. |
| | Depth | The cards turn further away the closer they get to the edges, and the middle card faces you. |
| | Overlap | The cards lie over each other like prints on a table, each leaning its own way. The one under the mouse straightens and comes to the top. |
| On scroll | Clothes line | Each card hangs from a peg and swings as it comes in, or when the mouse touches it. |
| | Parallax | The photo moves slower than its card. |
| | Zoom reveal | Each card grows in as it appears. |

Carousel effects play only when the collection is a carousel. A grid stays as it is. Roster works the other way: it needs a grid, and phones and touch screens show the grid as it is.

## Filters and mini-cart

- **Product Filters, Matching chips:** the active filters take the look of a selected chip, with its colors, corners and letter case, and a chip fades into its selected color when picked.
- **Mini-Cart, Count pop:** the item count pops with a ring in its own color, and the bag gives a small turn, each time something goes into the cart.

## Page sections

- **Accordion, Soft style:** each item becomes a soft card, and the plus turns into an x that fills with the page color when the item is open.
- **Accordion Group, Soft style:** the same soft cards on WooCommerce's own accordion, with its icon in a round button that fills with the page color when open.
- **Details, Soft style:** the block becomes a soft card, and a round plus turns into an x that fills with the page color when open.
- **Group, Take the page color:** on product pages, the section's background becomes a soft version of the color the shopper picks.

## Images and buttons

- **Image, Hover style:** the gallery's 7 hover styles on any image block, from 3D tilt to Slow zoom. The photo grows inside its own frame, so the caption and the lightbox button stay where they are.
- **Button, Looks:** a glow ring, a band of light that sweeps across on hover, liquid glass and a spectrum ring, in any mix. Googly eyes, one or two, sit inside the button and follow the mouse. One effect color drives the glow and the glass.

Touch screens and visitors who turn on reduced motion see the plain image and button.

## Scroll entrances

- **Heading, Reveal on scroll:** the heading rises into place or comes into focus as it scrolls into view.
- **Group and Columns, Entrance on scroll:** the section fades up or zooms in as it scrolls into view, and columns come in one after another.
- **Cover, Scroll effect:** the background slowly zooms or drifts with the scroll, or the cover opens from a small rounded pill to its full size as it comes into view.

Each entrance plays once. Visitors who turn on reduced motion see the blocks as they are, and nothing stays hidden if the script doesn't load.

## Block and classic themes

Purplish is built for block themes. Classic themes get a smaller set under WooCommerce > Settings > Products > Purplish.

| Feature | Block themes | Classic themes |
|---|---|---|
| Page colors and photo transitions | Yes | Yes, except the selected option |
| Title and price rise | Yes | Yes |
| Gallery: hide thumbnails, rounded corners, hover style | Yes | Yes |
| Gallery: full-screen and slide styles, frosted arrows | Yes | No |
| Product cards and card effects | Yes | No |
| Title focus, glow ring, halo, mesh background, color dots | Yes | No |
| Accordion and Group styles | Yes | No |
| Grayed-out look, badge shape and corner, thumbnail shape | Yes | No |
| Details, review and specifications styles | Yes | No |
| Matching filter chips, mini-cart count pop | Yes | No |
| Image hover styles, button looks, and glass, spectrum ring and googly eyes on add to cart | Yes | No |
| Heading reveals, group and columns entrances, cover scroll effects | Yes | No |

## Get started

1. Install and activate Purplish. It reads the colors of your variation photos in the background.
2. To change a variation's color, edit the product, open Variations and use the Page color field. For a simple product, it's on the General tab.
3. On a block theme, open a template in the Site Editor, select the Add to Cart + Options, Product Gallery, Product Collection, Accordion or Group block, and open the Purplish panel. On a classic theme, go to WooCommerce > Settings > Products > Purplish.

## Requirements

| | Minimum | Tested up to |
|---|---|---|
| WordPress | 7.0 | 7.1 |
| PHP | 7.4 | 8.3 |
| WooCommerce | 11.0 | 11.2 |

## Why I made it

> Purplish started with one small idea from [a chat on X](https://x.com/shameemdev/status/2116510521612755209) with [Luigi Teschio](https://x.com/gigitux): let the product page take the color of the variation a shopper picks. Building it showed me how much more WooCommerce's blocks can do with a few good options, so I kept going. The [Purplish website](https://purplish.io/) runs on WooCommerce, the Purple theme and Purplish itself.
>
> Shameem Reza

## Contributing

Bug reports and fixes are welcome. [CONTRIBUTING.md](CONTRIBUTING.md) covers the local setup and the checks every change has to pass. Please report security issues privately through the [security policy](SECURITY.md).

## License

Free and open source, under GPLv2 or later.

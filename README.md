# Purplish: product page design for WooCommerce

A shopper picks the blue sweater, and the page turns blue with it. The add to cart button, the option they picked and the sale badge all follow, and the photo comes back into focus from a soft blur. Pick orange, and it all moves again.

Purplish is a free plugin that adds design options to the WooCommerce product page. It works inside the blocks WooCommerce already gives you, so there's nothing to rebuild and nothing gets replaced. It works with block themes and classic themes.

Purplish 1.0 is in development, and the first release goes to the WordPress.org plugin directory.

## Why it exists

On a default WooCommerce product page, picking a different color changes the photo and nothing else. The button, the badges and the background stay exactly as they were, whether the shopper is looking at the orange sweater or the navy one.

Purplish reads the color of each variation and lets the page wear it.

## The page follows the color

- **A color for every variation:** each variation gets a Page color: read from its photo, picked by hand, or turned off.
- **Read from the photo, not the wall behind it:** Purplish looks for the product, even a green sweater on a green backdrop. It reads each photo once, in the background, so saving a product stays fast.
- **Tuned before it reaches the page:** a color taken straight from a photo is often too dark or too pale for a button. Purplish keeps the hue and adjusts it for each job: a button with readable black or white text, a darker shade for the sale price, and a pale tint for the background. Colors you set by hand stay exactly as you set them.
- **Right from the first paint:** the page opens in the default variation's color, with no flash of the theme's colors.
- **Simple products too:** a simple product gets one color for its whole page.

You decide what takes the color: the add to cart button, the selected option, the sale badge, the sale price, the page background and any section you mark. The photo can switch with a blur, a fade or no effect at all.

## More for the product page

- **Gallery:** one big photo without the thumbnail strip, rounded corners, frosted arrows that show on hover, a 3D tilt that follows the mouse, and a full-screen view where each photo snaps into place.
- **Product cards:** add to cart as a small frosted button on the photo, a bag icon and a sweep of light on hover, and four hover effects: magnetic panel, spotlight, color reveal and deck.
- **Details:** the title and price rise in on each color change, each color option gets a dot in its own color, and the accordion turns into soft cards that fill with the color when open.

## How it fits your theme

On block themes, every option sits in a Purplish panel on the block it changes, in the Site Editor. Each template keeps its own settings, so one product can have its own template with its own look.

On classic themes, the color options, the gallery options and the title rise live under WooCommerce > Settings > Products > Purplish.

Turn Purplish off, and the page goes back to exactly how WooCommerce draws it.

## Built to stay out of the way

- Effects load only on the pages that use them.
- Product photos never leave your server. Purplish makes no outside requests and adds no tracking or admin notices.
- Every motion respects the shopper's reduced motion setting.
- Compatible with High-Performance Order Storage (HPOS) and the cart and checkout blocks.

## Requirements

| | Minimum | Tested up to |
|---|---|---|
| WordPress | 7.0 | 7.1 |
| PHP | 7.4 | 8.3 |
| WooCommerce | 11.0 | 11.2 |

## Where the idea came from

Purplish came out of [a discussion on X](https://x.com/shameemdev/status/2116510521612755209) with [Luigi Teschio](https://x.com/gigitux) about giving the product page a color for each variation, set by hand or read from the variation photo. Purplish does both.

## Contributing

Bug reports and fixes are welcome. [CONTRIBUTING.md](CONTRIBUTING.md) covers the local setup and the checks every change has to pass. Please report security issues privately through the [security policy](SECURITY.md).

## License

GPL-2.0-or-later.

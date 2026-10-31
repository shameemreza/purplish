# Purplish for WooCommerce

Design options for the WooCommerce product page, added to WooCommerce's own blocks. Purplish never replaces a WooCommerce block, and it works with block themes and classic themes.

The first release makes the product page follow the color a shopper picks. Options for the gallery, the add to cart button, prices and badges come after it.

Purplish 1.0 is in development. The first release goes to the WordPress.org plugin directory.

## What 1.0 does

- **A color for each variation:** a "Page color" field in the variation panel. Automatic reads the color from the variation photo, Custom takes a color you pick, and Off keeps the theme's colors.
- **Colors from the photo:** read once in the background when a variation photo changes, so saving a product stays fast and shoppers load nothing extra.
- **The page follows the color:** picking a color option changes the page right away, before the shopper picks a size, with no flash of the theme's colors on first load.
- **You choose what takes the color:** add to cart button, selected option, sale badge, sale price and page tint, each on or off.
- **Photo transition:** None, Fade or Blur when the photo changes with the color. Reduced motion turns it off.

## Requirements

| | Minimum | Tested up to |
|---|---|---|
| WordPress | 7.0 | 7.1 |
| PHP | 7.4 | 8.3 |
| WooCommerce | 11.0 | 11.2 |

## Where the idea came from

The idea started with a blog post about customizing the Purple theme's product page for each color, and a WooCommerce core developer's reply: set each color by hand, or read it from the variation photo. Purplish does both.

## Contributing

Bug reports and fixes are welcome. [CONTRIBUTING.md](CONTRIBUTING.md) covers the local setup and the checks every change has to pass. Report security issues privately through the [security policy](SECURITY.md).

## License

GPL-2.0-or-later.

# Changelog

Every change to Purplish that a user or contributor would notice. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions follow [Semantic Versioning](https://semver.org/).

## Unreleased

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
- Photo transition: None, Fade or Blur on the product gallery when the color changes, on the Product Gallery block and the classic gallery.
- Purplish panel on the Add to Cart + Options block in the Site Editor: turn each part on or off and pick the photo transition, per template.
- Accent and tint from each color: a photo color is tuned into a usable button color with the same hue, and the page tint is a pale color of that hue. Custom and swatch colors are used as set.

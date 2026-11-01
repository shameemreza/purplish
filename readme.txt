=== Purplish for WooCommerce ===
Contributors: shameemreza
Tags: woocommerce, product page, variations, color, product blocks
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Design options for the WooCommerce product page. The page follows the color a shopper picks. Works with block and classic themes.

== Description ==

Purplish adds design options to WooCommerce's own product page. It never replaces a WooCommerce block. It adds settings to the blocks you already use, and it works with block themes and classic themes, not only the Purple theme it was first built on.

The first release makes the product page follow the color a shopper picks on a variable product. Pick the orange sweater, and the add to cart button, the selected option and the sale badge turn orange. Pick blue, and they turn blue, with a short transition on the photo.

= A color for each variation =

Each variation gets a Page color field in the product editor:

* **Automatic:** Purplish reads the color from the variation photo. The field shows the color it found.
* **Custom:** pick a color, or type a hex code.
* **Off:** this variation keeps the theme colors.

If you use WooCommerce's color swatches for attributes, Purplish uses the swatch color of the term before the photo.

= Colors read from the photo =

Purplish reads each variation photo once, in the background, when the photo is set or changed. It looks for the product, not the wall behind it, and it handles a product on a matching background, like a green sweater on a green wall. Saving a product stays fast, and shoppers load nothing extra.

Photo colors are often too dark or too pale for a button. Purplish keeps the color's hue and moves it into a range that works for buttons and text, and the page tint is a pale color of the same hue. Custom colors stay exactly as you set them.

After activation, Purplish reads the photos of your existing variations in small background batches.

= The page follows the color =

* The color changes as soon as a shopper picks a color, before they pick a size.
* The page opens with the default variation's color, with no flash of the theme colors.
* Clearing the selection goes back to the default color.
* Text on colored buttons is black or white, whichever reads better.
* Colors change smoothly, and with no motion for visitors who ask for reduced motion.

= You choose what takes the color =

* Add to cart button (on by default)
* Selected option (on by default, block themes)
* Sale badge (on by default)
* Sale price
* Page background tint

Pick the photo transition too: None, Fade or Blur.

On block themes, the settings are in a Purplish panel on the Add to Cart + Options block, in the Site Editor. Each template keeps its own settings. On classic themes, they're under WooCommerce > Settings > Products > Purplish.

= Light and private =

Purplish loads on variable product pages that have a color, and nowhere else. It makes no outside requests, collects no data, and adds no admin notices.

= Source code =

The source code and build tools are on [GitHub](https://github.com/shameemreza/purplish). The readable source for the scripts ships in `assets/src`, next to the built files in `build`. To build them yourself, run `npm ci` and `npm run build`.

== Installation ==

1. Install and activate WooCommerce.
2. Go to Plugins > Add New, search for Purplish, then install and activate it.
3. Edit a variable product, open a variation, and check its Page color. Automatic is on for every variation already.
4. Block themes: open Appearance > Editor > Templates > Single Product, select the Add to Cart + Options block, and use the Purplish panel. Classic themes: go to WooCommerce > Settings > Products > Purplish.

== Frequently Asked Questions ==

= Does Purplish work with my theme? =

Yes, with block themes and classic themes. On block themes it works with WooCommerce's Add to Cart + Options and Product Gallery blocks. On classic themes it works with WooCommerce's variation dropdowns and product gallery.

= Does it only work with the Purple theme? =

No. Purplish started on the Purple theme, and the name came from there, but it works with any theme.

= Which products does it work on? =

Variable products, where a shopper picks a variation. Simple products and Product Bundles are planned.

= The color Purplish read from a photo looks wrong. What do I do? =

Set the variation's Page color to Custom and pick the color. Photos with a lot going on, like a model in a furnished room, can lead Purplish to the wrong area.

= Can I turn it off for one variation? =

Yes. Set that variation's Page color to Off. It keeps the theme colors.

= Does it slow down my store? =

No. Photos are read in the background with Action Scheduler, which comes with WooCommerce, and the color is saved with the variation. The product page loads a small script and stylesheet only when it has colors to show.

= Do my variation photos leave my site? =

No. Purplish reads the photo files on your server and never sends them anywhere.

= Where do I report a security issue? =

Please report it privately through the [security policy](https://github.com/shameemreza/purplish/security/policy) on GitHub, not in the support forum.

== Privacy ==

Purplish doesn't collect, store or send any personal data. It doesn't set cookies and makes no outside requests.

== Credits ==

The idea of letting each variation's color come from a manual pick or from its photo came from Luigi Teschio ([@gigitux](https://x.com/gigitux) on X, [gigitux](https://github.com/gigitux) on GitHub), a WooCommerce core developer, in a reply on X to the post about customizing the Purple theme's product page for each color. Thank you, Luigi.

== Changelog ==

= 1.0.0 =

* New: Page color field on each variation: Automatic, Custom or Off.
* New: Colors read from variation photos in the background, tuned into a button color and a page tint.
* New: The product page follows the color a shopper picks, on block and classic themes.
* New: Choose which parts take the color: add to cart button, selected option, sale badge, sale price and page tint.
* New: Photo transition: None, Fade or Blur.
* New: Purplish panel on the Add to Cart + Options block, and a settings section for classic themes.

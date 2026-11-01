=== Purplish - Product Page Design for WooCommerce ===
Contributors: shameemreza
Tags: woocommerce, product page, variations, color, product design
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Design the WooCommerce product page: the button, options and sale badge follow the color a shopper picks. Works with block and classic themes.

== Description ==

Purplish gives the WooCommerce product page design options it doesn't have on its own. It adds settings to WooCommerce's own product blocks instead of replacing them, so your store keeps working the way WooCommerce built it. It works with block themes and classic themes.

The first release makes the product page follow the color a shopper picks. When someone picks the orange sweater, the add to cart button, the selected option and the sale badge turn orange. When they pick blue, everything turns blue, and the product photo changes with a soft transition.

= Variation colors, set automatically =

Every variation gets a Page color field in the product editor:

* **Automatic:** Purplish reads the color from the variation image, and the field shows the color it found.
* **Custom:** pick a color, or type a hex code.
* **Off:** the variation keeps your theme's colors.

Simple products get the same field, so a single product page can carry its own color too.

If you use WooCommerce's color swatches for attributes, Purplish uses the swatch color first.

= Colors that look designed, not sampled =

Purplish looks for the product in the photo, not the wall behind it. It also handles a product shot against a matching background, like a green sweater on a green wall.

A color picked straight from a photo is often too dark or too pale for a button. Purplish keeps its hue and adjusts it for each job: a button color with readable black or white text, a darker version for the sale price, and a pale tint for the page background. Colors you set by hand are used exactly as you set them.

= What follows the color =

Choose which parts of the product page take the color:

* Add to cart button
* Selected option (block themes)
* Sale badge
* Sale price
* Page background tint

The button, option and badge are on by default. Pick the photo transition too: Blur, Fade or None.

On block themes, the settings sit in a Purplish panel on the Add to Cart + Options block in the Site Editor, so each product template can have its own look. On classic themes, they're under WooCommerce > Settings > Products > Purplish.

= Gallery, product cards and more =

Purplish adds options to more of WooCommerce's blocks, each in a Purplish panel in the Site Editor:

* **Product Gallery:** hide the thumbnails for one big photo, rounded corners, round frosted arrows that show on hover, a 3D tilt that follows the mouse, and a full-screen view tinted to the page color with photos that snap into place.
* **Product Collection:** add to cart as a small frosted button on the photo, a bag icon and light sweep on hover, a fill after adding to cart, and a hover effect: magnetic panel, spotlight, color reveal or deck.
* **Add to Cart + Options:** the title and price rise in on each color change, and color dots on the option buttons.
* **Accordion:** a soft card style, with a round button that fills with the color when open.
* **Group:** any section can take a soft version of the picked color.

Classic themes get the gallery's thumbnails, rounded corners and 3D tilt, and the title and price rise, under WooCommerce > Settings > Products > Purplish.

= Fast, and quiet when it isn't needed =

* Product images are read once, in the background, with WooCommerce's Action Scheduler. Saving a product stays fast.
* Shoppers load a small stylesheet, plus a small script on variable products. Gallery and card effects load only where a block uses them.
* The page opens in the right color, with no flash of your theme's colors.
* Visitors who ask their device for reduced motion get color changes without animation.
* No outside requests, no tracking, no admin notices.

= Works with =

* WooCommerce 11.0 and later, with block themes like Twenty Twenty-Five and classic themes like Storefront.
* WooCommerce's Add to Cart + Options, Product Gallery, Product Price and Sale Badge blocks.
* High-Performance Order Storage (HPOS), and the cart and checkout blocks.

= Source code =

The code is on [GitHub](https://github.com/shameemreza/purplish). The readable source of the scripts ships in `assets/src`, next to the built files in `build`. To build them, run `npm ci` and `npm run build`.

== Installation ==

1. Install and activate WooCommerce.
2. In Plugins > Add New, search for Purplish, then install and activate it.
3. Purplish starts reading your existing product images in the background. Open a variable product to see each variation's Page color.
4. Block themes: go to Appearance > Editor > Templates > Single Product, select the Add to Cart + Options block, and open the Purplish panel. Classic themes: go to WooCommerce > Settings > Products > Purplish.

== Frequently Asked Questions ==

= How do I change the add to cart button color for each variation in WooCommerce? =

Install Purplish. Each variation takes its color from its image by default, and the add to cart button follows the variation the shopper picks. To set a color yourself, open the variation in the product editor and set Page color to Custom.

= Does Purplish work with my theme? =

Yes. It works with block themes through WooCommerce's product blocks, and with classic themes through WooCommerce's variation dropdowns and product gallery.

= Does it work with simple products? =

Yes. A simple product gets one Page color for its whole page, read from its image or set by hand. Product Bundles support is planned.

= Do I need WooCommerce's color swatches? =

No. Purplish works with the plain dropdowns and option buttons too. If you turn on WooCommerce's swatches for attributes, Purplish uses those swatch colors.

= A color Purplish found looks wrong. What do I do? =

Set the variation's Page color to Custom and pick the color. A busy photo, like a model in a furnished room, can point Purplish at the wrong area.

= Can I turn it off for one variation or product? =

Yes. Set its Page color to Off. It keeps your theme's colors.

= Will Purplish slow down my store? =

No. Images are read in the background and the color is saved with the product. The product page loads a small stylesheet, and a small script only on variable products with colors.

= Do my product images leave my site? =

No. Purplish reads the image files on your own server and never sends them anywhere.

= Does it change WooCommerce's blocks or templates? =

No. Purplish adds a setting to the Add to Cart + Options block and styles the parts you choose. Deactivate it, and your product page looks exactly as it did before.

= Where do I report a security issue? =

Please report it privately through the [security policy](https://github.com/shameemreza/purplish/security/policy) on GitHub, not in the support forum.

== Screenshots ==

1. The product page in the orange variation's color: button, option, sale badge and sale price.
2. The same page after picking blue.
3. The Page color field on a variation: Automatic, Custom or Off.
4. The Purplish panel on the Add to Cart + Options block in the Site Editor.
5. Purplish settings for classic themes under WooCommerce > Settings > Products.

== Privacy ==

Purplish doesn't collect, store or send personal data. It sets no cookies and makes no outside requests.

== Credits ==

Thanks to [Luigi Teschio](https://x.com/gigitux) for the idea, from [a discussion on X](https://x.com/shameemdev/status/2116510521612755209): set each variation's color by hand, or read it from the photo.

== Changelog ==

= 1.0.0 =

* New: Page color field on each variation and simple product: Automatic, Custom or Off.
* New: Colors read from product images in the background, adjusted into a button color, a sale price color and a page tint.
* New: The product page follows the color a shopper picks, on block and classic themes.
* New: Choose which parts take the color: add to cart button, selected option, sale badge, sale price and page tint.
* New: Photo transition: Blur, Fade or None.
* New: Purplish panel on the Add to Cart + Options block, and a settings section for classic themes.
* New: Gallery options: hide thumbnails, rounded corners, frosted arrows, 3D tilt and a styled full-screen view.
* New: Product card options: add to cart on the photo, button motion and four hover effects.
* New: Title and price rise, color dots on options, a soft accordion style and group tint.

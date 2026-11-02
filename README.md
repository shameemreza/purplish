# Purplish: product page design for WooCommerce

Purplish is a free WooCommerce plugin that adds design options to the product page. When a shopper picks a color, the add to cart button, the selected option and the sale badge take that color, and the photo changes with a soft transition. It also adds options to the product gallery, product cards, accordions and sections.

[![CI](https://github.com/shameemreza/purplish/actions/workflows/ci.yml/badge.svg)](https://github.com/shameemreza/purplish/actions/workflows/ci.yml)
[![License: GPLv2 or later](https://img.shields.io/badge/license-GPLv2%20or%20later-blue)](https://www.gnu.org/licenses/gpl-2.0.html)

Every option lives inside the blocks WooCommerce already gives you, so nothing gets replaced and nothing needs rebuilding. It works with block themes and classic themes. There's no paid version and no account to sign up for.

## Features

### The product page follows the color

On a default WooCommerce product page, picking another color changes the photo and nothing else. The button, the badge and the background look the same for the orange sweater and the navy one.

With Purplish, each variation has a Page color. Pick blue, and the page turns blue. Pick orange, and it turns orange.

- **Automatic:** Purplish reads the color from the variation photo, in the background, so saving a product stays fast.
- **Custom:** pick a color, or type a hex code.
- **Off:** the variation keeps your theme's colors.
- **Swatches first:** in Automatic mode, a swatch color set on the color attribute comes before the photo.
- **Simple products:** a simple product gets one color for its whole page.

You choose which parts take the color: the add to cart button, the selected option, the sale badge, the sale price and a pale tint on the page background. The photo can change with blur, fade, zoom, slide, flip, wipe, circle reveal, color wash or no effect.

### Colors that look designed

Purplish looks for the product in the photo, not the wall behind it, and it handles a green sweater shot against a green backdrop.

A color taken straight from a photo is often too dark or too pale for a button. Purplish keeps the hue and adjusts it for each use: a button color with readable black or white text, a darker shade for the sale price, and a pale tint for the background. A color you set by hand is used exactly as you set it.

The page opens in the default variation's color, so there's no flash of the theme's colors first.

### Product gallery

- **Hide thumbnails:** one big photo, with no thumbnail strip.
- **Rounded corners:** on the main photo.
- **Frosted arrows:** round arrows that show on hover.
- **Hover style:** what the photo does under the mouse. A 3D tilt toward the mouse, a zoom where the mouse points, a lift with a soft shadow, a parallax drift, a light glare, a round loupe, or a slow push in.
- **Full-screen style:** the view that opens when a shopper clicks the photo. A snap stack tinted to the page color, a classic fade, a zoom out of the photo's own place, a filmstrip of small photos along the bottom, or a dark cinema screen.
- **Slide style:** how the next photo comes in. A 3D turn like a cube, a swing like a wheel, a film reel, or out of the distance like a tunnel.

### Product cards

On shop and collection pages, add to cart becomes a small frosted button on the photo, with a bag icon and a sweep of light on hover. Pick one card effect for the whole collection.

On hover:

- **Magnetic panel:** a soft panel in your effect color slides under the card.
- **Spotlight:** the card under the mouse grows a little while the rest blur and fade back.
- **Color reveal:** the photos sit in a color wash until the mouse brings one to full color.
- **Fan deck:** cards rest a little off straight; the one under the mouse straightens and its neighbors lean away.
- **Expanded cards:** each photo shows as a narrow band and opens to full under the mouse.
- **Card folder:** each card sits in a soft folder with a tab, and the photo lifts out on hover.

In a carousel (a grid stays as it is):

- **Deck:** the cards at the edges turn away and the one under the mouse faces you.
- **Arc:** the cards sit on a curve, highest in the middle.
- **Spotlight carousel:** the card in the middle is full size and the rest fade back.
- **Grow:** cards grow as they reach the middle.
- **Tilt:** cards lean as they travel across.

As cards scroll in:

- **Clothes line:** each card hangs from a peg on a line and swings as it comes in or when the mouse touches it.
- **Parallax:** the photo moves slower than its card.
- **Zoom reveal:** each card grows in as it appears.

### Details

- **Title and price rise:** they rise in again each time the color changes.
- **Title focus:** or the title comes into focus each time the color changes.
- **Glow ring:** a soft ring of the page color around the add to cart button.
- **Halo:** a ring leaves the button each time the product goes into the cart.
- **Mesh background:** with the page tint on, soft blobs of the color over the tint, which move to a new place each time the color changes.
- **Color dots:** each color option shows a dot in its own color. An option gets a dot only when every variation with it has the same color, like Orange or Blue. A size, or any option that comes in several colors, gets none. On the picked option, the dot turns the button's text color so it stays visible.
- **Soft accordion:** accordion items become soft cards, and the open one fills with the page color.
- **Section color:** any Group block can take a soft version of the page color.

Nothing moves on its own: every effect plays on a hover, a scroll, a color change or an add to cart, then stops. Every motion turns off for visitors who ask their device for reduced motion.

## Set it up

1. Install and activate Purplish. Purplish reads the colors of your existing variation photos in the background.
2. To change a variation's color, edit the product, open Variations, and use the Page color field. For a simple product, it's on the General tab.
3. Pick what takes the color:
   - **Block themes:** open the Single Product template in the Site Editor, select the Add to Cart + Options, Product Gallery, Product Collection, Accordion or Group block, and find the Purplish panel. Each template keeps its own settings, so one product can have its own look.
   - **Classic themes:** go to WooCommerce > Settings > Products > Purplish.

Classic themes get the color parts, the photo transition, the title rise, and three gallery options: hide thumbnails, rounded corners and the hover style. Product cards, color dots, accordions and sections need a block theme.

Turn Purplish off, and the page goes back to exactly how WooCommerce draws it.

## Performance and privacy

- **Loads only where it's used:** the effect styles load only on pages that use them, and a simple product page needs no script at all.
- **Photos stay on your server:** Purplish reads them with the image tools PHP already has. It makes no outside requests and adds no tracking.
- **No admin noise:** no banners, notices or upsells.
- **Compatible with WooCommerce features:** High-Performance Order Storage (HPOS) and the cart and checkout blocks.

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

GPLv2 or later.

Closes #

## What changed

## Why

## How I tested it

- [ ] CI passed
- [ ] Tested by hand: 

## Speed

For anything that moves or loads on the front end. Tick what applies and say why in a line when one can't be met.

- [ ] CSS first: scroll motion uses scroll timelines, script only for drag, keys or physics.
- [ ] Block front scripts under 5 KB gzipped (`npm run check:size`, CI checks it). An option adds no script unless it needs one, and only on pages that use it.
- [ ] No libraries, no canvas or WebGL, no blur filter on repeated items. Repeated items animate `transform` and `opacity` only.
- [ ] The first hero image is a real `<img>` with `fetchpriority="high"`, visible on load (no `opacity: 0` start).
- [ ] Frame loops pause off screen and in hidden tabs. Nothing loops on its own.
- [ ] A still version for reduced motion, touch screens and browsers without scroll timelines.
- [ ] PageSpeed mobile on the test store, before and after: 

## Accessibility and touch

For anything a shopper can click, tap, drag, hover or read. The full list is in CONTRIBUTING.

- [ ] Keyboard: Tab reaches every control, Enter or Space uses it, Escape closes it, and focus stays visible.
- [ ] Screen reader: every control has a name, alt text matches the photo on screen, and changes are announced.
- [ ] Touch: tap areas are at least 24px, hover styles are inside `@media (hover: hover)`, vertical swipes still scroll, and every drag has a tap or key option.
- [ ] Checked with VoiceOver or NVDA, and on a phone: 

## Screenshots

Before and after, for anything someone can see.

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

## Screenshots

Before and after, for anything someone can see.

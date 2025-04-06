# @uppercod/match-media

Allows to define a value in post of an evaluation of a string whose pattern is like [`img[srcset]`](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#attr-srcset).

## Example

```js
import media from "@upperco/match-media";

const template = media`
    https://cloudinary-res.cloudinary.com/images/c_scale,w_300,dpr_1.0/f_auto,q_auto/v1630689385/sandwich_video_static-frame/sandwich_video_static-frame.png,
    ${520} 520px,
    ${720} 720px,
    ${1080} 1080px
`;

const value = template.match(({ size, unit }) => {
  const match = matchMedia(`(min-width: ${size}${unit})`);
  return match.matches;
});
```

The return format is raw if parameters are used, you can only have one parameter per group (The groups are not defined by the use of the character `,`).

## Objective

Being able to execute match with javascript using matchMedia or ResizeObserver

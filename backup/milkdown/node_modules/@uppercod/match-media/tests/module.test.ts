import { test } from "uvu";
import * as assert from "uvu/assert";
import media from "../src/module";

test("media", () => {
  const template = media`
        https://cloudinary-res.cloudinary.com/images/c_scale,w_300,dpr_1.0/f_auto,q_auto/v1630689385/sandwich_video_static-frame/sandwich_video_static-frame.png,
        ${520} 520px,
        ${720} 720px,
        ${1080} 1080px
    `;

  assert.is(
    JSON.stringify(template.result.map(({ value, ...match }) => match)),
    JSON.stringify([
      {
        size: -1 >>> 0,
        unit: "",
        param: undefined,
        default: true,
      },
      {
        size: 1080,
        unit: "px",
        param: "2",
      },
      {
        size: 720,
        unit: "px",
        param: "1",
      },
      {
        size: 520,
        unit: "px",
        param: "0",
      },
    ])
  );

  const value1 = template.match(() => false);

  assert.is(
    "https://cloudinary-res.cloudinary.com/images/c_scale,w_300,dpr_1.0/f_auto,q_auto/v1630689385/sandwich_video_static-frame/sandwich_video_static-frame.png",
    value1
  );

  const value2 = template.match(({ size }) => size === 520);

  assert.is(value2, 520);
});

test.run();

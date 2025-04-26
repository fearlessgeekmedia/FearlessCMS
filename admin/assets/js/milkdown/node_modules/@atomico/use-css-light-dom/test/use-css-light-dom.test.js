import { css } from "atomico";
import { createHooks } from "atomico/test-hooks";
import { expect, it } from "vitest";
import { useCssLightDom } from "../src";

it("useCss", () => {
	const host = document.body.appendChild(document.createElement("div"));

	const hooks = createHooks(() => {}, host);

	const sheet = css`
		:host {
			width: 100px;
			height: 100px;
			color: red;
			border-radius: 50%;
			display: flex;
		}
	`;

	hooks.load(() => useCssLightDom(sheet));

	hooks.cleanEffects()();

	const style = getComputedStyle(host);

	expect(style.width).to.equal("100px");
	expect(style.height).to.equal("100px");
	expect(style.borderRadius).to.equal("50%");
	expect(style.display).to.equal("flex");
});

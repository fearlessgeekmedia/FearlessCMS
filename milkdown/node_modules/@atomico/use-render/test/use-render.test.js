import { it, expect } from "vitest";
import { html } from "atomico";
import { createHooks } from "atomico/test-hooks";
import { useRender } from "../src/";

it("use-render", () => {
	const container = document.createElement("div");
	const hooks = createHooks(() => {}, container);

	hooks.load(() => {
		useRender(() => html`<button />`);
	});

	hooks.cleanEffects()()();

	expect(container.querySelector("button")).to.not.equal(null);
});

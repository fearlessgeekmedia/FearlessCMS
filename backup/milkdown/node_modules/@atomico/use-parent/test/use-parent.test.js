import { it, expect } from "vitest";
import { createHooks } from "atomico/test-hooks";
import { useParent } from "../src";

it("useParent", () => {
	const host = document.createElement("div");
	const hooks = createHooks(null, host);

	document.body.appendChild(host);

	hooks.load(() => {
		const ref = useParent("body");
		expect(ref.current).to.equal(document.body);
	});
});

it("useParent shadowRoot", () => {
	const host = document.createElement("div");
	const child = new Image();
	host.attachShadow({ mode: "open" }).appendChild(child);
	const hooks = createHooks(null, child);

	document.body.appendChild(host);

	hooks.load(() => {
		const ref = useParent("body", true);
		expect(ref.current).to.equal(document.body);
	});
});

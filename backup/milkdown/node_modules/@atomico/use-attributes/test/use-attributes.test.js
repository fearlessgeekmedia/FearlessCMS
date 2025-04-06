import { describe, it, expect } from "vitest";
import { createHooks } from "atomico/test-hooks";
import { useAttributes } from "../src";

describe("useAttributes", () => {
	it("case 1", () => {
		const div = document.createElement("div");

		const hooks = createHooks(null, div);

		const load = () => useAttributes();

		div.setAttribute("random-attr", "10");
		div.setAttribute("class", "class-10");
		div.setAttribute("id", "id-10");

		div.dataset.random = "...100";

		const attrs = hooks.load(load);

		expect(attrs).to.deep.equal({
			randomAttr: "10",
			class: "class-10",
			id: "id-10",
			dataRandom: "...100",
		});
	});

	it("case 2", async () => {
		const results = [];

		const div = document.createElement("div");

		const hooks = createHooks(() => {
			hooks.load(load);
		}, div);

		const load = () => results.push(useAttributes());

		div.setAttribute("random-attr", "10");
		div.setAttribute("class", "class-10");

		hooks.load(load);
		hooks.cleanEffects()()();

		await new Promise((resolve) => setTimeout(resolve, 50));

		div.dataset.random = "...100";
		div.setAttribute("id", "id-10");

		await new Promise((resolve) => setTimeout(resolve, 50));

		expect(results).to.deep.equal([
			{ randomAttr: "10", class: "class-10" },
			{
				randomAttr: "10",
				class: "class-10",
				dataRandom: "...100",
				id: "id-10",
			},
		]);
	});
});

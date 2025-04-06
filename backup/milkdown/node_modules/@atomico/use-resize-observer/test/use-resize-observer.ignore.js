import { describe, it, expect } from "vitest";
import { createHooks } from "atomico/test-hooks";
import { useResizeObserver } from "../src";

describe("useResizeObserver", () => {
	it("loop", async () => {
		const values = [];
		const addValue = (value) => values.push(value);
		const current = document.createElement("div");

		current.style.width = "100px";

		document.body.appendChild(current);

		const hooks = createHooks(() => hooks.load(load), current);

		const ref = { current };

		const load = () => {
			useResizeObserver(ref, addValue);
		};

		hooks.load(load);

		hooks.cleanEffects()()();

		await new Promise((resolve) => setTimeout(resolve, 50)); // emulate task queue

		ref.current.style.width = "200px";

		await new Promise((resolve) => setTimeout(resolve, 50)); // emulate task queue

		expect(values).to.deep.equal([
			{
				x: 0,
				y: 0,
				width: 100,
				height: 0,
				top: 0,
				right: 100,
				bottom: 0,
				left: 0,
			},
			{
				x: 0,
				y: 0,
				width: 200,
				height: 0,
				top: 0,
				right: 200,
				bottom: 0,
				left: 0,
			},
		]);
	});
});

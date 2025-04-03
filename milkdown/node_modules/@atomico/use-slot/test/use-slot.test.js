import { it, expect } from "vitest";
import { asyncEventListener } from "atomico/test-dom";
import { TestElement } from "./element";

it("useSlot", async () => {
	const element = new TestElement();

	document.body.append(element);

	element.innerHTML = `<span></span>`;

	const event1 = await asyncEventListener(element, "changeSlot"); // 0 SLOTS

	expect(event1.detail).toEqual(0);

	const event2 = await asyncEventListener(element, "changeSlot");

	expect(event2.detail).toEqual(1);
});

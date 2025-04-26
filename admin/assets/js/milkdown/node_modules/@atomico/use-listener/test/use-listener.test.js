import { it } from "vitest";
import { Element } from "./element";
import { asyncEventListener } from "atomico/test-dom";

it("useListener", async () => {
	const element = new Element();

	document.body.append(element);

	await element.updated;

	setTimeout(() => {
		element.dispatchEvent(new Event("click"));
	}, 100);

	await asyncEventListener(element, "fromUseListener");
});

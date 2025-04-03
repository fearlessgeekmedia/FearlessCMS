import { asyncEventListener } from "atomico/test-dom";
import { it } from "vitest";
import { Element } from "./element";

it("useKeyboard", async () => {
	const element = new Element();

	document.body.append(element);

	await element.updated;

	const input = element.querySelector("input");

	setTimeout(() => {
		input.dispatchEvent(
			new KeyboardEvent("keydown", { code: "KeyQ", key: "1" }),
		);

		input.dispatchEvent(
			new KeyboardEvent("keydown", { code: "KeyA", key: "1" }),
		);

		input.dispatchEvent(
			new KeyboardEvent("keyup", { code: "KeyQ", key: "1" }),
		);

		input.dispatchEvent(
			new KeyboardEvent("keyup", { code: "KeyA", key: "1" }),
		);

		input.dispatchEvent(
			new KeyboardEvent("keydown", { code: "KeyA", key: "2" }),
		);
		input.dispatchEvent(
			new KeyboardEvent("keydown", { code: "KeyQ", key: "2" }),
		);
	}, 100);

	await asyncEventListener(element, "matchKeys");
});

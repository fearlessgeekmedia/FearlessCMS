import { c, html } from "atomico";
import { fixture } from "atomico/test-dom";
import { it, expect } from "vitest";
import { useFormInputHidden } from "../src/";

it("useFormInputHidden", async () => {
	const Component = c(() => {
		useFormInputHidden("field", "20");
		return html`<host shadowDom></host>`;
	});

	customElements.define("use-form-value", Component);

	const node = new Component();

	const form = fixture(
		html`<form>
			<${node} />
		</form>`,
	);

	await node.updated;

	expect(form.elements.field.value).to.equal("20");
});

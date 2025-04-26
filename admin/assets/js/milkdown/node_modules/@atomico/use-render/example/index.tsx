import { c } from "atomico";
import { useRender } from "../src";

const MyComponent = c(
	({ name, checked }) => {
		useRender(<input type="checkbox" name={name} checked={checked} />);
		useRender(() => (
			<input type="checkbox" name={name} checked={checked} />
		));

		return (
			<host shadowDom>
				<slot />
			</host>
		);
	},
	{
		props: {
			name: String,
			checked: Boolean,
		},
	},
);

customElements.define("my-component", MyComponent);

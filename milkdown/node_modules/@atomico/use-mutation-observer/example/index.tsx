import { c } from "atomico";
import { useMutationObserver } from "../src";

const MyComponent = c(() => {
	useMutationObserver((mutations) => {
		console.log({ mutations });
	});
	return (
		<host shadowDom>
			<slot />
		</host>
	);
});

customElements.define("my-component", MyComponent);

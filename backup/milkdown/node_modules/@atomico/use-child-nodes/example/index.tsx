import { c } from "atomico";
import { useChildNodes } from "../src";

const MyComponent = c(() => {
	const childNodes = useChildNodes();
	return (
		<host shadowDom={{ slotAssignment: "manual" }}>
			{childNodes
				.filter((el) => el instanceof HTMLElement)
				.map((child: HTMLElement) => (
					<slot assignNode={child}></slot>
				))}
		</host>
	);
});

customElements.define("my-component", MyComponent);

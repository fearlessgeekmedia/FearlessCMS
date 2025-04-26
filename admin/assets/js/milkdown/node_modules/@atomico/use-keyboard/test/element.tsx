import { c, useEvent, useRef } from "atomico";
import { useKeyboard } from "../src";

export const Element = c(() => {
	const ref = useRef();
	const dispatch = useEvent("matchKeys");
	useKeyboard(ref, ["KeyA", "KeyQ"], dispatch);
	return (
		<host>
			<input ref={ref} />
		</host>
	);
});

customElements.define("test-element-use-keyboard-1", Element);

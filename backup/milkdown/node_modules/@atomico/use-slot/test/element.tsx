import { c, useRef, useEvent, useEffect } from "atomico";
import { useSlot } from "../src";

export const TestElement = c(() => {
	const ref = useRef();
	const dispatch = useEvent("changeSlot");

	const slots = useSlot(ref);

	useEffect(() => {
		dispatch(slots.length);
	}, [slots]);

	return (
		<host shadowDom>
			<slot ref={ref} />
		</host>
	);
});

customElements.define("test-element-use-slot-1", TestElement);

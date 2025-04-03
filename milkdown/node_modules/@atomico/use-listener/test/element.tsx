import { c, useEvent, useRef } from "atomico";
import { useListener } from "../src";

export const Element = c(() => {
	const host = useRef();
	const dispatch = useEvent("fromUseListener");
	useListener(host, "click", dispatch);
	return <host ref={host}></host>;
});

customElements.define("test-element-use-listener-1", Element);

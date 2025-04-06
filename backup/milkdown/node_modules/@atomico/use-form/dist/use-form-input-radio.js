import { useProp, useRef, useEffect } from "atomico";
import { useListener } from "@atomico/use-listener";
import { useRender } from "@atomico/use-render";
import { useFormListener } from "./core.js";
/**
 * reflects input radio in forms, this hook requires the declaration
 * of the props checked: Boolean and name: String
 */
export function useFormInputRadio(input) {
    const ref = useRef();
    const [checked, setChecked] = useProp("checked");
    const [name] = useProp("name");
    useFormListener("change", ({ currentTarget, target }) => {
        if (!(target instanceof HTMLInputElement))
            return;
        if (currentTarget instanceof HTMLFormElement) {
            const group = currentTarget.elements[name];
            if (group instanceof RadioNodeList) {
                [...group].forEach((input) => {
                    input.checked = target === input;
                });
            }
        }
        setChecked(target === ref.current);
    });
    useFormListener("reset", () => setChecked(false));
    useRender(() => ({
        ...input,
        props: {
            ...input.props,
            ref,
            type: "radio",
            name,
            checked,
        },
    }));
    useListener(ref, "change", (event) => {
        setChecked(event.currentTarget.checked);
    });
    useEffect(() => {
        setChecked(ref.current.checked);
    }, []);
    return ref;
}

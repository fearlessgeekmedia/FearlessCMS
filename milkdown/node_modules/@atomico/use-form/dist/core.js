import { jsx as _jsx } from "atomico/jsx-runtime";
import { useListener } from "@atomico/use-listener";
import { useParent } from "@atomico/use-parent";
import { useRender } from "@atomico/use-render";
import { useState } from "atomico";
export function useForm() {
    return useParent("form");
}
/**
 * Allows you to listen to the native events of the form
 */
export function useFormListener(name, handler, options) {
    useListener(useForm(), name, handler, options);
}
export function useFormInputHidden(name, value) {
    const [slot] = useState(Math.random);
    useRender(() => (_jsx("input", { type: "hidden", value: value, name: name, slot: `${slot}` })), [name, value]);
}

import { useHost, useState } from "atomico";
import { useMutationObserver } from "@atomico/use-mutation-observer";
const getProp = (value) => value.replace(/-(\w)/g, (all, letter) => letter.toUpperCase());
const mapAttributes = (element) => {
    const { constructor } = element;
    const props = constructor.props || {};
    const attrs = {};
    return Object.values(element.attributes).reduce((attrs, attr) => {
        const prop = getProp(attr.name);
        if (!(prop in props))
            attrs[prop] = attr.value;
        return attrs;
    }, attrs);
};
export function useAttributes() {
    const host = useHost();
    const setAttributes = () => mapAttributes(host.current);
    const [state, setState] = useState(setAttributes);
    useMutationObserver(() => setState(setAttributes), {
        attributes: true,
    });
    return state;
}

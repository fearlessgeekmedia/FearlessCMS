import { useHost, useState } from "atomico";
import { useResizeObserver } from "@atomico/use-resize-observer";
import media from "@uppercod/match-media";
const CACHE_TEMPLATE = {};
export function useRefResizeState(ref, sizes) {
    const template = media.call(null, {
        raw: [sizes],
    });
    const [state, setState] = useState();
    function getState() {
        const { clientWidth } = ref.current;
        return template.match(({ size }) => size <= clientWidth);
    }
    useResizeObserver(ref, () => setState(getState));
    return state;
}
export function useResizeState(part, ...args) {
    const ref = useHost();
    let template;
    if (typeof part === "string") {
        if (!CACHE_TEMPLATE[part]) {
            CACHE_TEMPLATE[part] = media.call(null, { raw: [part] });
        }
        template = CACHE_TEMPLATE[part];
    }
    else {
        template = media.call(null, part, ...args);
    }
    const [state, setState] = useState();
    function getState() {
        const { clientWidth } = ref.current;
        return template.match(({ size }) => size <= clientWidth);
    }
    useResizeObserver(ref, () => setState(getState));
    return state;
}

import { useEffect, useState } from "atomico";
import media from "@uppercod/match-media";
const CACHE = {};
const CACHE_TEMPLATE = {};
const getId = ({ size, unit }) => `(min-width: ${size}${unit})`;
export function useResponsiveState(part, ...args) {
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
    const check = () => template.match((result) => {
        const id = getId(result);
        CACHE[id] = CACHE[id] || matchMedia(id);
        return CACHE[id].matches;
    });
    const [value, setValue] = useState(check);
    useEffect(() => {
        const unlisteners = template.result
            .filter((result) => !result.default)
            .map((result) => {
            const id = getId(result);
            CACHE[id] = CACHE[id] || matchMedia(id);
            const eventType = "change";
            const eventHandler = () => {
                setValue(check);
            };
            CACHE[id].addEventListener(eventType, eventHandler);
            return () => {
                CACHE[id].removeEventListener(eventType, eventHandler);
            };
        });
        setValue(check);
        return () => unlisteners.forEach((fn) => fn());
    }, [template.result]);
    return value;
}

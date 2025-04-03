import { useHost, useLayoutEffect } from "atomico";
import { getRules, getSheet } from "./utils.js";
const ATTR = "data-sheet";
let ID = 0;
/**
 * Create a style collector to apply once the render is finished
 */
export function useCssLightDom(sheet) {
    const host = useHost();
    useLayoutEffect(() => {
        const style = document.createElement("style");
        const { current } = host;
        if (!current.hasAttribute(ATTR)) {
            current.setAttribute(ATTR, `${ID++}`);
        }
        current.appendChild(style);
        if (style.sheet) {
            (Array.isArray(sheet) ? sheet.flat(100) : [sheet]).forEach((target) => {
                if (!target)
                    return;
                const sheet = getSheet(target);
                getRules(sheet, current.localName +
                    `[data-sheet="${current.dataset.sheet}"]`).forEach((rule) => style.sheet.insertRule(rule, style.sheet.cssRules.length));
            });
        }
        return () => style.remove();
    }, [sheet]);
}

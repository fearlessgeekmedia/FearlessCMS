import { useListener } from "@atomico/use-listener";
/**
 * reflects an event to another node
 */
export const reflectEvent = (current, event, composed) => {
    const { currentTarget } = event;
    const { shadowRoot } = currentTarget;
    const path = event.composedPath();
    if (path.includes(current))
        return;
    const index = path.indexOf(currentTarget);
    const insetShadowRoot = path
        .slice(0, index)
        .find((el) => el instanceof ShadowRoot);
    if (!composed && insetShadowRoot !== shadowRoot)
        return;
    event.preventDefault();
    event.stopImmediatePropagation();
    //@ts-ignore
    current.dispatchEvent(new event.constructor(event.type, event));
};
/**
 * This hook reflects an event and cancels its propagation
 */
export function useReflectEvent(refFrom, refTo, type, { capture = true, composed = true, } = {}) {
    useListener(refFrom, type, (event) => {
        const { current } = refTo;
        current && reflectEvent(current, event, composed);
    }, { capture });
}

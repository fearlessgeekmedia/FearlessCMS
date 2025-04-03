import { useCurrentValue } from "@atomico/use-current-value";
import { useRefEffect, useState } from "atomico";
const listenersId = Symbol();
let resizeObserver;
/**
 * Observe the ResizeObserver state of a reference
 * @example
 * ```js
 * useResizeObserver(ref, (rect)=>{
 *  console.log(rect.width)
 * });
 * ```
 */
export function useResizeObserver(ref, callback) {
    const value = useCurrentValue(callback);
    useRefEffect(() => {
        const { current } = ref;
        if (!current)
            return;
        if (!resizeObserver) {
            resizeObserver = new ResizeObserver((entries) => entries.forEach(({ contentRect, target }) => {
                const rect = contentRect.toJSON();
                const listeners = target[listenersId];
                for (const listener of listeners)
                    listener(rect);
            }));
        }
        /**
         * @param {Rect} rect
         */
        const listener = (rect) => value.current(rect);
        if (!current[listenersId]) {
            resizeObserver.observe(current);
            current[listenersId] = new Set();
        }
        current[listenersId].add(listener);
        return () => {
            current[listenersId].delete(listener);
            if (!current[listenersId].size) {
                delete current[listenersId];
                resizeObserver.unobserve(current);
            }
        };
    }, [ref]);
}
/**
 * Observes the ResizeObserver state of a reference and reflects
 * it to a local state of the component
 * @example
 * ```js
 * const ref = useResizeObserverState(ref);
 * if(ref){
 *  console.log(ref.width)
 * }
 * ```
 */
export function useResizeObserverState(ref) {
    const [rect, setRect] = useState();
    useResizeObserver(ref, setRect);
    return rect;
}

import { useState, useRef } from "atomico";
const fpsP = () => new Promise((resolve) => requestAnimationFrame(() => resolve()));
export const fps = async (length = 1) => {
    let current = Promise.resolve();
    while (length--)
        current = current.then(fpsP);
    await current;
};
export const idle = (ms) => window.requestIdleCallback
    ? new Promise((resolve) => requestIdleCallback(resolve, { timeout: ms }))
    : timeout(ms);
export const timeout = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
export function useDebounceState(delay, initialState, mode = "timeout") {
    const [state, setState] = useState(initialState);
    const ref = useRef();
    return [
        state,
        (current) => {
            ref.current = current;
            if (!ref.prevent) {
                ref.prevent = true;
                (mode === "fps" ? fps : mode === "idle" ? idle : timeout)(delay).then(() => {
                    ref.prevent = false;
                    setState(ref.current);
                });
            }
        },
    ];
}

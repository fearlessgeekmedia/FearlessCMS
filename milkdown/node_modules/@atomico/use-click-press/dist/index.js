import { useRef } from "atomico";
import { useListener } from "@atomico/use-listener";
/**
 * Capture the click or touch event to execute the callback multiple times,
 * depending on the type of pressure
 */
export function useClickPress(ref, callback, aceleration = 0.9, minMs = 24) {
    const ctx = useRef();
    const isEventIsTouches = (event) => event.type.startsWith("touch");
    const handlerStart = (event) => {
        let isTouches = isEventIsTouches(event);
        if (isTouches)
            ctx.touches = true;
        if (!ctx.current && (ctx.touches ? isTouches : true)) {
            ctx.current = true;
            const loop = (ms) => {
                ms = minMs > ms ? minMs : ms;
                if (ctx.current) {
                    ctx.timeout = setTimeout(() => {
                        if (ctx.current) {
                            callback();
                            loop(ms * aceleration);
                        }
                    }, ms);
                }
            };
            loop(200);
        }
    };
    const handlerStop = (event) => {
        let isTouches = isEventIsTouches(event);
        if (ctx.touches && !isTouches)
            return;
        if (ctx.timeout && ctx.current) {
            clearInterval(ctx.timeout);
            callback();
        }
        ctx.current = false;
    };
    useListener(ref, "mousedown", handlerStart);
    useListener(ref, "touchstart", handlerStart);
    const x = new Image();
    useListener(ref, "keydown", (event) => event.code === "Space" && handlerStart(event));
    useListener(ref, "mouseup", handlerStop);
    useListener(ref, "mouseleave", handlerStop);
    useListener(ref, "touchend", handlerStop);
    useListener(ref, "touchmove", handlerStop);
    useListener(ref, "keyup", (event) => handlerStop(event));
}

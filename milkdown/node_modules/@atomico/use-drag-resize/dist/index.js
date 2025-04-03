import { useDebounceState } from "@atomico/use-debounce-state";
import { useListener } from "@atomico/use-listener";
import { useEffect, useState } from "atomico";
export function useDragResize(ref, refParent, rootState = [1, 1]) {
    const [active, setActive] = useState(false);
    const [[x, y], setValue] = useDebounceState(1, rootState, "fps");
    useEffect(() => setValue(rootState), rootState);
    const start = () => setActive(true);
    const end = () => setActive(false);
    useListener(ref, "mousedown", start);
    useListener(refParent, "mouseup", end);
    useListener(refParent, "mouseleave", end);
    useListener(ref, "touchstart", start);
    useListener(refParent, "touchend", end);
    useListener(refParent, "touchmove", (event) => {
        const { targetTouches: [touche], } = event;
        const rect = refParent.current.getBoundingClientRect();
        const offsetX = touche.pageX - rect.x;
        const offsetY = touche.pageY - rect.y;
        const { current } = refParent;
        setValue([
            offsetX / current.clientWidth,
            offsetY / current.clientHeight,
        ]);
    });
    useListener(refParent, "mousemove", (event) => {
        if (active) {
            const { current } = refParent;
            setValue([
                event.offsetX / current.clientWidth,
                event.offsetY / current.clientHeight,
            ]);
        }
    });
    return [active, x, y];
}

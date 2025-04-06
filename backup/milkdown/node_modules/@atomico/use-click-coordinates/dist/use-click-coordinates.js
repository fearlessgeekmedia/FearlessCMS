import { useCurrentValue } from "@atomico/use-current-value";
import { useListener } from "@atomico/use-listener";
export function useClickCoordinates(ref, callback) {
    const value = useCurrentValue(callback);
    useListener(ref, "click", (event) => {
        const coordinates = getCoordinates(event);
        coordinates && value.current(coordinates);
    });
}
export function getCoordinates({ pageX: x, pageY: y, currentTarget, }) {
    const rect = currentTarget.getBoundingClientRect();
    return {
        x,
        y,
        offset: {
            x: x - rect.left,
            y: y - rect.top,
        },
    };
}

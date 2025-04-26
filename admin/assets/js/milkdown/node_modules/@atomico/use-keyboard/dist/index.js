import { useCurrentValue } from "@atomico/use-current-value";
import { addListener } from "@atomico/use-listener";
import { useRefEffect } from "atomico";
export function useKeyboard(ref, keys, callback) {
    const value = useCurrentValue(callback);
    useRefEffect(() => {
        const { current } = ref;
        if (!current)
            return;
        const history = new Set();
        const check = () => {
            if (keys.length == history.size) {
                const map = [...history];
                if (map.every((code, i) => code == keys[i])) {
                    return true;
                }
            }
        };
        const removeKeydown = addListener(current, "keydown", (event) => {
            history.add(event.code);
            if (check())
                value.current(event);
        });
        const removeKeyup = addListener(current, "keyup", (event) => {
            if (check())
                value.current(event);
            history.delete(event.code);
        });
        return () => {
            removeKeydown();
            removeKeyup();
        };
    }, [ref]);
}

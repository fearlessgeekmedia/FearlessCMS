import { useRef } from "atomico";
export function useCurrentValue(value) {
    const ref = useRef();
    ref.current = value;
    return ref;
}

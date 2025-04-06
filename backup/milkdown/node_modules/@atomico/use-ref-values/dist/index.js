import { useEffect, useRef, useLayoutEffect } from "atomico";
export function useRefValues(callback, args, mode) {
    const { current } = useRef({ values: [], mode });
    const clean = () => {
        if (typeof current.collector === "function") {
            current.collector();
            delete current.collector;
        }
    };
    const effect = current.mode ? useEffect : useLayoutEffect;
    effect(() => clean, []);
    effect(() => {
        const oldValues = current.values;
        const values = args.map((ref) => ref.current);
        const withDiff = values.some((value, i) => value !== oldValues[i]);
        if (withDiff) {
            clean();
            if (values.filter((value) => value != null).length === args.length) {
                current.collector = callback(values);
            }
        }
        current.values = values;
    });
}

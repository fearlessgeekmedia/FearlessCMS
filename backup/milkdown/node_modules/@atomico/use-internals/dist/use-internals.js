import { useHost, useState } from "atomico";
const INTERNALS = Symbol("attachInternals");
export function useInternals() {
    const host = useHost();
    const [internals] = useState(() => {
        const { current } = host;
        if (!current[INTERNALS]) {
            current[INTERNALS] = current.attachInternals();
        }
        return current[INTERNALS];
    });
    return internals;
}

import { useHost, useState } from "atomico";
const INTERNALS = Symbol.for("@atomico/use-internals");
export function useInternals() {
    const { current } = useHost();
    const [internals] = useState(() => (current[INTERNALS] =
        current[INTERNALS] || current.attachInternals()));
    return internals;
}

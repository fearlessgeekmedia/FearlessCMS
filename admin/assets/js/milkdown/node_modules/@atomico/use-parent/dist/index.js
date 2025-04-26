import { createRef, useHost, useMemo } from "atomico";
export function useParent(matches, composed) {
    const path = useParentPath(composed);
    return useMemo(() => createRef(path.find(typeof matches === "string"
        ? (el) => el?.matches?.(matches)
        : //@ts-ignore
            (el) => el instanceof matches)), [matches]);
}
export function useParentPath(composed) {
    const host = useHost();
    return useMemo(() => {
        const path = [];
        let { current } = host;
        //@ts-ignore
        while ((current = current.parentNode || (composed && current.host)))
            path.push(current);
        return path;
    }, [composed]);
}

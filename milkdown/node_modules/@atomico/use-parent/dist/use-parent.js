import { useHost, useMemo } from "atomico";
export function useParent(matches, composed) {
    const path = useParentPath(composed);
    return useMemo(() => ({
        current: path.find((el) => el.matches && el.matches(matches)),
    }), [matches]);
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

import { useHost, useRefEffect } from "atomico";
export function useRefIntersectionObserver(ref, callback, options) {
    useRefEffect(() => {
        const { current } = ref;
        if (!current)
            return;
        const intersection = new IntersectionObserver(callback, options);
        intersection.observe(current);
        return () => intersection.disconnect();
    }, [ref]);
}
export function useIntersectionObserver(callback, options) {
    const host = useHost();
    useRefIntersectionObserver(host, callback, options);
}

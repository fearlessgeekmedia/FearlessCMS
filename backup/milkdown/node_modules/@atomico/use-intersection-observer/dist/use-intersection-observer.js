import { useHost } from "atomico";
import { useRefValues } from "@atomico/use-ref-values";
export function useRefIntersectionObserver(ref, callback, options) {
    useRefValues(() => {
        const intersection = new IntersectionObserver(callback, options);
        intersection.observe(ref.current);
        return () => intersection.disconnect();
    }, [ref], true);
}
export function useIntersectionObserver(callback, options) {
    const host = useHost();
    useRefIntersectionObserver(host, callback, options);
}

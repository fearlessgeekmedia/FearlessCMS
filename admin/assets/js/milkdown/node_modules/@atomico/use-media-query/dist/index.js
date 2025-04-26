import { useUpdate, useEffect } from "atomico";
const medias = {};
/**
 * listen to a media query expression
 */
export function useMediaQuery(size) {
    const update = useUpdate();
    medias[size] = medias[size] || window.matchMedia(size);
    useEffect(() => {
        medias[size].addListener(update);
        return () => medias[size].removeListener(update);
    }, [medias[size]]);
    return medias[size].matches;
}

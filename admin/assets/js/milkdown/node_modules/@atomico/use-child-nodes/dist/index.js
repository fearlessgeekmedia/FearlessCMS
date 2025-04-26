import { useMutationObserver } from "@atomico/use-mutation-observer";
import { Mark, useHost, useState } from "atomico";
const getChildNodes = (element) => {
    return Array.from(element.childNodes).filter((el) => !(el instanceof Mark) && !(el instanceof Comment));
};
export function useChildNodes(config = {
    childList: true,
}) {
    const host = useHost();
    const [childNodes, setChildNodes] = useState(() => getChildNodes(host.current));
    useMutationObserver(() => {
        setChildNodes(getChildNodes(host.current));
    }, config);
    return childNodes;
}

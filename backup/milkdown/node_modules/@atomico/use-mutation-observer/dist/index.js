import { useHost, useRef, useRefEffect } from "atomico";
export function useMutationObserver(callback, config = {
    childList: true,
    characterData: true,
}) {
    useRefMutationObserver(useHost(), callback, config);
}
export function useRefMutationObserver(host, callback, config = {
    childList: true,
    characterData: true,
}) {
    const ref = useRef();
    ref.current = callback;
    useRefEffect(() => {
        if (!host.current)
            return;
        const mutation = new MutationObserver((entries) => {
            entries.forEach(({ addedNodes }) => addedNodes.forEach(map));
            ref.current(entries, mutation);
        });
        const map = (child) => {
            if (child instanceof Text) {
                mutation.observe(child, { ...config, characterData: true });
            }
        };
        mutation.observe(host.current, config);
        host.current.childNodes.forEach(map);
        return () => mutation.disconnect();
    }, [host]);
}

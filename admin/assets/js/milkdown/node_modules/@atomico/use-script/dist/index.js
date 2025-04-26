import { options, usePromise } from "atomico";
const cache = {};
export const importScript = (src) => (cache[src] =
    cache[src] ||
        new Promise((resolve) => {
            if (!options.ssr)
                return;
            const script = document.createElement("script");
            script.src = src;
            script.onload = () => resolve();
            document.head.append(script);
        }));
export const useScript = (src) => usePromise(importScript, [src]);

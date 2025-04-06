import { useHost, useState } from "atomico";
import { useCurrentValue } from "@atomico/use-current-value";
/**
 * this hook replaces a property with a setter and
 * getter to observe these mutations
 */
export function usePropProxy(prop, config) {
    const host = useHost();
    const value = useCurrentValue(config);
    useState(() => {
        const { current } = host;
        const descriptor = Object.getOwnPropertyDescriptor(current.constructor.prototype, prop);
        Object.defineProperty(current, prop, {
            configurable: true,
            enumerable: !!descriptor?.enumerable,
            get() {
                if (value.current?.get) {
                    return value.current?.get();
                }
                if (descriptor?.get) {
                    return descriptor?.get?.call(current);
                }
            },
            set(nextValue) {
                if (value.current?.set) {
                    value.current?.set(nextValue);
                }
                if (descriptor?.set) {
                    return descriptor?.set?.call(current, nextValue);
                }
            },
        });
    });
}

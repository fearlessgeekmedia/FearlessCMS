export declare function useParent<T extends Element>(matches: T | string, composed?: boolean): import("atomico").Ref<T extends string ? {
    new (): Element;
    prototype: Element;
} : T>;
export declare function useParentPath(composed: boolean): Element[];

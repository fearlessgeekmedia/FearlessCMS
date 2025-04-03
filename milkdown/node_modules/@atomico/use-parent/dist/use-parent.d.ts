import { Ref } from "atomico";
export declare function useParent<T extends HTMLElement>(matches: string, composed?: boolean): Ref<T>;
export declare function useParentPath(composed: boolean): Element[];

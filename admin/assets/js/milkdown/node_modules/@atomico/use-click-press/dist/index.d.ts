import { Ref } from "atomico";
/**
 * Capture the click or touch event to execute the callback multiple times,
 * depending on the type of pressure
 */
export declare function useClickPress(ref: Ref<Element | Window>, callback: () => any, aceleration?: number, minMs?: number): void;

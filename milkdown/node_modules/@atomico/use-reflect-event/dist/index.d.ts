import { Ref } from "atomico";
/**
 * reflects an event to another node
 */
export declare const reflectEvent: (current: Element, event: Event, composed?: boolean) => void;
/**
 * This hook reflects an event and cancels its propagation
 */
export declare function useReflectEvent(refFrom: Ref, refTo: Ref, type: string, { capture, composed, }?: {
    capture?: boolean;
    composed?: boolean;
}): void;

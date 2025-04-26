import { Ref, DOMEvent } from "atomico";
export declare function useListener(ref: Ref, name: string, handler: EventListener, options?: boolean | AddEventListenerOptions): void;
/**
 * Associate an event and return a callback to remove said event
 */
export declare function addListener<T extends ChildNode | typeof window, E extends Event>(current: T, name: string, handler: (event: DOMEvent<T, E>) => any, options: boolean | AddEventListenerOptions): () => void;
export declare function useListenerState<T>(ref: Ref, name: string, handler: (event: Event) => T, options: boolean | AddEventListenerOptions): T | null;

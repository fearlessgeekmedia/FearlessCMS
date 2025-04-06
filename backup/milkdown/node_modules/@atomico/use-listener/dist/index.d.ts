import { Ref } from "atomico";
export type HandlerEvent<Base extends Node | Window, Name extends string, From = GlobalEventHandlersEventMap> = Omit<`on${Name}` extends keyof Base ? Base[`on${Name}`] extends (event: infer E) => void ? E : Event : Name extends keyof From ? From[Name] : Event, "currentTarget"> & {
    currentTarget: Base;
};
export type Handler<Base extends Node | Window, Name extends string> = (event: HandlerEvent<Base, Name>) => any;
export declare function useListener<Base extends Ref, Name extends string>(ref: Base, name: Name, handler: Handler<Base["current"], Name>, options?: boolean | AddEventListenerOptions): void;
/**
 * Associate an event and return a callback to remove said event
 */
export declare function addListener<Base extends Node | Window, Name extends string>(current: Base, name: Name, handler: Handler<Base, Name>, options?: boolean | AddEventListenerOptions): () => void;
export declare function useListenerState<Base extends Ref, Name extends string>(ref: Base, name: Name, handler: Handler<Base["current"], Name>, options?: boolean | AddEventListenerOptions): HandlerEvent<Base["current"], Name> | null;

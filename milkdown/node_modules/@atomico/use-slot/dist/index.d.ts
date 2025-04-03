import { Ref } from "atomico";
/**
 * returns the assigned nodes of a slot
3 */
export declare function useSlot<Type extends Node>(ref: Ref<HTMLSlotElement>, filter?: (node: Node) => boolean): Ref<Type>["current"][];
/**
 * creates a persistent list of nodes from a source with the intention of
 * keeping the node in the list as long as it remains on the host
 */
export declare function useProxySlot<Type extends ChildNode>(ref: Ref<HTMLSlotElement>, filter?: (node: ChildNode) => boolean): Ref<Type>["current"][];

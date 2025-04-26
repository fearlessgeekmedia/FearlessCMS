import { addListener } from "@atomico/use-listener";
import { Mark, useEffect, useHost, useState, useRefEffect } from "atomico";
/**
 * returns the assigned nodes of a slot
3 */
export function useSlot(ref, filter) {
    const [childNodes, setChildNodes] = useState([]);
    useRefEffect(() => {
        const { current } = ref;
        if (!current)
            return;
        // handler subscriber to the event
        const handler = () => setChildNodes(current
            .assignedNodes()
            .filter((child) => !(child instanceof Mark) &&
            (filter ? filter(child) : true)));
        // First load
        handler();
        // listener and unlistener
        return addListener(current, "slotchange", handler);
    }, [ref]);
    return childNodes;
}
/**
 * creates a persistent list of nodes from a source with the intention of
 * keeping the node in the list as long as it remains on the host
 */
export function useProxySlot(ref, filter) {
    const host = useHost();
    const children = useSlot(ref, filter);
    const [currentChildren, setCurrentChildren] = useState(children);
    const intoHost = (node) => node.parentElement === host.current;
    useEffect(() => {
        if (!children.length)
            return;
        // clean the list keeping only the nodes nested in the host
        setCurrentChildren([...currentChildren, ...children].filter(intoHost));
    }, children);
    useEffect(() => {
        if (!currentChildren.length)
            return;
        // gets all assigned slots
        const slots = new Set(currentChildren.map((child) => child.assignedSlot));
        // Avoid the reference given as parameter
        slots.delete(ref.current);
        const unlisteners = [...slots].map((slot) => addListener(slot, "slotchange", () => setCurrentChildren((children) => {
            // clean the list keeping only the nodes nested in the host
            const next = children.filter(intoHost);
            if (children.length === next.length)
                return children;
            return next;
        })));
        // remove the subscription to assigned slots
        return () => unlisteners.map((unlistener) => unlistener());
    }, currentChildren);
    return currentChildren;
}

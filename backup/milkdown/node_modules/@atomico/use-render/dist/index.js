import { jsx as _jsx } from "atomico/jsx-runtime";
import { useHost, useInsertionEffect, render } from "atomico";
/**
 * Ensures that the render function always
 * receives a tree that starts from the host tag
 */
const fillHost = (vnode) => vnode && vnode.type === "host" ? vnode : _jsx("host", { children: vnode });
/**
 * Generate a second render, this render escapes the current
 * one and is useful for collaborative work between LightDOM and shadowDOM
 */
export function useRender(view, args) {
    const host = useHost();
    host.id = host.id || Symbol();
    useInsertionEffect(() => {
        render(fillHost(typeof view === "function" ? view() : view), host.current, host.id);
    }, args);
}

import { useRef, useState } from "atomico";
import { useListener } from "@atomico/use-listener";
import { useDebounceState } from "@atomico/use-debounce-state";
import { useIntersectionObserver } from "@atomico/use-intersection-observer";
export function useParallax(host) {
    const refWindow = useRef(globalThis);
    const [state, setState] = useDebounceState(1, {
        x: 0,
        y: 0,
    }, "fps");
    const [intersectio, setIntersection] = useState(false);
    useIntersectionObserver(([{ isIntersecting }]) => setIntersection(isIntersecting));
    useListener(refWindow, "deviceorientation", ({ beta, gamma }) => {
        if (!intersectio)
            return;
        if (!refWindow.start) {
            refWindow.start = { beta, gamma };
        }
        const diffBeta = beta - refWindow.start.beta;
        const diffGamma = gamma - refWindow.start.gamma;
        setState({
            y: diffBeta / 15,
            x: diffGamma / 15,
        });
    }, { passive: true });
    useListener(host, "mousemove", ({ currentTarget, clientX, clientY, }) => {
        if (!intersectio)
            return;
        const { innerWidth, innerHeight, clientWidth = innerWidth, clientHeight = innerHeight, } = currentTarget;
        const centerX = clientWidth / 2;
        const centerY = clientHeight / 2;
        // Reset the coordinates only to the observed container
        if (currentTarget.getBoundingClientRect) {
            const rect = currentTarget.getBoundingClientRect();
            clientX -= rect.x;
            clientY -= rect.y;
        }
        const x = clientX > centerX
            ? (clientX - centerX) / centerX
            : (1 - clientX / centerX) * -1;
        const y = clientY > centerY
            ? (clientY - centerY) / centerY
            : (1 - clientY / centerY) * -1;
        setState({ x, y });
    }, { passive: true });
    return state;
}

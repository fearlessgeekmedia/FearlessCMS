import { Ref } from "atomico";
interface Rect {
    width: number;
    height: number;
    x: number;
    y: number;
    top: number;
    right: number;
    bottom: number;
    left: number;
}
/**
 * Observe the ResizeObserver state of a reference
 * @example
 * ```js
 * useResizeObserver(ref, (rect)=>{
 *  console.log(rect.width)
 * });
 * ```
 */
export declare function useResizeObserver(ref: Ref, callback: (rect: Rect) => void): void;
/**
 * Observes the ResizeObserver state of a reference and reflects
 * it to a local state of the component
 * @example
 * ```js
 * const ref = useResizeObserverState(ref);
 * if(ref){
 *  console.log(ref.width)
 * }
 * ```
 */
export declare function useResizeObserverState(ref: Ref): Rect;
export {};

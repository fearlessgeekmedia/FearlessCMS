import { useProp, useLayoutEffect } from "atomico";
import { useParent } from "@atomico/use-parent";
import { useRefMutationObserver } from "@atomico/use-mutation-observer";
const checkDisable = (el) => el.hasAttribute("disabled");
/**
 * Synchronize disabled status with a parent
 * @example
 * ```jsx
 * <fieldset disabled>
 *      <my-input>I am disabled</my-input>
 * </fieldset>
 * ```
 * @example
 * ```css
 * :host([disabled]){
 *      pointer-events: none;
 * }
 * ```
 */
export function useDisabled(matches = "fieldset") {
    const fieldset = useParent(matches);
    const [disabled, setDisabled] = useProp("disabled");
    useRefMutationObserver(fieldset, (items) => items
        .filter((item) => item.attributeName == "disabled")
        .map((el) => {
        setDisabled(checkDisable(el.target));
    }), {
        attributes: true,
    });
    useLayoutEffect(() => {
        const { current } = fieldset;
        if (current) {
            setDisabled(checkDisable(current));
        }
    }, []);
    return disabled;
}

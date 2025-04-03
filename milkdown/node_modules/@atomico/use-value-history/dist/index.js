import { useState } from "atomico";
/**
 * save the first parameter in a list
 */
export function useHistory(value, maxLength = 100) {
    const [state] = useState(() => ({ history: [] }));
    if (state.history[state.history.length - 1] !== value) {
        state.history = [...state.history, value].slice(maxLength * -1);
    }
    return state.history;
}

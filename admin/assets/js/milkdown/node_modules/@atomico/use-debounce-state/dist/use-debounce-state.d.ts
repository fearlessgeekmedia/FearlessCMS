import { ReturnUseState } from "atomico/types/hooks";
type Mode = "timeout" | "fps" | "idle";
export declare const fps: (length?: number) => Promise<void>;
export declare const idle: (ms: number) => Promise<unknown>;
export declare const timeout: (ms: number) => Promise<unknown>;
export declare function useDebounceState<Value>(delay: number, initialState: Value, mode?: Mode): ReturnUseState<Value extends (...args: any[]) => infer V ? V : Value>;
export {};

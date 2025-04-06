import { Ref } from "atomico";
export declare function useMutationObserver(callback: MutationCallback, config?: MutationObserverInit): void;
export declare function useRefMutationObserver(host: Ref<HTMLElement>, callback: MutationCallback, config?: MutationObserverInit): void;

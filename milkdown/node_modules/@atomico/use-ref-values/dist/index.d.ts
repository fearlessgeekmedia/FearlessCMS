import { Ref } from "atomico";
type Args<E extends Ref[]> = {
    [I in keyof E]: E[I]["current"];
};
type Collector = void | (() => any);
export declare function useRefValues<T extends Ref[]>(callback: (args: Args<T>) => Collector | void, args: T, mode?: boolean): void;
export {};

/**
 * this hook replaces a property with a setter and
 * getter to observe these mutations
 */
export declare function usePropProxy<Value>(prop: string, config: {
    set?: (value: Value) => any;
    get?: () => Value;
}): void;

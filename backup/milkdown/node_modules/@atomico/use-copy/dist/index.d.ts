import { Ref } from "atomico";
/**
 * temporarily create a textarea tag to execute the command to copy the content
 * @param {string} content
 */
export declare function copy(content: string): void;
/**
 * copy the content of a reference when executing the return callback
 */
export declare const useCopy: (ref: Ref) => () => void;

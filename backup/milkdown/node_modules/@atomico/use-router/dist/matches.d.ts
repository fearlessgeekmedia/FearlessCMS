import { Params } from "@uppercod/exp-route";
export type RouterCallback = (params: Params, search: Params) => any;
export type Routes = {
    [path: string]: RouterCallback;
};
export declare const getMatch: (path: string) => (request: string) => {};
export declare const matches: (routes: Routes, currentPath: string) => any[];

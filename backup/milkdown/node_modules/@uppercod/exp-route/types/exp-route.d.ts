export * from "./create-match.js";
export * from "./search-params.js";
export type Params = Record<string, string>;
export type Match = (path: string) => Params | undefined;

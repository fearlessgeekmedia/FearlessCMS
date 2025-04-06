import { createMatch, getParts, searchParams, } from "@uppercod/exp-route";
const cache = {};
export const getMatch = (path) => (cache[path] = cache[path] || createMatch(path));
export const matches = (routes, currentPath) => {
    const [path, pathSearch] = getParts(currentPath);
    const search = pathSearch ? searchParams(pathSearch) : {};
    for (const route in routes) {
        const match = getMatch(route);
        const params = match(path);
        if (params) {
            return [routes[route](params, search), route, params, search];
        }
    }
};

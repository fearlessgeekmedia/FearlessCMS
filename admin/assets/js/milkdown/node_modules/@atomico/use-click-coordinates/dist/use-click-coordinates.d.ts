import { Ref } from "atomico";
interface Coordinates {
    x: number;
    y: number;
    offset: {
        x: number;
        y: number;
    };
}
export declare function useClickCoordinates(ref: Ref, callback: (coordinates: Coordinates) => void): void;
export declare function getCoordinates({ pageX: x, pageY: y, currentTarget, }: {
    pageX: number;
    pageY: number;
    currentTarget: Element | null;
}): Coordinates;
export {};

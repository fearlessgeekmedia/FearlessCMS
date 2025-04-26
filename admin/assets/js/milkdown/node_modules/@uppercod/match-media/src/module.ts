const ID = Math.random();
const ID_REG = RegExp(`{${ID}-(\\d+)}`);
const CACHE: {
  [index: string]: Result[];
} = {};

const getIndexParam = (value: string) => value.match(ID_REG)?.[1];

export interface Result {
  value: string;
  size: number;
  unit: string;
  param: string;
  default?: boolean;
}

export default function media(parts: TemplateStringsArray, ...params: any[]) {
  const template = parts.raw.reduce(
    (str, part, i) => str + part + (i in params ? `{${ID}-${i}}` : ""),
    ""
  );
  if (!CACHE[template]) {
    CACHE[template] = parse(template);
  }
  const result: Result[] = CACHE[template];

  return {
    result,
    match(callback: (result: Result) => boolean) {
      let defaultResult;
      let value;
      for (let prop in result) {
        const currentResult = result[prop];
        if (currentResult.default) {
          defaultResult = currentResult;
        } else if (callback(currentResult)) {
          value = currentResult;
          break;
        }
      }
      value = value || defaultResult;
      return value.param ? params[value.param] : value.value;
    },
  };
}

export function parse(template: string) {
  const groups = template.split(/,\s+/).map<Result>((value) => {
    value = value.trim();
    const test = value.match(/(.+)\s+(\d+(?:\.\d+){0,1})(\w+)$/);
    if (test) {
      const [, value, size, unit] = test;
      return {
        value,
        size: Number(size),
        unit,
        param: getIndexParam(value),
      };
    } else {
      return {
        value,
        size: -1 >>> 0,
        unit: "",
        param: getIndexParam(value),
        default: true,
      };
    }
  });

  return groups.sort((a, b) => (a.size > b.size ? -1 : 1));
}

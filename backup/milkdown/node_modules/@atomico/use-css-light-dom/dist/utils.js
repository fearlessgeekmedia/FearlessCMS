const scaleSelector = (selector) => selector.replace(/([^\w\_\-])/g, "\\$1");
const serializeRules = ({ cssText, selectorText }, scopeSelector) => `${selectorText.split(/\s*,\s*/).map((selector) => (selector.startsWith(":") ? selector : ":host " + selector)
    .replace(/(:host)\((.+)\)/, "$1$2")
    .replace(/::slotted\((.+)\)/, ":host > $1")
    .replace(/:host/g, scopeSelector))}${cssText
    .replace(selectorText, "")
    .replace(/(animation(?:-name){0,1}\s*:\s*)([^;}]+)/g, "$1$2-" + scaleSelector(scopeSelector))}`;
export function getRules(Sheet, scopeSelector) {
    const { cssRules } = Sheet;
    let rules = [];
    for (let i = 0; i < cssRules.length; i++) {
        const rule = cssRules[i];
        if (rule instanceof CSSStyleRule) {
            rules.push(serializeRules(rule, scopeSelector));
        }
        else if (rule instanceof CSSKeyframesRule) {
            const { cssText } = rule;
            rules.push(cssText.replace(/\s+([^\s{]+)/, " $1-" + scaleSelector(scopeSelector)));
        }
        else if (rule instanceof CSSMediaRule) {
            const { conditionText } = rule;
            rules.push(`@media ${conditionText}{${getRules(rule, scopeSelector)}}`);
        }
    }
    return rules;
}
let sandbox;
export function getSheet(style) {
    if (style instanceof CSSStyleSheet) {
        return style;
    }
    if (style.sheet) {
        return style.sheet;
    }
    if (!sandbox) {
        sandbox = document.createElement("iframe");
        sandbox.style.display = "none";
        document.body.appendChild(sandbox);
    }
    const { contentDocument } = sandbox;
    contentDocument.head.appendChild(style);
    const { sheet } = style;
    const { cloneNode } = style;
    style.cloneNode = (deep) => {
        const copy = cloneNode.call(style);
        const { cssRules } = sheet;
        for (let i = 0; i < cssRules.length; i++) {
            copy.textContent += cssRules[i].cssText;
        }
        return copy;
    };
    return sheet;
}

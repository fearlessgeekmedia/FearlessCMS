/**
 * temporarily create a textarea tag to execute the command to copy the content
 * @param {string} content
 */
export function copy(content) {
    const textarea = document.createElement("textarea");
    textarea.value = content;
    textarea.setAttribute("style", "width:0;height:0;opcity:0");
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand("copy");
    textarea.remove();
}
/**
 * copy the content of a reference when executing the return callback
 */
export const useCopy = (ref) => () => {
    const { current } = ref;
    if (!current)
        return;
    copy("value" in current ? current.value : current.textContent);
};

export function objCmp(a: any, b: any) {
    return (a > b) ? 1 : (a == b) ? 0 : -1;
}

export function numCmp(a: string, b: string) {
    return objCmp(parseInt(a), parseInt(b));
}

export function dateCmp(a: string, b: string) {
    return objCmp(Date.parse(a), Date.parse(b));
}

export function byId(id: string) {
    return document.getElementById(id);
}

export function onLoad(fun: (this: Window, ev: Event) => any) {
    window.addEventListener('load', fun);
}

export function capitalize(str: string) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

export function html(str: string) {
    return str
        .replace(/[<>&]/gm, (s: String) => "&#" + s.charCodeAt(0) + ";")
        .replace(/\r\n/gm, '<br />');
}

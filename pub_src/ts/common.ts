export type SortFun<T> = (a: T, b: T) => -1 | 0 | 1;

export function rand(x: number) {
    return Math.floor(Math.random() * x);
}

export function objCmp(a: any, b: any) {
    return (a > b) ? 1 : (a == b) ? 0 : -1;
}

export function numCmp(a: string, b: string) {
    return objCmp(parseInt(a), parseInt(b));
}

export function dateCmp(a: string, b: string) {
    return objCmp(Date.parse(a), Date.parse(b));
}

export function capitalize(str: string) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

export function timeStr(time: string | number) {
    if (typeof time === 'string') {
        time = parseInt(time);
    }
    return new Date(time * 1000).toLocaleString([], {
        hour12: false,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

export function html(str: string) {
    return str
        .replace(/[<>&]/gm, (s: String) => "&#" + s.charCodeAt(0) + ";")
        .replace(/\r\n/gm, '<br />')
        .replace(/\n/gm, '<br />');
}

export function textBuilder() {
    let text = '';
    const p = (str: string) => {
        text += str + '\n';
    }
    p.text = () => text;
    return p;
}

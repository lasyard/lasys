export function objCmp(a: any, b: any) {
    return (a > b) ? 1 : (a == b) ? 0 : -1;
}

export function numCmp(a: string, b: string) {
    return objCmp(parseInt(a), parseInt(b));
}

export function dateCmp(a: string, b: string) {
    return objCmp(Date.parse(a), Date.parse(b));
}

(String.prototype as { [key: string]: any }).capitalize = function () {
    return this.charAt(0).toUpperCase() + this.slice(1);
};

(String.prototype as { [key: string]: any }).html = function () {
    const str = this.replace(/[<>&]/gm, (s: String) => "&#" + s.charCodeAt(0) + ";");
    return str.replace(/\r\n/gm, '<br />');
};

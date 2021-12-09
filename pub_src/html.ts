export function html(str: string) {
    return str
        .replace(/[<>&]/gm, (s: String) => "&#" + s.charCodeAt(0) + ";")
        .replace(/\r\n/gm, '<br />');
}

export function byId(id: string) {
    return document.getElementById(id);
}

export function newTag(tag: string, content?: string, id?: string, className?: string) {
    let node = document.createElement(tag);
    if (content) {
        node.appendChild(new Text(content));
    }
    if (id) {
        node.id = id;
    }
    if (className) {
        node.className = className;
    }
    return node;
}

export function onLoad(fun: (this: Window, ev: Event) => any) {
    window.addEventListener('load', fun);
}

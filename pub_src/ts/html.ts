export function html(str: string) {
    return str
        .replace(/[<>&]/gm, (s: String) => "&#" + s.charCodeAt(0) + ";")
        .replace(/\r\n/gm, '<br />');
}

export class Tag {
    private element: HTMLElement;

    private constructor(element: HTMLElement) {
        this.element = element;
    }

    public static of(name: string) {
        return new Tag(document.createElement(name));
    }

    public static byId(id: string) {
        const element = document.getElementById(id);
        return element ? new Tag(element) : null;
    }

    public get() {
        return this.element;
    }

    public id(id: string) {
        this.element.id = id;
        return this;
    }

    public className(className: string) {
        this.element.className = className;
        return this;
    }

    public attr(attrs: { [index: string]: any }) {
        for (let key in attrs) {
            this.element.setAttribute(key, attrs[key]);
        }
        return this;
    }

    public style(props: { [index: string]: any }) {
        for (let key in props) {
            this.element.style.setProperty(key, props[key]);
        }
        return this;
    }

    public html(html: string) {
        this.element.innerHTML = html;
        return this;
    }

    public add(tag: Tag | string) {
        this.element.appendChild(typeof tag === 'string' ? new Text(tag) : tag.element);
        return this;
    }

    public event(eventName: string, handler: (e: Event) => any) {
        this.element.addEventListener(eventName, handler);
        return this;
    }

    public putInto(tag: Tag) {
        tag.element.appendChild(this.element);
        return this;
    }

    public putIntoHtml(element: HTMLElement) {
        element.appendChild(this.element);
        return this;
    }

    public emerge() {
        document.body.appendChild(this.element);
        return this;
    }
}

export function onLoad(fun: (this: Window, ev: Event) => any) {
    window.addEventListener('load', fun);
}

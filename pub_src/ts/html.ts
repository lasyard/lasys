export function html(str: string) {
    return str
        .replace(/[<>&]/gm, (s: String) => "&#" + s.charCodeAt(0) + ";")
        .replace(/\r\n/gm, '<br />');
}

export type TagList = (Tag | string)[];

interface ToolTipInfo {
    title?: string;
    body: string | Tag | TagList;
}

class ToolTip {
    private static toolTip: ToolTip = null;

    private divTip: Tag;
    private spanTitle: Tag;
    private divBody: Tag;

    private constructor() {
        this.spanTitle = Tag.of('span');
        this.divBody = Tag.of('div');
        this.divTip = Tag.of('div').id('-tool-tip-')
            .add(Tag.of('div')
                .add(this.spanTitle)
                .add(Tag.bi('x-circle sys').event('click', this.hide.bind(this))))
            .add(this.divBody)
            .event('click', (e) => e.stopPropagation())
            .emerge();
        document.body.addEventListener('click', this.hide.bind(this));
    }

    public static get() {
        if (!ToolTip.toolTip) {
            ToolTip.toolTip = new ToolTip();
        }
        return ToolTip.toolTip;
    }

    public on(tag: Tag, info: ToolTipInfo) {
        if (tag.tagName() === 'A') {
            tag.attr({ href: 'javascript:void(0)' });
        }
        tag.event('click', (e) => {
            this.show(e as MouseEvent, info);
            e.preventDefault();
        });
        return tag;
    }

    private hide() {
        this.divTip.style({ display: 'none' });
    }

    private show(e: MouseEvent, info: ToolTipInfo) {
        if (info.title) {
            this.spanTitle.clear().add(info.title);
        }
        this.divBody.clear();
        const bd = info.body;
        if (typeof bd === 'string') {
            this.divBody.add(bd);
        } else if (Array.isArray(bd)) {
            this.divBody.add(...bd);
        } else {
            this.divBody.add(bd);
        }
        const x = e.pageX;
        const y = e.pageY;
        const divTip = this.divTip;
        divTip.style({ left: x + 'px', top: y + 'px', display: 'block' });
        const width = divTip.get().offsetWidth;
        const right = e.clientX + width / 2 - document.documentElement.clientWidth;
        let left = x - width / 2;
        if (left < 0) {
            left = 0;
        } else if (right > 0) {
            left = left - right;
        }
        const height = divTip.get().offsetHeight;
        const bottom = e.clientY + height - document.documentElement.clientHeight;
        let top = y;
        if (bottom > 0) {
            top = y - height;
        }
        divTip.style({ left: left + 'px', top: top + 'px', display: 'block' });
        e.stopPropagation();
    }
}

export class Tag {
    private element: HTMLElement;

    private constructor(element: HTMLElement) {
        this.element = element;
    }

    public static of(name: string) {
        return new Tag(document.createElement(name));
    }

    public static p(...tags: TagList) {
        return Tag.of('p').add(...tags);
    }

    public static br() {
        return Tag.of('br');
    }

    public static bi(icon: string) {
        return Tag.of('i').cls('bi bi-' + icon);
    }

    public static byId(id: string) {
        const element = document.getElementById(id);
        return element ? new Tag(element) : null;
    }

    public get() {
        return this.element;
    }

    public tagName() {
        return this.element.tagName;
    }

    public id(id: string) {
        this.element.id = id;
        return this;
    }

    public cls(cls: string) {
        this.element.className = cls;
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

    public add(...tags: TagList) {
        for (let tag of tags) {
            this.element.appendChild(typeof tag === 'string' ? new Text(tag) : tag.element);
        }
        return this;
    }

    public event(eventName: string, handler: (e: Event) => any) {
        this.element.addEventListener(eventName, handler);
        return this;
    }

    public toolTip(info: ToolTipInfo) {
        return ToolTip.get().on(this, info);
    }

    public putInto(tag: Tag) {
        tag.element.appendChild(this.element);
        return this;
    }

    public clear() {
        this.element.innerHTML = '';
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

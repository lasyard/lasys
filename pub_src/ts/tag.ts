import { Ajax, AjaxCallback, HttpMethod, MimeType } from './ajax';
import { ToolTip, ToolTipContent } from './tool_tip';

type TagList = (Tag<HTMLElement> | string)[];

export type TagContent = string | Tag<HTMLElement> | TagList;

export class Tag<T extends HTMLElement> {
    private element: T;

    private constructor(element: T) {
        this.element = element;
    }

    public static of(name: string) {
        return new Tag(document.createElement(name));
    }

    public static p(...tags: TagList) {
        return Tag.of('p').add(tags);
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

    public static form(name: string) {
        const form = document.forms.namedItem(name);
        return form ? new Tag(form) : null;
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

    public addAll(...tags: TagList) {
        for (const tag of tags) {
            this.element.appendChild(typeof tag === 'string' ? new Text(tag) : tag.element);
        }
        return this;
    }

    public add(obj: string | Tag<HTMLElement> | TagList) {
        if (Array.isArray(obj)) {
            return this.addAll(...obj);
        }
        return this.addAll(obj);
    }

    public event(eventName: string, handler: (e: Event) => any) {
        this.element.addEventListener(eventName, handler);
        return this;
    }

    public toolTip(info: ToolTipContent): Tag<T> {
        return ToolTip.get().on(this, info);
    }

    public ajaxfy(
        cb: AjaxCallback,
        dataCallback: (form: HTMLFormElement) => any,
        method: HttpMethod,
        accept = MimeType.JSON,
        type = MimeType.JSON,
    ) {
        if (this.element instanceof HTMLFormElement) {
            const form = (this.element as HTMLFormElement);
            this.event('submit', function (e: Event) {
                e.preventDefault();
                const data = dataCallback(form);
                Ajax.call(cb, method, JSON.stringify(data), form.action, accept, type);
            });
        }
        return this;
    }

    public putInto(tag: Tag<HTMLElement>) {
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

    public vanish() {
        this.element.parentNode.removeChild(this.element);
        return this;
    }
}

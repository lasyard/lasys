import { Tooltip, TooltipContent } from './tooltip';

type TagList = (Tag<HTMLElement> | any)[];

export type TagContent = string | Tag<HTMLElement> | TagList;

export class Tag<T extends HTMLElement> {
    private element: T;

    private constructor(element: T) {
        this.element = element;
    }

    public static of<H extends HTMLElement>(name: string) {
        return new Tag(document.createElement(name) as H);
    }

    public static div(...tags: TagList) {
        return Tag.of<HTMLDivElement>('div').add(tags);
    }

    public static span(...tags: TagList) {
        return Tag.of<HTMLSpanElement>('span').add(tags);
    }

    public static a(...tags: TagList) {
        return Tag.of<HTMLAnchorElement>('a').add(tags);
    }

    public static p(...tags: TagList) {
        return Tag.of<HTMLParagraphElement>('p').add(tags);
    }

    public static b(...tags: TagList) {
        return Tag.of('b').add(tags);
    }

    public static i(...tags: TagList) {
        return Tag.of('i').add(tags);
    }

    public static br() {
        return Tag.of<HTMLBRElement>('br');
    }

    public static li(...tags: TagList) {
        return Tag.of<HTMLLIElement>('li').add(tags);
    }

    public static fieldset(title: string) {
        return Tag.of<HTMLFieldSetElement>('fieldset').add(Tag.of('legend').add(title));
    }

    public static icon(icon: string) {
        return Tag.of('i').cls('bi bi-' + icon);
    }

    public static byId<H extends HTMLElement>(id: string) {
        const element = document.getElementById(id) as H;
        return element ? new Tag(element) : null;
    }

    public static form(name: string) {
        const form = document.forms.namedItem(name);
        return form ? new Tag(form) : null;
    }

    public static linkify(text: string) {
        const urlRegex = /(https?:\/\/[^\s]+)/g;
        return Tag.span().html(text.replace(urlRegex, (url) => {
            return Tag.a(url).attr({ href: url, target: '_blank' }).getHtml();
        }));
    }

    public get() {
        return this.element;
    }

    public getHtml() {
        return this.element.outerHTML;
    }

    public getPos() {
        let left = 0;
        let top = 0;
        let e: HTMLElement = this.element;
        do {
            left += e.offsetLeft;
            top += e.offsetTop;
        } while (e = e.offsetParent as HTMLElement);
        return { left: left, top: top };
    }

    public tagName() {
        return this.element.tagName;
    }

    public find(tagName: string, index: number = 0) {
        return new Tag(this.element.getElementsByTagName(tagName)[index] as HTMLElement);
    }

    public id(id: string) {
        this.element.id = id;
        return this;
    }

    public name(name: string) {
        this.element.setAttribute('name', name);
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
            const e = tag instanceof Tag ? tag.element : new Text(tag.toString());
            this.element.appendChild(e);
        }
        return this;
    }

    public add(obj: TagContent) {
        if (Array.isArray(obj)) {
            return this.addAll(...obj);
        }
        return this.addAll(obj);
    }

    public insert(tag: Tag<HTMLElement>, ref: Tag<HTMLElement>) {
        this.element.insertBefore(tag.element, ref.element);
        return this;
    }

    public event(eventName: string, handler: (e: Event) => any) {
        this.element.addEventListener(eventName, handler);
        return this;
    }

    public clickShow(tag: Tag<HTMLElement>) {
        this.element.addEventListener('click', (e) => {
            tag.show();
            e.stopPropagation();
        });
        return this;
    }

    public toolTip(info: TooltipContent): Tag<T> {
        return Tooltip.get().on(this, info);
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

    public show() {
        this.element.style.display = 'block';
        return this;
    }

    public hide() {
        this.element.style.display = 'none';
        return this;
    }

    public outClickHide() {
        const element = this.element;
        document.body.addEventListener('click', (e) => {
            element.style.display = 'none';
        });
        this.event('click', (e) => {
            e.stopPropagation();
        });
        return this;
    }

    public emerge() {
        document.body.appendChild(this.element);
        return this;
    }

    public vanish() {
        this.element.parentNode?.removeChild(this.element);
        return this;
    }
}

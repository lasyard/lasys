import { Tag, TagContent } from "./tag";

interface ToolTipInfo {
    title?: TagContent;
    body: TagContent;
    width?: string;
}

// Do fun call to allow calculating when showing tool tip.
type ToolTipInfoCallback = () => ToolTipInfo;

export type ToolTipContent = ToolTipInfo | ToolTipInfoCallback;

export class ToolTip {
    private static toolTip: ToolTip = null;

    private divTip: Tag<HTMLDivElement>;
    private spanTitle: Tag<HTMLSpanElement>;
    private divBody: Tag<HTMLDivElement>;

    private constructor() {
        this.spanTitle = Tag.of('span');
        this.divBody = Tag.of('div') as Tag<HTMLDivElement>;
        this.divTip = Tag.of('div').id('-tool-tip-')
            .add(Tag.of('div')
                .add(this.spanTitle)
                .add(Tag.icon('x-circle sys').event('click', this.hide.bind(this))))
            .add(this.divBody)
            .event('click', (e) => e.stopPropagation())
            .emerge() as Tag<HTMLDivElement>;
        document.body.addEventListener('click', this.hide.bind(this));
    }

    public static get() {
        if (!ToolTip.toolTip) {
            ToolTip.toolTip = new ToolTip();
        }
        return ToolTip.toolTip;
    }

    public on<T extends HTMLElement>(tag: Tag<T>, info: ToolTipContent) {
        if (tag.tagName() === 'A') {
            tag.attr({ href: 'javascript:void(0)' });
        }
        tag.event('click', (e) => {
            this.show(e as MouseEvent, info);
            e.preventDefault();
        });
        return tag;
    }

    public hide() {
        this.divTip.style({ display: 'none' });
    }

    private show(e: MouseEvent, info: ToolTipContent) {
        if (typeof info === 'function') {
            info = info();
        }
        this.spanTitle.clear().add(info.title ? info.title : document.title);
        this.divBody.clear().add(info.body);
        if (info.width) {
            this.divTip.style({ width: info.width });
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

import { onLoad } from './html';
import { Tag } from './index';

export class ToolTip {
    private static toolTip: ToolTip = null;

    private divTip: Tag;
    private spanTitle: Tag;
    private divBody: Tag;
    private cb: (context: any) => { title?: string, body: string } | string;

    private constructor() {
        this.spanTitle = Tag.of('span').className('nobr');
        this.divBody = Tag.of('div');
        const self = this;
        this.divTip = Tag.of('div').id('-tool-tip-')
            .add(Tag.of('div')
                .add(this.spanTitle)
                .add(Tag.of('i').className('bi bi-x-circle sys').event('click', this.hide.bind(this))))
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

    public static on(text: string, context: any) {
        return Tag.of('a').attr({ href: 'javascript:void(0)' }).event('click', (e) => {
            ToolTip.get().show(e as MouseEvent, context);
            e.preventDefault();
        }).add(text);
    }

    private hide() {
        this.divTip.style({ display: 'none' });
    }

    public callback(cb: (context: any) => { title?: string; body: string; } | string) {
        this.cb = cb;
    }

    private show(e: MouseEvent, context: any) {
        const info = this.cb(context);
        if (typeof info === 'string') {
            this.divBody.html(info);
        } else {
            this.spanTitle.html(info.title);
            this.divBody.html(info.body);
        }
        const divTip = this.divTip;
        divTip.style({ left: e.pageX + 'px', top: e.pageY + 'px', display: 'block' });
        if (e.clientX + divTip.get().offsetWidth > window.innerWidth) {
            divTip.style({ left: e.pageX - divTip.get().offsetWidth + 'px' });;
        }
        if (e.clientY + divTip.get().offsetHeight > window.innerHeight) {
            divTip.style({ top: e.pageY - divTip.get().offsetHeight + 'px' });;
        }
        e.stopPropagation();
    }
}

declare const toolTipCallback: (data: any) => any;

onLoad(function () {
    ToolTip.get().callback(toolTipCallback);
});

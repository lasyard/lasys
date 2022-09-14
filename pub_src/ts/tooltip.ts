import { Tag, TagContent } from "./tag";

interface TooltipInfo {
    title?: TagContent;
    body: TagContent;
    width?: string;
}

// Do fun call to allow calculating when showing tool tip.
type TooltipInfoCallback = () => TooltipInfo;

export type TooltipContent = TooltipInfo | TooltipInfoCallback;

export class Tooltip {
    private static tooltip: Tooltip;

    private divTooltip: Tag<HTMLDivElement>;
    private spanTitle: Tag<HTMLSpanElement>;
    private divBody: Tag<HTMLDivElement>;
    private showTimer: number = 0;
    private hideTimer: number = 0;
    private owner: Tag<HTMLElement> | null = null;

    private constructor() {
        this.spanTitle = Tag.of('span');
        this.divBody = Tag.div();
        this.divTooltip = Tag.div(
            Tag.div(
                this.spanTitle,
                Tag.icon('x-circle sys').event('click', this.hide.bind(this)),
            ),
            this.divBody,
        ).cls('tooltip').emerge();
    }

    public static get() {
        if (!Tooltip.tooltip) {
            Tooltip.tooltip = new Tooltip();
        }
        return Tooltip.tooltip;
    }

    private clearShowTimer() {
        if (this.showTimer) {
            window.clearTimeout(this.showTimer);
            this.showTimer = 0;
        }
    }

    private clearHideTimer() {
        if (this.hideTimer) {
            window.clearTimeout(this.hideTimer);
            this.hideTimer = 0;
        }
    }

    private setShowTimer(owner: Tag<HTMLElement>, e: Event, info: TooltipContent) {
        this.clearHideTimer();
        if (owner != this.owner) {
            if (this.showTimer) {
                this.clearShowTimer();
            }
            this.showTimer = window.setTimeout(() => {
                this.show(owner, e as MouseEvent, info);
                this.showTimer = 0;
            }, 500);
        }
    }

    private setHideTimer() {
        // Always set new timeout for hiding.
        this.clearHideTimer();
        this.hideTimer = window.setTimeout(() => {
            this.clearShowTimer();
            this.hide();
            this.hideTimer = 0;
        }, 300);
    }

    public on<T extends HTMLElement>(tag: Tag<T>, info: TooltipContent) {
        if (tag.tagName() === 'A') {
            tag.attr({ href: 'javascript:void(0)' });
            tag.event('click', (e) => {
                this.show(tag, e as MouseEvent, info);
                e.preventDefault();
                e.stopPropagation();
            });
            this.divTooltip.outClickHide();
        } else {
            tag.event('mouseenter', (e) => { this.setShowTimer(tag, e, info); })
                .event('mouseleave', this.setHideTimer.bind(this));
            // Set this to remedy mouse moving into tip div.
            this.divTooltip.event('mouseenter', this.clearHideTimer.bind(this))
                .event('mouseleave', this.setHideTimer.bind(this));
        }
        return tag;
    }

    public close() {
        this.clearShowTimer();
        this.hide();
    }

    private hide() {
        this.divTooltip.hide();
        this.owner = null;
    }

    private show(owner: Tag<HTMLElement>, e: MouseEvent, info: TooltipContent) {
        if (typeof info === 'function') {
            info = info();
        }
        this.spanTitle.clear().add(info.title ? info.title : document.title);
        this.divBody.clear().add(info.body);
        if (info.width) {
            this.divTooltip.style({ width: info.width });
        }
        const x = e.pageX;
        const y = e.pageY;
        const divTip = this.divTooltip;
        divTip.style({ left: x + 'px', top: y + 'px' }).show();
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
        divTip.style({ left: left + 'px', top: top + 'px' });
        e.stopPropagation();
        this.owner = owner;
    }
}

import { onLoad } from './html';
import { Ajax, MimeType } from './ajax';
import { Tag } from './tag';
import { Tooltip } from './tooltip';
import { numCmp, timeStr } from './common';

interface Image {
    file: string; // Url of file.
    thumb: string; // Url of thumb.
    title: string;
    time: string; // Timestamp.
    user: string; // Name of the uploader.
    delete?: boolean; // If the image can be delete by current user.
}

export class Gallery {
    private imageSet: Image[];
    private divThumbs: Tag<HTMLDivElement>;
    private divImage: Tag<HTMLDivElement>;
    private msg: Tag<HTMLElement>;
    private popupMsg: Tag<HTMLElement>;
    private current = -1;
    private readonly keyDownHandler = this.handleKeyDown.bind(this);

    private static title(image: Image) {
        return image.title == '' ? 'untitled' : image.title;
    }

    private showMsg(msg: string) {
        if (this.msg) {
            this.msg.html(msg);
        }
    }

    private showPopupMsg(msg: string) {
        if (this.popupMsg) {
            this.popupMsg.html(msg).show();
        }
    }

    render(panelId: string) {
        const panel = Tag.byId(panelId);
        if (!panel) {
            return;
        }
        this.msg = Tag.byId('-msg');
        this.popupMsg = Tag.byId('-popup-msg').outClickHide();
        this.divThumbs = Tag.div().cls('gallery').putInto(panel);
        this.divImage = Tag.div().hide().putInto(panel);
        const btnUpload = Tag.byId('-btn-upload');
        const divForm = Tag.byId('-div-form-upload');
        if (btnUpload && divForm) {
            btnUpload.clickShow(divForm);
            divForm.outClickHide();
        }
        return this;
    }

    private data(imageSet: Image[]) {
        this.imageSet = imageSet;
        return this;
    }

    private refresh() {
        const imageSet = this.imageSet;
        imageSet.sort((a, b) => -numCmp(a.time, b.time));
        this.divThumbs.clear();
        this.closeImage();
        for (let i = 0; i < imageSet.length; ++i) {
            const image = imageSet[i];
            const title = Gallery.title(image);
            const thumb = Tag.a(
                Tag.div(
                    Tag.of('img').attr({ src: image.thumb }).toolTip({
                        title: image.title,
                        body: Tag.of('ul').cls('icon').addAll(
                            Tag.li(Tag.icon('clock'), timeStr(image.time)),
                            Tag.li(Tag.icon('person'), image.user),
                        ),
                    }),
                    Tag.br(),
                    Tag.span(title).cls('title'),
                )
            )
                .cls("thumb")
                .attr({ href: 'javascript:void(0)' })
                .event('click', (e) => {
                    this.openImage(i);
                    e.preventDefault();
                })
                .putInto(this.divThumbs);
            if (image.delete) {
                thumb.add(Tag.div(Tag.icon('x')).cls('x-button').event('click', (e) => {
                    e.stopPropagation();
                    const r = confirm('Are you sure to delete image "' + title + '"?');
                    if (r) {
                        Ajax.delete(
                            (r) => {
                                this.showPopupMsg(r);
                                this.loadData();
                            },
                            image.file,
                            '',
                            MimeType.TEXT,
                            MimeType.HTML
                        );
                    }
                }));
            }
        }
        return this;
    }

    private handleKeyDown(e: KeyboardEvent) {
        e.preventDefault();
        switch (e.key) {
            case 'Escape':
                this.closeImage();
                break;
            case 'ArrowLeft':
                if (this.current > 0) {
                    --this.current;
                    this.refreshImage();
                }
                break;
            case 'ArrowRight':
                if (this.current < this.imageSet.length - 1) {
                    ++this.current;
                    this.refreshImage();
                }
                break;
        }
    }

    private openImage(index: number) {
        Tooltip.get().close();
        this.divThumbs.hide();
        this.current = index;
        this.refreshImage();
        this.divImage.show();
        document.body.addEventListener('keydown', this.keyDownHandler);
    }

    private closeImage() {
        document.body.removeEventListener('keydown', this.keyDownHandler);
        this.divImage.hide();
        this.divThumbs.show();
        this.showMsg(Tag.span(Tag.icon('info-circle'), 'Total ' + this.imageSet.length + ' images').getHtml());
    }

    private refreshImage() {
        const image = this.imageSet[this.current];
        Tag.div(
            Tag.of('img').attr({ src: image.file })
        ).cls('image').putInto(this.divImage.clear());
        this.showMsg(Tag.span(
            Tag.icon('info-circle'), Gallery.title(image), ' ',
            Tag.icon('clock'), timeStr(image.time), ' ',
            Tag.icon('person'), image.user,
        ).getHtml());
    }

    loadData() {
        Ajax.get((r) => {
            this.data(JSON.parse(r)).refresh();
        });
    }
}

onLoad(() => {
    new Gallery().render('main').loadData();
});

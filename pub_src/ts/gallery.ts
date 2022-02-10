import { onLoad } from './html';
import { Ajax, MimeType } from './ajax';
import { Tag } from './tag';
import { Tooltip } from './tooltip';
import { numCmp, timeStr } from './common';

interface Image {
    name: string; // Name of image file.
    title: string;
    time: string; // Timestamp.
    user: string; // Name of the uploader.
    delete?: boolean; // If the image can be delete by current user.
    update?: boolean; // If the title of image can be update by current user.
}

interface ImageSet {
    image: {
        prefix: string;
        suffix: string;
    };
    thumb: {
        prefix: string;
        suffix: string;
    }
    list: Image[];
}

export class Gallery {
    private imageSet: ImageSet;
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

    private ajaxResponse(r: any) {
        this.showPopupMsg(r);
        this.loadData();
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

    private data(imageSet: ImageSet) {
        this.imageSet = imageSet;
        return this;
    }

    private refresh() {
        const imageSet = this.imageSet;
        const images = imageSet.list;
        this.divThumbs.clear();
        this.closeImage();
        for (let i = 0; i < images.length; ++i) {
            const image = images[i];
            const title = Gallery.title(image);
            let src: string;
            let box: Tag<HTMLElement>;
            const imageUrl = imageSet.image.prefix + image.name + imageSet.image.suffix;
            if (imageSet.thumb) {
                src = imageSet.thumb.prefix + image.name + imageSet.thumb.suffix;
                box = Tag.a().cls("thumb").event('click', (e) => {
                    this.openImage(i);
                    e.preventDefault();
                }).attr({ href: 'javascript:void(0)' });
            } else {
                src = imageUrl;
                box = Tag.div().cls("image");
            }
            box.add(Tag.div(
                Tag.of('img').attr({ src: src }).toolTip({
                    title: title,
                    body: Tag.of('ul').cls('icon').addAll(
                        Tag.li(Tag.icon('clock'), timeStr(image.time)),
                        Tag.li(Tag.icon('person'), image.user),
                    ),
                }),
                Tag.br(),
                Tag.span(title).cls('title'),
            )).putInto(this.divThumbs);
            const btns = Tag.div().cls('buttons').putInto(box);
            if (image.update) {
                btns.add(Tag.icon('dash').event('click', (e) => {
                    e.stopPropagation();
                    const newTitle = prompt('New title for image "' + title + '":');
                    if (newTitle) {
                        Ajax.update(
                            this.ajaxResponse.bind(this),
                            newTitle,
                            imageUrl,
                            MimeType.TEXT,
                            MimeType.HTML
                        );
                    }
                }));
            }
            if (image.delete) {
                btns.add(Tag.icon('x').event('click', (e) => {
                    e.stopPropagation();
                    const r = confirm('Are you sure to delete image "' + title + '"?');
                    if (r) {
                        Ajax.delete(
                            this.ajaxResponse.bind(this),
                            null,
                            imageUrl,
                            MimeType.JSON,
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
                if (this.current < this.imageSet.list.length - 1) {
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
        this.showMsg(Tag.span(
            Tag.icon('info-circle'), 'Total ' + this.imageSet.list.length + ' images'
        ).getHtml());
    }

    private refreshImage() {
        const imageSet = this.imageSet;
        const image = imageSet.list[this.current];
        Tag.div(
            Tag.of('img').attr({ src: imageSet.image.prefix + image.name + imageSet.image.suffix })
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

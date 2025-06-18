import { onLoad } from './html';
import { Ajax, MimeType, HttpMethod, TYPE_KEY } from './ajax';
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
    private divImageBox: Tag<HTMLDivElement>;
    private msg: Tag<HTMLElement> | null;
    private popupMsg: Tag<HTMLElement> | null;
    private current = -1;
    private readonly keyDownHandler = this.handleKeyDown.bind(this);

    private static title(image: Image) {
        return image.title == '' ? 'untitled' : image.title;
    }

    private showMsg(msg: string) {
        this.msg?.html(msg);
    }

    private showPopupMsg(msg: string) {
        this.popupMsg?.html(msg).show();
    }

    private ajaxResponse(r: any) {
        this.showPopupMsg(r);
        this.loadData();
    }

    render(panelId: string) {
        const panel = Tag.byId(panelId);
        if (!panel) {
            return this;
        }
        this.msg = Tag.byId('-msg');
        this.popupMsg = Tag.byId('-popup-msg');
        this.popupMsg?.outClickHide();
        const div = Tag.div().cls('gallery').putInto(panel);
        this.divThumbs = Tag.divLoading().cls('thumbs').putInto(div);
        this.divImage = Tag.div().hide().putInto(div);
        Tag.p(
            Tag.icon('escape').event('click', (e) => {
                e.stopPropagation();
                this.closeImage();
            }),
            Tag.icon('arrow-left-circle').event('click', (e) => {
                e.stopPropagation();
                this.showImageOnLeft();
            }),
            Tag.icon('arrow-right-circle').event('click', (e) => {
                e.stopPropagation();
                this.showImageOnRight();
            })
        ).cls('image-ctrl').putInto(this.divImage);
        this.divImageBox = Tag.div().cls('image').putInto(this.divImage);
        const btnUpload = Tag.byId('-btn-upload');
        const divForm = Tag.byId('-div-form-upload');
        if (btnUpload && divForm) {
            btnUpload.clickShow(divForm);
            divForm.outClickHide();
        }
        const btnCheck = Tag.byId('-btn-check');
        if (btnCheck) {
            btnCheck.event('click', (e) => {
                const url = new URL('', location.href);
                url.searchParams.set(TYPE_KEY, 'check');
                Ajax.call(
                    this.ajaxResponse.bind(this),
                    HttpMethod.POST,
                    null,
                    url,
                    MimeType.TEXT,
                    MimeType.HTML
                );
            });
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
                    const newTitle = prompt('New title for image "' + title + '":', title);
                    if (newTitle && newTitle != title) {
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
                this.showImageOnLeft();
                break;
            case 'ArrowRight':
                this.showImageOnRight();
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

    private showImageOnLeft() {
        if (this.current > 0) {
            --this.current;
            this.refreshImage();
        }
    }

    private showImageOnRight() {
        if (this.current < this.imageSet.list.length - 1) {
            ++this.current;
            this.refreshImage();
        }
    }

    private refreshImage() {
        const imageSet = this.imageSet;
        const image = imageSet.list[this.current];
        Tag.of('img').attr({
            src: imageSet.image.prefix + image.name + imageSet.image.suffix
        }).putInto(this.divImageBox.clear());
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

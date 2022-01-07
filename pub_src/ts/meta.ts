import { onLoad } from './html';
import { Ajax, MimeType } from "./ajax";
import { Tag } from './tag';

onLoad(function () {
    const btnEdit = Tag.byId('-meta-btn-edit');
    const btnDelete = Tag.byId('-meta-btn-delete');
    const divForm = Tag.byId('-meta-div-edit-form');
    const ajaxMsg = Tag.byId('-ajax-msg');
    if (btnEdit && divForm) {
        btnEdit.event('click', (e) => {
            divForm.show();
            e.stopPropagation();
        });
        divForm.outClickHide();
    }
    if (btnDelete) {
        btnDelete.event('click', () => {
            const r = confirm('Are you sure to delete this item?');
            if (r) {
                Ajax.delete(function (res) {
                    Tag.byId('main').html(res);
                }, '', MimeType.HTML);
            }
        });
    }
    if (ajaxMsg) {
        ajaxMsg.outClickHide();
    }
});

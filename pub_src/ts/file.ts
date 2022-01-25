import { onLoad } from './html';
import { Ajax, MimeType } from "./ajax";
import { Tag } from './tag';

onLoad(function () {
    const btnUpdate = Tag.byId('-btn-update');
    const btnDelete = Tag.byId('-btn-delete');
    const divForm = Tag.byId('-div-form-update');
    if (btnUpdate && divForm) {
        btnUpdate.event('click', (e) => {
            divForm.show();
            e.stopPropagation();
        });
        divForm.outClickHide();
    }
    if (btnDelete) {
        btnDelete.event('click', () => {
            const r = confirm('Are you sure to delete this file?');
            if (r) {
                Ajax.delete(
                    function (res) {
                        Tag.byId('main').html(res);
                    },
                    null,
                    '',
                    MimeType.HTML
                );
            }
        });
    }
});

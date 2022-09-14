import { onLoad } from './html';
import { Ajax, MimeType } from "./ajax";
import { Tag } from './tag';

onLoad(() => {
    const btnUpdate = Tag.byId('-btn-update');
    const btnDelete = Tag.byId('-btn-delete');
    const divForm = Tag.byId('-div-form-update');
    if (btnUpdate && divForm) {
        btnUpdate.clickShow(divForm);
        divForm.outClickHide();
    }
    if (btnDelete) {
        btnDelete.event('click', () => {
            const r = confirm('Are you sure to delete this file?');
            if (r) {
                Ajax.delete(
                    (r) => {
                        Tag.byId('main')?.html(r);
                    },
                    null,
                    '',
                    MimeType.JSON,
                    MimeType.HTML
                );
            }
        });
    }
});

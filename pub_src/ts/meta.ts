import { onLoad, Tag, Ajax, MimeType } from ".";

onLoad(function () {
    const btnEdit = Tag.byId('-meta-btn-edit-');
    const btnDelete = Tag.byId('-meta-btn-delete-');
    const divForm = Tag.byId('-meta-div-edit-form-');
    if (btnEdit && divForm) {
        btnEdit.event('click', (e) => {
            divForm.style({ display: 'block' });
            e.stopPropagation();
        });
        document.body.addEventListener('click', (e) => {
            divForm.style({ display: 'none' });
        });
        divForm.event('click', (e) => {
            e.stopPropagation();
        });
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
});

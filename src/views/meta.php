<span class="nobr">
    <em><?php echo date('Y.m.d H:i:s', $time); ?></em> by <?php echo $uname; ?>
</span>
<span class="nobr" style="float:right">
    <?php if ($edit) { ?>
        <i id='btn-edit' class="bi bi-pencil-square"></i>
    <?php } ?>
    <?php if ($delete) { ?>
        <i id='btn-delete' class="bi bi-x-square"></i>
    <?php } ?>
</span>
<?php if ($edit) { ?>
    <div id="edit-form-div" style="display:none">
        <?php FileActions::updateForm('更新 ' . $name, $accept, $sizeLimit); ?>
    </div>
    <script>
        const v = byId('edit-form-div');
        v.addEventListener('click', (e) => {
            e.stopPropagation();
        })
        byId('btn-edit').addEventListener('click', (e) => {
            v.style.display = 'block';
            e.stopPropagation();
        });
        document.body.addEventListener('click', (e) => {
            v.style.display = 'none';
        })
    </script>
<?php }
if ($delete) { ?>
    <script>
        byId('btn-delete').addEventListener('click', () => {
            const r = confirm('Are you sure to delete "<?php echo $name; ?>"?');
            if (r) {
                Ajax.delete(function(res, type) {
                    byId('main').innerHTML = res;
                });
            }
        });
    </script>
<?php } ?>

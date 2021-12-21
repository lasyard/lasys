<span class="nobr">
    <em><?php echo date('Y.m.d H:i:s', $time); ?></em> by <?php echo $uname; ?>
</span>
<span class="nobr" style="float:right">
    <?php if ($edit) { ?>
        <i id='-meta-btn-edit-' class="bi bi-pencil-square"></i>
    <?php } ?>
    <?php if ($delete) { ?>
        <i id='-meta-btn-delete-' class="bi bi-x-square"></i>
    <?php } ?>
</span>
<?php if ($edit) { ?>
    <div id="-meta-div-edit-form-" style="display:none">
        <?php FileActions::updateForm('更新 ' . $name, $accept, $sizeLimit); ?>
    </div>
<?php }

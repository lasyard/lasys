<div id="meta">
    <span class="nobr">
        <em><?php echo date('Y.m.d H:i:s', $time); ?></em>
        <?php
        if (isset($uname)) {
            echo ' by ', $uname;
        } ?>
    </span>
    <span class="buttons">
        <?php
        foreach ($buttons as $button) {
            echo $button;
        }
        ?>
    </span>
    <?php if (!empty($editForm)) { ?>
        <div id="-meta-div-edit-form-" style="display:none">
            <?php echo $editForm; ?>
        </div>
    <?php } ?>
</div>

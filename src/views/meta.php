<div id="meta">
    <span id="msg">
        <?php
        if (isset($msg)) {
            echo $msg;
        }
        ?>
    </span>
    <span class="buttons">
        <?php
        if (isset($btnEdit)) {
        ?>
            <span id="-meta-btn-edit"><?php echo $btnEdit; ?></span>
        <?php
        }
        if (isset($btnDelete)) {
        ?>
            <span id="-meta-btn-delete"><?php echo $btnDelete; ?></span>
        <?php
        }
        ?>
    </span>
    <?php if (!empty($editForm)) { ?>
        <div id="-meta-div-edit-form" style="display:none">
            <?php echo $editForm; ?>
        </div>
    <?php } ?>
    <div id="-ajax-msg" style="display:none"></div>
</div>

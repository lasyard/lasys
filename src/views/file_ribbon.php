<div class="ribbon">
    <span>
        <?php
        if (isset($msg)) {
            echo $msg;
        }
        ?>
    </span>
    <span class="buttons">
        <?php
        if (isset($btnUpdate)) {
        ?>
            <span id="-btn-update"><?php echo $btnUpdate; ?></span>
        <?php
        }
        if (isset($btnDelete)) {
        ?>
            <span id="-btn-delete"><?php echo $btnDelete; ?></span>
        <?php
        }
        ?>
    </span>
    <?php if (!empty($formUpdate)) { ?>
        <div id="-div-form-update" style="display:none">
            <?php echo $formUpdate; ?>
        </div>
    <?php } ?>
</div>

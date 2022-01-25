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
        if (isset($btnInsert)) {
        ?>
            <span id="-btn-insert"><?php echo $btnInsert; ?></span>
        <?php
        }
        ?>
    </span>
    <?php if (!empty($formInsert)) { ?>
        <div id="-div-form-insert" style="display:none">
            <?php echo $formInsert; ?>
        </div>
    <?php } ?>
    <div id="-ajax-msg" style="display:none"></div>
</div>

<div class="ribbon">
    <span id="-msg"></span>
    <span class="buttons">
        <?php
        if (isset($btnCheck)) {
        ?>
            <span id="-btn-check"><?php echo $btnCheck; ?></span>
        <?php
        }
        if (isset($btnUpload)) {
        ?>
            <span id="-btn-upload"><?php echo $btnUpload; ?></span>
        <?php
        }
        ?>
    </span>
    <?php if (!empty($formUpload)) { ?>
        <div id="-div-form-upload" style="display:none">
            <?php echo $formUpload; ?>
        </div>
    <?php } ?>
    <div id="-popup-msg" style="display:none"></div>
</div>

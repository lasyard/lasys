<form name="<?php echo $formName ?? ''; ?>" enctype="multipart/form-data" action="<?php echo $action ?? '#'; ?>" method="<?php echo $method ?? 'POST'; ?>">
    <fieldset>
        <legend><?php echo $title; ?></legend>
        <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $sizeLimit; ?>" />
        <div class="field">
            <label>
                <span class="label">File</span>
                <input type="file" name="file" accept="<?php echo $accept ?? '*'; ?>" />
            </label>
        </div>
        <?php if ($hasTitle ?? true) { ?>
            <div class="field">
                <label>
                    <span class="label">Title</span>
                    <input type="text" name="title" />
                </label>
            </div>
        <?php } ?>
    </fieldset>
    <div class="buttons">
        <input type="submit" />
    </div>
</form>

<form name="<?php echo $formName ?? ''; ?>" enctype="multipart/form-data" action="<?php echo $action ?? '#'; ?>" method="<?php echo $method ?? 'POST'; ?>">
    <fieldset>
        <legend><?php echo $title; ?></legend>
        <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $sizeLimit; ?>" />
        <div class="field">
            <label for="file">File</label>
            <input type="file" name="file" accept="<?php echo $accept ?? '*'; ?>" />
        </div>
        <?php if ($hasTitle ?? true) { ?>
            <div class="field">
                <label for="title">Title</label>
                <input type="text" name="title" />
            </div>
        <?php } ?>
    </fieldset>
    <div class="buttons">
        <input type="submit" />
    </div>
</form>

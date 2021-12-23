<fieldset>
    <legend><?php echo $title; ?></legend>
    <form enctype="multipart/form-data" action="<?php echo $action ?? ''; ?>" method="<?php echo $method ?? 'POST'; ?>">
        <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $sizeLimit; ?>" />
        <div class="field">
            <span class="label">File</span>
            <input type="file" name="<?php echo $fieldName; ?>" accept="<?php echo $accept ?? '*'; ?>" />
        </div>
        <div class="field">
            <span class="label"></span>
            <input type="submit" />
        </div>
    </form>
</fieldset>

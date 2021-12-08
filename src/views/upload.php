<fieldset>
    <legend><?php echo $title; ?></legend>
    <form enctype="multipart/form-data" action="<?php echo $action ?? ''; ?>" method="<?php echo $method ?? 'POST'; ?>">
        <div class="field">
            <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $sizeLimit; ?>" />
        </div>
        <div class="field">
            <input type="file" name="<?php echo $fieldName; ?>" accept="<?php echo $accept ?? '*'; ?>" />
        </div>
        <div class="field">
            <input type="submit" />
        </div>
    </form>
</fieldset>

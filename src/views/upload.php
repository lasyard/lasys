<fieldset>
    <legend><?php echo $title; ?></legend>
    <form enctype="multipart/form-data" action="<?php echo $action ?? ''; ?>" method="POST">
        <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $sizeLimit; ?>" />
        <input type="file" name="<?php echo $fieldName; ?>" accept="<?php echo $accept ?? '*'; ?>" />
        <div class="center"><input type="submit" /></div>
    </form>
</fieldset>

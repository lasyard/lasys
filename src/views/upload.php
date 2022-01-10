<form enctype="multipart/form-data" action="<?php echo $action ?? ''; ?>" method="<?php echo $method ?? 'POST'; ?>">
    <fieldset>
        <legend><?php echo $title; ?></legend>
        <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $sizeLimit; ?>" />
        <div class="field">
            <span class="label">File</span>
            <input type="file" name="file" accept="<?php echo $accept ?? '*'; ?>" />
        </div>
        <div class="field">
            <span class="label">Title</span>
            <input type="text" name="title" />
        </div>
    </fieldset>
    <div class="buttons">
        <input type="submit" />
    </div>
</form>

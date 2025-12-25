<form name="<?php echo $formName ?? ''; ?>" enctype="multipart/form-data" action="<?php echo $action ?? '#'; ?>" method="<?php echo $method ?? 'POST'; ?>">
    <fieldset>
        <legend><?php echo $title; ?></legend>
        <div class="field">
            <label>
                <span class="label">Name</span>
                <?php echo Html::input('name', 'text', true, ['dataList' => $nameList]); ?>
            </label>
        </div>
        <div class="field">
            <label>
                <span class="label">Title</span>
                <input type="text" name="title" />
            </label>
        </div>
        <div class="field">
            <label>
                <span class="label">Description</span>
                <input type="text" name="desc" />
            </label>
        </div>
    </fieldset>
    <div class="buttons">
        <input type="submit" />
    </div>
</form>

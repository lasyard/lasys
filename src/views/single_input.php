<form name="<?php echo $formName; ?>" method="<?php echo $method ?? 'POST'; ?>">
    <fieldset>
        <legend><?php echo $title; ?></legend>
        <div class="field">
            <label>
                <span class="label"><?php echo $label; ?></span>
                <?php echo Html::input($name, $type, true, $attrs ?? []); ?>
            </label>
        </div>
    </fieldset>
    <div class="buttons">
        <input type="submit" />
    </div>
</form>

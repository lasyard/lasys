<form name="<?php echo $formName; ?>" method="<?php echo $method ?? 'POST'; ?>">
    <fieldset>
        <legend><?php echo $title; ?></legend>
        <div class="field">
            <label for="<?php echo $name; ?>"><?php echo $label; ?></label>
            <?php echo Html::input($name, $type, true, $attrs ?? []); ?>
        </div>
    </fieldset>
    <div class="buttons">
        <input type="submit" />
    </div>
</form>

<form <?php
        if (isset($attrs)) {
            foreach ($attrs as $key => $value) {
                echo $key, '="', $value, '" ';
            }
        }
        ?>name="<?php echo $name; ?>" action="<?php echo $action ?? '#'; ?>" method="<?php echo $method ?? 'POST'; ?>">
    <fieldset>
        <legend><?php echo $title; ?></legend>
        <?php foreach ($fields as $name => $f) {
            extract($f);
            if ($purpose === 'insert' && $auto) {
                continue;
            }
            if ($readOnly || $purpose === 'update' && ($primary || $auto)) {
                $attrs['disabled'] = 1;
            }
        ?>
            <div class="field">
                <label>
                    <span class="label"><?php echo $label; ?></span>
                    <?php echo Html::input($name, $type, $required, $attrs); ?>
                </label>
            </div>
        <?php } ?>
    </fieldset>
    <div class="buttons">
        <input type="submit" />
        <?php
        if ($purpose === 'update') {
            echo '<span id="-span-insert-new">';
            echo Html::link(Icon::INSERT, 'javascript:void(0)', 'Insert new');
            echo '</span>';
        }
        ?>
    </div>
</form>

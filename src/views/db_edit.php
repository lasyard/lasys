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
            if ($purpose === 'update' && ($primary || $readOnly || $auto)) {
                $attrs['disabled'] = 1;
            }
        ?>
            <div class="field">
                <label for="<?php echo $name; ?>"><?php echo $label; ?></label>
                <?php
                echo Html::input($name, $type, $required, $attrs);
                ?>
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

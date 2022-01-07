<form <?php
        if (isset($attrs)) {
            foreach ($attrs as $key => $value) {
                echo $key, '="', $value, '" ';
            }
        }
        ?>name="<?php echo $name; ?>" action="<?php echo $action ?? ''; ?>" method="<?php echo $method ?? 'POST'; ?>">
    <fieldset>
        <legend><?php echo $title; ?></legend>
        <?php foreach ($fields as $name => $f) {
            if ($purpose === 'insert' && $f['auto']) {
                continue;
            }
            if ($purpose === 'update' && $f['primary']) {
                $f['attrs']['disabled'] = 1;
            }
        ?>
            <div class="field">
                <span class="label"><?php echo $f['label']; ?></span>
                <?php
                echo Html::input($name, $f['type'], $f['required'], $f['attrs']);
                ?>
            </div>
        <?php } ?>
    </fieldset>
    <input type="submit" />
</form>

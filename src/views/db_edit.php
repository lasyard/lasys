<fieldset>
    <legend><?php echo $title; ?></legend>
    <form action="<?php echo $action ?? ''; ?>" method="<?php echo $method ?? 'POST'; ?>">
        <?php foreach ($fields as $name => $f) { ?>
            <div class="field">
                <span class="label"><?php echo $f['label']; ?></span>
                <?php
                echo Html::input($name, $f['type'], $f['required'], $f['attrs']);
                ?>
            </div>
        <?php } ?>
        <div class="field">
            <span class="label"></span>
            <input type="submit" />
        </div>
    </form>
</fieldset>

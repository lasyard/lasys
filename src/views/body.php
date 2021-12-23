<div id="bar">
    <span id="logo"><a href="<?php echo $home; ?>"><?php echo $homeLink ?? 'Lasys Home'; ?></a></span>
    <span id="breadcrumbs">
        <?php
        foreach ($breadcrumbs as $b) {
            echo ' <i class="bi bi-chevron-double-right sys"></i> ' . Html::link($b);
        }
        ?>
    </span>
    <span id="user">
        <?php if (!Sys::user()->isGuest) { ?>
            <a href="<?php echo $home; ?>logout" class="sys">
                <i class="bi bi-person"></i> <?php echo Sys::user()->name; ?></a>
        <?php } ?>
    </span>
</div>
<div id="list">
    <?php if (!empty($buttons)) { ?>
        <div class="buttons">
            <?php
            foreach ($buttons as $btn) {
                echo Html::link($btn);
            }
            ?>
        </div>
    <?php } ?>
    <fieldset>
        <legend><i class="bi bi-files sys"></i> 文件列表 (<span class="hot"><?php echo count($files); ?></span>)</legend>
        <ul>
            <?php
            foreach ($files as $file) {
                echo $file['selected'] ? '<li class="highlighted">' : '<li>';
                echo Html::link($file);
                if (substr($file['url'], -1) == '/') {
                    echo ' <i class="bi bi-folder" style="float:right"></i>';
                }
                echo '</li>', PHP_EOL;
            }
            ?>
        </ul>
    </fieldset>
</div>
<div id="main">
    <?php echo $content; ?>
</div>

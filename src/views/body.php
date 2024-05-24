<div id="bar">
    <span id="logo"><a href="<?php echo $home; ?>">Lasys Home</a></span>
    <span id="breadcrumbs">
        <?php
        foreach ($breadcrumbs as $breadcrumb) {
            echo Icon::BREADCRUMBS, $breadcrumb;
        }
        ?>
    </span>
    <span id="user">
        <?php if (!Sys::user()->isGuest) { ?>
            <a href="<?php echo $home; ?>logout" class="sys"><?php echo Icon::USER, Sys::user()->name; ?></a>
        <?php } ?>
    </span>
</div>
<div id="list">
    <?php if (!empty($buttons)) { ?>
        <div class="buttons">
            <?php
            foreach ($buttons as $btn) {
                echo $btn, ' ';
            }
            ?>
        </div>
    <?php } ?>
    <fieldset>
        <legend><?php echo Icon::FILES; ?> 文件列表 (<span class="hot"><?php echo count($files); ?></span>)</legend>
        <ul>
            <?php
            foreach ($files as $file) {
                echo $file, PHP_EOL;
            }
            ?>
        </ul>
    </fieldset>
</div>
<div id="main">
    <?php echo $content; ?>
</div>
<div id="footer">
    <p class="center">&copy; <?php echo date('Y'); ?> Lasy. All rights reserved.</p>
</div>

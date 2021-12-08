<div id="bar">
    <a href="<?php echo $home; ?>"><?php echo $homeLink ?? 'Lasys Home'; ?></a>
    <div id="breadcrumbs">
        <?php
        foreach ($breadcrumbs as $b) {
            echo ' <i class="green bi bi-chevron-double-right"></i> ' . Html::link($b);
        }
        ?>
    </div>
    <span>
        <?php if (!Sys::user()->isGuest) { ?>
            <a href="<?php echo $home; ?>logout" class="sys" style="float:right">
                <i class="bi bi-person"></i> <?php echo Sys::user()->name; ?></a>
        <?php } ?>
    </span>
</div>
<div id="list">
    <fieldset>
        <legend><i class="bi bi-files red"></i> 文件列表</legend>
        <ul>
            <?php
            foreach ($list as $item) {
                echo $item['selected'] ? '<li class="highlighted">' : '<li>';
                echo Html::link($item);
                if (substr($item['url'], -1) == '/') {
                    echo ' <i class="bi bi-folder" style="float:right"></i>';
                }
                echo '</li>', PHP_EOL;
            }
            ?>
        </ul>
        <?php if ($base != $home) { ?>
            <p><a href="<?php echo dirname($base) . '/'; ?>" class="sys"><i class="bi bi-chevron-double-left"></i> 返回</a></p>
        <?php } ?>
    </fieldset>
</div>
<div id="main">
    <?php if (!empty($meta)) { ?>
        <div id="meta"><?php echo $meta; ?></div>
    <?php } ?>
    <div id="content">
        <?php echo $content; ?>
    </div>
</div>

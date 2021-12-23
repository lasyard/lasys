<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?php echo $title; ?></title>
<script>
    <?php
    foreach ($datum as $data) {
        echo $data . PHP_EOL;
    }
    ?>
</script>
<?php
foreach ($scripts as $script) {
    echo Html::scriptLink($script);
}
foreach ($styles as $style) {
    echo Html::cssLink($style);
}
?>

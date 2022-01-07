<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?php echo $title; ?></title>
<?php
if (!empty($datum)) {
    echo '<script>', PHP_EOL;
    foreach ($datum as $data) {
        echo $data . PHP_EOL;
    }
    echo '</script>', PHP_EOL;
}
foreach ($scripts as $script) {
    echo Html::scriptLink($script);
}
foreach ($styles as $style) {
    echo Html::cssLink($style);
}
if (!empty($css)) {
    echo '<style>', PHP_EOL;
    echo $css, PHP_EOL;
    echo '</style>', PHP_EOL;
}

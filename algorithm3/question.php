<?php

$s1 = "やきゅうボールドまっちゃフラペチーノいちごパフェ";
$s2 = mb_convert_kana($s1, 'cC', "UTF-8");

echo $s2;




?>
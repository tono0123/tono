<?php

$a = array(0=>1,1=>1000,2=>90,3=>105,4=>800,5=>900);
//最大値求める
$max = max($a);
$i = 0;
foreach($a as $k1 => $v1){
	if($i < $v1 && $v1 != $max){
		$i = $v1;
	}
}
echo $i;




?>
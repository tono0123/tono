<?php

/*--------------------------------------------------------------
ジャンケン

変数 $player と $rival には、
ジャンケンの履歴が文字列で入っています
プレイヤー側が何勝何敗したかを求めて、
勝利数：$win
敗北数：$lose
それぞれの変数に結果を格納してください 

<数字の意味>
0:グー
2:チョキ
5:パー

（例）
$player = "050020";
$rival = "500025";

<結果>
$win => 1
$lose => 2

環境PHP4.4.9

http://www.coding-doujo.jp/questions/102/challenge
--------------------------------------------------------------*/

//str_splitが使えないので一旦区切り文字を入れ、配列化
//同じ問題をわかりやすく書き換えました。

$player = wordwrap($player, 1, "-");
$rival = wordwrap($rival, 1, "-");

$player2 = explode("-",$player);
$rival2 = explode("-",$rival);


$win = 0;
$lose = 0;
$i = 0;
foreach($player2 as $v){
	switch($v2){
		//グー
		case 0:
			if($rival2[$i] == 2) $win++;
			if($rival2[$i] == 5) $lose++;
			break;
		//チョキ
		case 2:
			if($rival2[$i] == 5) $win++;
			if($rival2[$i] == 0) $lose++;
			break;
		//パー
		case 5:
			if($rival2[$i] == 0) $win++;
			if($rival2[$i] == 2) $lose++;
			break;
		default:
			break;
	}
	$i++;
}

echo "win => ".$win."<br/>";
echo "lose => ".$lose;




?>
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

//一つ目の回答です。
//str_splitが使えないので一旦区切り文字を入れ、配列化

$player = "050020";
$rival = "500025";

$player = wordwrap($player, 1, "-", true);
$rival = wordwrap($rival, 1, "-", true);

$player2 = explode("-",$player);
$rival2 = explode("-",$rival);


$win = 0;
$lose = 0;
for($i=0; $i<count($player2); $i++){
	//あいこ以外
	if($player2[$i] != $rival2[$i]){
		//勝った時の場合
		if(
			(
			//プレイヤーがグー、ライバルがチョキ
			($player2[$i] == 0 && $rival2[$i] == 2)
			//プレイヤーがチョキ、ライバルがパー
			|| ($player2[$i] == 2 && $rival2[$i] == 5)
			//プレイヤーがパー、ライバルがグー
			|| ($player2[$i] == 5 && $rival2[$i] == 0)
			)
		){
			$win++;
		}else{
			$lose++;
		}		
	}
}
echo "win => ".$win."<br/>";
echo "lose => ".$lose;





?>
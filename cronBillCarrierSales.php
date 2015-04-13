#!/usr/bin/php
<?php
//--------------------------------------------------------------------
//
//	外部API送信をバッチ処理で行うプログラムです。
//	DBの内容はお見せできないので、そこは追加修正してあります。
//
//--------------------------------------------------------------------
	//共通関数
	require_once('mod_common.php');
	require_once('mod_log.php');

	//GMO共通関数
	require_once('mod_cf_gmo_kikan.php');


	//DB接続オブジェクト
	$poGDB=new gcConnectDB();

	//GMO
	$pcGmoKikan = new pcGmoKikan($poGDB);

	
#**********************************************************
# バッチ処理開始
#**********************************************************
if($argv[1]==1){
	
	$sLogText = "【売上計上】";
	
	//一日前の日付を取得
	$sYesterDay = "";
	$sYesterDay = date("Y-m-d",strtotime("-1 day"));
	$hYesterDay = explode("-",$sYesterDay);
	
	$hList = array();
	$hList['iSalesYear'] = $hYesterDay[0];
	$hList['iSalesMonth'] = $hYesterDay[1];
	$hList['iSalesDay'] = $hYesterDay[2];
	
	//売上計上区分
	$hList['psTranKbn'] = "CAPTURE";
	
	//支払方法指定
	$hList['payment_way'] = GS_PAYMENTWAY_CARRIER;

	//売上計上する対象レコード取得
	$hSales =	$pcGmoKikan->fGetGmoSalesList($hList);
	
	$hParamList = array();
	//一つずつ処理
	foreach($hSales as $k1 => $v1){
		$hParamList = $v1;
		$hParamList['Amount'] = $v1['amount'];
		$hParamList['idpass'] = "sales";
		$hParamList['JobCd'] = 'SALES';
		//実行URL取得
		$hParamList['gmopay_url'] = $pcGmoKikan->fGetGmoEcMobilePayurl($hParamList);
		
		
		gfDebugLog($sLogText."処理前:bill_id:".$hParamList['bill_id']);
		//--------------------
		//売上計上実行
		//--------------------
		$pbRet = $pcGmoKikan->fUpdateGmoSalesList($hParamList);
		if(!$pbRet){
			gfDebugLog($sLogText."失敗:bill_id:".$hParamList['bill_id']);
			return false;
		}else{
			gfDebugLog($sLogText."成功:bill_id:".$hParamList['bill_id']);
		}
	}
	
}
	
?>
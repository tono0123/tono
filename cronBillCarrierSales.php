#!/usr/bin/php
<?php
//--------------------------------------------------------------------
//
//	�O��API���M���o�b�`�����ōs���v���O�����ł��B
//	DB�̓��e�͂������ł��Ȃ��̂ŁA�����͒ǉ��C�����Ă���܂��B
//
//--------------------------------------------------------------------
	//���ʊ֐�
	require_once('mod_common.php');
	require_once('mod_log.php');

	//GMO���ʊ֐�
	require_once('mod_cf_gmo_kikan.php');


	//DB�ڑ��I�u�W�F�N�g
	$poGDB=new gcConnectDB();

	//GMO
	$pcGmoKikan = new pcGmoKikan($poGDB);

	
#**********************************************************
# �o�b�`�����J�n
#**********************************************************
if($argv[1]==1){
	
	$sLogText = "�y����v��z";
	
	//����O�̓��t���擾
	$sYesterDay = "";
	$sYesterDay = date("Y-m-d",strtotime("-1 day"));
	$hYesterDay = explode("-",$sYesterDay);
	
	$hList = array();
	$hList['iSalesYear'] = $hYesterDay[0];
	$hList['iSalesMonth'] = $hYesterDay[1];
	$hList['iSalesDay'] = $hYesterDay[2];
	
	//����v��敪
	$hList['psTranKbn'] = "CAPTURE";
	
	//�x�����@�w��
	$hList['payment_way'] = GS_PAYMENTWAY_CARRIER;

	//����v�シ��Ώۃ��R�[�h�擾
	$hSales =	$pcGmoKikan->fGetGmoSalesList($hList);
	
	$hParamList = array();
	//�������
	foreach($hSales as $k1 => $v1){
		$hParamList = $v1;
		$hParamList['Amount'] = $v1['amount'];
		$hParamList['idpass'] = "sales";
		$hParamList['JobCd'] = 'SALES';
		//���sURL�擾
		$hParamList['gmopay_url'] = $pcGmoKikan->fGetGmoEcMobilePayurl($hParamList);
		
		
		gfDebugLog($sLogText."�����O:bill_id:".$hParamList['bill_id']);
		//--------------------
		//����v����s
		//--------------------
		$pbRet = $pcGmoKikan->fUpdateGmoSalesList($hParamList);
		if(!$pbRet){
			gfDebugLog($sLogText."���s:bill_id:".$hParamList['bill_id']);
			return false;
		}else{
			gfDebugLog($sLogText."����:bill_id:".$hParamList['bill_id']);
		}
	}
	
}
	
?>
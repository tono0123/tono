<?php
//------------------------------------------------------------------------------
//
//	GMOプロトコル決済（基幹）
//			auther		date		ver		memo
//			s.madono	20131005	1.0		新規作成
//------------------------------------------------------------------------------

class pcGmoKikan extends gcGmo{
	function pcGmoKikan($db = false){
		//DB接続
		if($db){
			$this->poGDB = $db;
		}else{
			$this->poGDB = new gcConnectDB();
		}
		//エラーメッセージ
		$this->sErrMes = "";
	}
	
	//-------------------------------------------------
	//
	// 売上算出・売上計上 情報取得テーブル
	//
	// $phPost :ポスト値
	//
	//-------------------------------------------------
	function fGetGmoSalesList($phPost){
		
		$aSql=array();
		$aSql[]="SELECT";
		//売上算出
		if($phPost['psTranKbn'] == "CALCULATION"){
			$aSql[]=	"sum(a.amount) as amount";
			$aSql[]=	",count(a.bill_id)";
		//売上計上
		}elseif($phPost['psTranKbn'] == "CAPTURE"){
			$aSql[]=	"a.bill_id";
			$aSql[]=	",a.bill_branch";
			$aSql[]=	",a.bill_branch_sub";
			$aSql[]=	",a.order_id";
			$aSql[]=	",a.accessid";
			$aSql[]=	",a.accesspass";
			$aSql[]=	",a.amount";
		}
		$aSql[]="FROM";
		$aSql[]=	"table_a as a";
		$aSql[]="LEFT JOIN table_b as c";
		$aSql[]=	"ON a.bill_id = c.bill_id AND a.bill_branch = c.bill_branch";
		$aSql[]=	"INNER JOIN";
		$aSql[]=		"(SELECT";
		$aSql[]=			"a_sub.bill_id";
		$aSql[]=			",a_sub.bill_branch";
		$aSql[]=			",(SELECT";
		$aSql[]=				"count(*)";
		$aSql[]=				"FROM table_b as x";
		$aSql[]=				"LEFT JOIN table_c as y";
		$aSql[]=					"ON (x.order_no,x.order_no_branch) = (y.order_no,y.order_no_branch)";
		$aSql[]=				"WHERE (x.bill_id,x.bill_branch) = (a_sub.bill_id,a_sub.bill_branch)";
		$aSql[]=			") as order_cnt";
		$aSql[]=			",(SELECT";
		$aSql[]=				"count(*)";
		$aSql[]=				"FROM table_b as x";
		$aSql[]=				"LEFT JOIN table_c as y";
		$aSql[]=					"ON (x.order_no,x.order_no_branch) = (y.order_no,y.order_no_branch)";
		$aSql[]=				"WHERE (x.bill_id,x.bill_branch) = (a_sub.bill_id,a_sub.bill_branch)";
		$aSql[]=				"AND y.print_sts IN ('PRINT_CANCEL')";
		$aSql[]=			") as cancel_cnt";
		$aSql[]=			",(SELECT";
		$aSql[]=				"count(*)";
		$aSql[]=				"FROM table_b as x";
		$aSql[]=				"LEFT JOIN table_c as y";
		$aSql[]=					"ON (x.order_no,x.order_no_branch) = (y.order_no,y.order_no_branch)";
		$aSql[]=				"WHERE (x.bill_id,x.bill_branch) = (a_sub.bill_id,a_sub.bill_branch)";
		$aSql[]=				"AND y.print_sts IN ('PRINT_THANKS')";
		$aSql[]=			") as thanks_cnt";
		$aSql[]=			",(SELECT";
		$aSql[]=				"count(*)";
		$aSql[]=				"FROM table_b as x";
		$aSql[]=				"LEFT JOIN table_c as y";
		$aSql[]=					"ON (x.order_no,x.order_no_branch) = (y.order_no,y.order_no_branch)";
		$aSql[]=				"WHERE (x.bill_id,x.bill_branch) = (a_sub.bill_id,a_sub.bill_branch)";
		$aSql[]=				"AND y.ship_kanryo_date IS NOT NULL";
		$aSql[]=			") as ship_kanryo_date_cnt";
		$aSql[]=		"FROM";
		$aSql[]=			"table_a as a_sub";
		$aSql[]=		"WHERE";
		$aSql[]=			"a_sub.effect_flg = true";
		$aSql[]=		"GROUP BY";
		$aSql[]=			"a_sub.bill_id,";
		$aSql[]=			"a_sub.bill_branch";
		$aSql[]=		") as e";
		$aSql[]=	"ON (e.bill_id,e.bill_branch) = (a.bill_id,a.bill_branch)";
		$aSql[]="WHERE";
		$aSql[]=	"(e.thanks_cnt = e.order_cnt OR (e.thanks_cnt + e.cancel_cnt) = e.order_cnt)";
		$aSql[]="AND";
		$aSql[]=	"a.effect_flg = true";
		$aSql[]="AND";
		$aSql[]=	"e.thanks_cnt = e.ship_kanryo_date_cnt";
		$aSql[]="AND";
		$aSql[]=	"c.bill_sts = 'BILL_SUMI'";
		$aSql[]="AND";
		$aSql[]=	"c.total_charge = c.payment_charge";
		$aSql[]="AND";
		$aSql[]=	"a.jobcd <> 'SALES'";
		$aSql[]="AND";
		$aSql[]=	"a.jobcd <> 'CAPTURE'";
		if($phPost['payment_way']!=""){
			$aSql[]="AND";
			$aSql[]=	"a.payment_way = ".$phPost['payment_way'];
		}
		$aSql[]="AND";
		$aSql[]=	"(SELECT MAX(ship_kanryo_date)";
		$aSql[]=		"FROM table_c";
		$aSql[]=		"WHERE table_c.cart_no = c.cart_no GROUP BY cart_no) <= '".$phPost['iSalesYear']."-".$phPost['iSalesMonth']."-".$phPost['iSalesDay']." 23:59:59'";
		if($phPost['psTranKbn'] == "CAPTURE"){
			$aSql[]="ORDER BY a.update_time DESC";
		}
		$aSql[]=";";
		$sSql = "";
		$sSql = implode(" ",$aSql);

		//SQL実行
		$pbRet=$this->poGDB->fExecSql($sSql);
		if(!$pbRet){
			$this->sErrMes = "fGetGmoSalesList クエリエラー".implode("<br/>",$aSql);
			$this->poGDB->fErrProcess($this->sErrMes);
			return false;
		}
		//取得したレコードを格納する
		$hSales = $this->poGDB->fFetch();
		
		return $hSales;
	}

	//-------------------------------------------------
	//
	// キャリア決済取引URL判別
	//
	// $hParamList
	//
	//-------------------------------------------------
	function fGetGmoEcMobilePayurl($hParamList){
		
		switch($hParamList['idpass']){
			//取引登録
			case "entrytran":
				if($hParamList['mobile_company'] == "softbank"){
					return GS_GMOPAY_ENTRYTRAINSB;
				}elseif($hParamList['mobile_company'] == "au"){
					return GS_GMOPAY_ENTRYTRAINAU;
				}elseif($hParamList['mobile_company'] == "docomo"){
					return GS_GMOPAY_ENTRYTRAINDOCOMO;
				}
				break;
			//決済実行
			case "exectran":
				if($hParamList['mobile_company'] == "softbank"){
					return GS_GMOPAY_EXECTRAINSB;
				}elseif($hParamList['mobile_company'] == "au"){
					return GS_GMOPAY_EXECTRAINAU;
				}elseif($hParamList['mobile_company'] == "docomo"){
					return GS_GMOPAY_EXECTRAINDOCOMO;
				}
				break;
			//支払手続き開始IFの呼び出し
			case "start":
				if($hParamList['mobile_company'] == "softbank"){
					return GS_GMOPAY_SBSTART;
				}elseif($hParamList['mobile_company'] == "au"){
					return GS_GMOPAY_AUSTART;
				}elseif($hParamList['mobile_company'] == "docomo"){
					return GS_GMOPAY_DOCOMOSTART;
				}
				break;
			//実売上
			case "sales":
				if($hParamList['mobile_company'] == "softbank"){
					return GS_GMOPAY_SBSALES;
				}elseif($hParamList['mobile_company'] == "au"){
					return GS_GMOPAY_AUSALES;
				}elseif($hParamList['mobile_company'] == "docomo"){
					return GS_GMOPAY_DOCOMOSALES;
				}
				break;
			//キャンセル
			case "cancel":
				if($hParamList['mobile_company'] == "softbank"){
					return GS_GMOPAY_SBCANCEL;
				}elseif($hParamList['mobile_company'] == "au"){
					return GS_GMOPAY_AUCANCEL;
				}elseif($hParamList['mobile_company'] == "docomo"){
					return GS_GMOPAY_DOCOMOCANCEL;
				}
				break;
		}
	}
	
	//-------------------------------------------------
	//
	// 売上計上処理
	//
	// bill_idを一つずつ渡して処理
	// $hParamList :ポスト、登録に必要な配列
	//
	//-------------------------------------------------
	function fUpdateGmoSalesList($hParamList){
		
		//トランザクションスタート
		$this->poGDB-> fBegin();
		
		//GMOへPOST
		$sAfterTrain = $this->fSendGmoPostData($hParamList);
		if(!$sAfterTrain){
			$this->sErrMes = "fUpdateGmoSalesList 決済実行失敗。 返り値:".$sAfterTrain." bill_id:".$hParamList['bill_id'];
			$this->poGDB->fErrProcess($this->sErrMes);
			return false;
		}else{			
			//テーブルに登録
			$pbRet = $this->fInsertGmoSlnKessai($hParamList,$sAfterTrain);
			if(!$pbRet){
				$this->sErrMes = "fUpdateGmoSalesList 登録失敗。 bill_id:".$hParamList['bill_id'];
				$this->poGDB->fErrProcess($this->sErrMes);
				//ロールバック
				$this->poGDB->fRollBack();
				return false;
			}else{
				pfCreditLog("売上計上テーブル登録完了 bill_id:".$hParamList['bill_id']);
				//コミット
				$this->poGDB-> fCommit();
			}
		}
		
		return $pbRet;
	}

	
	//-------------------------------------------------
	//
	// POST
	//
	// $hParamList		:POSTされた内容
	//								+fGetBillInfoから取得した請求に関する情報
	//
	//-------------------------------------------------
	function fSendGmoPostData($hParamList){
		
		//エラー
		global $ghGMOCARDERR;
		
		unset($hPost);
		//POSTしたい内容
		switch($hParamList['gmopay_url']){
			/* -------------------------------------------------- */
			//
			// 取引登録
			//
			/* -------------------------------------------------- */
			//---------------
			//クレジットカード
			//---------------
			case GS_GMOPAY_ENTRYTRAIN:
				//SHOPID
				$hPost['ShopID'] = GS_GMOPAY_SHOPID;
				//SHOPPASS
				$hPost['ShopPass'] = GS_GMOPAY_SHOPPASS;
				//オーダーID
				$hPost['OrderID'] = $hParamList['order_id'];
				//処理区分
				//CHECK:有効性チェック CAPTURE:即時売上 AUTH:仮売上 SAUTH:簡易オーソリ
				$hPost['JobCd'] = 'AUTH';
				//利用金額
				$hPost['Amount'] = $hParamList['total_charge'] - $hParamList['payment_charge'];
				//税送料
				$hPost['Tax'] = '0';
				//商品コード
				$hPost['ItemCode'] = '';
				//本人承認サービス利用フラグ
				$hPost['TdFlag'] = '0';
				//3Dセキュア表示店舗名
				$hPost['TdTenantName'] = '';
				$hPost['graphic'] = 0;
				break;
				
			//---------------
			//キャリア決済
			//---------------
			case GS_GMOPAY_ENTRYTRAINSB:
			case GS_GMOPAY_ENTRYTRAINAU:
			case GS_GMOPAY_ENTRYTRAINDOCOMO:
				//Version
				$hPost['Version'] = "";
				//SHOPID
				$hPost['ShopID'] = GS_GMOPAY_SHOPID;
				//SHOPPASS
				$hPost['ShopPass'] = GS_GMOPAY_SHOPPASS;
				//オーダーID
				$hPost['OrderID'] = $hParamList['order_id'];
				//処理区分
				//CAPTURE:即時売上 AUTH:仮売上
				$hPost['JobCd'] = 'AUTH';
				//利用金額
				$hPost['Amount'] = $hParamList['total_charge'] - $hParamList['payment_charge'];
				//税送料
				$hPost['Tax'] = '0';
				$hPost['graphic'] = 0;
				break;
				
			/* -------------------------------------------------- */
			//
			// 決済実行
			//
			/* -------------------------------------------------- */
			//---------------
			//クレジットカード
			//---------------
			case GS_GMOPAY_EXECTRAIN:
				/*------------------------------------------------*/
				//支払方法 Method
				//1:一括 2:分割 3:ボーナス一括 4:ボーナス分割 5:リボ
				//
				//支払回数 PayTimes
				/*------------------------------------------------*/
				if($hParamList['PayType']!=""){
					$hParamList['PayTimes'] = "";
					switch($hParamList['PayType']){
						//翌月一括
						case "01":
							$hParamList['Method'] = "1";
							break;
						//ボーナス一回(12/16～ 6/10、7/16～11/10)
						case "80":
							$hParamList['Method'] = "3";
							break;
						//リボルビング
						case "88":
							$hParamList['Method'] = "5";
							break;
						//分割(2,3,6,10,15,20)
						case "02":
						case "03":
						case "06":
						case "10":
						case "15":
						case "20":
							$hParamList['Method'] = "2";
							$hParamList['PayTimes'] = $hParamList['PayType'];
							break;
						default:
							break;
					}
				}
				
				//取引ID
				$hPost['AccessID'] = $hParamList['accessid'];
				//取引Pass
				$hPost['AccessPass'] = $hParamList['accesspass'];
				//オーダーID
				$hPost['OrderID'] = $hParamList['order_id'];
				//支払方法
				$hPost['Method'] = $hParamList['Method'];
				//支払回数
				$hPost['PayTimes'] = $hParamList['PayTimes'];
				//カード番号
				$hPost['CardNo'] = $hParamList['CardNo'];
				//有効期限
				$hPost['Expire'] = $hParamList['CardExp2'].$hParamList['CardExp1'];
				//セキュリティコード
				$hPost['SecurityCode'] = $hParamList['SecCd'];
				//暗証番号
				$hPost['PIN'] = "";
				//自由項目 bill_id
				$hPost['ClientField1'] = $hParamList['bill_id'];
				$hPost['ClientField2'] = $hParamList['bill_branch'];
				$hPost['ClientField3'] = $hParamList['bill_branch_sub'];
				$hPost['graphic'] = 0;
				break;
				
			//---------------
			//キャリア決済
			//---------------
			case GS_GMOPAY_EXECTRAINSB:
			case GS_GMOPAY_EXECTRAINAU:
			case GS_GMOPAY_EXECTRAINDOCOMO:
				//Version
				$hPost['Version'] = "";
				//SHOPID
				$hPost['ShopID'] = GS_GMOPAY_SHOPID;
				//SHOPPASS
				$hPost['ShopPass'] = GS_GMOPAY_SHOPPASS;
				//取引ID
				$hPost['AccessID'] = $hParamList['accessid'];
				//取引Pass
				$hPost['AccessPass'] = $hParamList['accesspass'];
				//オーダーID
				$hPost['OrderID'] = $hParamList['order_id'];
				//自由項目 bill_id
				$hPost['ClientField1'] = $hParamList['bill_id'];
				$hPost['ClientField2'] = $hParamList['bill_branch'];
				$hPost['ClientField3'] = $hParamList['bill_branch_sub'];
				//決済結果戻しURL
				$hPost['RetURL'] = GS_GMOPAY_RETURL;
				//支払開始期限秒
				$hPost['PaymentTermSec'] = GI_GMOPAY_TERMSEC;
				//auのときのみ
				if($hParamList['gmopay_url'] == GS_GMOPAY_EXECTRAINAU){
					//摘要
					$hPost['Commodity'] = $hParamList['commodity'];
					//表示サービス名
					$hPost['ServiceName'] = GS_GMOPAY_SERVICENAME;
					//表示電話番号
					$hPost['ServiceTel'] = GS_GMOPAY_SERVICETEL;
				//docomoのときのみ
  				}elseif($hParamList['gmopay_url'] == GS_GMOPAY_EXECTRAINDOCOMO){
					//ドコモ表示項目1
					$hPost['DocomoDisp1'] = $hParamList['docomodisp1'];
					//ドコモ表示項目2
					$hPost['DocomoDisp2'] = $hParamList['docomodisp2'];
				}
				$hPost['graphic'] = 0;
				break;
				

			/* -------------------------------------------------- */
			//
			// 決済変更
			//
			/* -------------------------------------------------- */
			//---------------
			//クレジットカード
			//取消・再オーソリ・実売上
			//---------------
			case GS_GMOPAY_ALTERTRAN:
				//SHOPID
				$hPost['ShopID'] = GS_GMOPAY_SHOPID;
				//SHOPPASS
				$hPost['ShopPass'] = GS_GMOPAY_SHOPPASS;
				//取引ID
				$hPost['AccessID'] = $hParamList['accessid'];
				//取引Pass
				$hPost['AccessPass'] = $hParamList['accesspass'];
				//処理区分
				/*-----------------------------------
					VOID:取消 RETURN:返品 RETURNX:月跨り返品
					CAPTURE:即時売上 AUTH:仮売上
					SALES:実売上
				-----------------------------------*/
				$hPost['JobCd'] = $hParamList['JobCd'];
				//再オーソリの場合
				if($hPost['JobCd'] == "CAPTURE" || $hPost['JobCd'] == "AUTH"){
					//利用金額
					$hPost['Amount'] = $hParamList['total_charge'] - $hParamList['payment_charge'];
					//税送料
					$hPost['Tax'] = "0";
					//支払方法
					$hPost['Method'] = $hParamList['Method'];
					//支払方法
					$hPost['PayTimes'] = $hParamList['PayTimes'];
				//売上計上の場合
				}elseif($hPost['JobCd'] == "SALES"){
					//利用金額
					$hPost['Amount'] = $hParamList['Amount'];
				}
				$hPost['graphic'] = 0;
				break;
				

			/* -------------------------------------------------- */
			//
			// 決済キャンセル
			//
			/* -------------------------------------------------- */
			//---------------
			//キャリア決済
			//---------------
			case GS_GMOPAY_SBCANCEL:
			case GS_GMOPAY_AUCANCEL:
			case GS_GMOPAY_DOCOMOCANCEL:
				//Version
				$hPost['Version'] = "";
				//SHOPID
				$hPost['ShopID'] = GS_GMOPAY_SHOPID;
				//SHOPPASS
				$hPost['ShopPass'] = GS_GMOPAY_SHOPPASS;
				//取引ID
				$hPost['AccessID'] = $hParamList['accessid'];
				//取引Pass
				$hPost['AccessPass'] = $hParamList['accesspass'];
				//オーダーID
				$hPost['OrderID'] = $hParamList['order_id'];
				//キャンセル金額
				$hPost['CancelAmount'] = $hParamList['Amount'];
				//キャンセル税送料
				$hPost['CancelTax'] = "0";
				$hPost['graphic'] = 0;
				break;

			/* -------------------------------------------------- */
			//
			// 金額変更
			//
			/* -------------------------------------------------- */
			//---------------
			//クレジットカード
			//---------------
			case GS_GMOPAY_CHANGETRAN:
				//SHOPID
				$hPost['ShopID'] = GS_GMOPAY_SHOPID;
				//SHOPPASS
				$hPost['ShopPass'] = GS_GMOPAY_SHOPPASS;
				//取引ID
				$hPost['AccessID'] = $hParamList['accessid'];
				//取引Pass
				$hPost['AccessPass'] = $hParamList['accesspass'];
				//処理区分
				$hPost['JobCd'] = 'AUTH';
				//利用金額
				$hPost['Amount'] = $hParamList['Amount'];
				//税送料
				$hPost['Tax'] = "0";
				$hPost['graphic'] = 0;
				break;

			/* -------------------------------------------------- */
			//
			// 実売上
			//
			/* -------------------------------------------------- */
			//---------------
			//キャリア決済
			//---------------
			case GS_GMOPAY_SBSALES:
			case GS_GMOPAY_AUSALES:
			case GS_GMOPAY_DOCOMOSALES:
				//Version
				$hPost['Version'] = "";
				//SHOPID
				$hPost['ShopID'] = GS_GMOPAY_SHOPID;
				//SHOPPASS
				$hPost['ShopPass'] = GS_GMOPAY_SHOPPASS;
				//取引ID
				$hPost['AccessID'] = $hParamList['accessid'];
				//取引Pass
				$hPost['AccessPass'] = $hParamList['accesspass'];
				//オーダーID
				$hPost['OrderID'] = $hParamList['order_id'];
				//利用金額
				$hPost['Amount'] = $hParamList['Amount'];
				//税送料
				$hPost['Tax'] = '0';
				$hPost['graphic'] = 0;
				break;
		}
		
		$sSendGmoUrl = "";
		$sSendGmoUrl = GS_GMOPAY_URL.$hParamList['gmopay_url'];
		
		$aTmp = array();
		foreach($hPost as $k => $v){
			$aTmp[] = $k."=".$this->poGDB->fEscStr($v);
			//セッションに入れる
			if($_SESSION['payment_gmo'][$k] == "") $_SESSION['payment_gmo'][$k] = $v;
		}
		$sPostTxt = implode("&",$aTmp);
		
		//送信コマンド
		$sCmd = "echo '{$sPostTxt}' | POST {$sSendGmoUrl}\n";
		//送信
		$sRes = `{$sCmd}`;
		
		//エラーコード取得
		$hErr = explode("&", $sRes);
		if(strstr($hErr['0'], 'ErrCode') && strstr($hErr['1'], 'ErrInfo')){
			$hErr['code'] = str_replace("ErrCode=", "", $hErr['0']);
			$hErr['info'] = str_replace("ErrInfo=", "", $hErr['1']);
			$hErrCode = explode("|", $hErr['code']);
			$hErrInfo = explode("|", $hErr['info']);
			
			$hParamList['err_txt'] = "";
			for($i=0; $i<count($hErrCode); $i++){
				$this->sErrMes .= $ghGMOCARDERR[$hErrCode[$i]][$hErrInfo[$i]]."<br/>";
				$hParamList['err_txt'].= $ghGMOCARDERR[$hErrCode[$i]][$hErrInfo[$i]]."|";
			}
			$hParamList['err_txt'] = rtrim($hParamList['err_txt'], "|");
			
			//登録
			//----------------------------------------------------
			//トランザクションスタート
			$this->poGDB-> fBegin();
			
			$hParamList['err_code'] = $hErr['code'];
			$hParamList['err_info'] = $hErr['info'];
			//tranidとaccessidがないので無理やり作る
			$hParamList['tranid'] = $hParamList['bill_id'].date('YmdHis');
			$hParamList['accessid'] = $hParamList['bill_id'].date('YmdHis');
			$hParamList['gmopay_url'] = GS_GMOPAY_ERROR;
			$hParamList['effect_flg'] = "false";
			$_SESSION['payment_gmo']['JobCd'] = "ERROR";
			
			//エラーコード登録
			$pbRet = $this->fInsertGmoSlnKessai($hParamList);
			if(!$pbRet){
				$this->sErrMes = "fInsertGmoSlnKessaiクエリエラー エラーコード登録";
				gfDebugLog($this->sErrMes);
				//ロールバック
				$this->poGDB->fRollBack();
				return false;
			}else{
				//コミット
				$this->poGDB-> fCommit();
			}
			//----------------------------------------------------
			
			return false;
		}else{
			return $sRes;
		}
		
	}

	//-------------------------------------------------
	//
	// テーブルへ登録
	//
	// $hParamList				:$_REQUEST
	//										+fGetBillInfoから取得した請求に関する情報
	// $sExecTrain	:GMOからの返り値
	//
	//-------------------------------------------------
	function fInsertGmoSlnKessai($hParamList,$sExecTrain=""){
		
		//GMOからの返り値を分解
		if($sExecTrain!=""){
			$hExecTrain = explode("&", $sExecTrain);
			$hGmoReturn = array();
			foreach($hExecTrain as $k1 => $v1){
				$hGmoR = explode("=", $v1);
				//小文字にする
				$sStrK1 = "";
				$sStrV1 = "";
				$sStrK1 = strtolower($hGmoR['0']);
				$sStrV1 = $hGmoR['1'];
				
				$hGmoReturn[$sStrK1] = $sStrV1;
			}
		}
		
		//有効・無効フラグを修正
		if(
			$hParamList['gmopay_url'] == GS_GMOPAY_ALTERTRAN
			|| $hParamList['gmopay_url'] == GS_GMOPAY_CHANGETRAN
			|| ($hParamList['idpass'] == "start" && $hParamList["Amount"] > 0)
			|| $hParamList['idpass'] == "sales"
			|| ($hParamList['idpass'] == "cancel" && $hParamList["Amount"] > 0)
		){
			//UPDATE
			$pbRet = true;
			$aSql=array();
			$aSql['field'] = "update_time = '".date('Y-m-d H:i:s')."' ";
			$aSql['field'].= ",effect_flg = false";
			
			$sSql = "";
			$sSql.= "UPDATE table_a SET " . $aSql['field'];
			$sSql.= 		" WHERE bill_id = ".$this->poGDB->fEscStr($hParamList['bill_id']);
			$sSql.= 		" AND bill_branch = ".$this->poGDB->fEscStr($hParamList['bill_branch']);
			$sSql.= 		" AND order_id = '".$this->poGDB->fEscStr($hParamList['order_id'])."' ";
			if($hParamList['accessid']!="") 		$sSql.=" AND accessid = '".$this->poGDB->fEscStr($hParamList['accessid'])."' ";
			if($hParamList['accesspass']!="") 	$sSql.=" AND accesspass = '".$this->poGDB->fEscStr($hParamList['accesspass'])."' ";
			$sSql.= ";";
			$pbRet = $this->poGDB->fExecSql($sSql);
			if(!$pbRet){
				$this->sErrMes = "fInsertGmoSlnKessaiクエリエラー".$sSql;
				$this->poGDB->fErrProcess($this->sErrMes);
				return false;
			}
		}
		
		//bill_branch_subを取得
		$sSql = "";
		$sSql.= "SELECT MAX(bill_branch_sub) FROM table_a WHERE bill_id = ".$hParamList['bill_id'];
		$pbRet=$this->poGDB->fExecSql($sSql);
		if(!$pbRet){
			$this->sErrMes = "fSendGmoPostData エラーコード取得 データ取得失敗:".$sSql;
			$this->poGDB->fErrProcess($this->sErrMes);
			return false;
		}
		$hMax = $this->poGDB->fFetch();
		if(count($hMax) > 0){
			$hParamList['bill_branch_sub'] = $hMax['0']['max'] + 1;
		}

		//INSERT
		$aSql=array();
		$aSql[]="INSERT INTO table_a (";
		$aSql[]=	"bill_id";
		$aSql[]=	"bill_branch";
		$aSql[]=	"bill_branch_sub";
		$aSql[]=	"order_id";
		$aSql[]=	"accessid";
		$aSql[]=	"accesspass";
		$aSql[]=") VALUES (";
		$aSql[]=	$this->poGDB->fEscStr($hParamList['bill_id']);
		$aSql[]=	",".$this->poGDB->fEscStr($hParamList['bill_branch']);
		$aSql[]=	",".$this->poGDB->fEscStr($hParamList['bill_branch_sub']);
		$aSql[]=	",'".$this->poGDB->fEscStr($hParamList['order_id'])."'";
		$aSql[]=	",'".$this->poGDB->fEscStr($hParamList['accessid'])."'";
		$aSql[]=	",'".$this->poGDB->fEscStr($hParamList['accesspass'])."'";
		$aSql[]=");";
		$aSql[]=";";
		$sSql = "";
		$sSql = implode(" ",$aSql);
		$pbRet = $this->poGDB->fExecSql($sSql);
		if(!$pbRet){
			$this->sErrMes = "fInsertGmoSlnKessaiクエリエラー".$sSql;
			$this->poGDB->fErrProcess($this->sErrMes);
			return false;
		}
		
		return $pbRet;
	}


}//class 終端
?>

<?php
//------------------------------------------------------------------------------
//
//	GMO�v���g�R�����ρi��j
//			auther		date		ver		memo
//			s.madono	20131005	1.0		�V�K�쐬
//------------------------------------------------------------------------------

class pcGmoKikan extends gcGmo{
	function pcGmoKikan($db = false){
		//DB�ڑ�
		if($db){
			$this->poGDB = $db;
		}else{
			$this->poGDB = new gcConnectDB();
		}
		//�G���[���b�Z�[�W
		$this->sErrMes = "";
	}
	
	//-------------------------------------------------
	//
	// ����Z�o�E����v�� ���擾�e�[�u��
	//
	// $phPost :�|�X�g�l
	//
	//-------------------------------------------------
	function fGetGmoSalesList($phPost){
		
		$aSql=array();
		$aSql[]="SELECT";
		//����Z�o
		if($phPost['psTranKbn'] == "CALCULATION"){
			$aSql[]=	"sum(a.amount) as amount";
			$aSql[]=	",count(a.bill_id)";
		//����v��
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

		//SQL���s
		$pbRet=$this->poGDB->fExecSql($sSql);
		if(!$pbRet){
			$this->sErrMes = "fGetGmoSalesList �N�G���G���[".implode("<br/>",$aSql);
			$this->poGDB->fErrProcess($this->sErrMes);
			return false;
		}
		//�擾�������R�[�h���i�[����
		$hSales = $this->poGDB->fFetch();
		
		return $hSales;
	}

	//-------------------------------------------------
	//
	// �L�����A���ώ��URL����
	//
	// $hParamList
	//
	//-------------------------------------------------
	function fGetGmoEcMobilePayurl($hParamList){
		
		switch($hParamList['idpass']){
			//����o�^
			case "entrytran":
				if($hParamList['mobile_company'] == "softbank"){
					return GS_GMOPAY_ENTRYTRAINSB;
				}elseif($hParamList['mobile_company'] == "au"){
					return GS_GMOPAY_ENTRYTRAINAU;
				}elseif($hParamList['mobile_company'] == "docomo"){
					return GS_GMOPAY_ENTRYTRAINDOCOMO;
				}
				break;
			//���ώ��s
			case "exectran":
				if($hParamList['mobile_company'] == "softbank"){
					return GS_GMOPAY_EXECTRAINSB;
				}elseif($hParamList['mobile_company'] == "au"){
					return GS_GMOPAY_EXECTRAINAU;
				}elseif($hParamList['mobile_company'] == "docomo"){
					return GS_GMOPAY_EXECTRAINDOCOMO;
				}
				break;
			//�x���葱���J�nIF�̌Ăяo��
			case "start":
				if($hParamList['mobile_company'] == "softbank"){
					return GS_GMOPAY_SBSTART;
				}elseif($hParamList['mobile_company'] == "au"){
					return GS_GMOPAY_AUSTART;
				}elseif($hParamList['mobile_company'] == "docomo"){
					return GS_GMOPAY_DOCOMOSTART;
				}
				break;
			//������
			case "sales":
				if($hParamList['mobile_company'] == "softbank"){
					return GS_GMOPAY_SBSALES;
				}elseif($hParamList['mobile_company'] == "au"){
					return GS_GMOPAY_AUSALES;
				}elseif($hParamList['mobile_company'] == "docomo"){
					return GS_GMOPAY_DOCOMOSALES;
				}
				break;
			//�L�����Z��
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
	// ����v�㏈��
	//
	// bill_id������n���ď���
	// $hParamList :�|�X�g�A�o�^�ɕK�v�Ȕz��
	//
	//-------------------------------------------------
	function fUpdateGmoSalesList($hParamList){
		
		//�g�����U�N�V�����X�^�[�g
		$this->poGDB-> fBegin();
		
		//GMO��POST
		$sAfterTrain = $this->fSendGmoPostData($hParamList);
		if(!$sAfterTrain){
			$this->sErrMes = "fUpdateGmoSalesList ���ώ��s���s�B �Ԃ�l:".$sAfterTrain." bill_id:".$hParamList['bill_id'];
			$this->poGDB->fErrProcess($this->sErrMes);
			return false;
		}else{			
			//�e�[�u���ɓo�^
			$pbRet = $this->fInsertGmoSlnKessai($hParamList,$sAfterTrain);
			if(!$pbRet){
				$this->sErrMes = "fUpdateGmoSalesList �o�^���s�B bill_id:".$hParamList['bill_id'];
				$this->poGDB->fErrProcess($this->sErrMes);
				//���[���o�b�N
				$this->poGDB->fRollBack();
				return false;
			}else{
				pfCreditLog("����v��e�[�u���o�^���� bill_id:".$hParamList['bill_id']);
				//�R�~�b�g
				$this->poGDB-> fCommit();
			}
		}
		
		return $pbRet;
	}

	
	//-------------------------------------------------
	//
	// POST
	//
	// $hParamList		:POST���ꂽ���e
	//								+fGetBillInfo����擾���������Ɋւ�����
	//
	//-------------------------------------------------
	function fSendGmoPostData($hParamList){
		
		//�G���[
		global $ghGMOCARDERR;
		
		unset($hPost);
		//POST���������e
		switch($hParamList['gmopay_url']){
			/* -------------------------------------------------- */
			//
			// ����o�^
			//
			/* -------------------------------------------------- */
			//---------------
			//�N���W�b�g�J�[�h
			//---------------
			case GS_GMOPAY_ENTRYTRAIN:
				//SHOPID
				$hPost['ShopID'] = GS_GMOPAY_SHOPID;
				//SHOPPASS
				$hPost['ShopPass'] = GS_GMOPAY_SHOPPASS;
				//�I�[�_�[ID
				$hPost['OrderID'] = $hParamList['order_id'];
				//�����敪
				//CHECK:�L�����`�F�b�N CAPTURE:�������� AUTH:������ SAUTH:�ȈՃI�[�\��
				$hPost['JobCd'] = 'AUTH';
				//���p���z
				$hPost['Amount'] = $hParamList['total_charge'] - $hParamList['payment_charge'];
				//�ő���
				$hPost['Tax'] = '0';
				//���i�R�[�h
				$hPost['ItemCode'] = '';
				//�{�l���F�T�[�r�X���p�t���O
				$hPost['TdFlag'] = '0';
				//3D�Z�L���A�\���X�ܖ�
				$hPost['TdTenantName'] = '';
				$hPost['graphic'] = 0;
				break;
				
			//---------------
			//�L�����A����
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
				//�I�[�_�[ID
				$hPost['OrderID'] = $hParamList['order_id'];
				//�����敪
				//CAPTURE:�������� AUTH:������
				$hPost['JobCd'] = 'AUTH';
				//���p���z
				$hPost['Amount'] = $hParamList['total_charge'] - $hParamList['payment_charge'];
				//�ő���
				$hPost['Tax'] = '0';
				$hPost['graphic'] = 0;
				break;
				
			/* -------------------------------------------------- */
			//
			// ���ώ��s
			//
			/* -------------------------------------------------- */
			//---------------
			//�N���W�b�g�J�[�h
			//---------------
			case GS_GMOPAY_EXECTRAIN:
				/*------------------------------------------------*/
				//�x�����@ Method
				//1:�ꊇ 2:���� 3:�{�[�i�X�ꊇ 4:�{�[�i�X���� 5:���{
				//
				//�x���� PayTimes
				/*------------------------------------------------*/
				if($hParamList['PayType']!=""){
					$hParamList['PayTimes'] = "";
					switch($hParamList['PayType']){
						//�����ꊇ
						case "01":
							$hParamList['Method'] = "1";
							break;
						//�{�[�i�X���(12/16�` 6/10�A7/16�`11/10)
						case "80":
							$hParamList['Method'] = "3";
							break;
						//���{���r���O
						case "88":
							$hParamList['Method'] = "5";
							break;
						//����(2,3,6,10,15,20)
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
				
				//���ID
				$hPost['AccessID'] = $hParamList['accessid'];
				//���Pass
				$hPost['AccessPass'] = $hParamList['accesspass'];
				//�I�[�_�[ID
				$hPost['OrderID'] = $hParamList['order_id'];
				//�x�����@
				$hPost['Method'] = $hParamList['Method'];
				//�x����
				$hPost['PayTimes'] = $hParamList['PayTimes'];
				//�J�[�h�ԍ�
				$hPost['CardNo'] = $hParamList['CardNo'];
				//�L������
				$hPost['Expire'] = $hParamList['CardExp2'].$hParamList['CardExp1'];
				//�Z�L�����e�B�R�[�h
				$hPost['SecurityCode'] = $hParamList['SecCd'];
				//�Ïؔԍ�
				$hPost['PIN'] = "";
				//���R���� bill_id
				$hPost['ClientField1'] = $hParamList['bill_id'];
				$hPost['ClientField2'] = $hParamList['bill_branch'];
				$hPost['ClientField3'] = $hParamList['bill_branch_sub'];
				$hPost['graphic'] = 0;
				break;
				
			//---------------
			//�L�����A����
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
				//���ID
				$hPost['AccessID'] = $hParamList['accessid'];
				//���Pass
				$hPost['AccessPass'] = $hParamList['accesspass'];
				//�I�[�_�[ID
				$hPost['OrderID'] = $hParamList['order_id'];
				//���R���� bill_id
				$hPost['ClientField1'] = $hParamList['bill_id'];
				$hPost['ClientField2'] = $hParamList['bill_branch'];
				$hPost['ClientField3'] = $hParamList['bill_branch_sub'];
				//���ό��ʖ߂�URL
				$hPost['RetURL'] = GS_GMOPAY_RETURL;
				//�x���J�n�����b
				$hPost['PaymentTermSec'] = GI_GMOPAY_TERMSEC;
				//au�̂Ƃ��̂�
				if($hParamList['gmopay_url'] == GS_GMOPAY_EXECTRAINAU){
					//�E�v
					$hPost['Commodity'] = $hParamList['commodity'];
					//�\���T�[�r�X��
					$hPost['ServiceName'] = GS_GMOPAY_SERVICENAME;
					//�\���d�b�ԍ�
					$hPost['ServiceTel'] = GS_GMOPAY_SERVICETEL;
				//docomo�̂Ƃ��̂�
  				}elseif($hParamList['gmopay_url'] == GS_GMOPAY_EXECTRAINDOCOMO){
					//�h�R���\������1
					$hPost['DocomoDisp1'] = $hParamList['docomodisp1'];
					//�h�R���\������2
					$hPost['DocomoDisp2'] = $hParamList['docomodisp2'];
				}
				$hPost['graphic'] = 0;
				break;
				

			/* -------------------------------------------------- */
			//
			// ���ϕύX
			//
			/* -------------------------------------------------- */
			//---------------
			//�N���W�b�g�J�[�h
			//����E�ăI�[�\���E������
			//---------------
			case GS_GMOPAY_ALTERTRAN:
				//SHOPID
				$hPost['ShopID'] = GS_GMOPAY_SHOPID;
				//SHOPPASS
				$hPost['ShopPass'] = GS_GMOPAY_SHOPPASS;
				//���ID
				$hPost['AccessID'] = $hParamList['accessid'];
				//���Pass
				$hPost['AccessPass'] = $hParamList['accesspass'];
				//�����敪
				/*-----------------------------------
					VOID:��� RETURN:�ԕi RETURNX:���ׂ�ԕi
					CAPTURE:�������� AUTH:������
					SALES:������
				-----------------------------------*/
				$hPost['JobCd'] = $hParamList['JobCd'];
				//�ăI�[�\���̏ꍇ
				if($hPost['JobCd'] == "CAPTURE" || $hPost['JobCd'] == "AUTH"){
					//���p���z
					$hPost['Amount'] = $hParamList['total_charge'] - $hParamList['payment_charge'];
					//�ő���
					$hPost['Tax'] = "0";
					//�x�����@
					$hPost['Method'] = $hParamList['Method'];
					//�x�����@
					$hPost['PayTimes'] = $hParamList['PayTimes'];
				//����v��̏ꍇ
				}elseif($hPost['JobCd'] == "SALES"){
					//���p���z
					$hPost['Amount'] = $hParamList['Amount'];
				}
				$hPost['graphic'] = 0;
				break;
				

			/* -------------------------------------------------- */
			//
			// ���σL�����Z��
			//
			/* -------------------------------------------------- */
			//---------------
			//�L�����A����
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
				//���ID
				$hPost['AccessID'] = $hParamList['accessid'];
				//���Pass
				$hPost['AccessPass'] = $hParamList['accesspass'];
				//�I�[�_�[ID
				$hPost['OrderID'] = $hParamList['order_id'];
				//�L�����Z�����z
				$hPost['CancelAmount'] = $hParamList['Amount'];
				//�L�����Z���ő���
				$hPost['CancelTax'] = "0";
				$hPost['graphic'] = 0;
				break;

			/* -------------------------------------------------- */
			//
			// ���z�ύX
			//
			/* -------------------------------------------------- */
			//---------------
			//�N���W�b�g�J�[�h
			//---------------
			case GS_GMOPAY_CHANGETRAN:
				//SHOPID
				$hPost['ShopID'] = GS_GMOPAY_SHOPID;
				//SHOPPASS
				$hPost['ShopPass'] = GS_GMOPAY_SHOPPASS;
				//���ID
				$hPost['AccessID'] = $hParamList['accessid'];
				//���Pass
				$hPost['AccessPass'] = $hParamList['accesspass'];
				//�����敪
				$hPost['JobCd'] = 'AUTH';
				//���p���z
				$hPost['Amount'] = $hParamList['Amount'];
				//�ő���
				$hPost['Tax'] = "0";
				$hPost['graphic'] = 0;
				break;

			/* -------------------------------------------------- */
			//
			// ������
			//
			/* -------------------------------------------------- */
			//---------------
			//�L�����A����
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
				//���ID
				$hPost['AccessID'] = $hParamList['accessid'];
				//���Pass
				$hPost['AccessPass'] = $hParamList['accesspass'];
				//�I�[�_�[ID
				$hPost['OrderID'] = $hParamList['order_id'];
				//���p���z
				$hPost['Amount'] = $hParamList['Amount'];
				//�ő���
				$hPost['Tax'] = '0';
				$hPost['graphic'] = 0;
				break;
		}
		
		$sSendGmoUrl = "";
		$sSendGmoUrl = GS_GMOPAY_URL.$hParamList['gmopay_url'];
		
		$aTmp = array();
		foreach($hPost as $k => $v){
			$aTmp[] = $k."=".$this->poGDB->fEscStr($v);
			//�Z�b�V�����ɓ����
			if($_SESSION['payment_gmo'][$k] == "") $_SESSION['payment_gmo'][$k] = $v;
		}
		$sPostTxt = implode("&",$aTmp);
		
		//���M�R�}���h
		$sCmd = "echo '{$sPostTxt}' | POST {$sSendGmoUrl}\n";
		//���M
		$sRes = `{$sCmd}`;
		
		//�G���[�R�[�h�擾
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
			
			//�o�^
			//----------------------------------------------------
			//�g�����U�N�V�����X�^�[�g
			$this->poGDB-> fBegin();
			
			$hParamList['err_code'] = $hErr['code'];
			$hParamList['err_info'] = $hErr['info'];
			//tranid��accessid���Ȃ��̂Ŗ��������
			$hParamList['tranid'] = $hParamList['bill_id'].date('YmdHis');
			$hParamList['accessid'] = $hParamList['bill_id'].date('YmdHis');
			$hParamList['gmopay_url'] = GS_GMOPAY_ERROR;
			$hParamList['effect_flg'] = "false";
			$_SESSION['payment_gmo']['JobCd'] = "ERROR";
			
			//�G���[�R�[�h�o�^
			$pbRet = $this->fInsertGmoSlnKessai($hParamList);
			if(!$pbRet){
				$this->sErrMes = "fInsertGmoSlnKessai�N�G���G���[ �G���[�R�[�h�o�^";
				gfDebugLog($this->sErrMes);
				//���[���o�b�N
				$this->poGDB->fRollBack();
				return false;
			}else{
				//�R�~�b�g
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
	// �e�[�u���֓o�^
	//
	// $hParamList				:$_REQUEST
	//										+fGetBillInfo����擾���������Ɋւ�����
	// $sExecTrain	:GMO����̕Ԃ�l
	//
	//-------------------------------------------------
	function fInsertGmoSlnKessai($hParamList,$sExecTrain=""){
		
		//GMO����̕Ԃ�l�𕪉�
		if($sExecTrain!=""){
			$hExecTrain = explode("&", $sExecTrain);
			$hGmoReturn = array();
			foreach($hExecTrain as $k1 => $v1){
				$hGmoR = explode("=", $v1);
				//�������ɂ���
				$sStrK1 = "";
				$sStrV1 = "";
				$sStrK1 = strtolower($hGmoR['0']);
				$sStrV1 = $hGmoR['1'];
				
				$hGmoReturn[$sStrK1] = $sStrV1;
			}
		}
		
		//�L���E�����t���O���C��
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
				$this->sErrMes = "fInsertGmoSlnKessai�N�G���G���[".$sSql;
				$this->poGDB->fErrProcess($this->sErrMes);
				return false;
			}
		}
		
		//bill_branch_sub���擾
		$sSql = "";
		$sSql.= "SELECT MAX(bill_branch_sub) FROM table_a WHERE bill_id = ".$hParamList['bill_id'];
		$pbRet=$this->poGDB->fExecSql($sSql);
		if(!$pbRet){
			$this->sErrMes = "fSendGmoPostData �G���[�R�[�h�擾 �f�[�^�擾���s:".$sSql;
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
			$this->sErrMes = "fInsertGmoSlnKessai�N�G���G���[".$sSql;
			$this->poGDB->fErrProcess($this->sErrMes);
			return false;
		}
		
		return $pbRet;
	}


}//class �I�[
?>

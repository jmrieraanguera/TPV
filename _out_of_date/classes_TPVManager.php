<?php
require_once('apiRedsys.php');
class TPVManager {
	// 'urlpay'=>"https://www.bicinscripcions.com/CIN/V0",
	// 'sendtopay'=>"CINP00V1_InputBasket.php",
	// 'sendtonopay'=>'/CIN/V0/noTPVneeded.php',
	// 'sendtoguest'=>'/CIN/V0/guest.php',
	// The published webservice must be called 'InotificacionSIS'
	// Must have a method 'procesoNotificacionSIS',
	public function dataModel($prod=true) {
		return self::TPVData_design();
		return $prod ? self::TPVData_prod() : self::TPVData_test();
	}
	public function checkSignature($input) {
		$trz=$error=[];
		$clapi=new RedsysAPI;
		// Arrived set of data
		$trz[]=dbg::seeDetailsStrOpen($input,"Arrived Data");
		// Get the data from the input
		$s=$clapi->decodeBase64($input['Ds_MerchantParameters']);
		$trz[]="Json of Data:[{$s}]<br>";
		$p=json_decode($s,true);
		$trz[]=dbg::seeDetailsStrOpen($p,"Data recovered:");
		// Check the signature
		$pwd=$this->dataModel()['OQP']['pwd'];
		foreach($p as $key=>$v)$clapi->SetParameter($key,$v);
		$signature=$clapi->createMerchantSignature($pwd);
		if( $signature == $input['Ds_Signature'] ) {
		    $trz[]="Matching signatures [{$signature}]";
		}
		else {
			$m="NO MATCHING SIGNATURES";
		    $trz[]= "<span style='color:brown'>{$m}</span>";
		    $trz[]= "Input:[{$_POST['Ds_Signature']}]<br>";
		    $trz[]= "Calculated:[{$signature}]<br>";
			$error[]=$m;
		}
		$inputSig=$input['Ds_SignatureVersion'];
		if( $inputSig != 'HMAC_SHA256_V1') $error[]="Unknown version";
		// Return the data, the errors and the trace
		return [$p,implode("<br>",$error),implode("<br>",$trz)];
	}
	public function procesoNotificacionSIS(& $d) {
		if(0) dbg::seeDetails($d,"Input Notification:");
		$form='';$pars=$error=[];
		$trt=self::translator();
		foreach($d as $voucher=>$z) break;
		if( isset($z['toPayment']) ) {
			foreach($z['toPayment'] as $ind=>$zz) break;
			$prp=$zz['properties'];
			$data=[];
			foreach($trt as $field=>$x) {
				if( isset($prp[$field])) $data[$field]=$prp[$field];
				else if( $field == 'voucher') $data[$field]=$voucher;
				else $error[]="Input data lacks of {$field}";
			}
			$data['amount'] *= 100;
			list($form,$pars)=$this->PrepareFormForCaixa($data);
		}
		else $error[]="Lacks the to pay section";
		return [$form,$pars,$error];
	}
	function PrepareFormForCaixa($data) {
		if(0) dbg::seeDetails($data,"Data for form preparation");
		$model=$this->dataModel();
		#---------------------------------------------------------------
		# General Variables
		$pwd=$model['OQP']['pwd'];
		$destino=$model['OQP']['callto'];
		#---------------------------------------------------------------
		# Form Calculations from model
		$p=$model['Ds_Merchant_Parameters'];
		foreach(self::translator() as $key=>$field)
			$p[$field]=$data[$key];
		require_once("TESTER/apiRedsys.php");
		$miobj= new RedsysAPI;
		foreach($p as $key=>$v)$miobj->SetParameter($key,$v);
		$ver=$model['Ds_SignatureVersion'];
		$par=$miobj->createMerchantParameters();
		$signature=$miobj->createMerchantSignature($pwd);
		#---------------------------------------------------------------
		# Form to JUMP to the
		$parar=false;
		$rc=[];
		if($parar)  {
			$rc[]=dbg::seeDetailsStrOpen($p,"Parameters to send");
			$th='';
		}
		else $th="type=hidden";
		$rc[]="<html><head></head><body>";
		$rc[]="<!-- Form for going to the TPV -->";
		$rc[]="<form name=compra method=POST action='{$destino}''>";
		$rc[]="<input $th name='Ds_SignatureVersion' value='{$ver}'>";
		$rc[]="<br>";
		$rc[]="<input $th name='Ds_MerchantParameters' value='{$par}'>";
		$rc[]="<br>";
		$rc[]="<input $th name='Ds_Signature' value='{$signature}' >";
		$rc[]="<br>";
		if( $parar ) $rc[]="<button>GO</button>";
		$rc[]="</form>";
		$rc[]="</body></html>";
		if(! $parar )
			$rc[]="<script>document.forms['compra'].submit();</script>";
		return [implode("\n",$rc),$p];
	}
	private static function translator() {
		$t=[
			'transactionType'=>'Ds_Merchant_TransactionType',
			'amount'=>'Ds_Merchant_Amount',
			'voucher'=>'Ds_Merchant_Order',
			'productDescription'=>'Ds_Merchant_ProductDescription',
			'titular'=>'Ds_Merchant_Titular',
			'name'=>'Ds_Merchant_MerchantName',
			'merdata'=>'Ds_Merchant_MerchantData',
			'language'=>'Ds_Merchant_ConsumerLanguage'
		];
		return $t;
	}
	const BASE="http://www.bicinscripcions.com/payment";
	private static function TPVData_test() {
		$urlbase=self::BASE;
		$d=[
			'OQP'=>[
				'callto'=>'https://sis-t.redsys.es:25443/sis/realizarPago',
				'prod'=>'0',
				'pwd'=>'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
			],
			'Ds_Signature'=>'', #To be calculated with algorithm SHA-256
			'Ds_SignatureVersion'=>'HMAC_SHA256_V1',
			'Ds_Merchant_Parameters'=>[ #* Specific for the payment
				'Ds_Merchant_MerchantCode'=>'297361610',
				'Ds_Merchant_Terminal'=>'1',
				'Ds_Merchant_TransactionType'=>'=transactionType',
				'Ds_Merchant_Amount'=>'=amount', #*
				'Ds_Merchant_Currency'=>'978',
				'Ds_Merchant_Order'=>'=voucher', #*
				'Ds_Merchant_MerchantURL'=>$u.'/tpvback_V0.php',
				'Ds_Merchant_ProductDescription'=>'=productDescription',
				'Ds_Merchant_Titular'=>'=titular',
				'Ds_Merchant_URLOK'=>$u.'/tpvok_V0.php',
				'Ds_Merchant_URLKO'=>$u.'/tpvok_V0.php',
				'Ds_Merchant_MerchantName'=>'=name',# sftp
				'Ds_Merchant_ConsumerLanguage'=>'=language',  # sftp
				'Ds_Merchant_MerchantData'=>'=merdata'#*
			],
		];
		return $d;
	}
	private static function TPVData_prod() {
		$u=self::BASE;
		$d=[
			'OQP'=>[
				'callto'=>'https://sis.redsys.es/sis/realizarPago',
				'prod'=>'1',
				'pwd'=>'DpaW9h61FGc0oM+y0D7J6XisuJvAzJiM',
			],
			'Ds_Signature'=>'', #To be calculated with algorithm SHA-256
			'Ds_SignatureVersion'=>'HMAC_SHA256_V1',
			'Ds_Merchant_Parameters'=>[#* Specific for the payment
				'Ds_Merchant_MerchantCode'=>'297361610',
				'Ds_Merchant_Terminal'=>'1',
				'Ds_Merchant_TransactionType'=>'=transactionType',
				'Ds_Merchant_Amount'=>'=amount,0', #*
				'Ds_Merchant_Currency'=>'978',
				'Ds_Merchant_Order'=>'=voucher,0', #*
				'Ds_Merchant_MerchantURL'=>$u.'/tpvback_V0.php',
				'Ds_Merchant_ProductDescription'=>'=productDescription',#*
				'Ds_Merchant_Titular'=>'=titular',
				'Ds_Merchant_URLOK'=>$u.'/tpvok_V0.php',
				'Ds_Merchant_URLKO'=>$u.'/tpvok_V0.php',
				'Ds_Merchant_MerchantName'=>'=name',# sftp
				'Ds_Merchant_ConsumerLanguage'=>'=language',  # sftp
				'Ds_Merchant_MerchantData'=>'=merdata'#*
			],
		];
		return $d;
	}
	private static function TPVData_design() {
		$utpv="http://www.indiroom.es/INDIROOM/SIMULTPV";
		$u="http://www.indiroom.es/TPV"; //
		$d=[
			'OQP'=>[
				'callto'=>$utpv.'/makePayment',
				'prod'=>'0',
				'pwd'=>'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
			],
			'Ds_Signature'=>'', #To be calculated with algorithm SHA-256
			'Ds_SignatureVersion'=>'HMAC_SHA256_V1',
			'Ds_Merchant_Parameters'=>[#* Specific for the payment
				'Ds_Merchant_MerchantCode'=>'297361610',
				'Ds_Merchant_Terminal'=>'1',
				'Ds_Merchant_TransactionType'=>'=tipus',
				'Ds_Merchant_Amount'=>'=amount', #*
				'Ds_Merchant_Currency'=>'978',
				'Ds_Merchant_Order'=>'=voucher', #*
				'Ds_Merchant_MerchantURL'=>$u.'/tpvback_V0.php',
				'Ds_Merchant_ProductDescription'=>'=product',#*
				'Ds_Merchant_Titular'=>'BICINSCRIPCIONS',
				'Ds_Merchant_URLOK'=>$u.'/userback_V0.php',
				'Ds_Merchant_URLKO'=>$u.'/userback_V0.php',
				'Ds_Merchant_MerchantName'=>'=name',# sftp
				'Ds_Merchant_ConsumerLanguage'=>'=language', #sftp
				'Ds_Merchant_MerchantData'=>'=merdata'#*
			],
		];
		return $d;
	}
}
?>

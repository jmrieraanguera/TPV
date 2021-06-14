<?php
ini_set('display_errors','On');
ini_set('default_charset','UTF-8');
date_default_timezone_set('Europe/Madrid');
$trz=false;
require_once('../_DATA/currentSystem.php');
require_once('classes_support.php');
require_once('class_soapV1.php');
if($trz)dbg::seeDetails($_POST,'Input data');
// Data for call the webservice of shopping cart
$SYSTEM=System::memory();
// Get the dataTPV
list($data,$error)= checkSignature($_POST);
if( count($error) == 0 ) {
    $SYSTEM['answer']=$data;
    if($trz) dbg::seeDetails($SYSTEM,"System Data:");
    // Send the data to the webservice to perform the action
    $wb='www.bicinscripcions.com/WSORDER/';
    $webs="https://{$wb}WSORDER.php";
    $oqpc=new oqpsoapClient(['location'=>$webs,'uri'=>$webs]);
    $d=$oqpc->user_TPVAnswer($SYSTEM);
    $e=$oqpc->getLastErrors();
    $trace=htmlentities($oqpc->getTrz(),ENT_QUOTES,"UTF-8");
    $z=[ 'data'=>$d, 'errorSOAP'=>$e, 'action'=>'user_TPVAnswer',
        'arguments'=>$SYSTEM,'trace'=>$trace];
    if($trz) dbg::seeDetails($z,"WS answer");
    $Response= ! isset($d['no response']);
    $NeDATA= ! isset($d['error']);
    $s=[
        'Response'=>$Response ? 'OK' : 'NOK',
        'Error DATA'=>$NeDATA ? 'OK' : 'NOK',
    ];
    if($trz) dbg::seeDetails($s,"Conditions OK");
    $diagnose= ($Response && $NeDATA) ? 'OK':'NOK';
    if($diagnose == 'NOK') {
        $msg="WEBSERVICE CONNECTION ERROR";
        $msg.=isset($d['error'])? $d['error']:'';
        $d['html']['script']=$d['html']['styles']='';
        $d['html']['html']="<h3 style='color:brown'>{$msg}</h3>";
    }
}
else {
    $diagnose='NOK';
    $msg="TPV Wrong Data";
    if($trz)  dbg::seeDetails($trace,"TPV Answer analysis ");
    $d['html']['script']=$d['html']['styles']='';
    $d['html']['html']="<h3 style='color:brown'>{$msg}</h3>";
}
if($trz) echo $diagnose."<br>";
#==============================
$rc=[];
$rc[]="<!DOCTYPE html>";
$rc[]="<html><head>";
$rc[]=$d['html']['styles'];
$rc[]=$d['html']['script'];
$rc[]="</head><body>";
$rc[]=$d['html']['html'];
$rc[]="</body></html>";
#==============================
exit(implode("\n",$rc));
#===============================================================
function checkSignature($input) {
    global $trz;
    require_once('apiRedsysPHP5.php');
    $error=[];
    $clapi=new RedsysAPI;
    // Arrived set of data
    if($trz) dbg::seeDetails($input,"Arrived Data");
    // Get the data from the input
    $s=$clapi->decodeBase64($input['Ds_Merchant_Parameters']);
    if($trz) echo "Json of Data:[{$s}]<br>";
    $p=json_decode($s,true);
    if($trz) dbg::seeDetails($p,"Data recovered:");
    foreach($p as $key=>$v) $clapi->SetParameter($key,$v);
    // Check the signature
    $pwd='sq7HjrUOBfKmC576ILgskD5srU870gJ7';
    $signature=$clapi->createMerchantSignature($pwd);
    if( $signature == $input['Ds_Signature'] ) {
        if($trz) echo "Matching signatures [{$signature}]<br>";
    }
    else {
        $m="NO MATCHING SIGNATURES";
        if($trz) echo  "<span style='color:brown'>{$m}</span><br>";
        if($trz) echo "Input:[{$input['Ds_Signature']}]<br>";
        if($trz) echo "Calculated:[{$signature}]<br>";
        $error[]=$m;
    }
    $inputSig=$input['Ds_SignatureVersion'];
    if( $inputSig != 'HMAC_SHA256_V1') $error[]="Unknown version";
    // Return the data, the errors
    return [$p,$error];
}
?>

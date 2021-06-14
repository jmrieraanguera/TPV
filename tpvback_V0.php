<?php
ini_set('display_errors','On');
ini_set('default_charset','UTF-8');
date_default_timezone_set('Europe/Madrid');
$trz=[];
require_once('../_DATA/currentSystem.php');
require_once('classes_support.php');
require_once('class_soapV1.php');
$trz[]="Input data";$trz[]=$_POST;
// Data for call the webservice of order management
$SYSTEM=System::memory();
$trz[]="System data";$trz[]=$SYSTEM;
$trz[]="Sender data";$trz[]=wsMail::phpData();
// Get the dataTPV
list($data,$error,$trace)= checkSignature($_POST);
$trz=array_merge($trz,$trace);
if( count($error) == 0 ) {
    $SYSTEM['answer']=$data;
    // $SYSTEM['voucher']=$data['Ds_Merchant_Order'];
    // Get the data from the webservice
    $wb='www.bicinscripcions.com/WSORDER/';
    $webs="https://{$wb}WSORDER.php";
    $oqpc=new oqpsoapClient(['location'=>$webs,'uri'=>$webs]);
    $d=$oqpc->server_TPVAnswer($SYSTEM);
    if( isset($d['no response']) ) $d['error']= $d['no response'];
    $e=$oqpc->getLastErrors();
    if( wsMail::phpData()['sender']['verbose']) $trace=$oqpc->getTrz();
    else $trace=['VERBOSE MODE SET OFF'];
    $trz[]=$trace;
    $z=[
        'data'=>$d, 'errorSOAP'=>$e, 'action'=>'server_TPVAnswer',
        'arguments'=>$SYSTEM,'trace'=>$trz ];
    $diagnose=(!isset($d['error']) && $e=='') ? 'OK':'NOK';
}
else {
    $diagnose='NOK';
    $trz[]="NO WS called";
    $z=[
        'error'=>$error,
        'errorSOAP'=>'', 'action'=>$action,
        'arguments'=>$SYSTEM,
        'trace'=>$trz,
    ];
}
if( false || $diagnose == 'NOK' ) sendErrorMail($z);
exit($diagnose);
#=======================================================================
function sendErrorMail($z) {
    $ts=dbg::seeD($z,"Trace",0,10);
    // Send an email
    // $tone=[];
    // foreach($z as $i=>$tt) $tone[]=compactToString($tt);
    // $ts=implode("<br><br>",$tone);
    $body="
        <!DOCTYPE html>
        <html>
            <head>
                <style>
                    .one {
                        font-family: Verdana, Arial,
                            Helvetica, sans-serif;
                        font-size:12px;
                        color:DarkBlue;
                    }
                    h1.one {
                        font-size:16px;
                    }
                    .two {
                        width:250px;
                        margin:16px;
                    }
                </style>
            </head>
            <body  >
                <img src='cid:LogoBNV.png' class=two>
                <h1 class=one>TPV BACK ANSWER</h1>
                <p><b>From tpvback_V0.php</b></p>
                <div class=one>{$ts}</div>
            </body>
        </html>
    ";
    $sender=new wsMail;
    $sender->send($body);
}
function compactToString($x) {
    // $x is anything (either array or not) to be transformed to string
    $xout='';
    if( is_array($x) )
        foreach($x as $xx) $xout.= compactToString($xx);
    else $xout=$x;
    return $xout;
}
// They are classes for export into the applications in order to
// call the webservice
class wsImage {
    const OUT = "46.27.234.70";
    const IN  = "192.168.0.248";
    protected $soap=null;
    protected function prpDefault() {
		$d=[
            'trz'=>false,'errors'=>false, 'returned'=>false,'ws'=>false,
        ];
		return $d;
	}
    protected $trz=["<p>No trace activated</p>"];
	protected $prp;
    protected function getWS() {
        if($_SERVER['SERVER_NAME'] == self::IN ) $where=self::IN;
        else $where=self::OUT;
        return $ws='http://'.$where."/WSINDEX.php";
    }
    public function __construct($par=[]) {
        $ws=$this->getWS();
        $this->prp=gnrl::MergeProperties($this->prpDefault(), $par);
        if(0) dbg::seeDetails($ws,"URL Web service:");
        $this->soap=new oqpsoapClient(['location'=>$ws,'uri'=>$ws]);
        if(0) dbg::seeDetails($this->soap,"SOAP Class:");
    }
    public function trace() { return $this->trz;}
    public function filterResult($action,$arguments,$x) {
        $e=$this->soap->getLastErrors();
        if($this->prp['errors']) dbg::seeDetails($e,"Errors:");
		if($this->prp['trz']){
            $t=$this->soap->getTrz();
            $this->trz=htmlentities($t,ENT_QUOTES,"UTF-8");
        }
		if($this->prp['returned'])dbg::seeDetails($x,"Return fromn WS");
		if( isset($x['no response']) )
			$x="<h3 style='color:brown;'>NO WS RESPONSE</h3>";
        return [ 'data'=>$x, 'errorSOAP'=>$e, 'action'=>$action,
			'arguments'=>$arguments,'trace'=>$this->trz];
    }
}
class wsMail extends wsImage {
    public static function phpData() {
        $memory=[
            'sender'=>[
    			# Properties for setting the behaviour of mailer
    			// 'notifyerror'=>[], # Notificar un error
                'simulation'=>false, //
    			'verbose'=>true, // if true the operations will be traced
    			# Properties of OQP
    			# $mode=0; mail to current people;
    			# $mode=1; mail only to testmail
    			# $mode=2; mail both current people and testmail
    			# $mode=3; mail both current people and testmail (hidden copy)
    			'sendmode'=>0,
    			'testmail'=>"jmriera@oqpbcn.com",
    			# properties for phpmailer
         		# MTP class debug output mode.
         		# Debug output level.
    		    # Options:
    		    # 0 No output
    		    # 1 Commands
    		    # 2 Data and commands
    		    # 3 As 2 plus connection status
    		    # 4 Low-level data output
    			'SMTPDebug'	=>0,
    			'Debugoutput'=>'html',

    			# properties for sending e-mails via smtp
    			'SMTPMail'	=> true, # if false use php mail function
    			'SMTPAuth' 	=> true,
                // 'Host'     	=> "217.116.0.228",
		        // 'Port'		=> 25,
    			'Host'     	=> "smtp.servidor-correo.net",
    			'Port'		=> 587,
    			'Username' 	=> 'administracio@bicinscripcions.com',
    			'Password' 	=> '58305830Oqp',

    			# Data of sender
        		'from'=>'administracio@bicinscripcions.com',
        		'namefrom'=>"OWN TPV",
        		'replay'=>'administracio@bicinscripcions.com',
        		'namereplay'=>"OWN TPV",
    		],
            'emails'=>[
                [
                    // Data for one e-mail
        			'to'=>['signuperror@bicinscripcions.com'],
        			'cc'=>[],
        			'bcc'=>[],
                    'subject'=>'Trace of TPV',
                    'bodyIsHTML'=>true,
                    'wordwrap'=>80,
                    'CustomH'=>[], // Custom headers for email
        			'body'=>'',
        			'images'=>['/CCT/V0/images/LogoBNV.png'],
        			'attached'=>[],
        		],
            ],
		];
        return $memory;
    }
    protected function getWS() {
        $wb='https://www.bicinscripcions.com/WSMAILSIMPLE/';
        $sc="WSMAIL.php";
        return $wb.$sc;
    }
    public function call($action,$arguments) {
        $x=$this->soap->$action($arguments);
		return $this->filterResult($action,$arguments,$x);
    }
    public function send($body) {
        $arguments=self::phpData();
        $arguments['emails'][0]['body']=$body;
        if(0) dbg::seeDetails($arguments,"Sent data:");
        $x=$this->soap->send($arguments);
        if(0) dbg::seeDetails($x,"Returned data");
    }
}
#===============================================================
function checkSignature($input) {
    $trz=[];
    require_once('apiRedsysPHP5.php');
    $error=[];
    $clapi=new RedsysAPI;
    // Arrived set of data
    $trz[]=dbg::seeD($input,"Input Data for checking:");
    // Get the data from the input
    $s=$clapi->decodeBase64($input['Ds_Merchant_Parameters']);
    $trz[]="Json of Data:[{$s}]<br>";
    $p=json_decode($s,true);
    $trz[]=dbg::seeD($p,"Data recovered from the message:");
    foreach($p as $key=>$v) $clapi->SetParameter($key,$v);
    // Check the signature
    $pwd='sq7HjrUOBfKmC576ILgskD5srU870gJ7';
    $signature=$clapi->createMerchantSignature($pwd);
    if( $signature == $input['Ds_Signature'] ) {
        $trz[]="Matching signatures [{$signature}]<br>";
    }
    else {
        $m="NO MATCHING SIGNATURES";
        $trz[]="<span style='color:brown'>{$m}</span><br>";
        $trz[]="Input:[{$input['Ds_Signature']}]<br>";
        $trz[]="Calculated:[{$signature}]<br>";
        $error[]=$m;
    }
    $inputSig=$input['Ds_SignatureVersion'];
    if( $inputSig != 'HMAC_SHA256_V1') $error[]="Unknown version";
    // Return the data, the errors
    return [$p,$error,$trz];
}
?>

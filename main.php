<?php
require_once('class_soapV1.php');
require_once('classes_support.php');
require_once("apiRedsysPHP5.php");
require_once("classes_DBMV1__abstract.php");
function helpAndInputDataFormatAndMeaning() {
    // This script receives data from a caller for asking
    // to the TPV server as external as internal
    // if it's a right question about the operation to perform
    // This step is mandatory for external TPV
    // but the internal fits the external one
    // in order to have a standard system to perform operations.
    // The format of input data is simple
    $t=[
        // It serves to branch to the proper server,
        // (the internal one or the one of external production
        // or the one of external test)
        // The allowed values are
        // [0,3,80,81,82,70,71,72,60,61,62,90,91,92]
        'typePayment',
        // It serves to signal the kind of operation to perform:
        //  0 is a external buy, 3 is a money return,
        //  8 is delayed payment, 7 is a payment,
        //  6 is a internal return, 9 is for a guest
        'transactionType',
        // The amount to be transferred in cents of euro
        // (euros divided by 100, it's always integer)
        'amount',
        // The order involved in the operation
        'voucher'=>'',
        // The product description of the product involved
        'productDescription',
        // The code of company who is making the operation
        'titular',
        // The company who is making the operation
        'name',
        // A string data about other data interesting
        // for the operation and for the merchant
        'merdata',
        // The language to use for the communications
        'language'=>''
    ];
    return $t;
}
class main {
    const TEST = false;
    protected $browsertype;
    protected $title='Payment';
    public function execute() {
        $trz=false;
        if($trz) {
            $m="THE FORMER STEP TO CALL EXTERNAL(OR OWN) TPV";
            $s="style='color:brown; font-weight:bold;'";
            echo "<h3 {$s}>{$m}</h3>";
        }
        foreach($_GET as $key=>$v) $_POST[$key]=$v;
        if($trz) dbg::seeDetails($_POST,"Arrived data:");
        // Collect the sent data
        $data=[];
        foreach($this->translator() as $k=>$kp) $data[$kp]=$_POST[$k];
        $s="TP".$_POST['typePayment']."-".$_POST['merdata'];
        $data['Ds_Merchant_MerchantData']=$s;
        if($trz) dbg::seeDetails($data,"Transformed data:");
        // To send to the proper TPV server
        switch($_POST['typePayment']) {
            case 0: case 3: // call the external TPV
                if( self::TEST )    TESTPVManager::gotoTPV($data);
                else                TPVManager::gotoTPV($data);
                break;
            case 60: case 70: case 80: case 90:
                $t=floor($_POST['typePayment']/10);
                $cl=new OWNManager;
                $cl->gotoTPV($data,$t);
                break;
            case 61: case 71: case 81: case 91:
                $t=floor($_POST['typePayment']/10);
                $cl=new OWNManager;
                $cl->gotoTPVServerTest($data,$t);
                break;
            case 62: case 72: case 82: case 92:
                $t=floor($_POST['typePayment']/10);
                $cl=new OWNManager;
                $cl->gotoTPVUserTest($data,$t);
                break;
            default:
                $cl=new OWNManager;
                $cl->gotoTPV($data,5);
                break;
        }
        $rc='There is NO RETURN from that call';
        return $rc;
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
}
//-------------------------- Internal TPV
class OWNManager {
    const BASE="https://www.bicinscripcions.com/TPV";
    const PWD='sq7HjrUOBfKmC576ILgskD5srU870gJ7';
    const VERSION='HMAC_SHA256_V1';
    const SERVER=self::BASE.'/tpvback_V0.php';
    const USER='userback_V0.php';
    private function getAuthorization($typePayment) {
        $count=[6=>"Returns",7=>'#Payments',8=>'#Sale',9=>'#Guest'];
        $name=$count[$typePayment];
        $root=$_SERVER['DOCUMENT_ROOT'];
		require_once($root.'/_DATA/DBConnectors.php');
		$db=DBConnectors::getDB('indiroom_1');
        $cldb=new pararray_V1(
            $name,
            'counterFormat',
            [
                'bdconnector'=>[
    				"host"=>$db['host'],
    				'db'=>$db['db'],
    				'usr'=>$db['usr'],
    				'user'=>$db['usr'],
    				'pwd'=>$db['pwd'],
    			],
                'bdstruct'=>[
    				'counterFormat'=>[
    					'name'=>$name,
    					'initial'=>$typePayment*1000000,
    					'format'=>'%d',
    					'pattern'=>"/^[{$typePayment}][0-9]{6,6}$/",
    				],
    			],
            ]
        );
        return $authorisation=$cldb->getNextCounter();
    }
    private function answerModel($data,$typePayment) {
        $now=new DateTime();
        $date=$now->format("d-m-Y");
        $time=$now->format("H:i");
        $response='0180';
        if( in_array($typePayment,[5,50,51,52]) )// To force an error
            ; // Force a bad response
        else if(in_array($data['Ds_Merchant_TransactionType'],[7,8,9]))
            $response='0000';
        else if(in_array($data['Ds_Merchant_TransactionType'],[6]))
            $response='0900';
        // ------------------ $authorization
        $authorization='';
        if($response != '0180' )
            $authorization=$this->getAuthorization($typePayment);
        $answer=[
            'Ds_Terminal'=>'001',
            'Ds_Response'=>$response,
            'Ds_Amount'=>$data['Ds_Merchant_Amount'],
            'Ds_SecurePayment'=>0,
            'Ds_Card_Type'=>'X',
            'Ds_Card_Country'=>'NO',
            'Ds_Card_Brand'=>'X',
            'Ds_ProcessedPayMethod'=>5,
            'Ds_ConsumerLanguage'=>$data['Ds_Merchant_ConsumerLanguage'],
            'Ds_Date'=>$date,
            'Ds_MerchantData'=>$data['Ds_Merchant_MerchantData'],
            'Ds_Order'=>$data['Ds_Merchant_Order'],
            'Ds_Merchant_Order'=>$data['Ds_Merchant_Order'],
            'Ds_MerchantCode'=>297361610,
            'Ds_TransactionType'=>$data['Ds_Merchant_TransactionType'],
            'Ds_Hour'=>$time,
            'Ds_AuthorisationCode'=>$authorization,
            'Ds_Currency'=>978,
        ];
        return $answer;
    }
    public function gotoTPVServerTest($data,$TP) {
        $answer=$this->answerModel($data,$TP);
        $post=setForm($answer,self::PWD);
        exit(gnrl::pageJump(self::SERVER,$post,true));
    }
    public function gotoTPVUserTest($data,$TP) {
        $answer=$this->answerModel($data,$TP);
        $post=setForm($answer,self::PWD);
        exit(gnrl::pageJump(self::USER,$post,true));
    }
    public function gotoTPV($data,$TP) {
        $send=false;$t=[];
        $t[]=dbg::seeD($data,"Input data",0,5);
        $t[]="Type of Payment={$TP}";
        // Build the answer from the request
        $answer=$this->answerModel($data,$TP);
        $post=setForm($answer,self::PWD);
        $t[]="POST:"; foreach($post as $key=>$p) $t[]=$key."=>".$p;
        // Call the TPV SERVER
        $CURL=curl_init();
        curl_setopt($CURL,CURLOPT_URL,self::SERVER);
        curl_setopt($CURL,CURLOPT_HEADER,false);
        curl_setopt($CURL,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($CURL,CURLOPT_TIMEOUT,10);
        curl_setopt($CURL,CURLOPT_POST,true);
        curl_setopt($CURL,CURLOPT_POSTFIELDS,$post);
        $response=curl_exec($CURL);
        if( $response === false ){
            $send=true;
            $t[]="No response";
            $t[]=dbg::seeDetails(curl_getinfo($CURL),"Curl INFO;");
        }
        else if( $e=curl_errno($CURL) ) {
            $send=true;
            // There is an error
            $t[]= curl_error($CURL);
        }
        else $t[]="Response:".$response;
        curl_close($CURL);
        // Call the TPV USER
        $t[]="Send to ".self::USER;
        $this->SendMail($send,$t);
        exit(gnrl::pageJump(self::USER,$post,false));
    }
    private function SendMail($send,$t) {
        if(! $send ) return ;
        // Send an email
        $tone=[];
        foreach($t as $i=>$tt) $tone[]=$this->compactToString($tt);
        $ts=implode("<br><br>",$tone);
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
                    <h1 class=one>OWN TPV ANSWER</h1>
                    <div class=one>
                        $ts;
                    </div>
                </body>
            </html>
        ";
        $sender=new wsMail;
        $sender->send($body);
    }
    private function compactToString($x) {
		// $x is anything (either array or not) to be transformed to string
		$xout='';
		if( is_array($x) )
			foreach($x as $xx) $xout.= $this->compactToString($xx);
		else $xout=$x;
		return $xout;
	}
}
//-------------------------- External TPV
class TESTPVManager {
    const BASE="https://www.bicinscripcions.com/TPV";
    const CALLTO='https://sis-t.redsys.es:25443/sis/realizarPago';
    const PWD='sq7HjrUOBfKmC576ILgskD5srU870gJ7';
	private static function TPVData() {
		$u=self::BASE;
		$d=[
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
			'Ds_Merchant_MerchantData'=>'=merdata',#*
		];
		return $d;
	}
    public static function gotoTPV($data) {
        // Past the current data to the model
        $d=self::TPVData();
        foreach($data as $key=>$v) $d[$key]=$v;
		# Form Calculations from model
		$post=setForm($d,self::PWD);
        exit(gnrl::pageJump(self::callTO,$post,true));
    }
}
class TPVManager {
    const BASE="https://www.bicinscripcions.com/TPV";
    const CALLTO='https://sis-t.redsys.es:25443/sis/realizarPago';
    const PWD='sq7HjrUOBfKmC576ILgskD5srU870gJ7';
    private static function TPVData() {
        $u=self::BASE;
        $d=[
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
        ];
        return $d;
    }
    public function gotoTPV($data) {
        // Past the current data to the model
        $d=$this->TPVData();
        foreach($data as $key=>$v) $d[$key]=$v;
		$post=setForm($d,self::PWD);
        exit(gnrl::pageJump(self::callTO,$post,true));
    }
}
function setForm($data,$pwd) {
    $miobj= new RedsysAPI;
    foreach($data as $key=>$v) $miobj->SetParameter($key,$v);
    $par=$miobj->createMerchantParameters();
    $signature=$miobj->createMerchantSignature($pwd);
    $post=[
        'Ds_SignatureVersion'=>'HMAC_SHA256_V1',
        'Ds_Signature'=>$signature,
        'Ds_Merchant_Parameters'=>$par,
    ];
    return $post;
}
#==========================================
# To send an email when there is an error (or when we force it)
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

?>

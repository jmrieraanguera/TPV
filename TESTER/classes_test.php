<?php
trait question {
	// To allow several kinds of results from a class
	public function type() { return 'WS';}
	// To define what block must be openend and updated
	public function showAfter() { return get_class();}
	// to put the title in the list
	public function title_base() {return ["NO TITLE",'red'];}
	// to put the left part of the question in order to be triggered
	public function html($i) {
		$hh=self::fM([
			'name'=>'','#'=>$i,
			["No HTML function",'','?'=>'plain'],
		]);
		return [$hh['html'],$hh['styles'],$hh['script']];
	}
	// to execute the action which is associated to the question
	public function call() {return main::HTML;}
	// to put the right part of the question
	public function response() {
		$p ="<div style='width:100%; text-align:center;'>";
		$p.="<h3>NO RESPONSE FUNCTION</h3>";
		$p.="</div>";
		return [
			'data'=>[
				'html'=>[
					'html'=>$p,
					'styles'=>'',
					'script'=>'',
				]
			],
			'trace'=>'NO TRACE AVAILABLE',
			'action'=>'',
			'arguments'=>[],
		];
	}
	// to store data
	private $mem,$dub;
	public function setMEM($what) { return $this->mem=$what;}
	public function __construct($name='') {
		$this->dub=$name ? $name : get_class();
	}
}
class noQuestion extends blockform {
	use question;
	const MSG="NO SUBMITTED QUESTION";
	public function showAfter() {
		$open=['keepBuying'=>'addToSC'];
		$x=get_class();
		if( isset($_POST['what']) )
			if ( array_key_exists($_POST['what'],$open) )
				$x=$open[$_POST['what']];
		if(0) dbg::seeDetails($_POST,"What:{$x}:");
		return $x;
	}
	public function display(){
		return $this->showAfter() == get_class();
	}
	public function title_base() {return [self::MSG,'red'];}
	public function type() { return 'NOWS';}
	public function response() {
		$p ="<div style='width:100%; text-align:center;'>";
		$p.="<h3>".self::MSG."</h3>";
		$p.="</div>";
		return [
			'data'=>[
				'html'=>[
					'html'=>$p,
					'styles'=>'',
					'script'=>'',
				]
			],
			'trace'=>'NO TRACE AVAILABLE',
			'action'=>'',
			'arguments'=>[],
		];
	}
	public function html($i) {
		$hh=self::fM([
			'name'=>'','#'=>$i,
			['No question','','?'=>'plain'],
		]);
		return [$hh['html'],$hh['styles'],$hh['script']];
	}
}
class noImplemented extends blockform {
	use question;
	private $dub;
	public function __construct($name) {
		$this->dub=$name;
	}
	protected function title_base() {
		return ["Not found class",'red'];
	}
	public function type() { return 'NOWS';}
	public function html() {
		$html="NOT IMPLEMENTED YET";
		return [$html,'',''];
	}
	public function response() {
		$p="NOT IMPLEMENTED question {$this->dub}";
		return [
			'data'=>[
				'html'=>[
					'html'=>$p,
					'styles'=>'',
					'script'=>'',
				]
			],
			'trace'=>'NO TRACE AVAILABLE',
			'action'=>$this->dub,
			'arguments'=>[],
		];
	}
}
/*
class mirror extends blockForm {
	use question;
	protected function title_base() {
		return ["To check the communications",'green'];
	}
	public function html($i){
		$h='';
		if(isset($this->r) ) {
			$h=dbg::seeDetails($this->r,"Returned:",true);
			$st="style='border:2px solid darkblue; text-align:left;'";
			$h="<div {$st}>{$h}</div>";
		}
		$name=get_class();
		// This is the data send
		$testr=
			"types=facypagoprov
			ejercicio=19
			fecha=25-04-2019
			concepto=sfra 190834
			facpro=10704
			entidad=JDA
			total=53,18
			cuentagasto=601
			bi=43,95
			iva=9,23
			cuentapago=57203";
		$test=str_replace("\t","",$testr);
		$hh=self::fM([
			'name'=>get_class(),'#'=>$i,
			["List of the properties to be sent",$test,'?'=>'textarea'],
			['?'=>'button',1=>'mirror'],
			[$h,'','?'=>'plain'],
		]);
		return [$hh['html'],$hh['styles'],$hh['script']];
	}
	public function call($argraw) {
		$arguments=format::fromKeyboardToArray($argraw[0]);
		$this->mem=webPlan::classCall(get_class(),$arguments);
		return main::HTML;
	}
	public function response() { return $this->mem;}
}
class help extends blockForm {
	use question;
	public function title_base() {
		return [ "WSDL of the webservice and other help",'green'];
	}
	public function html($i) {
		$hh=self::fM([
			'name'=>get_class(),'#'=>$i,
			['?'=>'button',1=>get_class()],
			["To get the help from the webservice",'','?'=>'plain'],
		]);
		return [$hh['html'],$hh['styles'],$hh['script']];
	}
	public function call($arguments) {
		$this->mem=webPlan::classCall(get_class(),$arguments);
		return main::HTML;
	}
	public function response() { return $this->mem; }
}
*/
class sendToTPVSender extends blockForm {
	use question;
	// public function showAfter() { return 'wholeSC'; }
	protected function title_base() {
		$s="To simulate the question sending on our TPV server
			for the shopping cart [99999999]";
		return [$s,'green'];
	}
	public function html($i){
		$type=$_SESSION['type'];
		$whom=$_SESSION['whom'];
		$reply=models::model($type);
		$hh=self::fM([
			['It prepares the form for a send','','?'=>'plain'],
			['WHOM TO SEND:',$whom,'?'=>'select',
				['options'=>[
					0=>'BOTH',1=>'SERVER',2=>'USER',
				]]
			],
			'name'=>get_class(),'#'=>$i,
			['Type',$type,'?'=>'select',
				['options'=>[
					0=>'TPVBuy',3=>'TPVReturn',
					8=>'OWNBuy',7=>'OWNPay',6=>'OWNReturn',
					9=>'OWNGuest',
					5=>'BAD',
				]]
			],
			['?'=>'button',1=>'sendToTPVSender'],
		]);
		return [$hh['html'],$hh['styles'],$hh['script']];
	}
	public function call($arguments) {
		global $clsession;
		$clsession->updateSession([
			'whom'=>$arguments[0],
			'type'=>$arguments[1],
		]);
		$this->mem=webPlan::classCall('buildForm',$_SESSION);
		return main::HTML;
	}
	public function response() {
		return $this->mem;
	}
}
class seeOrder extends blockForm  {
	use question;
	// public function showAfter() { return 'wholeSC'; }
	protected function title_base() {
		$s="To see the status of the Shopping Cart [99999999]";
		return [$s,'green'];
	}
	public function html($i){
		$hh=self::fM([
			'name'=>get_class(),'#'=>$i,
			['?'=>'button',1=>'seeOrder'],
		]);
		return [$hh['html'],$hh['styles'],$hh['script']];
	}
	public function call($arguments) {
		$rc=webPlan::classCall('seeOrder',[]);
		$this->mem=$rc['data'];
		return main::HTML;
	}
	public function response() {
		return $this->mem;
	}

}
class setTestSC extends blockForm {
	use question;
	public function type() { return 'NO-WS';}
	public function title_base() {
		$s="To create a shopping cart [99999999] to perform the tests";
		return [$s,'green'];
	}
	public function html($i){
		global $cldata,$clsession;
		$name=get_class();
		$hh=self::fM([
			'name'=>get_class(), '#'=>$i,
			['?'=>'button',1=>get_class()],
		]);
		return [$hh['html'],$hh['styles'],$hh['script']];
	}
	public function call($arguments) {
		global $clsession;
		$this->mem=webPlan::classCall(get_class(),$this->modelSC());
		return main::HTML;
	}
	public function response() {
		$s=$this->modelSC();
		if( isset($this->mem['data']) ){
			$this->mem['data']['sent']=$s;
			$h=$this->mem;
		}
		else{
			$ss=
			$h=[
				'data'=>$s,
				'trace'=>'NO TRACE AVAILABLE',
				'action'=>'',
				'arguments'=>[],
			];
		}
		return $h;
	}
	// "where voucher =10000006 and type='fringe'";
	public function modelSC() {
		$rc=[
			'99999999'=>[
				'fringe'=>[
					1=>$this->recFringe1(),
					2=>$this->recFringe2(),
				],
				'buyer'=>[
					1=>$this->recBuyer(),
				],
				'totals'=>[
					1=>$this->recTotals(),
				],
				'toPayment'=>[
					1=>$this->recToPayment(),
				],
			]

		];
		return $rc;
	}
	private static function dataModelFringe() {
		$r=[
			// Voucher If cero it must be opened //
			// 'voucher'=>'voucher',
			// The producte which the line refers to
			'idProduct'=>"T0",
			// Timestamp for the beginning of the rent
			'from'=>"F0",
			// Timestamp for the ending of the rent
			'to'=>'F1',
			// Amount of Hours being rented
			'hours'=>'D9',
			// Euros for hour to rent
			'priceHour'=>'D8',
			// Total of cost in euros
			'price'=>'D4',
			// Absolute dct to be applied in that fringe
			'discountAbs'=>'D7',
			// % dct to be applied in that fringe
			'discountPct'=>'D6',
			// Total of Discount
			'discount'=>'D3',
			// Total to be applied to the taxes
			'taxBase'=>'D2',
			// Rate of taxes to be applied
			'taxRate'=>'D5',
			// Total amount of taxes
			'VAT'=>"D1",
			// Total amount to pay for
			'Total'=>'D0'
		];
		return $r;
	}
	private function fringeRecord($ind,$x,$y) {
		$di=datetime::createFromFormat('Y-m-d H:i:s',$y['from']);
		$df=datetime::createFromFormat('Y-m-d H:i:s',$y['to']);
		$in=$df->diff($di);
		$h =$in->format('%h')+$in->format('%i')/60;
		$h+=$in->format('%s')/3600;
		$y['hours']=$h;
		$p=$y['price']=round($y['hours']*$y['priceHour'],2);
		$y['discount']  = $y['discountAbs'];
		$y['discount'] += round($p*$y['discountPct'],2);
		if( $y['discount'] < 0 ) $y['discount'];
		$y['taxBase']=round($y['price']-$y['discount'],2);
		$y['VAT']=round($y['taxBase']*$y['taxRate'],2);
		$y['Total']=round($y['taxBase']+$y['VAT'],2);
		$record=[];
		$record['voucher']=$y['voucher'];
		$record['type']='fringe';
		$record['ind']=$ind;
		$record['userdata']=$x; // Image of the data entered
		$record['properties']=$y; // Image after compilation
		foreach(self::dataModelFringe() as $field=>$dbfield)
			if($dbfield) $record[$dbfield]=$y[$field];
		$prp=json_encode($record['properties']);
		$record['properties']=$prp;
		if( count($x) ) {
			$ud=json_encode($record['userdata']);
			$record['userdata']=$ud;
		}
		else $record['userdata']="";
		return $record;
	}
	private function recFringe1() {
		$x=[
			"idProduct"=>"P0050",
			"from"=>"15-05-2021 11:30:00",
			"to"=>"15-05-2021 12:43:00",
			"hours"=>"",
			"priceHour"=>"15",
			"price"=>"",
			"discountAbs"=>"0",
			"discountPct"=>"0.1",
			"discount"=>"",
			"taxBase"=>"",
			"taxRate"=>"0.21",
			"VAT"=>"",
			"Total"=>"",
			"voucher"=>"99999999"
		];
		$y=[
			"voucher"=>99999999,
			"idProduct"=>"P0050",
			"from"=>"2021-05-15 11:30:00",
			"to"=>"2021-05-15 12:43:00",
			"priceHour"=>15,
			"discountAbs"=>0,
			"discountPct"=>0.1,
			"taxRate"=>0.21,
			"hours"=>1.2166666666667,
			"price"=>18.25,
			"discount"=>1.83,
			"taxBase"=>16.42,
			"VAT"=>3.45,
			"Total"=>19.87
		];
		return $this->fringeRecord(1,$x,$y);
	}
	private function recFringe2() {
		$x=[
			"idProduct"=>"P0010",
			"from"=>"30-05-2021 16:00:00",
			"to"=>"30-05-2021 18:15:00",
			"hours"=>"",
			"priceHour"=>"20",
			"price"=>"",
			"discountAbs"=>"2",
			"discountPct"=>"0.1",
			"discount"=>"",
			"taxBase"=>"",
			"taxRate"=>"0.21",
			"VAT"=>"",
			"Total"=>"",
			"voucher"=>"99999999"
		];
		$y=[
			"voucher"=>99999999,
			"idProduct"=>"P0010",
			"from"=>"2021-05-30 16:00:00",
			"to"=>"2021-05-30 18:15:00",
			"priceHour"=>20,
			"discountAbs"=>2,
			"discountPct"=>0.1,
			"taxRate"=>0.21,
			"hours"=>2.25,
			"price"=>45,
			"discount"=>6.5,
			"taxBase"=>38.5,
			"VAT"=>8.09,
			"Total"=>46.59
		];
		return $this->fringeRecord(2,$x,$y);
	}
	private static function dataModelBuyer() {
		$r=[
			// If need factura is 1 otherwise 0
			'invoice'=>'I0',
			// The fiscal name
			'fiscalName'=>'T1',
			// The Fiscal Id
			'cif'=>'T0',
			// The Adress
			'address'=>'T2',
			'zip'=>'T3',
			'city'=>'T4',
			'province'=>'T5',
			'country'=>'T6',
			'tels'=>'T7',
			'mails'=>'T8',
			// To transmit notes with the shopping cart
			'notes'=>'T9',
		];
		return $r;
	}
	private function recBuyer() {
		$x=[
			"voucher"=>"99999999",
			"invoice"=>false,
			"fiscalName"=>"Josep Maria Riera",
			"cif"=>"",
			"address"=>"Avgda. Mare de Deu de Montserrat, 218",
			"zip"=>"08041",
			"city"=>"Barcelona",
			"province"=>"Barcelona",
			"country"=>"Spain",
			"tels"=>"607420609",
			"mails"=>"jmriera@oqpbcn.com",
			"notes"=>"Some notes"
		];
		$y=[
			"voucher"=>99999999,
			"invoice"=>false,
			"fiscalName"=>"Josep Maria Riera",
			"cif"=>"",
			"address"=>"Avgda. Mare de Deu de Montserrat, 218",
			"zip"=>"08041",
			"city"=>"Barcelona",
			"province"=>"Barcelona",
			"country"=>"Spain",
			"tels"=>"607420609",
			"mails"=>"jmriera@oqpbcn.com",
			"notes"=>"Some notes"
		];
		$record=[];
		$record['voucher']=$y['voucher'];
		$record['type']='buyer';
		$record['ind']=1;
		$record['userdata']=$x; // Image of the data entered
		$record['properties']=$y; // Image after
		foreach(self::dataModelBuyer() as $field=>$dbfield)
			if($dbfield) $record[$dbfield]=$y[$field];
		$prp=json_encode($record['properties']);
		$record['properties']=$prp;
		if( count($x) ) {
			$ud=json_encode($record['userdata']);
			$record['userdata']=$ud;
		}
		else $record['userdata']="";
		return $record;
	}
	public static function dataModelTotals() {
		$r=[
			// The total price adding each fringe
			'price'=>'D4',
			// Total of Discount
			'discount'=>'D3',
			// Total to be applied to the taxes
			'taxBase'=>'D2',
			// Total amount of taxes
			'VAT'=>"D1",
			// Total amount to pay for
			'Total'=>'D0',
			// hours
			'hours'=>'D9',
			// Tax rates used
			'taxRate'=>'T0',
			// highest date
			'highestDate'=>'F0',
			// Lowest date
			'lowestDate'=>'F1',
		];
		return $r;
	}
	private function recTotals() {
		$x=[];
		$y=[
			"voucher"=>99999999,
			"price"=>63.25,
			"discount"=>8.33,
			"taxBase"=>54.92,
			"VAT"=>11.54,
			"Total"=>66.46,
			"hours"=>3.4666666666667,
			"taxRate"=>"21%",
			"highestDate"=>"2021-05-30 18:15:00",
			"lowestDate"=>"2021-05-15 11:30:00",
		];
		$record=[];
		$record['voucher']=$y['voucher'];
		$record['type']='totals';
		$record['ind']=1;
		$record['userdata']=[]; // Image of the data entered
		$record['properties']=$y; // Image after
		foreach(self::dataModelTotals() as $field=>$dbfield)
			if ( $dbfield ) $record[$dbfield]=$y[$field];
		$prp=json_encode($record['properties']);
		$record['properties']=$prp;
		if( count($x) ) {
			$ud=json_encode($record['userdata']);
			$record['userdata']=$ud;
		}
		else $record['userdata']="";
		return $record;
	}
	public static function dataModelToPayment() {
		$r=[
			'typePayment'=>['I0',1], // 0=>WITHOUT, 1=>TPV, 2=>OTHER
			// (0=>ask for paymenrt, 3 for paying it back)
			'transactionType'=>['I1',0],
			// The total amount to pay for
			'amount'=>'D0',
			// The description of the sold product
			'productDescription'=>['T0','Alquiler Salas'],
			// The name of the owner of the commerce
			'titular'=>['T1','Indiroom'],
			// The name of merchant
			'name'=>['T2','INDIROOM'],
			// Special data for the purchase
			'merdata'=>['T3',"X23"],
			// The language of the user in terms of TPV
			'language'=>'I2',
		];
		return $r;
	}
	private function recToPayment() {
		$x=[];
		$y=[
			"voucher"=>99999999,
			"typePayment"=>1,
			"transactionType"=>0,
			"amount"=>66.46,
			"productDescription"=>"Alquiler Salas",
			"titular"=>"Indiroom",
			"name"=>"INDIROOM",
			"merdata"=>"JOSEP MAR",
			"language"=>"001"
		];
		$record['voucher']=$y['voucher'];
		$record['type']='toPayment';
		$record['ind']=1;
		$record['userdata']=[]; // Image of the data entered
		$record['properties']=$y; // Image after
		foreach(self::dataModelToPayment() as $field=>$dbfield) {
			if( is_array($dbfield) ) $dbfield=$dbfield[0];
			if ( $dbfield ) $record[$dbfield]=$y[$field];
		}
		$prp=json_encode($record['properties']);
		$record['properties']=$prp;
		if( count($x) ) {
			$ud=json_encode($record['userdata']);
			$record['userdata']=$ud;
		}
		else $record['userdata']="";
		return $record;
	}
}
class eraseTestSC extends blockForm {
	use question;
	public function title_base() {
		$s="To erase a shopping cart [99999999] ";
		return [$s,'green'];
	}
	public function html($i){
		global $cldata,$clsession;
		$name=get_class();
		$hh=self::fM([
			'name'=>get_class(), '#'=>$i,
			['?'=>'button',1=>get_class()],
		]);
		return [$hh['html'],$hh['styles'],$hh['script']];
	}
	public function call($arguments) {
		global $clsession;
		$this->mem=webPlan::classCall(get_class(),[]);
		return main::HTML;
	}
	public function response() {
		return $this->mem;
	}
}
#=======================================================================
# Class attending the questions. Remember this is not a web service
class tester {
    const callTO='https://www.bicinscripcions.com/TPV/index.php';
	/*
    // Responses
    public function mirror($arguments) {
        // WS Mirror to debug purposes
        return $arguments;
    }
    public function help() {
        // Returns tyhe WSDL file for the web service
        $f=appData::getParam('wsdl');
        $file=appData::getParam('wsdlpath').$f;
        $xml=file_get_contents($file);
        $x=htmlentities($xml,ENT_QUOTES,'UTF-8');
        $rc=[ "[h3]The wdsl file ({$f}) defines the web service
            according to SOAP model ",
            "<a href='{$file}' target=_blank>
                To see the file in a browser tab</a>",
        ];
        return ['xml'=>$x,
            'html'=>[
                'html'=>htmlSimple::html($rc),
                'styles'=>'',
                'script'=>''
            ]
        ];
    }
	*/
	public function buildForm($arguments) {
        // It supplies the whole data for one shopping cart
        // and the status of the action
        $d=[];
        if($this->getParams($data=$arguments[0])){
			$whom=$data['whom'];
			$type=$data['type'];
			$t=$type*10+$whom;
            $post=models::model($type);
			$post['typePayment']=$t;
			$action=self::callTO;
			// to build the HTML form
			$m="TO JUMP TO SCRIPT CALLING THE TPV";
            $s="style='color:brown; font-weight:bold;'";
            $title = "<h3 {$s}>{$m}</h3>";
			$rc=[];
			$rc[]="<div style='width:90%; margin:2em auto;'>";
			$rc[]=$title;
			$rc[]="<form name=local method=post action='{$action}' >";
			if(0) dbg::seeDetails($list,"One dimensional list:");
			foreach($post as $key=>$v) {
				if( is_numeric($key) ) $key="NN[{$key}]";
				$rc[]="<input type=hidden name='{$key}' value='{$v}' >";
			}
			$rc[]="<button>SEND</button>";
			$rc[]="</form>";
			// The visible part of form
			foreach($post as $key=>$v) {
				if( is_numeric($key) ) $key="NN[{$key}]";
				$s="<input type=hidden name='{$key}' value='{$v}' >";
				$ss=htmlentities($s,ENT_QUOTES,"UTF-8");
				$rc[]="<div>{$ss}</div>";
			}
			$rc[]="</div>";
			$rcs=implode("\n",$rc);
			$d['html']=['html'=>$rcs,'styles'=>'','script'=>''];
        }
        return $this->answer($d,"WHOLE SC");
    }
    public function setTestSC($arguments) {
        require_once("../../_DATA/systemTEST_V0.php");
        $dins=$arguments[0];
        $dbclass=new putOnDB(System::memory());
        $ins=$dbclass->add($dins);
        return $ins;
    }
    public function eraseTestSC($arguments) {
        require_once("../../_DATA/systemTEST_V0.php");
        $dins=$arguments[0];
        $dbclass=new putOnDB(System::memory());
        $ins=$dbclass->erase();
        return $ins;
    }
	public function seeorder($arguments) {
		// To call the Order web service
		require_once('../../_DATA/systemTEST_V0.php');
		$sys=System::memory();
		$sys['voucher']=99999999;
		$ws=new wsOrder;
		return $ws->call('wholeSC',$sys);
	}
	//==================================================
    protected $PARAMS;
	protected $ercl;
    protected function getParams($data) {
        $this->PARAMS=$data;
		$this->ercl=new store;
        return true;
    }
	protected function answer($data,$from) {
		if( ! $this->ercl->isEmpty() )
			$data['error']=$this->ercl->html("{$from} ERRORS:");
		return $data;
	}
}
#=======================================================================
# Classes for calling webservices
class wsOrder extends wsImage {
    protected function getWS() {
        $wb='https://www.bicinscripcions.com/WSORDER/';
        $sc="WSORDER.php";
        return $wb.$sc;
    }
    public function call($action,$arguments) {
        $x=$this->soap->$action($arguments);
		return $this->filterResult($action,$arguments,$x);
    }
}
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
		require_once('class_soapV1.php');
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
#=======================================================================
# Classes for manage the test shopping cart (99999999)
class putOnDB extends DB_admin_V1 {
    private $PARAMS;
    public function __construct($system) {
        parent::__construct();
        $this->PARAMS=$system;
        $this->connectV1($system['bdconnector']);
    }
    private static function fieldsDB(){
		$f=[
			'voucher'=>'INT',
			'type'=>'VC20',
			'ind'=>'INT',
			'timestamp'=>'DATETIME',
			'properties'=>'TEXT',
			'userdata'=>'TEXT',
		];
		for($i=0;$i<10;$i++){
			$f['T'.$i]='VCFF';
			$f['I'.$i]='INT';
			$f['D'.$i]='DOUB';
			$f['F'.$i]='DTIM';
		}
		$key=['voucher','type','ind'];
		return [$f,$key];
	}
    protected function structure(){
        // Required by the abstract class DB_Admin
        // Gives the complete structure of the DB table
        if(0) dbg::seeDetails($this->PARAMS,"Parameters:");
        list($fields,$key)=self::fieldsDB();
        $name=$this->PARAMS['bdstruct']['tabledata'];
        $structure=[
            $name=>['name'=>$name,'fields'=>$fields,
                'primary'=>$key,'force'=>false,
            ],
        ];
        return $structure;
    }
    public function add($dins) {
		if(0) dbg::seeDetails($dins,"addToDb-Input:");
		// Insert each one of the records on the structure
		// Insert into the table
		$timestamp=Date('Y-m-d H:i:s');
		$ins=[];
		foreach($dins as $key=>$data) {
			foreach($data as $type=>$list) {
				foreach($list as $ind=>$onerecord) {
					$onerecord['timestamp']=$timestamp;
					$x=$this->insert($key,$type,$ind,$onerecord);
					$ins[$key][$type][$ind]=$x;
				}
			}
		}
		return $ins;
    }
    protected function insert($key,$type,$ind,$dins) {
		// Inserting one record into the DB
		// It's used basically to make easier the operation of append to
		if(0) dbg::seeDetails($dins,"Insert[{$key}-{$type}-{$ind}]");
		// To assure the primary key
		$dins['voucher']=$key;
		$dins['type']=$type;
		$dins['ind']=$ind;
		if(0) dbg::seeDetails($dins,"Record to insert:");
		$br=$this->structure()[$this->PARAMS['bdstruct']['tabledata']];
		if(0) dbg::seeDetails($br,"Structure of table:");
		$key=[];
		foreach($br['primary'] as $field) $key[]=$dins[$field];
		$keys=implode("-",$key);
		$sqlval=$sqlins=[];
		foreach($br['fields'] as $key=>$t) {
			if( isset($dins[$key]) ) {
				if( $dins[$key] === '' ) $val='NULL';
				else $val=$this->quote($dins[$key]);
				$sqlins[]=$key;
				$sqlval[]=$val;
			}
		}
		$sqins=implode(", ",$sqlins);
		$sqval=implode(", ",$sqlval);
		$sql ="REPLACE INTO {$br['name']} ({$sqins}) ";
		$sql.="VALUES ( {$sqval} )";
		if(0) dbg::seeDetails($sql,"SQL GENERATED");
		$n=$this->execSQL($sql);
		$di="{$n} records inserted of key {$keys}";
		return $di;
	}
    public function erase() {
        $br=$this->structure()[$this->PARAMS['bdstruct']['tabledata']];
        $sql ="DELETE FROM {$br['name']} ";
		$sql.="WHERE voucher='99999999' ";
		if(0) dbg::seeDetails($sql,"SQL GENERATED");
		$n=$this->execSQL($sql);
		$di="{$n} records deleted of key [99999999]";
        $ins['99999999']=$di;
		return $ins;
    }
}
#=======================================================================
# Class Models get the messages to send
class models {
	/*
	public static function model1($tt) {
        if( 90 <= $tt && $tt <= 92) $mer='guest 0a23f6';
        else $mer='indiroom';
        $t=[
            'typePayment'=>$tt,
			'transactionType'=>0,
			'amount'=>'5000',
			'voucher'=>'99999999',
			'productDescription'=>'Test',
			'titular'=>'INDIROOM',
			'name'=>'Bicinscripcions',
			'merdata'=>$mer,
			'language'=>'2'
		];
		return $t;
    }
	*/
	public static function model($type) {
		if(0) dbg::seeDetails(func_get_args(),"Input:");
		switch($type) {
			case 0: return self::TPVBuy(); break;
			case 3: return self::TPVReturn(); break;
			case 6: return self::OWNReturn(); break;
			case 7: return self::OWNPay(); break;
			case 8: return self::OWNBuy(); break;
			case 9: return self::OWNGuest(); break;
			default: return self::OWNBad();
		}
	}
	public static function TPVBuy() {
        $t=[
            'typePayment'=>0,
			'transactionType'=>0,
			'amount'=>5000,
			'voucher'=>99999999,
			'productDescription'=>'Test',
			'titular'=>'INDIROOM',
			'name'=>'Bicinscripcions',
			'merdata'=>'indiroom',
			'language'=>2
		];
		return $t;
	}
	public static function TPVReturn() {
		$t=[
            'typePayment'=>3,
			'transactionType'=>3,
			'amount'=>2500,
			'voucher'=>99999999,
			'productDescription'=>'Test',
			'titular'=>'INDIROOM',
			'name'=>'Bicinscripcions',
			'merdata'=>'(P0010)',
			'language'=>2
		];
		return $t;
	}
	public static function OWNBuy() {
		$t=[
            'typePayment'=>8,
			'transactionType'=>8,
			'amount'=>5000,
			'voucher'=>99999999,
			'productDescription'=>'Test',
			'titular'=>'INDIROOM',
			'name'=>'Bicinscripcions',
			'merdata'=>'indiroom',
			'language'=>2
		];
		return $t;
	}
	public static function OWNPay() {
		$t=[
            'typePayment'=>8,
			'transactionType'=>7,
			'amount'=>5000,
			'voucher'=>99999999,
			'productDescription'=>'Test',
			'titular'=>'INDIROOM',
			'name'=>'Bicinscripcions',
			'merdata'=>'indiroom',
			'language'=>2
		];
		return $t;
	}
	public static function OWNReturn() {
		$t=[
            'typePayment'=>6,
			'transactionType'=>6,
			'amount'=>2500,
			'voucher'=>99999999,
			'productDescription'=>'Test',
			'titular'=>'INDIROOM',
			'name'=>'Bicinscripcions',
			'merdata'=>'(P0010)',
			'language'=>2
		];
		return $t;
	}
	public static function OWNGuest() {
		$t=[
            'typePayment'=>9,
			'transactionType'=>9,
			'amount'=>0000,
			'voucher'=>99999999,
			'productDescription'=>'Test',
			'titular'=>'INDIROOM',
			'name'=>'Bicinscripcions',
			'merdata'=>'indiroom',
			'language'=>2
		];
		return $t;
	}
	public static function OWNBad() {
		$t=[
            'typePayment'=>5,
			'transactionType'=>8,
			'amount'=>2500,
			'voucher'=>99999999,
			'productDescription'=>'Test',
			'titular'=>'INDIROOM',
			'name'=>'Bicinscripcions',
			'merdata'=>'indiroom',
			'language'=>2
		];
		return $t;
	}
}
?>

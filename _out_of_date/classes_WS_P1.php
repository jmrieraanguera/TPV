<?php
$SEEINPUT=false;
#=======================================================================
# TODO How to avoid update functions by the again button on the browser
# TODO make a select without replacing the answer of the.
#=======================================================================
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
class wholeSC extends blockForm {
	use question;
	public function title_base() {
		$voucher=$_SESSION['voucher'];
		$s="To display the whole shopping Cart (Current:#{$voucher}) ";
		return [$s,'green'];
	}
	public function html($i){
		global $cldata,$clsession;
		$name=get_class();
		$hh=self::fM([
			'name'=>get_class(), '#'=>$i,
			['Language',$_SESSION['language'],'?'=>'select',
				['options'=>[1=>'CAT','ES','EN','FR']]
			],
			["<b>To empty selection enter the number zero</b>",'',
				'?'=>'plain'],
			["Voucher",$_SESSION['voucher']],
			['?'=>'button',1=>get_class()],
		]);
		$s=$hh['script'].$this->script();
		return [$hh['html'],$hh['styles'],$s];
	}
	private function script() {
		$rc= <<<'EOF'
			function addToSC($id) {
				var $jq=$('#'+$id);
				var $data=$jq.attr('data-product');
				var $d=$data.split("|");
				var $d={
						voucher:$d[0],
						idProduct:$d[1],
						from:$d[2],
						to:$d[3],
						priceHour:$d[4],
						discountAbs:$d[5],
						discountPct:$d[6],
						taxRate:$d[7],
						what:'addToSC',
						part:'editSC',
				};
				OQPAPP.commandFromObjects($d);
			}
EOF;
		return "<script>{$rc}</script>";
	}
	public function call($arguments) {
		global $clsession;
		$new=['language'=>$arguments[0],'voucher'=>$arguments[1]];
		$clsession->updateSession($new);
		$this->mem=webPlan::classCall(get_class(),$_SESSION);
		return main::HTML;
	}
	public function response() {
		return $this->mem;
	}
}
class addToSC extends blockForm {
	use question;
	public function showAfter() { return 'wholeSC';}
	public function type() { return 'NO-WS';}
	public function title_base() {
		$s="Links which allow to add a product upon the shopping cart";
		return [$s,'blue'];
	}
	public function html($i){
		// if( $this->type() != 'WS' )
		// $s=$_SESSION['ownlist'];
		$msg="Question triggered from the links in the right hand";
		$msg.="<br>";
		$msg.="The result is displayed in the wholeSC";
		$hh=self::fM([
			'name'=>get_class(), '#'=>$i,
			[$msg,'?'=>'plain'],

		]);
		return [$hh['html'],$hh['styles'],$hh['script']];
	}
	public function call($arguments) {
		global $classes;
		$arg=$_SESSION;
		$arg['own']=$_POST;
		if(0) dbg::seeDetails($arg,"New product data:");
		$this->mem=webPlan::classCall('appendType',$arg);
		$classes[$this->showAfter()]->setMEM($this->mem);
		return main::HTML;
	}
	public function response() {
		$voucher=$_SESSION['voucher'];
		$hoy=dateTime::createFromFormat('d-m-Y',Date('d-m-Y'));
		$hoy->add(new DateInterval('P3D') );
		$one=$hoy->format('d-m-Y');
		$hoy->add(new DateInterval('P1D') );
		$two=$hoy->format('d-m-Y');
		$links=[
			['P0010',"{$one} 16:00:00","{$one} 18:15:00",20,2,0.1,0.21],
			['P0020',"{$one} 19:25:00","{$one} 20:20:30",25,0,0,0.21],
			['P0030',"{$one} 21:00:00","{$one} 22:00:00",25,0,0,0.21],
			['P0040',"{$one} 23:00:00","{$two} 00:00:30",30,0,0.1,0.21],
			['P0050',"{$one} 11:30:00","{$one} 12:43:00",15,0,0.1,0.21],
			['BONUS',"01-01-2021 00:00:00","31-03-2021 23:59:59",
				0,-5,-0.1,0.21],
		];
		$rc=[];
		$rc[]="<div style='width:50%; margin:0 auto;
									border:2px solid darkBlue'>";
		$rc[]="<h3>List of Products</h3>";
		foreach($links as $i=>$link) {
			$rc[]="<div style='margin:0.3em'>";
			$a=$link[0].' '.$link[1].' '.$link[2];
			$data="data-product='".$voucher."|".implode('|',$link)."'";
			$rc[]="<a href=\"javascript:addToSC('{$link[0]}');\"
				id=$link[0] {$data} >{$a}</a>";
			$rc[]="</div>";
		}
		$rc[]="</div>";
		$html=implode("\n",$rc);
		$rc=[
			'data'=>[
				'html'=>['html'=>$html,'styles'=>'','script'=>'']
			],
			'trace'=>"No trace because WS is not called",
		];
		return $rc;
	}
}
class deleteLine extends blockForm {
	use question;
	public function showAfter() { return 'wholeSC';}
	public function type() { return 'NO-WS';}
	public function title_base() {
		$s="To delete an item in the shopping cart of the selected SC";
		return [$s,'blue'];
	}
	#===================================================================
	# Calling the Web Services function
	public function html($i){
		$h="<div>It's triggered from the whole form</div>";
		$hh=self::fM([
			'name'=>get_class(), '#'=>$i,
			[$h,'','?'=>'plain'],
		]);
		return [$hh['html'],$hh['styles'],$hh['script']];
	}
	public function call($arguments) {
		global $classes;
		$arg=$_SESSION;
		$arg['own']=$_POST;
		if(0) dbg::seeDetails($arg,"New product data:");
		$this->mem=webPlan::classCall('outLine',$arg);
		$classes[$this->showAfter()]->setMEM($this->mem);
		return main::HTML;
	}
	public function response() {
		$diag=$this->mem['data']['diag'];
		$html=dbg::seeDetailsStr($diag,"Diagnose:");
		$rc=[
			'data'=>[
				'html'=>['html'=>$html,'styles'=>'','script'=>'']
			],
			'trace'=>"No trace because WS is not called",
		];
		return $rc;
	}
}
class editPart extends blockForm {
	use question;
	public function title_base() {
		$s="To edit data of some part of SC";
		return [$s,'blue'];
	}
	#===================================================================
	# Calling the Web Services function
	public function html($i){
		$h="<div>It's triggered from the whole form</div>";
		$hh=self::fM([
			'name'=>get_class(), '#'=>$i,
			[$h,'','?'=>'plain'],
		]);
		return [$hh['html'],$hh['styles'],$hh['script']];
	}
	public function call($arguments) {
		global $classes;
		$part=isset($_POST['part']) ? $_POST['part'] :'';
		// Calling to the WS
		$arg=$_SESSION;
		$arg['own']['part']=$part;
		$this->mem=webPlan::classCall('getPart',$arg);
		return main::HTML;
	}
	public function response() { return $this->mem; }
}
class savePart extends blockForm {
	use question;
	public function type() { return 'WS';}
	public function showAfter() { return 'wholeSC'; }
	public function title_base() {
		$s="To save the edited part of the SC";
		return [$s,'blue'];
	}
	#===================================================================
	# Calling the Web Services function
	public function html($i){
		$m="Action triggered in the EDIT part";
		$hh=self::fM([
			'name'=>get_class(), '#'=>$i,
			[$m,'','?'=>'plain'],
		]);
		return [$hh['html'],$hh['styles'],$hh['script']];
	}
	public function call($arguments) {
		global $classes;
		$arg=$_SESSION; $arg['own']=$_POST;
		$this->mem=webPlan::classCall('appendType',$arg);
		$classes[$this->showAfter()]->setMEM($this->mem);
		return main::HTML;
	}
	public function response() {
		$p ="<div style='width:100%; text-align:center;'>";
		$p.="<h3>You arriving here through accepting a form
			in edit part</h3>";
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
			'action'=>$this->dub,
			'arguments'=>[],
		];
	}
}
class gotoPay extends blockForm {
	use question;
	public function type() { return 'JUMP';}
	public function title_base() {
		$s="To send the user to pay for the shopping cart";
		return [$s,'blue'];
	}
	#===================================================================
	# Calling the Web Services function
	public function html($i){
		$m="You arriving here trough the payment button of whole SC";
		$hh=self::fM([
			'name'=>get_class(), '#'=>$i,
			[$m,'','?'=>'plain'],
		]);
		return [$hh['html'],$hh['styles'],$hh['script']];
	}
	public function call($arguments) {
		global $clsession;
		$this->mem=webPlan::classCall(get_class(),$_SESSION);
		if( isset($this->mem['data']['dataTPV']) ) {
			$arg=$this->mem['data']['dataTPV'];
			$url='https://www.bicinscripcions.com/TPV/index.php';
			exit(gnrl::pageJump($url,$arg,true));
		}
		return main::HTML;
	}
	public function response() {
		$p ="<div style='width:100%; text-align:center;'>";
		$p.="<h3>It's not really a WS question</h3>";
		$p.="<p>It goes outside of the tester to connect
			with a payment system";
		$p.="</div>";
		if( isset($this->mem['data']['error']) )  {
			$e=$this->mem['data']['error'];
			$p ="<div style='width:100%; text-align:center;'>";
			$p.="<h3>There are errors in the WS call</h3>";
			foreach($e as $er) $p.="<p style='color:brown'>$er</p>";
			$p.="</div>";
		}
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





function modelAnswer($voucher,$acc,$now) {
	if($voucher) {
		$response=[
		    'Ds_Terminal'=>'001',
		    'Ds_Response'=>$acc=='ACCEPT' ? '0000' :'0180',
		    'Ds_Amount'=>'10752',
		    'Ds_SecurePayment'=>'1',
		    'Ds_Card_Type'=>'D',
		    'Ds_Card_Country'=>'724',
		    'Ds_Card_Brand'=>'1',
		    'Ds_ProcessedPayMethod'=>'5',
		    'Ds_ConsumerLanguage'=>'001',
		    'Ds_Date'=>$now->format('d-m-Y'),
		    'Ds_MerchantData'=>'indiroom',
		    'Ds_Order'=>$voucher,
		    'Ds_MerchantCode'=>'297361610',
		    'Ds_TransactionType'=>'0',
		    'Ds_Hour'=> $now->format('H:i'), //
		    'Ds_AuthorisationCode'=>$acc=='ACCEPT'? '300969':'',
		    'Ds_Currency'=>'978',
		    'Server URL: sis.redsys.es'
		];
	}
	else
		$response=['diag'=>"The voucher 0 cannot be received"];
	return [$voucher!=0,$response];
}
class server_TPVAnswer extends blockForm {
	use question;
	public function showAfter() { return 'wholeSC'; }
	protected function title_base() {
		$s="To simulate the answer of TPV on our server";
		return [$s,'red'];
	}
	public function html($i){
		$voucher=$_SESSION['voucher'];
		$acc=$_SESSION['simulator']['acceptation'];
		$now=new DateTime;
		$nowstr=$now->format('Y-m-d H:i');
		list($ok,$reply)=modelAnswer($voucher,$acc,$now);
		if($ok) $b=['?'=>'button',1=>'server_TPVAnswer'];
		else $b=['NO VOUCHER','','?'=>'plain'];
		$dr=dbg::SeeDetails($reply,"Data Received:",true);
		$hh=self::fM([
			'name'=>get_class(),'#'=>$i,
			$b,
			[$dr,'','?'=>'plain'],
		]);
		return [$hh['html'],$hh['styles'],$hh['script']];
	}
	public function call($arguments) {
		global $clsession,$classes;
		$voucher=$_SESSION['voucher'];
		$acc=$_SESSION['simulator']['acceptation'];
		$now=new DateTime;
		$nowstr=$now->format('Y-m-d H:i');
		$clsession->updateSession(['simulator'=>['now'=>$nowstr]]);
		$arg=$_SESSION;
		list($isOK,$answer)=modelAnswer($voucher,$acc,$now);
		if( $isOK) {
			$this->model=$arg['answer']=$answer;
			$this->mem=webPlan::classCall(get_class(),$arg);
			$classes[$this->showAfter()]->setMEM($this->mem);
		}
		else
			$this->mem['data']=$answer;
		return main::HTML;
	}
	public function response() {
		$diag=$this->mem['data']['diag'];
		$html=dbg::seeDetailsStr($diag,"Diagnose:");
		$rc= [
			'data'=>[
				'html'=>[
					'html'=>$html,
					'styles'=>'',
					'script'=>'',
				]
			],
			'trace'=>'NO TRACE AVAILABLE',
			'action'=>'server_TPVAnswer',
			'arguments'=>$this->model,
		];
		return $rc;
	}
}
class user_TPVAnswer extends blockForm {
	use question;
	protected function title_base() {
		$s="To simulate the answer of TPV on the user side";
		return [$s,'red'];
	}
	#===================================================================
	# Calling the Web Services function
	public function html($i){
		$voucher=$_SESSION['voucher'];
		$acc=$_SESSION['simulator']['acceptation'];
		$nowstr=$_SESSION['simulator']['now'];
		$now=DateTime::createFromFormat('Y-m-d H:i',$nowstr);
		list($ok,$reply)=modelAnswer($voucher,$acc,$now);
		if($ok) $b=['?'=>'button',1=>'user_TPVAnswer'];
		else $b=['NO VOUCHER','','?'=>'plain'];
		$dr=dbg::SeeDetails($reply,"Data Received:",true);
		$hh=self::fM([
			'name'=>get_class(),'#'=>$i,
			$b,
			[$dr,'','?'=>'plain'],
		]);
		return [$hh['html'],$hh['styles'],$hh['script']];
	}
	public function call($arguments) {
		global $classes;
		$voucher=$_SESSION['voucher'];
		$acc=$_SESSION['simulator']['acceptation'];
		$nowstr=$_SESSION['simulator']['now'];
		$now=DateTime::createFromFormat('Y-m-d H:i',$nowstr);
		list($isOK,$answer)=modelAnswer($voucher,$acc,$now);
		$arg=$_SESSION;
		if( $isOK) {
			$this->model=$arg['answer']=$answer;
			$this->mem=webPlan::classCall(get_class(),$arg);
		}
		else
			$this->mem['data']=$answer;
		return main::HTML;
	}
	public function response() {
		$voucher=$_SESSION['voucher'];
		$acc=$_SESSION['simulator']['acceptation'];
		$nowstr=$_SESSION['simulator']['now'];
		$now=DateTime::createFromFormat('Y-m-d H:i',$nowstr);
		list($isOK,$answer)=modelAnswer($voucher,$acc,$now);
		if(0) dbg::seeDetails($answer,"IS OK [{$ok}]");
		if( $isOK )
			$rc=$this->mem;
		else
			$rc= [
				'data'=>[
					'html'=>[
						'html'=>"No available with 0 voucher",
						'styles'=>'',
						'script'=>'',
					]
				],
				'trace'=>'NO TRACE AVAILABLE',
				'action'=>'user_TPVAnswer',
				'arguments'=>[],
			];
		return $rc;
	}
}
class admin_Rent extends blockForm {
	use question;
	public function showAfter() { return 'wholeSC'; }
	protected function title_base() {
		$s="To fit the shopping chart with the order";
		return [$s,'red'];
	}
	#===================================================================
	# Calling the Web Services function
	public function html($i){
		$dr="This function checks the shop cart against the order";
		$dr.="<br>";
		$dr.="It's usually called from the answer to server TPV";
		$hh=self::fM([
			'name'=>get_class(),'#'=>$i,
			['?'=>'button',1=>'admin_Rent'],
			[$dr,'','?'=>'plain'],
		]);
		return [$hh['html'],$hh['styles'],$hh['script']];
	}
	public function call($arguments) {
		$arg=$_SESSION;
		$this->mem=webPlan::classCall(get_class(),$arg);
		return main::HTML;
	}
	public function response() {
		$diag=$this->mem['data']['diag'];
		$html=dbg::seeDetailsStr($diag,"Diagnose:");
		$rc= [
			'data'=>[
				'html'=>[
					'html'=>$html,
					'styles'=>'',
					'script'=>'',
				]
			],
			'trace'=>'NO TRACE AVAILABLE',
			'action'=>'server_TPVAnswer',
			'arguments'=>$this->model,
		];
		return $rc;
	}
}

class reIndex extends blockForm {
	use question;
	protected function title_base() {
		$s="To reindex a list of shopping Carts";
		return [$s,'red'];
	}
	#===================================================================
	# Calling the Web Services function
	public function html($i){
		$hh=self::fM([
			'name'=>get_class(), '#'=>$i,
			['Comma separated List:','10000000'],
			['?'=>'button',1=>'reIndex'],
		]);
		return [$hh['html'],$hh['styles'],$hh['script']];
	}
	public function call($arguments) {
		$list=explode(',',$arguments[0]);
		foreach($list as $i=>$l) $list[$i]=trim($l);
		$arg=$_SESSION; $arg['own']['list']=$list;
		$this->mem=webPlan::classCall(get_class(),$arg);
		return main::HTML;
	}
	public function response() { return $this->mem;}
}
?>

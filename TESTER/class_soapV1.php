<?php
class oqpBase {
    #===================================================================
    # SOAP interchange with PHP Soap devices
    #===================================================================
    # Rev 1. Changes in the use of the signatire
    const firma="Organizaci&oacute;n, Calidad y Proyectos, S.L.X";
    public static function MergeProperties($a, $b) {
        $final = $b;
        foreach ($a as $key => $av) {
            if (array_key_exists($key, $final)) {
                if (is_array($av))
                $final[$key] = self::MergeProperties($av, $final[$key]);
            } else
                $final[$key] = $av;
        }
        return $final;
    }
	public static function SeeDetails($results,$msg='',$string=false){
        $rc ='';
        $rc.="<details><summary>{$msg}</summary><pre>";
        $rc.=print_r($results,true);
        $rc.="</pre></details>";
        if( ! $string ) echo $rc;
        return $rc;
    }
    protected $prp;
	protected $errors;
	protected $trz;
	public function getLastErrors() {return $this->errors;}
	public function getTrz() {
		$t=implode("<br>",$this->trz);
		 return $t;
	}
	protected function setTrz($msg) { $this->trz[]=$msg;}
	protected function sd($data,$title='') {
		$this->trz[]=self::seeDetails($data,$title,true);
	}

}
class oqpsoapClient extends oqpBase {
    protected static function def() {
        $default=[
    		'location'=>'',
    		'uri'=>'',
            'firma'=>''
    	];
        return $default;
    }
    public function __construct($prp=[]) {
		$this->prp=self::MergeProperties($this->def(), $prp);
        if($this->prp['firma']=='') $this->prp['firma']=self::firma;
		$this->trz=[];
	}
	public function __call($name,$arguments) {
		$this->setTrz("Coming in with the order:<b>{$name}</b>");
		$this->sd($arguments,'Arguments');
		$d=$errors=[];
		$qsts=serialize($arguments);
		// Prepare data
    	$xl=base64_encode($qsts);
    	// Calculate the signature
    	$signature=sha1($xl.$this->prp['firma']);
    	$param=['order'=>$name,'data'=>$xl,'signature'=>$signature];
		$this->sd($param,'Data ready to send');
		try {
			$this->setTrz("Calling to {$this->prp['location']}");
			$options=[
				'location'=>$this->prp['location'],
				'uri'=>$this->prp['uri'],
				'trace'=>true,
				'exceptions'=>true,
			];
			$soapclient=new SoapClient(null,$options);
			$this->setTrz("CREATED THE SOAP Client");
		}
    	catch(SoapFault $e) {
    		$this->setTrz("NOT CREATED THE SOAP Client");
    		$errors[]=self::seeDetails($e,"Exception SoapFault:",true);
    		$errors[]="There are errors before send:".$e->getMessage();
    	}
		$draw=['empty array'];
		if( $soapclient ) try {
			$this->setTrz("Calling");
			$draw=$soapclient->dispatch($param);
			$this->sd($draw,"Raw data returned:");
		}
		catch (SoapFault $e ) {
    			$errors[]=self::seeDetails($e,"Exception SoapFault:",true);
    			$errors[]="There are errors after send:".$e->getMessage();
    	}
		if( $soapclient && isset($draw['data']) ){
			$signature=sha1($draw['data'].$this->prp['firma']);
			if( $signature == $draw['signature']) {
				$dser=base64_decode($draw['data']);
				$darr=unserialize($dser);
				$d=[$draw['order']=>$darr];
				$this->setTrz(self::seeDetails($d,"Unpacked response",true));
				$d=$darr;
			}
			else {
				$errors[]="Signatures not matching";
				$errors[]="Coming up:{$draw['signature']}";
				$errors[]="Calculated:{$signature}";
			}
		}
		else $d=['no response'=>['empty response']];
		$this->errors=implode("<br>",$errors);
		return $d;
	}
}
class oqpsoapServer extends soapServer{
	protected $prp;
	public function __construct() {
		global $URI,$CLASS;
		// It is not possible to pass the parameters in the classical
		// way because handle creates a new instance without using $prp
		$prp = ['uri' => $URI,'class'=>$CLASS];
        $prp['firma']=isset($SIGNATURE) ? $SIGNATURE : oqpBase::firma;
		$trz=false;
		$this->prp=$prp;
		$options=['uri'=>$this->prp['uri'],];
		parent::__construct(null,$options);
		$this->setClass('oqpsoapServer');
		if($trz) oqpBase::seeDetails($this->prp,"Propietats:");
	}
	public function handle($soap_request=NULL) {
		$trz=false;
		$request=file_get_contents("php://input");
		If($trz) oqpBase::seeDetails($request,"PHP-INPUT");
		ob_end_flush();
		ob_start();
		$result=parent::handle($request);
		ob_flush();
		return $result;
	}
	//------------------------------------------------------------------
	// The properties don't arrive in this instance who is created
	// afterwards for the own oqpSoapServer
	public function dispatch($data) {
		$errors=[];
		$name=$data['order'];
		if( ! class_exists($this->prp['class']) ) {
			$errors[]="Server OQP SOAP class doesn't exists";
		}
		else {
			$clser=new $this->prp['class'];
			if( ! method_exists($clser,$name) ) {
				$errors[]="The method {$name} doesnt exists";
			}
		}
        // To trace the executiuon 
        // $errors[]="Data=".$data['data']."<br>";
        // $errors[]="Firma=".$this->prp['firma']."<br>";
		$signature=sha1($data['data'].$this->prp['firma']);
		if( $signature != $data['signature']) {
			$errors[]="Signatures not matching";
			$errors[]="Coming up:{$data['signature']}";
			$errors[]="Calculated:{$signature}";
		}
		if( count($errors) == 0 ){
			$dser=base64_decode($data['data']);
			$d=unserialize($dser);
			$answeraw=$clser->$name($d);
			if(! $clser->isThereErrors() ) {
				$a=serialize($answeraw);
				$a64=base64_encode($a);
				$answer=[];
				$answer['order']="{$name} Response";
				$answer['data']=$a64;
				$answer['signature']=sha1($a64.$this->prp['firma']);
			}
			else {
				$errors[]="Errors in the method {$name}";
				foreach($answeraw['error'] as $e )$errors[]=$e;
			}
		}
		if( count($errors) )  {
			$ea=array_merge(["Errors in DISPATCH:"],$errors);
			$e=implode("-",$ea);
			throw new SoapFault('DISPATCH',$e);
		}
		return $answer;
	}
	public function mirror($data) {
		return $data;
	}
}
?>

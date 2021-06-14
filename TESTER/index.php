<?php
//======================================================================
// ATTENTION Remember that the error of compilation in this
// script is very difficult to correct because of PHP settings
// which are changed after execution of that script
#=======================================================================
# The only output message to be released when it's called
exit(singleton::outputPage());
#=======================================================================
// Classes to serve the script
class singleton {
	const WEBASE="https://www.bicinscripcions.com/TPV/";
    const SESSIONLOGIN="index.php";
	const ISCLOSED = false;
    const OPENTEST= true;
    const SHOWSESSION = false;
    const SEEINPUT= false;
	const SEEPHPINFO = false;
    // The initial class to manage the rest of classes
    public static function phpSetup() {
		if( self::OPENTEST ) $s='On'; else $s='Off';
    	ini_set('display_errors',$s);
    	ini_set('default_charset','UTF-8');
    	date_default_timezone_set('Europe/Madrid');
    }
	public static function see() {
		global $SEEINPUT;
        if( self::SEEINPUT || (isset($SEEINPUT) && $SEEINPUT) ){
    		if(isset($_GET))self::show($_GET,"GET:");
    		if(isset($_POST))self::show($_POST,"POST:");
    		if(isset($_COOKIE))self::show($_COOKIE,"COOKIES:");
    		if(isset($_FILES))self::show($_FILES,"FILES:");
    		if(isset($_ENV))self::show($_ENV,"ENV:");
    		if(isset($_SERVER))self::show($_SERVER,"SERVER:");
        }
	}
    public static function show($results,$msg='',$onlystring=false) {
        $rc ='';
        $rc.="<details><summary>{$msg}</summary><pre>";
        $rc.=print_r($results,true);
        $rc.="</pre></details>";
        if($onlystring) return $rc; else echo $rc;
    }
    public static function outputPage() {
		// Set the php options
		self::phpSetup();
		// Set the session
        global $clsession;
        $clsession =new SessionV4;
        //--------------------------------------------------------------
        // Exit if the web is closed for maintenance except
        // when the user is the master
        if( self::ISCLOSED && $_SESSION['usr'] != 'master' )
            exit(pageMaintenance::show());
        # --------------------------------------------------------------
    	# Show the parameters entered with the request
        self::see();
		if( self::SEEPHPINFO ) phpinfo();
		# --------------------------------------------------------------
		# Reset the session if requested
		if( isset($_POST['reset']) && $_POST['reset'] == 'XRESET')
			$clsession->logout(sessionV4::NOACT);
        # --------------------------------------------------------------
		# Open the main class of the system
		require_once('main.php');
        $clmain=new main;
        $s=$clmain->execute();
        $shses=(singleton::SHOWSESSION ? $clsession->getSession() : '');
		return $s.$shses;
	}
}
class sessionV4 { // Class to manage the session
	private function  aim() {
		# The class pretends to use the session for storing the
		# permanent memory of one application
		# wheteher it does or doesn't a log in to execute it.
		# It has two ways of make the logout:
		#	a) The logout is performed by calling to a website from
		#		which yhe user will need to login again to come back
		#		inside the app.
		#	b) The logout is performed changing to the anonymous user
		#		and accessing to the places suitable for that user.
	}
	#===================================================================
    # Secret keyword for the calculation of the data transfer signature.
    const firma="Organizaci&oacute;n, Calidad y Proyectos, S.L.X";
	const JUMP=1; // Indicates making the logout by jumping to a page
	const CHGUSER=2; // Indicates making the logout by changing user
	const NOACT=3; // No action is taken
	private $prp; // Mantain the memory of the session
	private $trz; // Traces the sesion execution
	private $backup; // Makes a backup of the memory before changing it
	public function updateSession($data=[]) {
		$this->backup=$_SESSION;
		$_SESSION=self::MergeProperties($_SESSION,$data);
		list($_SESSION['signature'],$t)=$this->calculateSignature();
	}
	public static function MergeProperties($a, $b) {
        $final = $b;
		if( is_array($a)) foreach ($a as $key => $av) {
            if (array_key_exists($key, $final)) {
                if (is_array($av))
                    $final[$key] =
                        self::MergeProperties($av, $final[$key]);
            } else
                $final[$key] = $av;
        }
        return $final;
    }
	private function def(){
		$wb=$_SERVER['SERVER_NAME'];

		$default=[
			'mode'=>self::NOACT,
			'memory'=>[
				'usr'=>'anonymous', 'idioma'=>3, 'weblogin'=>$wb,
			],
			'firma'=>self::firma,
			'lifetime'=>1800,
			// It's only needed if you wanted
			// to change the values coming in
			'params'=>[],
		];
		return $default;
	}
	public function __construct($prp=[]) {
		$this->trz=[];
		$this->prp=self::MergeProperties($this->def(), $prp);
		$this->trz[]=singleton::show($this->prp,"PROPERTIES:",true);
		#---------------------------------------------------------------
		if( session_status() == PHP_SESSION_DISABLED )
			die("Session are disabled");
		if( ! session_start() ) die("Session cannot be started");
		$s=[
			'Name'=>session_name(),
			'Status'=>session_status(),
			'Id'=>session_id(),
		];
		$this->setUpCookie();
		$this->trz[]="STATUS=".($st=session_status());
		if(!$this->check_Signature())$this->logout($this->prp['mode']);
		// exit();
	}
	public function trace() {return $this->trz;}
	public function formerMemory(){ return $this->backup;}
	//==================================================================
	// The function make the logout
	public function logout($mode) {
		$_SESSION=[];
		if( $mode == self::JUMP ) $this->logoutJUMP();
		else if($mode == self::CHGUSER) $this->logoutCHGUSER();
		else if( $mode == self::NOACT) $this->logoutNOACT();
		else die("WRONG SESSION MODE");
	}
	private function logoutCHGUSER() {
		$this->trz[]="Reset the session:".session_id();
		session_start();
		$this->trz[]="New session after logout:".session_id();
		$this->setUpCookie();
	}
	private function logoutJUMP($action='') {
		// Jump to the website
		if($action == '')
            $action=singleton::WEBASE.singleton::SESSIONLOGIN;
		$stop=false;
		$rc=[];
		$rc[]="<!DOCTYPE html>";
		$rc[]="<html><head></head><body>";
        $rc[]="<form name=local method=post action='{$action}' >";
		if( $stop ) {
			$rc[]=$this->show();
			$rc[]="<button>GO ON</button>";
		}
        $rc[]="</form>";
		if( ! $stop ){
			$rc[]="<script>";
			$rc[]="document.forms['local'].submit();";
			$rc[]="</script>";
		}
		$rc[]="</body></html>";
		exit(implode("\n",$rc));
	}
	private function logoutNOACT($action='') {
		$this->trz[]="New session array without logout:";
	}
	#===================================================================
	# Renew the due date for the cookie that manages the session
	public function setUpCookie() {
		 if (ini_get("session.use_cookies")) {
		 	$this->time=time()+$this->prp['lifetime'];
			$t=new DateTime();
			$t->setTimestamp($this->time);
			$f=$t->format('c');
			$this->trz[]="Expire at {$f}";
			$params= session_get_cookie_params();
			foreach($this->prp['params'] as $p=>$v) $params[$p]=$v;
			if(0) singleton::show($params,"Cookie params before set:");
			 // This set the cookie for the next call
            setcookie(session_name(), session_id(),
            		$this->time,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
		else die( 'session cookies disabled');
	}
	public function resetCookie() {
		$params= session_get_cookie_params();
		setcookie(session_name(),session_id(), time()-42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
	}
	//==================================================================
	// Signature management
	public function CalculateSignature() {
    	$x='';
		$ses=$_SESSION;
		array_walk_recursive($ses,function($item,$clave) use ($x){
			if(strtolower($clave)!='signature') $x.="{$clave}={$item}";
		});
        $x.=$this->prp['firma'];
        $sig=sha1($x);
        return array($sig,$x);
    }
	private function check_Signature() {
		list($calsig,$txt) = $this->CalculateSignature();
        $insig=isset($_SESSION['signature'])?$_SESSION['signature']:'';
        $rc=$insig == $calsig;
		$this->trz[]="Signatures comparison: [{$rc}]";
		return $rc;
	}
	#===================================================================
	# Show the data related with the current session
	public function getSession() {
		$msg[]="<details><summary>SESSION AND COOKIES</summary>";
		$msg[]=$this->show();
		$msg[]="</details>";
		return implode("\n",$msg);
	}
	public function show() {
		$d=$this->array();
		$x="<pre>".print_r($d,true)."</pre>";
		return $x;
    }
	public function array() {
		if( isset($_COOKIE) ) $c=$_COOKIE; else $c=[];
		if( isset($_SESSION) ) $s=$_SESSION; else $s=[];
		$d=['cookies'=>$c,'session'=>$s,
			'expire'=>session_cache_expire(),
			'cookparam'=>session_get_cookie_params(),
			'trace'=>$this->trz
		];
		return $d;
	}
}
class pageMaintenance { // Class whe the system is closed
	private static function MSG() {
		$msg=[
			1=>'El proper 1 de Maig',
			2=>'El próximo 1 de Mayo',
			3=>'The next May 1st',
			4=>'Te prochain 1 Mai',
		];
		$msg=[1=>'','','',''];
		return $msg;
	}
	public static function show() {
		$rc=[];
        $rc[]="<html>";
		$rc[]="<head>";
		$rc[]="<title>Maintenance Notice</title>";
		$rc[]=self::stylesMNTNC();
		$rc[]="</head>";
		$rc[]="<body>";
		$rc[]="<div class=block>";
		$lans=self::MSG();
		foreach($lans as $lan=>$msg) $rc[]=self::block($lan,$msg);
		$rc[]="</div>";
		$rc[]="</body>";
	    $rc[]="</html>";
		return implode("\n",$rc);
	}
	private static function block($idioma,$msg) {
		$rc=[];
		$rc[]="<div class=blockwrapper>";
		$rc[]="	<div class=blocfirstline>";
		$rc[]="		<p>";
		$rc[]=			self::translate('firstLine',$idioma,$msg);
		$rc[]="		</p>";
		$rc[]="	</div>";
		$rc[]="	<div class=blocsecondline>";
		$rc[]="		<p>";
		$rc[]=			self::translate('secondLine',$idioma,$msg);
		$rc[]="		</p>";
		$rc[]="	</div>";
		$rc[]="	<div class=blocthirdline>";
		$rc[]="		<p>";
		$rc[]=			self::translate('thirdLine',$idioma,$msg);
		$rc[]="		</p>";
		$rc[]="	</div>";
		$rc[]="</div>";
		return implode("\n",$rc);
	}
	private static function stylesMNTNC() {
		$r="
			.block {
				box-sizing:border-box;
				width:80%;
				margin:3em auto;
			}
			.block p {
				padding:0.5em;
			}
			.blockwrapper {
				box-sizing:border-box;
				width:75%;
		        margin:1em auto;
		        border:2px solid #bbb;
		        background-color:#ffcccc;
			}
			.blocfirstline {
				font-weight:bold;
				box-sizing:border-box;
				color:Darkred;
				text-align:center
			}
			.blocsecondline {
				font-weight:bold;
				box-sizing:border-box;
				color:Darkred;
				text-align:center;
			}
			.blocthirdline {
				font-weight:bold;
				box-sizing:border-box;
				color:Darkred;
				text-align:center;
			}
		";
		return "<style>$r</style>";
	}
	protected static function translate($line,$idioma,$msg='') {
		$cat=[
			'firstLine'=>'Web tancada temporalment per
									tasques de manteniment',
			'secondLine'=>'Apertura prevista proximament',
			'thirdLine'=>'Disculpeu les molesties',
		];
		$es=[
			'firstLine'=>'Web cerrada temporalmente por
									tareas de mantenimiento',
			'secondLine'=>'Apertura prevista proximamente',
			'thirdLine'=>'Disculpen las molestias',
		];
		$uk=[
			'firstLine'=>'Web temporarily closed because maintenance tasks',
			'secondLine'=>'It will be open soon',
			'thirdLine'=>'Apologize for the inconveniences',
		];
		$fr=[
			'firstLine'=>'Web temporairement fermé en raison de
				tâches de maintenance',
			'secondLine'=>'Il sera bientôt ouvert',
			'thirdLine'=>'Désolé pour le dérangement',
		];
		switch($idioma) {
			case 1: default: $x=$cat; break;
			case 2: $x=$es; break;
			case 3: $x=$uk; break;
			case 4: $x=$fr; break;
		}
		if( $msg ) $x['secondLine']=$msg;
		return $x[$line];
	}
}
?>

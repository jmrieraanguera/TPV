<?php
//======================================================================
// ATTENTION Remember that the error of compilation in this
// script is very difficult to correct becuase of the settings
// of PHP which is changed after execution of that script
#=======================================================================
# The only output to the make the arrived request
exit(singleton::outputPage());
// Classes to open the program
class singleton {
	const WEBASE="https://www.bicinscripcions.com/INDIROOM/";
    const SESSIONLOGIN="indexVigent.php";
	const ISCLOSED = false;
    const OPENTEST= true;
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
		# Open the main class of the system
		require_once('main.php');
        $clmain=new main;
        $s=$clmain->execute();
		return $s;
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

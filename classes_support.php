<?php
class gnrl {
	# Private signature to algorithms
	const firma="Organizaci&oacute;n, Calidad y Proyectos, S.L.X";
	# Languages codes
	const CAT=1; const ES=2; const EN=3; const FR=4;
	#===================================================================
    # Recursive merging of two arrays with pririoty in the second arg
    #===================================================================
    # This merge take two arguments $a and $b
    # The $b argument is taken as output
	# if the argument $a isn't an array the $b is output
    # If $a is an array, it is scanned
    # if the $key is not in the final array it is simply added
    # if $key exists in the final and the member is an array the
    # own function is invoked recursivily otherwise substituted
    public static function MergeProperties($a, $b) {
        $final = $b;
		if( is_array($a)) foreach ($a as $key => $av) {
            if (array_key_exists($key, $final)) {
                if (is_array($av))
                	$final[$key]=self::MergeProperties($av,$final[$key]);
            } else
                $final[$key] = $av;
        }
        return $final;
    }
	#===================================================================
    # Recursive building of two array diferences
    #===================================================================
    # The objective is generate an array with the diferences
    # between the two arrays
    # The first instruction is set up the dif array to empty.
	public static function compareArrays($a, $b) {
		$trz=false;
		if($trz)dbg::seeDetails([$a,$b],"Input");
		$dif=[];
		foreach($a as $key=>$av) {
			if( isset($b[$key]) ) {
				if( is_array($av) && is_array($b[$key])) {
					if($trz) dbg::seeDetails($av,"Call {$key}");
					$d=self::compareArrays($av,$b[$key]);
					if($trz) dbg::seeDetails($d,"Diferences {$key}");
					if( count($d) ) $dif[$key]=$d;
				}
				else if( is_array($av)) {
					$dif[$key]=$b[$key];
				}
				else if( $b[$key] != $av ) {
					if($trz) echo "Key:{$key}:{$b[$key]}:{$av}";
					$dif[$key]=$av;
				}
			}
			else $dif[$key]=$av;
			if($trz) dbg::seeDetails($dif,"Diferences {$key}");
		}
		return $dif;
	}
	#===================================================================
    # Rec. transformation from ISO to UTF-8 codification and vice versa
    #===================================================================
	public static function toUTF8($data) {
		switch(gettype($data) ) {
			case 'string':
				$y=mb_detect_encoding($data,'ISO-8859-1',true);
				$z=mb_detect_encoding($data,'UTF-8',true);
				# echo "$v => [{$z}] trobat [{$zz}]<br>";
				if( $y && !$z ) $data=utf8_encode($data);
				break;
			case 'array':
				array_walk_recursive($data,function(&$v,$key){
				 	if( is_string($v) ) {
				 		$y=mb_detect_encoding($v,'ISO-8859-1',true);
						$z=mb_detect_encoding($v,'UTF-8',true);
						# echo "$v => [{$z}] trobat [{$zz}]<br>";
						if( $y && !$z ) $v=utf8_encode($v);
					}
				});
		}
		return $data;
	}
	public static function toISO($data) {
		switch(gettype($data) ) {
			case 'string':
				$z=mb_detect_encoding($data,'UTF-8',true);
				if( $z ) $data=utf8_decode($data);
				break;
			case 'array':
				array_walk_recursive($data,function(&$v,$key){
				 	if( is_string($v) ) {
						$z=mb_detect_encoding($v,'UTF-8',true);
						# echo "$v => is UTF-8? [{$z}]<br>";
						if( $z ) $v=utf8_decode($v);
					}
				});

		}
		return $data;
	}
	#===================================================================
    # Tranform the php array data to a javascript object rc
    public static function writeJSData($data,$level=0) {
        $prf=str_repeat("\t",$level);
        $keys=array_keys($data);
        $isnumeric=true;
        foreach($keys as $key )
					$isnumeric = $isnumeric && is_numeric($key);
        if( $isnumeric ) { $ini='['; $fin=']'; $withkey=false; }
        else             { $ini='{'; $fin='}'; $withkey=true;  }
        $rc=array();
        $rc[]="{$prf}{$ini}";
        $i=1;
        foreach($data as $key=>$val) {
            if( $i++ == count($data) ) $sep=''; else $sep=",";
            if( is_array($val) )
				$valtt=self::writeJSData($val,$level+1);
            else                 {
                if( is_numeric($val) )   $valtt=$val;
                else  {
                    $val=str_replace("\n",'\\n',$val);
                    $val=str_replace("\r",'\\r',$val);
                    $val=str_replace("\t",'\\t',$val);
                    $val=str_replace("\b",'\\b',$val);
                    $val=str_replace("\f",'\\f',$val);
                    $val=str_replace("'","\\'",$val);
                    $val=str_replace('"',"\\\"",$val);
                    $valtt="\"{$val}\"";
                }
            }
            if( preg_match("/^[A-Z][A-Z0-9]*$/i",$key) ) $keytt=$key;
            else $keytt="\"{$key}\"";
            if( $withkey ) {
                $rc[]="{$prf}{$keytt}:{$valtt} {$sep} ";
            }
            else {
                 $rc[]="{$prf} {$valtt} {$sep} ";
            }
        }
        $rc[]="{$prf}{$fin}";
        return implode("\n",$rc);
    }
	#===================================================================
	# Look in a multilevel array through the path
	public static function getPath($prp,$name) {
		// Get the name of parameter as array or a string
		$type=gettype($name);
		if( $type != 'array' && $type != 'object' )
			$name=explode('.',$name);
		$p=$prp;
		foreach($name as $n) {
			if( is_array($p) && isset($p[$n]) )         $p=$p[$n];
			else if( is_array($p) && ! isset($p[$n]) ) {$p='';break;}
			else break;
		}
		return $p;
	}
	#===================================================================
	# Print a array like a string
	protected static function toString($x) {
		$s='';
		foreach($x as $xx) {
			if( is_array($xx) ) $s.=self::toString($xx);
			else $s.=$xx;
		}
		return $s;
	}
	#===================================================================
	# Send a post to a another page
	public static function pageJUMP($action='',$post=[],$stop=false) {
		if($stop) dbg::seeDetails($post,"To {$action}");
		$rc=[];
		$rc[]="<!DOCTYPE html>";
		$rc[]="<html><head><title>pageJUMP</title></head><body>";
		$rc[]="<form name=local method=post action='{$action}' >";
		$list=self::generateTerminalsValues('',$post);
		if(0) dbg::seeDetails($list,"One dimensional list:");
		foreach($list as $key=>$v) {
			if( is_numeric($key) ) $key="NN[{$key}]";
			$s="<input type=hidden name='{$key}' value='{$v}' >";
			$rc[]=$s;
			// if($stop ) {
			//	$ss=htmlentities($s,ENT_QUOTES,"UTF-8");
			//	$rc[]="<div>{$ss}</div>";
			// }
		}
		if( $stop ) $rc[]="<button>GO ON</button>";
		$rc[]="</form>";
		if( ! $stop ){
			$rc[]="<script>";
			$rc[]="document.forms['local'].submit();";
			$rc[]="</script>";
		}
		$rc[]="</body></html>";
		return implode("\n",$rc);
	}
	private static function generateTerminalsValues($keya,$s) {
		$out=[];
		foreach($s as $key=>$value) {
			$ka=$keya.($keya ? "[".$key."]" : $key);
			if(is_array($value)) {
				$subout = self::generateTerminalsValues($ka,$value);
				foreach($subout as $k=>$v) $out[$k]=$v;
			}
			else $out[$ka]=$value;
		}
		return $out;
	}
}
class dbg {
	public static function SeeStr($results,$msg='') {
		return self::see($results,$msg,true);
	}
	 public static function See($results,$msg='',$string=false) {
        $rc ='';
        $rc.="<pre>";
        if($msg )$rc.=$msg;
        $rc.=print_r($results,true);
        $rc.="</pre>";
        if( ! $string ) echo $rc;
        return $rc;
    }
	public static function SeeDetailsStr($results,$msg='') {
        return self::seeDetails($results,$msg,true);
    }
	public static function SeeDetails($results,$msg='',$string=false) {
        $rc ='';
        $rc.="<details><summary>{$msg}</summary><pre>";
        $rc.=print_r($results,true);
        $rc.="</pre></details>";
        if( ! $string ) echo $rc;
        return $rc;
    }
	public static function SeeDetailsStrOpen($results,$msg='') {
        return self::seeDetailsOpen($results,$msg,true);
    }
	public static function SeeDetailsOpen($results,$msg='',$string=false) {
        $rc ='';
        $rc.="<details open><summary>{$msg}</summary><pre>";
        $rc.=print_r($results,true);
        $rc.="</pre></details>";
        if( ! $string ) echo $rc;
        return $rc;
    }
	public static function SeeOQPStr($results,$msg='') {
        return self::seeOQP($results,$msg,true);
    }
	public static function SeeOQP($results,$msg='',$string=false) {
        $rc ='';
        $rc.="<pre>";
        if($msg )$rc.=$msg;
        if( is_array($results) || is_object($results) )
            $rc.=self::onelevel($results);
        else $rc.=print_r($results,true);
        $rc.="</pre>";
        if( ! $string ) echo $rc;
        return $rc;
    }
    protected static function onelevel($a,$level=0){
        $prf=str_repeat("\t",$level);
        $rc='';
        if( is_array($a) ) {
            $rc.="{$prf} Array [\n";
            foreach( $a as $key=>$v) {
                $rc.="{$prf} [{$key}] => ";
                $rc.=self::onelevel($v,$level+1);
            }
            $rc.="{$prf}]\n";
        }
        else if (is_object($a) ) {
            $name=get_class($a);
            $rc.=" {$name} Object\n";
        }
        else {
            $rc.=" {$a}\n";
        }
        return $rc;
    }
	public static function seeD($in,$msg='No title',$level=0,$open=3) {
		if(0) dbg::seeDetails($in,"Input seeD:");
		if($level<$open) $o='open'; else $o='';
		$rc=[];
		$rc[]="<details {$o}><summary>{$msg}</summary>";
		$rc[]="<ul style='list-style-type:none;'>";
		if( is_array($in) ) foreach($in as $key=>$v) {
			if( is_array($v) ) $x=self::seeD($v,$key,$level+1,$open);
			else $x="{$key}={$v}";
			$rc[]="<li style='margin:0 1em;'>{$x}</li>";
		}
		else $rc[]="<li style='margin:0 1em;'>{$in}</li>";
		$rc[]="</ul>";
		$rc[]="</details>";
		if(0) dbg::seeDetails($rc,"Output seeD:");
		return implode("\n",$rc);
	}
	public static function seeDNotitle($in,$level=0,$open=3) {
		if($level<$open) $o='open'; else $o='';
		$rc=[];
		// $rc[]="<details {$o}><summary>{$msg}</summary>";
		$rc[]="<ul style='list-style-type:none;'>";
		foreach($in as $key=>$v) {
			if( is_array($v) ) $x=self::seeD($v,$key,$level+1,$open);
			else $x="{$key}={$v}";
			$rc[]="<li style='margin:0 1em;'>{$x}</li>";
		}
		$rc[]="</ul>";
		// $rc[]="</details>";
		if(0) dbg::seeDetails($rc,"Output seeD:");
		return implode("\n",$rc);
	}
	/*
	public static function seeD($in,$msg='No title',$level=0) {
		if(0) dbg::seeDetails($in,"Input seeD:");
		if($level<3) $o='open'; else $o=0;
		$rc=[];
		$rc[]="<details {$o}><summary>{$msg}</summary>";
		$rc[]="<ul style='list-style-type:none;'>";
		foreach($in as $key=>$v) {
			if( is_array($v) ) $x=self::seeD($v,$key,$level+1);
			else $x="{$key}={$v}";
			$rc[]="<li style='margin:0 1em;'>{$x}</li>";
		}
		$rc[]="</ul>";
		$rc[]="</details>";
		if(0) dbg::seeDetails($rc,"Output seeD:");
		return implode("\n",$rc);
	}
	*/
}
class callerprops {
	# Get the properties from the caller browser trhough the properties
	# of HTTP_USER_AGENT
	#===================================================================
	# Get the kind device is the caller
	#===================================================================
	public static function getBrowserType() {
		# The method returns 0 if the browser is on a ordinary computer
		# The method returns 1 if the browser is on a
		#	iphone, ipad, or android device
		#===============================================================
		# Check the type of browser
		$s=$_SERVER['HTTP_USER_AGENT'];
		# PAY ATTENTION THE ORDER OF IF's IS VERY IMPORTANT
		$browsertype=0;
		if     ( stripos($s,'iphone') !== false  ) $browsertype=1;
		else if( stripos($s,'gt') !== false      ) $browsertype=0;
		else if( stripos($s,'android') !== false ) $browsertype=1;
		else if( stripos($s,'ipad') !== false    ) $browsertype=1;
		return $browsertype;
	}
    #===================================================================
    # Caller characteristics
    #===================================================================
    public static function get_CallerString() {
    	$s[]=$_SERVER['REMOTE_ADDR'];
		$s[]=$_SERVER['HTTP_COOKIE'];
    	return implode(" - ",$s);
	}
    public static function get_LanguageFromBrowserCall() {
    	// initialize variables prepare user language array
		$a_languages = self::language_data();
		// self::seeDetails($a_languages,"Languages table:");
		//check to see if language is set
		if ( isset( $_SERVER["HTTP_ACCEPT_LANGUAGE"] ) ) {
			$languages = strtolower( $_SERVER["HTTP_ACCEPT_LANGUAGE"] );
			// need to remove spaces from strings to avoid error
			$languages = str_replace( ' ', '', $languages );
			$i=stripos($languages,";");
			if( $i !== false ) $languages=substr($languages,0,$i);
			$a = $a_languages_working = explode( ',', $languages);
			// self::seeDetails($a,"Languages set in the browser:");
			$a_user_languages=array();
			foreach ( $a_languages_working as $language_list ) {
				$a_temp = substr( $language_list, 0, 2 );
				if ( array_key_exists($a_temp, $a_languages ) )
					$a_user_languages[] = $a_temp;
			}
			// self::seeDetails($a_user_languages,
			// 			"Languages from the broswer:");
			// Select the first language found
			$l=$a_user_languages[0];
			switch($l) {
				default:  $languageselected=3; break;
				case 'ca':$languageselected=1; break;
				case 'es':$languageselected=2; break;
				case 'en':$languageselected=3; break;
				case 'us':$languageselected=3; break;
				case 'fr':$languageselected=4; break;
			}
		}
		// if no languages found
		else $languageselected=$this->idiomadefault;
		return $languageselected;
    }
	public static function get_countryFromBrowserCall($forbidenlist) {
    	// initialize variables prepare user language array
		$a_languages = self::language_data();
		// check to see if language is set
		if ( isset( $_SERVER["HTTP_ACCEPT_LANGUAGE"] ) ) {
			$languages = strtolower( $_SERVER["HTTP_ACCEPT_LANGUAGE"] );
			// need to remove spaces from strings to avoid error
			$languages = str_replace( ' ', '', $languages );
			$i=stripos($languages,";");
			if( $i !== false ) $languages=substr($languages,0,$i);
			$a = $a_languages_working = explode( ',', $languages);
			$iscatalegmode=false;
			foreach($a as $v) {
				if( in_array($v,$forbidenlist) ) {
					$iscatalegmode=true;
					break;
				}
			}
		}
		// if no languages found
		else $iscatalegmode=false;
		// echo "Language automatically selected[{$languageselected}]";
		return $iscatalegmode;
    }
	public static function getHTMLLang($idioma) {
    	switch($idioma) {
			default:  	$l=''; break;
			case 1: 	$l='lang="ca"'; break;
			case 2: 	$l='lang="es"'; break;
			case 3: 	$l='lang="en"'; break;
    		case 4: 	$l='lang="fr"'; break;
    	}
		return $l;

    }
	public static function language_data() {
		# pack abbreviation/language array
		# important note: you must have the default
		# language as the last item
		# in each major language, after all the
		# en-ca type entries, so en would be last in that case
		$a_languages = array(
			'af' => 'Afrikaans',
			'sq' => 'Albanian',
			'am' => 'Amharic',
			'ar-dz' => 'Arabic (Algeria)',
			'ar-bh' => 'Arabic (Bahrain)',
			'ar-eg' => 'Arabic (Egypt)',
			'ar-iq' => 'Arabic (Iraq)',
			'ar-jo' => 'Arabic (Jordan)',
			'ar-kw' => 'Arabic (Kuwait)',
			'ar-lb' => 'Arabic (Lebanon)',
			'ar-ly' => 'Arabic (Libya)',
			'ar-ma' => 'Arabic (Morocco)',
			'ar-om' => 'Arabic (Oman)',
			'ar-qa' => 'Arabic (Qatar)',
			'ar-sa' => 'Arabic (Saudi Arabia)',
			'ar-sy' => 'Arabic (Syria)',
			'ar-tn' => 'Arabic (Tunisia)',
			'ar-ae' => 'Arabic (U.A.E.)',
			'ar-ye' => 'Arabic (Yemen)',
			'ar' => 'Arabic',
			'hy' => 'Armenian',
			'as' => 'Assamese',
			'az' => 'Azeri',
			'eu' => 'Basque',
			'be' => 'Belarusian',
			'bn' => 'Bengali',
			'bs' => 'Bosnian',
			'bg' => 'Bulgarian',
			'my' => 'Burmese',
			'ca' => 'Catalan',
			'zh-cn' => 'Chinese (China)',
			'zh-hk' => 'Chinese (Hong Kong SAR)',
			'zh-mo' => 'Chinese (Macau SAR)',
			'zh-sg' => 'Chinese (Singapore)',
			'zh-tw' => 'Chinese (Taiwan)',
			'zh' => 'Chinese',
			'hr' => 'Croatian',
			'cs' => 'Czech',
			'da' => 'Danish',
			'div' => 'Divehi',
			'nl-be' => 'Dutch (Belgium)',
			'nl' => 'Dutch (Netherlands)',
			'en-au' => 'English (Australia)',
			'en-bz' => 'English (Belize)',
			'en-ca' => 'English (Canada)',
			'en-cb' => 'English (Caribbean)',
			'en-in' => 'English (India)',
			'en-ie' => 'English (Ireland)',
			'en-jm' => 'English (Jamaica)',
			'en-nz' => 'English (New Zealand)',
			'en-ph' => 'English (Philippines)',
			'en-za' => 'English (South Africa)',
			'en-tt' => 'English (Trinidad)',
			'en-gb' => 'English (United Kingdom)',
			'en-us' => 'English (United States)',
			'en-zw' => 'English (Zimbabwe)',
			'en' => 'English',
			'us' => 'English (United States)',
			'et' => 'Estonian',
			'fo' => 'Faeroese',
			'fa' => 'Farsi',
			'fi' => 'Finnish',
			'fr-be' => 'French (Belgium)',
			'fr-ca' => 'French (Canada)',
			'fr-lu' => 'French (Luxembourg)',
			'fr-mc' => 'French (Monaco)',
			'fr-ch' => 'French (Switzerland)',
			'fr' => 'French (France)',
			'mk' => 'FYRO Macedonian',
			'gd' => 'Gaelic',
			'ka' => 'Georgian',
			'de-at' => 'German (Austria)',
			'de-li' => 'German (Liechtenstein)',
			'de-lu' => 'German (Luxembourg)',
			'de-ch' => 'German (Switzerland)',
			'de' => 'German (Germany)',
			'el' => 'Greek',
			'gn' => 'Guarani (Paraguay)',
			'gu' => 'Gujarati',
			'he' => 'Hebrew',
			'hi' => 'Hindi',
			'hu' => 'Hungarian',
			'is' => 'Icelandic',
			'id' => 'Indonesian',
			'it-ch' => 'Italian (Switzerland)',
			'it' => 'Italian (Italy)',
			'ja' => 'Japanese',
			'kn' => 'Kannada',
			'ks' => 'Kashmiri',
			'kk' => 'Kazakh',
			'km' => 'Khmer',
			'kok' => 'Konkani',
			'ko' => 'Korean',
			'kz' => 'Kyrgyz',
			'lo' => 'Lao',
			'la' => 'Latin',
			'lv' => 'Latvian',
			'lt' => 'Lithuanian',
			'ms-bn' => 'Malay (Brunei)',
			'ms-my' => 'Malay (Malaysia)',
			'ms' => 'Malay',
			'ml' => 'Malayalam',
			'mt' => 'Maltese',
			'mi' => 'Maori',
			'mr' => 'Marathi',
			'mn' => 'Mongolian (Cyrillic)',
			'ne' => 'Nepali (India)',
			'nb-no' => 'Norwegian (Bokmal)',
			'nn-no' => 'Norwegian (Nynorsk)',
			'no' => 'Norwegian (Bokmal)',
			'or' => 'Oriya',
			'pl' => 'Polish',
			'pt-br' => 'Portuguese (Brazil)',
			'pt' => 'Portuguese (Portugal)',
			'pa' => 'Punjabi',
			'rm' => 'Rhaeto-Romanic',
			'ro-md' => 'Romanian (Moldova)',
			'ro' => 'Romanian',
			'ru-md' => 'Russian (Moldova)',
			'ru' => 'Russian',
			'sa' => 'Sanskrit',
			'sr' => 'Serbian',
			'sd' => 'Sindhi',
			'si' => 'Sinhala',
			'sk' => 'Slovak',
			'ls' => 'Slovenian',
			'so' => 'Somali',
			'sb' => 'Sorbian',
			'es-ar' => 'Spanish (Argentina)',
			'es-bo' => 'Spanish (Bolivia)',
			'es-cl' => 'Spanish (Chile)',
			'es-co' => 'Spanish (Colombia)',
			'es-cr' => 'Spanish (Costa Rica)',
			'es-do' => 'Spanish (Dominican Republic)',
			'es-ec' => 'Spanish (Ecuador)',
			'es-sv' => 'Spanish (El Salvador)',
			'es-gt' => 'Spanish (Guatemala)',
			'es-hn' => 'Spanish (Honduras)',
			'es-mx' => 'Spanish (Mexico)',
			'es-ni' => 'Spanish (Nicaragua)',
			'es-pa' => 'Spanish (Panama)',
			'es-py' => 'Spanish (Paraguay)',
			'es-pe' => 'Spanish (Peru)',
			'es-pr' => 'Spanish (Puerto Rico)',
			'es-us' => 'Spanish (United States)',
			'es-uy' => 'Spanish (Uruguay)',
			'es-ve' => 'Spanish (Venezuela)',
			'es' => 'Spanish (Traditional Sort)',
			'sx' => 'Sutu',
			'sw' => 'Swahili',
			'sv-fi' => 'Swedish (Finland)',
			'sv' => 'Swedish',
			'syr' => 'Syriac',
			'tg' => 'Tajik',
			'ta' => 'Tamil',
			'tt' => 'Tatar',
			'te' => 'Telugu',
			'th' => 'Thai',
			'bo' => 'Tibetan',
			'ts' => 'Tsonga',
			'tn' => 'Tswana',
			'tr' => 'Turkish',
			'tk' => 'Turkmen',
			'uk' => 'Ukrainian',
			'ur' => 'Urdu',
			'uz' => 'Uzbek',
			'vi' => 'Vietnamese',
			'cy' => 'Welsh',
			'xh' => 'Xhosa',
			'yi' => 'Yiddish',
			'zu' => 'Zulu'
		);
		return $a_languages;
	}
}
class session {
	private $lastsession=1800; //How long the session last in minutes
	private $sessioninit='';
	public function __construct($session) {
		$this->sessioninit=$session;
		if( $session ) $this->sessionSet($session);
	}
	public function sessionSet($what,$last='') {
		If($last) $this->lastsession=$last;
		if( strtolower($what) == 'new') $this->resetSession();
		else if( $what ) 				$this->controlSession($what);
	}
	#===================================================================
    # For the Session control
    #===================================================================
    public static function CalculateSignature($firma='') {
    	$x='';
    	foreach($_SESSION as $k=>$v)
    		if(strtolower($k) != 'signature') $x.="{$k}={$v}";
        $x.=$firma ? $firma : self::firma;
        $sig=sha1($x);
        return array($sig,$x);
    }
	public function controlSession($wheretofail) {
		$trz=false;
		if($trz) dbg::seeDetails($_COOKIE,"Arriving COOKIES:");
		session_start();
		$que=session_status();
		if($trz) {
			$quetrans=['PHP_SESSION_DISABLED','PHP_SESSION_NONE',
				'PHP_SESSION_ACTIVE'
			];
			$quetr='STATUS NOT FOUND';
			if( array_key_exists($que,$quetrans) )$quetr=$quetrans[$que];
			echo "SESSION_STATUS[{$quetr}]";
		}
		if( $que == PHP_SESSION_NONE || $que == PHP_SESSION_DISABLED )
				if(!$trz) self::DestroySession($wheretofail);
		if($trz) dbg::seeDetails($_SESSION,"The SESSION arrived:");
		if( !$trz && ! array_key_exists('signature', $_SESSION) )
			self::DestroySession($wheretofail);
		#-------------------------------------------------------------------
		# Check the signature
		list($sign,$frase)=self::CalculateSignature();
		if($trz) {
			dbg::seeDetails($_SESSION,"The SESSION data:");
			echo "Calculated signature[{$sign}]";
			echo "Sobre la frase[{$frase}]";
		}
		if(! $trz && $_SESSION['signature']!= $sign)
			self::DestroySession($wheretofail);
		#-------------------------------------------------------------------
		# Memorize the session arrived
		$this->previous=$_SESSION;
		$this->previous['sigcal']=$sign;
		$this->previous['frase']=$frase;
		#-------------------------------------------------------------------
		# Check the expiration
		$previous=new DateTime($_SESSION['when']);
		$now=new DateTime();
		$d=$now->diff($previous);
		$diferencia=0;
		if( $d->y || $d->m || $d->d || $d->h ) $diferencia=86400;
		$diferencia += $d->i*60+$d->s;
		if( ! $trz && $diferencia > $this->lastsession )
			self::DestroySession($wheretofail);
		#-------------------------------------------------------------------
		# Recalculate the signature
		$_SESSION['when']=Date("Y-m-d H:i:s");
		list($sig,$x)=self::CalculateSignature();
		$_SESSION['signature']=$sig;
	}
    public static function showSession( $OK=true ) {
        $x = '';
        $cookies=ini_get("session.use_cookies");
		$x .= "<hr>";
        $x.="COOKIES ";
        if( $cookies ) {
            $s=session_id();
            $x.=" YES Id:[$s] WITH Parameters:<br>";
            $params = session_get_cookie_params();
            $x.="<pre>".print_r($params,true)."</pre>";
        } else {
            $x.="NO <br>";
        }
        $x .= "<hr>";
        $s= $OK ? "OK" : "THERE ARE ERRRORS IN THE SESSION";
		if (isset($_SESSION)){
        	$x .= "NEW SESSION VALUES:$s:<br>";
        	$x .="<pre>".print_r($_SESSION,true)."</pre>";
			$x .= "<hr>";
		}
        return $x;
    }
    public function resetSession() {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
		$this->previous=[];
    }
    public static function DestroySession($wheretofail,$x='',$idioma=3){
    	$trz=false;

		if( $trz ) {
			echo self::showSession();
			return "";
		}
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        if( $x=='' ) $x="Unauthorized User";
        # Finalmente, destruir la sesión.
        $msg="SESSION EXPIRED. NEW LOGIN IS NEEDED";
		$parar=false;
		$l=callerprops::getHTMLLang($idioma);
        $rc = '';
        $rc.= "\n<!DOCTYPE html> ";
        $rc.= "<html {$l}><head>\n";
        $rc.= "  <meta charset='UTF-8'>";
        $rc.= "  <meta http-equiv=\"Content-Type\"  ";
        $rc.= "        content=\"text/html; charset=iso-8859-1\">\n";
        $rc.= "</head><body>\n";
        $rc.= "<form name=local action=\"{$wheretofail}\"method=post>\n";
        $rc.= "<input name=error type=hidden value=\"{$msg}\">\n";
        if( $parar )$rc .= "<button>Go on</button>";
        $rc.= "</form>\n";
        $rc.= "</body>\n";
		if( ! $parar )
			$rc .= "<script>document.forms[0].submit()</script>";
        $rc.= "</html>\n";
        echo $rc;
        exit();
    }
	public static function getUser(){
		$usr='anonymous';
		if(       isset($_SESSION['usr']) ) $usr=$_SESSION['usr'];
		else if ( isset($_POST['usr'])    ) $usr=$_POST['usr'];
		return $usr;
	}
}
class sessionV1 {
	// it pretends open always a session and make the control
	// through the user that is changed with a login
	#===================================================================
    # Secret keyword for the calculation of the data transfer signature.
	private $default=[
		'dataini'=>['usr'=>'anonymous', 'idioma'=>3, 'token'=>'',
			'weblogin'=>'to',
		],
		'firma'=>gnrl::firma,
		'lifetime'=>1800,
		'params'=>[],
	];
	private $prp;
	private $trz;
	public function __construct($prp=[]) {
		$this->trz=[];
		$this->default['dataini']['weblogin']=$_SERVER['SCRIPT_NAME'];
		$this->default['params']= session_get_cookie_params();
		$this->prp=gnrl::MergeProperties($this->default, $prp);
		$this->trz[]=dbg::seeDetails($this->prp,"PROPERTIES:",true);
		#---------------------------------------------------------------
		if( session_status() == PHP_SESSION_DISABLED )
			die("Session are disabled");
		if( ! session_start() )
			die("Session cannot be started");
		$this->setUpCookie();
		$this->trz[]="STATUS=".($st=session_status());
		if( $st == PHP_SESSION_NONE )
			$this->init();
		else
			if( ! $this->check_Signature() ) $this->logout();
	}
	public function init() {
		$_SESSION=$this->prp['dataini'];
		list($_SESSION['signature'],$txt)=$this->calculateSignature();
    }
	public function login($data) {
		$_SESSION=gnrl::mergeProperties($_SESSION,$data);
		list($_SESSION['signature'],$txt)=$this->calculateSignature();
	}
	public function logout() {
		$this->trz[]="Reset the session:".session_id();
		session_destroy();
		$this->resetCookie();
		$_SESSION=[];
		# --------------------------------------------------------------
		# Initialize again the new session
		session_start();
		$this->trz[]="New session after logout:".session_id();
		$this->setUpCookie();
		$this->init();
	}
	public function setUpCookie() {
		 if (ini_get("session.use_cookies")) {
		 	$this->time=time()+$this->prp['lifetime'];
			$t=new DateTime();
			$t->setTimestamp($this->time);
			$f=$t->format('c');
			$this->trz[]="Expire at {$f}";
           	$params=$this->prp['params'];
            setcookie(session_name(), session_id(),
            		$this->time,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
		else die( 'session cookies disabled');
	}
	public function resetCookie() {
		$params=$this->prp['params'];
		setcookie(session_name(), FALSE, 0,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
	}
	public function CalculateSignature() {
    		$x='';
    		foreach($_SESSION as $k=>$v)
    			if(strtolower($k) != 'signature') $x.="{$k}={$v}";
        $x.=gnrl::toISO($this->prp['firma']);
        $sig=sha1($x);
        return array($sig,$x);
    }
	public function show() {
		if( isset($_COOKIE) ) $c=$_COOKIE; else $c=[];
		if( isset($_SESSION) ) $s=$_SESSION; else $s=[];
		$d=['cookies'=>$c,'session'=>$s,
			'cookparam'=>session_get_cookie_params(),
			'trace'=>$this->trz
		];
		$x=dbg::seeDetails($d,"SESSION AND COOKIES",true);
		return $x;
    }
	private function check_Signature() {
		list($sig,$txt) = $this->CalculateSignature();
		$rc=false;
		if( isset($_SESSION['signature'])) {
			$rc=$sig==$_SESSION['signature'];
		}
		$this->trz[]="Signatures comparison: [{$rc}]";
		return $rc;
	}
	public static function getUser(){
		$usr='anonymous';
		if(isset($_SESSION['usr']) ) $usr=$_SESSION['usr'];
		return $usr;
	}
	public static function get($name){
		$rc='Not in sesssion';
		if( isset($_SESSION[$name]) ) $rc=$_SESSION[$name];
		return $rc;
	}
}
class sessionV3 {
	private function  aim() {
		/*
		The class pretends to use the session for storing the
		permanent memory of one application
		wheteher it does or doesn't a log in to execute it.
		It has two ways of make the logout:
			a) The logout is performed by calling to a website from
				which yhe user will need to login again to come back
				inside the app.
			b) The logout is performed changing to the anonymous user
				and accessing to the places suitable for that user.
		*/
	}
	#===================================================================
    # Secret keyword for the calculation of the data transfer signature.
	const JUMP=1; // Indicates making the logout by jumping to a page
	const CHGUSER=2; // Indicates making the logout by changing user
	const NOACT=3; // No action is taken
	private function def(){
		$wb=$_SERVER['SCRIPT_NAME'];
		$default=[
			'mode'=>self::NOACT,
			'public'=>[
				'usr'=>'anonymous', 'idioma'=>3, 'weblogin'=>$wb,
			],
			'firma'=>gnrl::firma,
			'lifetime'=>1800,
			// It's only needed if you wanted
			// to change the values coming in
			'params'=>[],
		];
		return $default;
	}
	private $prp;
	private $trz;
	public function __construct($prp=[]) {
		$this->trz=[];
		$this->prp=gnrl::MergeProperties($this->def(), $prp);
		$this->trz[]=dbg::seeDetails($this->prp,"PROPERTIES:",true);
		#---------------------------------------------------------------
		if( session_status() == PHP_SESSION_DISABLED )
			die("Session are disabled");
		if( ! session_start() )
			die("Session cannot be started");
		$s=['Name'=>session_name(),'Status'=>session_status(),
			'Id'=>session_id(),
		];
		if(0) dbg::seeDetails($s,"Basic session param:");
		if(0) dbg::seeDetails($_SESSION,"Session:");
		if(0) dbg::seeDetails($_COOKIE,"Cookies:");
		if(0) dbg::seeDetails($this->prp,"Parameters of the class:");
		$this->setUpCookie();
		$this->trz[]="STATUS=".($st=session_status());
		if( $st == PHP_SESSION_NONE ) $this->init();
		else if( ! $this->check_Signature() ) $this->logout();
		if(0) dbg::seeDetails($this->trz,"Traza:");
		$this->buildMemoryOfSession();
		// exit();
	}
	#===================================================================
	# Prepare the session if there is no any
	public function init() {
		$_SESSION=$this->prp['public'];
		$this->setSignature();
    }
	#===================================================================
	# If the login is OK the data is put in the SESSION array
	public function login($data) {
		$_SESSION=gnrl::mergeProperties($_SESSION,$data);
		list($_SESSION['signature'],$txt)=$this->calculateSignature();
	}
	public function formerMemory(){ return $this->backup;}
	private $backup; // Makes a backup of the memory before changing it
	protected function buildMemoryOfSession() {
		if(0) dbg::seeDetails($_SESSION,"Session here:");
		$this->backup=$_SESSION;
		foreach($this->memory() as $key=>$def) {
			if( isset($_SESSION[$key]) )
				$v=isset($_POST[$key]) ? $_POST[$key] : $_SESSION[$key];
			else
				$v=isset($_POST[$key]) ? $_POST[$key] : $def;
			$memory[$key]=$_SESSION[$key]=$v;
		}
		$x=$this->calculateSignature();
		$_SESSION['signature']=$x[0];
	}
	//==================================================================
	// The function make the logout
	public function logout() {
		if( $this->prp['mode'] == self::JUMP )
			$this->logoutJUMP();
		else if($this->prp['mode'] == self::CHGUSER)
			$this->logoutCHGUSER();
		else if( $this->prp['mode'] == self::NOACT)
			$this->logoutNOACT();
		else die("WRONG SESSION MODE");
	}
	private function logoutCHGUSER() {
		$this->trz[]="Reset the session:".session_id();
		// session_destroy(); // It seems no necessary
		// $this->resetCookie(); Below the cookie is set again
		$_SESSION=[];
		# --------------------------------------------------------------
		# Initialize again the new session
		session_start();
		$this->trz[]="New session after logout:".session_id();
		$this->setUpCookie();
		$this->init();
	}
	private function logoutJUMP($action='') {
		// Destroy the actual session
		// session_destroy();
		// $this->resetCookie();
		$_SESSION=[];
		// Jump to the website
		if( $action == '' ) $action=appData::getParam('session');
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
		$_SESSION=[];
		$this->buildMemoryOfSession();
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
			if(0) dbg::seeDetails($params,"Cookie params before set:");
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
	public function setSignature($data=[]) {
		foreach($data as $key=>$v) $_SESSION[$key]=$v;
		list($_SESSION['signature'],$txt)=$this->calculateSignature();
	}
	public function CalculateSignature() {
    		$x='';
		$ses=$_SESSION;
		array_walk_recursive($ses,function($item,$clave) use ($x){
			if(strtolower($clave)!='signature') $x.="{$clave}={$item}";
		});
        $x.=gnrl::toISO($this->prp['firma']);
        $sig=sha1($x);
        return array($sig,$x);
    }
	private function check_Signature() {
		list($sig,$txt) = $this->CalculateSignature();
		$rc=false;
		if( isset($_SESSION['signature'])) {
			$rc=$sig==$_SESSION['signature'];
		}
		$this->trz[]="Signatures comparison: [{$rc}]";
		if( 0 ) {
			echo $this->show();
			die("No matching signatures");
		}
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
		if( isset($_COOKIE) ) $c=$_COOKIE; else $c=[];
		if( isset($_SESSION) ) $s=$_SESSION; else $s=[];
		$d=['cookies'=>$c,'session'=>$s,
			'expire'=>session_cache_expire(),
			'cookparam'=>session_get_cookie_params(),
			'trace'=>$this->trz
		];
		$x=dbg::seeDetails($d,"SESSION AND COOKIES",true);
		return $x;
    }

}
class format {
	#===================================================================
	# Formats converters
	#===================================================================
	public static function normalToIsoDateTime($f) {
		if($f) {
			$fd=substr($f,0,10);
			$f=self::normalToIsoDate($fd).substr($f,10);
		}
		return $f;
	}
	public static function normalToIsoDate($f) {
		if( $f )
			return substr($f,6,4).'-'.substr($f,3,2).'-'.substr($f,0,2);
		else return $f;
	}
	public static function isoToNormalDateTime($f) {
		if($f){
			$fd=substr($f,0,10);
			$f=self::isoToNormalDate($fd).substr($f,10);
		}
		return $f;
	}
	public static function isoToNormalDate($f) {
		if($f)
			return substr($f,8,2).'-'.substr($f,5,2).'-'.substr($f,0,4);
		else return $f;
	}
	public static function compactDate($f) {
		$ff=substr($f,0,10);
		$fa=explode('-',$ff);
		if( count($fa) == 3 ){
			$fc='';
			foreach($fa as $i=>$faf ) {
				if($faf>1000) $faf=$faf%100;
				$fc.=$faf;
			}
			$f=$fc;
		}
		return $f;
	}
	public static function getNumberFromValue($string) {
		$s=str_replace(',','.',trim($string));
		$i=strripos($s,'.');
		$s=str_replace('.','',substr($s,0,$i)).substr($s,$i);
		if( is_numeric($s)) $s=floatval($s); else $s='NULL';
		return $s;
	}
	public static function fmtMoney($v,$f="%6.2f") {
		$nv=sprintf($f,$v/100);
		if(  $v == 0 ) $n='';
		else if( $v<0 )  $n="<span style='color:red'>$nv</span>" ;
		else $n="<span style='color:black'>$nv</span>" ;
		return $n;
	}
	public static function fmtMoneyPoints($v,$f="%6.2f") {
		$nvr=sprintf($f,$v/100);
		$nvc=str_replace('.',',',$nvr);
		$nv=self::putPoints($nvc);
		if(  $v == 0 ) $n='';
		else if( $v<0 )  $n="<span style='color:red'>$nv</span>" ;
		else $n="<span style='color:black'>$nv</span>" ;
		return $n;
	}
	public static function fmtNumber($v,$f='%d') {
		$nv=sprintf($f,$v);
		if(  $v == 0 ) $n='';
		else if( $v<0 )  $n="<span style='color:red'>$nv</span>" ;
		else $n="<span style='color:black'>$nv</span>" ;
		return $n;
	}
	public static function fmtNumberPoints($v,$f='%d') {
		$nvr=sprintf($f,$v);
		$nvc=str_replace('.',',',$nvr);
		$nv=self::putPoints($nvc);
		if(  $v == 0 ) $n='';
		else if( $v<0 )  $n="<span style='color:red'>$nv</span>" ;
		else $n="<span style='color:black'>$nv</span>" ;
		return $n;
	}
	public static function putPoints($x) {
		$x=trim($x);
		if( ($end=strpos($x,',')) === false ) $end=strlen($x);
		$xnew=substr($x,$end);
		$k=0; $x=trim($x);
		for($i=$end;$i;$i-- ) {
			if( $k % 3 == 0 && $k ) $xnew='.'.$xnew;
			$k++;
			$ch=substr($x,$i-1,1);
			$xnew=$ch.$xnew;
		}
		return $xnew;
	}
	public static function getDateFromValue($string){
		$s=[]; $token='';$rc='';
		for($i=0;$i<strlen($string); $i++) {
			$ch=substr($string,$i,1);
			if( '0'<=$ch && $ch<='9' ) $token.=$ch;
			else if( $ch=='-' || $ch=='/' ) {
				$s[]=$token;
				$token='';
			}
			else {
				$s=''; break;
			}
		}
		if($token) $s[]=$token;
		if( $s && count($s) > 1 && count($s) < 4) {
			if( count($s) == 2 ) $s[]=Date('Y');
			$value=$s[0].'-'.$s[1].'-'.$s[2];
			$f=DateTime::createFromFormat('d-m-Y',$value);
			if( $f !== false ) {
				$rc=date_format($f,'Y-m-d');
			}
		}
		else $rc='NULL';
		return $rc;
	}
	public static function toseecode($s) {
		$ss='';
		for($i=0;$i<strlen($s);$i++) {
			$ch=substr($s,$i,1);
			$ss.=sprintf("%2x(%s)|",ord($ch),$ch);
		}
		return $ss;
	}
	public static function toDecHours($hour) {
		// Convert an day hour expressed as hh?mm in decimal format
		$h=0.0;
		if( preg_match_all("/[0-9]+/",$hour,$sub) ) {
			if(0) dbg::seeDetails($sub,"For hour {$hour}");
			$hh=$sub[0][0];
			$mm=$sub[0][1];
			$h=$hh+$mm/60;
		}
		return $h;
	}
	#===================================================================
    # function to transform a string from a form input to a php array
    #===================================================================
	public static function stringToArray($sraw) {
		$s=explode('|',$sraw);
		$t=[];
		foreach($s as $ss) {
			if( ($i=strpos($ss,'=')) !== false ) {
				$key=substr($ss,0,$i);
				$value=substr($ss,$i+1);
				$v=explode('&',$value);
				if(count($v) == 1 ) $t[$key]=$value; else $t[$key]=$v;
			}
			else $t[]=$ss;
		}
		# self::see($t,"From {$sraw}");
		return $t;
	}
	public static function fromKeyboardToArray($praw) {
		//--------------------------------------------------------------
		// Compile the properties in string edited in a textarea
		if( is_string($praw) ){
			$pvl=str_replace("\r\n","|",$praw);
			$plines=explode("|",$pvl);
			$p=[];
			foreach($plines as $pv) {
				$parts=explode("=",$pv);
				if( array_key_exists(1,$parts)) $valor=$parts[1];
				else $valor='???';
				$p[$parts[0]]=$valor;
			}
		}
		else if( is_array($praw)) {
			array_walk_recursive($praw,function(&$v){
				if( is_string($v) ){
					$v=self::fromKeyboardToArray($v);
				}
			});
		}
		else $p='error=Ilegal tipo';
		return $p;
	}
	public static function fromArrayToKeyboard($praw,$toedit=true) {
		if( is_array($praw) ) {
			$oqpa=[];
			foreach($praw as $p=>$v) $oqpa[]=trim($p)."=".$v;
			if( $toedit ) 	$oqp=implode("\r\n",$oqpa);
			else 			$oqp=implode("<br>",$oqpa);
		}
		else $oqp=$praw;
		return $oqp;
	}
	#===================================================================
    # function to prepare a index for DB indexing
    #===================================================================
    public static function toDBIndex($number,$p) {
    	$result=[];
    	foreach($p as $propietat=>$element) {
    		if( is_array($element)) {
    			foreach($element as $i=>$subelement) {
    				$v=self::oneIndexMember($number,$propietat,
						$subelement);
					if( $v !== false ) {
						$v['ind']=$i;
						$result[]=$v;
					}
    			}
    		}
			else {
				$v=self::oneIndexMember($number,$propietat,$element);
				if( $v !== false) $result[]=$v;
			}
    	}
		return $result;
    }
	public static function oneIndexMember($number,$propietat,$element) {
		// The structure of any index
    	// 'id'=>'INT',
		// 'propietat'=>'VC20',
		// 'ind'=>'INT',
		// 'value'=>'VCFF',
		// 'number'=>'DOUB',
		// 'time'=>'DATETIME'
		$v=[
			'id'=>$number,
			'propietat'=>$propietat,
			'ind'=>0,
			'value'=>'',
			'number'=>'NULL',
			'time'=>'NULL',
		];
		if (is_numeric($element) ) {
			$num=(double)($element);
			$text=(string) $num;
			$v['value']=$text;
			$v['number']=$num;
			$result[]=$v;
		}
		else if( is_string($element)  ) {
			if( self::is_date($element) ) {
				$v['value']=$normal=self::standardDate($element);
				$v['time']=self::normalToIsoDate($normal);
			}
			else {
				$v['value']=$normal=$element;
			}
		}
		else $v=false;
		return $v;
	}
	public static function is_date($st) {
		$formats=[ 'd-m-Y','d/m/Y'];
		$is=false;
		$st=substr($st,0,10); // Delete the part time
		foreach($formats as $f){
			$d=date_create_from_format($f,$st);
			if( $d !== false ) {
				$is=true;
				break;
			}
		}
		return $is;
	}
	public static function standardDate($string){
		$string=substr($string,0,10);
		if( self::is_date($string) ) {
			// Split up the string
			$s=[]; $token='';$rc='';
			for($i=0;$i<strlen($string); $i++) {
				$ch=substr($string,$i,1);
				if( '0'<=$ch && $ch<='9' ) $token.=$ch;
				else if( $ch=='-' || $ch=='/' ) {
					$s[]=$token;
					$token='';
				}
				else {
					$s=''; break;
				}
			}
			if($token) $s[]=$token;
			if( $s && count($s) > 1 && count($s) < 4) {
				if( count($s) == 2 ) $s[]=Date('Y');
				$value=$s[0].'-'.$s[1].'-'.$s[2];
				$f=DateTime::createFromFormat('d-m-Y',$value);
				if( $f !== false ) $rc=date_format($f,'d-m-Y');
				else $rc='NULL';
			}
			else $rc='NULL';
		}
		else $rc='NULL';
		return $rc;
	}
	#===================================================================
    # Generate a xml from a php array list the name of the first is
    # optional by default is set to 'root'
    #===================================================================
    public static function sendAsXML($list,$firstnode='root') {
        $fmt=self::generar_XML(array($firstnode=>$list));
        header("Content-type: text/xml");
        echo "<?xml version='1.0' encoding='UTF-8'?>";
        echo $fmt;
        exit();
	}
	public static function getXML($list,$firstnode='root') {
		$fmt=self::generar_XML(array($firstnode=>$list));
        $s= "<?xml version='1.0' encoding='UTF-8'?>\n";
       return $s.$fmt;
	}
	public static function getOnlyXMLData($list,$firstnode='root') {
		$fmt=self::generar_XML(array($firstnode=>$list));
       return $fmt;
	}
    private static function generar_XML($o,$nivel=0) {
    	$x=str_repeat("\t", $nivel);
        $rc='';
        foreach($o as $key=>$valor) {
            if( is_numeric($key) ) {
                $keyt="Numeric";
                $at="order=\"{$key}\"";
            }
            else {
                $keyt=$key;
                $at="";
            }
            $rc.="$x<$keyt $at>\n";
            if( is_array($valor )) {
                $rc.=self::generar_XML($valor,$nivel+1);
            }
            else $rc.=$x."\t{$valor}\n";
            $rc.="{$x}</$keyt>\n";
        }
        return $rc;
    }
	public static function beautifyXML($s) {
		$iden ='..';
		$trz=false;
		if($trz) dbg::seeDetails(htmlentities($s,ENT_QUOTES,'utf-8'),
															"Input:");
		$ixml=stripos($s,"<?xml");$l=strlen($s);
		$ixmlm= $ixml === false ? $ixmlm="NO XML":$ixml;
		if($trz) dbg::seeDetails($ixml,"XML at {$ixmlm} sobre {$l}:");
		$bxml=[];
		if( $ixml !== false ) {
			$xml=substr($s,$ixml);
			if($trz) dbg::seeDetails(htmlentities($xml,ENT_QUOTES,
													'utf-8'),"XML:");
			#-----------------------------------------------------------
			# Find the first line
			$ilast=stripos($xml,"?>");
			if($ilast !== false ) {
				$bxml[]=self::chunking($iden,0,substr($xml,0,$ilast+2));
				$xml=trim(substr($xml,$ilast+2));
			}
			#-----------------------------------------------------------
			# Displaying the true data of XML
			$IN='in';$OUT='out';
			$stack=[];$level=0; $estado=$OUT;$token='';
			for($i=0;$i<strlen($xml);$i++) {
				$ch=substr($xml,$i,1);
				//------------------------------------------------------
				if($trz) {
					$e=[
						'i'=>$i,
						'ch'=>htmlentities($ch,ENT_QUOTES,'utf-8'),
						'estado'=>$estado,
						'token'=>$token,
						'stack'=>$stack,
						'level'=>$level,
					];
					dbg::seeDetails($e,"Char[$i]:");
				}
				//------------------------------------------------------
				switch ($estado) {
				case $OUT:
					if($ch == '<') {
						if($token)
							$bxml[]=self::chunking($iden,$level,$token);
						$estado=$IN;
						$token=$ch;
					}
					else $token.=$ch;
					break;
				case $IN:
					if($ch == '>') {
						$token.=$ch;
						if( substr($token,1,1) == '/'){
							$parent="<".substr($token,2,-1);
							$found=false;
							for($j=count($stack)-1;$j>=0;$j--) {
								$st=substr($stack[$j],0,
													strlen($parent));
								if( $st == $parent){
									$found=true;
									for($level;$level>$j;$level--)
										array_pop($stack);
									$bxml[]=
									   self::chunking($iden,$level,
									   						$token);
									break;
								}
							}
							if( ! $found ) {
								$s='ORPHAN'.$token;
								$bxml[]=self::chunking($iden,$level,$s);
							}
						}
						else {
							$bxml[]=self::chunking($iden,$level,$token);
							array_push($stack,$token);
							$level++;
						}
						$estado=$OUT;
						$token='';
					}
					else $token.=$ch;
				}
			}
		}
		else $ixml=strlen($s);
		return substr($s,0,$ixml).implode("",$bxml);
	}
	public static function chunking($iden,$level,$token,$chunksize=80) {
		$trz=false;
		$l=strlen($token);
		$status=[$token,$level,$iden,$chunksize];
		if( $trz ) dbg::seeDetails($status,"Entro [$l]");
		$ch=[];$if= -1;
		while( $if < strlen($token) ) {
			$ii=$if+1;
			$c=substr($token,$ii,$chunksize);
			$ipch=[' ',',','.',"\t"];
			$i=0;
			foreach($ipch as $char) {
				$ilf=strrpos($c,$char);
				if( $ilf !== false && $ilf > $i) $i=$ilf;
			}
			if( $i==0) $i=strlen($c);
			$i=strlen($c);
			$ch[]=substr($c,0,$i);
			if($trz) dbg::seeDetails($ch,"Chunk progression [{$ii}]:");
			$if=$ii+$i;
		}
		$pre=str_repeat($iden,$level > 0 ? $level : 0);
		if( $trz ) dbg::seeDetails($ch,"Surto:");
		$lines='';
		foreach($ch as $chu) {
			$lines.=$pre.$chu."\n";
			$sp=str_repeat(' ',strlen($iden));
			$pre=str_repeat($sp,$level+2);
		}
		return $lines;
	}
	public static function wrapSOAP($action,$body,$header=[]) {
		$url="http://schemas.xmlsoap.org/soap/";
		$HEADER=[];
		$bodyxml=self::getOnlyXMLData($body,$action);
		if( count($header) ) {
			$headerxnl=self::getOnlyXMLData($header);
			$HEADER[]="<SOAP-ENV:Header>";
			$HEADER[]=$headerxml;
			$HEADER[]="<SOAP-ENV:Header>";
		}
		$rc=[];
		$rc[]="<SOAP-ENV:Envelope xmlns:SOAP-ENV='{$url}envelope/'>";
		foreach($HEADER as $h ) $r[]=$h;
		$rc[]="<SOAP-ENV:Body>";
		$rc[]=$bodyxml;
		$rc[]="</SOAP-ENV:Body>";
		$rc[]="</SOAP-ENV:Envelope>";
		return implode("\n",$rc);
	}
	public static function simpleXMLToArray($sxmlobj) {
		$x=self::sxmltoarray($sxmlobj);
		return self::deletenodelevel($x);
	}
	private static function deletenodelevel($x,$nivel=0) {
		$xx=[];
		$n=$nivel+1;
		foreach($x as $key=>$v) {
			if( ((string)$key) == "Numeric" ) {
				$vv=self::deletenodelevel($v,$nivel+1);
				$xx=array_merge($xx,$vv);
			}
			else $xx[$key]=$v;
		}
		return $xx;
	}
	private static function sxmltoarray($o,$nivel=0) {
		$x=[];
		$data=$o;
		if( is_object($data) ) $data=get_object_vars($data);
	 	if( is_array($data) && count($data) ){
			foreach($data as $key=>$v) {
				if( ((string)$key) != '@attributes' )
					$x[$key]=self::sxmltoarray ($v,$nivel+1);
			}
		}
		else if( is_array($data) && count($data)==0 )
			$x='';
		else
			$x=$data;
		return $x;
	}
}
class stdmsg {
	#-------------------------------------------------------------------
	# Texts for different language
	private static function msg() {
		$msgi=[
			'lemas'=>["Seguir comprant","Seguir comprando",
					"Go on buying",
					"Poursuivre vos achats"],
			'dadespestanyes'=>['Dades Enviament','Datos Env�o',
				'Delivery Data','Don�es de Livraison'],
			#-----------------------------------------------------------
			'titol'=>array("Articles a la cistella",
				"Articulos en la cesta",
				"Products in the shopping cart","Produits au panier"),
			'model'=>array("Model","Modelo","Model","Mod�le"),
			'mida'=>array("Mides","Medidas","Measures","Mesures"),
			'priu'=>array('Preu unitari','Precio unitario',
				'Single Price',
				'Prix unitaire'),
			'qty'=>array('Quantitat','Cantidad','Quantity','Quantit�'),
			'total'=>array('Total','Total','Total','Totale'),
			'totalp'=>array('Total articles','Total art�culos',
				'Product Total','Totale articles'),
			'totalt'=>array('Cost Transport','Coste del Transporte',
				'Delivery cost','Cost de Livraison'),
			'totala'=>array('Total a pagar','Total a pagar',
				'Total to pay','Totale a payer'),
			'empty'=>array("La cistella est� buida",
				"La cesta esta vac�a",
				"The shopping cart is empty", "Le panier est vide"),
			'qtyzero'=>array('El total a pagar es cero',
				'El total a pagar es cero',
				'The amount to pay is zero',
				'La quantit�� a payer est cero'),
			'treure'=>["Treure","Quitar","Delete","Supprimer"],
			'tornar'=>["Tornar a veure","Volver a ver","See again",
				"Retour"],
			'tbrut'=>["Total brut: ","Total bruto: ","Gross Total:
				","Net Total: "],
			'tdesc'=>["Descompte  ","Descuento ","Discount ",
				"Discount "],
			'tnet'=>["Total net: ","Total neto: ","Net Total: ",
				"Net Total: "],
			'tmin'=>["M�nim Total Bru: ","M�nimo Total Bruto: ",
				"Minimum Gross Total: ","Minimum Gross Total: "],
			'tbal'=>["Difer�ncia: ","Diferencia: ","Balance: ",
				"Balance: "],
			'tbut'=>["Continuar la compra",
				"Continuar la compra","Go on buying",
					"Poursuivre l'ach�t"],
			'tbutout'=>["Anar a l'Area de particulars",
				"Ir a la �rea de particulares",
				"Go to indivdual area",
				"Changer a l'aire de particuliers"
			],
			'titrs'=>["Resum de la compra","Resumen de la compra",
				'Purchase summary','Purchase summary' ],
			'trans'=>["Transport:","Transporte:","Transport:",
				"Transport:"],
			'tapag'=>["Total a pagar:","Total a pagar:",
				"Total to pay:","Total to pay::"],
			'bim'=>["Base imponible","Base imponible","Tax Base",
				"Tax Base"],
			'iva'=>["Iva","Iva","VAT","Tax"],
			#-----------------------------------------------------------
			'titolllista'=>array("Llista de preus","Lista de precios",
				"Price List","Liste de prix"),
			'mida'=>array("Mides","Medidas","Measures","Dimensions"),
			'po'=>array('Preu Original','Precios Originales',
				'Original Prices','Original Prix'),
			'preu'=>array('Preus Actuals','Precios Actuales',
				'Actual Prices','Prix Actuelle'),
			'modellista'=>array('Refer�ncia-Model','Referencia-Modelo',
				'Reference-Model','R�f�rence-Mod�le'),
			#-----------------------------------------------------------
			'fsae'=>["Adre�a d'enviament","Adreca envio","Send Adress",
				"Adresse"],
			'EnvNom-pr'=>['Ra� Social',"Raz�n social","registered Name",
				"Raison Sociale"],
			'EnvNom-ph'=>['Nom','Nombre','Name','Nom'],
			'EnvAdr-pr'=>["Adre�a d'enviament","Direcci�n envio",
				"Send Adress",
				"Adresse"],
			'EnvAdr-ph'=>["Adre�a","Direcci�n","Address","Adresse"],
			'EnvCP-pr'=>["CP/Poblaci�","CP/Poblaci�n","PC/City",
				"CP/Cit�"],
			'EnvCP-ph'=>["C�digo postal","C�digo Postal","Postal Code",
				"Code Postal"],
			'EnvPob-ph'=>["Poblaci�","Poblaci�n","City","Cit�"],
			'EnvPais-pr'=>["Pais","Pa�s","Country","Pays"],
			'EnvMail-pr'=>["e-mail","e-mail","e-mail","e-mail"],
			'EnvMail-ph'=>["e-mail","e-mail","e-mail","e-mail"],
			'EnvTel-pr'=>["Tel�fon","Tel�fono","Phone","Telephone"],
			'EnvTel-ph'=>["Tel�fon","Tel�fono","Phone","Telephone"],
			#-----------------------------------------------------------
			'fsaf'=>["Adre�a fiscal","Direcci�n fiscal","Tax Adress",
				"Adresse fiscale"],
			'FisNom-pr'=>['Ra� Social',"Raz�n social","registered Name",
				"Raison Sociale"],
			'FisNom-ph'=>['Nom','Nombre','Name','Nom'],
			'FisNIF-pr'=>['CIF/NIF',"CIF/NIF","CIF/NIF","CIF/NIF"],
			'FisNIF-ph'=>['CIF','CIF','CIF','CIF'],
			'FisAdr-pr'=>["Adre�a de factura","Direcci�n factura",
				"Invoice Adress",
				"Adresse"],
			'FisAdr-ph'=>["Adre�a","Direcci�n","Address","Adresse"],
			'FisCP-pr'=>["CP/Poblaci�","CP/Poblaci�n","PC/City",
				"CP/Cit�"],
			'FisCP-ph'=>["C�digo postal","C�digo Postal","Postal Code",
				"Code Postal"],
			'FisPob-ph'=>["Poblaci�","Poblaci�n","City","Cit�"],
			'FisPais-pr'=>["Pais","Pa�s","Country","Pays"],
			'FisMail-pr'=>["e-mail","e-mail","e-mail","e-mail"],
			'FisMail-ph'=>["e-mail","e-mail","e-mail","e-mail"],
			'FisTel-pr'=>["Tel�fon","Tel�fono","Phone","Telephone"],
			'FisTel-ph'=>["Tel�fon","Tel�fono","Phone","Telephone"],
			#-----------------------------------------------------------
			'fsam'=>["","","",""],#WARNING &euros; interpreted as array.
			'ButTPV-pr'=>["Finalitzar la compra",
				"Finalizar la compra",
				"End purchase",
				"Finaliser le �chat"
			],
			'ButDIR-pr'=>["Finalitzar la compra i enviar comanda ",
				"Finalizar la compra i hacer pedido",
				"End the purchase and order",
				"Ordonner"
			],
			'ButBAC-pr'=>["Tornar a la cistella","Volver a la cesta",
				"Back to shopping cart","Back to shopping cart",],
			#-----------------------------------------------------------
			'entrada'=>[
				"Confirmaci�n de pedido de las caracter�sticas
					siguientes:",
				"Confirmaci�n de pedido de las caracter�sticas
					siguientes:",
				"Confirmaci�n de pedido de las caracter�sticas
					siguientes:",
				"Confirmaci�n de pedido de las caracter�sticas
					siguientes:",
			],

			'imprimir'=>['Imprimir','Imprimir','Print','Print'],
			'gomain'=>['Sortir','Salir','Exit','Sortir'],

			'ticket'=>[
				'Tiquet de confirmaci&oacute; n&ordm;: ',
				'Tiquet de confirmaci&oacute;n n&ordm;: ',
				'Confirmation Ticket  n&ordm;: ',
				'Ticket de confirmation   n&ordm;: '

			],
			'periode'=>[
				'Per�ode estimat de lliurament 20 dies',
				'Periodo estimado de entrega 20 d�as',
				'Estimated delivery time 20 days',
				'D�lai de livraison estim� 20 jours',
			],
			'adrecaenvia'=>[
				"Enviat a l'adre&ccedil;a ",
				"Enviado a la direcci&oacute;n",
				"Sent to following address",
				"Livr&eacute; a l'adresse suivante",
			]
		];
		return $msgi;
	}
	public static function getStandardMessages($language,$what='') {
		$msg=self::msg();
		if( $what && ! isset($msg['$what'])) {
			$m=$what.' NOT FOUND';
		}
		else if( $what && isset($msg['$what'])) {
			if($language && isset($msg['$what'][$language-1]))
				$m=$msg['$what'][$language-1];
			else $m="{$what} AND idioma={$language} NOT FOUND";
		}
		else {
			$m=[];
			foreach($msg as $key=>$v) {
				if( isset($v[$language-1]) ) $m[$key]=$v[$language-1];
				else {
					$m[$key]="Langage for {$key} not found";
				}
			}
		}
		return $m;
	}
}
class rsrcs {
	public static function cssDatatables() {
		$css="//cdn.datatables.net/1.10.9/css/jquery.dataTables.css";
		$h="href=\"{$css}\"";
    	$x="<link  rel=\"stylesheet\" type=\"text/css\" {$h}>";
		return $x;
	}
	public static function cssQueryMobile() {
		$c ="https://code.jquery.com/mobile/1.4.5/";
		$c.="jquery.mobile-1.4.5.min.css";
		$rc=[];
		$rc[]="<link rel=stylesheet type=text/css href='{$c}'>";
		return implode("\n",$rc);
	}
	public static function cssQueryMobileOwnTheme() {
		$h ="themes/jquery.mobile.icons.min.css";
		$x ="http://code.jquery.com/mobile/1.4.5/";
		$x.="jquery.mobile.structure-1.4.5.min.css";
		$y ="http://code.jquery.com/jquery-1.11.1.min.js";
		$z ="http://code.jquery.com/mobile/1.4.5/";
		$z.="jquery.mobile-1.4.5.min.js";
		$rc=[];
		$rc[]='<link rel="stylesheet" href="themes/oqp-theme.min.css"/>';
		$rc[]="<link rel='stylesheet' href='{$h}' />";
		$rc[]="<link rel='stylesheet' href='{$x}' />";
		$rc[]="<script src='{$y}'></script>";
		$rc[]="<script src='{$z}'></script>";
		return implode("\n",$rc);
	}
	public static function cssQueryUI() {
		$css="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css";
		$h="href='{$css}'";
     	return "<link rel='stylesheet' type='text/css' {$h} />";
	}
	public static function cssFromArray($cssl=[],$styles=[]) {
		$x="rel=stylesheet type=text/css";
		$rc=[];
		foreach($cssl as $css ) $rc[]="<link {$x} href='{$css}.css'>";
		if( count($styles) ) {
			$st=implode("\n",$styles);
			$rc[]="<style>{$st}</style>";
		}
		return implode("\n",$rc);
	}
	public static function iconsMaps() {
		 $c="https://fonts.googleapis.com/icon?family=Material+Icons";
         return "<link  rel=\"stylesheet\" href=\"{$c}\">";
	}
	public static function jsQuery() {
		 $js="ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js";
         return "<script src=\"https://{$js}\"></script>";
	}
	public static function jsQueryMobile() {
		$rc=[];
		$rc[]="<script>
				$(document).bind('mobileinit',function(){
					$.mobile.ignoreContentEnabled=true;
				});
			</script>";
        $js="code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.js";
        $rc[]="<script src=\"https://{$js}\"></script>";
        return implode("\n",$rc);
	}
	public function jsMaps() {
		$js ="https://maps.googleapis.com/maps/api/js";
		$js.="?key={$this->prp['keymaps']}";
		$js.="&v=3.30";
		return "<script async defer src='{$js}'></script>";
	}
	public static function jsDatatables() {
		$js="//cdn.datatables.net/1.10.9/js/jquery.dataTables.js";
        return "<script src=\"{$js}\"></script>";
	}
	public static function jsFromArray($jsl,$scripts,$version) {
		$rc=[];
		foreach($jsl as $js ) {
        	if( stripos($js,'?version') !== false ) $donothing=0;
			else if( substr($js,-3) != '.js' ) $js.='.js';
        	$rc[]="<script src='{$js}'></script>";
        }
		$action =basename($_SERVER['SCRIPT_NAME']);
        $url=substr($action,0,-strlen("php")).'js';
        if( ! file_exists($url) ) $url='';
		if( $url)
			$rc[]="<script src='{$url}?version={$version}'></script>";
		if( count($scripts) ){
			$s=implode("\n",$scripts);
			$rc[]="<script>{$s}</script>";
		}
		return implode("\n",$rc);
	}
	public static function jsQueryUI() {
		$js="https://code.jquery.com/ui/1.12.1/jquery-ui.js";
        return "<script src='{$js}'></script>";
	}
	public static function cssIcons() {
		$lk ="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/";
		$lk.="4.7.0/css/font-awesome.min.css";
		return "<link rel='stylesheet' href='{$lk}'>";
	}
}
class download {
	#===================================================================
    # Functions to download an EXCEL file
    #===================================================================
	public static function downloadEXCEL($nomfitxer,$data,$latin=true){
		if($latin) 	$file=self::generarLCSV($data);
		else 		$file=self::generarCSV($data);
		self::download_File($nomfitxer, $file);
	}
	protected static function generarLCSV($data) {
		return self::genEXCEL($data,";",true);
	}
	protected static function generarCSV($data) {
		return self::genEXCEL($data,",",false);
	}
	protected static function genEXCEL($table,$sep,$changepoint) {
        $qua='"';$lf="\r\n"; $isfirst=true;
        $rc='';
        foreach($table as $linia) {
            if( $isfirst ) {
                $lin='';
                foreach($linia as $key=>$v) {
                    $keyesc=self::Escapar($key);
                    $lin.=($lin ? $sep :'')."\"$keyesc\"";
                }
                $lin.=$lf;
                $rc.=$lin;
                $isfirst=false;
            }
            $lin='';
            foreach($linia as $key=>$v) {
                if( $changepoint && self::IsPseudoNumeric($v) )
                    $v=str_replace('.',',',$v);
                $vesc=self::Escapar($v);
                $lin.=($lin ? $sep :'')."\"$vesc\"";
            }
            $lin.=$lf;
            $rc.=$lin;
        }
        return $rc;
	}
	private static function Escapar($s) {
        $s=str_replace("\"","'",$s);
        $s=html_entity_decode($s);
        return $s;
    }
	private static function IsPseudoNumeric($v) {
        $rc=false;
        if( is_numeric($v) ) $rc=true;
        else if( substr($v,0,4) == '<loq' && is_numeric(substr($v,4)))
			$rc=true;
        else if( substr($v,0,1) == '<' && is_numeric(substr($v,1)))
			$rc=true;
        return $rc;
    }
	#===================================================================
	# Headers for several kinds of downloading files
	#===================================================================
	public static function download_File($nomfitxer,$file) {
        $cuantos=strlen($file);
        $s=$_SERVER['HTTP_USER_AGENT'];
        if( isset($s) && strpos($s,'MSIE') )
            header('Content-Type: application/force-download');
        else
            header('Content-Type: application/octet-stream');
        header('Content-Length:'.$cuantos);
		$f="filename='{$nomfitxer}'";
        header("Content-disposition: attachment; {$f}");
        echo $file;
    }
}
class upFILES {
	public static function upload($dir,$index='',$allowed=[]) {
		if( count($_FILES) == 0  ) return 'No files in array $_FILES';
		if(0) dbg::seeDetails($_FILES,'$_FILES:');
		// Intermediate directory
		$dir=$_SERVER['DOCUMENT_ROOT'].$dir;
		//$dir=appData::getParam('filereserve');
		// Mime types allowed to bbe uploaded
		$allowed=[]; // The empty array indicates everything is valid
		// Traspose the $_FILES array
		$files=[];
		foreach($_FILES as $key=>$v) {
			foreach($v['name'] as $i=>$name)
				$files[$key][$i]['name']=gnrl::toUTF8($name);
			foreach($v['type'] as $i=>$type)
				$files[$key][$i]['type']=$type;
			foreach($v['tmp_name'] as $i=>$tmp_name)
				$files[$key][$i]['tmp_name']=$tmp_name;
			foreach($v['error'] as $i=>$error)
				$files[$key][$i]['error']=$error;
			foreach($v['size'] as $i=>$size)
				$files[$key][$i]['size']=$size;
		}
		if(0) dbg::seeDetails($files,'Files trasposed');
		// It checks the array $_FILES to see si some one file is loaded
		$diag=[];
		foreach($files as $key=>$list) foreach($list as $i=>$prp) {
			extract($prp);
			$finfo=new finfo(FILEINFO_MIME_TYPE);
			$mime=$finfo->file($tmp_name);
			$isFileAllowed=true;
			if( count($allowed) )
				$isFileAllowed=in_array($mime,$allowed);
			$diag[]=dbg::see($prp,"File properties:",true);
			if( $error==UPLOAD_ERR_NO_FILE ) $diag[]="NO FILE";
			else if ( $error== UPLOAD_ERR_INI_SIZE ||
						$error == UPLOAD_ERR_FORM_SIZE)
				$diag[]="Exceeded filesize limit";
			else if ( $error != UPLOAD_ERR_OK )
				 $diag[]="Unlnpwn error";
			else if( ! is_uploaded_file($tmp_name) )
				$diag[]="It's not an uploaded file";
			else if( $isFileAllowed === false)
				$diag[]="Invalid file format";
			else if( self::mkdir($dir) === false )
				$diag[]="Directory cannot be created";
			else {
				$destination=$dir.$name;
				if(move_uploaded_file($tmp_name,$destination)===false)
					$diag[]="The file cannot be moved";
				else {
					$diag[]="File {$name} moved OK";
				}
			}
		}
		//--------------------------------------------------------------
		if($index) {
			$s=serialize($files);
			$f=$index.".txt";
			$index=$dir.$f;
			if( file_put_contents($index,$s) === false )
				$diag[]="Index {$f} cannot recorded";
			else
				$diag[]="Index file recorded";
		}
		//--------------------------------------------------------------
		$d=implode("<br>",$diag);
		$rc="<details><summary>Files uploaded</summary>{$d}</details>";
		return $rc;
	}
	public static function mkdir($dir) {
		if(0) dbg::seeDetails($dir,"Check directory:");
		$root=$_SERVER['DOCUMENT_ROOT'];
		if( substr($dir,0,strlen($root)) != $root)  $dir=$root.$dir;
		if( file_exists($dir) )
			$d=dir($dir);
		else {
			if ( mkdir($dir) === false  ) die('Fatal error: ERR19564 ');
			$d=dir($dir);
		}
		return $d;
	}
	public static function removeDir($directory) {
		$directory=$_SERVER['DOCUMENT_ROOT'].$directory;
		if(substr($directory,-1) != '/') $directory.='/';
		if( file_exists($directory) ){
			$dir=dir($directory);
			while( $dir && false !== ($entry = $dir->read()) ) {
				if( substr($entry,0,1) == '.' ) continue;
				$f=$directory.$entry;
				unlink($f);
			}
			rmdir($directory);
		}
	}
	public static function moveFile($name,$origin,$destination) {
		if(substr($destination,-1) != '/') $destinationDir.='/';
		if(substr($origin,-1) != '/') $origin.='/';
		$e='';
		if( self::mkdir($destination) ) {
			$for=$_SERVER['DOCUMENT_ROOT'].$origin.$name;
			$fde=$_SERVER['DOCUMENT_ROOT'].$destination.$name;
			if( file_exists($for) ) {
				$fb=file_get_contents($for);
				if( file_put_contents($fde,$fb) === false )
					$e="Move to {$fde} failed";
				else
					$e="OK Move correct";
				unlink($for);
			}
			else $e="The file {$for} doesn't exist";
		}
		else $e="The destination directory cannot be created";
		return $e;
	}
}
class htmlSimple {
	public static function html($what,$level=0) {
		$rc=[];
		foreach($what as $i=>$w) {
			if( is_array($w) ) $ww=self::html($w,$level+1); else $ww=$w;
			if( preg_match("/^[\[].+[\]]/",$ww,$sub) ) {
				$prg=substr($sub[0],1,-1);
				$www=substr($ww,strlen($prg)+2);
				if(0) dbg::seeDetails($sub,$prg);
			}
			else {$www=$ww; $prg="";}
			$rc[]=self::paragraphOperations($www,$prg,$level);
		}
		return implode("\n",$rc);
	}
	private static function paragraphOperations($t,$prg,$level) {
		$ib=$level*1.5;
		$st=$level?"style='padding:0 {$ib}em'":'';
		$ins=explode("|",$prg);
		$oper=$ins[0];
		switch($oper) {
			default: $oper='p';
			case 'h1': case 'h2': case 'h3':
			case 'h4': case 'h5': case 'h6':
			case 'p':
				$rc="<{$oper} class=fhelp {$st }>{$t}</{$oper}>";
				break;
			case 'li':
				$rc =$st? "<div {$st}>" :'';
				$rc.="<li class=fhelp >{$t}</{$oper}>";
				$rc.=$st? "</div>" :'';
				break;
			case 'ul(':$rc="<ul class=fhelp >{$t}"; break;
			case 'ul)':$rc="</ul>{$t}"; break;
			case 'ol(':$rc="<ol class=fhelp >{$t}"; break;
			case 'ol)':$rc="</ol>{$t}"; break;
		}
		return $rc;
	}
	public static function styles() {
		$rc="
			.fhelp { color:black; max-width:50em;}
			li.fhelp {margin:0 0 1em 0;}
			ul.fhelp  {
				list-style-type:square;
				list-style-position:outside;
				margin:0 0 1em 0;
				padding:0 1em;
			}
			ol.fhelp {
				list-style-type:decimal;
				list-style-position:outside;
				margin:0 0 1em 0;
				padding:0 1em;
			}
			p.fhelp, h3.fhelp {
				margin:0 0 1em 0;
				padding:0 1em;
				color:black;
			}
			.fhelpsum {
				color:brown;
			}
		";
		return $rc;
	}

}
class store {
	private $stack=[];
	public function push() {
		// Analyze all the arguments only if the first is no empty
		foreach(func_get_args() as $list ){
			if( $list && ! is_array($list) ) $list=[$list];
			if( is_array($list) && count($list) )
				foreach($list as $x) $this->stack[]=$x;
			else
				break;
		}
	}
	public function pushStrict() { // Analyze all the arguments
		foreach(func_get_args() as $list ){
			if( $list && ! is_array($list) ) $list=[$list];
			if( is_array($list) && count($list) )
				foreach($list as $x) $this->stack[]=$x;
		}
	}
	public function get() {return $this->stack;}
	public function html($msg='') {
		if(0) dbg::seeDetails($this->stack,"Stack:");
		return dbg::seeD($this->stack,$msg);
	}
	public function getString() {
		return $this->compactToString($this->stack);
	}
	protected function compactToString($x) {
		// $x is any data type (either array or not)
		// to be transformed to string
		$xout='';
		if( is_array($x) )
			foreach($x as $xx) $xout.= $this->compactToString($xx);
		else $xout=$x;
		return $xout;
	}
	public function isEmpty() { return count($this->stack)==0; }
}
class blockForm {
    // Display a list of blocks where in each block can be displayed
    // a field for edition.
    // blocks := [name('')] ['#'('')] [button(false)] block*
    // block := prompt
    public static function fM($blocks) {
        // Get the global parameters
        $gp=['name'=>'','#'=>'','styles'=>''];
        foreach($gp as $field=>$def) if(isset($blocks[$field])) {
            $gp[$field]=$blocks[$field];
            unset($blocks[$field]);
        }
        // To start to generate the HTML of blocks
        $rc=[];
        $rc[]="<div class='blkwrapper ws{$gp['#']}'>";
        // If the name is not empty a form is issued
        if($gp['name']) $rc[]="<form name={$gp['name']} method=post>";
        // To go through the block list
        foreach($blocks as $key=>$block ) {
            $rc[]="<div class=blkline>";
            $rc[]=self::block($block);
            $rc[]="</div>";
        }
        if($gp['name'])$rc[]="</form>";
        $rc[]="</div>";
        return ['html'=>implode("\n",$rc),
            'styles'=>self::styles($gp['styles']),
            'script'=>''];
    }
    protected static function block($block) {
        // Analyse block data
        $prompt=isset($block[0]) ? $block[0]:'';
        $value=isset($block[1]) ? $block[1]:'';
        $type=isset($block['?']) ? $block['?']:'text';
        $opcions=isset($block[2]) ? $block[2] : [];
        $pr=$prompt; if($value) $pr="<br>".$value;
        // To build up the elements
        $rc=[];
        if( $type == 'plain')
            $rc[]=$pr;
        else if( $type == 'details' ) {
            $rc[]="<details><summary>{$prompt}</summary>";
            $rc[]=$value;
            $rc[]="</details>";
        }
        else {
            $rc[]="	<div class=blkprompt>{$prompt}</div>";
            $rc[]="	<div class=blkinput>";
            $rc[]=		self::putField($type,$value,$opcions);
            $rc[]="	</div>";
        }
        return implode("\n",$rc);
    }
    protected static function putField($type,$value,$op) {
        $P='pairs';
        $def=[
            'pairing'=>'',
            'options'=>[],
            'prompt'=>'',
        ];
        $p=gnrl::mergeProperties($def,$op);
        extract($p);
        $cl='';
        if(substr($type,-strlen($P)) == $P ) $cl="class={$pairing}";
        $vvalue=''; if( $value !== '' ) $vvalue="value='{$value}'";
        $rc=[];
        switch($type) {
            default: case 'input': case 'inputpairs':
                $rc[]="<input {$cl} name=argument[] {$vvalue} >";
                break;
            case 'password':
                $rc[]="<input name=argument[] {$vvalue} type={$type}>";
                break;
            case 'textarea':
                $rc[]="<textarea name=argument[] >{$value}</textarea>";
                break;
            case 'select': case 'selectpairs':
                $rc[]="<select name=argument[] {$cl} >";
                foreach($options as $v=>$t) {
                    if($v == $value) $s='selected'; else $s='';
                    $rc[]="<option {$s} value='{$v}'>{$t}</option>";
                }
                $rc[]="</select>";
                break;
            case 'button':
                if( $prompt === '' ) $prompt='GO';
                $rc[]="<button name=question $vvalue>{$prompt}</button>";
                break;
            case 'hidden':
                $rc[]="<input type=hidden name=argument[] {$vvalue} >";
        }
        return implode("\n",$rc);
    }
    public function title() {
        $t=$this->title_base();
        $title=$t[0];
        $format=isset($t[1]) ? $t[1] : '';
        switch($format) {
            default: case '': $st=''; break;
            case 'red':
                $st="style='background-color:brown; color:white'";
                break;
            case 'blue':
                $st="style='background-color:blue; color:white'";
                break;
            case 'green':
                $st="style='background-color:darkgreen; color:white'";
                break;
            case 'orange':
                $st="style='background-color:orange; color:brown'";
        }
        return "<span {$st}>".$title."</span>";
    }
    public static function styles($kind='') {
        switch($kind) {
            default: return self::stylesTop();break;
        }
    }
    public static function stylesTop() {
        $st="
            .blkwrapper {
                width:100%;
            }
            .blkline {
                width:100%;
                padding:0.3em 0;
            }
            .blkprompt {
                width:100%;
                background-color:darkblue;
                color: white;
                text-align:center;
            }
            .blkinput { width:100%; }
            .blkwrapper form { display:inline-block;}
            .blkwrapper input { font-size:1.2em; }
            .blkwrapper select { font-size:1.2em; }
            .blkwrapper password { font-size:1.2em; }
            .blkwrapper button { font-size:1.2em; width:100%; }
            .blkwrapper textarea { font-size:1.2em; rows:8; }
        ";
        return "<style>{$st}</style>";
    }
}
?>

<?php
class appData {
	public static function getParam($name) {
		$b='gnrl'; $idm=[$b::CAT,$b::ES,$b::EN,'default'=>$b::EN];
		$root="https://www.bicinscripcions.com/";
		$wb=$root."TPV/TESTER/";
		$prp=[
			'isclosed'=>false,
			'mainlink'=>"{$root}index.php",
			'title'=>'TPV Tester',
			'headline'=>'Tester for TPV Service',
			'idiomas'=>$idm,
			'colors'=>[
				'tinta1'=>'black', // Medium gray
				'tinta2'=>'rgb(64,172,124)', // Green OQP (Spring green?)
				'tinta3'=>'darkblue',
				'tinta4'=>'gray',
				'papel1'=>'white',
				'papel2'=>'rgb(215,215,215)', //Light gray
				'papel3'=>'rgb(249,249,249)', //Almost white
				'papel4'=>'rgb(249,249,249)',
				'ercolor'=>'Brown',
				'erback'=>'white',
				'atinta'=>'blue',
			],
		];
		return $b::getPath($prp,$name);
	}
	public static function basicStyles() {
		$tinta=self::getParam('colors.tinta1');
		$fondo=self::getParam('colors.papel1');
		$atinta=self::getParam('colors.atinta');
		$rc="
	    	 * {margin:0; padding:0; box-sizing:border-box;}

	     	html, body {height:100%}

	    	body {
				font-family:Lato,'Helvetica Neue',Helvetica,sans-serif;
				font-size: 1em;
				font-weight: 400;
				color: {$tinta};
				background-color: {$fondo};
	    	}

	    	.layout {
				box-sizing: border-box;
				width: 90%;
				margin: 0em auto;
				font-size:1em;
    		}

		    a:link { color:{$tinta}; text-decoration: none;}
		    a:visited { color:{$tinta}; text-decoration: none;}
		    a:hover  { color:{$atinta}; text-decoration:dotted;}

		    .left { text-align:left; }
		    .right { text-align:right; }
			.center { text-align:center; }
		";
		return "<style>{$rc}</style>";
	}
}
?>

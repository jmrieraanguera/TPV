<?php
require_once('../classes_support.php');
require_once('../classes_DBMV1__abstract.php');
class tester {
    const callTO='https://www.bicinscripcions.com/TPV/index.php';
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
    public function type8($arguments) {
        // It supplies the whole data for one shopping cart
        // and the status of the action
        $x=[];
        if($this->getParams($data=$arguments[0])){
            $t=80;
            if( isset($data['type']) && $data['type'] == 1) $t=81;
            if( isset($data['type']) && $data['type'] == 2) $t=82;
            $post=self::model1($t);
            $m="TO JUMP TO SCRIPT CALLING THE TPV";
            $s="style='color:brown; font-weight:bold;'";
            echo "<h3 {$s}>{$m}</h3>";
            exit(gnrl::pageJump(self::callTO,$post,true));
        }
        return $this->answer($d,"WHOLE SC");
    }
    public function type7($arguments) {
        // It supplies the whole data for one shopping cart
        // and the status of the action
        $x=[];
        if($this->getParams($data=$arguments[0])){
            $t=70;
            if( isset($data['type']) && $data['type'] == 1) $t=71;
            if( isset($data['type']) && $data['type'] == 2) $t=72;
            $post=self::model1($t);
            $m="TO JUMP TO SCRIPT CALLING THE TPV";
            $s="style='color:brown; font-weight:bold;'";
            echo "<h3 {$s}>{$m}</h3>";
            exit(gnrl::pageJump(self::callTO,$post,true));
        }
        return $this->answer($d,"WHOLE SC");
    }
    public function type9($arguments) {
        // It supplies the whole data for one shopping cart
        // and the status of the action
        $x=[];
        if($this->getParams($data=$arguments[0])){
            $t=90;
            if( isset($data['type']) && $data['type'] == 1) $t=91;
            if( isset($data['type']) && $data['type'] == 2) $t=92;
            $post=self::model1($t);
            $m="TO JUMP TO SCRIPT CALLING THE TPV";
            $s="style='color:brown; font-weight:bold;'";
            echo "<h3 {$s}>{$m}</h3>";
            exit(gnrl::pageJump(self::callTO,$post,true));
        }
        return $this->answer($d,"WHOLE SC");
    }
    public function type6($arguments) {
        // It supplies the whole data for returns of money
        $x=[];
        if($this->getParams($data=$arguments[0])){
            $t=60;
            if( isset($data['type']) && $data['type'] == 1) $t=61;
            if( isset($data['type']) && $data['type'] == 2) $t=62;
            $post=[
                'typePayment'=>$t,
    			'transactionType'=>3,
    			'amount'=>'2500',
    			'voucher'=>'99999999',
    			'productDescription'=>'Test',
    			'titular'=>'INDIROOM',
    			'name'=>'Bicinscripcions',
    			'merdata'=>'P0010',
    			'language'=>'2'
    		];
            $m="TO JUMP TO SCRIPT CALLING THE TPV";
            $s="style='color:brown; font-weight:bold;'";
            echo "<h3 {$s}>{$m}</h3>";
            exit(gnrl::pageJump(self::callTO,$post,true));
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
    //==================================================
    protected $PARAMS;
    protected function getParams($data) {
        $this->PARAMS=$data;
        return true;
    }
}
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
?>

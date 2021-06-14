<?php
abstract class DB_admin_V1 {
	abstract protected function structure();
	private function example() {
		# It can contains several
		$structure=[
			'name'=>[
				'name'=>'pararray',
				'fields'=>[
					'grp'=>'VC50',
					'nm'=>'VC50',
					'ind'=>'INT',
					'what'=>'TEXT',
				],
				'primary'=>['grp','nm','ind'],
				'force'=>false,
			],
		];
		return $structure;
	}
	protected function connection() {
		$con['bdconnector']=[
			"host"=>"",'bd'=>"",'db'=>"",'usr'=>"",'user'=>"",'pwd'=>'',
		];
		return $con;
	}
	protected function types () {
		$ss="utf8 COLLATE utf8_general_ci";
		$types=[
		'AINT'=>'INTEGER AUTO_INCREMENT',
		'INTG'=>'INT', 'DOUB'=>'DOUBLE',
		'VC20'=>"VARCHAR(20) CHARACTER SET {$ss}",
		'VC50'=>"VARCHAR(50) CHARACTER SET {$ss}",
		'VCFF'=>"VARCHAR(255) CHARACTER SET {$ss}",
		'BOOL'=>'BOOL',
		'DTIM'=>'DATETIME','TIME'=>'TIME',
		'TEXT'=>"TEXT CHARACTER SET {$ss}"
		];
		return $types;
	}
	private function def() {
		$def=[ 'connect'=>false,
			'bdconnector'=>[
				"host"=>"undefined",
				'bd'=>"undefined", 'db'=>"undefined",
				'usr'=>"undefined",'user'=>"undefined",
				'pwd'=>'undefined',
			],
		];
		return $def;
	}
	protected $pdo=null; # The connector to database
	# Basic properties of the class
	protected $prp=[];
	public function __construct($prp=[]) {
		if(0) dbg::seeDetails($prp,"Arrived:");
        	$this->prp = gnrl::MergeProperties($this->def(), $prp);
		if($this->prp['connect']) $this->connect($this->connection());
	}
	public function connect($prp) {
		#===============================================================
        # Connect and get PDO object
        extract($this->prp['bdconnector']=$prp);
        $dsn="mysql:dbname={$db};host={$host}";
        $persistent=array( PDO::ATTR_PERSISTENT => true );
		$pdo=null;
        try {
            $pdo= new PDO($dsn,$usr,$pwd,$persistent);
        }
        catch ( PDOException $e){
            $error ="It's not possible to set the connection:<br>";
            $error .= dbg::see(debug_backtrace(),"Backtrace",true);
			if( false ) {
				$error.="{$host}<br>";
	            	$error.="{$dsn}<br>";
	            	$error.="{$usr}<br>";
			}
            $error.="Error:".$e->getMessage();
            die($error);
        }
		$this->pdo=$pdo;
		$this->createStructure();
	}
	public function connectV1($prp) {
		if(0) dbg::seeDetails($prp,"Prop to connect");
		#===============================================================
        # Connect and get PDO object
        extract($this->prp['bdconnector']=$prp);
		$error=[];
		if( ! isset($host) ) $error[]="There is no host";
		if( ! isset($db) && isset($bd)  ) $db=$bd;
		if( ! isset($db) ) $error[]="There is no db name";
		if( ! isset($usr) && isset($user)  ) $usr=$user;
		if( ! isset($usr) ) $error[]="There is no user name";
		if( ! isset($pwd) ) $error[]="There is no password";
		if( count($error) == 0 ) {
			$dsn="mysql:dbname={$db};host={$host}";
	        $persistent=array( PDO::ATTR_PERSISTENT => true );
			$pdo=null;
	        try {
	            $pdo= new PDO($dsn,$usr,$pwd,$persistent);
	        }
	        catch ( PDOException $e){
	            $error[] ="It's not possible to set the connection:<br>";
				$error[]="{$host}<br>";
	            	$error[]="{$dsn}<br>";
	            	$error[]="{$usr}<br>";
	            $error[]="Error:".$e->getMessage();
	        }
			$this->pdo=$pdo;
			if($pdo)$this->createStructure();
		}
		if(0) dbg::seeDetails($error,"Connection:");
		return $error;
	}
	#===================================================================
	# Basic functions to manage data
	#===================================================================
	public function getPDO() {return $this->pdo;}
	public function quote($what) { return $this->pdo->quote($what); }
	#-------------------------------------------------------------------
	public function getAllIfOK($sql) {
		$error='';
		$pdost=$this->pdo->query($sql);
        if( $pdost === false ) {
            $error  = "ERROR in SQL:{$sql}<br>";
            $error .= implode("<br>",$this->pdo->errorInfo());
			$error .= '<br>';
			$rc=['error'=>$error];
        }
		else {
			$rc=$pdost->fetchALL(PDO::FETCH_ASSOC);
        		$pdost->CloseCursor();
        }
		return $rc;
	}
	public function getAll($sql) {
		$r=$this->getAllIfOK($sql);
		if( isset($r['error']) ) die($r['error']);
        return $r;
	}
	public function execSQLIfOK($sql) {
		$error='';$n=0;
		if( ($n=$this->pdo->exec($sql)) === false  ) {
            $error  = "ERROR in SQL:{$sql}<br>";
            $error .= implode("<br>",$this->pdo->errorInfo()).'<br>';
			$error .= '<br>';
			$rc=['error'=>$error];
        }
		else $rc=[$n,$sql];
		return $rc;
	}
	public function execSQL($sql) {
		$x=$this->execSQLIfOK($sql);
		if( isset($x['error']) ) die($x['error']);
		return $x[0];
	}
	public function checkSQL($sql) { //Not action
		$pdost=$this->pdo->query($sql);
		$isOK=$pdost !== false;
		$e='';
		if( $isOK ) $pdost->CloseCursor();
		else $e=implode("<br>",$this->pdo->errorInfo());
        return $e;
	}
	#===================================================================
	# Operations for DB Administration
	#===================================================================
	public function createStructure() {
		$tablesraw=$this->getTables();
		$tables=[];
		foreach( $tablesraw as $row ) $tables[]=$row['Name'];
		$is=$this->structure();
		if(0) dbg::seeDetails($is,"Structure:");
		$nr=[];
		foreach($is as $t) {
			if( $t['force'] || ! in_array($t['name'],$tables) )
				$nr[]=$this->createTable($t);
		}
		return implode("-",$nr);
    }
	protected function createTable($t) {
		extract($t);
		if( $force ) $n=$this->execSQL("DROP TABLE {$name} ");
		$rc=[];
		$rc[]="CREATE TABLE {$name} (";
		$f=[];
		if( count($primary) ) {
			$p=[];foreach($primary as $v) $p[]="`{$v}`";
			$f[]="PRIMARY KEY (".implode(", ",$p).")";
		}
		foreach($fields as $key=>$p ) {
			$def='';
			if( is_array($p) ) {
				$x=$p;$p=$p[0];
				if( isset($x[1]) ) $def='DEFAULT '.$x[1];
			}
			if( isset($this->types()[$p]) ) $p=$this->types()[$p];
			$f[]="`{$key}` $p {$def}";
		}
		$rc[]=implode(", ",$f);
		$rc[]=")";
		$sql=implode("\n",$rc);
		$n=$this->execSQL($sql);
		return $n;
	}
	public function getTables($param=array()) {
		$default=array(
			'like'=>'', 'where'=>'',
			'filter'=>array(
				'positive'=>array('/.+/'),
				'negative'=>array('/^pma__/'),
			),
			'fields'=>array(
				'Name'=>1,
				'Table_type'=>'1',
				'Engine'=>0,
				'Version'=>0,
				'Row_format'=>0,
				'Rows'=>1,
				'Avg_row_length'=>0,
				'Data_length'=>1,
				'Max_data_length'=>0,
				'Index_length'=>0,
				'Data_free'=>0,
				'Auto_increment'=>1,
				'Create_time'=>0,
				'Update_time'=>1,
				'Check_time'=>0,
				'Collation'=>1,
				'Checksum'=>0,
				'Create_options'=>0,
				'Comment'=>1,
				'Structure'=>'1',
			)
		);
		$p=gnrl::MergeProperties($default, $param);
		//--------------------------------------------------------------
		// get the raw tables and views
		$sql="SHOW FULL TABLES ";
		$tview=$this->getAll($sql);
		$traw=array();
		$bd=$this->prp['bdconnector']['db'];
		foreach($tview as $v ) {
			$vv=array();
			$vv['Name']=$v['Tables_in_'.$bd];
			$vv['Table_type']=$v['Table_type'];
			$traw[]=$vv;
		}
		//--------------------------------------------------------------
		// filter by positive selection
		$t=array();
		foreach( $traw as $i=>$v ) {
			$selected=false;
			foreach( $p['filter']['positive'] as $pat ) {
				if( preg_match($pat,$v['Name']) ) {
					$selected=true;
					break;
				}
			}
			if( $selected ) $t[]=$v;
		}
		//--------------------------------------------------------------
		// filter by negative selection
		# dbg::see($t, "POSITIVE:");
		$nf=$p['filter']['negative'];
		$filter=array();
		foreach( $nf as $pat ) if( $pat != '//' )$filter[]=$pat;
		# dbg::see($filter, "NEGATIVE FILTER:");
		if( count($filter) ) {
			$tt=array();
			foreach( $t as $i=>$v ) {
				$selected=false;
				foreach( $filter as $pat ) {
					if( ! preg_match($pat,$v['Name']) ) {
						$selected=true;
						break;
					}
				}
				if( $selected ) $tt[]=$v;
			}
		}
		else $tt=$t;
		# dbg::see($tt, "FULL:");
		//--------------------------------------------------------------
		// get the raw tables
		$sql="SHOW TABLE STATUS ";
		if( $p['like']  ) $sql.=" LIKE '{$p['like']}' ";
		if( $p['where'] ) $sql.=" WHERE {$p['where']} ";
		$tstatus=$this->getAll($sql);
		foreach($tstatus as $data ) {
			$selected=false;
			foreach($tt as $i=>$d ) if( $d['Name']==$data['Name'] ){
				$selected=true; break;
			}
			if( $selected ){
				$tt[$i]=array_merge($tt[$i],$data);
			}
		}
		//--------------------------------------------------------------
		// select the fields interesting
		$ttt=array();
		foreach( $tt as $v ) {
			foreach($p['fields'] as $key=>$si ) {
				if( ! $si ) unset($v[$key]);
			}
			$ttt[]=$v;
		}
		//--------------------------------------------------------------
		// look up for the fileds of the tables
		if($p['fields']['Structure'] ) {
			foreach($ttt as $i=>$tt)
				$ttt[$i]['Structure']=$this->getFields($tt['Name']);
		}
		return $ttt;
	}
	public function getFields($name,$param=array()){
		$default=array(
			'full'=>1,
			'like'=>'', 'where'=>'',
			'filter'=>array(
				'positive'=>array('/.+/'),
				'negative'=>array('/^pma__/'),
			),
			'fields'=>array(
				'Field'=>1,
				'Type'=>1,
				'Null'=>1,
				'Key'=>1,
				'Default'=>1,
				'Extra'=>1,
				'Collation'=>1,
				'Comments'=>1,
			)
		);
		$p=gnrl::MergeProperties($default, $param);
		//--------------------------------------------------------------
		// get the raw fields
		if( $p['full']    ) $full='FULL'; else $full='';
		$sql="SHOW {$full} COLUMNS FROM {$name} ";
		if( $p['like']  ) $sql.=" LIKE '{$p['like']}' ";
		if( $p['where'] ) $sql.=" WHERE {$p['where']} ";
		$fld=$this->getAll($sql);
		//--------------------------------------------------------------
		// select the fields interesting
		$ttt=array();
		foreach( $fld as $v ) {
			foreach($p['fields'] as $key=>$si ) {
				if( ! $si ) unset($v[$key]);
			}
			$ttt[]=$v;
		}
		return $ttt;
	}
	protected function getDataTable($table) {
		$fields=$this->getFields($table);
		$keys=array();$fnames=array();
		foreach($fields as $f ) {
			$fnames[]=$f['Field'];
			if($f['Key']=='PRI') $keys[]=$f['Field'];
		}
		return array('keys'=>$keys,'fields'=>$fnames);
	}
	#===================================================================
	# SQL Generators
	#===================================================================
	protected function retrieve($table,$prp=[]) {
		$default=['select'=>'*','cnd'=>[],'limit'=>0,'offset'=>0];
		$prp=gnrl::MergeProperties($default, $prp);
		extract($prp);
		if( is_array($cnd) ) $cnd=implode(' AND ', $cnd);
		if( $cnd ) $cnd="WHERE {$cnd} ";
		$strlimit='';
		if( $limit )  $strlimit.="LIMIT {$limit} ";
		if( $offset ) $strlimit.="OFFSET {$offset} ";
		$sql="
			SELECT {$select} FROM {$table}
			{$cnd}
			$strlimit
		";
		# dbg::see($sql,"SQL:");
		$r=$this->getALL($sql);
		return $r;
	}
	public function isUpdatableQuery($sql) {
		//--------------------------------------------------------------
		// If there is only a table and there are every keys
		$cmp=new sqlCompiler;
		$r=$cmp->compile($sql);
		$rc=false;$ftable='';$keysl=[];
		if( $sql && ! $r['error'] ) {
			#-----------------------------------------------------------
			# Find out the table data
			$d=$this->getDataTable($r['table']);
			$keysl=$d['keys'];
			$ftable=$r['table'];
			if( $r['fields'][0] == '*'  ) $rc=true;
			else {
				$keys=true;
				foreach( $d['keys'] as $key ) {
					if( ! in_array($key,$r['fields']) ) {
						$keys=false;
						break;
					}
				}
				if( $keys) $rc=true;
			}
		}
		return ['updatable'=>$rc,'ftable'=>$ftable,'keys'=>$keysl];
	}
	protected function updateRecord($sql,$record,$which,$newvalue){
		#---------------------------------------------------------------
		# Update if has changed
		if( $newvalue != $record[$which] ) {
			list($isupdatable,$table,$keys)=$this->isUpdatableQuery($sql);
			if( $isupdatable  ) {
				$xnewvalue=$this->pdo->quote($newvalue);
				$cndc=array();
				foreach($keys as $field) {
					$value=$record[$field];
					$xvalue=$this->pdo->quote($value);
					$cndc[]="{$field}={$xvalue}";
				}
				$cndc=implode(' AND ',$cndc);
				$squp="
					UPDATE {$table} SET {$which}=$xnewvalue;
					WHERE {$cndc}
				";
				$n=$this->execSQL($squp);
				$msg="UPDATE RECORD SQL:{$squp}<br>";
				$msg.="{$n} records updated";
			}
			else {
				$msg ="NO UPDATABLE SQL<br>";
				$msg.=$sql;
			}

		}
		else $msg = "The value has no changed";
		return $msg;
	}
	protected function delete($table,$prp=[]) {
		$default=['cnd'=>[],'limit'=>0,'offset'=>0];
		$prp=gnrl::MergeProperties($default, $prp);
		# dbg::see($prp,"Properties of delete:");
		extract($prp);
		if( is_array($cnd) ) $cnd=implode(' AND ', $cnd);
		if( $cnd ) $cnd="WHERE {$cnd} ";
		$strlimit='';
		if( $limit )  $strlimit.="LIMIT {$limit} ";
		if( $offset ) $strlimit.="OFFSET {$offset} ";
		$sql="
			DELETE FROM {$table}
			{$cnd}
			$strlimit
		";
		# dbg::see($sql,"SQL delete:");
		$n=$this->execSQL($sql);
		return $n;
	}
	protected function transform($trf,$valors) {
		$tracefor=['']; # Put the name of the function you want to trace
		$resultat=array();
		foreach($trf as $reference=>$x ) {
			$res='';
			if( substr($x,0,1) == '=' ) {
				$parts=explode(',',substr($x,1));
				$function=array_shift($parts);
				$args=[];
				foreach($parts as $xx ) {
					if( array_key_exists($xx,$valors) )
						$args[]=$valors[$xx];
					else $args[]='';
				}
				if( in_array($function,$tracefor) ){
					dbg::see($valors,"Entra a transformar");
					echo "function to apply {$function} with <br>";
					dbg::see($args,"Arguments:");
				}
				if( method_exists($this,$function ) )
					$res=$this->$function($args);
				else if ( function_exists($function) )
					$res=$function($args);
				else
					$res="Transform {$function} not found";
				if( in_array($function,$tracefor) ) {
					if( is_array($res) )
						$sres=dbg::see($res,"Resultat",true);
					else $sres=$res;
					echo
						"Result after apply the function[{$function}]
						<br>
						{$sres}
					";
				}
			}
			else if( $x=='O.this')
				$res=$this;
			else if( array_key_exists($x,$valors) )
				$res=$valors[$x];
			$resultat[$reference]=$res;
		}
        return $resultat;
	}
	protected function serializeFirstArgument($arg) {
		# dbg::see($arg,"Arguments:");
		$a=$arg[0];
		# dbg::see($a,"Matriu a serialitzar:");
		return serialize($a);
	}
	protected function unserializeFirstArgument($arg) {
		# dbg::see($arg,"Arguments:");
		$a=$arg[0];
		# dbg::see($a,"Matriu a unserialitzar:");
		return unserialize($a);
	}
	protected function getTablesFromStructure() {
		# Retrieve data from structure
		$s=$this->structure();
		$t=[];
		foreach($s as $key=>$v) $t[]=$v['name'];
		return $t;
	}
	#===================================================================
	# General functions involving table parametres
	#===================================================================
	public function paramCounter($name,$ind='d') {
		$xname=$this->quote($name);
		$xind=$this->quote($ind);
		$sql="
			SELECT * FROM parametres
			WHERE grp='Contadors' AND nm={$xname} AND ind={$xind}
		";
		$r=$this->getAll($sql);
		if( count($r) ) {
			$rc=$r[0]['I0']+1;
			$xrc=$this->quote($rc);
			$sql="UPDATE parametres SET I0={$xrc}
				WHERE grp='Contadors' AND nm={$xname} AND ind={$xind}
			";
			$this->execSQL($sql);
		}
		else {
			$rc=1;
			$xrc=$this->quote($rc);
			$sql="INSERT INTO parametres
				(grp,nm,ind,I0)
				VALUES ('Contadors',{$xname},{$xind},{$xrc})
			";
			$this->execSQL($sql);
		}
		return $rc;
	}
	protected function getConditions($cnd,$text){
		$trz=false;
		if( $text ) {
			$text="%{$text}%";
			$selector=[];
			foreach($this->structure()[0]['fields'] as $field=>$t)
				$selector[]="{$field} like '{$text}' ";
			$selector="(".implode(" OR ",$selector).")";
		}
		else $selector='1';
		if( count($cnd)) $cnds=implode(" AND ",$cnd); else $cnds='1';
		if($trz) {
			here::seedetails($selector,"LIKE of Selector:");
			here::seedetails($cnds,"Conditions of query:");
		}
		return [$cnds,$selector];
	}
}
#=======================================================================
class pararray_V1 extends DB_admin_V1{
	// protected $plantillas=[''];
	// protected $comptador='billing';
	protected function structure(){
		$structure=[
			[
				'name'=>'pararray',
				'fields'=>[
					'grp'=>'VC50',
					'nm'=>'VC50',
					'ind'=>'INT',
					'what'=>'TEXT',
				],
				'primary'=>['grp','nm','ind'],
				'force'=>false,
			],
		];
		return $structure;
	}
	private $counter=[
			'name'=>'noName',
			'initial'=>'10000',
			'format'=>'%d',
			'pattern'=>"/^[1-9][0-9]{4,4}$/"
	];
	public function __construct($name,$counterClass,$prp) {
		parent::__construct();
		$this->connectV1($prp['bdconnector']);
		if(isset($prp['bdstruct'][$counterClass])) {
			$this->counter=$prp['bdstruct'][$counterClass];
			$cnd=[
				"grp='counters'",
				"nm='{$name}'",
				'ind=0',
			];
			list($ntotal,$page,$npages,$rows)=$this->getRawData($cnd,[]);
			if($ntotal == 0) {
				// Create the counter
				$whats=serialize($this->counter);
				$row=[
					'grp'=>'counters',
					'nm'=>$this->counter['name'],
					'ind'=>0,
					'what'=>$whats,
				];
				$this->saveRows([$row]);
			}
		}
	}
	protected function getPrp() {}
	private function getRawData($cnd,$sel) {
		$trz=false;
		$default=['text'=>'','npp'=>50,'page'=>0];
		$prp=gnrl:: MergeProperties($default, $sel);
		extract($prp);
		list($cnds,$selector)=$this->getConditions($cnd,$text);
		// =============================================================
		# Set off the table
		$table=$this->structure()[0]['name'];
		#===============================================================
		# Count the elements
		$sql="SELECT count(*) as n FROM {$table} WHERE {$selector}
			AND {$cnds}";
		$ntotal=$this->getAll($sql)[0]['n'];
		if( $ntotal == 0 ) {
			$page=0; $npages=0; $rows=[];
		}
		else {
			if( $npp ) {
				$npages=(int) ($ntotal/$npp);
				if( $ntotal % $npp ) $npages+=1;
				if( $page < 0 ) $page=0;
				if( $page >= $npages ) $page=$npages-1;
				$offset=$page*$npp;
				$limits="LIMIT {$npp} OFFSET {$offset} ";
			}
			else {$limits="";$npages=1;}
			$sql="SELECT * FROM {$table} WHERE $selector AND {$cnds}
				{$limits}
			";
			$rows=$this->getAll($sql);
			if($trz) dbg::seeDetails($rows,"SQL:{$sql}:
					Orders Sel:{$ntotal}");
		}
		return [$ntotal,$page,$npages,$rows];
	}
	public function getData($cnd,$sel){
		$trz=false;
		$default=['text'=>'','npp'=>50,'page'=>0,'decode'=>true];
		$prp=gnrl:: MergeProperties($default, $sel);
		extract($prp);
		# Get the rows of the data
		list($ntotal,$page,$npages,$rows)=$this->getRawData($cnd,$sel);
		# Transform to an indexed array
		$r=[];
		foreach($rows as $i=>$row){
			$group=$row['grp'];
			$name=$row['nm'];
			$ind=$row['ind'];
			if($decode) $what=utf8_decode($row['what']);
			else 		$what=$row['what'];
			$whata=unserialize($what);
			$row['what']=$whata;
			$r[$group][$name][$ind]=$row;
		}
		if($trz)dbg::seeDetails($r,"Rows by key");
		return [$ntotal,$page,$npages,$r];
	}
	public function updateData($data,$sel=[]) {
		$trz=false;
		$default=['encode'=>true];
		$prp=gnrl:: MergeProperties($default, $sel);
		extract($prp);
		#===============================================================
		# Transform to a list of rows
		$rows=[];
		foreach($data as $grp=>$dgrp)
			foreach($dgrp as $nm=>$dname )
				foreach($dname as $ind=>$row) {
					$c=["grp='{$grp}'","nm='{$nm}'","ind={$ind}"];
					list($n,$p,$np,$r)=$this->getData($c,$prp);
					if( count($r) ) {
						//dbg::seeDetails($r,"Matriu a la BD:");
						$rw=$r[$grp][$nm][$ind]['what'];
						$row['what']=
							gnrl:: MergeProperties($rw,$row['what']);
					}
					$whats=serialize($row['what']);
					if( $encode ) $whats=utf8_encode($whats);
					$rows[]=[
						'grp'=>$grp,
						'nm'=>$nm,
						'ind'=>$ind,
						'what'=>$whats,
					];
				}
		if($trz)dbg::seeDetails($r,"Rows generated");
		list($n,$msg)=$this->saveRows($rows);
		return [$n,$msg];
	}
	public function saveData($data,$sel=[]) {
		$trz=false;
		$default=['encode'=>true];
		$prp=gnrl:: MergeProperties($default, $sel);
		extract($prp);
		# Transform to a list of rows
		$rows=[];
		foreach($data as $grp=>$dgrp)
			foreach($dgrp as $name=>$dname )
				foreach($dname as $ind=>$row) {
					$whats=serialize($row['what']);
					if( $encode ) $whats=utf8_encode($whats);
					$rows[]=[
						'grp'=>$grp,
						'nm'=>$name,
						'ind'=>$ind,
						'what'=>$whats,
					];
				}
		if($trz)dbg::seeDetails($r,"Rows generated");
		list($n,$msg)=$this->saveRows($rows);
		return [$n,$msg];
	}
	protected function saveRows($rows){
		$trz=false;
		# Set off the table
		$table=$this->structure()[0]['name'];
		# Replace the rows
		$n=0; $msg=[];
		foreach($rows as $i=>$row) {
			$grp=$this->quote($row['grp']);
			$nm=$this->quote($row['nm']);
			$ind=$this->quote($row['ind']);
			$what=$this->quote($row['what']);
			$sql="REPLACE INTO {$table} (grp,nm,ind,what)
				VALUES(
					$grp,$nm,$ind,$what
				)
			";
			$n += $ni=$this->execSQL($sql);
			$msg[]="{$ni} Records replaced KEY=[{$grp}][{$nm}][{$ind}]";
		}
		return [$n,implode("\n",$msg)];
	}
	public function deleteParam($cnd) {
		# Set off the table
		$table=$this->structure()[0]['name'];
		# Delete according the conditions
		$n=$this->delete($table,['cnd'=>$cnd]);
		$cnds=implode(" AND ",$cnd);
		$msg="{$n} Records deleted by the conditions {$cnds}]";
		return [$n,$msg];
	}
	#===================================================================
	# Counters functions
	public function getNextCounter() {
		$name=$this->counter['name'];
		#===============================================================
		# Get the row of the counter data
		$cnd=[
			"grp='counters'",
			"nm='{$name}'",
			'ind=0',
		];
		list($ntotal,$page,$npages,$rows)=$this->getRawData($cnd,[]);
		$row=$rows[0];
		$what=unserialize($row['what']);
		if(isset($what['value']))$what['value']++;
		else $what['value']=$what['initial'];
		$whats=serialize($what);
		$row=[
			'grp'=>'counters',
			'nm'=>$name,
			'ind'=>0,
			'what'=>$whats,
		];
		$this->saveRows([$row]);
		$rc=$what['value'];
		if($what['format']) $rc=sprintf($what['format'],$what['value']);
		return $rc;
	}
	public function getMinCounter() {
		$name=$this->counter['name'];
		#===============================================================
		# Get the row of the counter data
		$cnd=[
			"grp='counters'",
			"nm='{$name}'",
			'ind=0',
		];
		list($ntotal,$page,$npages,$rows)=$this->getRawData($cnd,[]);
		$row=$rows[0];
		$what=unserialize($row['what']);
		$rc=$what['initial'];
		return $rc;
	}
	#===================================================================
	# Revisions management
	public function getDataWithRevision($grup,$name) {
		#===============================================================
		# Set off the table
		$table=$this->structure()[0]['name'];
		#===============================================================
		# look for the higher revision of this name
		$xg=$this->quote($grup);$xn=$this->quote($name);
		$sql="
			SELECT * FROM {$table}
			WHERE grp={$xg} AND nm={$xn}
			ORDER BY ind DESC;
			LIMIT 1
		";
		$rowsraw=$this->getALL($sql);
		$row=gnrl::toISO($rowsraw);
		#===============================================================
		# Transform the paramweters
		if( count($row) ){
			$d=$row[0];
			#dbg::seeDetails($d);
			$whata=unserialize($d['what']);
			$d['what']=$whata;
			$row=$d;
		}
		else {
			$row=[
				'grp'=>$grup,
				'nm'=>$name,
				'ind'=>0,
				'what'=>[
					'propietats'=>[],
					'error'=>'There is not any paramater'
				],
			];
		}
		return $row;
	}
	public function saveDataWithRevision($grup,$name,$dataiso) {
		$r=$this->getDataWithRevision($grup, $name);
		$currentwhat=serialize($r['what']);
		if( count($r) ) $rev=$r['ind']+1; else $rev=1;
		$data=gnrl::toUTF8($dataiso);
		$whats=serialize($data);
		if( $whats != $currentwhat ) {
			$rows=[];
			$rows[]=[
				'grp'=>$grup,
				'nm'=>$name,
				'ind'=>$rev,
				'what'=>$whats,
			];
			list($n,$msg)=$this->saveRows($rows);
		}
		else $msg="Revision no changed";
		return [$msg,$this->getDataWithRevision($grup,$name)];
	}
	public function undoDataWithRevision($grup,$name,$back=1) {
		$r=$this->getDataWithRevision($grup, $name);
		if( count($r) ) $rev=$r['ind']; else $rev=0;
		$old =$rev-abs($back); if( $old < 0 ) $old=0;
		#===============================================================
		# Set off the table
		$table=$this->structure()[0]['name'];
		#===============================================================
		# look for that revision
		$xg=$this->quote($grup);$xn=$this->quote($name);
		$sql="
			SELECT * FROM {$table}
			WHERE grp={$xg} AND nm={$xn} AND ind={$old}
		";
		$rowsraw=$this->getALL($sql);
		if( count($rowsraw) ) {
			$d=$rowsraw[0];
			$rev++;
			$rows=[];
			$rows[]=[
				'grp'=>$grup,
				'nm'=>$name,
				'ind'=>$rev,
				'what'=>$d['what'],
			];
			list($n,$msg)=$this->saveRows($rows);
		}
		else $msg="Revision {$old} no found";
		return [$msg,$this->getDataWithRevision($grup,$name)];
	}
	public function deleteDataWithRevision($grup,$name,$revision){
		#===============================================================
		# Set off the table
		$table=$this->structure()[0]['name'];
		#===============================================================
		# look for that revision
		$xg=$this->quote($grup);$xn=$this->quote($name);
		$sql="
			DELETE FROM {$table}
			WHERE grp={$xg} AND nm={$xn} AND ind<{$revision}
		";
		$n=$this->execSQL($sql);
		$msg="Delete from {$revision} revision to back.
			{$n} records deleted ";
		return [$msg,$this->getDataWithRevision($grup,$name)];
	}
	public function deleteRevision($grup,$name,$revision){
		#===============================================================
		# Set off the table
		$table=$this->structure()[0]['name'];
		#===============================================================
		# look for that revision
		$xg=$this->quote($grup);$xn=$this->quote($name);
		$sql="
			DELETE FROM {$table}
			WHERE grp={$xg} AND nm={$xn} AND ind={$revision}
		";
		$n=$this->execSQL($sql);
		$msg="Delete {$revision}. {$n} records deleted ";
		return [$msg,$this->getDataWithRevision($grup,$name)];
	}
	public function getListWithRevision($grup,$name) {
		#===============================================================
		# Set off the table
		$table=$this->structure()[0]['name'];
		#===============================================================
		# look for the higher revision of this name
		$xg=$this->quote($grup);$xn=$this->quote($name);
		$sql="
			SELECT * FROM {$table}
			WHERE grp={$xg} AND nm={$xn}
			ORDER BY ind DESC;
		";
		$rowsraw=$this->getALL($sql);
		$rows=gnrl::toISO($rowsraw);
		#===============================================================
		# Transform the paramweters
		if( count($rows) ){
			$x=[];
			foreach($rows as $i=>$row){
				$whata=unserialize($row['what']);
				$row['what']=$whata;
				$x[]=$row;
			}
			$rows=$x;
		}
		return $rows;
	}
	public function editionToArray($list) {
		$l=[];
		foreach($list as $i=>$lone) {
			$p=$lone['what']['propietats'];
			$pp=str_replace("\r\n","\n",$p);
			$ppp=explode("\n",$pp);
			$pppp=[];
			foreach($ppp as $pp) {
				$p=explode('=',$pp);
				if( array_key_exists(1,$p)) $pppp[$p[0]]=$p[1];
			}
			$lone['what']['propietats']=$pppp;
			$l[]=$lone;
		}
		return $l;
	}
}
#=======================================================================
class basicAC extends DB_admin_V1{
	public function help() {
		// Explanations about the class functionality
		if(0) dbg::seeDetails($data,"I've just arrived here:");
		$rc=[
			"<hr>",
			"[h3]The class 'basicAC'",
			"The aim of this class is give support in customize the
				DB operations for the tables past through the calling
				system",
			"The connection to DB is obtained from the parameters of the
				system input which are a <b>connector>b>, a
				<b>table name for the data</b>
				and a <b>table name for the index</b>",
			"Changing this parameters can manage severalsystems at
			 once",
			 "The connector is, as usual, the server, the database,
			 	the username and the paswword",
			"[h3]Principles",
			"[ul(]",
			[
			"[li]The SQL constructions must be only used in this class",
			"[li]It manages list of records through either a sql
				condition, a page, or the number of records per page",
			"[li]It manages also another list of records through a
				condition on the index of the data table",
			"[li]It manages only one record at once for edition",
			"[li]The only operation on the DB is add (insert) a record
				so as to have a behaviour as a true log",
			"[li]This way it can store the current values and the whole
				 history of the log ",
			],
			"[ul)]",
		];
		return htmlSimple::html($rc);
	}
	public static function checkValue($type,$value) {
		switch(strtolower($type)) {
			default:$rc=['','No identified type']; break;
			case 'date': case 'time': case 'datetime':
				$rc=self::dateTimeCheck($value);
				break;
			case 'integer': case 'int':
				$rc=self::integerCheck($value);
				break;
			case 'real': case 'float': case 'double':
				$rc=self::floatCheck($value);
				break;
			case 'bool': case 'boolean':
				$rc=self::boolCheck($value);
				break;
		}
		return $rc;
	}
	protected function structure(){
		// Required by the abstract class DB_Admin
		// Gives the complete structure of the DB table
		if(0) dbg::seeDetails($this->PARAMS,"Parameters:");
		list($fields,$key)=appData::fieldsDB();
		$name=$this->PARAMS['bdstruct']['tabledata'];
		$structure=[
			$name=>['name'=>$name,'fields'=>$fields,
				'primary'=>$key,'force'=>false,
			],
		];
		return $structure;
	}
	public function checkExistence($cnd,$inerrorRC=false) {
		// Check the existence of a condition
		$table=$this->PARAMS['bdstruct']['tabledata'];
		if(0) dbg::seeDetails($cnd,"check Condition:");
		if( $this->checkCondition($cnd, $table) == '') {
			$br=$this->structure()[$table];
			if(0) dbg::seeDetails($br,"Data table {$table}");
			$sql="
				SELECT * FROM {$br['name']}
				WHERE {$cnd}
				LIMIT 1
			";
			$r=$this->getAll($sql);
			if(0) dbg::seeDetails($r,"SQL to test:");
			$rc=count($r)>0;
		}
		else $rc=$inerrorRC;
		return $rc;
	}
	protected function getPageRecords($cnd,$pg,$npp) {
		// It retrieves a page
		// Get records from a set of conditions ('a')
		$br=$this->structure()[
			$tbluse=$this->PARAMS['bdstruct']['tabledata']
		];
		extract($br);
		// Check the condition lead to a rigth SQL
		$error=$table=[]; $ntotal=$npages=$page=0;
		if( ($e=$this->checkCondition($cnd,$tbluse)) == '' ) {
			$sql="
				SELECT count(DISTINCT()) as n
				FROM {$name}
				WHERE {$cnd}
			";
			$ntotal=$this->getAll($sql)[0]['n'];
			if( $npp == 0 ) $limits='';
			else if( $ntotal <= $npp ){
				$limits='';$npages=1;
			}
			else {
				$npages=floor($ntotal/$npp) + ($ntotal % $npp ? 1 :0);
				$page=$pg;
				if($pg<0) $page=0;
				if($pg>$npages-1) $page=$npages-1;
				$offset=$page*$npp;
				$limits="LIMIT {$npp} OFFSET {$offset}";
			}
			$sql="SELECT voucher FROM {$name} WHERE {$cnd}
				GROUP BY voucher
				{$limits}
			";
			$numbers=$this->getAll($sql);
			$rows=[];
			if( count($numbers) ) {
				$l=[];
				foreach($numbers as $i=>$r ) $l[]=$r['voucher'];
				$list=implode(", ",$l);
				$sql="
					SELECT * FROM {$name}
					WHERE voucher IN({$list})
					ORDER BY voucher, type, ind
				";
				$rows=$this->getAll($sql);
				foreach($rows as $row) {
					$voucher=$row['voucher'];
					$type=$row['type'];
					$ind=$row['ind'];
					$rowexpanded=$this->expand($row);
					$table[$voucher][$type][$ind]=$rowexpanded;
				}
			}
		}
		else {
			$s="Error: Not valid SQL condition {$e} for [{$cnd}]";
			$error[]=$s;
		}
		// Prepare the data to return
		$rc=[$table,
			'pg'=>
			['ntotal'=>$ntotal,'npages'=>$npages,'page'=>$page,'npp'=>$npp]
		];
		if( count($error) ) $rc['error']=$error;
		return $rc;
	}
	protected function getRecords($cnd) {
		// It retrieves records from a condition and a table
		// Get records from a set of conditions
		$br=$this->structure()[
			$tbluse=$this->PARAMS['bdstruct']['tabledata']
		];
		extract($br);
		// Check the condition lead to a rigth SQL
		$error=$table=[]; $ntotal=0;
		if( ($e=$this->checkCondition($cnd,$tbluse)) == '' ) {
			$sql="SELECT * FROM {$name} WHERE {$cnd}
				ORDER BY voucher, type, ind
			";
			$rows=$this->getAll($sql);
			foreach($rows as $row) {
				$voucher=$row['voucher'];
				$type=$row['type'];
				$ind=$row['ind'];
				$rowexpanded=$this->expand($row);
				$table[$voucher][$type][$ind]=$rowexpanded;
			}
			$ntotal=count($table);
		}
		else {
			$s="Error: Not valid SQL condition {$e} for [{$cnd}]";
			$error[]=$s;
		}
		// Prepare the data to return
		$rc=[$table,
			'pg'=>
			['ntotal'=>$ntotal,'npages'=>1,'page'=>0,'npp'=>0]
		];
		if( count($error) ) $rc['error']=$error;
		return $rc;
	}
	protected function maxRecord($voucher,$type) {
		// Looking for the maximum number of the list of a
		// voucher and type
		$br=$this->structure()[
			$this->PARAMS['bdstruct']['tabledata']
		];
		if(0) dbg::seeDetails($br,"Table data: for {$table}");
		// look for the voucher
		$sql="
			SELECT * FROM {$br['name']}
			WHERE voucher={$voucher} AND type='{$type}'
			ORDER BY ind DESC
		";
		$r=$this->getAll($sql);
		if(0) dbg::seeDetails($r,"Max record {$sql}");
		$maxind=0;
		if( count($r) ) $maxind=$r[0]['ind'];
		if(0) echo "Max used index is:{$maxind}<br>";
		return $maxind;
	}
	protected function keyNumber($voucher) {
		// Check if the key is compatible with the situation
		if(0) dbg::seeDetails($voucher,"Input keyNumber:");
		if(0) dbg::seeDetails($this->PARAMS,"Parametros");
		$class='counterFormat';
		$name=$this->PARAMS['bdstruct'][$class]['name'];
		$cl=new pararray_V1($name,$class,$this->PARAMS);
		$e='';
		$insert = ((int) $voucher) <  $cl->getMinCounter();
		$cnd="voucher={$voucher}";
		$check=$this->checkExistence($cnd);
		if($insert && $check ) $e="Key[{$voucher}] already exists";
		else if(!$insert && !$check) $e="Key[{$voucher}] doesn't exists";
		return $e;
	}
	protected function keyUpdate($voucher) {
		// To get the numbers of the new actions
		$class='counterFormat';
		$name=$this->PARAMS['bdstruct'][$class]['name'];
		$cn=$this->PARAMS['bdstruct'][$class];
		// Check if necessary
		$err='';
		$num=new pararray_V1($name,$class,$this->PARAMS);
		if( $voucher < $num->getMinCounter() ) {
			// Get the number of new voucher
			$new=$num->getNextCounter();
			if( preg_match($cn['pattern'],$new) ) $voucher=$new;
			else $err="The format of the number {$new} is wrong";
		}
		if( true && $err ) die("keyUpdate Error:{$err}");
		return $voucher;
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
		$sql ="INSERT INTO {$br['name']} ({$sqins}) ";
		$sql.="VALUES ( {$sqval} )";
		if(0) dbg::seeDetails($sql,"SQL GENERATED");
		$n=$this->execSQL($sql);
		$di="{$n} records inserted of key {$keys}";
		return $di;
	}
	protected static function compileToDb($record) {
		// Check the correctness for DB
		if(0) dbg::seeDetails($record,"Input compileToDb");
		list($f,$primary)=appData::fieldsDB();
		$data=$error=[];
		foreach($record as $field=>$val){
			if(isset($f[$field])) {
				$er=[];
				list($v,$er[])=self::arrayToJSON($val);
				switch($f[$field]) {
					case 'INT': case 'AINT':
						list($v,$er[])=self::integerCheck($v);
						break;
					case 'BOOL':
						list($v,$er[])=self::boolCheck($v);
						break;
					case 'DOUB':
						list($v,$er[])=self::floatCheck($v);
						break;
					case 'DTIM':
						list($v,$er[])=self::dateTimeCheck($v);
						break;
					default:
					case 'TEXT': case 'VC20': case 'VC50': case 'VCFF':
				}
				$data[$field]=$v;
				$err=[];
				foreach($er as $e) if($e) $err[]="{$field}:{$e}";
				$error=array_merge($error,$err);
			}
			else $error[]="{$field} not found";
		}
		return [$data,$error];
	}
	private function checkCondition($cnd,$table) {
		$br=$this->structure()[$table];
		if(0) dbg::seeDetails($br,"Data table {$table}");
		$sql="
			SELECT * FROM {$br['name']}
			WHERE {$cnd}
			LIMIT 1
		";
		if(0) echo "SQL is {$sql} <br>";
		return $this->checkSQL($sql);
	}
	private function expand($row) {
		list($f,$primary)=appData::fieldsDB();
		$expanded=[];
		foreach($row as $field=>$val){
			switch($f[$field]) {
				default: case 'INT': case 'AINT': case 'BOOL':
				case 'DTIM': case 'DOUB':
					$v=$val;
					break;
				case 'TEXT': case 'VC20': case 'VC50': case 'VCFF':
					$v=json_decode($val,true);
					if( is_null($v) ) $v=$val;
			}
			$expanded[$field]=$v;
		}
		return $expanded;
	}
	private static function arrayToJSON($val) {
		$s='gnrl';
		if( class_exists($s) ) $s='gnrl';
		else if ( class_exists('base') ) $s='base';
		// Convert any array to json so as to become a string
		$er='';
		if( is_array($val) ) {
			$va=$s::toUTF8($val);
			$v=json_encode($va);
			if( $v === false ) {
				$er="Error encoding json ".serialize($val);
				$v='';
			}
		}
		else $v=$val;
		return [$v,$er];
	}
	private static function integerCheck($v) {
		$er='';
		if( $v === '' || $v === null)
			;
		else if( is_numeric($v) ) {
			$vv=str_replace(',','.',$v);
			$vd=(float) $vv;
			$vi=(int) $vv;
			if( $vi != $vd )
				$er="El {$v} es float i se espera un itneger";
			else
				$v=$vi;
		}
		else
			$e="The field {$v} is not 'INT' or 'AINT";
		return [$v,$er];
	}
	private static function floatCheck($v) {
		$er='';
		if( $v === '' || $v === null)
			;
		else {
			$vv=str_replace(',','.',$v);
			if(0) dbg::seeDetails($vv,"Conversion to double");
			if( is_numeric($vv) ) $v=(float) $vv;
			else $er="This is not 'DOUBLE' or 'FLOAT";
		}
		return [$v,$er];
	}
	private static function boolCheck($v) {
		$er='';
		if( $v === '' || $v === null)
			;
		else if( is_bool($v) )
			$v=$v?1:0;
		else if( is_numeric($v) ) {
			$v=(int) $v;
		}
		else
			$er="The field {$field} is not 'BOOLEAN' ";
		return [$v,er];

	}
	private static function dateTimeCheck($v) {
		$fmts=['Y-m-d H:i:s','m-d-Y H:i:s','Y-m-d|','d-m-Y|', 'm-Y|','Y|'];
		$er='';
		if( $v === '' || $v === null)
			;
		else {
			foreach($fmts as $fmt) {
				$dt=dateTime::createFromFormat($fmt,$v);
				if($dt !== false ) break;
			}
			$v='';
			if($dt === false ) $er="Date time format not contemplate";
			else $v=$dt->Format('Y-m-d H:i:s');
			if(0) echo "result:{$v}<br>";
		}
		return [$v,$er];
	}
}
class dataStructureManagement extends basicAC{
	const NEWMSG='newKey#';
	public static function dataStructureModel() {
		// It operates at three level structure
		// The only operation allowed is appending records
		// to log any kind of updating
		// A single record is usually a array with the fields of the table
		$structure=[
			'key'=>[
				'type'=>[
					'singleRecord1(ind=1)'=>'Single record for the DB',
					'singleRecord2(ind=2)'=>'Single record for the DB',
					'singleRecord3(ind=3)'=>'Single record for the DB',
					'...',
					'singleRecordn(ind=n)'=>'Single record for the DB',
				]
			]
		];
		return $structure;
	}
	public function help() {
		$x=file_get_contents("dataStructureDefinition.xml");
		$x="<pre>".htmlentities($x)."</pre>";
		$rc=[ "<hr>",
			"[h3]The class 'dataStructureManagement'",
			"(extends the class 'basicAC' explained below)",
			"The aim of this class is give support on managing
				the whole strcuture of three dimensional data",
			"This structure can be represented by this XML text",
			$x,
			" ",
			"In this class the structure is always a key numbers list
				(the name of the key it depends of the basic class)
				from which hangs the types structures.
			"
		];
		return htmlSimple::html($rc).parent::help();
	}
	protected function getList($cnd) {
		// Get the List of every db record recovered from
		// a set of conditions
		if(0) dbg::seeDetails($args,"Data entered:");
		// Pick up the needed data from arguments
		if( is_array($cnd) ) $cnd=implode(" AND ",$cnd);
		if(0) dbg::seeDetails($cnd,"Select from {$tbluse} with condition");
		// Avoid the element of pagination
		$table=$this->getRecords($cnd)[0];
		return $table;
	}
	protected function getListClean($cnd) {
		// Get the List of every db record recovered from
		// a set of conditions avoiding the 'out' records and
		// those which are taken out by them
		$d=$this->getList($cnd);
		if(0) dbg::seeDetails($d,"Data recovered");
		return $this->cleanAList($d);
	}
	protected function cleanAlist($d) {
		// Apply the out to the list obtained
		foreach($d as $key=>$dd) {
			foreach($dd as $type=>$ddd) if($type == 'out' ) {
				foreach($ddd as $i=>$out) {
					$type=$out['T0'];
					$ind=$out['I0'];
					unset($d[$key][$type][$ind]);
				}
			}
			unset($d[$key]['out']);
		}
		if(0) dbg::seeDetails($d,"Data cleaned");
		return $d;
	}
	protected function hideNoNeededInd($d) {
		// Delete the no needed Ind for types which are always only one
		$clean=[];
		foreach($d as $key=>$dd) {
			$new=[];
			foreach($dd as $type=>$ddd) {
				if( $type != 'noaction' ) {
					foreach($ddd as $i=>$dddd) break;
					$new[$type]=$dddd;
				}
				else {
					$new[$type]=$ddd;
				}
			}
			$clean[$key]=$new;
		}
		if(0) dbg::seeDetails($clean,"Clean");
		return $clean;
	}
	protected  function checkList($args) {
		// Check a data structure fits the expected one
		// otherwise it emmits an error.
		if(0) dbg::seeDetails($args,"Data entered:");
		// Pick up the needed data from arguments
		$recList=$args;
		if(0) dbg::seeDetails($recList,"List for data table");
		$error=[];
		// Check a three level data is arrived
		list($done,$er)=self::indexList($recList);
		// $this->push($er,"Compiling strcuture");
		$this->ercl->push($er,"Compiling strcuture");
		if(0) dbg::seeDetails($done,"First 'a' filter List structure");
		// Check the right format of
		$doneone=$this->checkKeyInsertionUpdating($done);
		if(0) dbg::seeDetails($doneone,"First 'b' filter Key analysis");
		// Check a every record to form the calculated record
		$dtwo = $this->checkEachRecord($doneone);
		if(0) dbg::seeDetails($dtwo,"Second filter calculate records");
		// Expand the records if needed
		$dthree = $this->expandTypes($dtwo);
		if(0) dbg::seeDetails($dthree,"Third filter expand the types");
		// Are the recerds OK for the DB
		$dfour = $this->isOKToDB($dthree);
		if(0) dbg::seeDetails($dfour,"Fourth filter good for DB");
		// Number the records to allow appending records
		$dfive = $this->recordNumbering($dfour);
		if(0) dbg::seeDetails($dfive,"Fifth filter to renumber");
		// Return the good and wrong parts
		return ['readyToAppend'=>$dfive,'error'=>$this->ercl->get()];
	}
	protected function appendTo($args) {
		// Check a data structure fits the expected one
		// otherwise it emmits an error or
		// it append the data to the DB table
		if(0) dbg::seeDetails($args,"Data entered:");
		// Pick up the needed data from arguments
		$keyList=$args;
		if(0) dbg::seeDetails($keyList,"Records to append");
		// Checking the data arrived
		$check=$this->checkList($args);
		$this->ercl->push($check['error']);
		if(0) dbg::seeDetails($check,"Data after check:");
		if( isset($check['error']) && count($check['error']) )
			$ins=["errors prevent to append data"];
		else {
			$dout=$check['readyToAppend'];
			$dappend=[];
			foreach($dout as $key=>$d) {
				$key=$this->keyUpdate($key);
				$new=self::NEWMSG;
				array_walk_recursive($d,function(&$val,$k) use($new,$key){
					if( in_array($k,['properties','userdata']) ) {
						$val=str_replace($new,$key,$val);
					}
				});
				$dappend[$key]=$d;
				// Index the stack of data appeneded
				foreach($d as $type=>$z) ;
				foreach($z as $ind=>$zz) ;
				global $clwsindex;
				$ins['indexation']=$clwsindex->indexing($ind);
			}
			// Append finally in DB
			$ins=$this->addToDb($dappend);
			if(0) dbg::seeDetails($ins,"Diagnostic after addition:");
		}
		$rc=['diag'=>$ins];
		return $rc;
	}
	protected function aggregateCalculations($din) {
		// To calculate totals of structure
		if(0) dbg::seeDetails($din,"Data entered for totals");
		$agg=['n'=>0,'sum'=>0,'max'=>-PHP_INT_MAX,'min'=>PHP_INT_MAX,
			'sum2'=>0
		];
		$dout=[];
		foreach($din as $key=>$data) {
			foreach($data as $type=>$list) {
				$first=true;
				foreach($list as $ind=>$onerecord) {
					if($first) {
						foreach($onerecord as $field=>$v)
							$sum[$field]=$agg;
						$first=false;
					}
					foreach($onerecord as $field=>$v)
						if( is_numeric($v) ) {
							$sum[$field]['n']++;
							$sum[$field]['sum'] += (float) $v;
							$sum[$field]['sum2'] += $v*$v;
							if( $v > $sum[$field]['max'] )
								$sum[$field]['max']=$v;
							if( $v < $sum[$field]['min'] )
								$sum[$field]['min']=$v;
						}
				}
				$dout[$key][$type]['aggregates']=$sum;
			}
		}
		if(0) dbg::seeDetails($dout,"Data of calculated totals");
		return $dout;
	}
	private function checkKeyInsertionUpdating($din) {
		// Several checks to the whole structure
		if(0) dbg::seeDetails($din,'checkKeyInsertionUpdating:');
		$m="Insertion/Update";
		// Check the right format of the KEY
		$er=$dout=[];
		foreach($din as $key=>$x )  {
			if( ! is_numeric($key))$er[]="KEY {$key} is not number";
			else if( ($e=$this->keyNumber($key))  ) $er[]=$e;
			else $dout[$key]=$x;
		}
		$this->ercl->push($er);
		return $dout;
	}
	private function checkEachRecord($din) {
		if(0) dbg::seeDetails($din,"Data to check each record:");
		// Check a every record to form the calculated record
		$dout=$error=[];
		foreach($din as $key=>$dl)
			foreach($dl as $type=>$dll) {
				$clname=indexTypes::classOfType($type);
				$cl=new $clname;
				foreach($dll as $ind=>$onerecord) {
					$id="{$key}-{$type}-{$ind}";
					// This function must be in the child class
					$x=$cl->compile($onerecord,$this);
					if(0) dbg::seeDetails($x,"Return of {$id}");
					if( count($x[1]) == 0 ) $dout[$key][$type][$ind]=$x[0];
					else $this->ercl->push($x[1],"Single record:{$id}");
				}
			}
		return $dout;
	}
	private function expandTypes($din) {
		// Some types can require expand its record into several
		// For example,to update a type you need to put out
		// the previous one
		$m="Expanding";
		$dout=$error=[];
		foreach($din as $key=>$dl) {
			list($x,$kl)=appData::fieldsDB();
			$y=$this->getListClean("{$kl[0]}={$key}");
			$dnew=[];
			foreach($dl as $type=>$dll) {
				$clname=indexTypes::classOfType($type);
				$cl=new $clname;
				foreach($dll as $ind=>$dlll) {
					$x=$cl->expand($key,$type,$ind,$dlll,$y);
					if(0) dbg::seeDetails($x,"Return individual expand:");
					if( $x['error'] == '' ) $dnew=$x['expanded'];
					else $this->push($x['error'],$m);
				}
			}
			if(0) dbg::seeDetails($dl,"Array for the expand");
			if(0) dbg::seeDetails($dnew,"Expand Types-Expanded records");
			foreach($dnew as $ty=>$d) {
					if( isset($dl[$ty]) )
						$dl[$ty]=array_merge($dl[$ty],$d);
					else
						$dl[$ty]=$d;
			}
			if(0) dbg::seeDetails($dl,"End array");
			$dout[$key]=$dl;
		}
		if(0) dbg::seeDetails($dout,"Expand Types- Return value");
		return $dout;
	}
	private function isOKToDB($din) {
		if(0) dbg::SeeDetails($din,"Input isOKToDB");
		$m="DB Validity ";
		// Check a every record to form the calculated record
		$dout=$error=[];
		foreach($din as $key=>$dl)
			foreach($dl as $type=>$dll)
				foreach($dll as $ind=>$onerecord) {
					list($d,$er) = self::compileToDB($onerecord);
					if( $er ) $this->ercl->push($er,$m);
					else $dout[$key][$type][$ind]=$d;
				}
		return $dout;
	}
	private function recordNumbering($din) {
		// To ensure to be appened to the lines of this kind now
		$dout=[];
		foreach($din as $key=>$data) {
			foreach($data as $type=>$list) {
				$ind=$this->maxRecord($key,$type)+1;
				foreach($list as $iin=>$onerecord) {
					$dout[$key][$type][$ind++]=$onerecord;
				}
			}
		}
		return $dout;
	}
	private function addToDb($dins) {
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
	private static function indexList($datain,$options=[]) {
		// Check the structure of the data like an array of
		// three dimensions: the voucher number, the type, and the index
		$error=$dataout=[];
		if( ! is_array($datain) ) $error[]="list entered not array";
		else foreach($datain as $voucher=>$rl) {
			if( ! is_array($rl) )
				$error[]="list {$voucher} not array";
			else foreach( $rl as $type =>$rll)
				if( !is_array($rll) )
					$error[]="list {$voucher}-{$type} not array";
				else foreach($rll as $ind=>$onerecord )
					if(!is_array($onerecord) )
						$error[]="list {$voucher}-{$type}-[$ind] not array";
					else {
						 $dataout[$voucher][$type][$ind]=$onerecord;
					}
		}
		return [$dataout,$error];
	}
}
?>

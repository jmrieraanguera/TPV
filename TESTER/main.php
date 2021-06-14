<?php
require_once('class_appData.php');
require_once('classes_HTML_multi_stack.php');
require_once('../classes_support.php');
require_once('../classes_DBMV1__abstract.php');
require_once('classes_test.php');
class webPlan {
    // This program doeSn't test a web servce so it always callS
    // the question in a DIRECT WAY
    public static function list() {
		$listOfQuestions=[
            'seeOrder', // To see the whole order status
            'setTestSC', // Insert a Shoppinmg Cart (99999999) to test
            'eraseTestSC', // Delete erase test Shopping Cart
            'sendToTPVSender', // Send a message to be tested
        ];
		return $listOfQuestions;
	}
    public static function memory() {
		// The class to manage the session is made global
		// to be reachable from everywhere
        // It is set in the nucleus::__construct
		global $clsession;
		$memory=[
            // What kind of test
            // 0 Both SERVER and USER questions
            // 1 Only the SERVER answer
            // 2 Only the USER answer
            'whom'=>0,
            // The type of trenasaction
            // [0,3,6,7,8,9] are valid transactions
            'type'=>8,
        ];
		return $memory;
	}
    public static function classCall($name,$arguments){
		$clws=new directTester;
		return $clws->call($name,$arguments);
	}
    public static function planMultipages() {
        // There is no multiplan pages in the tester
    	$plans=[];
    	return $plans;
    }
}
class main {
    const HTML='webservicePlot';
    const AJAX='ajaxReply';
    // const WS = 0;
    // Prepare the objects for managing the site
    protected $browsertype;
    // The title in the tab of the browser
    protected $title='';
    private $actual;
    public function __construct() {
        global $clsession;
        // The memory designed into actual
        $this->actual=webPlan::memory();
        // Update actual with whatever read in the $_SESSION
        $this->actual=$this->update($this->actual,$_SESSION);
        // Update with the actual with the arrived $_POST
        foreach($_GET as $key=>$v) $_POST[$key]=$v;
        $this->actual=$this->update($this->actual,$_POST);
        // Equalize the two elements of memory
        if( isset($this->actual['voucher']) )
            $this->actual['dirspec']['item']=$this->actual['voucher'];
        // Update the SESSION if needed
        $clsession->updateSession($this->actual);
    }
    private function update($data,$newvalues) {
        $newdata=[];
        foreach($data as $key=>$value) {
            if( isset($newvalues[$key]) ) {
                if( is_array($value) )
                    $newdata[$key]=$this->update($value,$newvalues[$key]);
                else
                    $newdata[$key]=$newvalues[$key];
            }
            else $newdata[$key]=$value;
        }
        return $newdata;
    }
    public function execute() {
        global $clsession,$classes;
        // Open all the classes involved in order to interact each other
        $classes=[];
        foreach(webPlan::list() as $i=>$name) {
            if( class_exists($name) ) $cl= new $name;
            else $cl=new noImplemented($name);
            $classes[$name]=$cl;
        }
        // Add two classes to manage the exceptions
        $classes['noQuestion']=new noQuestion;
        // Select the question which have to be answered
        $info=dbg::seeDetails($_POST,"Arrived data:",true);
        $question='noQuestion';
        if(isset($_POST['question']) && $_POST['question'])
            $question=$_POST['question'];
        $clsession->updateSession(['question'=>$question]);
        // To look for the arguments if they exist.
        $arguments=[];
        if(isset($_POST['argument'])) $arguments=$_POST['argument'];
        if(0) dbg::seeDetails($_POST,"POST in execute:");
        // Select the class to be called for the answer
        // $cl=$classes[$question];
        // Call the selected class operation
        $builder=$classes[$question]->call($arguments);
        // Prepare the output
        global $clbuilder;
        $clbuilder=new $builder([
            'question'=>$question,'arguments'=>$arguments,
        ]);
        return $clbuilder->build();
    }
}
class directTester {
    private $cl;
    public function __construct( $par=[] ) {
        $this->cl=new tester;
    }
    public function call($action,$arguments) {
        $x=$this->cl->$action([$arguments]);
        return [ 'data'=>$x, 'errorSOAP'=>'', 'action'=>$action,
            'arguments'=>$arguments,'trace'=>''];
    }
}
class webservicePlot extends html5_Builder_base {
    // Basic class to build the HTML of the whole page
    protected function prpHead() {
        $prp=[  'charset'=>'utf8','title'=>'NC WS',
            'fave'=>"sizes='64x64' href='images/faveicon-16x16.png'",
        ];
        return $prp;
    }
    public function build() {
        if(0) dbg::seeDetails($plan,"Selected plan:");
        // The first thing is the contents.
        // Fill the body, it can be neccesary generate things
        // for the basic parts like files of scripts or styles
        $this->my_header();
        $this->mainPart();
        // Fill the buffers of the head
        $this->title(['title'=>appData::getParam('title')]);
        // Fill the basic stack
        $this->charset();
        $this->faveicon();
        $this->viewport();
        // Fill the scripts
        $this->jQuery();
        $this->icons();
        $this->push('jsfiles','../browser_OQPLibV2.js');
        $this->fileScripts();
        // Fill the styles
        $this->basicStyles();
        $this->commonStyles();
        $this->mainStyles();
        // Compact of buffers
        $z=$this->compactBuffers([ // Compact the buffers
            'head'=>['basic','linkstyles','styles','linkscripts',
                    'scripts'],
            'body'=>['body'],
        ]);
        if( headers_sent() ) $msg="HEADERS SENT"; else $msg='';
        $rc=[];
        $rc[]="<!DOCTYPE html>";
        $rc[]="<html >";
        $rc[]="<head>";
        $rc[]=$z['head'];
        $rc[]="</head>";
        $rc[]="<body>";
        $rc[]="<div class=layout>";
        $rc[]=$msg;
        $rc[]=$z['body'];
        $rc[]="</div>";
        $rc[]="</body>";
        $rc[]="</html>";
        $chset='utf8';
        if(isset($this->prp['charset'])) $chset=$this->prp['charset'];
        switch($chset) {
            case 'utf8': header("Content-type:text/html charset=utf-8");
        }
        return implode("\n",$rc);
    }
    public function mainPart() {
        global $classes;
        if(0) dbg::seeDetails($this->prp,"Input data:");
        $l=webPlan::list();
        $cl=$classes[$this->prp['question']];
        $execquestion=$cl->showAfter();
        foreach($l as $i=>$name) {
            $open = $execquestion==$name ? 'open' : '';
            $this->putBlock($i,$name,$open,$execquestion);
        }
        if( $this->prp['question'] == 'noQuestion' )
            if( $classes['noQuestion']->display() )
                $this->putBlock(1000,'noQuestion','open',$execquestion);
        $this->stylesBlocks();
    }
    protected function putBlock($i,$name,$open,$execquestion) {
        global $classes;
        $cl=$classes[$name];
        list($html,$styles,$scripts)=$cl->html($i);
        $title=$cl->title($i);
        $this->push('styles.blocks',$styles);
        $this->push('scripts',$scripts);
        // Display the result of the call
        $r=["",['html'=>"",'styles'=>'','script'=>'']];
        if($this->prp['question'] == $name || $cl->type() != 'WS')
            $r=$this->displayResult($cl->response());
        else if( $execquestion == $name ) {
            $r=$this->displayResult($cl->response());
        }
        $rhtml=$r[1]['html'];
        $this->push('styles',$r[1]['styles']);
        $this->push('scripts',$r[1]['script']);
        if(isset($r[1]['jslib']) && $r[1]['jslib'])
            $this->push('jsfiles',$r[1]['jslib']);
        $trz=$r[0];
        $this->question($i,$name,$open,$title,$html,$rhtml,$trz);
    }
    protected function question($j,$name,$open,$title,$html,$rh,$tr) {
        // Functions prepare the form for every question
        $rc=[];
        $rc[]="<details class='fquestion fhwrapper' {$open} ><summary>";
        $rc[]="[{$j}.{$name}]:<b>{$title}</b>";
        $rc[]="</summary>";
        $rc[]="<div class=qwrapper>";
        $rc[]="<div class=qleft>";
        $rc[]=$html;
        $rc[]="</div>";
        $rc[]="<div class=qright>";
        $rc[]=$rh;
        $rc[]="</div>";
        $rc[]="</div>";
        if($tr) $rc[]=$tr;
        $rc[]="</details>";
        $rcs = implode("\n",$rc);
        $this->push('body',$rcs);
        return $rcs;
    }
    protected function stylesBlocks() {
        $st="
            .qwrapper {
                width:100%;
                display:flex;
                border:2px solid darkgray;
            }
            .qleft {
                flex:1;
                border:2px solid darkgray;
            }
            .qright {
                flex:4;
            }
        ";
        $rc="<style>{$st}</style>";
        $this->push('styles',$rc);
        return $rc;
    }
    protected function displayResult($result) {
        // Get the display part of the return
        $html='';
        if( isset($result['data']['html']) ){
            $html=$result['data']['html'];
            $result['data']['html']='extracted';
        }
        else
            $html=[
                'html'=>dbg::seeD($result['data'],"Data:"),
                'styles'=>'','script'=>''];
        // Prepare the data for the following of the calculation
        $text="Data returned";
        if($e=isset($d['error'])) $text.="Some error has occurred";
        $trace=$result['trace']; unset($result['trace']);
        if( is_array($trace) || is_object($trace))
            $s=dbg::seeD($trace,"Trace of WS call");
        else if($trace)
            $s="<details><summary>Trace of WS call</summary>
                {$trace}</details>";
        else
            $s="<span style='color:orange'>WITHOUT</span>";
        // $tr[]="</div>";
        $tr=[];
        $tr[]=$s;
        $trs=implode("\n",$tr);
        $result['trace']=$trs;
        $details=dbg::seeDNotitle($result,0,0);
        $color=$e ? 'brown':'darkblue';
        $s="style='background-color:{$color}; color:white;'";
        $t ="<details {$s}>";
        $t.="<summary>$text</summary>";
        $t.=$details;
        $t.="</details>";
        return [$t,$html];
    }
    protected function my_header() {
        $this->prp['headline']=appData::getParam('headline');
        $e=isset($_POST['error']) ? $_POST['error'] :'';
        $rc=[];
        $rc[]="<div class='fhwrapper fhtop'>";
        $rc[]="<div class=fhleft >";
        $rc[]=$this->prp['headline'];
        $rc[]="</div>";
        $rc[]="<div class=fhcenter >";
        $rc[]=$this->memory();
        $rc[]="</div>";
        $rc[]="</div>";
        $rc[]="<div id=sentinel class=fherror >Internal error</div>";
        $rc[]="<div id=er_global class=fherror>{$e}</div>";
        $rcs=implode("\n",$rc);
        $this->push('body',$rcs);
        $this->stylesHead();
        return $rcs;
    }
    protected function memory() {
        global $clsession;
        $status=$clsession->array();
        $r ="Session and Cookies ";
        $r.="<form method=post style='display:inline-block;'>";
        $r.="<button name=reset value='XRESET'>RESET SESSION</button>";
        $r.="</form>";
        foreach($status as $key=>$v) $stat[$key]=$v;
        $rc=[];
        $rc[]=dbg::seeD($stat,$r,0,0);
        return implode("\n",$rc);
    }
    protected function stylesHead() {
        $papel=appData::getParam('colors.ercolor');
        $tinta='white';
        $st="
            .fhwrapper {
                box-sizing:border-box;
                margin: 0 auto;
                padding: 0em 0em;
                display:flex;
                color:darkBlue;
                /* border-bottom:2px solid brown; */
            }
            .fhtop {
                background-color:#ddd;
            }
            .fhleft {
                box-sizing:border-box;
                flex:3;
                text-align:left;
                font-size:1.17em;
                font-weight:bold;

            }
            .fhcenter  {
                box-sizing:border-box;
                text-align:left;
                /* padding:1em 0; */
                font-size:1.17em;
                flex:5;
            }
            .fhcistella  {
                box-sizing:border-box;
                text-align:right;
                font-size:0.5em;
            }
            .fherror {
                margin:1em 0;
                background-color:{$papel};
                color:{$tinta};
                text-align:center;
            }
            .fhline {
                margin:1em 0;
            }
            .fhhelp {
                color:black;
            }
            .fanswer {
                padding: 1em;
            }
            .fquestion {
                margin:1em 0;
                /* border:2px solid gray; */
            }
            .fline { display:flex; width:100%}
            .fprompt { flex:1;text-align:right;}
            .finp { flex:2;}
        ";
        $rc="<style>{$st}</style>";
        $this->push('styles',$rc);
        return $rc;
    }
}
class ajaxReply {
    // Class for building the AJAX Replies
    protected $prp;
    protected $fordebugging=false;
    public function __construct($par=[]) {
        $this->prp=$par;
    }
    public function build() {
        $out=$this->prp['response'];
        if(0) dbg::seeDetails($out,"Response:");
        if( $this->fordebugging ) {
            $outs=$out;
            array_walk_recursive($outs,function(&$v,$k){
                if( is_string($v))
                    $v=htmlentities($v,ENT_QUOTES,"ISO-8859-1",false);
            });
            dbg::seedetails($outs,"Data to send");
            $outf8=gnrl::toUTF8($out);
            dbg::seedetails($outf8,"Data to send in UTF-8");
            $s=json_encode($outf8);
            if( $s ) echo "JSON Generated:{$s}";
            else echo "Errors in JSON generation:".json_last_error_msg();
        }
        else {
            $outf8=gnrl::toUTF8($out);
            $s=json_encode($outf8);
            header("Content-type:application/json");
            echo $s;
        }
        exit();
    }
}
?>

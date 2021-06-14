<?php
class html5_Builder_base {
	private function aim() {
	# HTML5 offers new semantic elements to define different parts
	# of a webpage
	# ... <article> specifies independent, self-contained content.
	# <details> specifies additional details that the user can view or hide
	#			on demand.
	# <summary> defines a visible heading for the <details> element.
	#			The heading can be clicked to view/hide the details.
	# <header> represents a container for introductory content or a set
	# 			of navigational links.
	#			(Especially meaningful in JQuery Mobile)
	# <nav>		defines a set of navigation links.
	# <main>	specifies the main content of a document.
	# <footer>	defines a footer for a document or section.
	# <aside>	The <aside> tag defines some content aside from the content
	# 			it is placed in. The aside content should be related
	# 			to the surrounding content.
	# <figure>	The <figure> tag specifies self-contained content,
	#			like illustrations, diagrams, photos, code listings, etc.
	# <figcaption> defines a caption for a <figure> element.
	# 				The <figcaption> element can be placed as the first
	#				or last child of the <figure> element.
	# <mark>	defines marked text.Use the <mark> tag if you want
	#				to highlight parts of your text.
	# <section> defines sections in a document, such as chapters, headers,
	# 			footers, or any other sections of the document.
	# <time> 	The <time> tag defines a human-readable date/time.
	# 			This element can also be used to encode dates and times
	#			in a machine-readable way so that user agents can offer
	#			to add birthday reminders or scheduled events
	#			to the user's calendar, and search engines can produce
	#			smarter search results.
	# 			The datetime attribute represent the machine-readable
	#			date/time of the <time> element.
	# The basic idea is build as buffers as you need to build
	# elementts separately and in the end put all of them together
	# in a single array of html part, css part and js part.
	}
	protected $prp;	// The properties to define the process
	public function __construct($par=[]) {
		$this->prp=$par;
	}
	#=======================================================================
	# To manage the buffers
	# $this->buffers is un array multi level to store several
	# constructions in order to at the end put all together through
	# packing them
	private $buffers;
	// The buffers must only be accessed with the functions below
	public function push($nameraw,$content) {
		$n=explode(".",$nameraw);
		$name=$n[0];
		if( isset($n[1]) ) $key=$n[1]; else $key='';
		if( ! isset($this->buffers[$name]) ) $this->buffers[$name]=[];
		if( ! is_array($content) ) $content=[$content];
		if( $key == '' ) {
			// foreach($content as $c ) $this->buffers[$name][]=$c;
			$this->buffers[$name]=array_merge($this->buffers[$name],
				$content);
		}
		else {
			$x=[]; foreach($content as $c ) $x[]=$c;
			$this->buffers[$name][$key]=implode("\n",$x);
		}
	}
	public function merge($name,$content) {
		if( ! isset($this->buffers[$name]) )
			$this->buffers[$name]=$content;
		else
			$this->buffers[$name]=
				array_merge($this->buffers[$name],$content);
	}
	public function get($name) {
		$rc=[];
		if(isset($this->buffers[$name])) $rc=$this->buffers[$name];
		return $rc;
	}
	public function getList() {
		$rc=[];
		foreach( func_get_args() as $arg) $rc[]= $this->get($arg);
		return $rc;
	}
	public function getArray() {
		$rc=[];
		foreach( func_get_args() as $arg) $rc[$arg]= $this->get($arg);
		return $rc;
	}
	protected function compactBuffers($model,$level=0){
		// Compact un array of buffers into another array
		if(0) dbg::seeDetails($this->buffers,"Buffers:");
		if(0) dbg::seeDetails($model,"Model:");
		$s=[];
		foreach($model as $key=>$x) {
			if( ! is_array($x) ) $x=[$x];
			$z=[]; foreach($x as $xx) $z[]=$this->get($xx);
			if(0) dbg::seeDetails($z,"Array augmented:");
			$zs=$this->compactToString($z);
			$s[$key]=$zs;
		}
		if(0) dbg::seeDetails($s,"Out");
		return $s;
	}
	protected function compactToString($x) {
		// $x is anything (either array or not) to be transformed to string
		$xout='';
		if( is_array($x) )
			foreach($x as $xx) $xout.= $this->compactToString($xx);
		else $xout=$x;
		return $xout;
	}
	protected function executeFunction($function,$args=[]) {
		if(! $function ) return $args;
		$caller= isset($this->prp['caller']) ? $this->prp['caller'] : null ;
		if( $caller && method_exists($caller ,$function) )
			$rc=$caller->$function($args);
		else if( method_exists($this,$function) )
			$rc=$this->$function($args);
		else if( function_exists($function))
			$rc=$function($args);
		else $rc="!{$function}";
		return $rc;
	}
	//======================================================================
	// The head fundamentals functions
	protected function prpHead() {
		$prp=[
			'charset'=>'utf8','title'=>'No title',
			'fave'=>"sizes='64x64' href='images/faveicon-16x16.png'",
		];
		return $prp;
	}
	protected function title($prp=[]) {
		$d=gnrl::MergeProperties($this->prpHead(),$prp);
		extract($d);
		$s="<title>{$title}</title>";
		$this->push('basic',$s);
	}
	protected function charset() {
		extract($this->prpHead());
		$s="<meta charset='{$charset}'>";
		$this->push('basic',$s);
		return $s;
	}
	protected function faveicon() {
		extract($this->prpHead());
		$s="<link rel='icon' type='image/png' {$fave} >";
		$this->push('basic',$s);
	}
	protected function viewport() {
		$s="<meta name='viewport' content='width=device-width, ";
		$s.="initial-scale=1.0'>";
		$this->push('basic',$s);
	}
	protected function jQuery() {
		$this->push('linkscripts',rsrcs::jsQuery());
	}
	protected function jQueryMobile() {
		$this->push('linkstyles',rsrcs::cssQueryMobile());
		$this->push('linkscripts',rsrcs::jsQueryMobile());
	}
	protected function jQUI () {
		$this->push('linkstyles',rsrcs::cssQueryUI());
		$this->push('scripts',rsrcs::jsQueryUI());
	}
	protected function icons () {
		$this->push('linkstyles',rsrcs::cssIcons());
	}
	protected function fileStyles() {
		$cssf=$this->get('cssfiles');
		$s=rsrcs::cssFromArray($cssf,[]);
		$this->push('linkstyles',$s);
		return "{$s}";
	}
	protected function fileScripts() {
		$jsf=$this->get('jsfiles');
		$s=rsrcs::jsFromArray($jsf,[],$version=2);
		$this->push('linkscripts',$s);
		return "<script>{$s}</script>";
	}
	//======================================================================
	// To produce the main part
	protected function prpMain() {
		$prp=[ 'classes'=>['std'=>'main'],'attributes'=>[],
			'data_att'=>['data-role'=>'main']];
		return $prp;
	}
	protected function main($parts) {
		$c=$this->tagS($this->prpMain());
		$s=$this->compactToString($parts);
		$rcs="<main {$c} >{$s}</main>\n";
		$this->push("main",$rcs);
		return $rcs;
	}
	protected function mainStyles() {
		$rc="
			.main {
				box-sizing: border-box;
	    			width: 100%;
	   		 	margin:0 auto;
			}
		";
		$this->push('styles.main',"<style>{$rc}</style>");
		return "<style>{$rc}</style>";
	}
	//======================================================================
	// To produce the header for JQuery Mobile / UI
	protected function prpHeader() {
		$prp=['classes'=>['std'=>'header'],'attributes'=>[],
			'data_att'=>['data-role'=>'header']];
		return $prp;
	}
	protected function header($parts) {
		$c=$this->tagS($this->prpHeader());
		$s=$this->compactToString($parts);
		$rcs="<header {$c} >{$s}</header>";
		$this->push("header",$rcs);
		return $rcs;
	}
	protected function headerStyles() {
		$rc="
			.header {
				box-sizing: border-box;
	    			width: 100%;
	   		 	margin: 0 auto;
			}
		";
		$this->push('styles.header',"<style>{$rc}</style>");
		return "<style>{$rc}</style>";
	}
	//======================================================================
	// To produce the leftpanel for JQuery Mobile / UI
	protected function prpLeftPanel() {
		$prp=['classes'=>[],'attributes'=>[],
			'data_att'=>['data-role'=>'panel']];
		return $prp;
	}
	protected function leftPanel($parts) {
		$c=$this->tagS($this->prpLeftPanel());
		$s=$this->compactToString($parts);
		$id='lp'.get_class($this);
		//------------------------------------------------------------------
		$rcs="<div id=\"{$id}\" {$c} >{$s}</div>";
		$this->push("leftPanel",$rcs);
		return $rcs;
	}
	//======================================================================
	// To produce the rightpanel for JQuery Mobile / UI
	protected function prpRightPanel() {
		$prp=['classes'=>[],'attributes'=>[],
			'data_att'=>['data-role'=>'panel']];
		return $prp;
	}
	protected function rightPanel($parts) {
		$c=$this->tagS($this->prpRightPanel());
		$s=$this->compactToString($parts);
		$id='rp'.get_class($this);
		//------------------------------------------------------------------
		$rcs="<div id=\"{$id}\" {$c} >{$s}</div>";
		$this->push("rightPanel",$rcs);
		return $rcs;
	}
	//======================================================================
	// To produce the footer for JQuery Mobile / UI
	protected function prpFooter() {
		$prp=['classes'=>['std'=>'footer'],'attributes'=>[],
		'data_att'=>['data-role'=>'panel']];
		return $prp;
	}
	protected function footer($parts) {
		$c=$this->tagS($this->prpFooter());
		$s=$this->compactToString($parts);
		$rcs="<footer {$c} >{$s}</footer>";
		$this->push("footer",$rcs);
		return $rcs;
	}
	protected function footerStyles() {
		$rc="
			.footer {
				box-sizing: border-box;
	    			width: 100%;
	   		 	margin: 0 auto;
			}
		";
		$this->push('styles.footer',"<style>{$rc}</style>");
		return "<style>{$rc}</style>";
	}
	//======================================================================
	// To produce the aside
	protected function prpAside() {
		$prp=[
			'classes'=>['std'=>'aside'],'attributes'=>[],'data_att'=>[],
		];
		return $prp;
	}
	protected function asideFixed($parts) {
		$c=$this->tagS($this->prpAside());
		$s=$this->compactToString($parts);
		$rcs="<aside {$c}>{$s}</aside>";
		$this->push('aside',$rcs);
		return $rcs;
	}
	protected function asideHide($parts) {
		$c=$this->tagS($this->prpAside());
		$s=$this->compactToString($parts);
		$rc=[];
		$rc[]=$this->hiddenAside();
		$rc[]="<aside {$c} >".$this->shownAside().$s."</aside>";
		$rcs = implode("\n",$rc);
		$this->push('aside',$rcs);
		return $rcs;
	}
	protected function hiddenAside() {
		$s=translator::t("format.showpanel");
		$ss=''; for($i=0; $i<strlen($s);$i++) $ss.='<br>'.substr($s,$i,1);
		$rc=[];
		$rc[]="	<div class=asideshort >";
		$rc[]="		<a href=show>{$ss}</a>";
		$rc[]="	</div>";
		return implode("\n",$rc);
	}
	protected function shownAside() {
		$s=translator::t('format.hidepanel');
		$rc=[];
		$rc[]="<div style='text-align:left;' class=asidelong >";
		$rc[]="<a href=hidden>{$s}</a>";
		$rc[]="</div>";
		return implode("\n",$rc);
	}
	//======================================================================
	// To produce the article
	protected function prpArticle() {
		$prp=['classes'=>['std'=>'article'],'attributes'=>[],
			'data_att'=>[]];
		return $prp;
	}
	protected function article($parts) {
		$c=$this->tagS($this->prpArticle());
		$s=$this->compactToString($parts);
		$rcs="<article {$c} >{$s}</article> ";
		$this->push('article',$rcs);
		return $rcs;
	}
	#=======================================================================
	# It makes the structure of main body that binds together
	# the aside part and the article part
	protected function prpArticleAside() {
		$prp=['classes'=>['std'=>'asidearticlewrapper'],'attributes'=>[],
			'data_att'=>[], 'flexaside'=>250, 'flexarticle'=>750, ];
		return $prp;
	}
	protected function asideArticleBound() {
		$c=$this->tagS($this->prpArticleAside());
		$aside=$this->get("aside");
		$article=$this->get("article");
		$s=$this->compactToString([$aside,$article]);
		$rcs="<div  {$c} >{$s}</div>";
		$this->push('asidearticle',$rcs);
		return $rcs;
	}
	public function asideArticleStyles() {
		extract($this->prpArticleAside());
		$rc="
			.asidearticlewrapper {
				box-sizing: border-box;
				display:flex;
				width:95%;
				margin:0 auto;
				min-height:30em;
			}
			.article {
				box-sizing: border-box;
	    			flex:{$flexarticle};
	   		 	margin: 0 auto;
	   		 	overflow:scroll;
	   		 	padding:1em;
			}
			.aside {
				box-sizing: border-box;
	    			flex:{$flexaside};
	   		 	margin: 0 auto;
	   		 	overflow:scroll;
			}
			.asideshort {
				box-sizing: border-box;
				width:2em;
	    			display:none;
	   		 	margin: 0 auto;
			}
		";
		$s="<style>{$rc}</style>";
		$this->push('styles.aside',$s);
		return $s;
	}
	//======================================================================
	// To produce the details
	protected function prpDetails() {
		$prp=[
			'classes'=>['std'=>'details'],'attributes'=>[],'data_att'=>[],
		];
		return $prp;
	}
	protected function prpSummary() {
		$prp=[
			'classes'=>['std'=>'summary'],'attributes'=>[],'data_att'=>[],
		];
		return $prp;
	}
	protected function summary($parts,$w='.1') {
		$c=$this->tagS($this->prpSummary());
		$s=$this->compactToString($parts);
		$rcs="<summary {$c} >{$s}</summary>";
		$this->push('summary'.$w,$rcs);
		return $rcs;
	}
	protected function detailsWithoutSummary($parts,$w='.1') {
		$c=$this->tagS($this->prpDetails());
		$s=$this->compactToString($parts);
		$rcs="<details  {$c} >{$s}</details>";
		$this->push('details'.$w,$rcs);
		return $rcs;
	}
	protected function detailsWithSummary($parts,$w='1.') {
		$sum=$this->get('summary'.$w);
		$c=$this->tagS($this->prpDetails());
		$s=$this->compactToString($parts);
		$rcs="<details  {$c} >{$sum}{$s}</details>";
		$this->push('details'.$w,$rcs);
		return $rcs;
	}
	public function detailsStyles() {
		$rc="
			.details {
				box-sizing: border-box;
	    			width: 100%;
	   		 	margin: 0 auto;
			}
			.summary {
				box-sizing: border-box;
			}
		";
		$this->push('styles.details',"<style>{$rc}</style>");
		return "<style>{$rc}</style>";
	}
	//----------------------------------------------------------------------
	// Funtion supports
	public static function loremIpsum($length='') {
		$LI="
		Lorem ipsum dolor sit amet, consectetur adipiscing elit,
		sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
		 Ut enim ad minim veniam,
		 quis nostrud exercitation ullamco
		 laboris nisi ut aliquip ex ea commodo consequat.
		 Duis aute irure dolor in reprehenderit in voluptate velit
		 esse cillum dolore eu fugiat nulla pariatur.
		 Excepteur sint occaecat cupidatat non prcroident,
		 sunt in culpa qui officia deserunt mollit anim id est laborum.
		";
		if( $length ) {
			$li=strlen($LI);
			$rc=''; while( strlen($rc) < $length ) $rc.=$LI."<p></p>";
			$rc=substr($rc,0,$length);
		}
		else $rc=$LI;
		return "<p>".$rc."</p>";
	}
	protected function tagS($prp) {
		extract($prp);
		$c=$this->classes($classes);
		$att=$this->attcss($attributes);
		$dat=$this->attnocss($data_att);
		return "{$c} {$dat} {$att}";
	}
	protected function classes($a) {
		$s=trim(implode(" ",$a));
		if( $s ) $rc="class='{$s}'"; else $rc='';
		return $rc;
	}
	protected function attnocss($a) {
		if (count($a) == 0 ) return '';
		$rc=[];
		foreach($a as $key=>$v) $rc[]="{$key}='{$v}' ";
		$s=trim(implode(' ',$rc));
		return $s;
	}
	protected function attcss($a) {
		if (count($a) == 0 ) return '';
		$rc=[];
		foreach($a as $key=>$v) $rc[]="{$key}:{$v};";
		$s=implode(' ',$rc);
		return "style='{$s}'";
	}
	protected function basicStyles() {
		$st=appData::basicStyles();
		$this->push('styles.basic',"<style>".$st."</style>");
	}
	protected function commonStyles() {
		$rc="
			.left { text-align:left; }
			.right { text-align:right; }
			.center { text-align:center; }
			.red {color:brown;}
	        .yellow {color:gold;}
	        .green {color:darkgreen}
	        .redb {background-color:brown;}
	        .yellowb {background-color:lemonchiffon;}
	        .greenb {background-color:darkgreen}
			.brdred { border:1px solid brown;}
			.brdblu { border:1px solid blue;}
			.brdgre { border:1px solid darkgreen;}
			.hdr { margin:0 auto 1em 0; padding:1em; text-align:center;}
			.blueback { background-color:steelblue; color:white;
				text-align:center;}
			.ownmain {
				box-sizing: border-box;
				padding:0 1em;
			}
		";
		$this->push('styles.common',"<style>{$rc}</style>");
		return "<style>{$rc}</style>";
	}
}
class html5_Builder_JQMobile extends html5_Builder_base {
	protected function prpLeftPanel() {
		$prp=['classes'=>[],'attributes'=>[],
			'data_att'=>['data-role'=>'panel']
		];
		return $prp;
	}
	protected function prpRightPanel() {
		$prp=['classes'=>[],'attributes'=>[],
			'data_att'=>['data-role'=>'panel', 'data-position'=>'right',
			'data-display'=>'push',
			]
		];
		return $prp;
	}
	protected function prpHeader() {
		$prp=['classes'=>['std'=>'mobileheader'],
			'attributes'=>[],
			'data_att'=>['data-role'=>'header',
				'data-position'=>'fixed', 'data-fullscreen'=>'true',
				'data-tap-toggle'=>'false',
			]
		];
		return $prp;
	}
	protected function prpFooter() {
		$prp=[
			'classes'=>['std'=>'mobilefooter'],'attributes'=>[],
			'data_att'=>['data-role'=>'footer',
				'data-position'=>'fixed', 'data-fullscreen'=>'true',
				'data-tap-toggle'=>'false',
			]
		];
		return $prp;
	}
	protected function prpMain() {
		$prp=[
			'classes'=>['ui-content','std'=>'main'],
			'attributes'=>[],
			'data_att'=>['data-role'=>'main'],
		];
		return $prp;
	}
}
?>

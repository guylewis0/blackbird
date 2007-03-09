<?php

class BlackBird
{
	
	private $_data;
	public $pathA;
	public $session;
	public $db;	
	public $js_includes;

	function __construct()
	{
		
		//load core classes
		require_once(LIB.'database/Db.class.php');
		require_once(LIB.'forms/Forms.class.php');
		require_once(LIB.'utils/Utils.class.php');
		require_once(LIB.'session/Session.class.php');
		require_once(LIB.'_version.php');	
		
		require_once(INCLUDES.'SessionManager.class.php');
					
		$this->db = new Db;
		$this->session = new SessionManager;
				
	}
	
	public function __set($name,$value)
	{
		$this->_data[$name] = $value;
	}
	
	public function __get($name)
	{
		if (isset($this->_data[$name])){
			return $this->_data[$name];
		}else{
			return false;
		}
	}
	
	
	public function buildPage(){
			
		$this->pathA = explode("/",$_SERVER["REQUEST_URI"]);
		$tA = explode("/",substr($_SERVER['PHP_SELF'],1,-(strlen('index.php') + 1)));
		array_splice($this->pathA,0,count($tA)+1);
		$this->path = $this->pathA;
				
		if(isset($this->pathA[1])){
			$this->table = $this->pathA[1];
		}else{
			$this->pathA[1] = '';
			$this->table = '';
		}
		
		if($this->table != ""){
		
			$q_label = $this->db->queryRow("SELECT display_name FROM cms_tables WHERE table_name = '$this->table'");
			if($q_label['display_name'] == ''){
				$this->label = Utils::formatHumanReadable($this->table);
			}else{
				$this->label = $q_label['display_name'];
			}
		
		}
		
		if(!isset($this->pathA[0])){
			Utils::metaRefresh(CMS_ROOT . "home");
			die();
		}
		
		switch($this->pathA[0]){
		
			case "ajax":
				$this->session->check();
				require_once(INCLUDES.'Ajax.class.php');
				new Ajax($this);			
			break;
			
			case "edit":
				$this->session->check();
				$this->id = $this->pathA[2];
				require_once(INCLUDES.'EditPage.class.php');
				new EditPage($this);
			break;
			
			case "add":
				$this->session->check();
				require_once(INCLUDES.'EditPage.class.php');
				new EditPage($this);
			break;
			
			case "browse":
				$this->session->check();
				require_once(INCLUDES.'DataGrid.class.php');
				new DataGrid($this);			
			break;
			
			case "process":
				$this->session->check();
				switch(true){
				
					case($this->pathA[1] == "batch"):
						$this->table = $this->pathA[2];
						require_once(INCLUDES.'Batch.class.php');
						new Batch($this);
					break;
					
					case($this->pathA[1] == "remote"):
						require_once(INCLUDES.'Remote.class.php');
						new Remote($this);
					break;
					
					default:
						require_once(INCLUDES.'ProcessPage.class.php');
						new ProcessPage($this);
					break;
					
				}
			break;			
			
			case "home":
				$this->session->check();
				require_once(INCLUDES.'Home.class.php');
				new Home($this);				
			break;
			
			case "logout":
				require_once(INCLUDES.'Logout.class.php');
				new Logout($this);
			break;
			
			case "login":
				require_once(INCLUDES.'Login.class.php');
				new Login($this);
			break;
				
			default:
				//this catches exceptions when using httpd.conf alias
				//not used when using .htaccess
				$this->session->check();
				Utils::metaRefresh(CMS_ROOT . "home");
			break;
		}
				
	}
	
	public function processDelete($table,$id_set)
	{	
	
		switch($table){
	
			default:
				
				foreach($id_set as $id){
					Db::sql("DELETE FROM `$table` WHERE id = $id");
							
					$row_data = array();
					$row_data[] = array('field'=>'table_name','value'=>$table);
					$row_data[] = array('field'=>'record_id','value'=>$id);
					$row_data[] = array('field'=>'action','value'=>'delete');
					$row_data[] = array('field'=>'user_id','value'=>$this->session->u_id);
					$row_data[] = array('field'=>'session_id','value'=>session_id());
					Db::insert('cms_history',$row_data);
				}				
								
			break;
		
		}
	
	}
	
	public function displayDeleteWarning($table,$id_set)
	{
		switch($table){
	
			default:
			
			break;
			
		}
	
	}
	
	public function injectData($a)
	{
		return $a;
	}
	
	public function formatCol($col_name,$col_value,$table)
	{
	
		$boolSet = array("active","admin");
		
		if(in_array($col_name,$boolSet)){
			if($col_value == 0){ return "false";}
			if($col_value == 1){ return "true";}
		}
		
		if($col_name == 'groups'){
			
			//split list
			$tA = explode(',',$col_value);
			$r = array();
			foreach($tA as $item){
				$q = Db::queryRow("SELECT name FROM cms_groups WHERE id = '$item'");
				$r[] = $q['name'];
			}
			
			return join(', ',$r);
			
			
		}	
		
		if($col_name == 'user_id' && $table == 'cms_history'){
		
			$q = Db::queryRow("SELECT email FROM cms_users WHERE id = '$col_value'");
			return $q['email'];
		
		}
		
		if(strlen($col_value) > 100){
			$data = substr($col_value,0,100) . "...";
			return strip_tags($data);
		}
		
		return $col_value;
	
	
	}	
	
	public function pluginColumnEdit($name,$value,$options)
	{
		
		if($options['col_name'] == 'password' && $options['table'] == 'cms_users'){
			$options['type'] = 'password';
			Forms::text($name,'',$options);		
		}
		
		if($options['col_name'] == 'user_id' && $options['table'] == 'cms_history'){
			$q = Db::queryRow("SELECT email FROM cms_users WHERE id = '$value'");
			Forms::readonly($name,$q['email'],$options);		
		}
		
		if($options['col_name'] == 'groups' && $options['table'] == 'cms_users'){
			
			$q = Db::query("SELECT id,name FROM cms_groups ORDER BY name");
			$r = '<ul>';
			$tA = explode(',',$value);
			
			foreach($q as $group){
				(in_array($group['id'] ,$tA) ) ? $v = 'Y' : $v = '';
				$r .= '<li>' . Forms::checkboxBasic('group_' . $group['id'],$v,array('class'=>'checkbox noparse','label'=>$group['name'])) . '</li>';
			}
			
			$r .= '</ul>';
			$options['label'] = "Groups";
			Forms::buildElement($name,$r,$options);
			Forms::hidden($name,'',array('omit_id'=>true));
		
		}
		
		if($options['col_name'] == 'tables' && $options['table'] == 'cms_groups'){
			
			$q = Db::query("SHOW TABLE STATUS");
			$tA = explode(',',$value);
			$privA = array('browse','insert','update','delete');
			
			
			$xml = simplexml_load_string($value);
			$tableA = array();
			if($xml){
				foreach($xml->table as $mytable){
					$t = sprintf($mytable['name']);
					$tableA[$t] = sprintf($mytable);
				}
			}
			
			$r = '<table>
			<tr><th>Table</th>';
			
				foreach($privA as $priv){
					
					$r .= '<th>' . $priv . '</th>';
					
				}
			$r .= '</tr>';
						
			foreach($q as $table){
			
			
				if($table['Comment'] != 'private'){
				
					$r .= '<tr>';
					$r .= '<td>' .  Utils::formatHumanReadable($table['Name']) . '</td>';
					
					$tP = array();
					if(isset($tableA[$table['Name']])){
						$tP = explode(',',$tableA[$table['Name']]);
					}
					
					foreach($privA as $priv){
						
						(in_array($priv ,$tP) ) ? $v = 'Y' : $v = '';
						$r .= '<td>' . Forms::checkboxBasic('table_' . $table['Name'] . '_' . $priv,$v, array('class'=>'checkbox noparse','label'=>'')) . '</td>';
					
					}
					
					
					$r .= '</tr>';
				}
			
			}
			
			$r .= '</table>';
			
			$options['label'] = "Tables";
			Forms::buildElement($name,$r,$options);
			Forms::hidden($name,'',array('omit_id'=>true));
						
		}
		
	}
	
	//need to add namespaces in these functions - ie use name instead of temp strings
	
	public function pluginColumnProcess($name,$value,$options)
	{
	
		if($options['col_name'] == 'tables' && $options['table'] == 'cms_groups'){
			
			$q = Db::query("SHOW TABLE STATUS");
			$r = '<data>';
			
			$privA = array('browse','insert','update','delete');
			foreach($q as $table){
			
				if($table['Comment'] != 'private'){
					//
					$p = array();
					foreach($privA as $priv){
						if(isset($_REQUEST['table_' . $table['Name']. '_' . $priv])){
							if($_REQUEST['table_' . $table['Name']. '_' . $priv] == 'Y'){
								$p[] = $priv;
							}
						}
					}
					
					if(count($p)>0){
						$p = join(',',$p);
						$r .= '<table name="' . $table['Name'] . '">' . $p . '</table>';
					}
					
				}
			}
			
			$r .= '</data>';
			
			return array('field'=>'tables','value'=>$r);
		
		}
		
		if($options['col_name'] == 'groups' && $options['table'] == 'cms_users'){
			
			$q = Db::query("SELECT * FROM cms_groups");
			foreach($q as $group){
				if(isset($_REQUEST['group_' . $group['id']])){
					if($_REQUEST['group_' . $group['id']] == 'Y'){
						$r[] = $group['id'];
					}
				}
			}
		
			
			//trim last character;
			$r = join(',',$r);
			return array('field'=>'groups','value'=>$r);			
		
		}
		
		if($options['col_name'] == 'password' && $options['table'] == 'cms_users'){
			
			if(strlen($value) > 1){
				return array('field'=>'password','value'=>sha1($value));			
			}else{
				return false;
			}
		}
	
	}
	
	public function pluginTableProcess($table,$id,$mode)
	{
		
	}
		
	public function buildHeader($js="",$css="",$body_class="",$help="")
	{

/*
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">   
    
*/

print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<!-- 
Copyright 2004-2007 
Authors Charles Mastin & Joshua Rudd
c @ charlesmastin.com
email @ joshuarudd.com


Portions of this software rely upon the following software which are covered by their respective license agreements
* Prototype.js
* Scriptaculous Library
* Lightbox
* Magpie rss reader
-->
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="cache-control" content="no-cache" />
	<title>' . CMS_CLIENT . ' CMS</title>
	<!-- Main CSS -->
	<link rel="stylesheet" type="text/css" media="screen" href="' . CMS_ROOT . 'assets/css/style.css" />
	<!-- Core Javascript -->
	<script type="text/Javascript" src="' . CMS_ROOT . 'assets/js/prototype.js" ></script>
	<script type="text/Javascript" src="' . CMS_ROOT . 'assets/js/scriptaculous/scriptaculous.js" ></script>
	<script type="text/Javascript" src="' . CMS_ROOT . 'assets/js/functions.js" ></script>
	<script type="text/Javascript" src="' . CMS_ROOT . 'assets/js/eventbroadcaster.js" ></script>
	<script type="text/Javascript" src="' . CMS_ROOT . 'assets/js/cms.js" ></script>
	<script type="text/Javascript" src="' . CMS_ROOT . 'assets/js/validator.js" ></script>
	<script type="text/Javascript">
		<!-- <![CDATA[
		CMS.setProperty("cms_root","' . CMS_ROOT . '");
		// ]]> -->
	</script>
	<!-- IE conditionals -->
	<!--[if lt IE 7]>
		<script src="' . CMS_ROOT . 'assets/js/ie6.js" type="text/javascript" language="javascript" charset="utf-8"></script>
		<link rel="stylesheet" href="' . CMS_ROOT . 'assets/css/ie6.css" type="text/css" media="screen" charset="utf-8" />
	<![endif]-->
	<!--[if IE 7]>
		<link rel="stylesheet" href="' . CMS_ROOT . 'assets/css/ie7.css" type="text/css" media="screen" charset="utf-8" />
	<![endif]-->';
	if(file_exists(PLUGINS.'css/custom.css')){
		print '
		<!-- Custom CSS -->
		<link rel="stylesheet" type="text/css" media="screen" href="' . CMS_ROOT . PLUGINS . 'css/custom.css" />' . "\r";
	}
		
	if($js != ''){
		print '<!-- Custom Javascript -->' . "\r";
		print $js;
	}
	if($css != ''){
		print '<!-- Custom CSS -->';
		print $css;
	}
	
	// Check for debug mode
	if(isset($_GET['debug'])) $body_class = ' class="debug"';
	
	print '</head>
<body id="body"'.$body_class.'>
	<div id="page">
		<div id="header">
			<div id="masthead" onclick="window.location = \'' . CMS_ROOT . 'home\'" >
				<h1>'.CMS_CLIENT.' CMS</h1>';
	
	if($this->session->logged===true){
		
		print '
		</div>
		<div id="navigation">
		<p id="logged_info">Welcome, ' . $this->session->displayname . ' - <a href="' . CMS_ROOT . 'logout">Logout</a></p>';		
				
		$tables = $this->session->getTables('navigation');				
				
		if($tables){
			
			$siteA = array();
			$adminA = array();
			
			foreach($tables as $key => $row){
				
				$q_label = Db::queryRow("SELECT display_name FROM cms_tables WHERE table_name = '$key'");
				if($q_label['display_name'] == ''){
					$label = Utils::formatHumanReadable($key);
				}else{
					$label = $q_label['display_name'];
				}
				
				if(substr($key,0,4) == 'cms_'){
					$adminA[] = array('href'=>CMS_ROOT . 'browse/' . $key,'label'=>$label);
				}else{
					$siteA[] = array('href'=>CMS_ROOT . 'browse/' . $key,'label'=>$label);
				}
				
			}
			
			
			print '<ul id="nav"><li>
				<a href="#">Site Content</a>
				<ul>';
				
				foreach($siteA as $item){
					print '<li><a href="' . $item['href'] . '">' . $item['label'] . '</a></li>'; 
				}
			
			
			print '</ul>
			</li>';
			
			if(count($adminA) > 0){
			
				print '<li><a href="#">Admin</a><ul>';
				
				foreach($adminA as $item){
					print '<li><a href="' . $item['href'] . '">' . $item['label'] . '</a></li>'; 
				}
				
				print '</ul></li>';
			
			}
			
			
			print '			
		</ul>';
		
		}
		
		
		print '</div>';
	}else{
		print '</div>';		
	}
	
	print "<h1 id=\"page_label\">$this->label</h1>";
	if(strlen($help) > 1){
		print '<a id="toggle_help" class="icon help" href="#help" onclick="return false" title="Show/hide help">Help</a>';
	}
	if(strlen($help) > 1){
		print '
		<div id="help" style="display:none;">
			<div id="help_content">
			' . $help . '
			</div>
		</div>';
	}
	print '<div class="clearfix"></div></div>';
	
	
		
	
		
	}
	

	function buildHome($home)
	{
		
		print '
		<div id="content" class="home">
		<div class="column mr">';
			
		$home->modTables();
		$home->modSessions();
		$home->modEdits();
			
		print '
		</div>
		<div class="column">';
		
		$home->modRss();
		$home->modDocs();
		
		print '
		</div>		
		<div style="clear:both;"></div>';
	
	}
	
	public function buildFooter()
	{
		print'
		</div>
		<div id="footer">BlackBird &copy; 2004-' . date('Y') . ' CM &amp; JR - Version ' . CMS_VERSION . '</div>
		</div>
		</body>
		</html>';

	}
	

	
}


function __autoload($class){
	require_once(INCLUDES.''. $class.".class.php");
}

?>
<?php

/* $Id: config_core.php 53 2008-01-03 19:11:02Z joshuarudd $ */

error_reporting(E_STRICT | E_ALL);
date_default_timezone_set('America/Los_Angeles');
$tempObj = Array();

function setConfig($name,$value)
{
	global $tempObj;
	$tempObj[$name] = $value;
}

function createConstants()
{
	global $tempObj;
	foreach($tempObj as $key=>$value){
		if(!is_array($value)){
			define($key,$value);			
		}
	}
	unset($tempObj);
}


setConfig("XML_HEADER",'<?xml version="1.0" encoding="UTF-8"?>');
setConfig("SERVER",'http://'.$_SERVER['SERVER_NAME']);

setConfig("CMS_TRIM","right");
setConfig("CMS_DEFAULT_HOUR",19);
setConfig("CMS_DEFAULT_MIN",30);
setConfig("CMS_MAX_YEAR",(date('Y')+6));
setConfig("CMS_MIN_YEAR",1990);
setConfig("CMS_DATA_GRID_SORT_MAX",20);
setConfig('CMS_USERS_TABLE','cms_users');

setConfig("CMS_FILESYSTEM",substr($_SERVER['SCRIPT_FILENAME'],0,-strlen('index.php')));
setConfig("CMS_ROOT",substr($_SERVER['PHP_SELF'],0,-strlen('index.php')));
setConfig("CMS_VERSION","1.0.10 rev ".substr('$Revision$',11,-2));

setConfig("INCLUDES","core/php/");
setConfig("LIB","bobolink/");
setConfig("WEB_ROOT","../");
setConfig("CUSTOM","custom/");
setConfig("ASSETS","core/");

if(file_exists('custom/config_custom.php')){
	require_once('custom/config_custom.php');
	createConstants();
	if(isset($GLOBALS['DATABASE'])){
		if(file_exists(CUSTOM.'php/Custom.class.php')){
			require(INCLUDES.'BlackBird.class.php');
			require(CUSTOM.'php/Custom.class.php');
		}else{
			die('No Custom.class.php...');
		}
	}else{
		die('No database config...');
	}
}else{
	die('No config_custom.php found...');
}

?>
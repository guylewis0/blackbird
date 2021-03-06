<?php

function plugin__record_column_process($name,$value,$options)
{

	if($options['col_name'] == 'tables' && $options['table'] == BLACKBIRD_TABLE_PREFIX . 'groups'){
		
		$q = $options['db']->query("SHOW TABLE STATUS");
		$privA = array('select','insert','update','delete');
		
		$tableA = array();
		//loop her and throw out system tables
		$tlen = strlen(BLACKBIRD_TABLE_PREFIX);
		foreach($q as $table){
			//if pattern fails add to list
			if(substr($table['Name'],0,$tlen) != BLACKBIRD_TABLE_PREFIX){
				$tableA[] = $table['Name'];
			}
		}
		//get proper id
		$group_id = $options['id'];
		//query all existing permissions for this group
		$q_permissions = $options['db']->query("SELECT * FROM " . BLACKBIRD_TABLE_PREFIX . "permissions WHERE group_id = '$group_id' ORDER BY table_name");
		
		//handle bulk update query? or not
		foreach($tableA as $table){
			//use checkArray on each table_name
			$row_data = array();
			$row_data[] = array('field'=>'table_name','value'=>$table);
			$has_privs = false;
			foreach($privA as $priv){
				if(isset($_REQUEST['table_' . $table . '_' . $priv])){
					if($_REQUEST['table_' . $table . '_' . $priv] == 'Y'){
						$row_data[] = array('field'=>$priv.'_priv','value'=>1);
						$has_privs = true;
					}else{
						$row_data[] = array('field'=>$priv.'_priv','value'=>0);
					}
				}else{
					$row_data[] = array('field'=>$priv.'_priv','value'=>0);
				}
			}		
			
			$tA = Utils::checkArray($q_permissions,array('table_name'=>$table));
			if(is_array($tA)){
				//do updates
				//if privs exist
				if($has_privs){
					$options['db']->update(BLACKBIRD_TABLE_PREFIX . 'permissions',$row_data,'id',$tA['id']);
				}else{
					$options['db']->sql("DELETE FROM ".BLACKBIRD_TABLE_PREFIX."permissions WHERE id = '$tA[id]' LIMIT 1");
				}
				//if nothing exist, delete instead
			}else{
				//do inserts
				$row_data[] = array('field'=>'group_id','value'=>$group_id);
				if($has_privs){
					$options['db']->insert(BLACKBIRD_TABLE_PREFIX . 'permissions',$row_data);
				}
			}
			
		}
			
		//don't return anything
			
	}
	
	if($options['col_name'] == 'groups' && $options['table'] == BLACKBIRD_USERS_TABLE){
		
		$q_groups = $options['db']->query("SELECT * FROM " . BLACKBIRD_TABLE_PREFIX . "groups");
		$q_links = $options['db']->query("SELECT * FROM ".BLACKBIRD_TABLE_PREFIX."users__groups WHERE user_id = '$options[id]'");
		
		foreach($q_groups as $group){
			$delete = true;
			if(isset($_REQUEST['group_' . $group['id']])){
				if($_REQUEST['group_' . $group['id']] == 'Y'){
					$tA = Utils::checkArray($q_links,array('group_id'=>$group['id']));
					if(!is_array($tA)){
						$row_data = array();
						$row_data[] = array('field'=>'user_id','value'=>$options['id']);
						$row_data[] = array('field'=>'group_id','value'=>$group['id']);						
						$options['db']->insert(BLACKBIRD_TABLE_PREFIX."users__groups",$row_data);						
					}
					$delete = false;
				}else{
					
				}
			}
			
			//delete
			if($delete === true){
				AdaptorMysql::sql("DELETE FROM `". BLACKBIRD_TABLE_PREFIX."users__groups` WHERE user_id = $options[id] AND group_id = '$group[id]' LIMIT 1");
			}
			
		}
		
		//don't return anything		
	
	}
	
	if($options['col_name'] == 'password' && $options['table'] == BLACKBIRD_USERS_TABLE){
		
		if(strlen($value) > 1){
			return array('field'=>'password','value'=>sha1($value));			
		}else{
			return false;
		}
	}

}
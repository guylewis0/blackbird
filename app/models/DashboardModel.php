<?php

class DashboardModel extends Model
{
	//this will need to be tweaked for other session setups to map firstname/lastname to user id
	public function getActivity()
	{
		$_pre = BLACKBIRD_TABLE_PREFIX;
		$sql = "
		SELECT " . $_pre . "history.*," . 
		$_pre . "users.firstname, " . $_pre . "users.lastname
		FROM " . $_pre . "history AS history
			LEFT JOIN " . $_pre . "users
				ON " . $_pre . "users.id = history.user_id
		ORDER BY history.modtime DESC
		LIMIT 20";
		
		//$q = $this->db->query($sql);
				
		if ($q = $this->db->query("SELECT * FROM " . BLACKBIRD_TABLE_PREFIX . "history ORDER BY modtime DESC LIMIT 20")) {

			$dataA = array();
		
			foreach($q as $row){
				$q_user = $this->db->queryRow("SELECT firstname,lastname FROM " . BLACKBIRD_TABLE_PREFIX . "users WHERE id = '$row[user_id]'");
				$tA = array();
				$tA['user'] = $q_user['firstname'] . ' ' . $q_user['lastname'];
				$tA = array_merge($tA,$row);
				$dataA[] = $tA;			
			}

			return $dataA;
		}
	}
	
	public function getUsers()
	{
		//select from users, merge with their latest activity, could do in 1 query
		$q = $this->db->query("SELECT * FROM " . BLACKBIRD_TABLE_PREFIX . "users");
		$tA = array();
		foreach($q as $row){
			$q_activity = $this->db->queryRow("SELECT * FROM " . BLACKBIRD_TABLE_PREFIX . "history WHERE user_id = '$row[id]' ORDER BY modtime DESC");
			$tA[] = array(
				'name'=>$row['firstname'] . ' ' . $row['lastname'],
				'activity'=>$q_activity['modtime'],
				'user_id'=>$row['id'],
				'email'=>$row['email'],
				'gravatar'=>'http://www.gravatar.com/avatar/' . md5($row['email']) . '?s=',
				'groups'=>_ControllerFront::formatCol('groups',$row['groups'],null)
			);
		}
		$tA = Utils::arraySort($tA,'activity');
		$tA = array_reverse($tA);
		return $tA;
		
	}
	
	public function getTables()
	{
		//query all tables, match against permissions
		$q = $this->db->query("SHOW TABLE STATUS");
		$tableA = array();
		foreach($q as $row){
			if(_ControllerFront::$session->getPermissions('select',$row['Name']) && (strpos($row['Name'], BLACKBIRD_TABLE_PREFIX) === false)){
				$tableA[] = $row;
			}
		}
		return $tableA;		
	}
	
	private function formatChartData($dataA)
	{
		$percents = '';
		$labels = '';
		for($i=0;$i<count($dataA);$i++){
			$row = $dataA[$i];
			$percents .= $row['percent'];
			$labels .= $row['name'] . ' (' . $row['total'] . ')';
			if($i<count($dataA)-1){
				$percents .= ',';
				$labels .= '|';
			}
		}
		
		return array('labels'=>$labels,'percents'=>$percents,'data'=>$dataA);
	}
	
	public function getChartUsers()
	{
		//select
		$q_tot = $this->db->queryRow("SELECT count(*) AS total FROM " . BLACKBIRD_TABLE_PREFIX . "history");
		if ($q = $this->db->query("SELECT count(*) AS total,user_id FROM " . BLACKBIRD_TABLE_PREFIX . "history WHERE user_id != '' GROUP BY user_id")) {
			$dataA = array();
			foreach($q as $row){
				$q_user = $this->db->queryRow("SELECT firstname,lastname FROM " . BLACKBIRD_TABLE_PREFIX . "users WHERE id = '$row[user_id]'");
				$perc = $row['total']/$q_tot['total'];
				$dataA[] = array('name'=>$q_user['firstname'] . ' ' . $q_user['lastname'],'total'=>$row['total'],'percent'=>floor(100*($perc)),'percent_actual'=>round($perc*100,2));			
			}
		
			return $this->formatChartData(Utils::arraySort($dataA,'percent'));
		}
	}
	
	public function getChartTables($id='')
	{
		$where = '';
		if($id != ''){
			$where = " WHERE user_id = '$id' ";
		}else{
			$where = " WHERE user_id != '' ";
		}
		
		$q_tot = $this->db->queryRow("SELECT count(*) AS total FROM " . BLACKBIRD_TABLE_PREFIX . "history " . $where);
		if ($q = $this->db->query("SELECT count(*) AS total,table_name FROM " . BLACKBIRD_TABLE_PREFIX . "history " . $where . " GROUP BY table_name")) {
		
			$dataA = array();
			foreach($q as $row){
				$perc = $row['total']/$q_tot['total'];
				$dataA[] = array('name'=>_ControllerFront::getTableName($row['table_name']),'total'=>$row['total'],'percent'=>floor(100*($perc)),'percent_actual'=>round($perc*100,2));			
			}
		
			return $this->formatChartData(Utils::arraySort($dataA,'percent'));
		}
	}
	
	public function getChartEdits($id='')
	{
		$where = '';
		if($id != ''){
			$where = " WHERE user_id = '$id' ";
		}else{
			$where = " WHERE user_id != '' ";
		}
		
		$q_tot = $this->db->queryRow("SELECT count(*) AS total FROM " . BLACKBIRD_TABLE_PREFIX . "history " . $where);
		if ($q = $this->db->query("SELECT count(*) AS total,action FROM " . BLACKBIRD_TABLE_PREFIX . "history " . $where . " GROUP BY action")) {
		
			$dataA = array();
			foreach($q as $row){
				$perc = $row['total']/$q_tot['total'];
				$dataA[] = array('name'=>$row['action'],'total'=>$row['total'],'percent'=>floor(100*($perc)),'percent_actual'=>round($perc*100,2));			
			}
		
			return $this->formatChartData(Utils::arraySort($dataA,'percent'));
		}
	}
	
}
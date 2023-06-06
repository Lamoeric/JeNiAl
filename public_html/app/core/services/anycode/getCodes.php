<?php
require('../../../../../private/'. $_SERVER['HTTP_HOST'].'/include/config.php');
require('../../../../include/nocache.php');

	try{
		$language = $_POST['language'];
		$codename = $_POST['codename'];
		$orderby = 	$_POST['orderby'];
	
		if(empty($orderby)) $orderby = 'text';
	
		$query = "SELECT code, text 
							FROM cpa_codetable cd 
							join cpa_text txt on txt.id = cd.description and language = '$language' 
							where cd.ctname = '$codename' 
							order by $orderby";
		
		$result = $mysqli->query( $query );
		$data = array();
		while ($row = $result->fetch_assoc()) {
			$data['data'][] = $row;
		}
		$data['success'] = true;
		echo json_encode($data);
	}catch (Exception $e){
		$data = array();
		$data['success'] = false;
		$data['message'] = $e->getMessage();
		echo json_encode($data);
	}
?>
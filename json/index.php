<?php
	header('Content-Type: application/json');
	require_once("../config.php");
	require_once("tsstatus.php");
	$tsstatus = new TSStatus($ts_host, $ts_port);
	$tsstatus->useServerPort($ts_query_port);
	$tsstatus->timeout = $ts_timeout;
	if(!empty($ts_query_login) && !empty($ts_query_password)){
		$tsstatus->setLoginPassword($ts_query_login, $ts_query_password);
	}
	$tsstatus->setCache($ts_cache);
	$ts_channel_id_whitelist = preg_replace('/\s+/','', $ts_channel_id_whitelist);
	if(!empty($ts_channel_id_whitelist)){
		$tsstatus->limitToChannels($ts_channel_id_whitelist);
	}
	$tsstatus->hideEmptyChannels = $ts_hide_empty_channels;
	$tsstatus->hideParentChannels = $ts_hide_parent_channels;
	echo $tsstatus->render();
?>
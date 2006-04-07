<?php

include_once 'includes/bootstrap.inc';
include_once 'includes/common.inc';

fix_gpc_magic();

if ($_GET['story']) {
	$story = $_GET['story'];
	$nid = db_result(db_query("SELECT nid 
							   FROM {drupal_redirect}
							   WHERE Rid = '%s'", $story));

	drupal_goto("node/$nid");	
}
?>

<?php 

// adapt the path to the two Drupal files below, according to your server  setup:
require_once 'includes/bootstrap.inc';
require_once 'includes/common.inc';

fix_gpc_magic();

if(isset($_GET['t']) && is_numeric($_GET['t'])) {
    $topic_id = $_GET['t'];

    $nid = db_result(db_query("SELECT nid 
							   FROM {phpbb2drupal_temp_topic} 
							   WHERE topic_id = %d", $topic_id));

    drupal_goto("node/$nid");
} 
elseif(isset($_GET['p'])) {
    $post = explode("#", $_GET['p']);
    if(is_numeric($post[0])) {
        $post_id = $post[0];
    } else {
		drupal_goto("/");
    }
    
    $cid = db_result(db_query("SELECT cid
							   FROM {phpbb2drupal_temp_post}
							   WHERE post_id = %d", $post_id));

    $nid = db_result(db_query("SELECT nid
                               FROM {comments}
                               WHERE cid = %d", $cid));

    drupal_goto("node/$nid#comment-$cid");
} 
elseif(isset($_GET['f']) && is_numeric($_GET['f'])) {
    $forum_id = $_GET['f'];

    $tid = db_result(db_query("SELECT tid
							   FROM {phpbb2drupal_temp_forum}
							   WHERE forum_id = %d", $forum_id));

    drupal_goto("taxonomy/term/$tid/all");    
}
?>     

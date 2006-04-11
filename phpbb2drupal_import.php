<?php
#!/usr/local/bin/php

/* $Id$ */

/**
 * Conversion script from phpbb 2.0.18 to Drupal 4.6.5.
 * 
 * Written by: John Hwang
 * Date: 2006-01-12
 *
 * The following modules are required to be not only installed but also enabled
 *
 * forum
 * node
 * comment
 * user
 * profile
 * taxonomy
 * upload (optional, need for attachments)
 * comment_upload (optional, need for attachments)
 * 
 * Notes:
 * 1. You have to specify where your PHPBB database and Drupal databases are.
 *    a. Open setting.php
 *    b. Change the default dsn_url to
 *       $db_url['default'] = 'mysql://username:password@localhost/drupal_database';
 *       $db_url['phpbb'] = 'mysql://username:password@localhost/phpbb_database';
 *    Because this script uses Drupal's db_set_active() to switch between databases
 *    The advantage of this method is that now you are not required to have the tables
 *    in the same database.  It doesn't even require that either databases be on the 
 *    same machine.
 * 2. Adjust the $TIME_LIMIT for an appropriately large value.  The larger the 
 *    data you're trying to import, the higher the value should be.
 * 3. To import attachments, change $PHPBB2DRUPAL_IMPORT_ATTACHMENTS's value to TRUE.
 * 4. Once you enable the forum module, it's a good idea to go to adminster->forums 
 *    so that the forum module creates a vocabulary for your forum.  If not, the script
 *    will automatically create a vocabulary name "Forums."
 * 5. The script does not support PHPBB2's attachment thumbnails
 * 6. To import attachments, install and Enable both the new comment.module and the comment_upload.module
 * 7. You must manually move/link the contents of the old attachment directory to drupal's files 
 *    directory of your drupal installation.  It import script does not check if the
 *    file actually exists.  It won't move it for you either.  It only imports whatever
 *    is in your PHPBB2 database.
 */

// Disable access checking?
$access_check = TRUE;

// error_reporting(E_ALL);
ini_set('display_errors', TRUE);

$PHPBB2DRUPAL_IMPORT_ATTACHMENTS = TRUE;
$PHPBB2DRUPAL_TIME_LIMIT = 1200;                  // 20 Minutes still might not be enough... but it workes well for 300,000
$PHPBB2DRUPAL_FORUM_NAME = 'Forums';

// Adjust how long you want the script to run...
if (!ini_get("safe_mode")) {
  set_time_limit($PHPBB2DRUPAL_TIME_LIMIT);
}

if (isset($_GET["op"])) {
  include_once "includes/bootstrap.inc";
  include_once "includes/common.inc";

  // Access check:
  if (($access_check == 0) || ($user->uid == 1)) {
    phpbb2drupal_page();
  }
  else {
    print phpbb2drupal_page_header("Access denied");
    print "<p>Access denied.  You are not authorized to access this page.  Please log in as the admin user (the first user you created). If you cannot log in, you will have to edit <code>phpbb2drupal.php</code> to bypass this access check.  To do this:</p>";
    print "<ol>";
    print " <li>With a text editor find the phpbb2drupal.php file on your system. It should be in the main Drupal directory that you installed all the files into.</li>";
    print " <li>There is a line near top of phpbb2drupal.php that says <code>\$access_check = TRUE;</code>. Change it to <code>\$access_check = FALSE;</code>.</li>";
    print " <li>As soon as the script is done, you must change the phpbb2drupal.php script back to its original form to <code>\$access_check = TRUE;</code>.</li>";
    print " <li>To avoid having this problem in future, remember to log in to your website as the admin user (the user you first created) before you backup your database at the beginning of the update process.</li>";
    print "</ol>";

    print phpbb2drupal_page_footer();
  }
}
else {
  phpbb2drupal_info();
}

function phpbb2drupal_info() {
  print phpbb2drupal_page_header("Drupal PHPBB2 Import");
?>
  <h3>Before doing anything, <strong>backup your database!</strong> This process will change your database and its values, and some things might get lost!!</h3>
  
  <h3>Notes:</h3>

  The following modules are required to be not only installed but also enabled:
  <ul>
    <li>forum</li>
    <li>node</li>
    <li>comment</li>
    <li>user</li>
    <li>profile</li>
    <li>taxonomy</li>
    <li>upload (optional)</li>
    <li>comment_upload (optional)</li>
  </ul>

  <ol>
  <li>You have to specify where your PHPBB database and Drupal databases are.</li>
      <ol>
      <li>Open setting.php</li>
      <li>Change the default dsn_url to</li>
          <ul>
          <li>$db_url['default'] = 'mysql://username:password@localhost/drupal_database';</li>
          <li>$db_url['phpbb'] = 'mysql://username:password@localhost/phpbb_database';</li>
          </ul>
      <li>Because this script uses Drupal's db_set_active() to switch between databases.  The advantage of this method is that now you are not required to have the tables in the same database.  It doesn't even require that either databases be on the same machine.</li>
      </ol>
  <li>Adjust the $PHPBB2DRUPAL_TIME_LIMIT for an appropriately large value.  The larger the data you're trying to import, the higher the value should be.</li>
  <li>Once you enable the forum module, it's a good idea to go to adminster->forums so that the forum module creates a vocabulary for your forum.  If not, the script will automatically create a vocabulary name "Forums."</li>
  <li>To import attachments, change $PHPBB2DRUPAL_IMPORT_ATTACHMENTS's value to TRUE.
  <li>The script does not support PHPBB2's attachment thumbnails</li>
  <li>Install and Enable both the new comment.module and the comment_upload.module</li>
  <li>You must manually move/link the contents of the old attachment directory to drupal's files directory of your drupal installation.  It import script does not check if the file actually exists.  It won't move it for you either.  It only imports whatever is in your PHPBB2 database.</li>
  </ol>

  <h3><a href="phpbb2drupal_import.php?op=import">Begin the import process</a></h3>
<?php
  print phpbb2drupal_page_footer();
}

function phpbb2drupal_page() {
  if (isset($_POST['edit'])) {
    $edit = $_POST['edit'];
  }

  $PHPBB2DRUPAL_FUNCTIONS = array(
    'users' => 'Import Users',
    'categories' => 'Import Categories',
    'topics' => 'Import Topics',
    'polls' => 'Import Polls',
    'posts' => 'Import Posts',
    'cleanup' => 'Clean Up'
  );

  print phpbb2drupal_page_header("Drupal PHPBB2 import");
  $links[] = "<a href=\"index.php\">main page</a>";
  $links[] = "<a href=\"index.php?q=admin\">administration pages</a>";
  print theme("item_list", $links);

  $action = $edit['import'];
  switch ($action) {
    case "users":
      
      if(variable_get('phpbb2drupal_import_user_successful', 0) == '0') {
        phpbb2drupal_import_users();
        $selected = 'users';
      } else {
        $selected = 'categories';
      }
      continue;

    case "categories":
     # if(variable_get('phpbb2drupal_import_category_successful', 0) == 0) {
        phpbb2drupal_import_categories();
     #   $selected = 'categories';
     # } else {
        $selected = 'topics';
     # }
      continue;

    case "topics":      
      if(variable_get('phpbb2drupal_import_topic_successful', 0) == 0) {
        phpbb2drupal_import_topics();
        $selected = 'topics';
      } else {
        $selected = 'polls';
      }
   
      continue;
    
    case "polls":      
      if(variable_get('phpbb2drupal_import_poll_successful', 0) == 0) {
        phpbb2drupal_import_polls();
        $selected = 'polls';
      } else {
        $selected = 'posts';
      }
   
      continue;
      
    case "posts":
      if(variable_get('phpbb2drupal_import_post_successful', 0) == 0) {
        print '<h1>About to import posts</h1>';
        phpbb2drupal_import_posts();
        $selected = 'posts';
      } else {
        $selected = 'cleanup';
        print "<h2>Congratulations.  Import Finished</h2>";
      }
      continue;

    case "cleanup":
      phpbb2drupal_import_cleanup();
      continue;
    
    default:
       // make update form and output it.
      $selected = 'users';
      continue;
  }

  $form = form_select("Next import to perform", "import", $selected, $PHPBB2DRUPAL_FUNCTIONS);
  $form .= form_submit("Import");
  print form($form);

  print phpbb2drupal_page_footer();
}

/**
 * User Import Functions
 */
function phpbb2drupal_import_users() {

    // check if the user database has been successfully imported
    db_set_active('default');
    if(variable_get('phpbb2drupal_import_user_successful', 0) == 1) return;

    if(variable_get('phpbb2drupal_import_user_started', 0) == 0) {
        // create temporary tables
        db_set_active('default');
        db_query("DROP TABLE IF EXISTS {phpbb2drupal_temp_user}");
        db_query("CREATE TABLE {phpbb2drupal_temp_user} (
            user_id mediumint(8) DEFAULT '0' NOT NULL,
                    uid INTEGER UNSIGNED DEFAULT '0' NOT NULL,
                    KEY user_id (user_id))"  
                );

        // create profile fields for icq, aim, msn...etc
        db_query("INSERT INTO {profile_fields} (title, name, explanation, category, page, type, weight, required, register, visibility, options) VALUES ('YIM','user_yim','','Contact','','textfield',0,0,1,2,''),('AIM','user_aim','','Contact','','textfield',0,0,1,2,''),('MSN','user_msnm','','Contact','','textfield',0,0,1,2,''),('icq','user_icq','','Contact','','textfield',0,0,1,2,''),('Website','user_website','','Contact','','url',0,0,1,2,''),('Location','user_from','','Personal','','textfield',0,0,1,2,''),('Occupation','user_occ','','Personal','','textfield',0,0,1,2,''),('Interests','user_interests','','Personal','','textfield',0,0,1,2,'')");

        variable_set('phpbb2drupal_import_user_started', 1);
    }

    // adding the admin uid so that other functions can find the admin mapping
    db_set_active('default');
    db_query("INSERT INTO {phpbb2drupal_temp_user} (user_id, uid) VALUES (2 , 1)");

    $files_path = variable_get('file_directory_path', 'files');
    $pictures_path = variable_get('user_picture_path', 'pictures');

    // Insert the users into drupal
    db_set_active('phpbb');
    $user_ids = db_query("SELECT user_id FROM {users} WHERE user_id > 2 ORDER BY user_id");

    $user_count = db_num_rows($user_ids);

    if(!$user_count) {
        exit("There were no users found: Aborting script");
    }

    print "<h3>Found $user_count users: Beginning Import</h3>";
    flush();

    while($result = db_fetch_object($user_ids)) {    

        db_set_active('phpbb');
        $user = db_fetch_object(db_query("SELECT * FROM {users} WHERE user_id = %d", $result->user_id));

        // Make sure the user is not on the banlist
        /* db_set_active('phpbb');
        $banned = db_result(db_query("SELECT COUNT(*) FROM {banlist} WHERE ban_userid = %d", $user->user_id));
        if($banned) {
            db_set_active('phpbb');
            continue;
        }*/

        // Make sure the user is has not already been imported
        db_set_active('default');
        $count = db_result(db_query("SELECT COUNT(*) FROM {phpbb2drupal_temp_user} WHERE user_id = %d", $user->user_id));
        if($count > 0) {
            $user->user_active = 0;
        }

        $user->user_aim = strtr($user->user_aim, array("+" => ' ')); # PHPBB stores spaces as +, replace with ' '
        $user->user_yim = strtr($user->user_yim, array("+" => ' '));
        $user->user_timezone = $user->user_timezone * 60 * 60;  # Drupal stores timezones in seconds

        // remove the bbcode_uid from post_text
        $user->user_sig = preg_replace("/:$user->user_sig_bbcode_uid/", '', $user->user_sig);

        // if the $user->user_avatar_type is not their own image, delete it
        // drupal doesn't have pre-defined avatars.   if we were to import it
        // then multiple people would share the same avatar image and if one user 
        // were to changes their avatar then it would change it for everybody else.
        if($user->user_avatar_type > 1) {
            $user->user_avatar = '';
        }

        $user->user_avatar =  ($user->user_avatar) ? "$files_path/$pictures_path/$user->user_avatar" : '';
    
        $data = array(
                'name' => $user->username,
                'pass' => $user->user_password,
                'mail' => $user->user_email,
                'signature' => $user->user_sig,
                'created' => $user->user_regdate,
                'status' => $user->user_active,
                'timezone' => $user->user_timezone, 
                'picture' => $user->user_avatar,
                'init' => $user->user_email,
                'roles' => array(0 => 2),                       # Authenticated User
                'user_website' => $user->user_website,
                'user_from' => $user->user_from,
                'user_icq' => $user->user_icq,
                'user_aim' => $user->user_aim,
                'user_yim' => $user->user_yim,
                'user_msnm' => $user->user_msnm,
                'user_occ' => $user->user_occ,
                'user_interests' => $user->user_interest
                );

        db_set_active('default');
        $drupal_user = phpbb2drupal_user_save($data, array('account', 'Personal', 'Contact'));

        //      print "<pre>";
        //      print_r($drupal_user);
        //      print "</pre>";

        // populate the temporary user table
        db_set_active('default');
        db_query("INSERT INTO {phpbb2drupal_temp_user} (user_id, uid) VALUES ($user->user_id , $drupal_user->uid)");

        db_set_active('phpbb');
    }

    // set the user import successful flag in the variable table
    db_set_active('default');
    variable_set('phpbb2drupal_import_user_successful', '1');

    $count = db_result(db_query("SELECT COUNT(*) FROM {phpbb2drupal_temp_user}"));
    print "<h3>Successfully Imported $count Users</h3>";
}

/**
 *
 * Create Forum Containers and Forums
 *
 */
function phpbb2drupal_import_categories() {

    db_set_active('default');

    // check if the forum database has been successfully imported
    if(variable_get('phpbb2drupal_import_category_successful', 0) == 1) return;

    // forum mapping temporary tables
    if(variable_get('phpbb2drupal_import_category_started', 0) == 0) {
        db_set_active('default');
        db_query("DROP TABLE IF EXISTS {phpbb2drupal_temp_forum}");
        db_query("CREATE TABLE {phpbb2drupal_temp_forum} (
            forum_id smallint(5) UNSIGNED DEFAULT '0' NOT NULL,
                     tid integer UNSIGNED DEFAULT '0' NOT NULL,
                     KEY forum_id (forum_id))"  
                );
        variable_set('phpbb2drupal_import_category_started', 1);
    }

    // Retrieve the vocabulary vid named "Forum"
    $forum_vid = _forum_get_vid();

    print "<h3>Forum vid: $forum_vid</h3>";
    flush();

    // Get Categories/Forums from PHPBB
    db_set_active('phpbb');
    $category_results = db_query("SELECT * FROM {categories} ORDER BY cat_order");

    $cat_count = db_num_rows($category_results);

    print "<h3>Found $cat_count categories: Beginning Import</h3>";
    flush();

    while($category_result = db_fetch_array($category_results)) {

        $cat_id = $category_result['cat_id'];
        $forums_results = db_query("SELECT * FROM {forums} WHERE cat_id = $cat_id");

        $phpbb2_forums = array();  # reinitialize the temp var not to include it multiple times
            while($forum_result = db_fetch_object($forums_results)) {
                //$phpbb2_categories[$category_result->cat_id]['forums'][] = $forums_result;
                $phpbb2_forums[$forum_result->forum_id] = $forum_result;
            }

        $phpbb2_categories[$cat_id] = array_merge($category_result, array('forums' => $phpbb2_forums));
    }

    //  print "<pre>";
    //  print_r($phpbb2_categories);
    //  print "</pre>";

    // Insert the Containers / Forum into Drupal
    db_set_active('default');

    // Insert the Containers
    $container_order = -10;
    foreach($phpbb2_categories as $container) {
        $edit = array('name' => $container['cat_title'],
                'vid' => $forum_vid,
                'description' => '',
                'weight' => $container_order);

        $edit = taxonomy_save_term($edit);    
        //print_r($edit);

        // serialize the forum containers
        $containers = variable_get('forum_containers', array());
        $containers[] = $edit['tid'];
        variable_set('forum_containers', $containers); 

        // Insert the Forums
        $forum_order = -10;
        foreach($container['forums'] as $forum) {
            // Make sure the forum/term is has not already been imported
            if(!db_result(db_query("SELECT forum_id FROM {phpbb2drupal_temp_forum} WHERE forum_id = $forum->forum_id"))) {
                $forum_edit = array('name' => $forum->forum_name,
                        'vid' =>$forum_vid,
                        'description' => $forum->forum_desc,
                        'weight' => $forum_order,
                        'parent' => array(0=>$edit['tid']));

                $forum_edit = taxonomy_save_term($forum_edit);

                $forum_order++;
                $tid = $forum_edit['tid'];

                db_set_active('default');
                db_query("INSERT INTO {phpbb2drupal_temp_forum} (forum_id, tid) VALUES ($forum->forum_id, $tid)");
            }
        }

        $container_order++;
    }

    db_set_active('default');
    // set the forums import successful flag in the variable table
    variable_set('phpbb2drupal_import_category_successful', '1');

    $count = db_result(db_query("SELECT COUNT(*) FROM {phpbb2drupal_temp_forum}"));
    print "<h3>Successfully Imported $count forums</h3>";
}

/**
 *
 * Imports PHPBB topics to Drupal equivalent forum nodes
 *
 */
function phpbb2drupal_import_topics() {
    global $PHPBB2DRUPAL_IMPORT_ATTACHMENTS;

    db_set_active('default');

    // check if the post database has been successfully imported
    if(variable_get('phpbb2drupal_import_topic_successful', 0) == 1) return;

    if(variable_get('phpbb2drupal_import_topic_started', 0) == 0) {
        db_set_active('default');
        db_query("DROP TABLE IF EXISTS {phpbb2drupal_temp_topic}");
        db_query("CREATE TABLE {phpbb2drupal_temp_topic} (
                     topic_id mediumint(8) UNSIGNED DEFAULT '0' NOT NULL,
                     post_id  mediumint(8) UNSIGNED DEFAULT '0' NOT NULL,
                     nid integer UNSIGNED DEFAULT '0' NOT NULL,
                     KEY topic_id (topic_id))"  
                );
        variable_set('phpbb2drupal_import_topic_started', 1);
    }

    // Get All topics from PHPBB
    db_set_active('phpbb');
    $topic_ids = db_query("SELECT topic_id
                            FROM {topics} 
                            WHERE topic_vote <> 1
                            ORDER BY topic_id");  // topic_status == 2, Moved topics are duplicates don't import

    $topic_count = db_num_rows($topic_ids);

    print "<h3>About to import $topic_count topics</h3>";
    flush();

    // Import the topics into drupal
    $counter = 0;
    db_set_active('phpbb');
    while($result = db_fetch_object($topic_ids)) {
        
        // check if this topic has been imported already just to be sure
        db_set_active('default');
        $count = db_result(db_query("SELECT count(*) FROM {phpbb2drupal_temp_topic} WHERE topic_id = %d", $result->topic_id));
        if($count > 0) {
            #print "<h3>Topic $result->topic_id has already been imported</h3>";    
            #flush();
            db_set_active('phpbb');
            continue;
        } 

        db_set_active('phpbb');
        /*$query = db_query("SELECT *
                            FROM {topics} t
                            INNER JOIN {posts} p ON t.topic_id = p.topic_id
                            INNER JOIN {posts_text} pt ON p.post_id = pt.post_id                            
                            WHERE t.topic_id = %d
                            ORDER BY p.post_id     
                            LIMIT 1", $result->topic_id); /**/

        $query = db_query("SELECT *
                            FROM {topics} t
                            INNER JOIN {posts} p ON t.topic_id = p.topic_id
                            INNER JOIN {posts_text} pt ON p.post_id = pt.post_id                            
                            WHERE p.post_id = t.topic_first_post_id
                            AND t.topic_id = %d", $result->topic_id);
        
        // check if the topic is a valid topic.  if not, continue on
        if(db_num_rows($query)) {
            $topic = db_fetch_object($query);
        } else {
            print "<h3>Could not find post details of topic: $result->topic_id</h3>";    
            flush();
            continue;
        }
        
        db_set_active('default');
        $uid = db_result(db_query("SELECT uid FROM {phpbb2drupal_temp_user} WHERE user_id = %d", $topic->topic_poster));
        $tid = db_result(db_query("SELECT tid FROM {phpbb2drupal_temp_forum} WHERE forum_id = %d", $topic->forum_id));

        if($topic->topic_poster == 2) {   // is the admin
            $uid = 1;
        } elseif($topic->topic_poster == -1) {
            $uid = 0;
        }

        if($topic->topic_type == 1) {
            $sticky = 1;      // sticky
            $promote = 0;
        } elseif ($topic->topic_type == 2) {
            $sticky = 1;
            $promote = 0;     // display on the front page, i.e. promote
        } else {
            $sticky = 0;
            $promote = 0;
        }

        if ($topic->topic_status == 1) { // LOCKED
            $comment = 1;  // read-only
        } else { // UNLOCKED & WATCH NOTIFIED
            $comment = 2;  // read-write
        }

        // remove the bbcode_uid from post_text
        $topic->post_text = preg_replace("/:$topic->bbcode_uid/", '', $topic->post_text);

        $teaser = node_teaser($topic->post_text);

        //construct the node
        $node = array(
                'type' => 'forum',
                'title' => $topic->topic_title,
                'uid' => $uid,
                'status' => 1,  // published or not - always publish
                'promote' => $promote,
                'created' => $topic->topic_time,
                'changed' => $topic->post_edit_time,
                'comment' => $comment,
                'moderate' => 0,
                'body' => $topic->post_text,
                'sticky' => $sticky,
                'teaser' => $teaser
                );

        if($topic->topic_status == 2) {
            db_set_active('phpbb');
            $forum_id = db_result(db_query("SELECT forum_id FROM {topics} WHERE topic_id = %d", $topic_moved_id));
            db_set_active('default');
            $moved_tid = db_result(db_query("SELECT tid FROM {phpbb2drupal_temp_forum} WHERE forum_id = %d", $forum_id));

            $node['tid'] = $moved_tid;        // which forum it used to be part of
        } else {
            $node['tid'] = $tid;
        }

        $node = array2object($node); // node_save requires an object form

        //      print "<pre>";
        //      print_r($node);
        //      print "</pre>";

        db_set_active('default');
        $nid = node_save($node);
        taxonomy_node_save($nid, array(0 => $tid));

        if(!$nid) {
            print "<h3>Failed importing $topic->topic_id</h3>";
            flush();
        }

        // Handle attachments
        if($PHPBB2DRUPAL_IMPORT_ATTACHMENTS) {
            if($topic->topic_attachment == 1) {

                db_set_active('default');
                $file_path = variable_get('file_directory_path', 'files');

                db_set_active('phpbb');
                $files = db_query("SELECT * 
                                    FROM {attachments} a 
                                    INNER JOIN {attachments_desc} ad ON a.attach_id = ad.attach_id
                                    INNER JOIN {posts} p ON a.post_id = p.post_id 
                                    WHERE p.topic_id = %d
                                    ORDER BY a.attach_id", $topic->topic_id);

                while($file = db_fetch_object($files)) {
                    db_set_active('default');
                    $fid = db_next_id('{files}_fid');
                    db_query("INSERT INTO {files} (fid, nid, filename, filepath, filemime, filesize, list) VALUES (%d, %d, '%s', '%s', '%s', %d, %d)", $fid, $nid, $file->real_filename, "$file_path/$file->physical_filename", $file->mimetype, $file->filesize, 1);
                    db_set_active('phpbb');
                }
            }
        }

        db_set_active('default');
        db_query("INSERT INTO {phpbb2drupal_temp_topic} (topic_id, post_id, nid) VALUES (%d, %d, %d)", $topic->topic_id, $topic->post_id, $nid);

        db_set_active('phpbb');
    }

    db_set_active('default');
    // set the topic import successful flag in the variable table
    variable_set('phpbb2drupal_import_topic_successful', '1');

    $count = db_result(db_query("SELECT COUNT(*) FROM {phpbb2drupal_temp_topic}"));
    print "<h3>Successfully Imported $count topics</h3>";
}

function phpbb2drupal_import_polls() {
    db_set_active('default');

    // check if the post database has been successfully imported
    if(variable_get('phpbb2drupal_import_polls_successful', 0) == 1) return;

    if(variable_get('phpbb2drupal_import_polls_started', 0) == 0) {
        variable_set('phpbb2drupal_import_poll_started', 1);
    }

    // Get all polls from PHPBB
    db_set_active('phpbb');
    $topics = db_query("SELECT *
                        FROM {topics} t
                        WHERE topic_vote = 1
                        ORDER BY topic_id");

    $topic_count = db_num_rows($topics);

    print "<h3>About to import $topic_count polls</h3>";
    flush();

    // insert into polls
    while($topic = db_fetch_object($topics)) {

        // check if this topic has been imported already just to be sure
        db_set_active('default');
        $count = db_result(db_query("SELECT count(*) FROM {phpbb2drupal_temp_topic} WHERE topic_id = %d", $topic->topic_id));
        if($count > 0) {
            print "<h3>Poll $result->topic_id has already been imported</h3>";    
            flush();
            db_set_active('phpbb');
            continue;
        } 

        // get the polls 
        db_set_active('phpbb');
        $query = db_query("SELECT *
                           FROM {vote_desc} vd
                           WHERE topic_id = %d
                           ORDER BY vote_id", $topic->topic_id); 

        if(db_num_rows($query)) {
            $poll = db_fetch_object($query);
        } else {
            print "<h3>Could not find details of poll: $topic->topic_id</h3>";    
            flush();
            continue;
        }

        // get vote results
        $query = db_query("SELECT *
                           FROM {vote_results}
                           WHERE vote_id = %d
                           ORDER BY vote_option_id", $poll->vote_id);

        if(db_num_rows($query)) {
            $choice = array();
            while($result = db_fetch_object($query)) {
                $choice[] = array('chtext' => $result->vote_option_text,
                                  'chvotes' => $result->vote_result);
            }
        } else {
            print "<h3>Could not find vote_results details of poll: $poll->vote_id</h3>";    
            flush();
            continue;
        }

        // get voter information
        $query = db_query("SELECT vote_user_id
                           FROM {vote_voters}
                           WHERE vote_id = %d
                           ORDER BY vote_id", $poll->vote_id);

        if(db_num_rows($query)) {
            $polled = '';
            db_set_active('phpbb');
            while($result = db_fetch_object($query)) {
                db_set_active('default');
                $uid = db_result(db_query("SELECT uid FROM {phpbb2drupal_temp_user} WHERE user_id = %d", $result->vote_user_id));
                $polled = $polled . ' ' . "_" . $uid . "_";
                db_set_active('phpbb');
            }
            //print "<pre>";
            //print_r($polled);
            //print "</pre>";
        }

        db_set_active('default');
        $uid = db_result(db_query("SELECT uid FROM {phpbb2drupal_temp_user} WHERE user_id = %d", $topic->topic_poster));
        $tid = db_result(db_query("SELECT tid FROM {phpbb2drupal_temp_forum} WHERE forum_id = %d", $topic->forum_id));

        if($topic->topic_poster == 2) {   // is the admin
            $uid = 1;
        } elseif($topic->topic_poster == -1) {
            $uid = 0;
        }

        if($topic->topic_type == 1) {
            $sticky = 1;      // sticky
            $promote = 0;
        } elseif ($topic->topic_type == 2) {
            $sticky = 0;
            $promote = 1;     // display on the front page, i.e. promote
        } else {
            $sticky = 0;
            $promote = 0;
        }

        if ($topic->topic_status == 1) { // LOCKED
            $comment = 1;  // read-only
        } else { // UNLOCKED & WATCH NOTIFIED
            $comment = 2;  // read-write
        }

        //construct the node
        $node = array(
                'type' => 'poll',
                'title' => $poll->vote_text,
                'uid' => $uid,
                'status' => 1,  // published or not - always publish
                'promote' => $promote,
                'created' => $topic->topic_time,
                'changed' => $topic->topic_time,
                'comment' => $comment,
                'moderate' => 0,
                'body' => '',
                'sticky' => $sticky,
                'teaser' => $teaser
                );


        // handle moved nodes
        if($topic->topic_status == 2) {
            db_set_active('phpbb');
            $forum_id = db_result(db_query("SELECT forum_id FROM {topics} WHERE topic_id = %d", $topic_moved_id));
            db_set_active('default');
            $moved_tid = db_result(db_query("SELECT tid FROM {phpbb2drupal_temp_forum} WHERE forum_id = %d", $forum_id));

            $node['tid'] = $moved_tid;        // which forum it used to be part of
        } else {
            $node['tid'] = $tid;
        }

        // Add poll node information
        $node['runtime'] = $poll->vote_length;
        $node['active'] = (time() > ($poll->start+$poll->length)) ? 0 : 1;
        $node['choice'] = $choice;

        $node = array2object($node); // node_save requires an object form
        
        db_set_active('default');
        $nid = node_save($node);
        taxonomy_node_save($nid, array(0 => $tid));

        if(!$nid) {
            print "<h3>Failed importing $topic->topic_id</h3>";
            flush();
        }

        // manually update the poll table to store the uid of those who voted
        db_query("UPDATE {poll} SET polled = '%s' WHERE nid = %d", $polled, $nid);

        db_set_active('default');
        db_query("INSERT INTO {phpbb2drupal_temp_topic} (topic_id, post_id, nid) VALUES (%d, %d, %d)", $topic->topic_id, $topic->post_id, $nid);

        db_set_active('phpbb');
    }

    db_set_active('default');
    // set the topic import successful flag in the variable table
    variable_set('phpbb2drupal_import_poll_successful', '1');

    print "<h3>Successfully imported polls</h3>";
}

/**
 *
 * PHPBB Posts --> Drupal Comments
 *
 */
function phpbb2drupal_import_posts() {
    global $PHPBB2DRUPAL_IMPORT_ATTACHMENTS;

   # db_set_active('phpbb');
   # $total_posts = db_result(db_query("SELECT COUNT(*) FROM {posts} WHERE post_id <> topic_id"));

    db_set_active('default');
    // check if the post database has been successfully imported
    if(variable_get('phpbb2drupal_import_post_successful', 0) == 1) return;

    if(variable_get('phpbb2drupal_import_post_started', 0) == 0) {
        db_set_active('default');
        db_query("DROP TABLE IF EXISTS {phpbb2drupal_temp_post}");
        db_query("CREATE TABLE {phpbb2drupal_temp_post} (
                    post_id mediumint(8) UNSIGNED DEFAULT '0' NOT NULL,
                    cid int(10) DEFAULT '0' NOT NULL,
                    KEY post_id (post_id))"  
                );

        db_set_active('default');
        variable_set('phpbb2drupal_import_post_started', 1);
    }

    db_set_active('phpbb');
    $topic_ids = db_query("SELECT topic_id, topic_vote, topic_first_post_id, topic_last_post_id
                           FROM {topics} 
                           WHERE topic_replies > 0
                           ORDER BY topic_id");

    $topic_count = db_num_rows($topic_ids);
    print "<h3>Importing comments of $topic_count topics</h3>";
    flush();

    $errors = 0;
    $loops = 0;
    // Import the posts into drupal
    while($obj = db_fetch_object($topic_ids)) {
        $loops++;
        
        // skip first post if the post is not a poll
        // stupid phpbb... make the way you store topics consistent for crying out loud
        db_set_active('phpbb');
        if($obj->topic_vote == 0) {
            $post_ids = db_query("SELECT post_id 
                                  FROM {posts} 
                                  WHERE topic_id = %d
                                  AND post_id <> $obj->topic_first_post_id
                                  ORDER BY post_id", $obj->topic_id);
        } else {
            $post_ids = db_query("SELECT post_id 
                                  FROM {posts} 
                                  WHERE topic_id = %d
                                  ORDER BY post_id", $obj->topic_id);
        }
        
        unset($obj);

        while($result = db_fetch_object($post_ids)) {
            $loops++;

            db_set_active('phpbb');
            /*$query = db_query("SELECT *
                               FROM {posts} p
                               INNER JOIN {posts_text} pt ON p.post_id = pt.post_id
                               WHERE p.post_id = %d", $result->post_id); /**/

            $query = db_query("SELECT *
                               FROM {posts} p, {posts_text} pt
                               WHERE p.post_id = pt.post_id
                               AND p.post_id = %d", $result->post_id);
            
            // make sure the post is valid
            if(db_num_rows($query)) {
                $post = db_fetch_object($query);
            } else {
                $errors++;
                #print "<h3>Couldn't find post text for $result->post_id</h3>";
                #flush();
                continue;
            }
            
            // skip if the post has already been imported
            db_set_active('default');
            $count = db_result(db_query("SELECT COUNT(*) FROM {phpbb2drupal_temp_post} WHERE post_id = %d", $post->post_id));
            if($count > 0) {
                $errors++;
                //print "<h3>Error! $post->post_id was already inserted</h3>";
                //flush();
                db_set_active('phpbb');
                continue;
            }

            db_set_active('default');
            $uid = db_result(db_query("SELECT uid FROM {phpbb2drupal_temp_user} WHERE user_id = %d", $post->poster_id));
            $nid = db_result(db_query("SELECT nid FROM {phpbb2drupal_temp_topic} WHERE topic_id = %d", $post->topic_id));
            $pid = db_result(db_query("SELECT MAX(pid) FROM {comments} WHERE nid = %d", $nid));

            $pid = (is_null($pid)) ? 0 : $pid;

            if($post->poster_id == 2) {   // is the admin
                $uid = 1;
            } elseif($post->poster_id == -1) { // anonymous
                $uid = 0; 
            }    

            $hostname = phpbb2drupal_decode_ip($post->poster_ip);

            // remove the :bbcode_uid from post_text
            $post->post_text = preg_replace("/:$post->bbcode_uid/", '', $post->post_text);

            //construct the node
            $comment = array(
                    'pid' => $pid,
                    'nid' => $nid,
                    'uid' => $uid,
                    'subject' => $post->post_subject,
                    'comment' => $post->post_text,
                    'hostname' => $hostname,
                    'timestamp' => $post->post_time
                    );

            //      print "<pre>";
            //      print_r($comment);
            //      print "</pre>";

            db_set_active('default');
            $cid = phpbb2drupal_comment_save($comment);

            if(!$cid) {
                $errors++;
                #print "<h3>Failed importing $post->post_id</h3>";
                #flush();
            }
            
            // Handle attachments
            if($PHPBB2DRUPAL_IMPORT_ATTACHMENTS) {
                if($post->post_attachment == 1) {
                    db_set_active('default');
                    $file_path = variable_get('file_directory_path', 'files');

                    db_set_active('phpbb');
                    $files = db_query("SELECT * 
                                        FROM {attachments} a 
                                        INNER JOIN {attachments_desc} ad ON a.attach_id = ad.attach_id
                                        WHERE a.post_id = %d", $post->post_id);

                    while($file = db_fetch_object($files)) {
                        db_set_active('default');
                        $fid = db_next_id('{files}_fid');
                        db_query("INSERT INTO {files} (fid, nid, filename, filepath, filemime, filesize, list) VALUES (%d, %d, '%s', '%s', '%s', %d, %d)", $fid, 0, $file->real_filename, "$file_path/$file->physical_filename", $file->mimetype, $file->filesize, 1);
                        db_query("INSERT INTO {comment_files} (cid, fid) VALUES (%d, %d)", $cid, $fid);
                        db_set_active('phpbb');
                    }        
                }
            }

            db_set_active('default');
            db_query("INSERT INTO {phpbb2drupal_temp_post} (post_id, cid) VALUES (%d, %d)", $post->post_id, $cid);

            db_set_active('phpbb');
        }
    }

    // set the post import successful flag in the variable table
    db_set_active('default');
    variable_set('phpbb2drupal_import_post_successful', '1');
    print "<h3>Successfully Imported $imported posts</h3>";
    print "<h3>The were $loops loops executed</h3>";
    print "<h3>There $errors errors while importing posts</h3>";
    //}
}

/**
 *
 * Clean UP
 *
 */
function phpbb2drupal_import_cleanup() {
    global $PHPBB2DRUPAL_IMPORT_ATTACHMENTS;

#
# Update Drupal sequence 
#
    db_set_active('default');
    $term_data_tid = db_result(db_query("SELECT MAX(tid) FROM {term_data}"));
    $comments_cid = db_result(db_query("SELECT MAX(cid) FROM {comments}"));
    $node_nid = db_result(db_query("SELECT MAX(nid) FROM {node}"));
    $users_uid = db_result(db_query("SELECT MAX(uid) FROM {users}"));

    db_query("DELETE FROM {sequences} WHERE name='term_data_tid'");
    db_query("DELETE FROM {sequences} WHERE name='comments_cid'");
    db_query("DELETE FROM {sequences} WHERE name='node_nid'");
    db_query("DELETE FROM {sequences} WHERE name='users_uid'");

    db_query("INSERT INTO {sequences} (name,id) VALUE('term_data_tid', $term_data_tid)");
    db_query("INSERT INTO {sequences} (name,id) VALUE('comments_cid', $comments_cid)");
    db_query("INSERT INTO {sequences} (name,id) VALUE('node_nid', $node_nid)");
    db_query("INSERT INTO {sequences} (name,id) VALUE('users_uid', $users_uid)");

    if($PHPBB2DRUPAL_IMPORT_ATTACHMENTS) {
        $files_fid = db_result(db_query("SELECT MAX(fid) FROM {files}"));
        db_query("DELETE FROM {sequences} WHERE name='files_fid'");
        db_query("INSERT INTO {sequences} (name,id) VALUE('files_fid', $files_fid)");
    }

    #db_query("DROP TABLE IF EXISTS {phpbb2drupal_temp_user}");
    #db_query("DROP TABLE IF EXISTS {phpbb2drupal_temp_forum}");
    #db_query("DROP TABLE IF EXISTS {phpbb2drupal_temp_topic}");
    #db_query("DROP TABLE IF EXISTS {phpbb2drupal_temp_post}");

    variable_del('phpbb2drupal_import_user_successful');
    variable_del('phpbb2drupal_import_user_started');
    variable_del('phpbb2drupal_import_category_successful');
    variable_del('phpbb2drupal_import_category_started');
    variable_del('phpbb2drupal_import_topic_successful');
    variable_del('phpbb2drupal_import_topic_started');
    variable_del('phpbb2drupal_import_post_successful');
    variable_del('phpbb2drupal_import_post_started');

    db_query('DELETE FROM {cache}');
}

/**
 *
 * Helper Functions
 *
 */
function phpbb2drupal_user_save($array = array(), $category = array()) {
  // Dynamically compose a SQL query:
  $user_fields = user_fields();
  
  //$array['created'] = time();
  $array['changed'] = time();
  $array['uid'] = db_next_id('{users}_uid');

  // Note, we wait with saving the data column to prevent module-handled
  // fields from being saved there. We cannot invoke hook_user('insert') here
  // because we don't have a fully initialized user object yet.
  foreach ($array as $key => $value) {
    if ($key == 'pass') {
      $fields[] = db_escape_string($key);
      $values[] = $value;
      $s[] = "'%s'";
    }
    else if (substr($key, 0, 4) !== 'auth') {
      if (in_array($key, $user_fields)) {
        $fields[] = db_escape_string($key);
        $values[] = $value;
        $s[] = "'%s'";
      }
    }
  }
  db_query('INSERT INTO {users} ('. implode(', ', $fields) .') VALUES ('. implode(', ', $s) .')', $values);

  // Reload user roles (delete just to be safe).
  db_query('DELETE FROM {users_roles} WHERE uid = %d', $array['uid']);
  foreach ($array['roles'] as $rid) {
    db_query('INSERT INTO {users_roles} (uid, rid) VALUES (%d, %d)', $array['uid'], $rid);
  }

  // Build the initial user object.
  $user = user_load(array('uid' => $array['uid']));

  db_set_active('default');
  phpbb2drupal_profile_save_profile($array, $user, $category); // add user profile information

  // Build and save the serialized data field now
  $data = array();
  foreach ($array as $key => $value) {
    if ((substr($key, 0, 4) !== 'auth') && (!in_array($key, $user_fields)) && ($value !== null)) {
      $data[$key] = $value;
    }
  }
  db_query("UPDATE {users} SET data = '%s' WHERE uid = %d", serialize($data), $user->uid);

  // Build the finished user object.
  $user = user_load(array('uid' => $array['uid']));


  // Save distributed authentication mappings
  foreach ($array as $key => $value) {
    if (substr($key, 0, 4) == 'auth') {
      $authmaps[$key] = $value;
    }
  }
  if ($authmaps) {
    user_set_authmaps($user, $authmaps);
  }

  return $user;
}

function phpbb2drupal_profile_save_profile(&$edit, &$user, $category) {

    $result = db_query('SELECT fid, name, type, category, weight FROM {profile_fields} WHERE register = 1 ORDER BY category, weight');

    while ($field = db_fetch_object($result)) {
        if (_profile_field_serialize($field->type)) {
            $edit[$field->name] = serialize($edit[$field->name]);
        }
        db_query("DELETE FROM {profile_values} WHERE fid = %d AND uid = %d", $field->fid, $user->uid);
        db_query("INSERT INTO {profile_values} (fid, uid, value) VALUES (%d, %d, '%s')", $field->fid, $user->uid, $edit[$field->name]);
        // Mark field as handled (prevents saving to user->data).
        $edit[$field->name] = null;
    }
} 

function phpbb2drupal_comment_save($edit) {
    db_set_active('default');
    // Here we are building the thread field.  See the comment
    // in comment_render().
    if ($edit['pid'] == 0) {
        // This is a comment with no parent comment (depth 0): we start
        // by retrieving the maximum thread level.
        $max = db_result(db_query('SELECT MAX(thread) FROM {comments} WHERE nid = %d', $edit['nid']));

        // Strip the "/" from the end of the thread.
        $max = rtrim($max, '/');

        // Next, we increase this value by one.  Note that we can't
        // use 1, 2, 3, ... 9, 10, 11 because we order by string and
        // 10 would be right after 1.  We use 1, 2, 3, ..., 9, 91,
        // 92, 93, ... instead.  Ugly but fast.
        $decimals = (string) substr($max, 0, strlen($max) - 1);
        $units = substr($max, -1, 1);
        if ($units) {
            $units++;
        }
        else {
            $units = 1;
        }

        if ($units == 10) {
            $units = '90';
        }

        // Finally, build the thread field for this new comment.
        $thread = $decimals . $units .'/';
    }
    else {
        // This is comment with a parent comment: we increase
        // the part of the thread value at the proper depth.

        // Get the parent comment:
        $parent = db_fetch_object(db_query('SELECT * FROM {comments} WHERE cid = %d', $edit['pid']));

        // Strip the "/" from the end of the parent thread.
        $parent->thread = (string) rtrim((string) $parent->thread, '/');

        // Get the max value in _this_ thread.
        $max = db_result(db_query("SELECT MAX(thread) FROM {comments} WHERE thread LIKE '%s.%%' AND nid = %d", $parent->thread, $edit['nid']));

        if ($max == '') {
            // First child of this parent.
            $thread = $parent->thread .'.1/';
        }
        else {
            // Strip the "/" at the end of the thread.
            $max = rtrim($max, '/');

            // We need to get the value at the correct depth.
            $parts = explode('.', $max);
            $parent_depth = count(explode('.', $parent->thread));
            $last = $parts[$parent_depth];

            // Next, we increase this value by one.  Note that we can't
            // use 1, 2, 3, ... 9, 10, 11 because we order by string and
            // 10 would be right after 1.  We use 1, 2, 3, ..., 9, 91,
            // 92, 93, ... instead.  Ugly but fast.
            $decimals = (string)substr($last, 0, strlen($last) - 1);
            $units = substr($last, -1, 1);
            $units++;
            if ($units == 10) {
                $units = '90';
            }

            // Finally, build the thread field for this new comment.
            $thread = $parent->thread .'.'. $decimals . $units .'/';
        } 
    }

    $edit['cid'] = db_next_id('{comments}_cid');

    $status = 0;                        // 1 - not published, 0 - published
    $format = 1;                        // 1 - filtered, 2 - PHP, 3 FULL HTML
    $score = 0;                         // 0 default value, comments get higher score depending on the author's roles
    $users = serialize(array(0 => 1));  // default value for everybody!!

    db_query("INSERT INTO {comments} (cid, nid, pid, uid, subject, comment, format, hostname, timestamp, status, score, users, thread, name, mail, homepage) VALUES (%d, %d, %d, %d, '%s', '%s', %d, '%s', %d, %d, %d, '%s', '%s', '%s', '%s', '%s')", $edit['cid'], $edit['nid'], $edit['pid'], $edit['uid'], $edit['subject'], $edit['comment'], $format, $edit['hostname'], $edit['timestamp'], $status, $score, $users, $thread, $edit['name'], $edit['mail'], $edit['homepage']);

    _comment_update_node_statistics($edit['nid']);

    return $edit['cid'];
}

// function for inserting polls into drupal
function phpbb2_drupal_poll_insert($node) {
    if (!user_access('administer nodes')) {
        // Make sure all votes are 0 initially
        foreach ($node->choice as $i => $choice) {
            $node->choice[$i]['chvotes'] = 0;
        }
        $node->active = 1;
    }

    db_query("INSERT INTO {poll} (nid, runtime, polled, active) VALUES (%d, %d, '', %d)", $node->nid, $node->runtime, $node->active);

    foreach ($node->choice as $choice) {
        if ($choice['chtext'] != '') {
            db_query("INSERT INTO {poll_choices} (nid, chtext, chvotes, chorder) VALUES (%d, '%s', %d, %d)", $node->nid, $choice['chtext'], $choice['chvotes'], $i++);
        }
    }
} 

// PHPBB function for decoding the user ip
function phpbb2drupal_decode_ip($int_ip) {
  $hexipbang = explode('.', chunk_split($int_ip, 2, '.'));
  return hexdec($hexipbang[0]). '.' . hexdec($hexipbang[1]) . '.' . hexdec($hexipbang[2]) . '.' . hexdec($hexipbang[3]);
}

function phpbb2drupal_page_header($title) {
  $output = "<html><head><title>$title</title>";
  $output .= <<<EOF
      <link rel="stylesheet" type="text/css" media="print" href="misc/print.css" />
      <style type="text/css" title="layout" media="Screen">
        @import url("misc/drupal.css");
      </style>
EOF;
  $output .= "</head><body>";
  $output .= "<div id=\"logo\"><a href=\"http://drupal.org/\"><img src=\"misc/druplicon-small.png\" alt=\"Druplicon - Drupal logo\" title=\"Druplicon - Drupal logo\" /></a></div>";
  $output .= "<div id=\"update\"><h1>$title</h1>";
  return $output;
}

function phpbb2drupal_page_footer() {
  return "</div></body></html>";
}
?>

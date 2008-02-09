<?php
// $Id$

/**
 * Callback admin/settings/phpbb2drupal
 */
function phpbb2drupal_admin_settings() {
  global $conf ;
  global $db_url;
  $stage = variable_get('phpbb2drupal_config_stage', 0); 

  //We'll be checking that the setup is complete.
  //If it is not complete, the migration will not go ahead.
  $ready_for_migration = 1;

  // Checking that the migration has been tested on a copy first.
  $form['test'] = array(
    '#type' => 'fieldset',
    '#title' => t('Test on copy first'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
    
  $output = '';
  if (!variable_get('phpbb2drupal_tested', 0)) {
    $form['test']['#collapsed']= FALSE;
    $output = '<span class="marker">';
    $ready_for_migration = 0;
  }
  $output .= t('<p>The migration will modify and even destroy some data. You must test this module on a copy of your data first, several times if necessary. Only use this module on your live data when you are familiar with the procedure and you\'re confident everything will run smoothly.</p>
  <p>Also, you should make a full backup of your database just prior to permorming the migration on the live data. It is better to turn off both the phpBB board and the Drupal site during the migration.</p>');
  if (!variable_get('phpbb2drupal_tested', 0)) {
    $output .= '</span>';
  }
  $form['test']['intro'] = array('#value' => $output);
  $output = '';

  $form['test']['phpbb2drupal_tested'] = array(
    '#type' => 'checkbox',
    '#title' => t('I am running a trial migration on a disposable copy of the database OR I have already done so and I am running this on my live site, confident that the migration will proceed smoothly'),
    '#return_value' => 1,
    '#default_value' => variable_get('phpbb2drupal_tested', 0),
    '#description' => '',
  ); 

  // Input format settings
  $form['input'] = array(
    '#type' => 'fieldset',
    '#title' => t('Input Format settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $output = '<p>'. t('Select which <a href="@link">input format</a> should the imported messages be set:', array('@link' => url('admin/filters'))) .'</p>';
  $form['input']['intro'] = array('#value' => $output);
  $output = '';

  $input_formats = array(0 => t('not set yet'));
  $sql = 'SELECT format, name FROM {filter_formats}';
  $result = db_query($sql);
  while ($format = db_fetch_array($result)) {
    $input_formats[$format['format']]= $format['name'];
  }
  $input_format = variable_get('phpbb2drupal_input_format', 0);
  $form['input']['phpbb2drupal_input_format'] = array(
    '#type' => 'radios',
    '#title' => t('Input format'),
    '#default_value' => $input_format,
    '#options' => $input_formats,
  );
  if (!$input_format) {
    $ready_for_migration = 0;
    $form['input']['#collapsed']= FALSE;
  }
  $output .= '<p><em>'. t('This setting will be applied to all posts and private messages.') .'</em></p>';
  $form['input']['result'] = array('#value' => $output);


  // BBcode settings
  $form['bbcode'] = array(
    '#type' => 'fieldset',
    '#title' => t('BBcode settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $output = t('<p>pbpBB2 posts are often formatted in BBcode. During the import process, BBcode can be handled in two diffent ways:</p>
    <ul><li>You can leave the BBcode as is, and use the <a href="http://drupal.org/project/bbcode" target="_blank">bbcode module</a> after the migration to filter the imported posts. In this case, the module is not required during the migration but you will need to install it and configure it later.</li>
    <li>You can decide to have the BBcode filtered now and replaced by plain HTML. The BBcode module will be required during the migration process, but you can disable it later.</li>    </ul>
    <p>Which one do you choose?</p>
    <p>Also, make sure to choose the proper input filter, for it will affect how the post is displayed. For example, if you choose to strip the bbcode altogether and use HTML instead, some HTML tags will be stripped by the "filtered HTML" input format, unless you add those tags in the list of allowed tags. Using "Full HTML" is recommanded for the imported posts when doing away with bbcode.</p>');
  $form['bbcode']['intro'] = array('#value' => $output);
  $form['bbcode']['phpbb2drupal_bbcode'] = array(
    '#type' => 'radios',
    '#title' => t('BBcode handling'),
    '#default_value' => variable_get('phpbb2drupal_bbcode', 0),
    '#options' => array(t('Leave BBcode as is.'), t('Transform BBcode into HTML.')),
    '#description' => '',
  );
  $output = '';
  $result7 = _phpbb2drupal_check_module('bbcode');
  $output .= $result7['html'];
  $link_input_format_enable = url('admin/filters/'. $input_format);
  $link_input_format_configure = url('admin/filters/'. $input_format .'/configure');
  if (variable_get('phpbb2drupal_bbcode', 0)) { 
    if ($result7['result'] == 0) {
      $ready_for_migration = 0;
      $form['bbcode']['#collapsed']= FALSE;
      $output .= '<p><span class="marker">';
      $output .= t('<a href="http://drupal.org/project/bbcode" target="_blank">The bbcode module</a> is required to transform BBcode into HTML. 
        You first need to <a href="@adminmodules">enable the bbcode module</a>', array('@adminmodules' => url('admin/modules')));
      $output .= '</span></p>';
    }
    $sql = 'SELECT COUNT(*) FROM {filters} WHERE module = \'bbcode\' AND format = %d';
    $result = db_query($sql, $input_format);
    if (!db_result($result)) {
      $form['bbcode']['#collapsed']= FALSE;
      $output .= '<p class="marker">'. t('You need to <a href="@formatenable">enable bbcode in the selected input format</a> <a href="@formatconfigure">THEN click on configure to see bbcode options</a>', array('@formatenable' => $link_input_format_enable, '@formatconfigure' => $link_input_format_configure)) .'</p>';
    }
    $output .= '<p>'. t('For best results, <a href="@bbcodeconfig">all four BBcode configure options should be disabled</a>.', array('@bbcodeconfig' => url('admin/filters/'. $input_format .'/configure'))) .'</p>';
    
  }
  else {
    if ($result7['result'] == 0) {
      $form['bbcode']['#collapsed']= FALSE;
      $output .= '<p>'. t('<a href="http://drupal.org/project/bbcode" target="_blank">The bbcode module</a> is only recommanded. You may <a href="@adminmodules">enable the bbcode module</a> after having installed it.', array('@adminmodules' => url('admin/modules'))) .'</p>';
    }
    $output .= t('<p>Don\'t forget to <a href="@formatenable">enable bbcode in the selected input format</a> (admin > input formats > configure) and enable bbcode for the format selected for the imported posts.  <a href="@formatconfigure">Then click on configure to see bbcode options</a></p>
      <p>You may want to enable it for other input formats, too.</p>', array('@formatenable' => $link_input_format_enable, '@formatconfigure' => $link_input_format_configure));
  }
  $output .= '<p><em>'. t('This setting will be applied to all posts, private messages and the users\' signature.') .'</em></p>';
  $form['bbcode']['result'] = array('#value' => $output);

  

  // Drupal Modules settings
  $form['modules'] = array(
    '#type' => 'fieldset',
    '#title' => t('Drupal modules'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $output = '<p>';
  $result1 = _phpbb2drupal_check_module('forum');
  $output .= $result1['html'];
  $result2 = _phpbb2drupal_check_module('node');
  $output .= $result2['html'];
  $result3 = _phpbb2drupal_check_module('comment');
  $output .= $result3['html'];
  $result4 = _phpbb2drupal_check_module('user');
  $output .= $result4['html'];
  $result5 = _phpbb2drupal_check_module('profile');
  $output .= $result5['html'];
  $result6 = _phpbb2drupal_check_module('taxonomy');
  $output .= $result6['html'];
  $output .= '</p>';

  if ($result1['result'] == 0 || $result2['result'] == 0 || $result3['result'] == 0 || $result4['result'] == 0 || $result5['result'] == 0 || $result6['result'] == 0) {
    $ready_for_migration = 0;
    $form['modules']['#collapsed']= FALSE;
    $output .= '<p class="marker">';
    $output .= t('Some of the modules are not enabled. <a href="@adminmodules">Got to the admin/modules page to enable them</a>', array('@adminmodules' => url('admin/modules')));
    $output .= '</p>';
  }
  $form['modules']['result'] = array('#value' => $output);
  $output = '';

  // Where is phpbb2?
  $form['phpbb2'] = array(
    '#type' => 'fieldset',
    '#title' => t('Location of phpBB2 data'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['phpbb2']['phpbb2drupal_same_db'] = array(
    '#type' => 'checkbox',
    '#title' => t('phpbb is on the same database as drupal'),
    '#return_value' => 1,
    '#default_value' => variable_get('phpbb2drupal_same_db', 1),
    '#description' => '',
  );
  if (variable_get('phpbb2drupal_same_db', 1) != 1) {
    $url = variable_get('phpbb2drupal_db_url', $db_url);
    if ($url == $db_url) {
      $output = '<p class="marker">'. t('Set the address of the phpBB database according to the following format: <br /><pre>mysql://phpbb_username:phpbb_password@localhost/phpbb_database</pre>') .'</p>';
      $ready_for_migration = 0;
      $form['phpbb2']['#collapsed']= FALSE;
    }

    $form['phpbb2']['phpbb2drupal_db_url'] = array(
      '#type' => 'textfield',
      '#title' => t('phpBB2 database location'),
      '#default_value' => variable_get('phpbb2drupal_db_url', $db_url),
      '#size' => 80,
      '#maxlength' => 255,
      '#description' => '',
    );
  }

  $output = t('<p>Note that due to a limitation within Drupal core, we cannot import messages from one database type to another database type. E.g, we cannot import phpBB from a myqsl DB, to a postgresql DB. You need to import the data into the same type of database first (import all the phpBB data, as is, from mysql to postgresql.</p>
    <p>If both phpBB and Drupal are on postgresql, see <a href="http://drupal.org/node/64877">this issue</a>.</p>');
  $form['phpbb2']['note'] = array('#value' => $output);
  
  // phpBB2 table prefix
  $output = '';
  $form['phpbb2']['phpbb2drupal_table_prefix'] = array(
    '#type' => 'textfield',
    '#title' => t('phpBB2 table prefix'),
    '#default_value' => variable_get('phpbb2drupal_table_prefix', 'phpbb_'),
    '#size' => 20,
    '#maxlength' => 255,
    '#description' => t('What prefix is used for your phpBB2 tables?'),
  );
  $pre = variable_get('phpbb2drupal_table_prefix', 'phpbb_');
  $tables = array($pre .'categories', $pre .'forums', $pre .'posts', $pre .'posts_text', $pre .'topics', $pre .'users');
  $result = _phpbb2drupal_check_tables($tables, 'phpbb', 0);
  $output .= $result['html'];
  
  if ($result['result'] != 1) {
    $ready_for_migration = 0;
    $form['phpbb2']['#collapsed']= FALSE;
    $output .= '<span class="marker">';
    $output .= t("Some tables couldn't be found. Please correct the errors so that the phpBB2 tables can be accessed.");
    $output .= '</span>';
  }
  $form['phpbb2']['result'] = array('#value' => $output);


  
  // Misc.	
  $form['misc'] = array(
    '#type' => 'fieldset',
    '#title' => t('Misc. settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  // Time limit 
  $output = '';
  //Registration date format
  $form['misc']['phpbb2drupal_regdate_us_english'] = array(
    '#type' => 'checkbox',
    '#title' => t('Convert Registration Date'),
    '#default_value' => variable_get('phpbb2drupal_regdate_us_english', 0),
    '#return_value' => 1,
    '#description' => t('If you find out that the registration date for ALL your users is over 36 years ago, maybe your DB stores the registration date in the English US format (like: "Sep 11, 2001") instead as a linux timestamp (like: "1152568884"). If so, check this box.'),
  );
  $form['misc']['phpbb2drupal_time_limit'] = array(
    '#type' => 'textfield',
    '#title' => t('Time limit'),
    '#default_value' => variable_get('phpbb2drupal_time_limit', 1200),
    '#size' => 7,
    '#maxlength' => 10,
    '#description' => t('How long, in seconds, will the migration take?'),
  );
  $form['misc']['info'] = array(
    '#value' => '<p>'. t('1200 = 20 minutes should be enough for most boards. It worked well for a board with 300,000 rows. Beware: if your site is cohosted and safe-mode is on, then this value will have no effect.') .'</p>',
  );
  
  // USERS settings
  $form['users'] = array(
    '#type' => 'fieldset',
    '#title' => t('User import'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  // User to map admin user too
  $drupal_admin = user_load(array('uid' => 1));
  $form['users']['phpbb2drupal_admin_user'] = array(
    '#title' => t('User to map phpBB admin'),
    '#type' => 'textfield',
    '#autocomplete_path' => 'user/autocomplete',
    '#default_value' => variable_get('phpbb2drupal_admin_user', $drupal_admin->name ),
  );
  

  // Import users who have never posted?
  $form['users']['phpbb2drupal_import_spammers'] = array(
    '#type' => 'checkbox',
    '#title' => t('Import users who have never posted?'),
    '#return_value' => 1,
    '#default_value' => variable_get('phpbb2drupal_import_spammers', 1),
    '#description' => '',
  );
 
  //Polls
  $form['polls'] = array(
    '#type' => 'fieldset',
    '#title' => t('Polls import'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['polls']['phpbb2drupal_import_polls'] = array(
    '#type' => 'checkbox',
    '#title' => t('Import polls?'),
    '#return_value' => 1,
    '#default_value' => variable_get('phpbb2drupal_import_polls', 0),
    '#description' => '',
  );
  if (variable_get('phpbb2drupal_import_polls', 0) == 1) {
    //poll modules
    $result = _phpbb2drupal_check_module('poll');
    $output .= $result['html'];
    if ($result['result'] != 1) {
      $ready_for_migration = 0;
      $form['polls']['#collapsed'] = FALSE;
      $output .= '<span class="marker">';
      $output .= t('Please enable the poll module.') ;
      $output .= '</span>';
    }
    $form['polls']['info'] = array('#value' => $output);
  }
  
 
  //Attachments
  $output ='';
  $form['attachments'] = array(
    '#type' => 'fieldset',
    '#title' => t('Attachments import'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['attachments']['phpbb2drupal_import_attachments'] = array(
    '#type' => 'checkbox',
    '#title' => t('Import attachments?'),
    '#return_value' => 1,
    '#default_value' => variable_get('phpbb2drupal_import_attachments', 0),
    '#description' => '',
  );
  if (variable_get('phpbb2drupal_import_attachments', 0) == 1) {
    // Comment attachments module
    $result = _phpbb2drupal_check_module('comment_upload');
    $output .= $result['html'];
    if ($result['result'] != 1) {
      $ready_for_migration = 0;
      $form['attachments']['#collapsed']= FALSE;
      $output .= '<span class="marker">';
      $output .= t('The comment_upload.module is not enabled. You can <a href="http://drupal.org/node/37197" target="_blank">go to the module Home Page to download it</a>. <a href="@adminmodules">After having installed it, go to the admin/module page to enable it</a>', array('@adminmodules' => url('admin/modules')));
      $output .= '</span>';
    }
  }
  
  // Encoding
  $form['encoding'] = array(
    '#type' => 'fieldset',
    '#title' => 'Encoding setting',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  //check if the encoding functions are available.
  if (variable_get('phpbb2drupal_encode', '1')) {
    if (function_exists('iconv') || function_exists('mb_convert_encoding') || function_exists('recode_string')) {
    } 
    else {
      $ready_for_migration = 0;
      $form['encoding']['#collapsed']= FALSE;
      $output .= '<p class="marker">';
      $output .= t("The php function iconv is not available on this server: the posts' encoding cannot be changed during the migration.");
      $output .= '</p>';
    }	
  }
  
  $encodings = array(
    'utf-8' => 'utf-8',
    'iso-8859-1' => 'iso-8859-1',
    'UCS-4' => 'UCS-4',
    'UCS-4BE' => 'UCS-4BE',
    'UCS-4LE' => 'UCS-4LE',
    'UCS-2' => 'UCS-2',
    'UCS-2BE' => 'UCS-2BE',
    'UCS-2LE' => 'UCS-2LE',
    'UTF-32' => 'UTF-32',
    'UTF-32BE' => 'UTF-32BE',
    'UTF-32LE' => 'UTF-32LE',
    'UTF-16' => 'UTF-16',
    'UTF-16BE' => 'UTF-16BE',
    'UTF-16LE' => 'UTF-16LE',
    'UTF-7' => 'UTF-7',
    'UTF7-IMAP' => 'UTF7-IMAP',
    'ASCII' => 'ASCII',
    'EUC-JP' => 'EUC-JP',
    'SJIS' => 'SJIS',
    'eucJP-win' => 'eucJP-win',
    'SJIS-win' => 'SJIS-win',
    'ISO-2022-JP' => 'ISO-2022-JP',
    'JIS' => 'JIS',
    'ISO-8859-2' => 'ISO-8859-2',
    'ISO-8859-3' => 'ISO-8859-3',
    'ISO-8859-4' => 'ISO-8859-4',
    'ISO-8859-5' => 'ISO-8859-5',
    'ISO-8859-6' => 'ISO-8859-6',
    'ISO-8859-7' => 'ISO-8859-7',
    'ISO-8859-8' => 'ISO-8859-8',
    'ISO-8859-9' => 'ISO-8859-9',
    'ISO-8859-10' => 'ISO-8859-10',
    'ISO-8859-13' => 'ISO-8859-13',
    'ISO-8859-14' => 'ISO-8859-14',
    'ISO-8859-15' => 'ISO-8859-15',
    'byte2be' => 'byte2be',
    'byte2le' => 'byte2le',
    'byte4be' => 'byte4be',
    'byte4le' => 'byte4le',
    'BASE64' => 'BASE64',
    'HTML-ENTITIES' => 'HTML-ENTITIES',
    '7bit' => '7bit',
    '8bit' => '8bit',
    'EUC-CN' => 'EUC-CN',
    'CP936' => 'CP936',
    'HZ' => 'HZ',
    'EUC-TW' => 'EUC-TW',
    'CP950' => 'CP950',
    'BIG-5' => 'BIG-5',
    'EUC-KR' => 'EUC-KR',
    'UHC (CP949)' => 'UHC (CP949)',
    'ISO-2022-KR' => 'ISO-2022-KR',
    'Windows-1251 (CP1251)' => 'Windows-1251 (CP1251)',
    'Windows-1252 (CP1252)' => 'Windows-1252 (CP1252)',
    'Windows-1256 (CP1256)' => 'Windows-1256 (CP1256)',
    'CP866 (IBM866)' => 'CP866 (IBM866)',
    'KOI8-R' => 'KOI8-R',
    );
  
  $form['encoding']['phpbb2drupal_encode'] = array(
    '#type' => 'checkbox',
    '#title' => t('Change encoding of text strings during the import'),
    '#default_value' => variable_get('phpbb2drupal_encode', '1'),
  );

  $output .= '<p>'. t('This setting is useful when the phpBB2 encoding is not the same as the Drupal encoding.') .'</p>';
  $form['encoding']['info'] = array(
    '#value' => $output 
  );
  
  $form['encoding']['phpbb2drupal_encoding_phpbb'] = array(
    '#type' => 'select',
    '#title' => t('phpBB2 encoding'),
    '#default_value' => variable_get('phpbb2drupal_encoding_phpbb', 'iso-8859-1'),
    '#options' => $encodings,
  );

  // Are we ready for migration?
  $form['migration'] = array(
    '#type' => 'fieldset',
    '#title' => 'Migration',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  variable_set('phpbb2drupal_ready', $ready_for_migration);
  if ($ready_for_migration) {
    $form['migration']['#collapsed']= FALSE;
    $output .= '<p>'. t('Check again all the settings above, and if everything is ok, <a href="@migratelink">you can now proceed with the migration</a>', array('@migratelink' => url('admin/phpbb2drupal/migrate'))) .'</p>';
  }
  else {
    $output = '<p class="marker">'. t('You are not ready for the migration yet. Check the settings above.') .'</p>';
  }
  
  $form['migration']['info'] = array('#value' => $output);
  return system_settings_form($form); 
  
}

/**
 * Callback admin/phpbb2drupal/postconfiguration
 */
function phpbb2drupal_postconfiguration() {
  $output = '<p>'. t('This page will help guide you set up the forums. Before continuing it may be best to have already:') .'</p>';
  $output .= '<ul>';
  $output .= '<li>'. t('Imported users, forums, topics and optionally polls using the phpbb2drupal Module') .'</li>';
  $output .= '<li>'. t('Imported any additional data using add on modules if available.') .'</li>';
  $output .= '</ul>';
  //User Set up
  $output .= '<h2>'. t('User Settings') .'</h2>';  
  $output .= '<p>'. t('Make sure your <a href="@usersettings">user settings</a> are correct. here you can enable or disable signature. In order to activate avatar/picture support, you need to enable pictures on this page and also:', array('@usersettings' => url('admin/user/settings'))) .'</p>';  
  $output .= '<ul>';
  $output .= '<li>'. t('Tick the checkboxes to allow "User pictures in posts" and/or "User pictures in comments" on the <a href="@themeconfig">themes configuration</a> page.', array('@themeconfig' => url('admin/build/themes/settings'))) .'</li>';
  $output .= '<li>'. t('Manually copy the avatars from the phpbb2/images/avatars/ directory to the @files directory.', array('@files' => file_directory_path(). '/pictures')) .'</li>';
  $output .= '</ul>';
  $output .= '<p>'. t('Currently, the module does not block imported users who had been banned in phpBB. This needs to be done manually from the <a href="@userlist">users listing</a> page.', array('@userlist' => url('admin/user/user'))) .'</p>';
  $output .= '<p>'. t('User blocking in core is simple and does not have all the same features as phpBB. For a similar featureset, consider using the <a href="http://www.drupal.org/project/troll" target="_blank">Troll module</a>.') .'</p>';
  // Forum Set up
  $output .= '<h2>'. t('Forum Settings') .'</h2>';
  $output .= '<p>'. t('If you imported polls, you need to allow polls to show in the forums. On <a href="@forumvocab">this page</a> tick the checkbox next to the "polls" content type to allow polls to display in forums. Addidtionally check any additional content types you want to allow in the forums.',
      array('@forumvocab' => url('admin/content/taxonomy/edit/vocabulary/' .variable_get('forum_nav_vocabulary', 0)))) .'</p>';
  $output .= '<p>'. t('If need be, set up the private forums as explained in the <a href="http://drupal.org/node/58969" target="_blank">documentation</a>.') .'</p>';

  // Redirection from old URL:
  $output .= '<h2>'. t('Redirection from phpBB2 forum URL to new Drupal forum URL') .'</h2>';
  $output .= '<p>'. t('Enable the phpbb_redirect.module. This way, users accessing URLs from your old phpBB2 installation will be redirected to your new Drupal forum. (note that currently the module assumes that the phpbb forum was located in the forum/ subdirectory of your Drupal installation).') .'</p>';

  // BBcode
  $bbcode = variable_get('phpbb2drupal_bbcode', 0);
  if ($bbcode) {
    $output .= '<h2>'. t('Remove BBcode module') .'</h2>';
    $output .= '<p>'. t("The bbcode has been cleaned from the posts. You shouldn't need the bbcode module anymore. You can disable it.") .'</p>';
  }
  else {
    $output .= '<h2>'. t('Configure BBcode module') .'</h2>';
    $output .= '<p>'. t('Most probably, there are many phpBB forum postings formatted in BBcode. During the import procedure, the BBcode has been cleaned so that it can work with the Drupal bbcode.module. Please install and configure the <a href="http://drupal.org/project/bbcode" target="_blank">BBcode module</a> so that the forum topics display properly. See the BBcode module documentation for details.') .'</p>';
  }

  return $output;
}

/**
 * Callback admin/phpbb2drupal
 */
function phpbb2drupal_main() {
  $output = '<ol><li>';
  $output .= t('You may want to <a href="http://drupal.org/node/58199" target="_blank">check the documentation</a> before starting.');
  $output .= '</li>';
  $output .= '<li>';
  $output .= l(t('Configure the migration'), 'admin/settings/phpbb2drupal');
  $output .= '</li>';
  $output .= '<li>';
  $output .= l(t('Execute the migration'), 'admin/phpbb2drupal/migrate');
  $output .= '</li>';
  $output .= '<li>';
  $output .= l(t('Post migration configuration'), 'admin/phpbb2drupal/postconfiguration');
  $output .= '</li>';
  $output .= '<li>';
  $output .= t('Import additional data using other modules. Private Messages can be imported using the bundled phpbb2privatemsg module.');
  $output .= '</li>';
  $output .= '<li>';
  $output .= t('<a href="@cleanuplink">Cleanup</a> (Accessing this link will delete all the saved phpbb2drupal settings and temporary tables: click only when you\'re done with all the above.)', array('@cleanuplink' => url('admin/phpbb2drupal/cleanup')));
  $output .= '</ol>';

  // process will die() if the link to the phpBB2 database is wrong:
  $output .= '<p>'. t('If the phpBB2 data is in another data base, but you made a mistake while setting up the data base url, you may be unable to access the setting page due to some limitations of the core of Drupal. <a href="@reseturl">Click here to reset the database url</a>.',
      array('@reseturl' => url('admin/phpbb2drupal/reset'))) .'</p>';

  return $output;
}
<?php

/**
 * Callback admin/settings/phpbb2drupal
 */
function phpbb2drupal_admin_settings($form, &$form_state) {
  global $conf;
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
    $form['test']['#collapsed'] = FALSE;
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

  // Where is phpbb data?
  $form['phpbb_db'] = array(
    '#type' => 'fieldset',
    '#title' => t('Location of phpBB data'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['phpbb_db']['phpbb2drupal_same_db'] = array(
    '#type' => 'checkbox',
    '#title' => t('phpbb is on the same database as drupal'),
    '#return_value' => 1,
    '#default_value' => variable_get('phpbb2drupal_same_db', 1),
    '#description' => '',
  );
  if (variable_get('phpbb2drupal_same_db', 1) != 1) {
  
  // Get the default database so that its values can in entered as the default values.
  global $databases;
  
    $form['phpbb_db']['phpbb2drupal_db_driver'] = array(
      '#type' => 'textfield',
      '#title' => t('Driver'),
      '#default_value' => variable_get('phpbb2drupal_db_driver', $databases['default']['default']['driver']),
      '#size' => 20,
      '#maxlength' => 255,
      '#description' => 'the phpBB database driver (examples: "mysql", "pgsql", "sqlite").',
    );
    $form['phpbb_db']['phpbb2drupal_db_database'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the phpBB database'),
      '#default_value' => variable_get('phpbb2drupal_db_database', $databases['default']['default']['database']),
      '#size' => 20,
      '#maxlength' => 255,
      '#description' => 'the name of the phpBB database',
    );
    $form['phpbb_db']['phpbb2drupal_db_username'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#default_value' => variable_get('phpbb2drupal_db_username', $databases['default']['default']['username']),
      '#size' => 20,
      '#maxlength' => 255,
      '#description' => 'the username to access the phpBB database',
    );
    $form['phpbb_db']['phpbb2drupal_db_password'] = array(
      '#type' => 'textfield',
      '#title' => t('Password'),
      '#default_value' => variable_get('phpbb2drupal_db_pass', $databases['default']['default']['password']),
      '#size' => 20,
      '#maxlength' => 255,
      '#description' => 'the password the phpBB database',
    );
    $form['phpbb_db']['phpbb2drupal_db_host'] = array(
      '#type' => 'textfield',
      '#title' => t('Hostname'),
      '#default_value' => variable_get('phpbb2drupal_db_host', $databases['default']['default']['host']),
      '#size' => 20,
      '#maxlength' => 255,
      '#description' => 'the hostname for the phpBB database.',
    );
  }

  // phpBB table prefix
  $output = '';
  $form['phpbb_db']['phpbb2drupal_table_prefix'] = array(
    '#type' => 'textfield',
    '#title' => t('phpBB table prefix'),
    '#default_value' => variable_get('phpbb2drupal_table_prefix', 'phpbb_'),
    '#size' => 20,
    '#maxlength' => 255,
    '#description' => t('What prefix is used for your phpBB2 tables?'),
  );
  $pre = variable_get('phpbb2drupal_table_prefix', 'phpbb_');

  $tables = array('forums', 'posts', 'topics', 'users');

  $result = _phpbb2drupal_check_tables($tables, 'phpbb');
  $output .= $result['html'];

  if ($result['result'] != 1) {
    $ready_for_migration = 0;
    $form['phpbb_db']['#collapsed'] = FALSE;
    $output .= '<span class="marker">';
    $output .= t("Some tables couldn't be found. This can be caused by an incorrect prefix or if your phpbb version is incorrectly set. Please correct the errors so that the phpBB tables can be accessed.");
    $output .= '</span>';
  }

  $form['phpbb_db']['result'] = array('#markup' => $output);

  // Misc.	
  $form['time'] = array(
    '#type' => 'fieldset',
    '#title' => t('php Data Import Time limit'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  // Time limit
  $output = '';
  $form['time']['phpbb2drupal_time_limit'] = array(
    '#type' => 'textfield',
    '#title' => t('Time limit'),
    '#default_value' => variable_get('phpbb2drupal_time_limit', 1200),
    '#size' => 7,
    '#maxlength' => 10,
    '#description' => t('How long, in seconds, will the migration take?'),
  );
  $form['time']['info'] = array(
    '#markup' => '<p>' . t('1200 = 20 minutes should be enough for most boards. It worked well for a board with 300,000 rows. Beware: if your site is cohosted and safe-mode is on, then this value will have no effect.') . '</p>',
  );

  // USERS settings
  $form['users'] = array(
    '#type' => 'fieldset',
    '#title' => t('User import'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  // User to map admin user too
  $drupal_admin = user_load(1);
  $form['users']['phpbb2drupal_admin_user'] = array(
    '#title' => t('Drupal user to map phpBB admin to'),
    '#type' => 'textfield',
    '#autocomplete_path' => 'user/autocomplete',
    '#default_value' => variable_get('phpbb2drupal_admin_user', $drupal_admin->name ),
  );

  //Registration date format
  $form['users']['phpbb2drupal_regdate_us_english'] = array(
    '#type' => 'checkbox',
    '#title' => t('Convert Registration Date'),
    '#default_value' => variable_get('phpbb2drupal_regdate_us_english', 0),
    '#return_value' => 1,
    '#description' => t('If you find out that the registration date for ALL your users is over 36 years ago, maybe your DB stores the registration date in the English US format (like: "Sep 11, 2001") instead as a linux timestamp (like: "1152568884"). If so, check this box.'),
  );

  // Import users who have never posted?
  $form['users']['phpbb2drupal_import_spammers'] = array(
    '#type' => 'checkbox',
    '#title' => t('Import users who have never posted?'),
    '#return_value' => 1,
    '#default_value' => variable_get('phpbb2drupal_import_spammers', 1),
    '#description' => 'Do you want to import with zero posts? If the phpBB forums had a problem with spam accounts, it may be beneficial to not import any users with zero posts.',
  );

  // Input format settings
  $form['input'] = array(
    '#type' => 'fieldset',
    '#title' => t('Input Format settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $output = '<p>' . t('Select which <a href="@link">input format</a> should the imported messages be set:', array('@link' => url('admin/config/content/formats'))) . '</p>';
  $form['input']['intro'] = array('#markup' => $output);
  $output = '';

  $input_formats = array(0 => t('not set yet'));
  $sql = 'SELECT format, name FROM {filter_format}';
  $result = db_query($sql);
  foreach ($result as $format) {
    $input_formats[$format->format] = $format->name;
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
    $form['input']['#collapsed'] = FALSE;
  }
  $output .= '<p><em>' . t('This setting will be applied to all posts and private messages.') . '</em></p>';
  $form['input']['result'] = array('#markup' => $output);


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
  $link_input_format_enable = url('admin/config/content/formats/' . $input_format);
  $link_input_format_configure = url('admin/config/content/formats/' . $input_format . '/configure');
  if (variable_get('phpbb2drupal_bbcode', 0)) {
    if ($result7['result'] == 0) {
      $ready_for_migration = 0;
      $form['bbcode']['#collapsed'] = FALSE;
      $output .= '<p><span class="marker">';
      $output .= t('<a href="http://drupal.org/project/bbcode" target="_blank">The bbcode module</a> is required to transform BBcode into HTML.
        You first need to <a href="@adminmodules">enable the bbcode module</a>', array('@adminmodules' => url('admin/modules')));
      $output .= '</span></p>';
    }
    $result = db_query('SELECT COUNT(*) FROM {filters} WHERE module = :module AND format = :format', array(':module' => 'bbcode', ':format' => $input_format,));
    if (!$result->fetchField()) {
      $form['bbcode']['#collapsed'] = FALSE;
      $output .= '<p class="marker">' . t('You need to <a href="@formatenable">enable bbcode in the selected input format</a> <a href="@formatconfigure">THEN click on configure to see bbcode options</a>', array('@formatenable' => $link_input_format_enable, '@formatconfigure' => $link_input_format_configure)) . '</p>';
    }
    $output .= '<p>' . t('For best results, <a href="@bbcodeconfig">all four BBcode configure options should be disabled</a>.', array('@bbcodeconfig' => url('admin/config/content/formats/' . $input_format . '/configure'))) . '</p>';

  }
  else {
    if ($result7['result'] == 0) {
      $form['bbcode']['#collapsed'] = FALSE;
      $output .= '<p>' . t('<a href="http://drupal.org/project/bbcode" target="_blank">The bbcode module</a> is only recommended. You may <a href="@adminmodules">enable the bbcode module</a> after having installed it.', array('@adminmodules' => url('admin/modules'))) . '</p>';
    }
    $output .= t('<p>Don\'t forget to <a href="@formatenable">enable bbcode in the selected input format</a> (admin > config > text formats) and enable bbcode for the format selected for the imported posts.  <a href="@formatconfigure">Then click on configure to see bbcode options</a></p>
      <p>You may want to enable it for other input formats, too.</p>', array('@formatenable' => $link_input_format_enable, '@formatconfigure' => $link_input_format_configure));
  }
  $output .= '<p><em>' . t('This setting will be applied to all posts, private messages and the users\' signature.') . '</em></p>';
  $form['bbcode']['result'] = array('#markup' => $output);

  //Attachments
  $output = '';
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
    // Make sure that File module is enabled.
    //TODO: Add a check to see if a file field is enabled on the forum node and on comments and select the correct one.
    $result = _phpbb2drupal_check_module('file');
    $output .= $result['html'];
    if ($result['result'] != 1) {
      $ready_for_migration = 0;
      $form['attachments']['#collapsed'] = FALSE;
      $output .= '<span class="marker">';
      $output .= t('The file module is not enabled. You can go to <a href="@adminmodules">admin/modules</a> to enable this module.', array('@adminmodules' => url('admin/modules')));
      $output .= '</span>';
    }
    $form['attachments']['result'] = array('#markup' => $output);
  }

  // Are we ready for migration?
  $form['migration'] = array(
    '#type' => 'fieldset',
    '#title' => 'Migration',
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  variable_set('phpbb2drupal_ready', $ready_for_migration);
  if ($ready_for_migration) {
    $output = '<p>' . t('Check again all the settings above, and if everything is ok, <a href="@migratelink">you can now proceed with the migration</a>', array('@migratelink' => url('admin/phpbb2drupal/migrate'))) . '</p>';
  }
  else {
    $output = '<p class="marker">' . t('You are not ready for the migration yet. Check the settings above.') . '</p>';
  }

  $form['migration']['info'] = array('#markup' => $output);
  return system_settings_form($form);

}

/**
 * Callback admin/phpbb2drupal/postconfiguration
 */
function phpbb2drupal_postconfiguration() {
  $output = '<p>' . t('This page will help guide you set up the forums. Before continuing it may be best to have already:') . '</p>';
  $output .= '<ul>';
  $output .= '<li>' . t('Imported users, forums, topics and optionally polls using the phpbb2drupal Module') . '</li>';
  $output .= '<li>' . t('Imported any additional data using add on modules if available.') . '</li>';
  $output .= '</ul>';
  //User Set up
  $output .= '<h2>' . t('User Settings') . '</h2>';
  $output .= '<p>' . t('Visit the <a href="@accountsettings">account settings</a> page and make sure they are correct. In order for avatars to display correctly, the "enable user pictures" option must be checked.', array('@accountsettings' => url('admin/config/people/accounts'))) . '</p>';
  $output .= '<p>' . t('Visit the <a href="@themeconfig">themes configuration</a> page and check "User pictures in posts" and/or "User pictures in comments"', array('@themeconfig' => url('admin/appearance/settings'))) . '</p>';
  $output .= '<p>' . t('Finally, manually copy the avatar icons from the <tt>phpbb2/images/avatars/</tt> directory to the <tt>@files</tt> directory.', array('@files' => variable_get('file_public_path', conf_path() . '/files')  . '/pictures')) . '</p>';
  // Forum Set up
  $output .= '<h2>' . t('Forum Settings') . '</h2>';
  $output .= '<p>' . t('If need be, set up the private forums as explained in the <a href="http://drupal.org/node/58969" target="_blank">documentation</a>. Using the Forum Access module is the preferred and simplest method.') . '</p>';

  // Redirection from old URL:
  $output .= '<h2>' . t('Redirection from phpBB2 forum URL to new Drupal forum URL') . '</h2>';
  $output .= '<p>' . t('Enable the phpbb_redirect.module. This way, users accessing URLs from your old phpBB2 installation will be redirected to your new Drupal forum. (Note that currently the module assumes that the phpBB forum was located in the <tt>forum/</tt> subdirectory of your Drupal installation).') . '</p>';

  // BBcode  
  $bbcode = variable_get('phpbb2drupal_bbcode', 0);
  if ($bbcode) {
    $output .= '<h2>' . t('Remove BBcode module') . '</h2>';
    $output .= '<p>' . t("The bbcode has been cleaned from the posts. You shouldn't need the bbcode module anymore. You can disable it.") . '</p>';
  }
  else {
    $output .= '<h2>' . t('Configure BBcode module') . '</h2>';
    $output .= '<p>' . t('Most probably, there are many phpBB forum postings formatted in BBcode. During the import procedure, the BBcode has been cleaned so that it can work with the Drupal bbcode module. Please install and configure the <a href="http://drupal.org/project/bbcode" target="_blank">BBcode module</a> so that the forum topics display properly. See the BBcode module documentation for details.') . '</p>';
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
  $output .= l(t('Configure the migration'), 'admin/config/phpbb2drupal');
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
  $output .= t('<a href="@cleanuplink">Cleanup</a> (Accessing this link will delete all the saved phpbb2drupal settings: click only when you\'re done with all the above.)', array('@cleanuplink' => url('admin/phpbb2drupal/cleanup')));
  $output .= '</li>';
  $output .= '<li>';
  $output .= t('Enable the phpBB_Redirect module for seamless redirection from the old forums to the new. This module requires <a href="@cleanurls">clean urls</a> to be active.', array('@cleanurls' => url('admin/config/search/clean-urls')));
  $output .= '</li>';
  $output .= '<li>';
  $output .= t('If you decide to use the phpBB_Redirect module, do NOT uninstall phpbb2drupal - This will delete tables necessary for redirection. However, you CAN deactivate the module safely.');
  $output .= '</li>';
  $output .= '</ol>';

  // process will die() if the link to the phpBB2 database is wrong:
  $output .= '<p>' . t('If the phpBB2 data is in another database, but you mistakenly used the wrong database url, you may be unable to access the setting page due to some limitations of Drupal. <a href="@reseturl">Click here to reset the database url</a>.',
      array('@reseturl' => url('admin/phpbb2drupal/reset'))) . '</p>';

  return $output;
}

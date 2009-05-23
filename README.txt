
DO:
1. drop the whole phpbb2drupal sub-directory into your modules directory.
2. Get the phpass module either from http://www.theraggedyedge.co.uk/project/phpbb-2-drupal or from drupal.org/project/phpass - but the latter may be buggy or contain extra steps before enabling.
3. Enable the module at admin/build/modules.
4. visit the page admin/phpbb2drupal and follow the directions on screen.
5. After conversion, enable the phpbb_redirect Module to allow old links to keep working.
6. Be aware that uninstalling the phpbb2drupal module will break the redirection inphpbb_redirect as it uses information from the phpbb2drupal tables.


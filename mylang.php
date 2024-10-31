<?php

/**
 * myLang
 *
 * @package           myLang
 * @author            myLang
 * @license           GPL-3.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       myLang Easy Site Translator for WPML
 * Description:       An add-on for WPML that allows you to easily translate all pages, posts and products into 90 languages with a single click!
 * Version:           1.3.1
 * Requires at least: 5.4
 * Requires PHP:      5.6
 * Author:            myLang
 * Author URI:        https://mylang.me/
 * Text Domain:       mylang
 */

if (!defined('ABSPATH')) {
    exit; // Don't access directly.
};

define('MYLANG_VERSION', '1.3.1');
define('MYLANG_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('MYLANG_PLUGIN_FOLDER', dirname(MYLANG_PLUGIN_BASENAME));
define('MYLANG_PLUGIN_PATH', dirname(__FILE__));
define('MYLANG_PLUGINS_DIR', realpath(dirname(__FILE__) . '/..'));
define('MYLANG_PLUGIN_FILE', basename(MYLANG_PLUGIN_BASENAME));

/**
 * Write an entry to a log file in the uploads directory.
 * 
 * @since 1.0
 * 
 * @param mixed $entry String or array of the information to write to the log.
 * @param string $file Optional. The file basename for the .log file.
 * @param string $mode Optional. The type of write. See 'mode' at https://www.php.net/manual/en/function.fopen.php.
 * @return boolean|int Number of bytes written to the lof file, false otherwise.
 */
function mylang_translation_log($entry, $mode = 'a', $file = 'mylang_translation_log')
{
    // Get WordPress uploads directory.
    $upload_dir = wp_upload_dir();
    $upload_dir = $upload_dir['basedir'];

    // If the entry is array, json_encode.
    if (is_array($entry)) {
        $entry = json_encode($entry);
    }

    // Write the log file.
    $file  = $upload_dir . '/' . $file . '.log';
    $file  = fopen($file, $mode);
    $bytes = fwrite($file, $entry . "\n");
    fclose($file);

    return $bytes;
}
require_once MYLANG_PLUGIN_PATH . '/inc/class-mylang.php';


$myLang = new MyLangTranslate();

//Plugin activation and deactivation hooks
register_activation_hook(__FILE__, array(&$myLang, 'mylang_install_table'));
//runs when plugin is activated
register_deactivation_hook(__FILE__, array(&$myLang, 'mylang_uninstall_table'));

//Plugin Settings Page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(&$myLang, 'mylang_settings_link_on_plugin_page'));
<?php

/**
 * Main plugin file.
 * Organize media files into folders/ categories at ease.
 *
 * @package      KCMS Media
 * @author       KubeeCMS
 * @copyright    Copyright (c) 2012-2020, KubeeCMS - KUBEE
 * @license      GPL-2.0-or-later
 * @link         https://github.com/KubeeCMS/KCMS-Media/
 * @link         https://github.com/KubeeCMS/
 *
 * @wordpress-plugin
 * Plugin Name:  KCMS Media
 * Plugin URI:   https://github.com/KubeeCMS/KCMS-Media/
 * Description:  Organize media files into folders/ categories at ease.
 * Version:      4.0.4
 * Author:       KubeeCMS - KUBEE
 * Author URI:   https://github.com/KubeeCMS/
 * License:      GPL-2.0-or-later
 * License URI:  https://opensource.org/licenses/GPL-2.0
 * Text Domain:  filebird
 * Domain Path:  /i18n/languages/
 * Network:      true
 * Requires WP:  5.5
 * Requires PHP: 7.3
 *
 * Copyright (c) 2012-2020 KubeeCMS - KUBEE
 *
 *     This file is part of KubeeCMS,
 *    ... *
 *     KubeeCMS Additions is are free software:
 *     You can redistribute it and/or modify it under the terms of the
 *     GNU General Public License as published by the Free Software
 *     Foundation, either version 2 of the License, or (at your option)
 *     any later version.
 *
 *     KubeeCMS is distributed in the hope that
 *     it will be useful, but WITHOUT ANY WARRANTY; without even the
 *     implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
 *     PURPOSE. See the GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with WordPress. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FileBird;

defined('ABSPATH') || exit;

if (!defined('NJFB_PREFIX')) {
  define('NJFB_PREFIX', 'filebird');
}

if (!defined('NJFB_VERSION')) {
  define('NJFB_VERSION', '4.0.4');
}

if (!defined('NJFB_PLUGIN_FILE')) {
  define('NJFB_PLUGIN_FILE', __FILE__);
}

if (!defined('NJFB_PLUGIN_URL')) {
  define('NJFB_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('NJFB_PLUGIN_PATH')) {
  define('NJFB_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

if (!defined('NJFB_PLUGIN_BASE_NAME')) {
  define('NJFB_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
}

if (!defined('NJFB_REST_URL')) {
  define('NJFB_REST_URL', 'njt-fbv/v1');
}

if (!defined('NJFB_REST_PUBLIC_URL')) {
  define('NJFB_REST_PUBLIC_URL', 'njt-fbv/public/v1');
}

spl_autoload_register(function ($class) {
  $prefix = __NAMESPACE__; // project-specific namespace prefix
  $base_dir = __DIR__ . '/includes'; // base directory for the namespace prefix

  $len = strlen($prefix);
  if (strncmp($prefix, $class, $len) !== 0) { // does the class use the namespace prefix?
    return; // no, move to the next registered autoloader
  }

  $relative_class_name = substr($class, $len);

  // replace the namespace prefix with the base directory, replace namespace
  // separators with directory separators in the relative class name, append
  // with .php
  $file = $base_dir . str_replace('\\', '/', $relative_class_name) . '.php';

  if (file_exists($file)) {
    require $file;
  }
});

function init() {
  Plugin::getInstance();
  Plugin::activate();
  
  I18n::loadPluginTextdomain();

  Classes\Convert::getInstance();
  Classes\PageBuilders::getInstance();
  Classes\Feedback::getInstance();
  Classes\Review::getInstance();
  //Classes\Upgrade::getInstance();

  Page\Settings::getInstance();
  Controller\Folder::getInstance();
  Controller\FolderUser::getInstance();
  Controller\CompatibleWpml::getInstance();
  Controller\CompatiblePolylang::getInstance();

  Controller\Api::getInstance();
}
add_action('plugins_loaded', 'FileBird\\init');

register_activation_hook(__FILE__, array('FileBird\\Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('FileBird\\Plugin', 'deactivate'));

if ( function_exists( 'register_block_type' ) ) {
  require plugin_dir_path(__FILE__) . 'blocks/filebird-gallery/src/init.php';
}
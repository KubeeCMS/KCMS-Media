<?php
namespace FileBird\Controller;

//use FileBird\Controller\Convert as ConvertController;

use FileBird\Model\Folder as FolderModel;
use FileBird\Classes\Helpers as Helpers;
use FileBird\Classes\Tree;

use FileBird\I18n as I18n;

defined('ABSPATH') || exit;

/**
 * Folder Controller
 */

class Folder extends Controller {

  protected static $instance = null;
  
  public function __construct() {
    add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));

    add_action('rest_api_init', array($this, 'registerRestFields'));

    add_action('add_attachment', array($this, 'addAttachment'));
    // add_action('edit_attachment', array($this, 'filebird_set_attachment_category'));
    add_action('delete_attachment', array($this, 'deleteAttachment'));

    add_filter('ajax_query_attachments_args', array($this, 'ajaxQueryAttachmentsArgs'));
    add_filter('restrict_manage_posts', array($this, 'restrictManagePosts'));
    //add_action('pre_get_posts', array($this, 'preGetPosts'));
    add_filter( 'posts_clauses', array($this, 'postsClauses'), 10, 2 );
    add_action( 'pre-upload-ui', array($this, 'actionPluploadUi') );
    add_action( 'admin_notices', array($this, 'adminNotices') );
  }
  public function adminNotices()
  {
    global $pagenow;
    $showInPages = array('upload.php', 'plugins.php');
    if (FolderModel::countFolder() > 0 || !in_array($pagenow, $showInPages)) {
      return;
    }
    ?>
    <div class="notice notice-info is-dismissible">
      <p>
        <?php _e('Create your first folder for media library now.', 'filebird')?>
        <a href="<?php echo esc_url(admin_url('/upload.php')) ?>">
          <strong><?php _e('Get Started', 'filebird')?></strong>
        </a>
      </p>
    </div>
    <?php
  }
  public function registerRestFields() {
    register_rest_route(NJFB_REST_URL,
        'get-folders',
        array(
          'methods' => 'GET',
          'callback' => array($this, 'ajaxGetFolder'),
          'permission_callback' => array($this, 'resPermissionsCheck'),
        )
    );
    register_rest_route(NJFB_REST_URL,
        'new-folder',
        array(
          'methods' => 'POST',
          'callback' => array($this, 'ajaxNewFolder'),
          'permission_callback' => array($this, 'resPermissionsCheck'),
        )
    );
    register_rest_route(NJFB_REST_URL,
        'update-folder',
        array(
          'methods' => 'POST',
          'callback' => array($this, 'ajaxUpdateFolder'),
          'permission_callback' => array($this, 'resPermissionsCheck'),
        )
    );
    register_rest_route(NJFB_REST_URL,
        'update-folder-ord',
        array(
          'methods' => 'POST',
          'callback' => array($this, 'ajaxUpdateFolderOrd'),
          'permission_callback' => array($this, 'resPermissionsCheck'),
        )
    );
    register_rest_route(NJFB_REST_URL,
        'delete-folder',
        array(
          'methods' => 'POST',
          'callback' => array($this, 'ajaxDeleteFolder'),
          'permission_callback' => array($this, 'resPermissionsCheck'),
        )
    );
    register_rest_route(NJFB_REST_URL,
        'set-folder-attachments',
        array(
          'methods' => 'POST',
          'callback' => array($this, 'ajaxSetFolder'),
          'permission_callback' => array($this, 'resPermissionsCheck'),
        )
    );
    register_rest_route(NJFB_REST_URL,
        'update-tree',
        array(
          'methods' => 'POST',
          'callback' => array($this, 'ajaxUpdateTree'),
          'permission_callback' => array($this, 'resPermissionsCheck'),
        )
    );
    register_rest_route(NJFB_REST_URL,
        'get-relations',
        array(
          'methods' => 'POST',
          'callback' => array($this, 'ajaxGetRelations'),
          'permission_callback' => array($this, 'resPermissionsCheck'),
        )
    );
  }
  public function resPermissionsCheck() {
    return current_user_can('upload_files');
  }
  public static function getInstance() {
    if (null == self::$instance) {
      self::$instance = new self;
    }
    return self::$instance;
  }

  public function enqueueAdminScripts($screenId) {
    if (function_exists('get_current_screen')) {
      $current_screen = get_current_screen();
      if ($screenId == "upload.php") {
        wp_register_script('jquery-resizable', NJFB_PLUGIN_URL . 'assets/js/jquery-resizable.min.js' );
        wp_enqueue_script('jquery-resizable');
      }
    }

    wp_enqueue_script('jquery-ui-draggable');
    wp_enqueue_script('jquery-ui-droppable');


    wp_enqueue_script('fbv-folder', NJFB_PLUGIN_URL . 'assets/dist/app.js', array(), NJFB_VERSION, false);
    wp_enqueue_script('fbv-import', NJFB_PLUGIN_URL . 'assets/js/import.js', array('jquery'), NJFB_VERSION, false);
    wp_enqueue_script('fbv-lib', NJFB_PLUGIN_URL . 'assets/js/jstree/jstree.min.js', array(), NJFB_VERSION, false);
    
    wp_enqueue_style('fbv-folder', NJFB_PLUGIN_URL . 'assets/dist/app.css', array(), NJFB_VERSION);
    wp_style_add_data('fbv-folder', 'rtl', 'replace');

    // wp_enqueue_style('fbr-lib-css', NJFB_PLUGIN_URL . 'apps/src/themes/filebird/fbstyles.css', array(), NJFB_VERSION);
    // wp_enqueue_style('fbr-lib-css', NJFB_PLUGIN_URL . 'assets/js/jstree/themes/default/style.min.css', array(), NJFB_VERSION);
    // wp_enqueue_style('fbr-lib-css', NJFB_PLUGIN_URL . 'assets/js/jstree/themes/default/style.min.css', array(), NJFB_VERSION);
    // wp_enqueue_style('fbr-lib-css', NJFB_PLUGIN_URL . 'assets/js/jstree/themes/default/style.min.css', array(), NJFB_VERSION);

    // $folders_from_db = FolderModel::allFolders('*', null, null);
    // $tree = $this->getTree($folders_from_db, 0, array());

    wp_localize_script('fbv-folder', 'fbv_data', array(
      'nonce' => wp_create_nonce('fbv_nonce'),
      'rest_nonce' => wp_create_nonce('wp_rest'),
      'nonce_error' => __('Could not proccess your request.', 'filebird'),
      'current_folder' => ((isset($_GET['fbv'])) ? (int)sanitize_text_field($_GET['fbv']) : -1), //-1: all files. 0: uncategorized
      'folders' => FolderModel::allFolders('id as term_id, name as term_name', array('term_id', 'term_name')),
      'relations' => FolderModel::getRelations(),
      // 'is_upload' => $current_screen != null && $current_screen->id === 'upload' ? 1 : 0,
      'i18n' => i18n::getTranslation(),
      'media_mode' => get_user_option('media_library_mode', get_current_user_id()),
      'json_url' => rest_url(NJFB_REST_URL),
    ));
  }

  public function restrictManagePosts() {
    $screen = get_current_screen();
    if ($screen->id == "upload") {
      $fbv = ((isset($_GET['fbv'])) ? (int)sanitize_text_field($_GET['fbv']) : -1);
      $folders = FolderModel::allFolders();

      $all = new \stdClass();
      $all->id = -1;
      $all->name = __('All Folders', 'filebird');

      $uncategorized = new \stdClass();
      $uncategorized->id = 0;
      $uncategorized->name = __('Uncategorized', 'filebird');

      array_unshift($folders, $all, $uncategorized);
      echo '<select name="fbv" id="filter-by-fbv" class="fbv-filter attachment-filters fbv">';
      foreach($folders as $k => $folder) {
        echo sprintf('<option value="%1$d" %3$s>%2$s</option>', $folder->id, $folder->name, selected($folder->id, $fbv, false));
      }
      echo '</select>';
    }
  }
  // public function preGetPosts($query) {
  //   if ( is_admin() && $query->is_main_query() ) {
  //     if (isset($_GET['fbv'])) {
  //       $query->set('fbv', (int)$_GET['fbv']);
  //     }
  //   }
  // }
  public function postsClauses($clauses, $query) {
    if ($query->get("post_type") !== "attachment") {
      return $clauses;
    }
    if(isset($_GET['fbv'])) {
      $fbv = (int)sanitize_text_field($_GET['fbv']);
      $in_not_in = FolderModel::getInAndNotInIds($fbv, true);
      if(count($in_not_in['post__not_in']) > 0) {
        $clauses['where'] .= "AND ID NOT IN (".implode(',', $in_not_in['post__not_in']).")";
      } elseif(count($in_not_in['post__in']) > 0) {
        $clauses['where'] .= "AND ID IN (".implode(',', $in_not_in['post__in']).")";
      }
    }
    return $clauses;
  }
  public function addAttachment($post_id) {
    $fbv = ((isset($_REQUEST['fbv'])) ? sanitize_text_field($_REQUEST['fbv']) : '');
    if($fbv != '') {
      if(is_numeric($fbv)) {
        $parent = $fbv;
      } else {
        $fbv = explode('/', ltrim(rtrim($fbv, '/'), '/'));
        $parent = (int)$fbv[0];
        if($parent < 0) $parent = 0;//important
        unset($fbv[0]);
        foreach($fbv as $k => $v) {
          $parent = FolderModel::newOrGet($v, $parent);
        }
      }
      FolderModel::setFoldersForPosts($post_id, $parent);
    }
  }
  public function deleteAttachment($post_id) {
    FolderModel::deleteFoldersOfPost($post_id);
  }

  public function ajaxQueryAttachmentsArgs($query) {
    if(isset($_REQUEST['query']['fbv'])) {
      $fbv = (int)sanitize_text_field($_REQUEST['query']['fbv']);
      $in_not_in = FolderModel::getInAndNotInIds($fbv);
      if(!isset($query['post__not_in'])) {
        $query['post__not_in'] = array();
      }
      if(!isset($query['post__in'])) {
        $query['post__in'] = array();
      }
      $query['post__not_in'] += $in_not_in['post__not_in'];
      $query['post__in'] += $in_not_in['post__in'];
    }
    return $query;
  }
  public function ajaxGetFolder() {
    //check_ajax_referer('fbv_nonce', 'nonce', true);
    if(get_option('fbv_old_data_updated_to_v4', '0') !== '1') {
      Convert::insertToNewTable();
      update_option('fbv_old_data_updated_to_v4', '1');
    }
    $order_by = null;
    $sort_option = 'reset';

    if(isset($_GET['sort']) && \in_array(sanitize_text_field($_GET['sort']), array('name_asc', 'name_desc', 'reset'))) {
      if(sanitize_text_field($_GET['sort']) == 'name_asc') {
        $order_by = 'name asc';
        $sort_option = sanitize_text_field($_GET['sort']);
      } elseif(sanitize_text_field($_GET['sort']) == 'name_desc') {
        $order_by = 'name desc';
        $sort_option = sanitize_text_field($_GET['sort']);
      }
      update_option('njt_fb_sort_folder', $sort_option);
    } else {
      $njt_fb_sort_folder = get_option('njt_fb_sort_folder', 'reset');
      if($njt_fb_sort_folder == 'reset') {
        $order_by = null;
      } elseif($njt_fb_sort_folder == 'name_asc') {
        $order_by = 'name asc';
      } elseif($njt_fb_sort_folder == 'name_desc') {
        $order_by = 'name desc';
      }
    }
    
    $tree = Tree::getFolders($order_by);
    
    wp_send_json_success(array(
      'tree' => $tree,
      'folder_count' => array(
        '-1' => Tree::getCount(-1),
        '0' => Tree::getCount(0)
      )
    ));
  }
  public function ajaxNewFolder() {
    //check_ajax_referer('fbv_nonce', 'nonce', true);
    $name = ((isset($_POST['name'])) ? sanitize_text_field(wp_unslash($_POST['name'])) : '');
    $parent = ((isset($_POST['parent'])) ? sanitize_text_field($_POST['parent']) : '');
    $id = null;
    if($name != '' && $parent != '') {
      // if(!is_array($name)) {
      //   $name = array($name);
      // }
      // if($parent < 0) $parent = 0;

      // $paths = array();
      // foreach($name as $k => $v) {
      //   $parent2 = $parent;
      //   $sub_folders = explode('/', ltrim(rtrim($v, '/'), '/'));
      //   foreach($sub_folders as $k2 => $v2) {
      //     $parent2 = FolderModel::newOrGet($v2, $parent2);
      //   }
      //   $paths[$v] = $parent2;
      //   $id = $parent2; //ID Added
      // }
      // wp_send_json_success(array('id' => $id));
      $insert = FolderModel::newOrGet($name, $parent, false);
      if($insert !== false) {
        wp_send_json_success(array('id' => $insert));
      } else {
        wp_send_json_error(array('mess' => __('Please choose another name.', 'filebird')));
      }
    } else {
      wp_send_json_error(array(
        'mess' => __('Validation failed', 'filebird')
      ));
    }
  }
  public function ajaxUpdateFolder() {
    //check_ajax_referer('fbv_nonce', 'nonce', true);
    $id = ((isset($_POST['id'])) ? sanitize_text_field($_POST['id']) : '');
    $parent = ((isset($_POST['parent'])) ? intval(sanitize_text_field($_POST['parent'])) : '');
    $name = ((isset($_POST['name'])) ? sanitize_text_field(wp_unslash($_POST['name'])) : '');
    if(is_numeric($id) && is_numeric($parent) && $name != '') {
      $update = FolderModel::updateFolderName($name, $parent, $id);
      if($update === true) {
        wp_send_json_success();
      } else {
        wp_send_json_error(array('mess' => __('Please choose another name.', 'filebird')));
      }
      
    }
    wp_send_json_error();
  }
  public function ajaxUpdateFolderOrd() {
    $id = ((isset($_POST['id'])) ? sanitize_text_field($_POST['id']) : '');
    $parent = ((isset($_POST['parent'])) ? sanitize_text_field(wp_unslash($_POST['parent'])) : '');
    $ord = ((isset($_POST['ord'])) ? sanitize_text_field(wp_unslash($_POST['ord'])) : '');
    if(is_numeric($id) && is_numeric($parent) && is_numeric($ord)) {
      FolderModel::updateOrdAndParent($id, $ord, $parent);
      wp_send_json_success();
    }
    wp_send_json_error();
  }
  public function ajaxDeleteFolder() {
    //check_ajax_referer('fbv_nonce', 'nonce', true);
    $ids = ((isset($_POST['ids'])) ? Helpers::sanitize_array($_POST['ids']) : '');
    if($ids != '') {
      if(!is_array($ids)) $ids = array($ids);
      $ids = array_map('intval', $ids);
      
      foreach($ids as $k => $v) {
        if($v > 0) FolderModel::deleteFolderAndItsChildren($v);
      }
      wp_send_json_success();
    }
    wp_send_json_error(array(
      'mess' => __('Can\'t delete folder, please try again later', 'filebird')
    ));
  }
  public function ajaxSetFolder() {
    //check_ajax_referer('fbv_nonce', 'nonce', true);
    $ids = ((isset($_POST['ids'])) ? Helpers::sanitize_array($_POST['ids']) : '');
    $folder = ((isset($_POST['folder'])) ? sanitize_text_field($_POST['folder']) : '');
    if($ids != '' && is_array($ids) && is_numeric($folder)) {
      FolderModel::setFoldersForPosts($ids, $folder);
      wp_send_json_success();
    }
    wp_send_json_error(array(
      'mess' => __('Validation failed', 'filebird')
    ));
  }
  public function ajaxUpdateTree() {
    //check_ajax_referer('fbv_nonce', 'nonce', true);
    $tree = ((isset($_POST['tree'])) ? sanitize_text_field($_POST['tree']) : '');
    if($tree != '') {
      FolderModel::rawInsert('(id, ord, parent) VALUES ' . $tree . ' ON DUPLICATE KEY UPDATE ord=VALUES(ord),parent=VALUES(parent)');
      wp_send_json_success(array(
        'mess' => __('Tree\'s been updated', 'filebird')
      ));
    }
    wp_send_json_error(array(
      'mess' => __('Validation failed', 'filebird')
    ));
  }
  public function ajaxGetRelations() {
    //check_ajax_referer('fbv_nonce', 'nonce', true);
    wp_send_json_success(array(
      'relations' => FolderModel::getRelations()
    ));
  }
  public function actionPluploadUi() {
    global $pagenow;
    $folders = FolderModel::allFolders();
    $default = array(
      array(
        'title' => __('Uncategorized', 'filebird'),
        'value' => '0'
      )
    );
    $data = array(
      'tree' => $this->getFlatTree($folders, 0, $default)
    );
    $this->loadView('particle/folder_dropdown', $data);
  }
  private function _buildQuery($tree, $parent) {
    $results = array();
    $ord = 0;
    foreach($tree as $k => $v) {
      // if($v['key'] < 1) continue;
      $results[] = sprintf('(%1$d, %2$d, %3$d)', $v['id'], $ord, $parent);
      if(isset($v['children']) && is_array($v['children']) && count($v['children']) > 0) {
        $children = $this->_buildQuery($v['children'], $v['id']);
        foreach ($children as $k2 => $v2) {
          $results[] = $v2;
        }
      }
      $ord++;
    }
    return $results;
  }
  
  private function getFlatTree($data, $parent = 0, $default = null, $level = 0) {
    $tree = is_null($default) ? array() : $default;
    foreach($data as $k => $v) {
      if($v->parent == $parent) {
        $node = array(
          'title' => str_repeat('-', $level) .  $v->name,
          'value' => $v->id,
        );
        $tree[] = $node;
        $children = $this->getFlatTree($data, $v->id, null, $level + 1);
        foreach($children as $k2 => $child) {
          $tree[] = $child;
        }
      }
    }
    return $tree;
  }
  
}
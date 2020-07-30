<?php
namespace FileBird\Controller;

use FileBird\Config\Fbv;

defined('ABSPATH') || exit;

/**
 * Helps to convert old-data (from filebird old version) to new data
 */

class Convert {

  private static $folder_table = 'fbv';
  private static $relation_table = 'fbv_attachment_folder';

  public function __construct() {
  }

  public static function insertToNewTable($folders = null, $parent = 0) {
    global $wpdb;
    if(is_null($folders)) $folders = Convert::getOldFolers();
    foreach($folders as $k => $folder) {
      $check = self::detail($folder->name, $parent);
      $insert_id = 0;
      if(is_null($check)) {
        $wpdb->insert(self::getTable(self::$folder_table), array(
          'name' => $folder->name,
          'parent' => $parent,
          'type' => 0
        ));
        $insert_id = (int)$wpdb->insert_id;
      } else {
        $insert_id = (int)$check->id;
      }
      //attachments
      if(isset($folder->attachments)) {
        foreach($folder->attachments as $k2 => $attachment_id) {
          $post = get_post($attachment_id);
          if(is_object($post) && $post->post_type == 'attachment') {
            self::setFolder($attachment_id, $insert_id, true);
          }
        }
      }
      if(isset($folder->children)) {
        self::insertToNewTable($folder->children, $insert_id);
      }
      
    }
  }
  private static function setFolder($ids, $folder, $delete_first = false) {
    global $wpdb;
    if(is_numeric($ids)) {
      $ids = array($ids);
    }
    foreach ($ids as $k => $v) {
      if($delete_first) {
        $wpdb->delete(self::getTable(self::$relation_table), array('attachment_id' => $v), array('%d'));
      }
      if((int)$folder > 0) {
        $wpdb->insert(self::getTable(self::$relation_table), array('attachment_id' => (int)$v, 'folder_id' => (int)$folder), array('%d', '%d'));
      }
    }
  }
  private static function detail($name, $parent) {
    global $wpdb;
    $check = $wpdb->get_results("SELECT id FROM " . self::getTable(self::$folder_table) . " WHERE `name` = '".$name."' AND `parent` = '".$parent."'");
    if($check != null && count($check) > 0) {
      return $check[0];
    } else {
      return null;
    }
  }
  private static function getTable($table) {
    global $wpdb;
    return $wpdb->prefix . $table;
  }

  public static function getOldFolers() {
    $folders = self::_getOldFolers(0);
    return $folders;
  }

  private static function _getOldFolers($parent) {
    global $wpdb;
    $folders = array();
    $query = "SELECT t.term_id as id, t.name FROM $wpdb->terms as t LEFT JOIN $wpdb->term_taxonomy as tt ON (t.term_id = tt.term_id) WHERE parent = $parent and taxonomy = 'nt_wmc_folder'";
    $folders = $wpdb->get_results($query);
    foreach($folders as $k => $v) {
      $folders[$k]->children = self::_getOldFolers($v->id);
      $folders[$k]->attachments = self::_getAttachments($v->id);
    }
    return $folders;
  }
  private static function _getAttachments($term_id) {
    global $wpdb;
    $query = "SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id = " . $term_id;
    $_data = $wpdb->get_results($query);
    $ids = array();
    foreach($_data as $k => $v) {
      $ids[] = $v->object_id;
    }
    return $ids;
  }
}

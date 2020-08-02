<?php
namespace FileBird\Classes;
use FileBird\Controller\Folder;

defined('ABSPATH') || exit;

class PageBuilders {

    protected static $instance = null;
    protected $folderController;

    public static function getInstance() {
        if (null == self::$instance) {
          self::$instance = new self;
        }
        
        return self::$instance;
    }

    public function __construct() {
        $this->folderController = Folder::getInstance();
        add_action('init', array($this, 'prepareRegister'));
    }

    public function prepareRegister(){
        // Compatible for Elementor
        if (defined('ELEMENTOR_VERSION')) {
            $this->registerForElementor();
        }
        // Compatible for WPBakery - Work normally

        // Compatible for Beaver Builder
        if (class_exists('FLBuilderLoader')) {
            $this->registerForBeaver();
        }

        // Brizy Builder
        if (class_exists('Brizy_Editor')) {
            $this->registerForBrizy();
        }
        
        // Compatible for Divi
        if (class_exists('ET_Builder_Element')) {
            $this->registerForDivi();
        }

        // Compatible for Thrive
        if (defined('TVE_IN_ARCHITECT') || class_exists('Thrive_Quiz_Builder')) {
            $this->registerForThrive();
        }

        // Fusion Builder
        if (class_exists('Fusion_Builder_Front')) {
            $this->registerForFusion();
        }
    }

    public function enqueueScripts(){
        $this->folderController->enqueueAdminScripts('pagebuilders');
    }

    public function registerForElementor(){
        add_action('elementor/editor/before_enqueue_scripts', array($this, 'enqueueScripts'));
    }

    public function registerForBeaver(){
        add_action('fl_before_sortable_enqueue', array($this, 'enqueueScripts'));
    }

    public function registerForBrizy(){
        add_action('brizy_editor_enqueue_scripts', array($this, 'enqueueScripts'));
    }

    public function registerForDivi(){
        add_action('et_fb_enqueue_assets', function(){
            wp_register_script('fbv-ajax', '', array(), '', true);
            wp_enqueue_script('fbv-ajax');
            wp_localize_script('fbv-ajax', 'ajaxurl', admin_url('admin-ajax.php'));
            $this->enqueueScripts();
        });
    }

    public function registerForThrive(){
        add_action('tcb_main_frame_enqueue', array($this, 'enqueueScripts'));
    }

    public function registerForFusion(){
        add_action('fusion_builder_enqueue_live_scripts', array($this, 'enqueueScripts'));
    }
}

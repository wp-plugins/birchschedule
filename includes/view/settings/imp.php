<?php

class Birchschedule_View_Settings_Imp {

	private static $active_tab;
	private static $tabs;

	private function __construct() {
		
	}

	static function init() {
        global $birchschedule;
        
        $package = $birchschedule->view->settings;
        $package->add_action('admin_init', 'wp_admin_init');
	}

	static function wp_admin_init() {
		global $birchschedule;

        if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'birchschedule_settings') {
            if (isset($_GET['tab'])) {
                self::$active_tab = $_GET['tab'];
            } else {
                self::$active_tab = 'general';
            }
            self::$tabs = $birchschedule->view->settings->get_tabs();
            $birchschedule->view->settings->init_tab(array(
            	'tab' => self::$active_tab
            ));
	        $birchschedule->view->settings->add_action(
	        	'admin_enqueue_scripts', 'enqueue_scripts');
        }
	}

    static function enqueue_scripts($hook) {
        global $birchschedule;

        if($birchschedule->view->get_page_hook('settings') !== $hook) {
            return;
        }
        $birchschedule->view->enqueue_scripts(array('birs_admin_common'));
    }

	static function get_tabs() {
		return array();
	}

	static function init_tab($arg) {}

    static function compare_tab_order($a, $b) {
        if ($a['order'] == $b['order']) {
            return 0;
        }
        return ($a['order'] < $b['order']) ? -1 : 1;
    }

    static function render_admin_page() {
    	global $birchschedule;

        $setting_page_url = admin_url("admin.php") . "?page=birchschedule_settings";
        uasort(self::$tabs, array(__CLASS__, 'compare_tab_order'));
        $birchschedule->view->show_notice();
        ?>
        <div class="wrap">
            <h2 class="nav-tab-wrapper">
                <?php
                if (is_array(self::$tabs)):
                    foreach (self::$tabs as $tab_name => $tab):
                        $active_class = "";
                        if (self::$active_tab == $tab_name) {
                            $active_class = "nav-tab-active";
                        }
                        ?>
                        <a href='<?php echo $setting_page_url . "&tab=$tab_name"; ?>' class="nav-tab <?php echo $active_class; ?>"><?php echo $tab['title']; ?></a>
                        <?php
                    endforeach;
                endif;
                ?>
            </h2>
            <?php
            if(isset(self::$tabs[self::$active_tab])) {
	            $active_tab = self::$tabs[self::$active_tab];
	            if ($active_tab) {
	                call_user_func($active_tab['action']);
	            }
            }
            ?>
        </div>
        <?php
    }

}
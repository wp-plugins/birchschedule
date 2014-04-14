<?php

class Birchschedule_View_Settings_Imp {

	private static $active_tab;
	private static $tabs;

	private function __construct() {
		
	}

	static function init() {
        global $birchschedule;
        
        $package = $birchschedule->view->settings;
        add_action('admin_init', array($package, 'wp_admin_init'));
        $package->init_capabilities();
	}

	static function wp_admin_init() {
		global $birchschedule;

        $package = $birchschedule->view->settings;
        if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'birchschedule_settings') {
            if (isset($_GET['tab'])) {
                self::$active_tab = $_GET['tab'];
            } else {
                self::$active_tab = 'general';
            }
            self::$tabs = $package->get_tabs();
            $package->init_tab(array(
            	'tab' => self::$active_tab
            ));
	        add_action('admin_enqueue_scripts', array($package, 'enqueue_scripts'));
        }
	}

    static function enqueue_scripts($hook) {
        global $birchschedule;

        if($birchschedule->view->get_page_hook('settings') !== $hook) {
            return;
        }
        $birchschedule->view->register_3rd_scripts();
        $birchschedule->view->register_3rd_styles();
        $birchschedule->view->enqueue_scripts(array('birchschedule_view_admincommon'));
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

    static function get_tab_metabox_category($tab_name) {
        return $tab_name . '_main';
    }

    static function get_tab_page_hook($tab_name) {
        return 'birchschedule_page_settings_tab_' . $tab_name;
    }

    static function get_tab_save_action_name($tab_name) {
        return 'birchschedule_save_options_' . $tab_name;
    }

    static function get_tab_options_name($tab_name) {
        return 'birchschedule_options_' . $tab_name;
    }

    static function get_tab_transient_message_name($tab_name) {
        return "birchschedule_" . $tab_name . "_info";
    }

    static function save_tab_options($tab_name, $message) {
        global $birchschedule;
        
        $package = $birchschedule->view->settings;
        $save_action_name = $package->get_tab_save_action_name($tab_name);
        check_admin_referer($save_action_name);
        $options_name = $package->get_tab_options_name($tab_name);
        if(isset($_POST[$options_name])) {
            $options = stripslashes_deep($_POST[$options_name]);
            update_option($options_name, $options);
        }
        $transient_name = $package->get_tab_transient_message_name($tab_name);
        set_transient($transient_name, $message, 60);
        $orig_url = $_POST['_wp_http_referer'];
        wp_redirect($orig_url);
        exit;
    }

    static function render_tab_page($tab_name) {
        global $birchschedule;
        
        $package = $birchschedule->view->settings;
        $page_hook = $package->get_tab_page_hook($tab_name);
        $screen = $birchschedule->view->get_screen($page_hook);
        $save_action_name = $package->get_tab_save_action_name($tab_name);
        $options_name = $package->get_tab_options_name($tab_name);
        $options = get_option($options_name);
        if($options && isset($options['version'])) {
            $version = $options['version'];
        } else {
            $version = false;
        }
        $block_id = "birchschedule_" . $tab_name;
        ?>
        <style type="text/css">
            #notification_main-sortables .hndle {
                cursor: pointer;
            }
            #notification_main-sortables .wp-tab-panel {
                max-height: 500px;
            }
        </style>
        <div id="<?php echo $block_id; ?>" class="wrap">
            <form method="post" action="<?php echo admin_url('admin-post.php') ?>">
                <?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false); ?>
                <?php wp_nonce_field($save_action_name); ?>
                <input type="hidden" name="action" value="<?php echo $save_action_name; ?>" />
                <?php if($version) { ?>
                    <input type="hidden" name="<?php echo $options_name . '[version]'; ?>" value="<?php echo $version; ?>" />
                <?php } ?>
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-1">
                        <div id="postbox-container-1" class="postbox-container">
                            <?php do_meta_boxes($screen, $package->get_tab_metabox_category($tab_name), array()) ?>
                        </div>
                    </div>
                    <br class="clear" />
                </div>
                <input type="submit" name="submit" 
                    value="<?php _e('Save changes', 'birchschedule'); ?>" 
                    class="button-primary" />
            </form>
        </div>
        <script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready( function($) {
                postboxes.init = function() {};
                postboxes.add_postbox_toggles('<?php echo $package->get_tab_page_hook($tab_name); ?>');
                <?php
                $info_key = $package->get_tab_transient_message_name($tab_name);
                $info = get_transient($info_key);
                if (false !== $info) {
                ?>
                $.jGrowl('<?php echo esc_js($info); ?>', { 
                        life: 1000,
                        position: 'center',
                        header: '<?php _e('&nbsp', 'birchschedule'); ?>'
                    });
                <?php
                    delete_transient($info_key);
                }
                ?>
            });
            //]]>
        </script>
        <?php
    }

    static function get_post_types() {
        return array( 
            'birs_appointment', 'birs_client',
            'birs_location', 'birs_staff',
            'birs_service', 'birs_payment'
        );
    }

    static function get_core_capabilities() {
        global $birchschedule;
        
        $package = $birchschedule->view->settings;

        $capabilities = array();

        $capabilities['birs_core'] = array(
            "manage_birs_settings"
        );

        $capability_types = $package->get_post_types();

        foreach( $capability_types as $capability_type ) {
            $capabilities[ $capability_type ] = array(
                "edit_{$capability_type}",
                "read_{$capability_type}",
                "delete_{$capability_type}",
                "edit_{$capability_type}s",
                "edit_others_{$capability_type}s",
                "publish_{$capability_type}s",
                "read_private_{$capability_type}s",
                "delete_{$capability_type}s",
                "delete_private_{$capability_type}s",
                "delete_published_{$capability_type}s",
                "delete_others_{$capability_type}s",
                "edit_private_{$capability_type}s",
                "edit_published_{$capability_type}s"
            );
        }

        return $capabilities;
    }

    static function init_capabilities() {
        global $wp_roles, $birchschedule;

        $package = $birchschedule->view->settings;
        if ( class_exists('WP_Roles') )
            if ( ! isset( $wp_roles ) )
                $wp_roles = new WP_Roles();

        if ( is_object( $wp_roles ) ) {

            $capabilities = $package->get_core_capabilities();

            foreach( $capabilities as $cap_group ) {
                foreach( $cap_group as $cap ) {
                    $wp_roles->add_cap( 'administrator', $cap );
                }
            }
        }
    }
}
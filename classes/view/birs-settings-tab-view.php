<?php

class BIRS_Settings_Tab_View {
    var $tab_name;
    var $tab_title;
    var $order;
    var $action_name;
    var $meta_box_category;
    var $options_name;
    var $transient_info_name;

	function __construct($tab_name, $tab_title, $order) {
		$this->tab_name = $tab_name;
		$this->tab_title = $tab_title;
		$this->order = $order;
		$this->meta_box_category = $tab_name . "_main";
		$this->action_name = "birchschedule_save_options_" . $tab_name;
		$this->options_name = "birchschedule_options_" . $tab_name;
        $this->transient_info_name = "birchschedule_info_" . $tab_name;
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('birchschedule_settings_tabs', array(&$this, 'add_tab'));
        add_action('birchschedule_settings_' . $this->tab_name . '_view_init',
            array($this, 'tab_view_init'));
	}


	function init() {

	}

	function admin_init() {
        add_action('admin_post_' . $this->action_name, array($this, 'save_options'));
	}

    function add_tab($tabs) {
        $tabs[$this->tab_name] = array(
            'title' => $this->tab_title,
            'action' => array(&$this, 'render_tab_page'),
            'order' => $this->order
        );

        return $tabs;
    }

    function tab_view_init() {
        wp_enqueue_script('postbox');
        wp_enqueue_script('birs_admin_common');
        wp_enqueue_style('birchschedule_admin_styles');
    }

    function save_options() {
        check_admin_referer($this->action_name);
        if(isset($_POST[$this->options_name])) {
            $options = stripslashes_deep($_POST[$this->options_name]);
            update_option($this->options_name, $options);
        }
        set_transient($this->transient_info_name,  
        	$this->tab_title . " " . __("Updated", 'birchschedule'), 60);
        $orig_url = $_POST['_wp_http_referer'];
        wp_redirect($orig_url);
    }

    function get_page_hook_suffix() {
        return "birchschedule_page_settings_tab_" . $this->tab_name;
    }

    function render_tab_page() {
        $screen = apply_filters('birchschedule_get_wp_screen', 
            null, $this->get_page_hook_suffix());
       ?>
        <style type="text/css">
            #notification_main-sortables .hndle {
                cursor: pointer;
            }
            #notification_main-sortables .wp-tab-panel {
                max-height: 500px;
            }
        </style>
        <div id="birchschedule_calendar_sync" class="wrap">
            <form method="post" action="<?php echo admin_url('admin-post.php') ?>">
                <?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false); ?>
                <?php wp_nonce_field($this->action_name); ?>
                <input type="hidden" name="action" value="<?php echo $this->action_name; ?>" />
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-1">
                        <div id="postbox-container-1" class="postbox-container">
                            <?php do_meta_boxes($screen, $this->meta_box_category, array()) ?>
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
                postboxes.add_postbox_toggles('<?php echo $this->get_page_hook_suffix(); ?>');
                <?php
                $jgrowl_info = get_transient($this->transient_info_name);
                if (false !== $jgrowl_info) {
                ?>
                $.jGrowl('<?php echo esc_js($jgrowl_info); ?>', { 
                        life: 1000,
                        position: 'center',
                        header: '<?php _e('&nbsp', 'birchschedule'); ?>'
                    });
                <?php
                    delete_transient($this->transient_info_name);
                }
                ?>
            });
            //]]>
        </script>
        <?php
    }

}
<?php

class BIRS_Addon_Enhanced_Admin extends BIRS_Addon {

    function __construct() {
        parent::__construct();
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('birchschedule_view_calendar_admin_init', array($this, 'view_calendar_admin_init'));
        add_filter('birchschedule_general_client_section_admin_edit', array($this, 'get_general_section_client'), 15, 2);
        add_filter('birchschedule_appointment_details_admin_edit_duration', array($this, 'get_appointment_duration_html'), 15, 2);
    }

    function get_addon_dir_name() {
        return 'enhanced-admin';
    }

    function admin_init() {
        add_action('wp_ajax_birs_load_selected_client', array(&$this, 'ajax_load_selected_client'));
    }
    
    function view_calendar_admin_init() {
        $this->add_scripts();
        $this->add_styles();
    }

    function ajax_load_selected_client($client_id) {
        if (isset($_REQUEST['birs_client_id'])) {
            $client_id = $_REQUEST['birs_client_id'];
        } else {
            $client_id = 0;
        }
        ?>
        <div>
            <div id="birs_general_section_client">
                <?php
                echo apply_filters('birchschedule_general_client_section_admin_edit', '', $client_id);
                ?>
            </div>
            <div id="birs_client_details">
                <?php
                echo apply_filters('birchschedule_client_details_admin_edit', '', $client_id, 
                    array('client_name_first', 'client_name_last', 'client_email', 'client_phone'));
                ?>
            </div>
        </div>
        <?php
        exit();
    }

    function init() {
    }

    function add_scripts() {
        global $birchschedule;
        $product_version = $birchschedule->product_version;
        wp_register_script('birchschedule_enhanced_admin', $birchschedule->plugin_url() . '/addons/enhanced-admin/assets/js/enhanced-admin.js',
            array('birs_admin_calendar', 'select2'), $product_version);
        $params = array(
            'ajax_url' => admin_url('admin-ajax.php')
        );
        wp_enqueue_script('birchschedule_enhanced_admin');
        wp_localize_script('birchschedule_enhanced_admin', 'birs_enhanced_admin_params', $params);
    }

    function add_styles() {
        global $birchschedule;
        $product_version = $birchschedule->product_version;
        wp_register_style('birchschedule_enhanced_admin', $birchschedule->plugin_url() . '/addons/enhanced-admin/assets/css/enhanced-admin.css', 
            array('birchschedule_admin_styles', 'select2'), $product_version);
        wp_enqueue_style('birchschedule_enhanced_admin');
    }
    
    function get_appointment_duration_html($html, $appointment_duration) {
        ob_start();
        ?>
        <li class="birs_form_field">
            <label>
                <?php _e('Duration', 'birchschedule'); ?>
            </label>
            <div class="birs_field_content">
                <input type="text" id="birs_appointment_duration"
                        name="birs_appointment_duration"
                        value="<?php echo $appointment_duration; ?>">
                <span><?php _e('mins', 'birchschedule'); ?></span>
            </div>
        </li>
        <?php
        return ob_get_clean();
    }

    function get_general_section_client($html, $client_id) {
        ob_start();
        ?>
        <ul>
            <li class="birs_form_field">
                <label></label>
                <div class="birs_field_content">
                    <select id="birs_client_selector"
                        data-placeholder="<?php _e('Select an Existing Client'); ?>"
                        class="populate placeholder"
                        style="width:0;">
                        <option></option>
                        <?php
                        $query = new BIRS_Model_Query(
                                array(
                                    'post_type' => 'birs_client',
                                    'order' => 'ASC',
                                    'orderby' => 'title'
                                ),
                                array(
                                    'base_keys' => array('post_title')
                                ));
                        $clients = $query->query();
                        $client_options = array();
                        foreach ($clients as $client) {
                            $client_options[$client->ID] = $client->post_title;
                        }
                        $this->util->render_html_options($client_options);
                        ?>
                    </select>
                    <div id="birs_client_selector_status" style="display:none;">
                        <span class="update-nag"><?php _e('Loading...', 'birchschedule'); ?></span>
                    </div>
                </div>
            </li>
        </ul>
        <?php
        $html = ob_get_clean() . $html;
        return $html;
    }
}
?>

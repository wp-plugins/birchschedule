<?php

final class Birchschedule_Eadmin_Imp {

    private static $module_path;

    static function init() {
        global $birchschedule;
        
        add_action('admin_init', array(__CLASS__, 'wp_admin_init'));
        self::$module_path = $birchschedule->plugin_url() . '/modules/enhanced-admin/';
    }

    static function wp_admin_init() {
        global $birchschedule;

        $package = $birchschedule->eadmin;
        add_action('admin_enqueue_scripts', 
            array($package, 'enqueue_scripts'));
        add_action('wp_ajax_birchschedule_eadmin_load_selected_client', 
            array($package, 'ajax_load_selected_client'));
        add_filter('birchschedule_view_bookingadmin_get_client_general_html', 
            array($package, 'get_client_general_html'), 20, 2);
        add_filter('birchschedule_view_bookingadmin_get_appointment_duration_html', 
            array($package, 'get_appointment_duration_html'), 10, 2);
    }
    
    static function enqueue_scripts($hook) {
        global $birchschedule, $birchpress;

        $package = $birchschedule->eadmin;
        if($birchschedule->view->get_page_hook('calendar') !== $hook) {
            return;
        }
        self::add_scripts();
        self::add_styles();
    }

    static function ajax_load_selected_client() {
        global $birchschedule;

        if (isset($_REQUEST['birs_client_id'])) {
            $client_id = $_REQUEST['birs_client_id'];
        } else {
            $client_id = 0;
        }
        ?>
        <div>
            <div id="birs_general_section_client">
                <?php
                echo $birchschedule->view->bookingadmin->get_client_general_html($client_id);
                ?>
            </div>
            <div id="birs_client_details">
                <?php
                echo $birchschedule->view->bookingadmin->get_client_details_html($client_id);
                ?>
            </div>
        </div>
        <?php
        exit();
    }

    static function add_scripts() {
        global $birchschedule;
        $product_version = $birchschedule->product_version;
        wp_register_script('birchschedule_enhanced_admin', self::$module_path . 'assets/js/enhanced-admin.js',
            array('birs_admin_calendar', 'select2'), $product_version);
        $params = array(
            'ajax_url' => admin_url('admin-ajax.php')
        );
        wp_enqueue_script('birchschedule_enhanced_admin');
        wp_localize_script('birchschedule_enhanced_admin', 'birs_enhanced_admin_params', $params);
    }

    static function add_styles() {
        global $birchschedule;
        $product_version = $birchschedule->product_version;
        wp_register_style('birchschedule_enhanced_admin', self::$module_path . 'assets/css/enhanced-admin.css', 
            array('birchschedule_admin_styles', 'select2'), $product_version);
        wp_enqueue_style('birchschedule_enhanced_admin');
    }
    
    static function get_appointment_duration_html($html, $appointment_duration) {
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

    static function get_client_general_html($html, $client_id) {
        global $birchschedule;

        ob_start();
        ?>
        <ul>
            <li class="birs_form_field">
                <label></label>
                <div class="birs_field_content">
                    <input type="text"
                        id="birs_client_selector" 
                        data-placeholder="<?php _e('Select an Existing Client'); ?>"
                        class="populate placeholder"
                        style="width:0;" />
                    <script type="text/javascript">
                        <?php
                        $clients = $birchschedule->model->query(
                                array(
                                    'post_type' => 'birs_client',
                                    'order' => 'ASC',
                                    'orderby' => 'title'
                                ),
                                array(
                                    'base_keys' => array('post_title'),
                                    'meta_keys' => array()
                                ));
                        $client_options = array();
                        foreach ($clients as $client) {
                            $client_options[] = array(
                                    'id' => $client['ID'],
                                    'text' => $client['post_title']
                                );
                        }
                        ?>
                        var birs_client_options = <?php echo json_encode($client_options); ?>;
                    </script>
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

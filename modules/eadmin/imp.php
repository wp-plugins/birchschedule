<?php

final class Birchschedule_Eadmin_Imp {

    private static $birchschedule;

    private static $package;

    static function init_vars() {
        global $birchschedule;

        self::$birchschedule = $birchschedule;
        self::$package = $birchschedule->eadmin;
    }

    static function init() {
        add_action('admin_init', array(self::$package, 'wp_admin_init'));
        add_action('birchschedule_view_register_common_scripts_after', 
            array(self::$package, 'register_scripts'));
    }

    static function wp_admin_init() {
        add_action('birchschedule_view_enqueue_scripts_post_new_birs_appointment_after', 
            array(self::$package, 'enqueue_scripts'));

        add_action('birchschedule_view_enqueue_scripts_post_edit_birs_appointment_after', 
            array(self::$package, 'enqueue_scripts'));

        add_action('wp_ajax_birchschedule_eadmin_load_selected_client', 
            array(self::$package, 'ajax_load_selected_client'));

        add_action('wp_ajax_birchschedule_eadmin_search_clients',
            array(self::$package, 'ajax_search_clients'));

        add_action('birchschedule_view_appointments_new_render_client_info_header_after', 
            array(self::$package, 'render_client_selector'), 20);

        add_action('birchschedule_gbooking_render_client_info_header_after', 
            array(self::$package, 'render_client_selector'), 20);

    }

    static function register_scripts() {
        $version = self::$birchschedule->product_version;

        wp_register_script('birchschedule_eadmin', 
            self::$birchschedule->plugin_url() . 
            '/modules/eadmin/assets/js/base.js', 
            array('jquery-ui-autocomplete'), "$version");
    }
    
    static function enqueue_scripts() {
        self::$birchschedule->view->enqueue_scripts(
            array(
                'birchschedule_eadmin'
            )
        );
    }

    static function title_like_where($where, &$wp_query) {
        global $wpdb;
        if(isset($_REQUEST['term'])) {
            $post_title_like = $_REQUEST['term'];
            $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( like_escape( $post_title_like ) ) . '%\'';
        }
        return $where;
    }

    static function ajax_load_selected_client() {
        if (isset($_REQUEST['birs_client_id'])) {
            $client_id = $_REQUEST['birs_client_id'];
        } else {
            $client_id = 0;
        }
        echo self::$birchschedule->view->appointments->edit->clientlist->edit->get_client_info_html($client_id);
        ?>
        <script type="text/javascript">
            jQuery(function($) {
                birchschedule.eadmin.initClientInfo();
            });
        </script>
        <?php
        exit;
    }

    static function ajax_search_clients() {
        add_filter( 'posts_where', 
            array(self::$package, 'title_like_where'), 10, 2 );
        $clients = self::$birchschedule->model->query(
            array(
                'post_type' => 'birs_client',
                'orderby'=>'title',
                'order'=>'asc'
            ),
            array(
                'base_keys' => array('post_title'),
                'meta_keys' => array(
                    '_birs_client_name_first', '_birs_client_name_last'
                )
            )
        );
        remove_filter( 'posts_where', 
            array(self::$package, 'title_like_where'), 10, 2 );
        $results = array();
        foreach($clients as $client) {
            $el = array(
                'id' => $client['ID'],
                'label' => $client['post_title'],
                'value' => $client['post_title']
            );
            $results[] = $el;
        }
        $success = array(
            'code' => 'success',
            'message' => json_encode($results)
        );
        self::$birchschedule->view->render_ajax_success_message($success);
    }

    static function render_client_selector() {
        $ui_anim_url = self::$birchschedule->plugin_url() . "/assets/images/ui-anim_basic_16x16.gif";
        $placeholder = __('Search for an existing client', 'birchschedule');
        ?>
        <ul>
            <li class="birs_form_field">
                <label>&nbsp;</label>
                <div class="birs_field_content">
                    <input id="birs_client_selector" type="text" placeholder="<?php echo $placeholder; ?>">
                </div>
            </li>
        </ul>
        <style type="text/css">
            .ui-autocomplete-loading {
                background: white url('<?php echo $ui_anim_url; ?>') right center no-repeat;
            }
            .ui-autocomplete {
                max-height: 100px;
                overflow-y: auto;
                /* prevent horizontal scrollbar */
                overflow-x: hidden;
            }
        </style>
        <?php
    }

}

Birchschedule_Eadmin_Imp::init_vars();

?>

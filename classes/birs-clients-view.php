<?php

class BIRS_Clients_View extends BIRS_Content_View {

    function __construct() {
        parent::__construct();
    }

    function get_content_type() {
        return 'birs_client';
    }

    function init() {
        parent::init();
        register_post_type($this->get_content_type(), array(
            'labels' => array(
                'name' => __('Clients', 'birchschedule'),
                'singular_name' => __('Client', 'birchschedule'),
                'add_new' => __('Add Client', 'birchschedule'),
                'add_new_item' => __('Add New Client', 'birchschedule'),
                'edit' => __('Edit', 'birchschedule'),
                'edit_item' => __('Edit Client', 'birchschedule'),
                'new_item' => __('New Client', 'birchschedule'),
                'view' => __('View Client', 'birchschedule'),
                'view_item' => __('View Client', 'birchschedule'),
                'search_items' => __('Search Clients', 'birchschedule'),
                'not_found' => __('No Clients found', 'birchschedule'),
                'not_found_in_trash' => __('No Clients found in trash', 'birchschedule'),
                'parent' => __('Parent Client', 'birchschedule')
            ),
            'description' => __('This is where clients are stored.', 'birchschedule'),
            'public' => false,
            'show_ui' => true,
            'capability_type' => 'post',
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_in_menu' => 'birchschedule_schedule',
            'hierarchical' => false,
            'show_in_nav_menus' => false,
            'rewrite' => false,
            'query_var' => true,
            'supports' => array('custom-fields'),
            'has_archive' => false
                )
        );
    }

    function get_admin_scripts() {
        $scripts = parent::get_admin_scripts();
        $scripts['birs_admin_client'] = array(
            'source' => 'client.js',
            'depends' => array('birs_admin_common'),
            'version' => '1.0'
        );
        return $scripts;
    }

    function get_edit_columns($columns) {
        $columns = array();

        $columns["cb"] = "<input type=\"checkbox\" />";
        $columns["title"] = __("Client Name", 'birchschedule');
        $columns["birs_client_phone"] = __("Phone", 'birchschedule');
        $columns["birs_client_email"] = __("Email", 'birchschedule');
        $columns["birs_client_address"] = __("Address", 'birchschedule');
        return $columns;
    }

    function render_custom_columns($column) {
        global $post;

        if ($column === "birs_client_address") {
            $address1 = get_post_meta($post->ID, '_birs_client_address1', true);
            $address2 = get_post_meta($post->ID, '_birs_client_address2', true);
            $value = $address1 . '<br>' . $address2;
        } else {
            $value = get_post_meta($post->ID, '_' . $column, true);
        }

        echo $value;
    }

    function pre_save_content($data) {
        if (isset($_POST['birs_client_name_first'])) {
            $first_name = $_POST['birs_client_name_first'];
        } else {
            $first_name = '';
        }
        if (isset($_POST['birs_client_name_last'])) {
            $last_name = $_POST['birs_client_name_last'];
        } else {
            $last_name = '';
        }
        $data['post_title'] = $first_name . ' ' . $last_name;
        if (isset($_POST['birs_client_country']) && $_POST['birs_client_country'] != 'US') {
            $_POST['birs_client_state'] = $_POST['birs_client_province'];
        }
        return $data;
    }

    function process_content($post_id, $post) {
        $this->save_field_string($post_id, 'birs_client_title');
        $this->save_field_string($post_id, 'birs_client_name_first');
        $this->save_field_string($post_id, 'birs_client_name_last');
        $this->save_field_string($post_id, 'birs_client_phone');
        $this->save_field_string($post_id, 'birs_client_email');
        $this->save_field_string($post_id, 'birs_client_address1');
        $this->save_field_string($post_id, 'birs_client_address2');
        $this->save_field_string($post_id, 'birs_client_city');
        $this->save_field_string($post_id, 'birs_client_state');
        $this->save_field_string($post_id, 'birs_client_country');
        $this->save_field_string($post_id, 'birs_client_zip');
        $this->handle_errors();
    }

    function get_updated_messages($messages) {
        global $post, $post_ID;

        $messages['birs_client'] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __('Client updated.', 'birchschedule'),
            2 => __('Custom field updated.', 'birchschedule'),
            3 => __('Custom field deleted.', 'birchschedule'),
            4 => __('Client updated.', 'birchschedule'),
            5 => isset($_GET['revision']) ? sprintf(__('Client restored to revision from %s', 'birchschedule'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6 => __('Client updated.', 'birchschedule'),
            7 => __('Client saved.', 'birchschedule'),
            8 => __('Client submitted.', 'birchschedule'),
            9 => sprintf(__('Client scheduled for: <strong>%1$s</strong>.', 'birchschedule'), date_i18n(__('M j, Y @ G:i', 'birchschedule'), strtotime($post->post_date))),
            10 => __('Client draft updated.', 'birchschedule')
        );

        return $messages;
    }

    function create_admin_panel() {
        parent::create_admin_panel();
        remove_meta_box('postcustom', 'birs_client', 'normal');
        add_meta_box('birchschedule-client-info', __('Client Info', 'birchschedule'), array(&$this, 'render_client_info'), 'birs_client', 'normal', 'high');
    }

    function select_option($expect, $real) {
        if ($expect === $real) {
            echo ' selected="selected" ';
        }
    }

    function render_client_info($post) {
        $post_id = $post->ID;
        $client_titles = $this->get_util()->get_client_title_options();
        $client_title = get_post_meta($post_id, '_birs_client_title', true);
        $first_name = get_post_meta($post_id, '_birs_client_name_first', true);
        $last_name = get_post_meta($post_id, '_birs_client_name_last', true);
        $addresss1 = get_post_meta($post_id, '_birs_client_address1', true);
        $addresss2 = get_post_meta($post_id, '_birs_client_address2', true);
        $email = get_post_meta($post_id, '_birs_client_email', true);
        $phone = get_post_meta($post_id, '_birs_client_phone', true);
        $city = get_post_meta($post_id, '_birs_client_city', true);
        $zip = get_post_meta($post_id, '_birs_client_zip', true);
        $state = get_post_meta($post_id, '_birs_client_state', true);
        $country = get_post_meta($post_id, '_birs_client_country', true);
        $states = $this->get_util()->get_us_states();
        $countries = $this->get_util()->get_countries();
        ?>
        <div class="panel-wrap birchschedule">
            <table class="form-table">
                <tr class="form-field">
                    <th><label><?php _e('Title', 'birchschedule'); ?> </label>
                    </th>
                    <td>
                        <select id="birs_client_title" name="birs_client_title">
                            <?php $this->get_util()->render_html_options($client_titles, $client_title); ?>
                        </select>
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('First Name', 'birchschedule'); ?> </label>
                    </th>
                    <td><input type="text" name="birs_client_name_first" id="birs_client_name_first" value="<?php echo esc_attr($first_name); ?>">
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('Last Name', 'birchschedule'); ?> </label>
                    </th>
                    <td><input type="text" name="birs_client_name_last" id="birs_client_name_last" value="<?php echo esc_attr($last_name); ?>">
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('Phone Number', 'birchschedule'); ?> </label>
                    </th>
                    <td><input type="text" name="birs_client_phone"
                               id="birs_client_phone" value="<?php echo esc_attr($phone); ?>">
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('Email', 'birchschedule'); ?> </label>
                    </th>
                    <td><input type="text" name="birs_client_email"
                               id="birs_client_email" value="<?php echo esc_attr($email); ?>">
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('Address', 'birchschedule'); ?> </label>
                    </th>
                    <td><input type="text" name="birs_client_address1"
                               id="birs_client_address1"
                               value="<?php echo esc_attr($addresss1); ?>"> <br> <input type="text"
                               name="birs_client_address2" id="birs_client_address2"
                               value="<?php echo esc_attr($addresss2); ?>">
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('City', 'birchschedule'); ?> </label>
                    </th>
                    <td><input type="text" name="birs_client_city"
                               id="birs_client_city" value="<?php echo esc_attr($city); ?>">
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('State/Province', 'birchschedule'); ?> </label>
                    </th>
                    <td>
                        <select name="birs_client_state" id="birs_client_state">
                            <?php $this->get_util()->render_html_options($states, $state); ?>
                        </select>
                        <input type="text" name="birs_client_province" id="birs_client_province" value="<?php echo esc_attr($state); ?>" style="display: none;">
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('Country', 'birchschedule'); ?></label></th>
                    <td>
                        <select name="birs_client_country" id="birs_client_country">
                            <?php $this->get_util()->render_html_options($countries, $country, 'US'); ?>
                        </select>
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('Zip Code', 'birchschedule'); ?> </label>
                    </th>
                    <td><input type="text" name="birs_client_zip"
                               id="birs_client_zip" value="<?php echo esc_attr($zip); ?>">
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

}
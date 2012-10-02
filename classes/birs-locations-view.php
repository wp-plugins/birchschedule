<?php

class BIRS_Locations_View extends BIRS_Content_View {

    function __construct() {
        parent::__construct();
    }

    function get_content_type() {
        return 'birs_location';
    }

    function init() {
        parent::init();
        register_post_type($this->get_content_type(), array(
            'labels' => array(
                'name' => __('Locations', 'birchschedule'),
                'singular_name' => __('Location', 'birchschedule'),
                'add_new' => __('Add Location', 'birchschedule'),
                'add_new_item' => __('Add New Location', 'birchschedule'),
                'edit' => __('Edit', 'birchschedule'),
                'edit_item' => __('Edit Location', 'birchschedule'),
                'new_item' => __('New Location', 'birchschedule'),
                'view' => __('View Location', 'birchschedule'),
                'view_item' => __('View Location', 'birchschedule'),
                'search_items' => __('Search Locations', 'birchschedule'),
                'not_found' => __('No Locations found', 'birchschedule'),
                'not_found_in_trash' => __('No Locations found in trash', 'birchschedule'),
                'parent' => __('Parent Location', 'birchschedule')
            ),
            'description' => __('This is where locations are stored.', 'birchschedule'),
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
            'supports' => array('title'),
            'has_archive' => false
                )
        );
    }

    function get_admin_scripts() {
        $scripts = parent::get_admin_scripts();
        $scripts['birs_admin_location'] = array(
            'source' => 'location.js',
            'depends' => array('birs_admin_common'),
            'version' => '1.0'
        );
        return $scripts;
    }

    function get_edit_columns($columns) {
        $columns = array();

        $columns["cb"] = "<input type=\"checkbox\" />";
        $columns["title"] = __("Location Name", 'birchschedule');
        $columns["birs_location_address"] = __("Address", 'birchschedule');
        $columns["birs_location_city"] = __("City", 'birchschedule');
        $columns["birs_location_state"] = __("State/Province", 'birchschedule');
        return $columns;
    }

    function render_custom_columns($column) {
        global $post;

        if ($column === "birs_location_address") {
            $address1 = get_post_meta($post->ID, '_birs_location_address1', true);
            $address2 = get_post_meta($post->ID, '_birs_location_address2', true);
            $value = $address1 . '<br>' . $address2;
        } else {
            $value = get_post_meta($post->ID, '_' . $column, true);
        }

        if ($column === 'birs_location_state') {
            $states = $this->get_util()->get_us_states();
            if (isset($states[$value])) {
                $value = $states[$value];
            } else {
                $value = '';
            }
        }
        echo $value;
    }

    function pre_save_content($data) {
        if (isset($_POST['birs_location_country']) && $_POST['birs_location_country'] != 'US') {
            $_POST['birs_location_state'] = $_POST['birs_location_province'];
        }
        return $data;
    }

    function process_content($post_id, $post) {
        $this->save_field_string($post_id, 'birs_location_phone');
        $this->save_field_string($post_id, 'birs_location_address1');
        $this->save_field_string($post_id, 'birs_location_address2');
        $this->save_field_string($post_id, 'birs_location_city');
        $this->save_field_string($post_id, 'birs_location_state');
        $this->save_field_string($post_id, 'birs_location_country');
        $this->save_field_string($post_id, 'birs_location_zip');
        $this->handle_errors();
    }

    function get_updated_messages($messages) {
        global $post, $post_ID;

        $messages['birs_location'] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __('Location updated.', 'birchschedule'),
            2 => __('Custom field updated.', 'birchschedule'),
            3 => __('Custom field deleted.', 'birchschedule'),
            4 => __('Location updated.', 'birchschedule'),
            5 => isset($_GET['revision']) ? sprintf(__('Location restored to revision from %s', 'birchschedule'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6 => __('Location updated.', 'birchschedule'),
            7 => __('Location saved.', 'birchschedule'),
            8 => __('Location submitted.', 'birchschedule'),
            9 => sprintf(__('Location scheduled for: <strong>%1$s</strong>.', 'birchschedule'), date_i18n(__('M j, Y @ G:i', 'birchschedule'), strtotime($post->post_date))),
            10 => __('Location draft updated.', 'birchschedule')
        );

        return $messages;
    }

    function create_admin_panel() {
        parent::create_admin_panel();
        add_meta_box('birchschedule-location-info', __('Location Details', 'birchschedule'), array(&$this, 'render_location_info'), 'birs_location', 'normal', 'high');
    }

    function select_option($expect, $real) {
        if ($expect === $real) {
            echo ' selected="selected" ';
        }
    }

    function render_location_info($post) {
        $post_id = $post->ID;
        $addresss1 = get_post_meta($post_id, '_birs_location_address1', true);
        $addresss2 = get_post_meta($post_id, '_birs_location_address2', true);
        $phone = get_post_meta($post_id, '_birs_location_phone', true);
        $city = get_post_meta($post_id, '_birs_location_city', true);
        $zip = get_post_meta($post_id, '_birs_location_zip', true);
        $state = get_post_meta($post_id, '_birs_location_state', true);
        $country = get_post_meta($post_id, '_birs_location_country', true);
        $states = $this->get_util()->get_us_states();
        $countries = $this->get_util()->get_countries();
        ?>
        <div class="panel-wrap birchschedule">
            <table class="form-table">
                <tr class="form-field">
                    <th><label><?php _e('Phone Number', 'birchschedule'); ?> </label>
                    </th>
                    <td><input type="text" name="birs_location_phone"
                               id="birs_location_phone" value="<?php echo esc_attr($phone); ?>">
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('Address', 'birchschedule'); ?> </label>
                    </th>
                    <td><input type="text" name="birs_location_address1"
                               id="birs_location_address1"
                               value="<?php echo esc_attr($addresss1); ?>"> <br> <input type="text"
                               name="birs_location_address2" id="birs_location_address2"
                               value="<?php echo esc_attr($addresss2); ?>">
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('City', 'birchschedule'); ?> </label>
                    </th>
                    <td><input type="text" name="birs_location_city"
                               id="birs_location_city" value="<?php echo esc_attr($city); ?>">
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('State/Province', 'birchschedule'); ?> </label>
                    </th>
                    <td>
                        <select name="birs_location_state" id="birs_location_state">
                            <?php $this->get_util()->render_html_options($states, $state); ?>
                        </select>
                        <input type="text" name="birs_location_province" id="birs_location_province" value="<?php echo esc_attr($state); ?>" style="display: none;">
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('Country', 'birchschedule'); ?></label></th>
                    <td>
                        <select name="birs_location_country" id="birs_location_country">
                            <?php $this->get_util()->render_html_options($countries, $country, 'US'); ?>
                        </select>
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('Zip Code', 'birchschedule'); ?> </label>
                    </th>
                    <td><input type="text" name="birs_location_zip"
                               id="birs_location_zip" value="<?php echo esc_attr($zip); ?>">
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

}
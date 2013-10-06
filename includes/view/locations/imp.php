<?php

class Birchschedule_View_Locations_Imp {

	private function __construct() {
		
	}

	static function init() {
		global $birchschedule;

		$birchschedule->view->locations->
			add_action('admin_init', 'wp_admin_init');
        $birchschedule->view->locations->
            add_action('init', 'wp_init');
	}

    static function wp_init() {
        self::register_post_type();
    }

	static function wp_admin_init() {
        global $birchschedule;
        
        $package = $birchschedule->view->locations;
        $package->add_filter('manage_edit-birs_location_columns', 'get_edit_columns');
        $package->add_action('manage_birs_location_posts_custom_column', 'render_custom_columns', 2);
	}

	static function register_post_type() {
        register_post_type('birs_location', array(
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

    static function get_edit_columns($columns) {
        $columns = array();

        $columns["cb"] = "<input type=\"checkbox\" />";
        $columns["title"] = __("Location Name", 'birchschedule');
        $columns["birs_location_address"] = __("Address", 'birchschedule');
        $columns["birs_location_city"] = __("City", 'birchschedule');
        $columns["birs_location_state"] = __("State/Province", 'birchschedule');
        return $columns;
    }

    static function get_updated_messages($messages) {
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

    static function render_custom_columns($column) {
        global $post, $birchpress;

        if ($column === "birs_location_address") {
            $address1 = get_post_meta($post->ID, '_birs_location_address1', true);
            $address2 = get_post_meta($post->ID, '_birs_location_address2', true);
            $value = $address1 . '<br>' . $address2;
        } else {
            $value = get_post_meta($post->ID, '_' . $column, true);
        }

        if ($column === 'birs_location_state') {
            $states = $birchpress->util->get_us_states();
            if (isset($states[$value])) {
                $value = $states[$value];
            } else {
                $value = '';
            }
        }
        echo $value;
    }

    static function load_page_edit_birs_location($arg) {
        birch_assert(is_array($arg) && isset($arg['post_type']) && 
            $arg['post_type'] == 'birs_location');
        
        global $birchschedule;

        $birchschedule->view->locations->
            add_action('add_meta_boxes', 'add_meta_boxes');
        $birchschedule->view->locations->
            add_filter('post_updated_messages', 'get_updated_messages');
    }

    static function enqueue_scripts_edit_birs_location($arg) {
        birch_assert(is_array($arg) && isset($arg['post_type']) && 
            $arg['post_type'] == 'birs_location');

        global $birchschedule;

        $birchschedule->view->enqueue_scripts(array('birs_admin_location'));
        $birchschedule->view->enqueue_styles(array('birchschedule_admin_styles'));
    }

    static function pre_save_location($data, $post_attr) {
        if (isset($_POST['birs_location_country']) && $_POST['birs_location_country'] != 'US') {
            $_POST['birs_location_state'] = $_POST['birs_location_province'];
        }
        return $data;
    }

    static function save_location($post) {
        birch_assert(is_array($post) && isset($post['post_type']) && 
            $post['post_type'] == 'birs_location');
        global $birchschedule;
        $config = array(
            'meta_keys' => array(
                '_birs_location_phone', '_birs_location_address1',
                '_birs_location_address2', '_birs_location_city',
                '_birs_location_state', '_birs_location_country',
                '_birs_location_zip'
            ),
            'base_keys' => array()
        );
        $post_data = 
            $birchschedule->view->merge_request($post, $config);
        $birchschedule->model->save($post_data, $config);
    }


    static function add_meta_boxes() {
        global $birchschedule;

        $package = $birchschedule->view->locations;
        remove_meta_box('slugdiv', 'birs_location', 'normal');
        remove_meta_box('postcustom', 'birs_location', 'normal');
        add_meta_box('birchschedule-location-info', __('Location Details', 'birchschedule'), 
            array($package, 'render_location_info'), 'birs_location', 'normal', 'high');
    }

    static function render_location_info($post) {
        global $birchpress;

        $post_id = $post->ID;
        $addresss1 = get_post_meta($post_id, '_birs_location_address1', true);
        $addresss2 = get_post_meta($post_id, '_birs_location_address2', true);
        $phone = get_post_meta($post_id, '_birs_location_phone', true);
        $city = get_post_meta($post_id, '_birs_location_city', true);
        $zip = get_post_meta($post_id, '_birs_location_zip', true);
        $state = get_post_meta($post_id, '_birs_location_state', true);
        $country = get_post_meta($post_id, '_birs_location_country', true);
        $states = $birchpress->util->get_us_states();
        $countries = $birchpress->util->get_countries();
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
                            <?php $birchpress->util->render_html_options($states, $state); ?>
                        </select>
                        <input type="text" name="birs_location_province" id="birs_location_province" value="<?php echo esc_attr($state); ?>" style="display: none;">
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('Country', 'birchschedule'); ?></label></th>
                    <td>
                        <select name="birs_location_country" id="birs_location_country">
                            <?php $birchpress->util->render_html_options($countries, $country, 'US'); ?>
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
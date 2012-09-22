<?php

class BIRS_Services_View extends BIRS_Content_View {

    private $padding_types;
    private $length_types;
    private $price_types;

    function __construct() {
        parent::__construct();
        $this->length_types = array('minutes' => __('minutes', 'birchschedule'),
            'hours' => __('hours', 'birchschedule'));
        $this->padding_types = array('before' => __('Before', 'birchschedule'),
            'after' => __('After', 'birchschedule'),
            'before-and-after' => __('Before & After', 'birchschedule'));
        $this->price_types = array('fixed' => __('Fixed', 'birchschedule'),
            'free' => __('Free', 'birchschedule'),
            'varies' => __('Varies', 'birchschedule'),
            'dont-show' => __('Don\'t show', 'birchschedule'));
    }

    function init() {
        parent::init();
        register_post_type('birs_service', array(
            'labels' => array(
                'name' => __('Services', 'birchschedule'),
                'singular_name' => __('Service', 'birchschedule'),
                'add_new' => __('Add Service', 'birchschedule'),
                'add_new_item' => __('Add New Service', 'birchschedule'),
                'edit' => __('Edit', 'birchschedule'),
                'edit_item' => __('Edit Service', 'birchschedule'),
                'new_item' => __('New Service', 'birchschedule'),
                'view' => __('View Service', 'birchschedule'),
                'view_item' => __('View Service', 'birchschedule'),
                'search_items' => __('Search Services', 'birchschedule'),
                'not_found' => __('No Services found', 'birchschedule'),
                'not_found_in_trash' => __('No Services found in trash', 'birchschedule'),
                'parent' => __('Parent Service', 'birchschedule')
            ),
            'description' => __('This is where services are stored.', 'birchschedule'),
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
            'supports' => array('title', 'editor'),
            'has_archive' => false
                )
        );
    }
    
    function get_price_type_text_map(){
        return $this->price_types;
    }

    function get_content_type() {
        return 'birs_service';
    }

    function get_updated_messages($messages) {
        global $post, $post_ID;

        $messages['birs_service'] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __('Service updated.', 'birchschedule'),
            2 => __('Custom field updated.', 'birchschedule'),
            3 => __('Custom field deleted.', 'birchschedule'),
            4 => __('Service updated.', 'birchschedule'),
            5 => isset($_GET['revision']) ? sprintf(__('Service restored to revision from %s', 'birchschedule'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6 => __('Service updated.', 'birchschedule'),
            7 => __('Service saved.', 'birchschedule'),
            8 => __('Service submitted.', 'birchschedule'),
            9 => sprintf(__('Service scheduled for: <strong>%1$s</strong>.', 'birchschedule'), date_i18n(__('M j, Y @ G:i', 'birchschedule'), strtotime($post->post_date))),
            10 => __('Service draft updated.', 'birchschedule')
        );

        return $messages;
    }

    function get_admin_scripts() {
        return array('birs_admin_service' => array('source' => 'service.js',
                'depends' => array('jquery'),
                'version' => '1.0'));
    }

    function process_content($post_id, $post) {
        $this->save_field_int($post_id, 'birs_service_length');
        $this->save_field_string($post_id, 'birs_service_length_type');
        $this->save_field_int($post_id, 'birs_service_padding');
        $this->save_field_string($post_id, 'birs_service_padding_type');
        $this->save_field_int($post_id, 'birs_service_price');
        $this->save_field_string($post_id, 'birs_service_price_type');
        $this->save_content_relations($post_id, 'birs_staff', 'birs_assigned_staff', 'birs_assigned_services');
        $this->handle_errors();
    }

    function get_edit_columns($columns) {
        $columns = array();

        $columns["cb"] = "<input type=\"checkbox\" />";
        $columns["title"] = __("Service Name", 'birchschedule');
        $columns["description"] = __("Description", 'birchschedule');
        return $columns;
    }

    function create_admin_panel() {
        parent::create_admin_panel();
        add_meta_box('birchschedule-service-info', __('Service Settings', 'birchschedule'), array(&$this, 'render_service_info'), 'birs_service', 'normal', 'high');
        add_meta_box('birchschedule-service-staff', __('Staff', 'birchschedule'), array(&$this, 'render_assign_staff'), 'birs_service', 'side', 'default');
    }

    function render_service_info($post) {
        $post_id = $post->ID;
        $length = get_post_meta($post_id, '_birs_service_length', true);
        $length_type = get_post_meta($post_id, '_birs_service_length_type', true);
        $padding = get_post_meta($post_id, '_birs_service_padding', true);
        $padding_type = get_post_meta($post_id, '_birs_service_padding_type', true);
        $price = get_post_meta($post_id, '_birs_service_price', true);
        $price_type = get_post_meta($post_id, '_birs_service_price_type', true);
        ?>
        <div class="panel-wrap birchschedule">
            <table class="form-table">
                <tr class="form-field">
                    <th><label><?php _e('Length', 'birchschedule'); ?> </label>
                    </th>
                    <td><input type="text" name="birs_service_length"
                               id="birs_service_length" value="<?php echo $length; ?>"> <select
                               name="birs_service_length_type">
                                   <?php $this->render_select_options($this->length_types, $length_type); ?>
                        </select>
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php _e('Padding', 'birchschedule'); ?> </label>
                    </th>
                    <td><input type="text" name="birs_service_padding"
                               id="birs_service_padding" value="<?php echo $padding; ?>"> <span><?php echo _e('min padding time', 'birchschedule'); ?>
                        </span> <select name="birs_service_padding_type">
                            <?php $this->render_select_options($this->padding_types, $padding_type) ?>
                        </select>
                    </td>
                </tr>
                <tr class="form-field">
                    <th><label><?php echo _e('Price', 'birchschedule'); ?> </label></th>
                    <td><select name="birs_service_price_type" id="birs_service_price_type">
                            <?php $this->render_select_options($this->price_types, $price_type); ?>
                        </select> <span>$</span> 
                        <input type="text" 
                               name="birs_service_price" id="birs_service_price" value="<?php echo $price; ?>">
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    function render_staff_checkboxes($staff, $assigned_staff) {
        foreach ($staff as $thestaff) {
            if (array_key_exists($thestaff->ID, $assigned_staff)) {
                $checked = 'checked="checked"';
            } else {
                $checked = '';
            }
            echo '<li><label>' .
            "<input type=\"checkbox\" " .
            "name=\"birs_assigned_staff[$thestaff->ID]\" $checked >" .
            $thestaff->post_title .
            '</label></li>';
        }
    }

    function render_assign_staff($post) {
        $staff = get_posts(array('post_type' => 'birs_staff'));
        $assigned_staff = get_post_meta($post->ID, '_birs_assigned_staff', true);
        $assigned_staff = unserialize($assigned_staff);
        if ($assigned_staff === false) {
            $assigned_staff = array();
        }
        ?>
        <div class="panel-wrap birchschedule">
            <?php if (sizeof($staff) > 0): ?>
                <p><?php _e('Assign staff that can perform this service:', 'birchschedule'); ?></p>
                <div><ul>
                        <?php $this->render_staff_checkboxes($staff, $assigned_staff); ?>
                    </ul></div>
            <?php else: ?>
                <p>
                    <?php
                    _e('There is no staff to assign. Click <a
                        href="post-new.php?post_type=birs_staff">here</a> to add one.', 'birchschedule');
                    ?>
                </p>
                <?php endif; ?>
        </div>
            <?php
        }

    }
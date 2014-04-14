<?php

class Birchschedule_View_Staff_Imp {

	private function __construct() {
		
	}

	static function init() {
		global $birchschedule;

        $package = $birchschedule->view->staff;
		add_action('admin_init', array($package, 'wp_admin_init'));
        add_action('init', array($package, 'wp_init'));
    }

    static function wp_init() {
        self::register_post_type();
    }

	static function wp_admin_init() {
        global $birchschedule;

        $package = $birchschedule->view->staff;
        add_action('wp_ajax_birchschedule_view_staff_new_schedule', 
            array($package, 'ajax_new_schedule'));
        add_filter('manage_edit-birs_staff_columns', array($package, 'get_edit_columns'));
        add_action('manage_birs_staff_posts_custom_column', array($package, 'render_custom_columns'), 2);
    }

	static function register_post_type() {
        register_post_type('birs_staff', array(
            'labels' => array(
                'name' => __('Staff', 'birchschedule'),
                'singular_name' => __('Staff', 'birchschedule'),
                'add_new' => __('Add Staff', 'birchschedule'),
                'add_new_item' => __('Add New Staff', 'birchschedule'),
                'edit' => __('Edit', 'birchschedule'),
                'edit_item' => __('Edit Staff', 'birchschedule'),
                'new_item' => __('New Staff', 'birchschedule'),
                'view' => __('View Staff', 'birchschedule'),
                'view_item' => __('View Staff', 'birchschedule'),
                'search_items' => __('Search Staff', 'birchschedule'),
                'not_found' => __('No Staff found', 'birchschedule'),
                'not_found_in_trash' => __('No Staff found in trash', 'birchschedule'),
                'parent' => __('Parent Staff', 'birchschedule')
            ),
            'description' => __('This is where staff are stored.', 'birchschedule'),
            'public' => false,
            'show_ui' => true,
            'capability_type' => 'birs_staff',
            'map_meta_cap' => true,
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

    static function load_page_edit_birs_staff($arg) {
        birch_assert(is_array($arg) && isset($arg['post_type']) && 
            $arg['post_type'] == 'birs_staff');
        
        global $birchschedule;

        $package = $birchschedule->view->staff;
        add_action('add_meta_boxes', array($package, 'add_meta_boxes'));
        add_filter('post_updated_messages', array($package, 'get_updated_messages'));
    }

    static function enqueue_scripts_edit_birs_staff($arg) {
        birch_assert(is_array($arg) && isset($arg['post_type']) && 
            $arg['post_type'] == 'birs_staff');

        global $birchschedule;

        $birchschedule->view->register_3rd_scripts();
        $birchschedule->view->register_3rd_styles();
        $birchschedule->view->enqueue_scripts(
            array(
                'birchschedule_view_staff_edit', 'birchschedule_model',
                'birchschedule_view_admincommon', 'birchschedule_view'
            )
        );
        $birchschedule->view->enqueue_styles(
            array(
                'birchschedule_admincommon', 'birchschedule_staff_edit',
                'jquery-ui-bootstrap', 'jquery-ui-no-theme'
            )
        );
    }

    static function get_edit_columns($columns) {
        $columns = array();

        $columns["cb"] = "<input type=\"checkbox\" />";
        $columns["title"] = __("Staff Name", 'birchschedule');
        $columns["description"] = __("Description", 'birchschedule');
        return $columns;
    }

    static function render_custom_columns($column) {
        global $post;

        if ($column == 'description') {
            the_content();
            return;
        }
        $value = get_post_meta($post->ID, '_' . $column, true);

        echo $value;
    }

    static function get_updated_messages($messages) {
        global $post, $post_ID;

        $messages['birs_staff'] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __('Staff updated.', 'birchschedule'),
            2 => __('Custom field updated.', 'birchschedule'),
            3 => __('Custom field deleted.', 'birchschedule'),
            4 => __('Staff updated.', 'birchschedule'),
            5 => isset($_GET['revision']) ? sprintf(__('Staff restored to revision from %s', 'birchschedule'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6 => __('Staff updated.', 'birchschedule'),
            7 => __('Staff saved.', 'birchschedule'),
            8 => __('Staff submitted.', 'birchschedule'),
            9 => sprintf(__('Staff scheduled for: <strong>%1$s</strong>.', 'birchschedule'), date_i18n(__('M j, Y @ G:i', 'birchschedule'), strtotime($post->post_date))),
            10 => __('Staff draft updated.', 'birchschedule')
        );

        return $messages;
    }

    static function add_meta_boxes() {
        global $birchschedule;

        $package = $birchschedule->view->staff;
        remove_meta_box('slugdiv', 'birs_staff', 'normal');
        remove_meta_box('postcustom', 'birs_staff', 'normal');
        add_meta_box('birchschedule-work-schedule', __('Work Schedule', 'birchschedule'), 
            array($package, 'render_work_schedule'), 'birs_staff', 'normal', 'default');
        add_meta_box('birchschedule-staff-services', __('Services', 'birchschedule'), 
            array($package, 'render_assign_services'), 'birs_staff', 'side', 'default');
    }

    static function ajax_new_schedule() {
        global $birchschedule;

        $location_id = $_POST['birs_location_id'];
        $uid = uniqid();
        $schedule = array(
            'minutes_start' => 540,
            'minutes_end' => 1020,
            'weeks' => array(
                1 => 'on',
                2 => 'on',
                3 => 'on',
                4 => 'on',
                5 => 'on'
            )
        );
        $birchschedule->view->staff->render_schedule($location_id, $uid, $schedule);
        die;
    }

    static function pre_save_staff($post_data, $post_attr) {
        birch_assert(is_array($post_data) && isset($post_data['post_type']) && 
            $post_data['post_type'] == 'birs_staff');
        global $birchschedule;

        $user = $birchschedule->model->get_user_by_staff($post_attr['ID']);
        if($user) {
            $post_data['post_author'] = $user->ID;
        }
        return $post_data;
    }

    static function save_staff($post) {
        global $birchschedule;
        
        $config = array(
            'base_keys' => array(),
            'meta_keys' => array(
                '_birs_staff_schedule', '_birs_assigned_services'
            )
        );
        $staff_data = 
            $birchschedule->view->merge_request($post, $config, $_POST);
        if(!isset($_POST['_birs_assigned_services'])) {
            $staff_data['_birs_assigned_services'] = array();
        }
        $birchschedule->model->save($staff_data, $config);
        $birchschedule->model->update_model_relations($post['ID'], '_birs_assigned_services',
            'birs_service', '_birs_assigned_staff');
    }

    static function get_schedule_interval() {
        return 5;
    }

    static function render_schedule($location_id, $uid, $schedule) {
        global $birchschedule, $birchpress;

        $interval = $birchschedule->view->staff->get_schedule_interval();
        $time_options = $birchpress->util->get_time_options($interval);
        $start = $schedule['minutes_start'];
        $end = $schedule['minutes_end'];
        $weeks = $birchpress->util->get_weekdays_short();
        $start_of_week = $birchpress->util->get_first_day_of_week();
        ?>
        <ul>
            <li>
                <span class="birs_schedule_field_label"><?php _e('From', 'birchschedule'); ?></span>
                <div class="birs_schedule_field_content">
                    <select
                        name="birs_staff_schedule[<?php echo $location_id; ?>][schedules][<?php echo $uid; ?>][minutes_start]">
                            <?php $birchpress->util->render_html_options($time_options, $start); ?>
                    </select>
                    <a href="javascript:void(0);"
                        data-schedule-id="<?php echo $uid; ?>"
                        class="birs_schedule_delete">
                        <?php echo "Delete"; ?>
                    </a>
                </div>
            </li>
            <li>
                <span class="birs_schedule_field_label"><?php _e('To', 'birchschedule'); ?></span>
                <div class="birs_schedule_field_content">
                    <select
                        name="birs_staff_schedule[<?php echo $location_id; ?>][schedules][<?php echo $uid; ?>][minutes_end]">
                            <?php $birchpress->util->render_html_options($time_options, $end); ?>
                    </select>
                </div>
            </li>
            <li>
                <span class="birs_schedule_field_label"></span>
                <div class="birs_schedule_field_content">
                <?php
                    foreach($weeks as $week_value => $week_name): 
                        if($week_value < $start_of_week) {
                            continue;
                        }
                        if (isset($schedule['weeks']) && isset($schedule['weeks'][$week_value])) {
                            $checked_attr = ' checked="checked" ';
                        } else {
                            $checked_attr = '';
                        }
                ?>
                    <label>
                        <input type="checkbox" 
                            name="birs_staff_schedule[<?php echo $location_id; ?>][schedules][<?php echo $uid; ?>][weeks][<?php echo $week_value; ?>]"
                            <?php echo $checked_attr; ?>/>
                            <?php echo $week_name; ?>
                    </label>
                <?php endforeach; ?>
                <?php
                    foreach($weeks as $week_value => $week_name): 
                        if($week_value >= $start_of_week) {
                            continue;
                        }
                        if (isset($schedule['weeks']) && isset($schedule['weeks'][$week_value])) {
                            $checked_attr = ' checked="checked" ';
                        } else {
                            $checked_attr = '';
                        }
                ?>
                    <label>
                        <input type="checkbox" 
                            name="birs_staff_schedule[<?php echo $location_id; ?>][schedules][<?php echo $uid; ?>][weeks][<?php echo $week_value; ?>]"
                            <?php echo $checked_attr; ?>/>
                            <?php echo $week_name; ?>
                    </label>
                <?php endforeach; ?>
                </div>
            </li>
        </ul>
        <?php
    }

    static function render_timetable($staff_id, $location_id) {
        global $birchschedule;

        $location_schedule = $birchschedule->model->
            get_staff_schedule_by_location($staff_id, $location_id);
        if(isset($location_schedule['schedules'])) {
            $schedules = $location_schedule['schedules'];
        } else {
            $schedules = array();
        }
        ?>
        <div style="margin-bottom:20px;">
            <div id="<?php echo 'birs_schedule_' . $location_id ?>">
            <?php 
            foreach($schedules as $uid => $schedule): 
                $schedule_dom_id = 'birs_schedule_' . $uid;
            ?>
                <div id="<?php echo $schedule_dom_id; ?>"
                    class="birs_schedule_item">
                <?php 
                    $birchschedule->view->staff->render_schedule($location_id, $uid, $schedule); 
                ?>
                </div>
                <script type="text/javascript">
                    jQuery(document).ready( function($) {
                        var scheduleId = '<?php echo $schedule_dom_id; ?>';
                        $('#' + scheduleId + ' .birs_schedule_delete').click(function() {
                            $('#' + scheduleId).remove();
                        });
                    });
                </script>
            <?php endforeach; ?>
            </div>
            <div class="birs_schedule_new_box">
                <a href="javascript:void(0);" 
                    class="birs_schedule_new"
                    data-location-id="<?php echo $location_id; ?>">
                    <?php _e('+ Add Schedule', 'birchschedule'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    static function render_work_schedule($post) {
        global $birchpress, $birchschedule;

        $weeks = $birchpress->util->get_weekdays_short();
        $locations = get_posts(
                array(
                    'post_type' => 'birs_location',
                    'nopaging' => true,
                    'orderby' => 'post_title'
                )
        );
        $schedule = get_post_meta($post->ID, '_birs_staff_schedule', true);
        if (!isset($schedule)) {
            $schedule = array();
        } else {
            $schedule = unserialize($schedule);
        }
        ?>
        <div class="panel-wrap birchschedule">
            <?php if (sizeof($locations) > 0): ?>
                <div id="location_list">
                    <ul>
                        <?php
                        $index = 0;
                        foreach ($locations as $location):
                            ?>
                            <li data-location-id="<?php echo $location->ID; ?>"
                                <?php if ($index++ === 0) echo ' class="current" '; ?>><a><?php echo $location->post_title; ?>
                                </a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div id="timetable">
                    <?php
                    $index = 0;
                    foreach ($locations as $location):
                        if (!isset($schedule[$location->ID])) {
                            $location_schedule = array();
                        } else {
                            $location_schedule = $schedule[$location->ID];
                        }
                    ?>
                    <div data-location-id="<?php echo $location->ID; ?>"
                         <?php if ($index++ !== 0) echo 'style="display:none;"'; ?>>
                        <?php
                            $birchschedule->view->staff->render_timetable($post->ID, $location->ID);
                        ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="clear"></div>
            <?php else: ?>
                <p>
                    <?php
                    printf(__('There is no locations. Click %s here %s to add one.', 'birchschedule'), '<a
                        href="post-new.php?post_type=birs_location">', '</a>');
                    ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    static function render_service_checkboxes($services, $assigned_services) {
        foreach ($services as $service) {
            if (array_key_exists($service->ID, $assigned_services)) {
                $checked = 'checked="checked"';
            } else {
                $checked = '';
            }
            echo '<li><label>' .
            "<input type=\"checkbox\" " .
            "name=\"birs_assigned_services[$service->ID]\" $checked >" .
            $service->post_title .
            '</label></li>';
        }
    }

    static function render_assign_services($post) {
        $services = get_posts(
                array(
                    'post_type' => 'birs_service',
                    'nopaging' => true
                )
        );
        $assigned_services = get_post_meta($post->ID, '_birs_assigned_services', true);
        $assigned_services = unserialize($assigned_services);
        if ($assigned_services === false) {
            $assigned_services = array();
        }
        ?>
        <div class="panel-wrap birchschedule">
            <?php if (sizeof($services) > 0): ?>
                <p><?php _e('Assign services that this staff can perform:', 'birchschedule'); ?></p>
                <div><ul>
                        <?php self::render_service_checkboxes($services, $assigned_services); ?>
                    </ul></div>
            <?php else: ?>
                <p>
                    <?php
                    printf(__('There is no services to assign. Click %s here %s to add one.', 'birchschedule'), '<a
                        href="post-new.php?post_type=birs_service">', '</a>');
                    ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

}
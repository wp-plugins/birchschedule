<?php

final class Birchschedule_View_Appointments_Edit_Clientlist_Edit_Imp {

    private static $birchschedule;

    private static $package;

    static function init_vars() {
        global $birchschedule;

        self::$birchschedule = $birchschedule;
        self::$package = $birchschedule->view->appointments->edit->clientlist->edit;
    }

    static function init() {
        add_action('admin_init', array(self::$package, 'wp_admin_init'));
        add_action('birchschedule_view_register_common_scripts_after', 
            array(self::$package, 'register_scripts'));
    }

    static function wp_admin_init() {
        add_action('wp_ajax_birchschedule_view_appointments_edit_clientlist_edit_render_edit',
            array(self::$package, 'ajax_render_edit'));
        add_action('wp_ajax_birchschedule_view_appointments_edit_clientlist_edit_save',
            array(self::$package, 'ajax_save'));
        add_action('birchschedule_view_enqueue_scripts_post_edit_birs_appointment_after',
            array(self::$package, 'enqueue_scripts_post_edit_birs_appointment'));
    }

    static function register_scripts() {
        $version = self::$birchschedule->product_version;

        wp_register_script('birchschedule_view_appointments_edit_clientlist_edit', 
            self::$birchschedule->plugin_url() . '/assets/js/view/appointments/edit/clientlist/edit/base.js', 
            array('birchschedule_view_admincommon', 'birchschedule_view'), "$version");

    }

    static function enqueue_scripts_post_edit_birs_appointment($arg) {
        birch_assert(is_array($arg) && isset($arg['post_type']) && 
            $arg['post_type'] == 'birs_appointment');

        global $birchschedule;

        $birchschedule->view->register_3rd_scripts();
        $birchschedule->view->register_3rd_styles();
        $birchschedule->view->enqueue_scripts(
            array(
                'birchschedule_view_appointments_edit_clientlist_edit'
            )
        );
    }

    static function ajax_render_edit() {
        $client_id = $_POST['birs_client_id'];
        $appointment_id = $_POST['birs_appointment_id'];
        echo self::$package->get_client_edit_html($appointment_id, $client_id);
        exit;
    }

    static function ajax_save() {
        global $birchpress;

        $appointment1on1_errors = self::$package->validate_appointment1on1_info();
        $client_errors = self::$package->validate_client_info();
        $email_errors = self::$package->validate_duplicated_email();
        $errors = array_merge($appointment1on1_errors, $client_errors, $email_errors);
        if($errors) {
            self::$birchschedule->view->render_ajax_error_messages($errors);
        }
        $client_config = array(
            'base_keys' => array(),
            'meta_keys' => $_POST['birs_client_fields']
        );
        $client_info = self::$birchschedule->view->merge_request(array(), $client_config, $_POST);
        $client_info['ID'] = $_POST['birs_client_id'];
        $client_id = self::$birchschedule->model->booking->save_client($client_info);
        $appointment1on1s_config = array(
            'base_keys' => array(),
            'meta_keys' => $_POST['birs_appointment_fields']
        );
        $appointment1on1s_info = self::$birchschedule->view->merge_request(array(), $appointment1on1s_config, $_POST);
        $appointment1on1s_info['_birs_client_id'] = $_POST['birs_client_id'];
        $appointment1on1s_info['_birs_appointment_id'] = $_POST['birs_appointment_id'];
        self::$birchschedule->model->booking->change_appointment1on1_custom_info($appointment1on1s_info);
        self::$birchschedule->view->render_ajax_success_message(array(
            'code' => 'success',
            'message' => ''
        ));
    }

    static function validate_duplicated_email() {
        $errors = array();
        $client_id = $_POST['birs_client_id'];
        if(!isset($_POST['birs_client_email'])) {
            return $errors;
        }
        $email = $_POST['birs_client_email'];
        if(self::$birchschedule->model->booking->if_email_duplicated($client_id, $email)) {
            $errors['birs_client_email'] = __('Email already exists.', 'birchschedule') . ' (' . $email. ')';
        }
        return $errors;
    }

    static function validate_client_info() {
        $errors = array();
        if (!$_POST['birs_client_name_first']) {
            $errors['birs_client_name_first'] = __('This field is required', 'birchschedule');
        }
        if (!$_POST['birs_client_name_last']) {
            $errors['birs_client_name_last'] = __('This field is required', 'birchschedule');
        }
        if (!$_POST['birs_client_email']) {
            $errors['birs_client_email'] = __('Email is required', 'birchschedule');
        } else if (!is_email($_POST['birs_client_email'])) {
            $errors['birs_client_email'] = __('Email is incorrect', 'birchschedule');
        }
        if (!$_POST['birs_client_phone']) {
            $errors['birs_client_phone'] = __('This field is required', 'birchschedule');
        }

        return $errors;
    }

    static function validate_appointment1on1_info() {
        return array();
    }

    static function get_client_edit_actions() {
        ?>
        <ul>
            <li class="birs_form_field">
                <label>
                    &nbsp;
                </label>
                <div class="birs_field_content">
                    <input name="birs_appointment_client_edit_save" 
                        id="birs_appointment_client_edit_save"
                        type="button" class="button-primary" 
                        value="<?php _e('Save', 'birchschedule'); ?>" />
                    <a href="javascript:void(0);" 
                        id="birs_appointment_client_edit_cancel"
                        style="padding: 4px 0 0 4px; display: inline-block;">
                        <?php _e('Cancel', 'birchschedule'); ?>
                    </a>
                </div>
            </li>
        </ul>
        <script type="text/javascript">
            (function($){
                $(birchschedule.view.appointments.edit.clientlist.edit.initEdit);
            })(jQuery);
        </script>
        <?php
    }

    static function get_client_edit_html($appointment_id, $client_id) {
        ob_start();
        ?>
        <div style="overflow:hidden;">
            <h4><?php _e('Edit Client', 'birchschedule'); ?></h4>
            <?php echo self::$package->get_client_info_html($client_id); ?>
            <input type="hidden" name="birs_client_id" id="birs_client_id" value="<?php echo $client_id; ?>" />
            <div style="border-bottom: 1px solid #EEEEEE;"></div>
            <?php echo self::$package->get_appointment1on1_info_html($appointment_id, $client_id); ?>
            <?php echo self::$package->get_client_edit_actions(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    static function get_appointment1on1_info_html($appointment_id, $client_id) {
        $appointment1on1s = self::$birchschedule->model->query(
            array(
                'post_type' => 'birs_appointment1on1',
                'meta_query' => array(
                    array(
                        'key' => '_birs_client_id',
                        'value' => $client_id
                    ),
                    array(
                        'key' => '_birs_appointment_id',
                        'value' => $appointment_id
                    )
                )
            ),
            array(
                'base_keys' => array(),
                'meta_keys' => array('_birs_appointment_notes')
            )
        );
        $notes = '';
        if($appointment1on1s) {
            $appointment1on1s = array_values($appointment1on1s);
            $appointment_ext = $appointment1on1s[0];
            if(isset($appointment_ext['_birs_appointment_notes'])) {
                $notes = $appointment_ext['_birs_appointment_notes'];
            }
        }
        ob_start();
        ?>
        <ul>
            <li class="birs_form_field">
                <label>
                    <?php _e('Notes', 'birchschedule'); ?>
                </label>
                <div class="birs_field_content">
                    <textarea id="birs_appointment_notes" name="birs_appointment_notes"><?php echo $notes; ?></textarea>
                    <input type="hidden" name="birs_appointment_fields[]" value="_birs_appointment_notes" />
                </div>
            </li>
        </ul>
        <?php
        $content = ob_get_clean();
        return $content;
    }

    static function get_client_info_html($client_id) {
        global $birchpress;
        $client_titles = $birchpress->util->get_client_title_options();
        $client_title = get_post_meta($client_id, '_birs_client_title', true);
        $first_name = get_post_meta($client_id, '_birs_client_name_first', true);
        $last_name = get_post_meta($client_id, '_birs_client_name_last', true);
        $addresss1 = get_post_meta($client_id, '_birs_client_address1', true);
        $addresss2 = get_post_meta($client_id, '_birs_client_address2', true);
        $email = get_post_meta($client_id, '_birs_client_email', true);
        $phone = get_post_meta($client_id, '_birs_client_phone', true);
        $city = get_post_meta($client_id, '_birs_client_city', true);
        $zip = get_post_meta($client_id, '_birs_client_zip', true);
        $state = get_post_meta($client_id, '_birs_client_state', true);
        $country = get_post_meta($client_id, '_birs_client_country', true);
        if(!$country) {
            $country = self::$birchschedule->model->get_default_country();
        }
        $states = $birchpress->util->get_states();
        $countries = $birchpress->util->get_countries();
        if(isset($states[$country])) {
            $select_display = "";
            $text_display = "display:none;";
        } else {
            $select_display = "display:none;";
            $text_display = "";
        }        
        ob_start();
        ?>
        <ul>
            <li class="birs_form_field birs_client_title">
                <label for="birs_client_title"><?php _e('Title', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <select name="birs_client_title" id="birs_client_title">
                        <?php $birchpress->util->render_html_options($client_titles, $client_title); ?>
                    </select>
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_title">
                </div>
                <div class="birs_error" id="birs_client_title_error">
                </div>
            </li>
            <li class="birs_form_field birs_client_name_first">
                <label for="birs_client_name_first"><?php _e('First Name', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <input type="text" name="birs_client_name_first" id="birs_client_name_first" value="<?php echo esc_attr($first_name); ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_first">
                </div>
                <div class="birs_error" id="birs_client_name_first_error">
                </div>
            </li>
                <li class="birs_form_field birs_client_name_last">
                <label for="birs_client_name_last"><?php _e('Last Name', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <input type="text" name="birs_client_name_last" id="birs_client_name_last" value="<?php echo esc_attr($last_name); ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_name_last">
                </div>
                <div class="birs_error" id="birs_client_name_last_error">
                </div>
            </li>
                <li class="birs_form_field birs_client_email">
                <label for="birs_client_email"><?php _e('Email', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <input type="text" name="birs_client_email" id="birs_client_email" value="<?php echo esc_attr($email); ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_email">
                </div>
                <div class="birs_error" id="birs_client_email_error">
                </div>
            </li>
            <li class="birs_form_field birs_client_phone">
                <label for="birs_client_phone"><?php _e('Phone', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <input type="text" name="birs_client_phone" id="birs_client_phone" value="<?php echo esc_attr($phone); ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_phone">
                </div>
                <div class="birs_error" id="birs_client_phone_error">
                </div>
            </li>
            <li class="birs_form_field birs_client_address">
                <label for="birs_client_address1"><?php _e('Address', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <input type="text" name="birs_client_address1" id="birs_client_address1" style="display: block;" value="<?php echo esc_attr($addresss1); ?>">
                    <input type="text" name="birs_client_address2" id="birs_client_address2" value="<?php echo esc_attr($addresss2); ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_address1">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_address2">
                </div>
                <div class="birs_error" id="birs_client_address_error">
                </div>
            </li>
            <li class="birs_form_field birs_client_city">
                <label for="birs_client_city"><?php _e('City', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <input type="text" name="birs_client_city" id="birs_client_city" value="<?php echo esc_attr($city); ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_city">
                </div>
                <div class="birs_error" id="birs_client_city_error">
                </div>
            </li>
            <li class="birs_form_field birs_client_state">
                <label for="birs_client_state"><?php _e('State/Province', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <select name="birs_client_state_select" id ="birs_client_state_select" style="<?php echo $select_display; ?>">
                    <?php
                    if(isset($states[$country])) {
                        $birchpress->util->render_html_options($states[$country], $state);
                    }
                    ?>
                    </select>
                    <input type="text" name="birs_client_state" id="birs_client_state" value="<?php echo esc_attr($state); ?>" style="<?php echo $text_display; ?>" />
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_state">
                </div>
                <div class="birs_error" id="birs_client_state_error">
                </div>
            </li>
            <li class="birs_form_field birs_client_country">
                <label for="birs_client_country"><?php _e('Country', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <select name="birs_client_country" id="birs_client_country">
                        <?php $birchpress->util->render_html_options($countries, $country); ?>
                    </select>
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_country">
                </div>
                <div class="birs_error" id="birs_client_country_error">
                </div>
            </li>
            <li class="birs_form_field birs_client_zip">
                <label for="birs_client_zip"><?php _e('Zip Code', 'birchschedule'); ?></label>
                <div class="birs_field_content">
                    <input type="text" name="birs_client_zip" id="birs_client_zip" value="<?php echo esc_attr($zip); ?>">
                    <input type="hidden" name="birs_client_fields[]" value="_birs_client_zip">
                </div>
                <div class="birs_error" id="birs_client_zip_error">
                </div>
            </li>
        </ul>
        <?php
        return ob_get_clean();
    }

}

Birchschedule_View_Appointments_Edit_Clientlist_Edit_Imp::init_vars();

?>
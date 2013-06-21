<?php

class BIRS_Help_View extends BIRS_Admin_View {

    function __construct() {
        parent::__construct();
        add_filter('birchschedule_get_wp_screen', array($this, 'get_screen'), 10, 2);
    }

    function admin_init() {
        parent::admin_init();
        $screen = apply_filters('birchschedule_get_wp_screen', 
            null, $this->get_page_hook_suffix());
        add_meta_box('birs_help_general', __('Help and Support', 'birchschedule'), 
            array($this, 'render_help_general'), 
            $screen, 'main', 'default');
        add_meta_box('birs_help_version', __('Versions', 'birchschedule'), 
            array($this, 'render_help_version'), 
            $screen, 'main', 'default');
    }

    function get_page_hook_suffix() {
        return "birchschedule_page_help";
    }

    function get_screen($screen, $hook_name) {
        $page_hook = $hook_name . '.php';
        $screen = WP_Screen::get($page_hook);
        return $screen;
    }

    function render_help_version() {
        global $birchschedule, $wp_version;
        $version = $birchschedule->product_version;
        $product_name = $birchschedule->product_name;
        ?>
        <div class="wrap">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th><?php echo "$product_name"; ?></th>
                        <td><?php echo "$version" ?></td>
                    </tr>
                    <tr>
                        <th><?php echo "WordPress"; ?></th>
                        <td><?php echo "$wp_version" ?></td>
                    </tr>
                    <tr>
                        <th><?php echo "PHP"; ?></th>
                        <td><?php echo phpversion(); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo "MySQL"; ?></th>
                        <td><?php echo mysql_get_server_info(); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    function render_help_general() {
        ?>
        <div class="padding">
            <p>If you have any questions, please review the <a target="_blank" href="http://www.birchpress.com/support/documentation/scheduler-pro/get-started/">tutorial</a> first.</p>
            <p>If you are a <a target="_blank" href="http://www.birchpress.com/products/scheduler-pro/">BirchPress Scheduler Pro</a> user, please submit your question <a target="_blank" href="http://www.birchpress.com/support/submit-a-ticket/">here</a>.</p>
            <p>If you are a BirchPress Scheduler user, please submit your question through our <a target="_blank" href="http://www.birchpress.com/support/forums">support forum</a>.</p>
            <p>If you find our product helpful, please <a target="_blank" href="http://wordpress.org/extend/plugins/birchschedule">rate it!</a></p>
        </div>
        <?php
    }

    function render_admin_page() {
        $screen = apply_filters('birchschedule_get_wp_screen', 
            null, $this->get_page_hook_suffix());
        do_action('birchschedule_show_update_notice');
        ?>
        <div id="birchschedule_email_notification" class="wrap">
            <form method="post" action="">
                <?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false); ?>
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-1">
                        <div id="postbox-container-1" class="postbox-container">
                            <?php do_meta_boxes($screen, 'main', array()) ?>
                        </div>
                    </div>
                    <br class="clear" />
                </div>
            </form>
        </div>
        <?php
    }

}
?>

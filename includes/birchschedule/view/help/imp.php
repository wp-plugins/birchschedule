<?php

class Birchschedule_View_Help_Imp {

	private function __construct() {
		
	}

	static function init() {
        global $birchschedule;
        
        $package = $birchschedule->view->help;
        add_action('admin_init', array($package, 'wp_admin_init'));
	}

    static function wp_admin_init() {
        global $birchschedule;
        
        $package = $birchschedule->view->help;
        if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'birchschedule_help') {
            $package->load_page();
        }
    }

	static function get_screen() {
		global $birchschedule;

        $hook_name = $birchschedule->view->get_page_hook('help');
		$screen = $birchschedule->view->get_screen($hook_name);
		return $screen;
	}

	static function load_page() {
		$screen = self::get_screen();
        add_meta_box('birs_help_general', __('Help and Support', 'birchschedule'), 
            array(__CLASS__, 'render_help_general'), 
            $screen, 'main', 'default');
        add_meta_box('birs_help_version', __('Versions', 'birchschedule'), 
            array(__CLASS__, 'render_help_version'), 
            $screen, 'main', 'default');
	}

    static function render_help_version() {
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

    static function render_help_general() {
        ?>
        <div class="padding">
            <p>If you have any questions, please refer to <a target="_blank" href="http://www.birchpress.com/support/documentation">documentation</a> first.</p>
            <p>If you are using a <a target="_blank" href="http://www.birchpress.com/">premium edition</a>, please submit a ticket <a target="_blank" href="http://www.birchpress.com/support/submit-a-ticket/">here</a>.</p>
            <p>If you are using a free version, please submit your question through our <a target="_blank" href="http://www.birchpress.com/support/forums">support forum</a>.</p>
            <p>If you find our product helpful, please <a target="_blank" href="http://wordpress.org/extend/plugins/birchschedule">rate it!</a></p>
        </div>
        <?php
    }

    static function render_admin_page() {
    	global $birchschedule;

		$screen = self::get_screen();
		$birchschedule->view->show_notice();
        ?>
        <div id="birchschedule_help" class="wrap">
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
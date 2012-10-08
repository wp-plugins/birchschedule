<?php
class BIRS_Settings_View {
    function __construct(){
        add_action('admin_init', array(&$this, 'init'));
    }

    function init(){
        register_setting('birchschedule_options', 'birchschedule_options');
        
        add_settings_section('birchschedule_main', __(''),
        array(&$this, 'render_section_main_html'), 'birchschedule_settings');
        
        add_settings_field('birchschedule_option1', __('Option 1'),
        array(&$this, 'render_option1_html'), 'birchschedule_settings', 'birchschedule_main');
    }

    function render_section_main_html(){
        echo '';
    }

    function render_option1_html(){
        $options = get_option('birchschedule_options');
        $option1 = $options['option1'];
        echo "<input id='option1' name='birchschedule_options[option1]'
    	type='text' value='$option1' />";
    }

    function render_admin_page(){
        ?>
<div class="wrap">
	<h2>BirchSchedule Settings</h2>
	<?php settings_errors(); ?>
	<form action="options.php" method="post">
	<?php settings_fields('birchschedule_options'); ?>
	<?php do_settings_sections('birchschedule_settings') ?>
		<input name="Submit" type="submit" class="button-primary"
			value="Save Changes" />
	</form>
</div>
	<?php
    }
}
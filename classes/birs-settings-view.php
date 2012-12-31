<?php

class BIRS_Settings_View extends BIRS_Admin_View {

    private $tabs = array();
    private $active_tab;

    function __construct() {
        parent::__construct();
    }

    function admin_init() {
        parent::admin_init();
        if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'birchschedule_settings') {
            if (isset($_GET['tab'])) {
                $this->active_tab = $_GET['tab'];
            } else {
                $this->active_tab = 'general';
            }
            $this->tabs = apply_filters('birchschedule_settings_tabs', $this->tabs);
            do_action('birchschedule_settings_' . $this->active_tab . '_view_init');
        }
    }

    function compare_tab_order($a, $b) {
        if ($a['order'] == $b['order']) {
            return 0;
        }
        return ($a['order'] < $b['order']) ? -1 : 1;
    }

    function render_admin_page() {
        $setting_page_url = admin_url("admin.php") . "?page=birchschedule_settings";
        uasort($this->tabs, array(&$this, 'compare_tab_order'));
        ?>
        <div class="wrap">
            <h2 class="nav-tab-wrapper">
                <?php
                if (is_array($this->tabs)):
                    foreach ($this->tabs as $tab_name => $tab):
                        $active_class = "";
                        if ($this->active_tab == $tab_name) {
                            $active_class = "nav-tab-active";
                        }
                        ?>
                        <a href='<?php echo $setting_page_url . "&tab=$tab_name"; ?>' class="nav-tab <?php echo $active_class; ?>"><?php echo $tab['title']; ?></a>
                        <?php
                    endforeach;
                endif;
                ?>
            </h2>
            <?php
            $active_tab = $this->tabs[$this->active_tab];
            if ($active_tab) {
                call_user_func($active_tab['action']);
            }
            ?>
        </div>
        <?php
    }

    public function get_admin_scripts() {
        return array('birs_admin_common');
    }

}
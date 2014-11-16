<?php

final class Birchschedule_View_Appointments_Edit_Clientlist_Imp {

    private static $birchschedule;

    private static $package;

    static function init_vars() {
        global $birchschedule;

        self::$birchschedule = $birchschedule;
        self::$package = $birchschedule->view->appointments->edit->clientlist;
    }

    static function init() {
        add_action('admin_init', array(self::$package, 'wp_admin_init'));
        add_action('birchschedule_view_register_common_scripts_after', 
            array(self::$package, 'register_scripts'));
    }

    static function wp_admin_init() {

        add_action('birchschedule_view_appointments_edit_add_meta_boxes_after', 
            array(self::$package, 'add_meta_boxes'));
    }

    static function register_scripts() {
        $version = self::$birchschedule->product_version;

        wp_register_script('birchschedule_view_appointments_edit_clientlist', 
            self::$birchschedule->plugin_url() . '/assets/js/view/appointments/edit/clientlist/base.js', 
            array('birchschedule_view_admincommon', 'birchschedule_view', 'jquery-ui-datepicker'), "$version");

    }

    static function get_clients_meta_box_title() {
        return __('Client Info', 'birchschedule');
    }

    static function add_meta_boxes() {
        $title = self::$package->get_clients_meta_box_title();
        add_meta_box('birs_appointment_clients_meta_box', $title, 
            array(self::$package, 'render_clients'), 'birs_appointment', 'normal', 'high');
    }

    static function render_clients($post) {
        $testListTable = new Birchschedule_View_Appointments_Edit_Clientlist_Table($post->ID);
        $testListTable->prepare_items();
        $testListTable->display();
    }

    static function get_item_actions($item) {
        $action_html = '<a href="javascript:void(0);" data-item-id="%s">%s</a>';
        return array(
            'edit'      => sprintf($action_html, $item['ID'], __('View Details', 'birchschedule')),
            'payments' => sprintf($action_html, $item['ID'], __('Payments', 'birchschedule')),
            'cancel' => sprintf($action_html, $item['ID'], __('Cancel', 'birchschedule'))
        );
    }

    static function get_construct_args($wp_list_table) {
        return array(
            'singular'=> 'birs_client',
            'plural' => 'birs_clients',
            'ajax'  => true,
            'screen' => 'post.php'
        );
    }

    static function column_title($wp_list_table, $item) {
        $actions = self::$package->get_item_actions($item);
        return $item['_birs_client_name_first'] . $wp_list_table->row_actions($actions);
    }

    static function column_default($wp_list_table, $item, $column_name){
        if($column_name == 'title') {
            $column_name = 'client_name_first';
        }
        $meta_key = '_birs_' . $column_name;
        return $item[$meta_key];
    }

    static function get_columns($wp_list_table) {
        $labels = self::$birchschedule->view->bookingform->get_fields_labels();
        return $columns = array(
            'title' => $labels['client_name_first'],
            'client_name_last' => $labels['client_name_last'],
            'client_email' => $labels['client_email'],
            'client_phone' => $labels['client_phone']
        );
    }

    static function get_sortable_columns($wp_list_table) {
        return $sortable = array(
            'title' => array('client_name_first', false),
            'client_name_last' => array('client_name_last', false),
            'client_email' => array('client_email', false),
            'client_phone' => array('client_phone', false)
        );
    }

    static function single_row($wp_list_table, $item) {
        $column_count = $wp_list_table->get_column_count();
        static $row_class = '';
        $row_class = ( $row_class == '' ? ' alternate' : '' );
        ?>

        <tr class="<?php echo $row_class; ?> birs_row" 
            id="birs_client_list_row_<?php echo $item['ID']; ?>" 
            data-item-id = "<?php echo $item['ID']; ?>">
        <?php
            $wp_list_table->single_row_columns( $item );
        ?>
        </tr>
        <tr class="<?php echo $row_class; ?> birs_row_edit" 
            id="birs_client_list_row_edit_<?php echo $item['ID']; ?>" 
            data-item-id = "<?php echo $item['ID']; ?>">
            <td colspan = "<?php echo $column_count; ?>"></td>
        </tr>
        <tr class="<?php echo $row_class; ?> birs_row_payments" 
            id="birs_client_list_row_payments_<?php echo $item['ID']; ?>" 
            data-item-id = "<?php echo $item['ID']; ?>">
            <td colspan = "<?php echo $column_count; ?>"></td>
        </tr>
        <?php
    }

    static function get_item_count_per_page() {
        return 10;
    }

    static function prepare_items($wp_list_table) {
        $perpage = self::$package->get_item_count_per_page();
        $columns = $wp_list_table->get_columns();
        $hidden = array();
        $sortable = $wp_list_table->get_sortable_columns();

        $wp_list_table->_column_headers = array($columns, $hidden, $sortable);

        $screen = get_current_screen();

        $appointment1on1s = self::$birchschedule->model->query(
            array(
                'post_type' => 'birs_appointment1on1',
                'post_status' => array('publish'),
                'meta_query' => array(
                    array(
                        'key' => '_birs_appointment_id',
                        'value' => $wp_list_table->appointment_id
                    )
                )
            ),
            array(
                'base_keys' => array(),
                'meta_keys' => array('_birs_client_id')
            )
        );
        $client_ids = array();
        if($appointment1on1s) {
            $appointment1on1s = array_values($appointment1on1s);
            foreach($appointment1on1s as $appointment1on1) {
                $client_ids[] = $appointment1on1['_birs_client_id'];
            }
        }
        $totalitems = count($client_ids);

        $order = 'ASC';
        if(isset($_GET["order"]) && $_GET['order'] == 'desc') {
            $order = 'DESC';
        }
        $wp_list_table->order = $order;

        $orderby = 'client_name_first';
        if(isset($_GET["orderby"]) && !empty($_GET['orderby'])) {
            $orderby = $_GET["orderby"];
        }
        $wp_list_table->orderby = $orderby;

        $totalpages = ceil($totalitems / $perpage);
        $wp_list_table->set_pagination_args( array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage,
        ) );

        $paged = $wp_list_table->get_pagenum();
        $query = array(
            'post_type' => 'birs_client',
            'post__in' => $client_ids + array(0),
            'post_status' => 'publish',
            'nopaging' => false,
            'order' => $order,
            'orderby' => 'meta_value',
            'meta_key' => '_birs_' . $orderby,
            'posts_per_page' => $perpage,
            'paged' => $paged
        );
        $config = array(
            'base_keys' => array(),
            'meta_keys' => array(
                '_birs_client_name_first', '_birs_client_name_last',
                '_birs_client_email', '_birs_client_phone'
            )
        );
        $items = self::$birchschedule->model->query($query, $config);

        $items = array_values($items);

        $wp_list_table->items = $items;
    }

}

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Birchschedule_View_Appointments_Edit_Clientlist_Table extends WP_List_Table {

    var $appointment_id;

    var $order;

    var $orderby;

    var $package;
    
    function __construct($appointment_id) {
        global $birchschedule;

        $this->package = $birchschedule->view->appointments->edit->clientlist;
        $args = $this->package->get_construct_args($this);
        parent::__construct($args);
        $this->appointment_id = $appointment_id;
    }

    function column_title($item) {
        global $birchschedule;
        return $this->package->column_title($this, $item);
    }

    function column_default($item, $column_name){
        global $birchschedule;
        return $this->package->column_default($this, $item, $column_name);
    }

    function get_columns() {
        global $birchschedule;
        return $this->package->get_columns($this);
    }

    function get_sortable_columns() {
        global $birchschedule;
        return $this->package->get_sortable_columns($this);
    }

    function single_row($item) {
        global $birchschedule;
        $this->package->single_row($this, $item);
    }

    function prepare_items() {
        global $birchschedule;
        return $this->package->prepare_items($this);
    }
}

Birchschedule_View_Appointments_Edit_Clientlist_Imp::init_vars();

?>
<?php

function birchbase_view_def() {

    $ns = birch_ns( 'birchbase.view' );

    birch_defn( $ns, 'render_meta_boxes', function( $config ) use ( $ns ) {
            $assert_criteria = is_array( $config ) && isset( $config['screen'] );
            birch_assert( $assert_criteria );
            $default_config = array(
                'cols' => 1,
                'callback_arg' => ''
            );
            $config = array_merge( $default_config, $config );
            if ( $config['cols'] === 2 ) {
?>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="postbox-container-1" class="postbox-container">
                        <?php do_meta_boxes( $config['screen'], 'side', $config['callback_arg'] ); ?>
                    </div>
                    <div id="postbox-container-2" class="postbox-container">
                        <?php do_meta_boxes( $config['screen'], 'normal', $config['callback_arg'] ); ?>
                    </div>
                </div>
                <br class="clear" />
            </div>
<?php
            } else {
?>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="postbox-container-1" class="postbox-container">
                        <?php do_meta_boxes( $config['screen'], 'normal', $config['callback_arg'] ) ?>
                    </div>
                </div>
                <br class="clear" />
            </div>
<?php
            }
?>
<?php
        } );

    birch_defn( $ns, 'render_tabs', function( $page_config ) use ( $ns ) {

        } );

    birch_defn( $ns, 'get_wp_screen', function( $hook_name ) use ( $ns ) {
            if ( substr( $hook_name, -4 ) !== '.php' ) {
                $hook_name = $hook_name . '.php';
            }
            $screen = WP_Screen::get( $hook_name );
            return $screen;
        } );

}

birchbase_view_def();

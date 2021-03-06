<?php
/**
 * @package Pods\Widgets
 */
class PodsWidgetList extends WP_Widget {

	/**
	 * Register the widget
	 *
	 * @since 2.5.4
	 *
	 * Note: params are totally ignored. Included for the sake of strict standards.
	 *
	 *
	 * @param string $id_base         Optional Base ID for the widget, lowercase and unique. If left empty,
	 *                                a portion of the widget's class name will be used Has to be unique.
	 * @param string $name            Name for the widget displayed on the configuration page.
	 * @param array  $widget_options  Optional. Widget options. See {@see wp_register_sidebar_widget()} for
	 *                                information on accepted arguments. Default empty array.
	 * @param array  $control_options Optional. Widget control options. See {@see wp_register_widget_control()}
	 *                                for information on accepted arguments. Default empty array.
	 */
	public function __construct( $id_base = 'pods_widget_list', $name = 'Pods - List Items', $widget_options = array(), $control_options = array() ) {
	    parent::__construct(
            'pods_widget_list',
            'Pods - List Items',
            array( 'classname' => 'pods_widget_list', 'description' => 'Display multiple Pod items' ),
            array( 'width' => 200 )
        );

    }

    /**
     * Output of widget
     */
    public function widget ( $args, $instance ) {
        extract( $args );

        // Get widget fields
        $title = apply_filters( 'widget_title', pods_v( 'title', $instance ) );

        $args = array(
            'name' => trim( pods_var_raw( 'pod_type', $instance, '' ) ),
            'template' => trim( pods_var_raw( 'template', $instance, '' ) ),
            'limit' => (int) pods_var_raw( 'limit', $instance, 15, null, true ),
            'orderby' => trim( pods_var_raw( 'orderby', $instance, '' ) ),
            'where' => trim( pods_var_raw( 'where', $instance, '' ) ),
            'expires' => (int) trim( pods_var_raw( 'expires', $instance, ( 60 * 5 ) ) ),
            'cache_mode' => trim( pods_var_raw( 'cache_mode', $instance, 'none', null, true ) )
        );

        $before_content = trim( pods_var_raw( 'before_content', $instance, '' ) );
        $content = trim( pods_var_raw( 'template_custom', $instance, '' ) );
        $after_content = trim( pods_var_raw( 'after_content', $instance, '' ) );

        if ( 0 < strlen( $args[ 'name' ] ) && ( 0 < strlen( $args[ 'template' ] ) || 0 < strlen( $content ) ) ) {
            require PODS_DIR . 'ui/front/widgets.php';
        }
    }

    /**
     * Updates the new instance of widget arguments
     *
     * @returns array $instance Updated instance
     */
    public function update ( $new_instance, $old_instance ) {
        $instance = $old_instance;

        $instance[ 'title' ] = pods_var_raw( 'title', $new_instance, '' );
        $instance[ 'pod_type' ] = pods_var_raw( 'pod_type', $new_instance, '' );
        $instance[ 'template' ] = pods_var_raw( 'template', $new_instance, '' );
        $instance[ 'template_custom' ] = pods_var_raw( 'template_custom', $new_instance, '' );
        $instance[ 'limit' ] = (int) pods_var_raw( 'limit', $new_instance, 15, null, true );
        $instance[ 'orderby' ] = pods_var_raw( 'orderby', $new_instance, '' );
        $instance[ 'where' ] = pods_var_raw( 'where', $new_instance, '' );
        $instance[ 'expires' ] = (int) pods_var_raw( 'expires', $new_instance, ( 60 * 5 ) );
        $instance[ 'cache_mode' ] = pods_var_raw( 'cache_mode', $new_instance, 'none' );
        $instance[ 'before_content' ] = pods_var_raw( 'before_content', $new_instance, '' );
        $instance[ 'after_content' ] = pods_var_raw( 'after_content', $new_instance, '' );

        return $instance;
    }

    /**
     * Widget Form
     */
    public function form ( $instance ) {
        $title = pods_var_raw( 'title', $instance, '' );
        $pod_type = pods_var_raw( 'pod_type', $instance, '' );
        $template = pods_var_raw( 'template', $instance, '' );
        $template_custom = pods_var_raw( 'template_custom', $instance, '' );
        $limit = (int) pods_var_raw( 'limit', $instance, 15, null, true );
        $orderby = pods_var_raw( 'orderby', $instance, '' );
        $where = pods_var_raw( 'where', $instance, '' );
        $expires = (int) pods_var_raw( 'expires', $instance, ( 60 * 5 ) );
        $cache_mode = pods_var_raw( 'cache_mode', $instance, 'none' );
        $before_content = pods_var_raw( 'before_content', $instance, '' );
        $after_content = pods_var_raw( 'after_content', $instance, '' );

        require PODS_DIR . 'ui/admin/widgets/list.php';
    }
}

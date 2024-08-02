<?php 
if (!class_exists('SPS_Post_Meta')) {

    class SPS_Post_Meta {

        function __construct() {
            
            add_action('admin_init', array( $this, 'register_meta_settings' ) );
                
            add_action('save_post', array( $this, 'save_meta_fields' ) );
                   
        }

        function register_meta_settings()
        {
            global $sps_settings;
            add_meta_box(
                'sps_websites', 
                __('Select Websites', 'SPS_txt_domain'), 
                array( $this, 'print_meta_fields' ), 
                $sps_settings->sps_get_post_types(), 
                'side', 
                'default'
            );
        }
        
        public function print_meta_fields()
        {
            global $wpdb, $sps_settings, $post;
            $general_option = $sps_settings->sps_get_settings_func();

            echo '<div class="drop_meta_container">';
            echo '<div class="drop_meta_item fullwidth">';
            echo '<div class="inner_meta">';
                
            if( !empty( $general_option ) && isset( $general_option['sps_host_name'] ) && !empty( $general_option['sps_host_name'] ) ) {
                $sps_website = get_post_meta($post->ID, 'sps_website', false);
                $old_meta = ( isset($sps_website['0']) && !empty($sps_website['0']) ) ? $sps_website['0'] : array();
                $sps_selected = ( isset($general_option['sps_selected']) && !empty($general_option['sps_selected']) ) ? $general_option['sps_selected'] : array();

                foreach ($general_option['sps_host_name'] as $sps_key => $sps_value) {
                    $checked = (in_array($sps_value, $old_meta)) ? 'checked="checked"' : '';
                    $checked = ( isset( $sps_selected[$sps_key] ) && !empty($sps_selected[$sps_key]) ) ? 'checked="checked"' : '';

                    echo '<input type="checkbox" name="sps_website[]" id="sps_website_'.$sps_key.'" value="'.$sps_value.'" '.$checked.'>';
                    echo '<label for="sps_website_'.$sps_key.'">'.$sps_value.'</label>';
                    echo '<br/>';
                }
            } else {
                _e( 'Please add website in <b>Sync Post</b>. So you can select the website for sync post.', SPS_txt_domain );
                echo "<br/>";
            }

            echo "<br/>";
            echo '<div class="meta_description"><p>' . __('select which website you want to add/edit post with this post.',SPS_txt_domain) . '</p></div>';
            echo '</div><!-- end inner -->';
            echo '</div><!-- end single meta -->';
            echo '</div><!-- end meta container --><br />';
        }

        function save_meta_fields( $post_id ) {
            if( isset($_REQUEST['sps_website']) && !empty($_REQUEST['sps_website']) ) {
                $sps_websites = array();
                foreach( $_REQUEST['sps_website'] as $sps_webkey => $sps_webvalue ) {
                    $sps_websites[$sps_webkey] = esc_url_raw($sps_webvalue);
                }
                update_post_meta($post_id, 'sps_website', $sps_websites);
            }
        }
        
    }

    global $sps_post_meta;
    $sps_post_meta = new SPS_Post_Meta();
}




?>
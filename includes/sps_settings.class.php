<?php
if( !class_exists ( 'SPS_Settings' ) ) {

    class SPS_Settings {

    	function __construct(){

    		add_action( "sps_save_settings", array( $this, "sps_save_settings_func" ), 10 , 1 );

    	}

    	function sps_display_settings( ) {
    		if( file_exists( SPS_INCLUDES_DIR . "sps_settings.view.php" ) ) {
    			include_once( SPS_INCLUDES_DIR . "sps_settings.view.php" );
    		}
    	}

        function sps_default_setting_option() {
            return array(
                'sps_host_name' => array( '0' => '' ),
                'sps_strict_mode' => array( '0' => '1' ),
                'sps_content_match' => array( '0' => 'title' ),
                'sps_content_username' => array( '0' => '' ),
                'sps_content_password' => array( '0' => '' ),
                'sps_selected' => array( '0' => ''),
                'sps_roles_allowed' => array( '0' => array('roles' => array( 'administrator' => 'on', 'editor' => 'on', 'author' => 'on' ) ) )
            );
        }

    	function sps_save_settings_func( $params = array() ) {
            $nonce = wp_create_nonce('sps_nonce');
            if ( ! isset( $_POST['sps_general_option_field'] ) || ! wp_verify_nonce( $_POST['sps_general_option_field'], 'sps_nonce' ) ) {
                // Nonce verification failed; handle error or exit.
                wp_die('verification failed. Please try again');
            }

    		if( isset( $params['sps_setting'] ) && $params['sps_setting'] != '') {
                $sps_setting = $params['sps_setting'];
                unset( $params['sps_setting'] );
    			unset( $params['sps_setting_save'] );

                if( isset($params['sps_host_name']) && !empty($params['sps_host_name']) ) {
                    $hostnames = array();
                    $usernames = array();
                    $passwords = array();
                    foreach ($params['sps_host_name'] as $key => $hostname) {
                        $hostnames[] = sanitize_url($hostname);
                        $usernames[] = sanitize_user($params['sps_content_username'][$key]);
                        $passwords[] = wp_strip_all_tags($params['sps_content_password'][$key]);
                    }

                    $params['sps_host_name'] = $hostnames;
                    $params['sps_content_username'] = $usernames;
                    $params['sps_content_password'] = $passwords;
                }

                update_option('sps_setting', $params);

    			$_SESSION['sps_msg_status'] = true;
    			$_SESSION['sps_msg'] = 'Settings updated successfully.';

    		}
    	}

        function sps_get_settings_func( ) {
            $ncm_default_general_option = $this->sps_default_setting_option();
            $ncm_setting_option = get_option( 'sps_setting' );
            return shortcode_atts( $ncm_default_general_option, $ncm_setting_option );
        }

        function sps_get_admin_users( $all_user = false, $fields = 'ID' ) {
            global $wpdb;
            $args = array(
                'role'         => 'Administrator',
                'orderby'      => 'ID',
                'order'        => 'ASC',
                'fields'       => $fields 
            );
            $users = get_users( $args );
            if( $all_user ) {
                return $users;
            } else {
                return $users[0];
            }
        }

        function sps_get_post_types() {
            $all_types = get_post_types();
            $unset_keys = array( 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block' );

            foreach ($unset_keys as $uvalue) {
                if( isset($all_types[$uvalue]) ) {
                    unset($all_types[$uvalue]);
                }
            }
            return $all_types;
        }
       
    }

    global $sps_settings;
    $sps_settings = new SPS_Settings();

}

?>
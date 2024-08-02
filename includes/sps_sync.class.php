<?php
if( !class_exists ( 'SPS_Sync' ) ) {

    class SPS_Sync {
        var $is_website_post = true;
        var $post_old_title = '';

        function __construct(){
            
            add_filter( 'wp_insert_post_data' , array( $this, 'filter_post_data') , '99', 2 );

            // save tags to post for guttenburg because save post not get the tags in guttenburg.
            add_action( "rest_insert_post", array( $this, "sps_rest_insert_post" ), 10 , 3 );
            
            add_action( "save_post", array( $this, "sps_save_post" ), 10 , 3 );

            add_action( "spsp_after_save_data", array( $this, "spsp_grab_content_images" ), 10, 2 );

            add_action( 'rest_api_init', array( $this, 'rest_api_init_func' ) ); // Register custom API endpoints
        }


        function rest_api_init_func() {
            register_rest_route( 'sps/v1', '/data', array(
                'methods'  => 'POST',
                'callback' => array( $this, 'sps_get_request'  ),
            ) );
        }

        function filter_post_data( $data , $postarr ) {
            global $post_old_title;
            if( isset($postarr['ID']) && !empty($postarr['ID']) ) {
                $old_data = get_posts( array( 'ID' => $postarr['ID'] ) );
                if( $old_data && isset($old_data[0]->post_title) && $postarr != $old_data[0]->post_title ) {
                    $post_old_title = $old_data[0]->post_title; 
                } 
            }

            return $data;
        }

        function sps_rest_insert_post( $post, $reqest, $creating ) {
            $json = $reqest->get_json_params();
            if ( isset( $json['tags'] ) && !empty( $json['tags'] ) ) {
                $this->sps_save_post( $post->ID, $post, 1, $json['tags'] );
            }
        }

        function sps_send_data_to( $action, $args = array(), $sps_website = array() ) {
            global $wpdb, $sps, $sps_settings, $post_old_title;
            $general_option = $sps_settings->sps_get_settings_func();

            if( !empty( $general_option ) && isset( $general_option['sps_host_name'] ) && !empty( $general_option['sps_host_name'] ) ) {
                foreach ($general_option['sps_host_name'] as $sps_key => $sps_value) { 

                    $args['sps']['roles'] = isset( $general_option['sps_roles_allowed'][$sps_key]['roles'] ) ? $general_option['sps_roles_allowed'][$sps_key]['roles'] : array();
                    $args['sps']['host_name']   = !empty( $sps_value ) ? $sps_value : '';
                    $args['sps']['strict_mode'] = isset( $general_option['sps_strict_mode'][$sps_key] ) ? $general_option['sps_strict_mode'][$sps_key] : 1;
                    $args['sps']['roles']['administrator'] = 'on';

                    $args['sps']['content_match'] = isset( $general_option['sps_content_match'][$sps_key] ) ? $general_option['sps_content_match'][$sps_key] : 'title';
                    $args['sps']['content_username'] = isset( $general_option['sps_content_username'][$sps_key] ) ? $general_option['sps_content_username'][$sps_key] : '';
                    $args['sps']['content_password'] = isset( $general_option['sps_content_password'][$sps_key] ) ? $general_option['sps_content_password'][$sps_key] : '';

                    if( isset($args['post_content']) && isset($args['sps']['strict_mode']) && $args['sps']['strict_mode'] ) {
                        $args['post_content'] = addslashes($args['post_content']);
                    } else {
                        $args['post_content'] = do_shortcode($args['post_content']);
                    }

                    $loggedin_user_role = wp_get_current_user();
                    $matched_role = array_intersect( $loggedin_user_role->roles, array_keys( $args['sps']['roles'] ) );

                    if( !empty($sps_value) && !empty($matched_role) && in_array($sps_value, $sps_website) ) {
                        return $this->sps_remote_post( $action, $args );
                    }
                }
            }
        }

        function sps_remote_post( $action, $args = array() ) {
            do_action( 'spsp_before_send_data', $args );
            $args['sps_action'] = $action;
            $url = $args['sps']['host_name']."/wp-json/sps/v1/data"; 
            $return = wp_remote_post( $url, array( 'body' => $args ));
            return $return;
        }

        function sps_save_post( $post_ID, $post, $update, $tagids = '' ) {
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
                return;

            $sps_website = isset($_REQUEST['sps_website']) ? $_REQUEST['sps_website'] : array();
            $status_not = array('auto-draft', 'trash', 'inherit', 'draft');
            if($this->is_website_post && isset($post->post_status) && !in_array($post->post_status, $status_not) && !empty($sps_website) ) {

                global $wpdb, $sps, $sps_settings, $post_old_title;

                $args = (array) $post;
            
                if( !empty($post_old_title) ) {
                    $args['post_old_title'] = $post_old_title;
                } else {
                    $args['post_old_title'] = $args['post_title'];
                }

                if( has_post_thumbnail($post_ID) ) {
                   $args['featured_image'] = get_the_post_thumbnail_url($post_ID);
                }

                if( !empty($post->post_parent) ) {
                    $args['post_parent_slug'] = get_post_field("post_name", $post->post_parent);
                }

                $taxonomies = get_object_taxonomies( $args['post_type'] );
                if( !empty($taxonomies) ) {
                    $taxonomies_data = array();
                    foreach ($taxonomies as $taxonomy) {
                        $taxonomies_data[$taxonomy] = wp_get_post_terms( $post_ID, $taxonomy );
                    }
                    $args['taxonomies'] = $taxonomies_data;
                }

                $post_metas = get_post_meta($post_ID);
                if( !empty($post_metas) ) {
                    foreach ( $post_metas as $meta_key => $meta_value ) {
                        if( $meta_key != 'sps_website' ) {
                            $args['meta'][$meta_key] = isset($meta_value['0']) ? maybe_unserialize( $meta_value['0'] ) : '';
                        }
                    }
                }
                
                $response = $this->sps_send_data_to( 'add_update_post', $args, $sps_website );

                if( is_array($response) ) {
                    if( isset( $response['response']['code'] ) && $response['response']['code'] == 200 ) {
                        $other_site_post_id = $response['body'];
                    }
                }
            }
        }

        function sps_check_data( $content_mach, $post_data ) {

            global $wpdb;

            $the_slug = $post_data['post_name'];
            $the_title = isset( $post_data['post_old_title'] ) ? $post_data['post_old_title'] : '';

            $args_title = array(
              'title'        => $the_title,
              'post_type'   => $post_data['post_type']
            );
            $args_slug = array(
              'name'        => $the_slug,
              'post_type'   => $post_data['post_type']
            );
          
            $post_id = '';
            if($content_mach=="title") {
                $my_posts = get_posts($args_title);
                if($my_posts) { 
                    $post_id = $my_posts[0]->ID; 
                }
            } else if($content_mach=="title-slug") {
                $my_posts = get_posts($args_title);
                if($my_posts) {
                    $post_id = $my_posts[0]->ID; 
                } else { 
                    $my_posts2 = get_posts($args_slug);
                    if($my_posts2) { 
                        $post_id = $my_posts2[0]->ID; 
                    }
                }
            } else if($content_mach=="slug") {
                $my_posts = get_posts($args_slug);
                if($my_posts) { 
                    $post_id = $my_posts[0]->ID; 
                }
            } else if($content_mach=="slug-title") {
                $my_posts = get_posts($args_slug);
                if($my_posts) {
                    $post_id = $my_posts[0]->ID; 
                } else {
                    $my_posts = get_posts($args_title);
                    if($my_posts) {
                        $post_id = $my_posts[0]->ID; 
                    }
                }
            }

            return $post_id;
        }

        function grab_image($url,$saveto){

            $data = wp_remote_request( $url );
            
            if( isset( $data['body'] ) && isset( $data['response']['code'] ) && !empty( $data['response']['code'] ) ) {
                $raw = $data['body'];
                if(file_exists($saveto)){
                    unlink($saveto);
                }
                $fp = fopen($saveto,'x');
                fwrite($fp, $raw);
                fclose($fp);
            }
        }

        function sps_custom_wpkses_post_tags( $tags, $context ) {
            if ( 'post' === $context ) {
                $tags['iframe'] = array(
                    'src'             => true,
                    'height'          => true,
                    'width'           => true,
                    'frameborder'     => true,
                    'allowfullscreen' => true,
                );

                $tags['embed'] = array(
                    'type'   => true,
                    'src'    => true,
                    'height' => true,
                    'width'  => true,
                );
            }

            return $tags;
        }

        function sps_add_update_post( $author, $sps_sync_data ) {

            $return = array();
            $sps_sync_data['post_author'] = $author->ID;
            $post_id = $this->sps_check_data( $sps_sync_data['content_match'] , $sps_sync_data );
            $sps_sync_data['post_content'] = stripslashes($sps_sync_data['post_content']);

            if( !empty($sps_sync_data['post_parent']) && !empty($sps_sync_data['post_parent_slug']) ) {
                $parent_post_arg = array(
                  'name'        => $sps_sync_data['post_parent_slug'],
                  'post_type'   => $sps_sync_data['post_type']
                );

                $parent_post = get_posts($parent_post_arg);
                if($parent_post) { 
                    $sps_sync_data['post_parent'] = $parent_post[0]->ID; 
                }
            }

            // For allow some content tags like iframe
            add_filter( 'wp_kses_allowed_html', array( $this, 'sps_custom_wpkses_post_tags' ) , 10, 2 );

            $post_action = '';
            if( !empty($post_id) ) {
                $post_action = 'edit';
                $sps_sync_data['ID'] = $post_id;
                $post_id = wp_update_post( $sps_sync_data );
            } else {
                $post_action = 'add';
                $post_id = wp_insert_post( $sps_sync_data );
            }

            // For remove some content tags like iframe which are allowed above.
            remove_filter( 'wp_kses_allowed_html', array( $this, 'sps_custom_wpkses_post_tags' ) , 10, 2 );

            if( isset($sps_sync_data['taxonomies']) && !empty($sps_sync_data['taxonomies']) ) {
                foreach ($sps_sync_data['taxonomies'] as $taxonomy => $texonomy_data) {
                    if( is_taxonomy_hierarchical( $taxonomy ) ) {
                        // For hierarchical taxonomy - Categories
                        if( isset( $texonomy_data ) && !empty( $texonomy_data ) ) {
                            $post_categories = array();
                            foreach ( $texonomy_data as $category ) {
                                $term = term_exists( $category['name'], $taxonomy );
                                if( $term ) {
                                    $post_categories[] = $term['term_id'];
                                } else {
                                    $tag_temp = wp_insert_term( $category['name'], $taxonomy );
                                    $tag_id = $tag_temp['term_id'];
                                    $post_categories[] = $tag_id;
                                }
                            }
                            wp_set_post_terms( $post_id, $post_categories, $taxonomy, false );
                        } else {
                            wp_set_post_terms( $post_id );
                        }
                    } else {
                        // For non-hierarchical taxonomy - Tags
                        if( isset( $texonomy_data ) && !empty( $texonomy_data ) ) {
                            $post_tags = array();
                            foreach ( $texonomy_data as $tag ) {
                                $post_tags[] = $tag['name'];
                            }
                            wp_set_post_terms( $post_id, $post_tags, $taxonomy, false );
                        } else {
                            wp_set_post_terms( $post_id );
                        }
                    }
                }
            }

            if( isset($sps_sync_data['meta']) && !empty($sps_sync_data['meta']) ) {
                foreach ($sps_sync_data['meta'] as $meta_key => $meta_value) {
                    update_post_meta( $post_id, $meta_key, $meta_value );
                }
            }

            if( isset($sps_sync_data['featured_image']) && !empty($sps_sync_data['featured_image']) ) {
                
                $image_url        = $sps_sync_data['featured_image'];
                $image_arr        = explode( '/', $sps_sync_data['featured_image'] );
                $image_name       = end($image_arr);
                $upload_dir       = wp_upload_dir();
                $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name );
                $filename         = basename( $unique_file_name );

                // Check folder permission and define file location
                if( wp_mkdir_p( $upload_dir['path'] ) ) {
                    $file = $upload_dir['path'] . '/' . $filename;
                } else {
                    $file = $upload_dir['basedir'] . '/' . $filename;
                }

                // Create the image  file on the server
                $this->grab_image( $image_url, $file);

                // Check image file type
                $wp_filetype = wp_check_filetype( $filename, null );

                // Set attachment data
                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title'     => sanitize_file_name( $filename ),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                );

                // Create the attachment
                $attach_id = wp_insert_attachment( $attachment, $file, $post_id );

                // Include image.php
                require_once(ABSPATH . 'wp-admin/includes/image.php');

                // Define attachment metadata
                $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

                // Assign metadata to attachment
                wp_update_attachment_metadata( $attach_id, $attach_data );

                // And finally assign featured image to post
                $data = set_post_thumbnail( $post_id, $attach_id );
            }

            do_action( 'spsp_after_save_data', $post_id, $sps_sync_data );

            $return['status'] = __('success', SPS_txt_domain);
            $return['msg'] = __('Data proccessed successfully', SPS_txt_domain);
            $return['post_id'] = $post_id;
            $return['post_action'] = $post_action;
            return $return;
        }

        function sps_get_request( $request ) {

            $sps_sync_data = $request->get_params();
            if( isset($sps_sync_data['sps_action']) && !empty($sps_sync_data['sps_action']) ) {
                $this->is_website_post = false;
                
                $sps_host_name        = isset($sps_sync_data['sps']['host_name']) ? esc_url_raw($sps_sync_data['sps']['host_name']) : '';
                $sps_content_username = isset($sps_sync_data['sps']['content_username']) ? sanitize_text_field($sps_sync_data['sps']['content_username']) : '';
                $sps_content_password = isset($sps_sync_data['sps']['content_password']) ? sanitize_text_field($sps_sync_data['sps']['content_password']) : '';
                $sps_strict_mode      = isset($sps_sync_data['sps']['strict_mode']) ? sanitize_text_field($sps_sync_data['sps']['strict_mode']) : '';
                $sps_content_match    = isset($sps_sync_data['sps']['content_match']) ? sanitize_text_field($sps_sync_data['sps']['content_match']) : '';
                $sps_roles            = isset($sps_sync_data['sps']['roles']) ? sanitize_text_field($sps_sync_data['sps']['roles']) : '';
                $sps_action           = isset($sps_sync_data['sps_action']) ? 'sps_'.sanitize_text_field($sps_sync_data['sps_action']) : '';
                unset($sps_sync_data['sps']);

                $return = array();
                if( !empty($sps_content_username) && !empty($sps_content_password) ) {
                    $author = wp_authenticate( $sps_content_username, $sps_content_password );
                    if( isset($author->ID) && !empty($author->ID) ) {

                        unset($sps_sync_data['sps']);
                        unset($sps_sync_data['sps_action']);

                        if( isset($sps_sync_data['ID']) ) {
                            unset($sps_sync_data['ID']);
                        }

                        if( $sps_action == 'sps_authenticate' ) {
                            $return['status'] = __('success', SPS_txt_domain);
                            $return['msg'] = __('Authenitcate successfully.', SPS_txt_domain);
                        } else {
                            if( ( $sps_sync_data['post_type'] == 'page' && $author->has_cap('edit_pages') ) || $author->has_cap('edit_posts') ) {
                                $sps_sync_data['content_match'] = $sps_content_match;
                                $return = call_user_func( array( $this, $sps_action ), $author, $sps_sync_data );
                            } else {
                                $return['status'] = __('success', SPS_txt_domain);
                                $return['msg'] = __('You do not have permission to do the action.', SPS_txt_domain);
                            }
                        }
                    } else {
                        $return['status'] = __('failed', SPS_txt_domain);
                        $return['msg'] = __('Authenitcate failed.', SPS_txt_domain);
                    }
                } else {
                    $return['status'] = __('failed', SPS_txt_domain);
                    $return['msg'] = __('Username or Password is null.', SPS_txt_domain);
                }
                
                return new WP_REST_Response( $return, 200 );
            }
        }

        function spsp_grab_content_images( $post_id, $sps_sync_data ) {
            $post_content = stripslashes($sps_sync_data['post_content']);
            preg_match_all('/<img[^>]+>/i', $post_content, $images_tag);

            if( isset($images_tag[0]) && !empty($images_tag[0]) ) {
                foreach ($images_tag[0] as $img_tag) {
                    preg_match_all('/(alt|title|src)=("[^"]*")/i', $img_tag, $img_data);
                    if( isset($img_data[2][0]) && !empty($img_data[2][0]) && isset($img_data[1][0]) && $img_data[1][0] == 'src' ) {
                        $image_url = str_replace( '"', '', $img_data[2][0] );

                        // check image is exists
                        $args = array(
                            'post_type' => 'attachment',
                            'post_status' => 'inherit',
                            'meta_query' => array(
                                array(
                                    'key'       => 'old_site_url',
                                    'value'     => $image_url,
                                    'compare'   => '='
                                ),
                            ),
                        );

                        $attachment = new WP_Query( $args );

                        if( empty($attachment->posts) ) {

                            $image_arr        = explode( '/', $image_url );
                            $image_name       = end($image_arr);
                            $upload_dir       = wp_upload_dir();
                            $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name );
                            $filename         = basename( $unique_file_name );

                            // Check folder permission and define file location
                            if( wp_mkdir_p( $upload_dir['path'] ) ) {
                                $file = $upload_dir['path'] . '/' . $filename;
                            } else {
                                $file = $upload_dir['basedir'] . '/' . $filename;
                            }

                            // Create the image  file on the server
                            $this->grab_image( $image_url, $file);

                            // Check image file type
                            $wp_filetype = wp_check_filetype( $filename, null );

                            // Set attachment data
                            $attachment = array(
                                'post_mime_type' => $wp_filetype['type'],
                                'post_title'     => sanitize_file_name( $filename ),
                                'post_content'   => $image_url,
                                'post_status'    => 'inherit'
                            );
                            
                            $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
                            update_post_meta( $attach_id, 'old_site_url', $image_url );
                        } else {
                            $attachment_posts = $attachment->posts;
                            $attach_id = $attachment_posts[0]->ID;
                        }

                        $new_image_url = wp_get_attachment_url( $attach_id );
                        $post_content = str_replace($image_url, $new_image_url, $post_content);
                    }
                }
                
                wp_update_post( array( 'ID' => $post_id, 'post_content' => $post_content ) );
            }
        }
    }

    global $sps_sync, $post_old_title;
    $sps_sync = new SPS_Sync();

}

?>
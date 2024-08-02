<?php
global $sps, $sps_settings;
if( isset( $_REQUEST['sps_setting_save'] ) && isset( $_REQUEST['sps_setting'] ) && $_REQUEST['sps_setting'] != '' ) {
    do_action( 'sps_save_settings', $_POST );
}

echo '<div class="wrap sps_content">';

if( isset($_SESSION['sps_msg_status']) && $_SESSION['sps_msg_status'] ) { 
    echo '<div id="message" class="updated notice notice-success is-dismissible">';
    echo '<p>';
    echo (isset($_SESSION['sps_msg']) && $_SESSION['sps_msg']!='') ? $_SESSION['sps_msg'] : 'Something went wrong. Please try again';
    echo '</p>';
    echo '<button type="button" class="notice-dismiss"><span class="screen-reader-text">'.__('Dismiss this notice.',SPS_txt_domain).'</span></button>';
    echo '</div>';
	unset($_SESSION['sps_msg_status']);
	unset($_SESSION['sps_msg']);
} 

echo '<form name="sps_settings" id="sps_settings" method="post" >';
    
    global $sps, $sps_settings;

    wp_nonce_field('sps_nonce', 'sps_general_option_field');
    $general_option = $sps_settings->sps_get_settings_func();
    if(!empty($general_option)) {
        $total_record = count($general_option['sps_host_name']);
        $spcn = 0;
        
        echo '<div class="cmrc-table">';
            echo '<div class="setting-general" >';
                echo '<h2>'.__('General options', SPS_txt_domain).'</h2>';
                foreach ($general_option['sps_host_name'] as $sps_key => $sps_value) { 
                
                    $sps_host_name      = ($sps_value) ? $sps_value : '';
                    $sps_strict_mode    = isset($general_option['sps_strict_mode'][$sps_key]) ? $general_option['sps_strict_mode'][$sps_key] : 1;
                    $sps_content_match  = isset($general_option['sps_content_match'][$sps_key]) ? $general_option['sps_content_match'][$sps_key] : 'title';
                    $sps_roles_allowed  = isset($general_option['sps_roles_allowed'][$sps_key]) ? $general_option['sps_roles_allowed'][$sps_key] : array();
                    $sps_contributor    = isset($sps_roles_allowed['roles']['contributor']) ? $sps_roles_allowed['roles']['contributor'] : '';
                    $sps_author         = isset($sps_roles_allowed['roles']['author']) ? $sps_roles_allowed['roles']['author'] : '';
                    $sps_editor         = isset($sps_roles_allowed['roles']['editor']) ? $sps_roles_allowed['roles']['editor'] : '';
                    $sps_selected       = isset($general_option['sps_selected'][$sps_key]) ? $general_option['sps_selected'][$sps_key] : '';
                    $sps_content_username  = isset($general_option['sps_content_username'][$sps_key]) ? $general_option['sps_content_username'][$sps_key] : '';
                    $sps_content_password  = isset($general_option['sps_content_password'][$sps_key]) ? $general_option['sps_content_password'][$sps_key] : '';
                    ?>
                    <div class="remove_site_<?php echo $spcn; ?>">
                        <table class="form-table sps-setting-form count_table">
                            <tbody>
                                <tr>    
                                    <th><label for="sps_host_name_<?php echo $spcn; ?>"><?php _e('Host Name of Target', SPS_txt_domain); ?></label></th>
                                    <td>
                                        <input type="text" name="sps_host_name[<?php echo $spcn; ?>]" id="sps_host_name_<?php echo $spcn; ?>" class="sps_input sps_url" value="<?php echo sanitize_url($sps_host_name) ?>" />
                                        <?php if($spcn!=0) { ?>
                                        <a href="javascript:;" class="remove_site" data-site_id="<?php echo $spcn; ?>"> Remove Site </a>
                                        <?php } ?>
                                        <p><?php _e('https://example.com - This is the URL that your Content will be Pushed to. If WordPress is installed in a subdirectory, include the subdirectory.', SPS_txt_domain); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="sps_content_username_<?php echo $spcn; ?>"><?php _e('Username', SPS_txt_domain); ?></label></th>
                                    <td>
                                        <input type="text" name="sps_content_username[<?php echo $spcn; ?>]" id="sps_content_username_<?php echo $spcn; ?>" class="sps_input" value="<?php echo sanitize_user($sps_content_username) ?>" />
                                        <p><?php _e('Enter', SPS_txt_domain); ?> <span class="sps_username"></span> <?php _e('website username', SPS_txt_domain); ?></p>
                                    </td>
                                </tr>
                                <tr>    
                                    <th><label for="sps_content_password_<?php echo $spcn; ?>"><?php _e('Password', SPS_txt_domain); ?></label></th>
                                    <td>
                                        <div class="sps_password_box">
                                            <input type="password" name="sps_content_password[<?php echo $spcn; ?>]" id="sps_content_password_<?php echo $spcn; ?>" class="sps_input" value="<?php echo wp_strip_all_tags( stripslashes($sps_content_password) ) ?>" />
                                            <span class="dashicons dashicons-visibility sps_show_pass"></span>
                                            <span class="dashicons dashicons-hidden sps_hide_pass"></span>
                                        </div>
                                        <p><?php _e('Enter', SPS_txt_domain); ?> <span class="sps_password"></span> <?php _e('website password', SPS_txt_domain); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="sps_strict_mode_<?php echo $spcn; ?>"><?php _e('Strict Mode', SPS_txt_domain); ?></label></th>
                                    <td>
                                        <input <?php if($sps_strict_mode==1) { echo "checked='checked'"; } ?> type="radio" name="sps_strict_mode[<?php echo $spcn; ?>]" value="1" class="sps_radio" id="sps_strict_mode_on_<?php echo $spcn; ?>" >
                                        <label for="sps_strict_mode_on_<?php echo $spcn; ?>"> <?php _e('On - WordPress and SyncPostWithOtherSite for Content versions must match on Source and Target in order to perform operations.', SPS_txt_domain); ?> </label>

                                        <br>

                                        <input <?php if($sps_strict_mode==0) { echo "checked='checked'"; } ?> type="radio" name="sps_strict_mode[<?php echo $spcn; ?>]" value="0" class="sps_radio" id="sps_strict_mode_off_<?php echo $spcn; ?>"> 
                                        <label for="sps_strict_mode_off_<?php echo $spcn; ?>"><?php _e('Off - WordPress and SyncPostWithOtherSite for Content versions do not need to match.', SPS_txt_domain); ?> </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="sps_content_match_<?php echo $spcn; ?>"><?php _e('Content Match Mode', SPS_txt_domain); ?></label></th>
                                    <td>
                                        <select id="sps_content_match_<?php echo $spcn; ?>" name="sps_content_match[<?php echo $spcn; ?>]" class="sps_select">
                                            <option <?php if($sps_content_match=='slug') { echo "selected"; } ?> value="slug">Post Slug</option>
                                            <?php /*<option <?php if($sps_content_match=='title') { echo "selected"; } ?> value="title" >Post Title</option>
                                            <option <?php if($sps_content_match=='title-slug') { echo "selected"; } ?> value="title-slug">Post Title, then Post Slug</option>
                                            <option <?php if($sps_content_match=='slug-title') { echo "selected"; } ?> value="slug-title">Post Slug, then Post Title</option> */ ?>
                                        </select>
                                        <p><?php _e('Post slug - Search for matching Content on Target by Post Slug only.', SPS_txt_domain); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="sps_roles_allowed_<?php echo $spcn; ?>"><?php _e('Roles Allowed to use', SPS_txt_domain); ?></label></th>
                                    <td>
                                        <input <?php if($sps_contributor) { echo "checked"; } ?> type="checkbox" class="sps_checkbox" name="sps_roles_allowed[<?php echo $spcn; ?>][roles][contributor]" id="sps_roles_contributor_<?php echo $spcn; ?>" > 
                                        <label for="sps_roles_contributor_<?php echo $spcn; ?>"><?php _e('Contributor', SPS_txt_domain); ?></label><br>

                                        <input <?php if($sps_author) { echo "checked"; } ?> type="checkbox" class="sps_checkbox" name="sps_roles_allowed[<?php echo $spcn; ?>][roles][author]" id="sps_roles_author_<?php echo $spcn; ?>" > 
                                        <label for="sps_roles_author_<?php echo $spcn; ?>"><?php _e('Author', SPS_txt_domain); ?></label><br>

                                        <input <?php if($sps_editor) { echo "checked"; } ?> type="checkbox" class="sps_checkbox" name="sps_roles_allowed[<?php echo $spcn; ?>][roles][editor]" id="sps_roles_editor_<?php echo $spcn; ?>" > 
                                        <label for="sps_roles_editor_<?php echo $spcn; ?>"><?php _e('Editor', SPS_txt_domain); ?></label><br>

                                        <input type="checkbox" class="sps_checkbox" name="sps_roles_allowed[<?php echo $spcn; ?>][roles][administrator]" checked="checked" id="sps_roles_administrator_<?php echo $spcn; ?>" disabled="disabled"> 
                                        <label for="sps_roles_administrator_<?php echo $spcn; ?>"><?php _e('Administrator', SPS_txt_domain); ?></label><br>

                                        <p><?php _e('Select the Roles you wish to have access to the SyncPostWithOtherSite User Interface. Only these Roles will be allowed to perform Syncing operations.', SPS_txt_domain); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="sps_selected_<?php echo $spcn; ?>"><?php _e('By Default Selected', SPS_txt_domain); ?></label></th>
                                    <td>
                                        <input <?php if($sps_selected) { echo "checked"; } ?> type="checkbox" class="sps_checkbox" name="sps_selected[<?php echo $spcn; ?>]" id="sps_selected_<?php echo $spcn; ?>" /> 
                                        <label for="sps_selected_<?php echo $spcn; ?>"><?php _e('Site will be auto selected on Add/Edit Posts', SPS_txt_domain); ?></label><br>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <hr/>   
                    </div>         
                    <?php
                    $spcn++;
                }       

            echo '<input type="hidden" id="auto_increment" value="'.$spcn.'">';
            echo '</div>';
        echo '</div>';
       

    }
    ?>

    <script id="sps_setting_table" type="text/html">
        <div class="remove_site_{sps_no}">
            <table class="form-table sps-setting-form count_table">
                <tbody>
                    <tr>    
                        <th><label for="sps_host_name_{sps_no}"><?php _e('Host Name of Target', SPS_txt_domain); ?></label></th>
                        <td>
                            <input type="text" name="sps_host_name[{sps_no}]" id="sps_host_name_{sps_no}" class="sps_input sps_url" />
                            <a href="javascript:;" class="remove_site" data-site_id="{sps_no}"><?php _e(' Remove Site ', SPS_txt_domain); ?></a>
                            <p><?php _e('https://example.com - This is the URL that your Content will be Pushed to. If WordPress is installed in a subdirectory, include the subdirectory.', SPS_txt_domain); ?></p>
                        </td>
                    </tr>
                    <tr>    
                        <th><label for="sps_content_username_{sps_no}"><?php _e('Username', SPS_txt_domain); ?></label></th>
                        <td>
                            <input type="text" name="sps_content_username[{sps_no}]" id="sps_content_username_{sps_no}" class="sps_input" value="" />
                            <p><?php _e('Enter', SPS_txt_domain); ?> <span class="sps_username"></span> <?php _e('website username', SPS_txt_domain); ?></p>
                        </td>
                    </tr>
                    <tr>    
                        <th><label for="sps_content_password_{sps_no}"><?php _e('Password', SPS_txt_domain); ?></label></th>
                        <td>
                            <div class="sps_password_box">
                                <input type="password" name="sps_content_password[{sps_no}]" id="sps_content_password_{sps_no}" class="sps_input" value="" />
                                <span class="dashicons dashicons-visibility sps_show_pass"></span>
                                <span class="dashicons dashicons-hidden sps_hide_pass"></span>
                            </div>
                            <p><?php _e('Enter', SPS_txt_domain); ?> <span class="sps_password"></span> <?php _e('website password', SPS_txt_domain); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sps_strict_mode_{sps_no}"><?php _e('Strict Mode', SPS_txt_domain); ?></label></th>
                        <td>
                            <input type="radio" name="sps_strict_mode[{sps_no}]" value="1" class="sps_radio" id="sps_strict_mode_on_{sps_no}" checked="checked"> 
                            <label for="sps_strict_mode_on_{sps_no}"><?php _e('On - WordPress and SyncPostWithOtherSite for Content versions must match on Source and Target in order to perform operations.', SPS_txt_domain); ?></label>
                            <br>

                            <input type="radio" name="sps_strict_mode[{sps_no}]" value="0" class="sps_radio" id="sps_strict_mode_off_{sps_no}"> 
                            <label for="sps_strict_mode_off_{sps_no}"><?php _e('Off - WordPress and SyncPostWithOtherSite for Content versions do not need to match.', SPS_txt_domain); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sps_content_match_{sps_no}"><?php _e('Content Match Mode', SPS_txt_domain); ?></label></th>
                        <td>
                            <select id="sps_content_match_{sps_no}" name="sps_content_match[{sps_no}]" class="sps_select">
                                <option value="slug"><?php _e('Post Slug', SPS_txt_domain); ?></option>
                                <?php /*<option value="title" selected="selected"><?php _e('Post Title', SPS_txt_domain); ?></option>
                                <option value="title-slug"><?php _e('Post Title, then Post Slug', SPS_txt_domain); ?></option>
                                <option value="slug-title"><?php _e('Post Slug, then Post Title', SPS_txt_domain); ?></option> */ ?>
                            </select>
                            <p><?php _e('Post slug - Search for matching Content on Target by Post slug only.', SPS_txt_domain); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sps_roles_allowed_{sps_no}"><?php _e('Roles Allowed to use', SPS_txt_domain); ?></label></th>
                        <td>
                            <input type="checkbox" class="sps_checkbox" name="sps_roles_allowed[{sps_no}][roles][contributor]" id="sps_roles_contributor_{sps_no}"> 
                            <label for="sps_roles_contributor_{sps_no}"><?php _e('Contributor', SPS_txt_domain); ?></label><br>
                            <input type="checkbox" class="sps_checkbox" name="sps_roles_allowed[{sps_no}][roles][author]" id="sps_roles_author_{sps_no}" checked="checked"> 
                            <label for="sps_roles_author_{sps_no}"><?php _e('Author', SPS_txt_domain); ?></label><br>
                            <input type="checkbox" class="sps_checkbox" name="sps_roles_allowed[{sps_no}][roles][editor]" id="sps_roles_editor_{sps_no}" checked="checked"> 
                            <label for="sps_roles_editor_{sps_no}"><?php _e('Editor', SPS_txt_domain); ?></label><br>
                            <input type="checkbox" class="sps_checkbox" name="sps_roles_allowed[{sps_no}][roles][administrator]" id="sps_roles_administrator_{sps_no}" checked="checked" disabled="disabled"> 
                            <label for="sps_roles_administrator_{sps_no}"><?php _e('Administrator', SPS_txt_domain); ?></label><br>

                            <p><?php _e('Select the Roles you wish to have access to the SyncPostWithOtherSite User Interface. Only these Roles will be allowed to perform Syncing operations.', SPS_txt_domain); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sps_selected_{sps_no}"><?php _e('By Default Selected', SPS_txt_domain); ?></label></th>
                        <td>
                            <input <?php if($sps_selected) { echo "checked"; } ?> type="checkbox" class="sps_checkbox" name="sps_selected[{sps_no}]" id="sps_selected_{sps_no}" > 
                            <label for="sps_selected_{sps_no}"><?php _e('Site will be auto selected on Add/Edit Posts', SPS_txt_domain); ?></label><br>
                        </td>
                    </tr>
                </tbody>
            </table>
            <hr/>
        </div>
    </script>
    <?php
    echo '<p class="add_more_site">';
    echo '<input type="button" name="add_more_site" class="button-primary " value="Add more site" >';
    echo '</p>';

    echo '<p class="submit">';
    echo '<input type="hidden" name="sps_setting" id="sps_setting" value="sps_setting" />';
    echo '<input name="sps_setting_save" class="button-primary sps_setting_save" type="submit" value="Save changes" />';
    echo '</p>';

echo '</form>';
echo '</div>';
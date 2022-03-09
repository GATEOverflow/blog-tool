<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    $adminsection = strtolower( qa_request_part( 2 ) );

    //	Get list of categories and all options

    $categories = qa_db_select_with_pending( qa_db_category_nav_selectspec( null, true ) );


//	See if we need to redirect

    if ( empty( $adminsection ) ) {
        $subnav = qa_admin_sub_navigation();

        if ( isset( $subnav[ @$_COOKIE[ 'qa_admin_last' ] ] ) )
            qa_redirect( $_COOKIE[ 'qa_admin_last' ] );
        elseif ( count( $subnav ) ) {
            reset( $subnav );
            qa_redirect( key( $subnav ) );
        }
    }


//	Check admin privileges (do late to allow one DB query)

    if ( !qa_admin_check_privileges( $qa_content ) )
        return $qa_content;


//	For non-text options, lists of option types, minima and maxima

    $optiontype = array(
        'qas_blog_max_len_post_title'           => 'number',
        'qas_blog_max_num_post_tags'            => 'number',
        'qas_blog_min_len_c_content'            => 'number',
        'qas_blog_min_len_post_content'         => 'number',
        'qas_blog_min_len_post_title'           => 'number',
        'qas_blog_min_num_post_tags'            => 'number',
        'qas_blog_urls_title_length'            => 'number',
        'qas_blog_show_fewer_cs_count'          => 'number',
        'qas_blog_show_fewer_cs_from'           => 'number',
        'qas_blog_show_full_date_days'          => 'number',
        'qas_blog_show_content_on_list_len'     => 'number',
        'qas_blog_related_post_widg_count'      => 'number',
        'qas_blog_recent_post_widg_count'       => 'number',
        'qas_blog_recent_comments_widg_count'   => 'number',
        'qas_blog_recent_comments_w_trunc_len'  => 'number',
        'qas_blog_page_size_ps'                 => 'number',
        'qas_blog_featured_page_size_ps'        => 'number',
        'qas_blog_allow_close_ps'               => 'checkbox',
        'qas_blog_allow_view_p_bots'            => 'checkbox',
        'qas_blog_comment_on_ps'                => 'checkbox',
        'qas_blog_extra_field_active'           => 'checkbox',
        'qas_blog_extra_field_display'          => 'checkbox',
        'qas_blog_flagging_of_posts'            => 'checkbox',
        'qas_blog_urls_remove_accents'          => 'checkbox',
        'qas_blog_show_c_reply_buttons'         => 'checkbox',
        'qas_blog_show_custom_post'             => 'checkbox',
        'qas_blog_show_custom_comment'          => 'checkbox',
        'qas_blog_auto_update_search_box'       => 'checkbox',
        'qas_blog_show_list_view_counts'        => 'checkbox',
        'qas_blog_show_view_count_p_page'       => 'checkbox',
        'qas_blog_show_when_created'            => 'checkbox',
        'qas_blog_allow_drafts'                 => 'checkbox',
        'qas_blog_tag_separator_comma'          => 'checkbox',
        'qas_blog_do_count_p_views'             => 'checkbox',
        'qas_blog_allow_featured_posts'         => 'checkbox',
        'qas_blog_show_content_on_list'         => 'checkbox',
        'qas_blog_list_content_trunc'           => 'checkbox',
        'qas_blog_show_read_more_btn'           => 'checkbox',
        'qas_blog_show_image_on_list'           => 'checkbox',
        'qas_blog_show_comment_count'           => 'checkbox',
        'qas_blog_show_user_post_count'         => 'checkbox',
        'qas_blog_show_post_updates'            => 'checkbox',
        'qas_blog_recent_comments_w_trunc'      => 'checkbox',
        'qas_blog_xml_sitemap_show_posts'       => 'checkbox',
        'qas_blog_xml_sitemap_show_tag_ps'      => 'checkbox',
        'qas_blog_xml_sitemap_show_category_ps' => 'checkbox',
        'qas_blog_xml_sitemap_show_categories'  => 'checkbox',
        'qas_blog_adcode_blog_top'              => 'checkbox',
        'qas_blog_adcode_blog_top_content'      => 'textarea',
        'qas_blog_adcode_after_post'            => 'checkbox',
        'qas_blog_adcode_after_post_content'    => 'textarea',
        'qas_blog_adcode_before_post'           => 'checkbox',
        'qas_blog_adcode_before_post_content'   => 'textarea',
        'qas_blog_adcode_w'                     => 'checkbox',
        'qas_blog_adcode_w_content'             => 'textarea',
        'qas_blog_warn_on_leave'                => 'checkbox',
        'qas_blog_show_next_prev'               => 'checkbox',
        'qas_blog_allow_nested_cmnts'           => 'checkbox',
        'qas_blog_max_allow_nesting'            => 'number',
    );

    $optionmaximum = array(
        'qas_blog_max_len_post_title' => QA_DB_MAX_TITLE_LENGTH,
        'qas_blog_max_allow_nesting' => 10,
    );

    $optionminimum = array(
        'qas_blog_max_num_post_tags' => 2,
        'qas_blog_max_allow_nesting' => 2,
    );


//	Define the options to show (and some other visual stuff) based on request

    $formstyle = 'tall';
    $checkboxtodisplay = null;

    $maxpermitpost = max( qa_opt( 'qas_blog_permit_post_b' ), 0 );
    if ( qa_opt( 'qas_blog_comment_on_ps' ) )
        $maxpermitpost = max( $maxpermitpost, qa_opt( 'qas_blog_permit_post_c' ) );

    switch ( $adminsection ) {

        case 'viewing':
            $subtitle = 'admin/viewing_title';
            $showoptions = array( 'qas_blog_urls_title_length', 'qas_blog_urls_remove_accents', 'qas_blog_do_count_p_views', 'qas_blog_show_list_view_counts', 'qas_blog_show_view_count_p_page', '', 'qas_blog_show_when_created', 'qas_blog_show_full_date_days', '' );

            if ( qa_opt( 'qas_blog_comment_on_ps' ) )
                array_push( $showoptions, 'qas_blog_show_fewer_cs_from', 'qas_blog_show_fewer_cs_count', 'qas_blog_show_comment_count', 'qas_blog_show_c_reply_buttons' );

            array_push( $showoptions, '', 'qas_blog_page_size_ps', 'qas_blog_featured_page_size_ps', '' );

            array_push( $showoptions, 'qas_blog_show_image_on_list', 'qas_blog_show_content_on_list', 'qas_blog_list_content_trunc', 'qas_blog_show_content_on_list_len', 'qas_blog_show_read_more_btn', 'qas_blog_show_post_updates' );

            $showoptions[] = 'qas_blog_auto_update_search_box';

            array_push( $showoptions, '', 'qas_blog_show_user_post_count', '', 'qas_blog_related_post_widg_count', 'qas_blog_recent_post_widg_count', 'qas_blog_recent_comments_widg_count', 'qas_blog_recent_comments_w_trunc', 'qas_blog_recent_comments_w_trunc_len', 'qas_blog_show_next_prev' , '' );

            $showoptions[] = 'qas_blog_xml_sitemap_show_posts';

            if ( qas_blog_using_tags() ) {
                $showoptions[] = 'qas_blog_xml_sitemap_show_tag_ps';
            }

            if ( qas_blog_using_categories() ) {
                array_push( $showoptions, 'qas_blog_xml_sitemap_show_category_ps', 'qas_blog_xml_sitemap_show_categories' );
            }

            array_push( $showoptions, '', 'qas_blog_adcode_blog_top', 'qas_blog_adcode_blog_top_content', 'qas_blog_adcode_before_post', 'qas_blog_adcode_before_post_content', 'qas_blog_adcode_after_post', 'qas_blog_adcode_after_post_content', 'qas_blog_adcode_w', 'qas_blog_adcode_w_content' );

            $formstyle = 'wide';

            $checkboxtodisplay = array(
                'qas_blog_show_list_view_counts'       => 'option_qas_blog_do_count_p_views',
                'qas_blog_show_view_count_p_page'      => 'option_qas_blog_do_count_p_views',
                'qas_blog_show_full_date_days'         => 'option_qas_blog_show_when_created',
                'qas_blog_list_content_trunc'          => 'option_qas_blog_show_content_on_list',
                'qas_blog_show_content_on_list_len'    => 'option_qas_blog_show_content_on_list && option_qas_blog_list_content_trunc',
                'qas_blog_show_read_more_btn'          => 'option_qas_blog_show_content_on_list',
                'qas_blog_recent_comments_w_trunc_len' => 'option_qas_blog_recent_comments_w_trunc',
                'qas_blog_adcode_after_post_content'   => 'option_qas_blog_adcode_after_post',
                'qas_blog_adcode_before_post_content'  => 'option_qas_blog_adcode_before_post',
                'qas_blog_adcode_w_content'            => 'option_qas_blog_adcode_w',
                'qas_blog_adcode_blog_top_content'     => 'option_qas_blog_adcode_blog_top',
            );
            break;

        case 'posting':
            $getoptions = qa_get_options( array( 'qas_blog_tags_or_categories' ) );

            $subtitle = 'admin/posting_title';

            $showoptions = array( 'qas_blog_tags_or_categories', '', 'qas_blog_allow_close_ps', '', 'qas_blog_comment_on_ps', '', 'qas_blog_allow_drafts', '', 'qas_blog_allow_featured_posts', 'qas_blog_default_home', 'qas_blog_featured_page_layout', '' );

            if ( count( qa_list_modules( 'editor' ) ) > 1 )
                array_push( $showoptions, 'qas_blog_editor_for_ps', 'qas_blog_editor_for_cs', '', 'qas_blog_warn_on_leave', '' );

            array_push( $showoptions, 'qas_blog_show_custom_post', 'qas_blog_custom_post', 'qas_blog_extra_field_active', 'qas_blog_extra_field_prompt', 'qas_blog_extra_field_display', 'qas_blog_extra_field_label', 'qas_blog_show_custom_comment', 'qas_blog_custom_comment', '' );

            array_push( $showoptions, 'qas_blog_min_len_post_title', 'qas_blog_max_len_post_title', 'qas_blog_min_len_post_content' );

            if ( qa_using_tags() )
                array_push( $showoptions, 'qas_blog_min_num_post_tags', 'qas_blog_max_num_post_tags', 'qas_blog_tag_separator_comma' );

            array_push( $showoptions, 'qas_blog_min_len_c_content', '', 'qas_blog_allow_nested_cmnts', 'qas_blog_max_allow_nesting' );

            $formstyle = 'wide';

            $checkboxtodisplay = array(
                'qas_blog_editor_for_cs'            => 'option_qas_blog_comment_on_ps',
                'qas_blog_default_home'             => 'option_qas_blog_allow_featured_posts',
                'qas_blog_featured_page_layout'     => 'option_qas_blog_allow_featured_posts',
                'qas_blog_custom_post'              => 'option_qas_blog_show_custom_post',
                'qas_blog_extra_field_prompt'       => 'option_qas_blog_extra_field_active',
                'qas_blog_extra_field_display'      => 'option_qas_blog_extra_field_active',
                'qas_blog_extra_field_label'        => 'option_qas_blog_extra_field_active && option_qas_blog_extra_field_display',
                'qas_blog_extra_field_label_hidden' => '!option_qas_blog_extra_field_display',
                'qas_blog_extra_field_label_shown'  => 'option_qas_blog_extra_field_display',
                'qas_blog_show_custom_comment'      => 'option_qas_blog_comment_on_ps',
                'qas_blog_custom_comment'           => 'option_qas_blog_show_custom_comment && (option_qas_blog_comment_on_ps)',
                'qas_blog_min_len_c_content'        => 'option_qas_blog_comment_on_ps',
                'qas_blog_max_allow_nesting'        => 'option_qas_blog_allow_nested_cmnts',
            );
            break;

        case 'permissions':
            $subtitle = 'admin/permissions_title';

            $permitoptions = qas_blog_get_permit_options();

            $showoptions = array();
            $checkboxtodisplay = array();

            foreach ( $permitoptions as $permitoption ) {
                $showoptions[] = $permitoption;

                if ( $permitoption == 'qas_blog_permit_view_post_page' ) {
                    $showoptions[] = 'qas_blog_allow_view_p_bots';
                    $checkboxtodisplay[ 'qas_blog_allow_view_p_bots' ] = 'option_qas_blog_permit_view_post_page<' . qa_js( QA_PERMIT_ALL );

                } else {
                    $showoptions[] = $permitoption . '_points';
                    $checkboxtodisplay[ $permitoption . '_points' ] = '(option_' . $permitoption . '==' . qa_js( QA_PERMIT_POINTS ) .
                        ')||(option_' . $permitoption . '==' . qa_js( QA_PERMIT_POINTS_CONFIRMED ) . ')||(option_' . $permitoption . '==' . qa_js( QA_PERMIT_APPROVED_POINTS ) . ')';
                }
            }

            $formstyle = 'wide';
            break;

        default:
            $pagemodules = qa_load_modules_with( 'page', 'match_request' );
            $request = qa_request();

            foreach ( $pagemodules as $pagemodule )
                if ( $pagemodule->match_request( $request ) )
                    return $pagemodule->process_request( $request );

            return include QA_INCLUDE_DIR . 'qa-page-not-found.php';
            break;
    }


//	Filter out blanks to get list of valid options

    $getoptions = array();
    foreach ( $showoptions as $optionname )
        if ( strlen( $optionname ) && ( strpos( $optionname, '/' ) === false ) ) // empties represent spacers in forms
            $getoptions[] = $optionname;


//	Process user actions

    $errors = array();
    $securityexpired = false;

    $formokhtml = null;

    if ( qa_clicked( 'doresetoptions' ) ) {
        if ( !qa_check_form_security_code( 'admin/' . $adminsection, qa_post_text( 'code' ) ) )
            $securityexpired = true;

        else {
            qas_blog_reset_options( $getoptions );
            $formokhtml = qa_lang_html( 'admin/options_reset' );
        }
    } elseif ( qa_clicked( 'dosaveoptions' ) ) {
        if ( !qa_check_form_security_code( 'admin/' . $adminsection, qa_post_text( 'code' ) ) )
            $securityexpired = true;

        else {
            foreach ( $getoptions as $optionname ) {
                $optionvalue = qa_post_text( 'option_' . $optionname );

                if (
                    ( @$optiontype[ $optionname ] == 'number' ) ||
                    ( @$optiontype[ $optionname ] == 'checkbox' ) ||
                    ( ( @$optiontype[ $optionname ] == 'number-blank' ) && strlen( $optionvalue ) )
                )
                    $optionvalue = (int) $optionvalue;

                if ( isset( $optionmaximum[ $optionname ] ) )
                    $optionvalue = min( $optionmaximum[ $optionname ], $optionvalue );

                if ( isset( $optionminimum[ $optionname ] ) )
                    $optionvalue = max( $optionminimum[ $optionname ], $optionvalue );

                qa_set_option( $optionname, $optionvalue );
            }

            $formokhtml = qa_lang_html( 'admin/options_saved' );
        }
    }

    //	Get the actual options

    $options = qa_get_options( $getoptions );


    //	Prepare content for theme

    $qa_content = qa_content_prepare();

    $qa_content[ 'title' ] = qa_lang_html( 'qas_admin/admin_title' ) . ' - ' . qa_lang_html( $subtitle );
    $qa_content[ 'error' ] = $securityexpired ? qa_lang_html( 'admin/form_security_expired' ) : qa_admin_page_error();

    $qa_content[ 'script_rel' ][] = 'qa-content/qa-admin.js?' . QA_VERSION;

    $qa_content[ 'form' ] = array(
        'ok'      => $formokhtml,

        'tags'    => 'method="post" action="' . qa_self_html() . '" name="admin_form" onsubmit="document.forms.admin_form.has_js.value=1; return true;"',

        'style'   => $formstyle,

        'fields'  => array(),

        'buttons' => array(
            'save'  => array(
                'tags'  => 'id="dosaveoptions"',
                'label' => qa_lang_html( 'admin/save_options_button' ),
            ),

            'reset' => array(
                'tags'  => 'name="doresetoptions"',
                'label' => qa_lang_html( 'admin/reset_options_button' ),
            ),
        ),

        'hidden'  => array(
            'dosaveoptions' => '1', // for IE
            'has_js'        => '0',
            'code'          => qa_get_form_security_code( 'admin/' . $adminsection ),
        ),
    );

    function qa_optionfield_make_select( &$optionfield, $options, $value, $default )
    {
        $optionfield[ 'type' ] = 'select';
        $optionfield[ 'options' ] = $options;
        $optionfield[ 'value' ] = isset( $options[ qa_html( $value ) ] ) ? $options[ qa_html( $value ) ] : @$options[ $default ];
    }

    $indented = false;

    foreach ( $showoptions as $optionname )
        if ( empty( $optionname ) ) {
            $indented = false;

            $qa_content[ 'form' ][ 'fields' ][] = array(
                'type' => 'blank',
            );

        } elseif ( strpos( $optionname, '/' ) !== false ) {
            $qa_content[ 'form' ][ 'fields' ][] = array(
                'type'  => 'static',
                'label' => qa_lang_html( $optionname ),
            );

            $indented = true;

        } else {
            $type = @$optiontype[ $optionname ];
            if ( $type == 'number-blank' )
                $type = 'number';

            $value = $options[ $optionname ];

            $optionfield = array(
                'id'    => $optionname,
                'label' => ( $indented ? '&ndash; ' : '' ) . qa_lang_html( 'qas_options/' . $optionname ),
                'tags'  => 'name="option_' . $optionname . '" id="option_' . $optionname . '"',
                'value' => qa_html( $value ),
                'type'  => $type,
                'error' => qa_html( @$errors[ $optionname ] ),
            );

            if ( isset( $optionmaximum[ $optionname ] ) )
                $optionfield[ 'note' ] = qa_lang_html_sub( 'admin/maximum_x', $optionmaximum[ $optionname ] );

            $feedrequest = null;
            $feedisexample = false;

            switch ( $optionname ) { // special treatment for certain options

                case 'qas_blog_tags_or_categories':
                    qa_optionfield_make_select( $optionfield, array(
                        ''   => qa_lang_html( 'admin/no_classification' ),
                        't'  => qa_lang_html( 'admin/tags' ),
                        'c'  => qa_lang_html( 'admin/categories' ),
                        'tc' => qa_lang_html( 'admin/tags_and_categories' ),
                    ), $value, 'tc' );

                    $optionfield[ 'error' ] = '';

                    if ( qa_opt( 'cache_blog_tagcount' ) && !qas_blog_using_tags() )
                        $optionfield[ 'error' ] .= qa_lang_html( 'admin/tags_not_shown' ) . ' ';

                    if ( !qa_using_categories() )
                        foreach ( $categories as $category )
                            if ( $category[ 'qcount' ] ) {
                                $optionfield[ 'error' ] .= qa_lang_html( 'admin/categories_not_shown' );
                                break;
                            }
                    break;

                case 'qas_blog_min_len_post_title':
                case 'qas_blog_urls_title_length':
                case 'qas_blog_min_len_post_content':
                case 'qas_blog_min_len_c_content':
                case 'qas_blog_show_content_on_list_len':
                    $optionfield[ 'note' ] = qa_lang_html( 'admin/characters' );
                    break;

                case 'qas_blog_min_num_post_tags':
                case 'qas_blog_max_num_post_tags':
                    $optionfield[ 'note' ] = qa_lang_html_sub( 'main/x_tags', '' ); // this to avoid language checking error: a_lang('main/1_tag')
                    break;

                case 'qas_blog_show_full_date_days':
                    $optionfield[ 'note' ] = qa_lang_html_sub( 'main/x_days', '' );
                    break;

                case 'qas_blog_show_fewer_cs_from':
                case 'qas_blog_show_fewer_cs_count':
                    $optionfield[ 'note' ] = qa_lang_html_sub( 'main/x_comments', '' );
                    break;

                case 'qas_blog_editor_for_ps':
                case 'qas_blog_editor_for_cs':
                    $editors = qa_list_modules( 'editor' );

                    $selectoptions = array();
                    $optionslinks = false;

                    foreach ( $editors as $editor ) {
                        $selectoptions[ qa_html( $editor ) ] = strlen( $editor ) ? qa_html( $editor ) : qa_lang_html( 'admin/basic_editor' );

                        if ( $editor == $value ) {
                            $module = qa_load_module( 'editor', $editor );

                            if ( method_exists( $module, 'admin_form' ) )
                                $optionfield[ 'note' ] = '<a href="' . qa_admin_module_options_path( 'editor', $editor ) . '">' . qa_lang_html( 'admin/options' ) . '</a>';
                        }
                    }

                    qa_optionfield_make_select( $optionfield, $selectoptions, $value, '' );
                    break;

                case 'qas_blog_default_home':

                    $selectoptions = array(
                        'featured' => 'Featured posts',
                        'recent'   => 'Recent posts',
                        'views'    => 'Most Viewed posts',
                    );
                    qa_optionfield_make_select( $optionfield, $selectoptions, $value, '' );
                    break;

                case 'qas_blog_featured_page_layout':

                    $selectoptions = array(
                        'list' => 'List',
                        'grid' => 'Grid',
                    );
                    qa_optionfield_make_select( $optionfield, $selectoptions, $value, '' );
                    break;

                case 'qas_blog_show_custom_post':
                case 'qas_blog_extra_field_active':
                case 'qas_blog_show_custom_comment':
                    $optionfield[ 'style' ] = 'tall';
                    break;

                case 'qas_blog_custom_post':
                case 'qas_blog_custom_comment':
                    $optionfield[ 'style' ] = 'tall';
                    unset( $optionfield[ 'label' ] );
                    $optionfield[ 'rows' ] = 3;
                    break;

                case 'qas_blog_extra_field_display':
                    $optionfield[ 'style' ] = 'tall';
                    $optionfield[ 'label' ] = '<span id="qas_blog_extra_field_label_hidden" style="display:none;">' . $optionfield[ 'label' ] . '</span><span id="qas_blog_extra_field_label_shown">' . qa_lang_html( 'qas_options/qas_blog_extra_field_display_label' ) . '</span>';
                    break;

                case 'qas_blog_extra_field_prompt':
                case 'qas_blog_extra_field_label':
                    $optionfield[ 'style' ] = 'tall';
                    unset( $optionfield[ 'label' ] );
                    break;

                case 'qas_blog_allow_view_p_bots':
                    $optionfield[ 'note' ] = $optionfield[ 'label' ];
                    unset( $optionfield[ 'label' ] );
                    break;

                case 'qas_blog_permit_view_post_page':
                case 'qas_blog_permit_post_b':
                case 'qas_blog_permit_post_c':
                case 'qas_blog_permit_edit_p':
                case 'qas_blog_permit_retag_cat':
                case 'qas_blog_permit_edit_c':
                case 'qas_blog_permit_edit_silent':
                case 'qas_blog_permit_flag':
                case 'qas_blog_permit_close_p':
                case 'qas_blog_permit_hide_show':
                case 'qas_blog_permit_delete':
                case 'qas_blog_permit_moderate':
                case 'qas_blog_permit_delete_hidden':
                case 'qas_blog_permit_view_edit_draft':
                case 'qas_blog_permit_set_featred':
                    $dopoints = true;

                    if ( $optionname == 'qas_blog_permit_retag_cat' )
                        $optionfield[ 'label' ] = qa_lang_html( qa_using_categories() ? 'qas_options/qas_blog_permit_recat' : 'qas_options/qas_blog_permit_retag' ) . ':';
                    else
                        $optionfield[ 'label' ] = qa_lang_html( 'qas_options/' . $optionname ) . ':';

                    if ( ( $optionname == 'qas_blog_permit_view_post_page' ) || ( $optionname == 'qas_blog_permit_post_b' ) || ( $optionname == 'qas_blog_permit_post_c' ) || ( $optionname == 'permit_anon_view_ips' ) )
                        $widest = QA_PERMIT_ALL;
                    elseif ( ( $optionname == 'qas_blog_permit_close_p' ) || ( $optionname == 'qas_blog_permit_moderate' ) || ( $optionname == 'qas_blog_permit_hide_show' ) )
                        $widest = QA_PERMIT_POINTS;
                    elseif ( ( $optionname == 'qas_blog_permit_delete' ) || ( $optionname == 'qas_blog_permit_delete_hidden' ) || ( $optionname == 'qas_blog_permit_view_edit_draft' ) || ( $optionname == 'qas_blog_permit_set_featred' ) )
                        $widest = QA_PERMIT_EDITORS;
                    elseif ( ( $optionname == 'qas_blog_permit_edit_silent' ) )
                        $widest = QA_PERMIT_EXPERTS;
                    else
                        $widest = QA_PERMIT_USERS;

                    if ( $optionname == 'qas_blog_permit_view_post_page' ) {
                        $narrowest = QA_PERMIT_APPROVED;
                        $dopoints = false;
                    } elseif ( ( $optionname == 'qas_blog_permit_edit_c' ) || ( $optionname == 'qas_blog_permit_close_p' ) || ( $optionname == 'qas_blog_permit_moderate' ) || ( $optionname == 'qas_blog_permit_hide_show' ) || ( $optionname == 'permit_anon_view_ips' ) )
                        $narrowest = QA_PERMIT_MODERATORS;
                    elseif ( ( $optionname == 'qas_blog_permit_post_c' ) || ( $optionname == 'qas_blog_permit_edit_p' ) || ( $optionname == 'qas_blog_permit_retag_cat' ) || ( $optionname == 'qas_blog_permit_flag' ) )
                        $narrowest = QA_PERMIT_EDITORS;
                    elseif ( ( $optionname == 'qas_blog_permit_post_b' ) || ( $optionname == 'qas_blog_permit_delete' ) || ( $optionname == 'qas_blog_permit_delete_hidden' ) || ( $optionname == 'qas_blog_permit_view_edit_draft' ) || ( $optionname == 'qas_blog_permit_set_featred' ) || ( $optionname == 'qas_blog_permit_edit_silent' ) )
                        $narrowest = QA_PERMIT_ADMINS;
                    else
                        $narrowest = QA_PERMIT_EXPERTS;

                    $permitoptions = qa_admin_permit_options( $widest, $narrowest, ( !QA_FINAL_EXTERNAL_USERS ) && qa_opt( 'confirm_user_emails' ), $dopoints );

                    if ( count( $permitoptions ) > 1 ) {
                        qa_optionfield_make_select( $optionfield, $permitoptions, $value,
                            ( $value == QA_PERMIT_CONFIRMED ) ? QA_PERMIT_USERS : min( array_keys( $permitoptions ) ) );
                    } else {
                        $optionfield[ 'type' ] = 'static';
                        $optionfield[ 'value' ] = reset( $permitoptions );
                    }
                    break;

                case 'qas_blog_permit_post_b_points':
                case 'qas_blog_permit_post_c_points':
                case 'qas_blog_permit_flag_points':
                case 'qas_blog_permit_edit_p_points':
                case 'qas_blog_permit_retag_cat_points':
                case 'qas_blog_permit_edit_c_points':
                case 'qas_blog_permit_close_p_points':
                case 'qas_blog_permit_hide_show_points':
                case 'qas_blog_permit_delete_points':
                case 'qas_blog_permit_moderate_points':
                case 'qas_blog_permit_delete_hidden_points':
                    unset( $optionfield[ 'label' ] );
                    $optionfield[ 'type' ] = 'number';
                    $optionfield[ 'prefix' ] = qa_lang_html( 'admin/users_must_have' ) . '&nbsp;';
                    $optionfield[ 'note' ] = qa_lang_html( 'admin/points' );
                    break;

                case 'qas_blog_adcode_blog_top_content' :
                    $optionfield[ 'rows' ] = 10;
                    $optionfield[ 'note' ] = qa_lang_html( 'qas_options/qas_blog_adcode_blog_top_note' );
                    break;

                case 'qas_blog_adcode_after_post_content' :
                    $optionfield[ 'rows' ] = 10;
                    $optionfield[ 'note' ] = qa_lang_html( 'qas_options/qas_blog_adcode_after_post_note' );
                    break;

                case 'qas_blog_adcode_before_post_content' :
                    $optionfield[ 'rows' ] = 10;
                    $optionfield[ 'note' ] = qa_lang_html( 'qas_options/qas_blog_adcode_before_post_note' );
                    break;

                case 'qas_blog_adcode_w_content' :
                    $optionfield[ 'rows' ] = 10;
                    $optionfield[ 'note' ] = qa_lang_html( 'qas_options/qas_blog_adcode_w_note' );
                    break;

            }

            if ( isset( $feedrequest ) && $value )
                $optionfield[ 'note' ] = '<a href="' . qa_path_html( qa_feed_request( $feedrequest ) ) . '">' . qa_lang_html( $feedisexample ? 'admin/feed_link_example' : 'admin/feed_link' ) . '</a>';

            $qa_content[ 'form' ][ 'fields' ][ $optionname ] = $optionfield;
        }


    if ( isset( $checkboxtodisplay ) )
        qa_set_display_rules( $qa_content, $checkboxtodisplay );

    $qa_content[ 'navigation' ][ 'sub' ] = qa_admin_sub_navigation();


    return $qa_content;


    /*
        Omit PHP closing tag to help avoid accidental output
    */
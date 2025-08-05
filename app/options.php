<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }
    
    /**
     * Return whether the option is set to classify questions by categories
     */
    function qas_blog_using_categories()
    {
        return strpos( qa_opt( 'qas_blog_tags_or_categories' ), 'c' ) !== false;
    }

    /**
     * Return whether the option is set to classify questions by tags
     */
    function qas_blog_using_tags()
    {
        return strpos( qa_opt( 'qas_blog_tags_or_categories' ), 't' ) !== false;
    }

    /**
     * Return an array of defaults for the $options parameter passed to qa_post_html_fields() and its ilk for posts of
     * $basetype='Q'/'A'/'C' Set $full to true if these posts will be viewed in full, i.e. on a question page rather
     * than a question listing
     *
     * @param      $basetype
     * @param bool $full
     *
     * @return array|mixed
     */
    function qas_blog_post_html_defaults( $basetype, $full = false, $is_list = false )
    {
        if ( qa_to_override( __FUNCTION__ ) ) {
            $args = func_get_args();

            return qa_call_override( __FUNCTION__, $args );
        }

        return array(
            'tagsview'       => ( $basetype == 'B' ) && qas_blog_using_tags(),
            'categoryview'   => ( $basetype == 'B' ) && qas_blog_using_categories(),
            'contentview'    => $full,
            'voteview'       => false,
            'flagsview'      => false && qa_opt( 'qas_blog_flagging_of_posts' ) && $full, //temporarily turing off the flagging feature due to few limitations . Work on this later
            'favoritedview'  => true,
            'answersview'    => false,
            'viewsview'      => ( $basetype == 'B' ) && qa_opt( 'qas_blog_do_count_p_views' ) && ( ( $full && !$is_list ) ? qa_opt( 'qas_blog_show_view_count_p_page' ) : qa_opt( 'qas_blog_show_list_view_counts' ) ),
            'whatview'       => true,
            'whatlink'       => qa_opt( 'show_a_c_links' ),
            'whenview'       => qa_opt( 'qas_blog_show_when_created' ),
            'ipview'         => !qa_user_permit_error( 'permit_anon_view_ips' ),
            'whoview'        => true,
            'avatarsize'     => qa_opt( 'avatar_q_list_size' ),
            'pointsview'     => qa_opt( 'show_user_points' ),
            'pointstitle'    => qa_opt( 'show_user_titles' ) ? qa_get_points_to_titles() : array(),
            'updateview'     => qa_opt( 'qas_blog_show_post_updates' ),
            'blockwordspreg' => qa_get_block_words_preg(),
            'showurllinks'   => qa_opt( 'show_url_links' ),
            'linksnewwindow' => qa_opt( 'links_in_new_window' ),
            'microformats'   => $full,
            'fulldatedays'   => qa_opt( 'qas_blog_show_full_date_days' ),
        );
    }

    /**
     * Return an array of relevant permissions settings, based on other options
     *
     * @return array
     */
    function qas_blog_get_permit_options()
    {
        $permits = array( 'qas_blog_permit_view_post_page', 'qas_blog_permit_post_b' );

        if ( qa_opt( 'qas_blog_comment_on_ps' ) )
            $permits[] = 'qas_blog_permit_post_c';

        if ( qas_blog_using_tags() || qas_blog_using_categories() )
            $permits[] = 'qas_blog_permit_retag_cat';

        array_push( $permits, 'qas_blog_permit_edit_p' );

        if ( qa_opt( 'qas_blog_comment_on_ps' ) )
            $permits[] = 'qas_blog_permit_edit_c';

        $permits[] = 'qas_blog_permit_edit_silent';

        if ( qa_opt( 'qas_blog_allow_close_ps' ) )
            $permits[] = 'qas_blog_permit_close_p';

        if ( qa_opt( 'qas_blog_flagging_of_posts' ) )
            $permits[] = 'qas_blog_permit_flag';

        $permits[] = 'qas_blog_permit_moderate';

        array_push( $permits, 'qas_blog_permit_hide_show', 'qas_blog_permit_delete', 'qas_blog_permit_delete_hidden' );

        if ( qas_is_draft_enabled() )
            $permits[] = 'qas_blog_permit_view_edit_draft';

        if ( qas_is_featured_posts_enabled() )
            $permits[] = 'qas_blog_permit_set_featred';

        return $permits;
    }

    /**
     * Return an array of options for post $post to pass in the $options parameter to qa_post_html_fields() and its
     * ilk. Preferably, call qa_post_html_defaults() previously and pass its output in $defaults, to save excessive
     * recalculation for each item in a list. Set $full to true if these posts will be viewed in full, i.e. on a
     * question page rather than a question listing.
     *
     * @param      $post
     * @param null $defaults
     * @param bool $full
     *
     * @return array|mixed|null
     */
    function qas_blog_post_html_options( $post, $defaults = null, $full = false, $is_list = false )
    {
        if ( !isset( $defaults ) )
            $defaults = qas_blog_post_html_defaults( $post[ 'basetype' ], $full, $is_list );

        $defaults[ 'voteview' ] = false;
        $defaults[ 'ipview' ] = !qa_user_post_permit_error( 'permit_anon_view_ips', $post );
        $defaults[ 'categorypathprefix' ] = qas_get_blog_url_sub( qas_blog_url_plural_structure( '/' ) );

        return $defaults;
    }


    /**
     * Reset the options in $names to their defaults
     *
     * @param $names
     */
    function qas_blog_reset_options( $names )
    {
        foreach ( $names as $name )
            qa_set_option( $name, qas_blog_default_option( $name ) );
    }

    /**
     * Return the default value for option $name
     *
     * @param $name
     *
     * @return bool|mixed|string
     */
    function qas_blog_default_option( $name )
    {
        $fixed_defaults = array(
            'qas_blog_allow_close_ps'               => 1,
            'qas_blog_allow_view_p_bots'            => 1,
            'qas_blog_comment_on_ps'                => 1,
            'qas_blog_do_count_p_views'             => 1,
            'qas_blog_max_len_post_title'           => 120,
            'qas_blog_max_num_post_tags'            => 5,
            'qas_blog_min_len_c_content'            => 12,
            'qas_blog_min_len_post_content'         => 0,
            'qas_blog_min_len_post_title'           => 12,
            'qas_blog_min_num_post_tags'            => 0,
            'qas_blog_page_size_ps'                 => 20,
            'qas_blog_featured_page_size_ps'        => 20,
            'qas_blog_permit_close_p'               => QA_PERMIT_EDITORS,
            'qas_blog_permit_delete_hidden'         => QA_PERMIT_MODERATORS,
            'qas_blog_permit_delete'                => QA_PERMIT_MODERATORS,
            'qas_blog_permit_edit_c'                => QA_PERMIT_EDITORS,
            'qas_blog_permit_edit_p'                => QA_PERMIT_EDITORS,
            'qas_blog_permit_edit_silent'           => QA_PERMIT_MODERATORS,
            'qas_blog_permit_hide_show'             => QA_PERMIT_EDITORS,
            'qas_blog_permit_moderate'              => QA_PERMIT_EXPERTS,
            'qas_blog_permit_view_post_page'        => QA_PERMIT_ALL,
            'qas_blog_permit_view_edit_draft'       => QA_PERMIT_ADMINS,
            'qas_blog_permit_set_featred'           => QA_PERMIT_ADMINS,
            'qas_blog_urls_title_length'            => 50,
            'qas_blog_show_c_reply_buttons'         => 1,
            'qas_blog_show_fewer_cs_count'          => 5,
            'qas_blog_show_fewer_cs_from'           => 10,
            'qas_blog_show_full_date_days'          => 7,
            'qas_blog_show_when_created'            => 1,
            'qas_blog_tags_or_categories'           => 'tc',
            'qas_blog_allow_drafts'                 => 1,
            'qas_blog_urls_remove_accents'          => 1,
            'qas_blog_show_list_view_counts'        => 1,
            'qas_blog_show_view_count_p_page'       => 1,
            'qas_blog_auto_update_search_box'       => 1,
            'qas_blog_allow_featured_posts'         => 1,
            'qas_blog_default_home'                 => 'featured',
            'qas_blog_featured_page_layout'         => 'list',
            'qas_blog_tag_separator_comma'          => 0,
            'qas_blog_show_content_on_list'         => 1,
            'qas_blog_list_content_trunc'           => 1,
            'qas_blog_show_content_on_list_len'     => 200,
            'qas_blog_show_read_more_btn'           => 1,
            'qas_blog_show_image_on_list'           => 1,
            'qas_blog_show_user_post_count'         => 1,
            'qas_blog_show_comment_count'           => 0,
            'qas_blog_related_post_widg_count'      => 5,
            'qas_blog_recent_post_widg_count'       => 5,
            'qas_blog_recent_comments_widg_count'   => 5,
            'qas_blog_recent_comments_w_trunc_len'  => 50,
            'qas_blog_recent_comments_w_trunc'      => 1,
            'qas_blog_xml_sitemap_show_posts'       => 1,
            'qas_blog_xml_sitemap_show_tag_ps'      => 1,
            'qas_blog_xml_sitemap_show_category_ps' => 1,
            'qas_blog_xml_sitemap_show_categories'  => 1,
            'qas_blog_show_post_updates'            => 1,
            'qas_blog_adcode_blog_top'              => 0,
            'qas_blog_adcode_after_post'            => 0,
            'qas_blog_adcode_before_post'           => 0,
            'qas_blog_adcode_w'                     => 0,
            'qas_blog_allow_nested_cmnts'           => 1,
            'qas_blog_max_allow_nesting'            => 4,
        );

        if ( isset( $fixed_defaults[ $name ] ) )
            $value = $fixed_defaults[ $name ];

        else
            switch ( $name ) {

                case 'qas_blog_editor_for_ps':
                case 'qas_blog_editor_for_cs':

                    $value = '-'; // to match none by default, i.e. choose based on who is best at editing HTML
                    qa_load_editor( '', 'html', $value );
                    break;

                case 'qas_blog_permit_post_b': // convert from deprecated option if available
                    $value = qa_opt( 'ask_needs_login' ) ? QA_PERMIT_USERS : QA_PERMIT_ALL;
                    break;

                case 'qas_blog_permit_post_c': // convert from deprecated option if available
                    $value = qa_opt( 'comment_needs_login' ) ? QA_PERMIT_USERS : QA_PERMIT_ALL;
                    break;

                case 'qas_blog_permit_retag_cat': // convert from previous option that used to contain it too
                    $value = qa_opt( 'permit_edit_q' );
                    break;

                default: // call option_default method in any registered modules
                    $modules = qa_load_all_modules_with( 'option_default' );  // Loads all modules with the 'option_default' method

                    foreach ( $modules as $module ) {
                        $value = $module->option_default( $name );
                        if ( strlen( $value ) )
                            return $value;
                    }

                    $value = '';
                    break;
            }

        return $value;
    }

    /**
     * Returns an array of all options used in Blog Tool
     *
     * @return array
     */
    function qas_blog_get_all_options()
    {
        return array(
            'qas_blog_allow_close_ps',
            'qas_blog_allow_view_p_bots',
            'qas_blog_comment_on_ps',
            'qas_blog_do_count_p_views',
            'qas_blog_max_len_post_title',
            'qas_blog_max_num_post_tags',
            'qas_blog_min_len_c_content',
            'qas_blog_min_len_post_content',
            'qas_blog_min_len_post_title',
            'qas_blog_min_num_post_tags',
            'qas_blog_permit_close_p',
            'qas_blog_permit_delete_hidden',
            'qas_blog_permit_edit_c',
            'qas_blog_permit_edit_p',
            'qas_blog_permit_edit_silent',
            'qas_blog_permit_hide_show',
            'qas_blog_permit_moderate',
            'qas_blog_permit_view_post_page',
            'qas_blog_permit_set_featred',
            'qas_blog_urls_title_length',
            'qas_blog_show_c_reply_buttons',
            'qas_blog_show_fewer_cs_count',
            'qas_blog_show_fewer_cs_from',
            'qas_blog_show_full_date_days',
            'qas_blog_show_when_created',
            'qas_blog_tags_or_categories',
            'qas_blog_allow_drafts',
            'qas_blog_urls_remove_accents',
            'qas_blog_show_list_view_counts',
            'qas_blog_show_view_count_p_page',
            'qas_blog_auto_update_search_box',
            'qas_blog_tag_separator_comma',
            'qas_blog_editor_for_ps',
            'qas_blog_editor_for_cs',
            'qas_blog_permit_post_b',
            'qas_blog_permit_post_c',
            'qas_blog_permit_retag_cat',
            'qas_blog_allow_featured_posts',
            'qas_blog_default_home',
            'qas_blog_featured_page_layout',
            'qas_blog_show_content_on_list',
            'qas_blog_list_content_trunc',
            'qas_blog_show_content_on_list_len',
            'qas_blog_show_read_more_btn',
            'qas_blog_show_image_on_list',
            'qas_blog_show_comment_count',
            'qas_blog_show_user_post_count',
            'qas_blog_related_post_widg_count',
            'qas_blog_recent_post_widg_count',
            'qas_blog_recent_comments_widg_count',
            'qas_blog_recent_comments_w_trunc_len',
            'qas_blog_recent_comments_w_trunc',
            'qas_blog_xml_sitemap_show_posts',
            'qas_blog_xml_sitemap_show_tag_ps',
            'qas_blog_xml_sitemap_show_category_ps',
            'qas_blog_xml_sitemap_show_categories',
            'qas_blog_show_post_updates',
            'qas_blog_adcode_blog_top',
            'qas_blog_adcode_after_post',
            'qas_blog_adcode_before_post',
            'qas_blog_adcode_w',
            'qas_blog_page_size_ps',
            'qas_blog_featured_page_size_ps',
        );
    }

    /**
     * reset all blog options
     *
     * @return bool
     */
    function qas_blog_reset_all_blog_options()
    {
        qas_blog_reset_options( qas_blog_get_all_options() );

        return true;
    }


    /**
     * returns the default sorting page if featured page is enabled
     *
     * @return string
     */
    function qas_blog_get_default_sorting()
    {
        $sort = '';
        if ( qas_is_featured_posts_enabled() ) {
            $default_sort = qa_opt( 'qas_blog_default_home' );
            $allowed_sort = array( 'featured', 'views', 'recent', 'favorites' );
            if ( in_array( $default_sort, $allowed_sort ) )
                $sort = $default_sort;
        }

        return $sort;
    }

    /**
     * returns true if the page is a part of the blog plugin
     *
     * @param $template
     *
     * @return bool
     */
    function qas_is_blog_page( $template )
    {
        return ( strpos( $template, 'blog' ) === 0 );
    }

    /**
     * returns true if the draft feature is enabled
     *
     * @return bool
     */
    function qas_is_draft_enabled()
    {
        return (bool) qa_opt( 'qas_blog_allow_drafts' );
    }

    /**
     * returns true if the draft feature is enabled
     *
     * @return mixed
     */
    function qas_is_grid_view_enabled()
    {
        return qas_is_featured_posts_enabled() && ( qa_opt( 'qas_blog_featured_page_layout' ) === 'grid' );
    }

    /**
     * returns true if the featured posts option is enabled
     *
     * @return mixed
     */
    function qas_is_featured_posts_enabled()
    {
        return (bool) qa_opt( 'qas_blog_allow_featured_posts' );
    }

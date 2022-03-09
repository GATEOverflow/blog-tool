<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    //	Find queued posts , answers, comments

    $userid = qa_get_logged_in_userid();

    list( $queuedposts, $queuedcomments ) = qa_db_select_with_pending(
        qas_blog_db_blogs_selectspec( $userid, 'created', 0, null, null, 'B_QUEUED', true ),
        qas_blog_db_recent_c_bs_selectspec( $userid, 0, null, null, 'C_QUEUED', true )
    );

    //	Check admin privileges (do late to allow one DB query)

    if ( qa_user_maximum_permit_error( 'qas_blog_permit_moderate' ) ) {
        $qa_content = qa_content_prepare();
        $qa_content[ 'error' ] = qa_lang_html( 'users/no_permission' );

        return $qa_content;
    }

    //	Check to see if any were approved/rejected here

    $pageerror = qas_blog_admin_check_clicks();

    //	Combine sets of posts and remove those this user has no permission to moderate

    $posts = qa_any_sort_by_date( array_merge( $queuedposts, $queuedcomments ) );

    if ( qa_user_permit_error( 'qas_blog_permit_moderate' ) ) {// if user not allowed to moderate all posts
        foreach ( $posts as $index => $post ) {
            if ( qa_user_post_permit_error( 'qas_blog_permit_moderate', $post ) ) {
                unset( $posts[ $index ] );
            }
        }
    }

    //	Get information for users

    $usershtml = qa_userids_handles_html( qa_any_get_userids_handles( $posts ) );


    //	Prepare content for theme

    $qa_content = qa_content_prepare();

    $qa_content[ 'title' ] = qa_lang_html( 'admin/recent_approve_title' );
    $qa_content[ 'error' ] = isset( $pageerror ) ? $pageerror : qa_admin_page_error();

    $qa_content[ 'q_list' ] = array(
        'form' => array(
            'tags'   => 'method="post" action="' . qa_self_html() . '"',

            'hidden' => array(
                'code' => qa_get_form_security_code( 'admin/blog_click' ),
            ),
        ),

        'qs'   => array(),
    );

    if ( count( $posts ) ) {
        foreach ( $posts as $post ) {
            $postid = qa_html( isset( $post[ 'opostid' ] ) ? $post[ 'opostid' ] : $post[ 'postid' ] );
            $elementid = 'p' . $postid;

            $htmloptions = qas_blog_post_html_options( $post );
            $htmloptions[ 'voteview' ] = false;
            $htmloptions[ 'tagsview' ] = !isset( $post[ 'opostid' ] );
            $htmloptions[ 'answersview' ] = false;
            $htmloptions[ 'viewsview' ] = false;
            $htmloptions[ 'contentview' ] = true;
            $htmloptions[ 'elementid' ] = $elementid;

            $htmlfields = qas_blog_any_to_b_html_fields( $post, $userid, qa_cookie_get(), $usershtml, null, $htmloptions );

            if ( isset( $htmlfields[ 'what_url' ] ) ) // link directly to relevant content
                $htmlfields[ 'url' ] = $htmlfields[ 'what_url' ];

            $posttype = qa_strtolower( isset( $post[ 'obasetype' ] ) ? $post[ 'obasetype' ] : $post[ 'basetype' ] );
            switch ( $posttype ) {
                case 'b':
                default:
                    $approveKey = 'qas_blog/approve_post_popup';
                    $rejectKey = 'qas_blog/reject_post_popup';
                    break;
                case 'c':
                    $approveKey = 'question/approve_c_popup';
                    $rejectKey = 'question/reject_c_popup';
                    break;
            }

            $htmlfields[ 'form' ] = array(
                'style'   => 'light',

                'buttons' => array(
                    // Possible values for popup: approve_q_popup, approve_a_popup, approve_c_popup
                    'approve' => array(
                        'tags'  => 'name="admin_' . $postid . '_approve" onclick="return qas_blog_admin_click(this);"',
                        'label' => qa_lang_html( 'question/approve_button' ),
                        'popup' => qa_lang_html( $approveKey ),
                    ),

                    // Possible values for popup: reject_q_popup, reject_a_popup, reject_c_popup
                    'reject'  => array(
                        'tags'  => 'name="admin_' . $postid . '_reject" onclick="return qas_blog_admin_click(this);"',
                        'label' => qa_lang_html( 'question/reject_button' ),
                        'popup' => qa_lang_html( $rejectKey ),
                    ),
                ),
            );

            $qa_content[ 'q_list' ][ 'qs' ][] = $htmlfields;
        }

    } else
        $qa_content[ 'title' ] = qa_lang_html( 'admin/no_approve_found' );


    $qa_content[ 'navigation' ][ 'sub' ] = qa_admin_sub_navigation();
    $qa_content[ 'script_rel' ][] = 'qa-content/qa-admin.js?' . QA_VERSION;
    $qa_content[ 'script_rel' ][] = qas_blog_plugin_folder() . '/js/blog-admin.js';


    return $qa_content;


    /*
        Omit PHP closing tag to help avoid accidental output
    */
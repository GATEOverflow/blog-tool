<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    $userid = qa_get_logged_in_userid();

    //	Check admin privileges (do late to allow one DB query)

    if ( qa_user_maximum_permit_error( 'qas_blog_permit_view_edit_draft' ) ) {
        $qa_content = qa_content_prepare();
        $qa_content[ 'error' ] = qa_lang_html( 'users/no_permission' );

        return $qa_content;
    }

    $draftposts = qa_db_select_with_pending(
        qas_blog_db_blogs_selectspec( $userid, 'created', 0, null, null, 'D', true )
    );

    //	Check to see if any have been published or deleted

    $pageerror = qas_blog_admin_check_clicks();

    $posts = qa_any_sort_by_date( $draftposts );

    if ( qa_user_permit_error( 'qas_blog_permit_view_edit_draft' ) ) // not allowed to see all hidden posts
        foreach ( $posts as $index => $post )
            if ( qa_user_post_permit_error( 'qas_blog_permit_view_edit_draft', $post ) )
                unset( $posts[ $index ] );


    //	Get information for users

    $usershtml = qa_userids_handles_html( qa_any_get_userids_handles( $posts ) );

    $draftpostid = array();
    foreach ( $posts as $key => $post )
        $draftpostid[ $key ] = isset( $post[ 'opostid' ] ) ? $post[ 'opostid' ] : $post[ 'postid' ];

//	Prepare content for theme

    $qa_content = qa_content_prepare();

    $qa_content[ 'title' ] = qa_lang_html( 'qas_admin/recent_drafts_title' );
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
        foreach ( $posts as $key => $post ) {
            $elementid = 'p' . $draftpostid[ $key ];

            $htmloptions = qas_blog_post_html_options( $post );
            $htmloptions[ 'voteview' ] = false;
            $htmloptions[ 'tagsview' ] = !isset( $post[ 'opostid' ] );
            $htmloptions[ 'answersview' ] = false;
            $htmloptions[ 'viewsview' ] = false;
            $htmloptions[ 'updateview' ] = false;
            $htmloptions[ 'contentview' ] = true;
            $htmloptions[ 'flagsview' ] = true;
            $htmloptions[ 'elementid' ] = $elementid;

            $htmlfields = qas_blog_any_to_b_html_fields( $post, $userid, qa_cookie_get(), $usershtml, null, $htmloptions );

            if ( isset( $htmlfields[ 'what_url' ] ) ) // link directly to relevant content
                $htmlfields[ 'url' ] = $htmlfields[ 'what_url' ];

            $htmlfields[ 'what_2' ] = qa_lang_html( 'main/hidden' );

            if ( @$htmloptions[ 'whenview' ] ) {
                $updated = @$post[ isset( $post[ 'opostid' ] ) ? 'oupdated' : 'updated' ];
                if ( isset( $updated ) )
                    $htmlfields[ 'when_2' ] = qa_when_to_html( $updated, @$htmloptions[ 'fulldatedays' ] );
            }

            $buttons = array();

            $posttype = qa_strtolower( isset( $post[ 'obasetype' ] ) ? $post[ 'obasetype' ] : $post[ 'basetype' ] );

            if ( !qa_user_post_permit_error( 'qas_blog_permit_view_edit_draft', $post ) )
                $buttons[ 'publish' ] = array(
                    'tags'  => 'name="admin_' . qa_html( $draftpostid[ $key ] ) . '_publish" onclick="return qas_blog_admin_click(this);"',
                    'label' => qa_lang_html( 'qas_blog/publish_button' ),
                    'popup' => qa_lang_html( 'qas_blog/publish_post_popup' ),
                );

            if ( ( !qa_user_post_permit_error( 'qas_blog_permit_view_edit_draft', $post ) ) )
                $buttons[ 'delete' ] = array(
                    'tags'  => 'name="admin_' . qa_html( $draftpostid[ $key ] ) . '_delete" onclick="return qas_blog_admin_click(this);"',
                    'label' => qa_lang_html( 'question/delete_button' ),
                    'popup' => qa_lang_html( 'qas_admin/delete_draft_popup' ),
                );

            if ( count( $buttons ) )
                $htmlfields[ 'form' ] = array(
                    'style'   => 'light',
                    'buttons' => $buttons,
                );

            $qa_content[ 'q_list' ][ 'qs' ][] = $htmlfields;
        }

    } else {
        $qa_content[ 'title' ] = qa_lang_html( 'qas_admin/no_drafts_found' );
    }


    $qa_content[ 'navigation' ][ 'sub' ] = qa_admin_sub_navigation();
    $qa_content[ 'script_rel' ][] = 'qa-content/qa-admin.js?' . QA_VERSION;
    $qa_content[ 'script_rel' ][] = qas_blog_plugin_folder() . '/js/blog-admin.js';


    return $qa_content;


    /*
        Omit PHP closing tag to help avoid accidental output
    */
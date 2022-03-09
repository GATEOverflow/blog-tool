<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    //	$handle, $userhtml are already set by qa-page-user.php - also $userid if using external user integration

    $start = qa_get_start();

    //	Find the questions for this user

    $loginuserid = qa_get_logged_in_userid();
    $identifier = QA_FINAL_EXTERNAL_USERS ? $userid : $handle;

    list( $useraccount, $userpoints, $posts ) = qa_db_select_with_pending(
        QA_FINAL_EXTERNAL_USERS ? null : qa_db_user_account_selectspec( $handle, false ),
        qa_db_user_points_selectspec( $identifier ),
        qas_blog_db_user_recent_posts_selectspec( $loginuserid, $identifier, qa_opt_if_loaded( 'qas_blog_page_size_ps' ), $start )
    );

    if ( ( !QA_FINAL_EXTERNAL_USERS ) && !is_array( $useraccount ) ) // check the user exists
        return include QA_INCLUDE_DIR . 'qa-page-not-found.php';


    //	Get information on user questions

    $pagesize = qa_opt( 'qas_blog_page_size_ps' );
    $count = (int) qas_blog_db_user_recent_posts_count( $identifier );
    $posts = array_slice( $posts, 0, $pagesize );
    $usershtml = qa_userids_handles_html( $posts, false );


//	Prepare content for theme

    $qa_content = qa_content_prepare( true );

    if ( count( $posts ) )
        $qa_content[ 'title' ] = qa_lang_html_sub( 'qas_blog/posts_by_x', $userhtml );
    else
        $qa_content[ 'title' ] = qa_lang_html_sub( 'qas_blog/no_posts_by_x', $userhtml );


//	Recent questions by this user

    $qa_content[ 'q_list' ][ 'qs' ] = array();

    $htmldefaults = qas_blog_post_html_defaults( 'B' );
    $htmldefaults[ 'whoview' ] = false;
    $htmldefaults[ 'avatarsize' ] = 0;

    foreach ( $posts as $post ) {
        $qa_content[ 'q_list' ][ 'qs' ][] = qas_blog_post_html_fields( $post, $loginuserid, qa_cookie_get(),
            $usershtml, null, qas_blog_post_html_options( $post, $htmldefaults ) );
    }

    $qa_content[ 'page_links' ] = qa_html_page_links( qa_request(), $start, $pagesize, $count, qa_opt( 'pages_prev_next' ) );


//	Sub menu for navigation in user pages

    $ismyuser = isset( $loginuserid ) && $loginuserid == ( QA_FINAL_EXTERNAL_USERS ? $userid : $useraccount[ 'userid' ] );

    $qa_content[ 'navigation' ][ 'sub' ] = qa_user_sub_navigation( $handle, 'user_blogs', $ismyuser );


    return $qa_content;


    /*
        Omit PHP closing tag to help avoid accidental output
    */
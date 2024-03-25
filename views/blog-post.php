<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    $postid = qa_request_part( 1 );
    $userid = qa_get_logged_in_userid();
    $cookieid = qa_cookie_get();


//	Get information about this question

    list( $post, $childposts, $closepost, $extravalue, $categories, $favorite ) = qa_db_select_with_pending(
        qas_blog_db_full_post_selectspec( $userid, $postid ),
        qas_blog_db_full_child_posts_selectspec( $userid, $postid ),
        qas_blog_db_post_close_post_selectspec( $postid ),
        qas_blog_db_post_meta_selectspec( $postid, 'qa_q_extra' ),
        qas_blog_db_category_nav_selectspec( $postid, true, true, true ),
        isset( $userid ) ? qa_db_is_favorite_selectspec( $userid, QAS_BLOG_ENTITY_POST, $postid ) : null
    );

    if ( !( $post[ 'basetype' ] == 'B' || $post[ 'basetype' ] == 'D' ) ) { // don't allow direct viewing of other types of post
        $post = null;
    }

    if ( isset( $post ) ) {

        $post_request = qas_blog_request($postid, $post['title']);

        if (trim($post_request, '/') !== trim(qa_request(), '/')) {
            // redirect if the current URL is incorrect
            qa_redirect($post_request);
        }

        $post[ 'extra' ] = $extravalue;

        $commentsfollows = qas_blog_page_b_load_c_follows( $post, $childposts );

        $post = $post + qas_blog_page_b_post_rules( $post, null, null, $childposts ); // array union

        foreach ( $commentsfollows as $key => $commentfollow ) {
            $commentsfollows[ $key ] = $commentfollow + qas_blog_page_b_post_rules( $commentfollow, $post, $commentsfollows, null );
        }
    }

    if ( ( $post[ 'basetype' ] == 'D' && !$post[ 'publishable' ] ) ) {
        $post = null;
    }

//	Deal with question not found or not viewable, otherwise report the view event

    if ( !isset( $post ) )
        return include QA_INCLUDE_DIR . 'qa-page-not-found.php';


    if ( !$post[ 'viewable' ] ) {
        $qa_content = qa_content_prepare();

        if ( $post[ 'queued' ] )
            $qa_content[ 'error' ] = qa_lang_html( 'qas_blog/post_waiting_approval' );
        elseif ( $post[ 'flagcount' ] && !isset( $post[ 'lastuserid' ] ) )
            $qa_content[ 'error' ] = qa_lang_html( 'question/q_hidden_flagged' );
        elseif ( $post[ 'authorlast' ] )
            $qa_content[ 'error' ] = qa_lang_html( 'question/q_hidden_author' );
        else
            $qa_content[ 'error' ] = qa_lang_html( 'question/q_hidden_other' );

        $qa_content[ 'suggest_next' ] = qa_html_suggest_qs_tags( qas_blog_using_tags() );

        return $qa_content;
    }

    $permiterror = qa_user_post_permit_error( 'qas_blog_permit_view_post_page', $post, null, false );

    if ( $permiterror && ( qa_is_human_probably() || !qa_opt( 'qas_blog_allow_view_p_bots' ) ) ) {
        $qa_content = qa_content_prepare();
        $topage = qa_q_request( $postid, $post[ 'title' ] );

        switch ( $permiterror ) {
            case 'login':
                $qa_content[ 'error' ] = qa_insert_login_links( qa_lang_html( 'main/view_q_must_login' ), $topage );
                break;

            case 'confirm':
                $qa_content[ 'error' ] = qa_insert_login_links( qa_lang_html( 'main/view_q_must_confirm' ), $topage );
                break;

            case 'approve':
                $qa_content[ 'error' ] = qa_lang_html( 'main/view_q_must_be_approved' );
                break;

            default:
                $qa_content[ 'error' ] = qa_lang_html( 'users/no_permission' );
                break;
        }

        return $qa_content;
    }


//	Determine if captchas will be required

    $captchareason = qa_user_captcha_reason( qa_user_level_for_post( $post ) );
    $usecaptcha = ( $captchareason != false );


//	If we're responding to an HTTP POST, include file that handles all posting/editing/etc... logic
//	This is in a separate file because it's a *lot* of logic, and will slow down ordinary page views

    $pagestart = qa_get_start();
    $pagestate = qa_get_state();
    $showid = qa_get( 'show' );
    $pageerror = null;
    $formtype = null;
    $formpostid = null;
    $jumptoanchor = null;
    $commentsall = null;

    if ( substr( $pagestate, 0, 13 ) == 'showcomments-' ) {
        $commentsall = substr( $pagestate, 13 );
        $pagestate = null;

    } elseif ( isset( $showid ) ) {
        foreach ( $commentsfollows as $comment )
            if ( $comment[ 'postid' ] == $showid ) {
                $commentsall = $comment[ 'parentid' ];
                break;
            }
    }

    if ( qa_is_http_post() || strlen( $pagestate ) )
        require QAS_BLOG_DIR . '/app/blog-actions.php';

    $formrequested = isset( $formtype );

//	Get information on the users referenced

    $usershtml = qa_userids_handles_html( array_merge( array( $post ), $commentsfollows ), true );


//	Prepare content for theme

    $qa_content = qa_content_prepare( true, array_keys( qa_category_path( $categories, $post[ 'categoryid' ] ) ) );
$qa_content['html_tags'] .= ' itemscope itemtype="https://schema.org/Blog"';
    if ( isset( $userid ) && !$formrequested )
        $qa_content[ 'favorite' ] = qas_blog_favorite_form( QAS_BLOG_ENTITY_POST, $postid, $favorite,
            qa_lang( $favorite ? 'qas_blog/remove_blog_favorites' : 'qas_blog/add_blog_favorites' ) );

    $qa_content[ 'script_rel' ][] = 'qa-content/qa-question.js?' . QA_VERSION;

    if ( isset( $pageerror ) )
        $qa_content[ 'error' ] = $pageerror; // might also show voting error set in qa-index.php

    elseif ( $post[ 'queued' ] )
        $qa_content[ 'error' ] = $post[ 'isbyuser' ] ? qa_lang_html( 'qas_blog/post_your_waiting_approval' ) : qa_lang_html( 'qas_blog/post_waiting_your_approval' );

    if ( $post[ 'hidden' ] )
        $qa_content[ 'hidden' ] = true;

    qa_sort_by( $commentsfollows, 'created' );


    //	Prepare content for the question...

    if ( $formtype == 'q_edit' ) { // ...in edit mode
        $qa_content[ 'title' ] = qa_lang_html( $post[ 'editable' ] ? 'qas_blog/edit_blog_title' :
            ( qas_blog_using_categories() ? 'qas_blog/recat_blog_title' : 'qas_blog/retag_blog_title' ) );
        $qa_content[ 'form_q_edit' ] = qas_blog_page_edit_post_form( $qa_content, $post, @$qin, @$qerrors, $completetags, $categories );
        $qa_content[ 'q_view' ][ 'raw' ] = $post;

    } else { // ...in view mode
        $qa_content[ 'q_view' ] = qas_blog_page_blog_view( $post, $closepost, $usershtml, $formrequested );

        $qa_content[ 'title' ] = $qa_content[ 'q_view' ][ 'title' ];

        $qa_content[ 'description' ] = qa_html( qa_shorten_string_line( qa_viewer_text( $post[ 'content' ], $post[ 'format' ] ), 150 ) );

        $categorykeyword = @$categories[ $post[ 'categoryid' ] ][ 'title' ];

        $qa_content[ 'keywords' ] = qa_html( implode( ',', array_merge(
            ( qas_blog_using_categories() && strlen( $categorykeyword ) ) ? array( $categorykeyword ) : array(),
            qa_tagstring_to_tags( $post[ 'tags' ] )
        ) ) ); // as far as I know, META keywords have zero effect on search rankings or listings, but many people have asked for this
    }


//	Prepare content for comments on the question, plus add or edit comment forms

    if ( $formtype == 'q_close' ) {
        $qa_content[ 'q_view' ][ 'c_form' ] = qas_blog_page_b_close_post_form( $qa_content, $post, 'close', @$closein, @$closeerrors );
        $jumptoanchor = 'close';

    } elseif ( ( ( $formtype == 'c_add' ) && ( $formpostid == $postid ) ) || ( $post[ 'commentbutton' ] && !$formrequested ) ) { // ...to be added
        $qa_content[ 'q_view' ][ 'c_form' ] = qas_blog_page_b_add_c_form( $qa_content, $post, $post, 'c' . $postid,
		$captchareason, @$cnewin[ $postid ], @$cnewerrors[ $postid ], $formtype == 'c_add' );
//	print_r($qa_content['q_view']['c_form']);

        if ( ( $formtype == 'c_add' ) && ( $formpostid == $postid ) ) {
            $jumptoanchor = 'c' . $postid;
            $commentsall = $postid;
        }

    } elseif ( ( $formtype == 'c_edit' ) && ( @$commentsfollows[ $formpostid ][ 'parentid' ] == $postid ) ) { // ...being edited
        $qa_content[ 'q_view' ][ 'c_form' ] = qas_blog_page_post_edit_c_form( $qa_content, 'c' . $formpostid, $commentsfollows[ $formpostid ],
            @$ceditin[ $formpostid ], @$cediterrors[ $formpostid ] );

        $jumptoanchor = 'c' . $formpostid;
        $commentsall = $postid;
    }

    $qa_content[ 'q_view' ][ 'c_list' ] = qas_blog_page_b_comment_follow_list( $post, $post, $commentsfollows,
        //true, $usershtml, $formrequested, $formpostid ); // ...for viewing //arjun fix for partial hide
        $commentsall == $postid, $usershtml, $formrequested, $formpostid ); // ...for viewing

    if ( qa_opt( 'qas_blog_show_comment_count' ) ) {
        $countfortitle = @$qa_content[ 'q_view' ][ 'c_list' ][ 'comment_count' ] ;
        $qa_content[ 'q_view' ][ 'c_list' ][ 'title_tags' ] = 'id="c_list_title"';

        if ( $countfortitle == 1 )
            $qa_content[ 'q_view' ][ 'c_list' ][ 'title' ] = qa_lang_html( 'qas_blog/1_comment_title' );
        elseif ( $countfortitle > 0 )
            $qa_content[ 'q_view' ][ 'c_list' ][ 'title' ] = qa_lang_html_sub( 'qas_blog/x_comments_title', $countfortitle );
        else
            $qa_content[ 'q_view' ][ 'c_list' ][ 'title_tags' ] .= ' style="display:none;" ';
    }

    $pagesize = qa_opt( 'qas_blog_page_size_ps' );
    $countfortitle = 0;



    // set the canonical url based on possible pagination

    $qa_content[ 'canonical' ] = qa_path_html( qas_blog_request( $post[ 'postid' ], $post[ 'title' ] ),
        ( $pagestart > 0 ) ? array( 'start' => $pagestart ) : null, qa_opt( 'site_url' ) );


//	Some generally useful stuff

    if ( qas_blog_using_categories() && count( $categories ) )
        $qa_content[ 'navigation' ][ 'cat' ] = qa_category_navigation( $categories, $post[ 'categoryid' ], qas_get_blog_url_sub( qas_blog_url_plural_structure( '/' ) ) );

    if ( isset( $jumptoanchor ) )
        $qa_content[ 'script_onloads' ][] = array(
            'qa_scroll_page_to($("#"+' . qa_js( $jumptoanchor ) . ').offset().top);',
        );


//	Determine whether this request should be counted for page view statistics

    if (
        qa_opt( 'qas_blog_do_count_p_views' ) &&
        ( !$formrequested ) &&
        ( !qa_is_http_post() ) &&
        qa_is_human_probably() &&
        ( ( !$post[ 'views' ] ) || ( // if it has more than zero views
                ( ( $post[ 'lastviewip' ] != qa_remote_ip_address() ) || ( !isset( $post[ 'lastviewip' ] ) ) ) && // then it must be different IP from last view
                ( ( $post[ 'createip' ] != qa_remote_ip_address() ) || ( !isset( $post[ 'createip' ] ) ) ) && // and different IP from the creator
                ( ( $post[ 'userid' ] != $userid ) || ( !isset( $post[ 'userid' ] ) ) ) && // and different user from the creator
                ( ( $post[ 'cookieid' ] != $cookieid ) || ( !isset( $post[ 'cookieid' ] ) ) ) // and different cookieid from the creator
            ) )
    ) {
        $qa_content[ 'blog_inc_views_postid' ] = $postid;
        //update the view here
        qas_blog_update_views( $qa_content );
    }

    return $qa_content;


    /*
        Omit PHP closing tag to help avoid accidental output
    */

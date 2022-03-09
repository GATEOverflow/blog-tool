<?php
    
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    $tag = qa_request_part( 2 ); // picked up from qa-page.php
    $start = qa_get_start();
    $userid = qa_get_logged_in_userid();


//	Find the questions with this tag

    if ( !strlen( $tag ) )
        qa_redirect( qas_get_blog_url_sub( '^/tags' ) );

    list( $posts, $tagword ) = qa_db_select_with_pending(
        qas_blog_db_tag_recent_bs_selectspec( $userid, $tag, $start, false, qa_opt_if_loaded( 'page_size_tag_qs' ) ),
        qas_blog_db_tag_word_selectspec( $tag )
    );

    $pagesize = qa_opt( 'page_size_tag_qs' );
    $posts = array_slice( $posts, 0, $pagesize );
    $usershtml = qa_userids_handles_html( $posts );


//	Prepare content for theme

    $qa_content = qa_content_prepare( true );

    $qa_content[ 'title' ] = qa_lang_html_sub( 'qas_blog/posts_tagged_x', qa_html( $tag ) );

    if ( isset( $userid ) && isset( $tagword ) ) {
        $favoritemap = qas_blog_get_favorite_non_bs_map();

        $favorite = @$favoritemap[ 'tag' ][ qa_strtolower( $tagword[ 'word' ] ) ];


        $qa_content[ 'favorite' ] = qas_blog_favorite_form( QAS_BLOG_ENTITY_TAG, $tagword[ 'wordid' ], $favorite,
            qa_lang_sub( $favorite ? 'main/remove_x_favorites' : 'main/add_tag_x_favorites', $tagword[ 'word' ] ) );

    }

    if ( !count( $posts ) )
        $qa_content[ 'q_list' ][ 'title' ] = qa_lang_html( 'qas_blog/no_posts_found' );

    $qa_content[ 'q_list' ][ 'form' ] = array(
        'tags'   => 'method="post" action="' . qa_self_html() . '"',

        'hidden' => array(
            'code' => qa_get_form_security_code( 'blog_vote' ),
        ),
    );

    $qa_content[ 'q_list' ][ 'qs' ] = array();
    foreach ( $posts as $postid => $post )
        $qa_content[ 'q_list' ][ 'qs' ][] = qas_blog_post_html_fields( $post, $userid, qa_cookie_get(), $usershtml, null, qas_blog_post_html_options( $post ) );

    $qa_content[ 'page_links' ] = qa_html_page_links( qa_request(), $start, $pagesize, $tagword[ 'tagcount' ], qa_opt( 'pages_prev_next' ) );

    if ( empty( $qa_content[ 'page_links' ] ) )
        $qa_content[ 'suggest_next' ] = qa_html_suggest_qs_tags( true );

    return $qa_content;


    /*
        Omit PHP closing tag to help avoid accidental output
    */
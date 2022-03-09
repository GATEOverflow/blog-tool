<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    //	Load relevant information about this post and check it exists

    $postid = qa_post_text( 'c_postid' );
    $parentid = qa_post_text( 'c_parentid' );
    $userid = qa_get_logged_in_userid();

    list( $post, $parent, $children ) = qa_db_select_with_pending(
        qas_blog_db_full_post_selectspec( $userid, $postid ),
        qas_blog_db_full_post_selectspec( $userid, $parentid ),
        qas_blog_db_full_child_posts_selectspec( $userid, $parentid )
    );

    if ( isset( $parent ) ) {
        $parent = $parent + qas_blog_page_b_post_rules( $parent, null, null, $children );
        // in theory we should retrieve the parent's parent and siblings for the above, but they're not going to be relevant

        foreach ( $children as $key => $child )
            $children[ $key ] = $child + qas_blog_page_b_post_rules( $child, $parent, $children, null );

        $usershtml = qa_userids_handles_html( $children, true );

        qa_sort_by( $children, 'created' );

        $c_list = qas_blog_page_b_comment_follow_list( $post, $parent, $children, true, $usershtml, false, null );

        $themeclass = qa_load_theme_class( qa_get_site_theme(), 'blog-ajax-comments', null, null );
        $themeclass->initialize();
        
        echo "QA_AJAX_RESPONSE\n1\n";


        //	Send back the HTML

        $themeclass->c_list_items( $c_list[ 'cs' ] );

        return;
    }


    echo "QA_AJAX_RESPONSE\n0\n";


    /*
        Omit PHP closing tag to help avoid accidental output
    */
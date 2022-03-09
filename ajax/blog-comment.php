<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

//	Load relevant information about this post and the comment parent

    $postid = qa_post_text( 'c_postid' );
    $parentid = qa_post_text( 'c_parentid' );
    $userid = qa_get_logged_in_userid();

    list( $post, $parent, $children ) = qa_db_select_with_pending(
        qas_blog_db_full_post_selectspec( $userid, $postid ),
        qas_blog_db_full_post_selectspec( $userid, $parentid ),
        qas_blog_db_full_child_posts_selectspec( $userid, $parentid )
    );


//	Check if the post and parent exist, and whether the user has permission to do this

    if (
        ( @$post[ 'basetype' ] == 'B' ) &&
        ( ( @$parent[ 'basetype' ] == 'B' ) ) &&
        !qa_user_post_permit_error( 'qas_blog_permit_post_c', $parent, QA_LIMIT_COMMENTS )
    ) {

        //	Try to create the new comment

        $post = $post + qas_blog_page_b_post_rules( $post, null, null, $children ); // array union
        if ( $post[ 'commentbutton' ] && !$post[ 'closed' ] ) {
            $usecaptcha = qa_user_use_captcha( qa_user_level_for_post( $post ) );
            $commentid = qas_blog_page_b_add_c_submit( $post, $parent, $children, $usecaptcha, $in, $errors );
        }

        //	If successful, page content will be updated via Ajax

        if ( isset( $commentid ) ) {
            $children = qa_db_select_with_pending( qas_blog_db_full_child_posts_selectspec( $userid, $parentid ) );

            $parent = $parent + qas_blog_page_b_post_rules( $parent, ( $postid == $parentid ) ? null : $post, null, $children );
            // in theory we should retrieve the parent's siblings for the above, but they're not going to be relevant

            foreach ( $children as $key => $child )
                $children[ $key ] = $child + qas_blog_page_b_post_rules( $child, $parent, $children, null );

            $usershtml = qa_userids_handles_html( $children, true );

            qa_sort_by( $children, 'created' );

            $c_list = qas_blog_page_b_comment_follow_list( $post, $parent, $children, true, $usershtml, false, null );

            $themeclass = qa_load_theme_class( qa_get_site_theme(), 'blog-ajax-comments', null, null );
            $themeclass->initialize();
            
            echo "QA_AJAX_RESPONSE\n1\n";


            //	Send back the ID of the new comment

            echo qa_anchor( 'C', $commentid ) . "\n";


            //	Send back the HTML

            $themeclass->c_list_items( $c_list[ 'cs' ] );

            return;
        }
    }

    echo "QA_AJAX_RESPONSE\n0\n"; // fall back to non-Ajax submission if there were any problems


    /*
        Omit PHP closing tag to help avoid accidental output
    */
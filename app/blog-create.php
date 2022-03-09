<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    /**
     * Add a post (application level) - create record, update appropriate counts, index it, send notifications.
     * If question is follow-on from an answer, $followanswer should contain answer database record, otherwise null.
     * See qa-app-posts.php for a higher-level function which is easier to use.
     *
     * @param      $userid
     * @param      $handle
     * @param      $cookieid
     * @param      $title
     * @param      $content
     * @param      $format
     * @param      $text
     * @param      $tagstring
     * @param      $notify
     * @param      $email
     * @param null $categoryid
     * @param null $extravalue
     * @param bool $queued
     * @param null $name
     *
     * @return mixed
     */
    function qas_blog_post_create( $userid, $handle, $cookieid, $title, $content, $format, $text, $tagstring, $notify, $email,
                                   $categoryid = null, $extravalue = null, $queued = false, $name = null, $is_draft = false )
    {
        $post_type = ( $is_draft ? 'D' : ( $queued ? 'B_QUEUED' : 'B' ) );

        $postid = qas_blog_db_post_create( $post_type, null, null ,$userid, isset( $userid ) ? null : $cookieid,
            qa_remote_ip_address(), $title, $content, $format, $tagstring, qa_combine_notify_email( $userid, $notify, $email ),
            $categoryid, isset( $userid ) ? null : $name );

        if ( isset( $extravalue ) ) {
            qa_db_blogmeta_set( $postid, 'qa_q_extra', $extravalue );
        }

        qas_blog_db_posts_calc_category_path( $postid );

        if ( $queued ) {
            qas_blog_db_queuedcount_update();
        } else {
            if ( $post_type == 'B' ) {
                qas_blog_post_index( $postid, 'B', $postid, null, $title, $content, $format, $text, $tagstring, $categoryid );
                qas_blog_update_counts_for_post( $postid );
            }
        }

        if ( $post_type !== 'D' ) {
            qa_report_event( $queued ? 'qas_blog_b_queue' : 'qas_blog_b_post', $userid, $handle, $cookieid, array(
                'postid'     => $postid,
                'parentid'   => null,
                'parent'     => null,
                'title'      => $title,
                'content'    => $content,
                'format'     => $format,
                'text'       => $text,
                'tags'       => $tagstring,
                'categoryid' => $categoryid,
                'extra'      => $extravalue,
                'name'       => $name,
                'notify'     => $notify,
                'email'      => $email,
            ) );
        }

        return $postid;
    }

    /**
     * Perform various common cached count updating operations to reflect changes in the question whose id is $postid
     *
     * @param $postid
     */
    function qas_blog_update_counts_for_post( $postid )
    {
        if ( isset( $postid ) ) // post might no longer exist
            qas_blog_db_category_path_post_count_update( qas_blog_db_post_get_category_path( $postid ) );

        qas_blog_db_post_count_update();
    }

    /**
     * Add post $postid (which comes under $postid) of $type (Q/A/C) to the database index, with $title, $text,
     * $tagstring and $categoryid. Calls through to all installed search modules.
     *
     * @param $postid
     * @param $type
     * @param $blog_postid
     * @param $parentid
     * @param $title
     * @param $content
     * @param $format
     * @param $text
     * @param $tagstring
     * @param $categoryid
     */
    function qas_blog_post_index( $postid, $type, $blog_postid, $parentid, $title, $content, $format, $text, $tagstring, $categoryid )
    {
        global $qa_post_indexing_suspended;

        if ( $qa_post_indexing_suspended > 0 )
            return;

        //	Send through to any search modules for indexing

        $searches = qa_load_modules_with( 'search', 'index_blog_post' );
        foreach ( $searches as $search_module => $search ) {
            $search->index_blog_post( $postid, $type, $blog_postid, $parentid, $title, $content, $format, $text, $tagstring, $categoryid );
        }

    }

    /**
     * Add a comment (application level) - create record, update appropriate counts, index it, send notifications.
     * $post should contain database record for the question this is part of (as direct or comment on Q's answer).
     * $commentsfollows should contain database records for all previous comments on the same question or answer,
     * but it can also contain other records that are ignored.
     * See qa-app-posts.php for a higher-level function which is easier to use.
     *
     * @param      $userid
     * @param      $handle
     * @param      $cookieid
     * @param      $content
     * @param      $format
     * @param      $text
     * @param      $notify
     * @param      $email
     * @param      $post
     * @param      $parent
     * @param      $commentsfollows
     * @param bool $queued
     * @param null $name
     *
     * @return mixed
     */
    function qas_blog_comment_create( $userid, $handle, $cookieid, $content, $format, $text, $notify, $email, $post, $parent, $reply_to, $commentsfollows, $queued = false, $name = null )
    {
        if ( !isset( $parent ) )
            $parent = $post; // for backwards compatibility with old answer parameter

        $postid = qas_blog_db_post_create( $queued ? 'C_QUEUED' : 'C', $parent[ 'postid' ], $reply_to ,$userid, isset( $userid ) ? null : $cookieid,
            qa_remote_ip_address(), null, $content, $format, null, qa_combine_notify_email( $userid, $notify, $email ),
            $post[ 'categoryid' ], isset( $userid ) ? null : $name );

        qas_blog_db_posts_calc_category_path( $postid );

        if ( $queued ) {
            qas_blog_db_queuedcount_update();

        } else {
            if ( ( $post[ 'type' ] == 'B' ) && ( ( $parent[ 'type' ] == 'B' ) ) ) // only index if antecedents fully visible
                qas_blog_post_index( $postid, 'C', $post[ 'postid' ], $parent[ 'postid' ], null, $content, $format, $text, null, $post[ 'categoryid' ] );

        }

        $thread = array();

        foreach ( $commentsfollows as $comment )
            if ( ( $comment[ 'type' ] == 'C' ) && ( $comment[ 'parentid' ] == $parent[ 'postid' ] ) ) // find just those for this parent, fully visible
                $thread[] = $comment;

        qa_report_event( $queued ? 'qas_blog_c_queue' : 'qas_blog_c_post', $userid, $handle, $cookieid, array(
            'postid'     => $postid,
            'parentid'   => $parent[ 'postid' ],
            'parenttype' => $parent[ 'basetype' ],
            'parent'     => $parent,
            'questionid' => $post[ 'postid' ],
            'question'   => $post,
            'thread'     => $thread,
            'content'    => $content,
            'format'     => $format,
            'text'       => $text,
            'categoryid' => $post[ 'categoryid' ],
            'name'       => $name,
            'notify'     => $notify,
            'email'      => $email,
        ) );

        return $postid;
    }

    /**
     *  Processes a POSTed form to add a comment, returning the postid if successful, otherwise null. Pass in the
     *  antecedent
     *  $post and the comment's $parent post. Set $usecaptcha to whether a captcha is required. Pass an array which
     *  includes the other comments with the same parent in $commentsfollows (it can contain other posts which are
     *  ignored). The form fields submitted will be passed out as an array in $in, as well as any $errors on those
     *  fields.
     *
     * @param $post
     * @param $parent
     * @param $commentsfollows
     * @param $usecaptcha
     * @param $in
     * @param $errors
     *
     * @return null
     */
    function qas_blog_page_b_add_c_submit( $post, $parent, $commentsfollows, $usecaptcha, &$in, &$errors )
    {
        $parentid = $parent[ 'postid' ];

        $prefix = 'c' . $parentid . '_';

        $in = array(
            'name'   => qa_post_text( $prefix . 'name' ),
            'notify' => qa_post_text( $prefix . 'notify' ) !== null,
            'email'  => qa_post_text( $prefix . 'email' ),
            'queued' => qa_user_moderation_reason( qa_user_level_for_post( $parent ) ) !== false,
        );

        qa_get_post_content( $prefix . 'editor', $prefix . 'content', $in[ 'editor' ], $in[ 'content' ], $in[ 'format' ], $in[ 'text' ] );

        $errors = array();

        if ( !qa_check_form_security_code( 'blog_comment-' . $parent[ 'postid' ], qa_post_text( $prefix . 'code' ) ) )
            $errors[ 'content' ] = qa_lang_html( 'misc/form_security_again' );

        else {
            $filtermodules = qa_load_modules_with( 'filter', 'filter_blog_comment' );
            foreach ( $filtermodules as $filtermodule ) {
                $oldin = $in;
                $filtermodule->filter_blog_comment( $in, $errors, $post, $parent, null );
                qa_update_post_text( $in, $oldin );
            }

            if ( $usecaptcha )
                qa_captcha_validate_post( $errors );

            if ( empty( $errors ) ) {
                $testwords = implode( ' ', qa_string_to_words( $in[ 'content' ] ) );

                foreach ( $commentsfollows as $comment )
                    if ( ( $comment[ 'basetype' ] == 'C' ) && ( $comment[ 'parentid' ] == $parentid ) && !$comment[ 'hidden' ] )
                        if ( implode( ' ', qa_string_to_words( $comment[ 'content' ] ) ) == $testwords )
                            $errors[ 'content' ] = qa_lang_html( 'question/duplicate_content' );
            }

            $reply_to = qa_post_text('reply_to');

            if(!empty($reply_to)){
                $commentids = array_pluck('postid' , $commentsfollows);
                if(!in_array($reply_to, $commentids)){
                    $reply_to = null ;
                }
            } else {
               $reply_to = null ;
            }

            if ( empty( $errors ) ) {
                $userid = qa_get_logged_in_userid();
                $handle = qa_get_logged_in_handle();
                $cookieid = isset( $userid ) ? qa_cookie_get() : qa_cookie_get_create(); // create a new cookie if necessary

                $commentid = qas_blog_comment_create( $userid, $handle, $cookieid, $in[ 'content' ], $in[ 'format' ], $in[ 'text' ], $in[ 'notify' ], $in[ 'email' ],
                    $post, $parent, $reply_to ,$commentsfollows, $in[ 'queued' ], $in[ 'name' ] );

                return $commentid;
            }
        }

        return null;
    }

    /*
        Omit PHP closing tag to help avoid accidental output
    */

<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    /**
     * Change the fields of a question (application level) to $title, $content, $format, $tagstring, $notify,
     * $extravalue and $name, then reindex based on $text. For backwards compatibility if $name is null then the name
     * will not be changed. Pass the question's database record before changes in $oldquestion and details of the user
     * doing this in
     * $userid, $handle and $cookieid. Set $remoderate to true if the question should be requeued for moderation if
     * modified. Set $silent to true to not mark the question as edited. Reports event as appropriate. See
     * qa-app-posts.php for a higher-level function which is easier to use.
     *
     * @param      $oldpost
     * @param      $title
     * @param      $content
     * @param      $format
     * @param      $text
     * @param      $tagstring
     * @param      $notify
     * @param      $userid
     * @param      $handle
     * @param      $cookieid
     * @param null $extravalue
     * @param null $name
     * @param bool $remoderate
     * @param bool $silent
     */
    function qas_blog_b_post_set_content( $oldpost, $title, $content, $format, $text, $tagstring, $notify, $userid, $handle, $cookieid, $extravalue = null, $name = null, $remoderate = false, $silent = false, $categoryid, $is_draft = false )
    {
        qas_blog_post_unindex( $oldpost[ 'postid' ] );
        $wasqueued = ( $oldpost[ 'type' ] == 'B_QUEUED' );

        $post_type = ( $is_draft ? 'D' : ( $wasqueued ? 'B_QUEUED' : 'B' ) );

        $titlechanged = strcmp( $oldpost[ 'title' ], $title ) !== 0;
        $contentchanged = strcmp( $oldpost[ 'content' ], $content ) !== 0 || strcmp( $oldpost[ 'format' ], $format ) !== 0;
        $tagschanged = strcmp( $oldpost[ 'tags' ], $tagstring ) !== 0;
        $setupdated = ( $titlechanged || $contentchanged || $tagschanged ) && ( !$wasqueued ) && !$silent;

        qas_blog_db_post_set_content( $oldpost[ 'postid' ], $title, $content, $format, $tagstring, $notify,
            $setupdated ? $userid : null, $setupdated ? qa_remote_ip_address() : null,
            ( $titlechanged || $contentchanged ) ? QA_UPDATE_CONTENT : QA_UPDATE_TAGS, $name );

        if ( isset( $extravalue ) ) {
            qa_db_blogmeta_set( $oldpost[ 'postid' ], 'qa_q_extra', $extravalue );
        }

        if ( $setupdated && $remoderate ) {
            $commentsfollows = qas_blog_get_post_commentsfollows( $oldpost[ 'postid' ] );
            $closepost = qas_blog_post_get_b_post_closepost( $oldpost[ 'postid' ] );

            foreach ( $commentsfollows as $comment ) {
                if ( $comment[ 'basetype' ] == 'C' ) {
                    qas_blog_post_unindex( $comment[ 'postid' ] );
                }
            }

            if ( @$closepost[ 'parentid' ] == $oldpost[ 'postid' ] ) {
                qas_blog_post_unindex( $closepost[ 'postid' ] );
            }

            qas_blog_db_post_set_type( $oldpost[ 'postid' ], 'B_QUEUED' );
            qas_blog_update_counts_for_post( $oldpost[ 'postid' ] );
            qas_blog_db_queuedcount_update();

            if ( $oldpost[ 'flagcount' ] ) {
                qas_blog_db_flaggedcount_update();
            }

        } else if ( $oldpost[ 'type' ] == 'D' && !$is_draft ) {
            //this condition satisfies when a DRAFT is been confirmed to be published
            //now check if it is needed to send to moderation queue

            $categories = qa_db_select_with_pending( qas_blog_db_category_nav_selectspec( $categoryid, true ) );
            $categoryids = array_keys( qa_category_path( $categories, $categoryid ) );
            $userlevel = qa_user_level_for_categories( $categoryids );
            $queued = qa_user_moderation_reason( $userlevel ) !== false;

            if ( $queued ) {
                qas_blog_db_post_set_type( $oldpost[ 'postid' ], 'B_QUEUED' );
                qas_blog_db_queuedcount_update();
            } else {
                qas_blog_db_post_set_type( $oldpost[ 'postid' ], 'B' );
                qas_blog_post_index( $oldpost[ 'postid' ], 'B', $oldpost[ 'postid' ], $oldpost[ 'parentid' ], $title, $content, $format, $text, $tagstring, $oldpost[ 'categoryid' ] );
            }

            qas_blog_update_counts_for_post( $oldpost[ 'postid' ] );
            qas_blog_db_posts_calc_category_path( $oldpost[ 'postid' ] );

        } else if ( $oldpost[ 'type' ] == 'B' ) { // not hidden or queued
            qas_blog_post_index( $oldpost[ 'postid' ], 'B', $oldpost[ 'postid' ], $oldpost[ 'parentid' ], $title, $content, $format, $text, $tagstring, $oldpost[ 'categoryid' ] );
        }

        $eventparams = array(
            'postid'      => $oldpost[ 'postid' ],
            'title'       => $title,
            'content'     => $content,
            'format'      => $format,
            'text'        => $text,
            'tags'        => $tagstring,
            'extra'       => $extravalue,
            'name'        => $name,
            'oldquestion' => $oldpost,
        );

        qa_report_event( 'qas_blog_post_edit', $userid, $handle, $cookieid, $eventparams + array(
                'silent'         => $silent,
                'oldtitle'       => $oldpost[ 'title' ],
                'oldcontent'     => $oldpost[ 'content' ],
                'oldformat'      => $oldpost[ 'format' ],
                'oldtags'        => $oldpost[ 'tags' ],
                'titlechanged'   => $titlechanged,
                'contentchanged' => $contentchanged,
                'tagschanged'    => $tagschanged,
            ) );

        if ( $setupdated && $remoderate ) {
            qa_report_event( 'qas_blog_b_requeue', $userid, $handle, $cookieid, $eventparams );
        }
    }

    /**
     * Reopen $oldquestion if it was closed. Pass details of the user doing this in $userid, $handle and $cookieid, and
     * the
     * $oldclosepost (to match $oldquestion['closedbyid']) if any.
     * See qa-app-posts.php for a higher-level function which is easier to use.
     *
     * @param $oldpost
     * @param $oldclosepost
     * @param $userid
     * @param $handle
     * @param $cookieid
     */
    function qas_blog_post_close_clear( $oldpost, $oldclosepost, $userid, $handle, $cookieid )
    {
        if ( isset( $oldpost[ 'closedbyid' ] ) ) {
            qas_blog_db_post_set_closed( $oldpost[ 'postid' ], null, $userid, qa_remote_ip_address() );

            if ( isset( $oldclosepost ) && ( $oldclosepost[ 'parentid' ] == $oldpost[ 'postid' ] ) ) {
                qas_blog_post_unindex( $oldclosepost[ 'postid' ] );
                qas_blog_db_post_delete( $oldclosepost[ 'postid' ] );
            }

            qa_report_event( 'qas_blog_b_reopen', $userid, $handle, $cookieid, array(
                'postid'      => $oldpost[ 'postid' ],
                'oldquestion' => $oldpost,
            ) );
        }
    }

    /**
     * Close $oldquestion with the reason given in $note. Pass details of the user doing this in $userid, $handle and
     * $cookieid, and the $oldclosepost (to match $oldquestion['closedbyid']) if any.
     * See qa-app-posts.php for a higher-level function which is easier to use.
     *
     * @param $oldpost
     * @param $oldclosepost
     * @param $note
     * @param $userid
     * @param $handle
     * @param $cookieid
     */
    function qas_blog_post_close_other( $oldpost, $oldclosepost, $note, $userid, $handle, $cookieid )
    {
        qas_blog_post_close_clear( $oldpost, $oldclosepost, $userid, $handle, $cookieid );

        $postid = qas_blog_db_post_create( 'NOTE', $oldpost[ 'postid' ], null , $userid, isset( $userid ) ? null : $cookieid,
            qa_remote_ip_address(), null, $note, '', null, null, $oldpost[ 'categoryid' ] );

        qas_blog_db_posts_calc_category_path( $postid );

        if ( $oldpost[ 'type' ] == 'B' )
            qas_blog_post_index( $postid, 'NOTE', $oldpost[ 'postid' ], $oldpost[ 'postid' ], null, $note, '', $note, null, $oldpost[ 'categoryid' ] );

        qas_blog_db_post_set_closed( $oldpost[ 'postid' ], $postid, $userid, qa_remote_ip_address() );

        qa_report_event( 'qas_blog_b_close', $userid, $handle, $cookieid, array(
            'postid'      => $oldpost[ 'postid' ],
            'oldquestion' => $oldpost,
            'reason'      => 'other',
            'note'        => $note,
        ) );
    }

    /**
     * Set $oldquestion to hidden if $hidden is true, visible/normal if otherwise. All other parameters are as for
     * qa_question_set_status(...) This function is included mainly for backwards compatibility.
     *
     * @param      $oldpost
     * @param      $hidden
     * @param      $userid
     * @param      $handle
     * @param      $cookieid
     * @param      $commentsfollows
     * @param null $closepost
     */
    function qas_blog_post_b_set_hidden( $oldpost, $hidden, $userid, $handle, $cookieid, $commentsfollows, $closepost = null )
    {
        qas_blog_post_b_set_status( $oldpost, $hidden ? QA_POST_STATUS_HIDDEN : QA_POST_STATUS_NORMAL, $userid, $handle, $cookieid, $commentsfollows, $closepost );
    }

    /**
     * Set the status (application level) of $oldpost to $status, one of the QA_POST_STATUS_* constants above. Pass
     * details of the user doing this in $userid, $handle and $cookieid, the database records for all answers to the
     * question in $answers, the database records for all comments on the question or the question's answers in
     * $commentsfollows ($commentsfollows can also contain records for follow-on questions which are ignored), and
     * $closepost to match $oldquestion['closedbyid'] (if any). Handles indexing, user points, cached counts and event
     * reports. See qa-app-posts.php for a higher-level function which is easier to use.
     *
     * @param      $oldpost
     * @param      $status
     * @param      $userid
     * @param      $handle
     * @param      $cookieid
     * @param      $commentsfollows
     * @param null $closepost
     */
    function qas_blog_post_b_set_status( $oldpost, $status, $userid, $handle, $cookieid, $commentsfollows, $closepost = null )
    {
        $washidden = ( $oldpost[ 'type' ] == 'B_HIDDEN' );
        $wasqueued = ( $oldpost[ 'type' ] == 'B_QUEUED' );
        $wasrequeued = $wasqueued && isset( $oldpost[ 'updated' ] );

        qas_blog_post_unindex( $oldpost[ 'postid' ] );

        foreach ( $commentsfollows as $comment )
            if ( $comment[ 'basetype' ] == 'B' )
                qas_blog_post_unindex( $comment[ 'postid' ] );

        if ( @$closepost[ 'parentid' ] == $oldpost[ 'postid' ] )
            qas_blog_post_unindex( $closepost[ 'postid' ] );

        $setupdated = false;
        $event = null;

        if ( $status == QA_POST_STATUS_QUEUED ) {
            $newtype = 'B_QUEUED';
            if ( !$wasqueued )
                $event = 'q_requeue'; // same event whether it was hidden or shown before

        } elseif ( $status == QA_POST_STATUS_HIDDEN ) {
            $newtype = 'B_HIDDEN';
            if ( !$washidden ) {
                $event = $wasqueued ? 'q_reject' : 'q_hide';
                if ( !$wasqueued )
                    $setupdated = true;
            }

        } elseif ( $status == QA_POST_STATUS_NORMAL ) {
            $newtype = 'B';
            if ( $wasqueued )
                $event = 'q_approve';
            elseif ( $washidden ) {
                $event = 'q_reshow';
                $setupdated = true;
            }

        } else
            qa_fatal_error( 'Unknown status in qa_question_set_status(): ' . $status );

        qas_blog_db_post_set_type( $oldpost[ 'postid' ], $newtype, $setupdated ? $userid : null, $setupdated ? qa_remote_ip_address() : null, QA_UPDATE_VISIBLE );

        if ( $wasqueued && ( $status == QA_POST_STATUS_NORMAL ) && qa_opt( 'moderate_update_time' ) ) { // ... for approval of a post, can set time to now instead
            if ( $wasrequeued ) // reset edit time to now if there was one, since we're approving the edit...
                qas_blog_db_post_set_updated( $oldpost[ 'postid' ], null );

            else { // ... otherwise we're approving original created post
                qas_blog_db_post_set_created( $oldpost[ 'postid' ], null );
            }
        }

        if ( $wasqueued || ( $status == QA_POST_STATUS_QUEUED ) )
            qas_blog_db_queuedcount_update();

        if ( $oldpost[ 'flagcount' ] )
            qas_blog_db_flaggedcount_update();

        if ( $status == QA_POST_STATUS_NORMAL ) {
            qas_blog_post_index( $oldpost[ 'postid' ], 'B', $oldpost[ 'postid' ], $oldpost[ 'parentid' ], $oldpost[ 'title' ], $oldpost[ 'content' ],
                $oldpost[ 'format' ], qa_viewer_text( $oldpost[ 'content' ], $oldpost[ 'format' ] ), $oldpost[ 'tags' ], $oldpost[ 'categoryid' ] );

            foreach ( $commentsfollows as $comment )
                if ( $comment[ 'type' ] == 'C' ) {
                        qas_blog_post_index( $comment[ 'postid' ], $comment[ 'type' ], $oldpost[ 'postid' ], $comment[ 'parentid' ], null,
                            $comment[ 'content' ], $comment[ 'format' ], qa_viewer_text( $comment[ 'content' ], $comment[ 'format' ] ), null, $comment[ 'categoryid' ] );
                }

            if ( $closepost[ 'parentid' ] == $oldpost[ 'postid' ] )
                qas_blog_post_index( $closepost[ 'postid' ], $closepost[ 'type' ], $oldpost[ 'postid' ], $closepost[ 'parentid' ], null,
                    $closepost[ 'content' ], $closepost[ 'format' ], qa_viewer_text( $closepost[ 'content' ], $closepost[ 'format' ] ), null, $closepost[ 'categoryid' ] );
        }

        $eventparams = array(
            'postid'     => $oldpost[ 'postid' ],
            'parentid'   => $oldpost[ 'parentid' ],
            'parent'     => isset( $oldpost[ 'parentid' ] ) ? qa_db_single_select( qas_blog_db_full_post_selectspec( null, $oldpost[ 'parentid' ] ) ) : null,
            'title'      => $oldpost[ 'title' ],
            'content'    => $oldpost[ 'content' ],
            'format'     => $oldpost[ 'format' ],
            'text'       => qa_viewer_text( $oldpost[ 'content' ], $oldpost[ 'format' ] ),
            'tags'       => $oldpost[ 'tags' ],
            'categoryid' => $oldpost[ 'categoryid' ],
            'name'       => $oldpost[ 'name' ],
        );

        if ( isset( $event ) )
            qa_report_event( 'qas_blog_' . $event, $userid, $handle, $cookieid, $eventparams + array(
                    'oldquestion' => $oldpost,
                ) );

        if ( $wasqueued && ( $status == QA_POST_STATUS_NORMAL ) && !$wasrequeued ) {
            qa_report_event( 'qas_blog_b_post', $oldpost[ 'userid' ], $oldpost[ 'handle' ], $oldpost[ 'cookieid' ], $eventparams + array(
                    'notify'  => isset( $oldpost[ 'notify' ] ),
                    'email'   => qa_email_validate( $oldpost[ 'notify' ] ) ? $oldpost[ 'notify' ] : null,
                    'delayed' => $oldpost[ 'created' ],
                ) );
        }
    }

    /**
     * Sets the category (application level) of $oldquestion to $categoryid. Pass details of the user doing this in
     * $userid, $handle and $cookieid, the database records for all answers to the question in $answers, the database
     * records for all comments on the question or the question's answers in $commentsfollows ($commentsfollows can
     * also
     * contain records for follow-on questions which are ignored), and $closepost to match $oldquestion['closedbyid']
     * (if any). Set $silent to true to not mark the question as edited. Handles cached counts and event reports and
     * will reset category IDs and paths for all answers and comments. See qa-app-posts.php for a higher-level function
     * which is easier to use.
     *
     * @param      $oldpost
     * @param      $categoryid
     * @param      $userid
     * @param      $handle
     * @param      $cookieid
     * @param      $commentsfollows
     * @param null $closepost
     * @param bool $silent
     */
    function qas_blog_post_set_category( $oldpost, $categoryid, $userid, $handle, $cookieid, $commentsfollows, $closepost = null, $silent = false )
    {
        $oldpath = qas_blog_db_post_get_category_path( $oldpost[ 'postid' ] );

        qas_blog_db_post_set_category( $oldpost[ 'postid' ], $categoryid, $silent ? null : $userid, $silent ? null : qa_remote_ip_address() );
        qas_blog_db_posts_calc_category_path( $oldpost[ 'postid' ] );

        $newpath = qas_blog_db_post_get_category_path( $oldpost[ 'postid' ] );

        qas_blog_db_category_path_post_count_update( $oldpath );
        qas_blog_db_category_path_post_count_update( $newpath );

        $otherpostids = array();

        foreach ( $commentsfollows as $comment ) {
            if ( $comment[ 'basetype' ] == 'C' )
                $otherpostids[] = $comment[ 'postid' ];
        }

        if ( @$closepost[ 'parentid' ] == $oldpost[ 'postid' ] ) {
            $otherpostids[] = $closepost[ 'postid' ];
        }

        qas_blog_db_posts_set_category_path( $otherpostids, $newpath );

        $searchmodules = qa_load_modules_with( 'search', 'move_blog_post' );

        foreach ( $searchmodules as $searchmodule => $search ) {
            $search->move_blog_post( $oldpost[ 'postid' ], $categoryid );
            foreach ( $otherpostids as $otherpostid )
                $search->move_blog_post( $otherpostid, $categoryid );
        }

        qa_report_event( 'qas_blog_b_post_move', $userid, $handle, $cookieid, array(
            'postid'        => $oldpost[ 'postid' ],
            'oldquestion'   => $oldpost,
            'categoryid'    => $categoryid,
            'oldcategoryid' => $oldpost[ 'categoryid' ],
        ) );
    }

    /**
     * Permanently delete a question (application level) from the database. The question must not have any answers or
     * comments on it. Pass details of the user doing this in $userid, $handle and $cookieid, and $closepost to match
     * $oldquestion['closedbyid'] (if any). Handles unindexing, votes, points, cached counts and event reports.
     * See qa-app-posts.php for a higher-level function which is easier to use.
     *
     * @param      $oldpost
     * @param      $userid
     * @param      $handle
     * @param      $cookieid
     * @param null $oldclosepost
     */
    function qas_blog_post_b_delete( $oldpost, $userid, $handle, $cookieid, $oldclosepost = null )
    {
        if ( $oldpost[ 'type' ] != 'B_HIDDEN' ) {
            qa_fatal_error( 'Tried to delete a non-hidden question' );
        }

        if ( isset( $oldclosepost ) && ( $oldclosepost[ 'parentid' ] == $oldpost[ 'postid' ] ) ) {
            qas_blog_db_post_set_closed( $oldpost[ 'postid' ], null ); // for foreign key constraint
            qas_blog_post_unindex( $oldclosepost[ 'postid' ] );
            qas_blog_db_post_delete( $oldclosepost[ 'postid' ] );
        }

        $oldpath = qas_blog_db_post_get_category_path( $oldpost[ 'postid' ] );

        $params = array(
            'postid'      => $oldpost[ 'postid' ],
            'oldquestion' => $oldpost,
        );

        qa_report_event( 'qas_blog_b_delete_before', $userid, $handle, $cookieid, $params );

        qas_blog_post_unindex( $oldpost[ 'postid' ] );
        qas_blog_db_post_delete( $oldpost[ 'postid' ] ); // also deletes any related votes due to foreign key cascading
        qas_blog_update_counts_for_post( null );
        qas_blog_db_category_path_post_count_update( $oldpath ); // don't do inside qa_update_counts_for_q() since post no longer exists

        qa_report_event( 'qas_blog_b_delete', $userid, $handle, $cookieid, $params );
    }

    /**
     * Permanently delete a question (application level) from the database. The question must not have any answers or
     * comments on it. Pass details of the user doing this in $userid, $handle and $cookieid, and $closepost to match
     * $oldquestion['closedbyid'] (if any). Handles unindexing, votes, points, cached counts and event reports.
     * See qa-app-posts.php for a higher-level function which is easier to use.
     *
     * @param      $oldpost
     * @param      $userid
     * @param      $handle
     * @param      $cookieid
     * @param null $oldclosepost
     */
    function qas_blog_post_draft_delete( $oldpost, $userid, $handle, $cookieid )
    {
        $oldpath = qas_blog_db_post_get_category_path( $oldpost[ 'postid' ] );
        qas_blog_post_unindex( $oldpost[ 'postid' ] );
        qas_blog_db_post_delete( $oldpost[ 'postid' ] ); // also deletes any related voteds due to foreign key cascading
        qas_blog_update_counts_for_post( null );
        qas_blog_db_category_path_post_count_update( $oldpath ); // don't do inside qa_update_counts_for_q() since post no longer exists

    }

    /**
     * Set the author (application level) of $oldquestion to $userid and also pass $handle and $cookieid
     * of user. Updates points and reports events as appropriate.
     *
     * @param $oldpost
     * @param $userid
     * @param $handle
     * @param $cookieid
     */
    function qas_blog_post_set_userid( $oldpost, $userid, $handle, $cookieid )
    {
        $postid = $oldpost[ 'postid' ];

        qas_blog_db_post_set_userid( $postid, $userid );

        qa_report_event( 'qas_blog_post_claim', $userid, $handle, $cookieid, array(
            'postid'      => $postid,
            'oldquestion' => $oldpost,
        ) );
    }

    /**
     * Set the author in the database of $postid to $userid, and set the lastuserid to $userid as well if appropriate
     *
     * @param $postid
     * @param $userid
     */
    function qas_blog_db_post_set_userid( $postid, $userid )
    {
        qa_db_query_sub(
            'UPDATE ^blogs SET userid=$, lastuserid=IF(updated IS NULL, lastuserid, COALESCE(lastuserid,$)) WHERE postid=#',
            $userid, $userid, $postid
        );
    }

    /**
     * Remove post $postid from our index and update appropriate word counts. Calls through to all search modules.
     *
     * @param $postid
     */
    function qas_blog_post_unindex( $postid )
    {
        global $qa_post_indexing_suspended;

        if ( $qa_post_indexing_suspended > 0 )
            return;

        //	Send through to any search modules for unindexing

        $searchmodules = qa_load_modules_with( 'search', 'unindex_blog_post' );

        foreach ( $searchmodules as $searchmodule => $search ) {
            $search->unindex_blog_post( $postid );
        }
    }

    /**
     * Change the fields of a comment (application level) to $content, $format, $notify and $name, then reindex based
     * on
     * $text. For backwards compatibility if $name is null then the name will not be changed. Pass the comment's
     * database record before changes in $oldcomment, details of the user doing this in $userid, $handle and $cookieid,
     * the antecedent question in $post and the answer's database record in $answer if this is a comment on an answer,
     * otherwise null. Set $remoderate to true if the question should be requeued for moderation if modified. Set
     * $silent to true to not mark the question as edited. Handles unindexing and event reports. See qa-app-posts.php
     * for a higher-level function which is easier to use.
     *
     * @param      $oldcomment
     * @param      $content
     * @param      $format
     * @param      $text
     * @param      $notify
     * @param      $userid
     * @param      $handle
     * @param      $cookieid
     * @param      $post
     * @param      $parent
     * @param null $name
     * @param bool $remoderate
     * @param bool $silent
     */
    function qas_blog_comment_set_content( $oldcomment, $content, $format, $text, $notify, $userid, $handle, $cookieid, $post, $parent, $name = null, $remoderate = false, $silent = false )
    {
        if ( !isset( $parent ) )
            $parent = $post; // for backwards compatibility with old answer parameter

        qas_blog_post_unindex( $oldcomment[ 'postid' ] );

        $wasqueued = ( $oldcomment[ 'type' ] == 'C_QUEUED' );
        $contentchanged = strcmp( $oldcomment[ 'content' ], $content ) || strcmp( $oldcomment[ 'format' ], $format );
        $setupdated = $contentchanged && ( !$wasqueued ) && !$silent;

        qas_blog_db_post_set_content( $oldcomment[ 'postid' ], $oldcomment[ 'title' ], $content, $format, $oldcomment[ 'tags' ], $notify,
            $setupdated ? $userid : null, $setupdated ? qa_remote_ip_address() : null, QA_UPDATE_CONTENT, $name );

        if ( $setupdated && $remoderate ) {
            qas_blog_db_post_set_type( $oldcomment[ 'postid' ], 'C_QUEUED' );
            //qa_db_ccount_update();
            qas_blog_db_queuedcount_update();

            if ( $oldcomment[ 'flagcount' ] ) {
                qas_blog_db_flaggedcount_update();
            }

        } elseif ( ( $oldcomment[ 'type' ] == 'C' ) && ( $post[ 'type' ] == 'B' ) ) { // all must be visible
            qas_blog_post_index( $oldcomment[ 'postid' ], 'C', $post[ 'postid' ], $oldcomment[ 'parentid' ], null, $content, $format, $text, null, $oldcomment[ 'categoryid' ] );
        }

        $eventparams = array(
            'postid'     => $oldcomment[ 'postid' ],
            'parentid'   => $oldcomment[ 'parentid' ],
            'parenttype' => $parent[ 'basetype' ],
            'parent'     => $parent,
            'questionid' => $post[ 'postid' ],
            'question'   => $post,
            'content'    => $content,
            'format'     => $format,
            'text'       => $text,
            'name'       => $name,
            'oldcomment' => $oldcomment,
        );

        qa_report_event( 'qas_blog_c_edit', $userid, $handle, $cookieid, $eventparams + array(
                'silent'         => $silent,
                'oldcontent'     => $oldcomment[ 'content' ],
                'oldformat'      => $oldcomment[ 'format' ],
                'contentchanged' => $contentchanged,
            ) );

        if ( $setupdated && $remoderate )
            qa_report_event( 'qas_blog_c_requeue', $userid, $handle, $cookieid, $eventparams );
    }

    /**
     * Set $oldcomment to hidden if $hidden is true, visible/normal if otherwise. All other parameters are as for
     * qa_comment_set_status(...) This function is included mainly for backwards compatibility.
     *
     * @param $oldcomment
     * @param $hidden
     * @param $userid
     * @param $handle
     * @param $cookieid
     * @param $post
     * @param $parent
     */
    function qas_blog_comment_set_hidden( $oldcomment, $hidden, $userid, $handle, $cookieid, $post, $parent )
    {
        qas_blog_comment_set_status( $oldcomment, $hidden ? QA_POST_STATUS_HIDDEN : QA_POST_STATUS_NORMAL, $userid, $handle, $cookieid, $post, $parent );
    }

    /**
     * Set the status (application level) of $oldcomment to $status, one of the QA_POST_STATUS_* constants above. Pass
     * the antecedent question's record in $post, details of the user doing this in $userid, $handle and $cookieid, and
     * the answer's database record in $answer if this is a comment on an answer, otherwise null. Handles indexing,
     * user points, cached counts and event reports. See qa-app-posts.php for a higher-level function which is easier
     * to use.
     *
     * @param $oldcomment
     * @param $status
     * @param $userid
     * @param $handle
     * @param $cookieid
     * @param $post
     * @param $parent
     */
    function qas_blog_comment_set_status( $oldcomment, $status, $userid, $handle, $cookieid, $post, $parent )
    {
        if ( !isset( $parent ) )
            $parent = $post; // for backwards compatibility with old answer parameter

        $washidden = ( $oldcomment[ 'type' ] == 'C_HIDDEN' );
        $wasqueued = ( $oldcomment[ 'type' ] == 'C_QUEUED' );
        $wasrequeued = $wasqueued && isset( $oldcomment[ 'updated' ] );

        qas_blog_post_unindex( $oldcomment[ 'postid' ] );

        $setupdated = false;
        $event = null;

        if ( $status == QA_POST_STATUS_QUEUED ) {
            $newtype = 'C_QUEUED';
            if ( !$wasqueued )
                $event = 'c_requeue'; // same event whether it was hidden or shown before

        } elseif ( $status == QA_POST_STATUS_HIDDEN ) {
            $newtype = 'C_HIDDEN';
            if ( !$washidden ) {
                $event = $wasqueued ? 'c_reject' : 'c_hide';
                if ( !$wasqueued )
                    $setupdated = true;
            }

        } elseif ( $status == QA_POST_STATUS_NORMAL ) {
            $newtype = 'C';
            if ( $wasqueued )
                $event = 'c_approve';
            elseif ( $washidden ) {
                $event = 'c_reshow';
                $setupdated = true;
            }

        } else
            qa_fatal_error( 'Unknown status in qa_comment_set_status(): ' . $status );

        qas_blog_db_post_set_type( $oldcomment[ 'postid' ], $newtype, $setupdated ? $userid : null, $setupdated ? qa_remote_ip_address() : null, QA_UPDATE_VISIBLE );

        if ( $wasqueued && ( $status == QA_POST_STATUS_NORMAL ) && qa_opt( 'moderate_update_time' ) ) { // ... for approval of a post, can set time to now instead
            if ( $wasrequeued ) {
                qas_blog_db_post_set_updated( $oldcomment[ 'postid' ], null );
            } else {
                qas_blog_db_post_set_created( $oldcomment[ 'postid' ], null );
            }
        }

        //qa_db_ccount_update();

        if ( $wasqueued || ( $status == QA_POST_STATUS_QUEUED ) )
            qas_blog_db_queuedcount_update();

        if ( $oldcomment[ 'flagcount' ] )
            qas_blog_db_flaggedcount_update();

        if ( ( $post[ 'type' ] == 'B' ) && ( $status == QA_POST_STATUS_NORMAL ) ) // only index if none of the things it depends on are hidden or queued
            qas_blog_post_index( $oldcomment[ 'postid' ], 'C', $post[ 'postid' ], $oldcomment[ 'parentid' ], null, $oldcomment[ 'content' ],
                $oldcomment[ 'format' ], qa_viewer_text( $oldcomment[ 'content' ], $oldcomment[ 'format' ] ), null, $oldcomment[ 'categoryid' ] );

        $eventparams = array(
            'postid'     => $oldcomment[ 'postid' ],
            'parentid'   => $oldcomment[ 'parentid' ],
            'parenttype' => $parent[ 'basetype' ],
            'parent'     => $parent,
            'questionid' => $post[ 'postid' ],
            'question'   => $post,
            'content'    => $oldcomment[ 'content' ],
            'format'     => $oldcomment[ 'format' ],
            'text'       => qa_viewer_text( $oldcomment[ 'content' ], $oldcomment[ 'format' ] ),
            'categoryid' => $oldcomment[ 'categoryid' ],
            'name'       => $oldcomment[ 'name' ],
        );

        if ( isset( $event ) )
            qa_report_event( 'qas_blog_' . $event, $userid, $handle, $cookieid, $eventparams + array(
                    'oldcomment' => $oldcomment,
                ) );

        if ( $wasqueued && ( $status == QA_POST_STATUS_NORMAL ) && !$wasrequeued ) {
            $commentsfollows = qa_db_single_select( qas_blog_db_full_child_posts_selectspec( null, $oldcomment[ 'parentid' ] ) );
            $thread = array();

            foreach ( $commentsfollows as $comment )
                if ( ( $comment[ 'type' ] == 'C' ) && ( $comment[ 'parentid' ] == $parent[ 'postid' ] ) )
                    $thread[] = $comment;

            qa_report_event( 'qas_blog_c_post', $oldcomment[ 'userid' ], $oldcomment[ 'handle' ], $oldcomment[ 'cookieid' ], $eventparams + array(
                    'thread'  => $thread,
                    'notify'  => isset( $oldcomment[ 'notify' ] ),
                    'email'   => qa_email_validate( $oldcomment[ 'notify' ] ) ? $oldcomment[ 'notify' ] : null,
                    'delayed' => $oldcomment[ 'created' ],
                ) );
        }
    }

    /**
     * Permanently delete a comment in $oldcomment (application level) from the database. Pass the database post in
     * $post and the answer's database record in $answer if this is a comment on an answer, otherwise null. Pass
     * details of the user doing this in $userid, $handle and $cookieid. Handles unindexing, points, cached counts and
     * event reports. See qa-app-posts.php for a higher-level function which is easier to use.
     *
     * @param $oldcomment
     * @param $post
     * @param $parent
     * @param $userid
     * @param $handle
     * @param $cookieid
     */
    function qas_blog_comment_delete( $oldcomment, $post, $parent, $userid, $handle, $cookieid )
    {
        if ( !isset( $parent ) )
            $parent = $post; // for backwards compatibility with old answer parameter

        if ( $oldcomment[ 'type' ] != 'C_HIDDEN' )
            qa_fatal_error( 'Tried to delete a non-hidden comment' );

        $params = array(
            'postid'     => $oldcomment[ 'postid' ],
            'parentid'   => $oldcomment[ 'parentid' ],
            'oldcomment' => $oldcomment,
            'parenttype' => $parent[ 'basetype' ],
            'questionid' => $post[ 'postid' ],
        );

        qa_report_event( 'qas_blog_c_delete_before', $userid, $handle, $cookieid, $params );

        qas_blog_post_unindex( $oldcomment[ 'postid' ] );
        $all_replies = qas_blog_db_get_comment_reply_ids($oldcomment[ 'postid' ]);

        foreach($all_replies as $reply_id){
            if(!empty($reply_id))
                qas_blog_post_unindex( $reply_id );
        }

        qas_blog_db_post_delete( $oldcomment[ 'postid' ] );
        //qa_db_ccount_update();

        qa_report_event( 'qas_blog_c_delete', $userid, $handle, $cookieid, $params );
    }

    /**
     * Set the author (application level) of $oldcomment to $userid and also pass $handle and $cookieid
     * of user. Updates points and reports events as appropriate.
     *
     * @param $oldcomment
     * @param $userid
     * @param $handle
     * @param $cookieid
     */
    function qas_blog_comment_set_userid( $oldcomment, $userid, $handle, $cookieid )
    {
        $postid = $oldcomment[ 'postid' ];

        qas_blog_db_post_set_userid( $postid, $userid );

        qa_report_event( 'qas_blog_c_claim', $userid, $handle, $cookieid, array(
            'postid'     => $postid,
            'parentid'   => $oldcomment[ 'parentid' ],
            'oldcomment' => $oldcomment,
        ) );
    }

    /**
     * Set $postid to be closed by post $closedbyid (null if not closed) in the database, and optionally record that
     * $lastuserid did it from $lastip
     *
     * @param      $postid
     * @param      $closedbyid
     * @param null $lastuserid
     * @param null $lastip
     */
    function qas_blog_db_post_set_closed( $postid, $closedbyid, $lastuserid = null, $lastip = null )
    {
        if ( isset( $lastuserid ) || isset( $lastip ) ) {
            qa_db_query_sub(
                "UPDATE ^blogs SET closedbyid=#, updated=NOW(), updatetype=$, lastuserid=$, lastip=INET6_ATON($) WHERE postid=#",
                $closedbyid, QA_UPDATE_CLOSED, $lastuserid, $lastip, $postid
            );
        } else
            qa_db_query_sub(
                'UPDATE ^blogs SET closedbyid=# WHERE postid=#',
                $closedbyid, $postid
            );
    }

    /**
     * Fetches the child posts of the post and delete them recursively
     *
     * @param $postid
     */
    function qas_blog_post_delete_recursive( $postid )
    {
        static $qas_blog_deleted_posts;

        if ( is_null( $qas_blog_deleted_posts ) ) {
            $qas_blog_deleted_posts = array();
        }

        if ( in_array( $postid, $qas_blog_deleted_posts ) ) {
            return;
        }

        $oldpost = qas_blog_post_get_full( $postid, 'BC' );

        if ( !$oldpost[ 'hidden' ] ) {
            qas_blog_post_set_hidden( $postid, true, null );
            $oldpost = qas_blog_post_get_full( $postid, 'BC' );
        }

        switch ( $oldpost[ 'basetype' ] ) {
            case 'B':
                $commentsfollows = qas_blog_get_post_commentsfollows( $postid );
                $closepost = qas_blog_post_get_b_post_closepost( $postid );

                if ( count( $commentsfollows ) ) {
                    foreach ( $commentsfollows as $commentsfollow ) {
                        qas_blog_post_delete_recursive( $commentsfollow[ 'postid' ] );
                    }
                }
                if ( !in_array( $oldpost[ 'postid' ], $qas_blog_deleted_posts ) ) {
                    qas_blog_post_b_delete( $oldpost, null, null, null, $closepost );
                    $qas_blog_deleted_posts[] = $oldpost[ 'postid' ];
                }
                break;

            case 'C':
                $parent = qas_blog_post_get_full( $oldpost[ 'parentid' ], 'B' );
                $comment_parent_post = qas_blog_parent_to_post( $parent );

                if ( !in_array( $oldpost[ 'postid' ], $qas_blog_deleted_posts ) ) {
                    qas_blog_comment_delete( $oldpost, $comment_parent_post, $parent, null, null, null );
                    $qas_blog_deleted_posts[] = $oldpost[ 'postid' ];
                }
                break;
        }

    }

    /*
        Omit PHP closing tag to help avoid accidental output
    */

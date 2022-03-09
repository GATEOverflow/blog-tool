<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    $code = qa_post_text( 'code' );


//	Process general cancel button

    if ( qa_clicked( 'docancel' ) ) {
        qas_blog_page_b_refresh( $pagestart );
    }


//	Process incoming actions
//	Process close buttons for question

    if ( $post[ 'closeable' ] ) {
        if ( qa_clicked( 'blog_doclose' ) )
            qas_blog_page_b_refresh( $pagestart, 'close' );

        elseif ( qa_clicked( 'doclose' ) && qas_blog_page_blog_permit_edit( $post, 'qas_blog_permit_close_p', $pageerror ) ) {
            if ( qas_blog_page_b_close_b_submit( $post, $closepost, $closein, $closeerrors ) )
                qas_blog_page_b_refresh( $pagestart );
            else
                $formtype = 'q_close'; // keep editing if an error

        } elseif ( ( $pagestate == 'close' ) && qas_blog_page_blog_permit_edit( $post, 'qas_blog_permit_close_p', $pageerror ) )
            $formtype = 'q_close';
    }


//	Process any single click operations or delete button for question

    if ( qas_blog_page_post_single_click( $post, $commentsfollows, $closepost, $pageerror ) ) {
        qas_blog_page_b_refresh( $pagestart );
    }

    if ( qa_clicked( 'blog_dodelete' ) && $post[ 'deleteable' ] && qas_blog_page_b_click_check_form_code( $post, $pageerror ) ) {
        //qas_blog_post_b_delete( $post, $userid, qa_get_logged_in_handle(), $cookieid, $closepost );
        qas_blog_post_delete_recursive( $post[ 'postid' ] );
        qa_redirect( qas_get_blog_url_sub( qas_blog_url_plural_structure() ) ); // redirect to the blogs page
    }


//	Process edit or save button for question

    if ( $post[ 'editbutton' ] || $post[ 'retagcatbutton' ] ) {
        if ( qa_clicked( 'blog_doedit' ) ) {
            qas_blog_page_b_refresh( $pagestart, 'edit-' . $postid );
        } elseif ( qa_clicked( 'blog_dosave' ) && qas_blog_page_blog_permit_edit( $post, 'qas_blog_permit_edit_p', $pageerror, 'qas_blog_permit_retag_cat' ) ) {

            if ( qas_blog_page_p_edit_post_submit( $post, $commentsfollows, $closepost, $qin, $qerrors ) ) {
                qa_redirect( qas_blog_request( $postid, $qin[ 'title' ] ) ); // don't use refresh since URL may have changed
            } else {
                $formtype = 'q_edit'; // keep editing if an error
                $pageerror = @$qerrors[ 'page' ]; // for security code failure
            }

        } elseif ( ( $pagestate == ( 'edit-' . $postid ) ) && qas_blog_page_blog_permit_edit( $post, 'qas_blog_permit_edit_p', $pageerror, 'qas_blog_permit_retag_cat' ) ) {
            $formtype = 'q_edit';
        }

        if ( $formtype == 'q_edit' ) { // get tags for auto-completion
            if ( qa_opt( 'do_complete_tags' ) ) {
                $completetags = array_keys( qa_db_select_with_pending( qa_db_popular_tags_selectspec( 0, QA_DB_RETRIEVE_COMPLETE_TAGS ) ) );
            } else {
                $completetags = array();
            }
        }
    }


//	Process adding a comment to question (shows form or processes it)

    if ( $post[ 'commentbutton' ] && !$post[ 'closed' ] ) {
        if ( qa_clicked( 'blog_docomment' ) ) {
            qas_blog_page_b_refresh( $pagestart, 'comment-' . $postid );
        }

        if ( qa_clicked( 'c' . $postid . '_doadd' ) || ( $pagestate == ( 'comment-' . $postid ) ) ) {
            qas_blog_page_post_do_comment( $post, $post, $commentsfollows, $pagestart, $usecaptcha, $cnewin, $cnewerrors, $formtype, $formpostid, $pageerror );
        }
    }


//	Process hide, show, delete, flag, unflag, edit or save button for comments

    foreach ( $commentsfollows as $commentid => $comment ) {
        if ( $comment[ 'basetype' ] == 'C' ) {
            $cparentid = $comment[ 'parentid' ];
            $commentparent = $post;
            $prefix = 'c' . $commentid . '_';

            if ( qa_clicked( $prefix . 'docomment' ) ) {
                qas_blog_page_b_refresh( $pagestart, 'comment-' . $postid, 'C', $commentid );
            }

            if ( qas_blog_page_post_single_click_c( $comment, $post, $commentparent, $pageerror ) ) {
                qas_blog_page_b_refresh( $pagestart, 'showcomments-' . $cparentid, $commentparent[ 'basetype' ], $cparentid );
            }

            if ( $comment[ 'editbutton' ] ) {
                if ( qa_clicked( $prefix . 'doedit' ) ) {
                    if ( qas_blog_page_blog_permit_edit( $comment, 'qas_blog_permit_edit_c', $pageerror ) ) // extra check here ensures error message is visible
                        qas_blog_page_b_refresh( $pagestart, 'edit-' . $commentid, 'C', $commentid );
                } elseif ( qa_clicked( $prefix . 'dosave' ) && qas_blog_page_blog_permit_edit( $comment, 'qas_blog_permit_edit_c', $pageerror ) ) {
                    if ( qas_page_blog_edit_c_submit( $comment, $post, $commentparent, $ceditin[ $commentid ], $cediterrors[ $commentid ] ) ) {
                        qas_blog_page_b_refresh( $pagestart, null, 'C', $commentid );
                    } else {
                        $formtype = 'c_edit';
                        $formpostid = $commentid; // keep editing if an error
                    }
                } elseif ( ( $pagestate == ( 'edit-' . $commentid ) ) && qas_blog_page_blog_permit_edit( $comment, 'qas_blog_permit_edit_c', $pageerror ) ) {
                    $formtype = 'c_edit';
                    $formpostid = $commentid;
                }
            }
        }
    }




    /*
        Omit PHP closing tag to help avoid accidental output
    */
<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }
    
    class qas_blog_email_notifier
    {

        public function process_event( $event, $userid, $handle, $cookieid, $params )
        {
            switch ( $event ) {
                case 'qas_blog_b_post':
                    $sendhandle = isset( $handle ) ? $handle : ( strlen( $params[ 'name' ] ) ? $params[ 'name' ] : qa_lang( 'main/anonymous' ) );

                    if ( qa_opt( 'notify_admin_q_post' ) )
                        qa_send_notification( null, qa_opt( 'feedback_email' ), null, qa_lang( 'qas_emails/blog_posted_subject' ), qa_lang( 'qas_emails/blog_posted_body' ), array(
                            '^q_handle'  => $sendhandle,
                            '^q_title'   => $params[ 'title' ], // don't censor title or content here since we want the admin to see bad words
                            '^q_content' => $params[ 'text' ],
                            '^url'       => qas_blog_post_path( $params[ 'postid' ], $params[ 'title' ], true ),
                        ) );

                    break;

                case 'qas_blog_c_post':
                    $parent = $params[ 'parent' ];
                    $post = $params[ 'question' ];

                    $senttoemail = array(); // to ensure each user or email gets only one notification about an added comment
                    $senttouserid = array();

                    switch ( $parent[ 'basetype' ] ) {
                        case 'B':
                            $subject = qa_lang( 'qas_emails/blog_commented_subject' );
                            $body = qa_lang( 'qas_emails/blog_commented_body' );
                            $context = $parent[ 'title' ];
                            break;
                    }

                    $blockwordspreg = qa_get_block_words_preg();
                    $sendhandle = isset( $handle )
                        ? $handle
                        : ( strlen( $params[ 'name' ] )
                            ? $params[ 'name' ]
                            : qa_lang( 'main/anonymous' ) );

                    $sendcontext = qa_block_words_replace( $context, $blockwordspreg );
                    $sendtext = qa_block_words_replace( $params[ 'text' ], $blockwordspreg );
                    $sendurl = qas_blog_post_path( $post[ 'postid' ], $post[ 'title' ], true, 'C', $params[ 'postid' ] );

                    if ( isset( $parent[ 'notify' ] ) && !qa_post_is_by_user( $parent, $userid, $cookieid ) ) {
                        $senduserid = $parent[ 'userid' ];
                        $sendemail = @$parent[ 'notify' ];

                        if ( qa_email_validate( $sendemail ) )
                            $senttoemail[ $sendemail ] = true;
                        elseif ( isset( $senduserid ) )
                            $senttouserid[ $senduserid ] = true;

                        qa_send_notification( $senduserid, $sendemail, @$parent[ 'handle' ], $subject, $body, array(
                            '^c_handle'  => $sendhandle,
                            '^c_context' => $sendcontext,
                            '^c_content' => $sendtext,
                            '^url'       => $sendurl,
                        ) );
                    }

                    foreach ( $params[ 'thread' ] as $comment )
                        if ( isset( $comment[ 'notify' ] ) && !qa_post_is_by_user( $comment, $userid, $cookieid ) ) {
                            $senduserid = $comment[ 'userid' ];
                            $sendemail = @$comment[ 'notify' ];

                            if ( qa_email_validate( $sendemail ) ) {
                                if ( @$senttoemail[ $sendemail ] )
                                    continue;

                                $senttoemail[ $sendemail ] = true;

                            } elseif ( isset( $senduserid ) ) {
                                if ( @$senttouserid[ $senduserid ] )
                                    continue;

                                $senttouserid[ $senduserid ] = true;
                            }

                            qa_send_notification( $senduserid, $sendemail, @$comment[ 'handle' ], qa_lang( 'qas_emails/c_commented_subject' ), qa_lang( 'qas_emails/c_commented_body' ), array(
                                '^c_handle'  => $sendhandle,
                                '^c_context' => $sendcontext,
                                '^c_content' => $sendtext,
                                '^url'       => $sendurl,
                            ) );
                        }
                    break;


                case 'qas_blog_b_queue':
                case 'qas_blog_b_requeue':
                    if ( qa_opt( 'moderate_notify_admin' ) )
                        qa_send_notification( null, qa_opt( 'feedback_email' ), null,
                            ( $event == 'qas_blog_b_requeue' ) ? qa_lang( 'qas_emails/remoderate_subject' ) : qa_lang( 'qas_emails/moderate_subject' ),
                            ( $event == 'qas_blog_b_requeue' ) ? qa_lang( 'qas_emails/remoderate_body' ) : qa_lang( 'qas_emails/moderate_body' ),
                            array(
                                '^p_handle'  => isset( $handle ) ? $handle : ( strlen( $params[ 'name' ] ) ? $params[ 'name' ] :
                                    ( strlen( @$oldquestion[ 'name' ] ) ? $oldquestion[ 'name' ] : qa_lang( 'main/anonymous' ) ) ),
                                '^p_context' => trim( @$params[ 'title' ] . "\n\n" . $params[ 'text' ] ), // don't censor for admin
                                '^url'       => qas_blog_post_path( $params[ 'postid' ], $params[ 'title' ], true ),
                                '^a_url'     => qa_path_absolute( qas_get_blog_url_sub( 'admin/^/moderate' ) ),
                            )
                        );
                    break;

                case 'qas_blog_c_queue':
                case 'qas_blog_c_requeue':
                    if ( qa_opt( 'moderate_notify_admin' ) )
                        qa_send_notification( null, qa_opt( 'feedback_email' ), null,
                            ( $event == 'qas_blog_c_requeue' ) ? qa_lang( 'qas_emails/remoderate_subject' ) : qa_lang( 'qas_emails/moderate_subject' ),
                            ( $event == 'qas_blog_c_requeue' ) ? qa_lang( 'qas_emails/remoderate_body' ) : qa_lang( 'qas_emails/moderate_body' ),
                            array(
                                '^p_handle'  => isset( $handle ) ? $handle : ( strlen( $params[ 'name' ] ) ? $params[ 'name' ] :
                                    ( strlen( @$oldcomment[ 'name' ] ) ? $oldcomment[ 'name' ] : // could also be after answer converted to comment
                                        ( strlen( @$oldanswer[ 'name' ] ) ? $oldanswer[ 'name' ] : qa_lang( 'main/anonymous' ) ) ) ),
                                '^p_context' => $params[ 'text' ], // don't censor for admin
                                '^url'       => qas_blog_post_path( $params[ 'questionid' ], $params[ 'question' ][ 'title' ], true, 'C', $params[ 'postid' ] ),
                                '^a_url'     => qa_path_absolute( qas_get_blog_url_sub( 'admin/^/moderate' ) ),
                            )
                        );
                    break;
            }
        }

    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */
<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    /**
     * Checks for a POSTed click on $post by the current user and returns true if it was permitted and processed. Pass
     * all $commentsfollows from it or its answers, and its closing $closepost (or null if
     * none). If there is an error to display, it will be passed out in $error.
     *
     * @param $post
     * @param $commentsfollows
     * @param $closepost
     * @param $error
     *
     * @return bool
     */
    function qas_blog_page_post_single_click( $post, $commentsfollows, $closepost, &$error )
    {

        $userid = qa_get_logged_in_userid();
        $handle = qa_get_logged_in_handle();
        $cookieid = qa_cookie_get();

        if ( qa_clicked( 'blog_doreopen' ) && $post[ 'reopenable' ] && qas_blog_page_b_click_check_form_code( $post, $error ) ) {
            qas_blog_post_close_clear( $post, $closepost, $userid, $handle, $cookieid );

            return true;
        }

        if ( ( qa_clicked( 'blog_dohide' ) && $post[ 'hideable' ] ) || ( qa_clicked( 'blog_doreject' ) && $post[ 'moderatable' ] ) )
            if ( qas_blog_page_b_click_check_form_code( $post, $error ) ) {
                qas_blog_post_b_set_hidden( $post, true, $userid, $handle, $cookieid, $commentsfollows, $closepost );

                return true;
            }

        if ( ( qa_clicked( 'blog_doreshow' ) && $post[ 'reshowable' ] ) || ( qa_clicked( 'blog_doapprove' ) && $post[ 'moderatable' ] ) )
            if ( qas_blog_page_b_click_check_form_code( $post, $error ) ) {
                if ( $post[ 'moderatable' ] || $post[ 'reshowimmed' ] ) {
                    $status = QA_POST_STATUS_NORMAL;

                } else {
                    $in = qas_blog_page_b_prepare_post_for_filters( $post );
                    $filtermodules = qa_load_modules_with( 'filter', 'filter_blog_post' ); // run through filters but only for queued status

                    foreach ( $filtermodules as $filtermodule ) {
                        $tempin = $in; // always pass original question in because we aren't modifying anything else
                        $filtermodule->filter_blog_post( $tempin, $temperrors, $post );
                        $in[ 'queued' ] = $tempin[ 'queued' ]; // only preserve queued status in loop
                    }

                    $status = $in[ 'queued' ] ? QA_POST_STATUS_QUEUED : QA_POST_STATUS_NORMAL;
                }

                qas_blog_post_b_set_status( $post, $status, $userid, $handle, $cookieid, $commentsfollows, $closepost );

                return true;
            }

        if ( ( qa_clicked( 'blog_dopublish' ) && $post[ 'publishable' ] ) )
            if ( qas_blog_page_b_click_check_form_code( $post, $error ) ) {

                qas_blog_db_post_set_type( $post[ 'postid' ], 'B' );
                qas_blog_post_index( $post[ 'postid' ], 'B', $post[ 'postid' ], $post[ 'parentid' ], $post[ 'title' ], $post[ 'content' ], $post[ 'format' ], @$post[ 'text' ], $post[ 'tags' ], $post[ 'categoryid' ] );
                qas_blog_update_counts_for_post( $post[ 'postid' ] );
                qas_blog_db_posts_calc_category_path( $post[ 'postid' ] );

                return true;
            }

        if ( ( qa_clicked( 'blog_dosetfeatured' ) && $post[ 'allow_featured' ] ) )
            if ( qas_blog_page_b_click_check_form_code( $post, $error ) ) {
                qas_blog_set_featured_post( $post[ 'postid' ] );

                return true;
            }

        if ( ( qa_clicked( 'blog_dounsetfeatured' ) && $post[ 'allow_featured' ] ) )
            if ( qas_blog_page_b_click_check_form_code( $post, $error ) ) {
                qas_blog_unset_featured_post( $post[ 'postid' ] );

                return true;
            }

        if ( qa_clicked( 'blog_doclaim' ) && $post[ 'claimable' ] && qas_blog_page_b_click_check_form_code( $post, $error ) ) {
            if ( qa_user_limits_remaining( QA_LIMIT_QUESTIONS ) ) { // already checked 'permit_post_q'
                qas_blog_post_set_userid( $post, $userid, $handle, $cookieid );

                return true;

            } else
                $error = qa_lang_html( 'question/ask_limit' );
        }

        //temporarily turing off the flagging feature due to few limitations . Work on this later
        if ( false && qa_clicked( 'blog_doflag' ) && $post[ 'flagbutton' ] && qas_blog_page_b_click_check_form_code( $post, $error ) ) {
            require_once QA_INCLUDE_DIR . 'app/votes.php';

            $error = qa_flag_error_html( $post, $userid, qa_request() );
            if ( !$error ) {
                if ( qa_flag_set_tohide( $post, $userid, $handle, $cookieid, $post ) )
                    qas_blog_post_b_set_hidden( $post, true, null, null, null, $commentsfollows, $closepost ); // hiding not really by this user so pass nulls
                return true;
            }
        }

        if ( qa_clicked( 'blog_dounflag' ) && $post[ 'unflaggable' ] && qas_blog_page_b_click_check_form_code( $post, $error ) ) {
            require_once QA_INCLUDE_DIR . 'app/votes.php';

            qa_flag_clear( $post, $userid, $handle, $cookieid );

            return true;
        }

        if ( qa_clicked( 'blog_doclearflags' ) && $post[ 'clearflaggable' ] && qas_blog_page_b_click_check_form_code( $post, $error ) ) {
            require_once QA_INCLUDE_DIR . 'app/votes.php';

            qa_flags_clear_all( $post, $userid, $handle, $cookieid );

            return true;
        }

        return false;
    }

    /**
     * Checks for a POSTed click on $comment by the current user and returns true if it was permitted and processed.
     * Pass in the antecedent $question and the comment's $parent post. If there is an error to display, it will be
     * passed out in $error.
     *
     * @param $comment
     * @param $post
     * @param $parent
     * @param $error
     *
     * @return bool
     */
    function qas_blog_page_post_single_click_c( $comment, $post, $parent, &$error )
    {
        $userid = qa_get_logged_in_userid();
        $handle = qa_get_logged_in_handle();
        $cookieid = qa_cookie_get();

        $prefix = 'c' . $comment[ 'postid' ] . '_';

        if ( ( qa_clicked( $prefix . 'dohide' ) && $comment[ 'hideable' ] ) || ( qa_clicked( $prefix . 'doreject' ) && $comment[ 'moderatable' ] ) )
            if ( qas_blog_page_b_click_check_form_code( $parent, $error ) ) {
                qas_blog_comment_set_hidden( $comment, true, $userid, $handle, $cookieid, $post, $parent );

                return true;
            }

        if ( ( qa_clicked( $prefix . 'doreshow' ) && $comment[ 'reshowable' ] ) || ( qa_clicked( $prefix . 'doapprove' ) && $comment[ 'moderatable' ] ) )
            if ( qas_blog_page_b_click_check_form_code( $parent, $error ) ) {
                if ( $comment[ 'moderatable' ] || $comment[ 'reshowimmed' ] ) {
                    $status = QA_POST_STATUS_NORMAL;

                } else {
                    $in = qas_blog_page_b_prepare_post_for_filters( $comment );
                    $filtermodules = qa_load_modules_with( 'filter', 'filter_blog_comment' ); // run through filters but only for queued status

                    foreach ( $filtermodules as $filtermodule ) {
                        $tempin = $in; // always pass original comment in because we aren't modifying anything else
                        $filtermodule->filter_blog_comment( $tempin, $temperrors, $post, $parent, $comment );
                        $in[ 'queued' ] = $tempin[ 'queued' ]; // only preserve queued status in loop
                    }

                    $status = $in[ 'queued' ] ? QA_POST_STATUS_QUEUED : QA_POST_STATUS_NORMAL;
                }

                qas_blog_comment_set_status( $comment, $status, $userid, $handle, $cookieid, $post, $parent );

                return true;
            }

        if ( qa_clicked( $prefix . 'dodelete' ) && $comment[ 'deleteable' ] && qas_blog_page_b_click_check_form_code( $parent, $error ) ) {
            //qas_blog_comment_delete( $comment, $post, $parent, $userid, $handle, $cookieid );
            qas_blog_post_delete_recursive( $comment[ 'postid' ] );

            return true;
        }

        if ( qa_clicked( $prefix . 'doclaim' ) && $comment[ 'claimable' ] && qas_blog_page_b_click_check_form_code( $parent, $error ) ) {
            if ( qa_user_limits_remaining( QA_LIMIT_COMMENTS ) ) {
                qas_blog_comment_set_userid( $comment, $userid, $handle, $cookieid );

                return true;

            } else
                $error = qa_lang_html( 'question/comment_limit' );
        }
        //temporarily turing off the flagging feature due to few limitations . Work on this later
        if ( false && qa_clicked( $prefix . 'doflag' ) && $comment[ 'flagbutton' ] && qas_blog_page_b_click_check_form_code( $parent, $error ) ) {
            require_once QA_INCLUDE_DIR . 'app/votes.php';

            $error = qa_flag_error_html( $comment, $userid, qa_request() );
            if ( !$error ) {
                if ( qa_flag_set_tohide( $comment, $userid, $handle, $cookieid, $post ) )
                    qa_comment_set_hidden( $comment, true, null, null, null, $post, $parent ); // hiding not really by this user so pass nulls

                return true;
            }
        }

        if ( qa_clicked( $prefix . 'dounflag' ) && $comment[ 'unflaggable' ] && qas_blog_page_b_click_check_form_code( $parent, $error ) ) {
            require_once QA_INCLUDE_DIR . 'app/votes.php';

            qa_flag_clear( $comment, $userid, $handle, $cookieid );

            return true;
        }

        if ( qa_clicked( $prefix . 'doclearflags' ) && $comment[ 'clearflaggable' ] && qas_blog_page_b_click_check_form_code( $parent, $error ) ) {
            require_once QA_INCLUDE_DIR . 'app/votes.php';

            qa_flags_clear_all( $comment, $userid, $handle, $cookieid );

            return true;
        }

        return false;
    }

    /**
     * Check the form security (anti-CSRF protection) for one of the buttons shown for post $post. Return true if the
     * security passed, otherwise return false and set an error message in $error
     *
     * @param $post
     * @param $error
     *
     * @return bool
     */
    function qas_blog_page_b_click_check_form_code( $post, &$error )
    {
        $result = qa_check_form_security_code( 'blog_buttons-' . $post[ 'postid' ], qa_post_text( 'code' ) );

        if ( !$result )
            $error = qa_lang_html( 'misc/form_security_again' );

        return $result;
    }

    /**
     * Return the array of information to be passed to filter modules for the post in $post (from the database)
     *
     * @param $post
     *
     * @return array
     */
    function qas_blog_page_b_prepare_post_for_filters( $post )
    {
        $in = array(
            'content' => $post[ 'content' ],
            'format'  => $post[ 'format' ],
            'text'    => qa_viewer_text( $post[ 'content' ], $post[ 'format' ] ),
            'notify'  => isset( $post[ 'notify' ] ),
            'email'   => qa_email_validate( $post[ 'notify' ] ) ? $post[ 'notify' ] : null,
            'queued'  => qa_user_moderation_reason( qa_user_level_for_post( $post ) ) !== false,
        );

        if ( $post[ 'basetype' ] == 'B' ) {
            $in[ 'title' ] = $post[ 'title' ];
            $in[ 'tags' ] = qa_tagstring_to_tags( $post[ 'tags' ] );
            $in[ 'categoryid' ] = $post[ 'categoryid' ];
            $in[ 'extra' ] = $post[ 'extra' ];
        }

        return $in;
    }

    /**
     * Redirects back to the question page, with the specified parameters
     *
     * @param int  $start
     * @param null $state
     * @param null $showtype
     * @param null $showid
     */
    function qas_blog_page_b_refresh( $start = 0, $state = null, $showtype = null, $showid = null )
    {
        $params = array();

        if ( $start > 0 )
            $params[ 'start' ] = $start;
        if ( isset( $state ) )
            $params[ 'state' ] = $state;

        if ( isset( $showtype ) && isset( $showid ) ) {
            $anchor = qa_anchor( $showtype, $showid );
            $params[ 'show' ] = $showid;
        } else
            $anchor = null;

        qa_redirect( qa_request(), $params, null, null, $anchor );
    }

    /**
     * Returns whether the editing operation (as specified by $permitoption or $permitoption2) on $post is permitted.
     * If not, sets the $error variable appropriately
     *
     * @param      $post
     * @param      $permitoption
     * @param      $error
     * @param null $permitoption2
     *
     * @return bool
     */
    function qas_blog_page_blog_permit_edit( $post, $permitoption, &$error, $permitoption2 = null )
    {
        // The 'login', 'confirm', 'userblock', 'ipblock' permission errors are reported to the user here
        // The other options ('approve', 'level') prevent the edit button being shown, in qas_blog_page_q_post_rules(...)

        $permiterror = qa_user_post_permit_error( $post[ 'isbyuser' ] ? null : $permitoption, $post );
        // if it's by the user, this will only check whether they are blocked

        if ( $permiterror && isset( $permitoption2 ) ) {
            $permiterror2 = qa_user_post_permit_error( $post[ 'isbyuser' ] ? null : $permitoption2, $post );

            if ( ( $permiterror == 'level' ) || ( $permiterror == 'approve' ) || ( !$permiterror2 ) ) // if it's a less strict error
                $permiterror = $permiterror2;
        }

        switch ( $permiterror ) {
            case 'login':
                $error = qa_insert_login_links( qa_lang_html( 'question/edit_must_login' ), qa_request() );
                break;

            case 'confirm':
                $error = qa_insert_login_links( qa_lang_html( 'question/edit_must_confirm' ), qa_request() );
                break;

            default:
                $error = qa_lang_html( 'users/no_permission' );
                break;

            case false:
                break;
        }

        return !$permiterror;
    }

    /**
     * Returns a $qa_content form for editing the question and sets up other parts of $qa_content accordingly
     *
     * @param $qa_content
     * @param $post
     * @param $in
     * @param $errors
     * @param $completetags
     * @param $categories
     *
     * @return array
     */
    function qas_blog_page_edit_post_form( &$qa_content, $post, $in, $errors, $completetags, $categories )
    {
        $form = array(
            'tags'    => 'method="post" action="' . qa_self_html() . '"',

            'style'   => 'tall',

            'fields'  => array(
                'title'    => array(
                    'type'  => $post[ 'editable' ] ? 'text' : 'static',
                    'label' => qa_lang_html( 'qas_blog/post_title_lable' ),
                    'tags'  => 'name="q_title"',
                    'value' => qa_html( ( $post[ 'editable' ] && isset( $in[ 'title' ] ) ) ? $in[ 'title' ] : $post[ 'title' ] ),
                    'error' => qa_html( @$errors[ 'title' ] ),
                ),

                'category' => array(
                    'label' => qa_lang_html( 'question/q_category_label' ),
                    'error' => qa_html( @$errors[ 'categoryid' ] ),
                ),

                'content'  => array(
                    'label' => qa_lang_html( 'qas_blog/post_content' ),
                    'error' => qa_html( @$errors[ 'content' ] ),
                ),

                'extra'    => array(
                    'label' => qa_html( qa_opt( 'qas_blog_extra_field_prompt' ) ),
                    'tags'  => 'name="q_extra"',
                    'value' => qa_html( isset( $in[ 'extra' ] ) ? $in[ 'extra' ] : $post[ 'extra' ] ),
                    'error' => qa_html( @$errors[ 'extra' ] ),
                ),

                'tags'     => array(
                    'error' => qa_html( @$errors[ 'tags' ] ),
                ),

            ),

            'buttons' => array(
                'save'   => array(
                    'tags'  => 'onclick="qa_show_waiting_after(this, false);"',
                    'label' => qa_lang_html( 'qas_blog/post_button' ),
                ),

                'cancel' => array(
                    'tags'  => 'name="docancel"',
                    'label' => qa_lang_html( 'main/cancel_button' ),
                ),
            ),

            'hidden'  => array(
                'blog_dosave' => '1',
                'code'        => qa_get_form_security_code( 'blog_edit-' . $post[ 'postid' ] ),
            ),
        );

        if ( $post[ 'editable' ] ) {
            $content = isset( $in[ 'content' ] ) ? $in[ 'content' ] : $post[ 'content' ];
            $format = isset( $in[ 'format' ] ) ? $in[ 'format' ] : $post[ 'format' ];

            $editorname = isset( $in[ 'editor' ] ) ? $in[ 'editor' ] : qa_opt( 'qas_blog_editor_for_ps' );
            $editor = qa_load_editor( $content, $format, $editorname );

            $form[ 'fields' ][ 'content' ] = array_merge( $form[ 'fields' ][ 'content' ],
                qa_editor_load_field( $editor, $qa_content, $content, $format, 'q_content', 12, true ) );

            if ( method_exists( $editor, 'update_script' ) )
                $form[ 'buttons' ][ 'save' ][ 'tags' ] = 'onclick="qa_show_waiting_after(this, false); ' . $editor->update_script( 'q_content' ) . '"';

            $form[ 'hidden' ][ 'q_editor' ] = qa_html( $editorname );

        } else
            unset( $form[ 'fields' ][ 'content' ] );

        if ( qas_blog_using_categories() && count( $categories ) && $post[ 'retagcatable' ] )
            qas_blog_set_up_category_field( $qa_content, $form[ 'fields' ][ 'category' ], 'q_category', $categories,
                isset( $in[ 'categoryid' ] ) ? $in[ 'categoryid' ] : $post[ 'categoryid' ],
                qa_opt( 'blog_allow_no_category' ) || !isset( $post[ 'categoryid' ] ), qa_opt( 'allow_no_sub_category' ) );
        else
            unset( $form[ 'fields' ][ 'category' ] );

        if ( !( $post[ 'editable' ] && qa_opt( 'qas_blog_extra_field_active' ) ) )
            unset( $form[ 'fields' ][ 'extra' ] );

        if ( qas_blog_using_tags() && $post[ 'retagcatable' ] )
            qa_set_up_tag_field( $qa_content, $form[ 'fields' ][ 'tags' ], 'q_tags', isset( $in[ 'tags' ] ) ? $in[ 'tags' ] : qa_tagstring_to_tags( $post[ 'tags' ] ),
                array(), $completetags, qa_opt( 'page_size_ask_tags' ) );
        else
            unset( $form[ 'fields' ][ 'tags' ] );

        if ( $post[ 'isbyuser' ] ) {
            if ( !qa_is_logged_in() )
                qa_set_up_name_field( $qa_content, $form[ 'fields' ], isset( $in[ 'name' ] ) ? $in[ 'name' ] : @$post[ 'name' ], 'q_' );

            qas_blog_set_up_notify_fields( $qa_content, $form[ 'fields' ], 'B', qa_get_logged_in_email(),
                isset( $in[ 'notify' ] ) ? $in[ 'notify' ] : !empty( $post[ 'notify' ] ),
                isset( $in[ 'email' ] ) ? $in[ 'email' ] : @$post[ 'notify' ], @$errors[ 'email' ], 'q_' );
        }

        if ( !qa_user_post_permit_error( 'qas_blog_permit_edit_silent', $post ) )
            $form[ 'fields' ][ 'silent' ] = array(
                'type'  => 'checkbox',
                'label' => qa_lang_html( 'question/save_silent_label' ),
                'tags'  => 'name="q_silent"',
                'value' => qa_html( @$in[ 'silent' ] ),
            );

        if ( qas_is_draft_enabled() && $post[ 'basetype' ] == 'D' && ( $post[ 'isbyuser' ] || !qa_user_post_permit_error( 'qas_blog_permit_view_edit_draft', $post ) ) ) {
            $checked = ( $post[ 'basetype' ] == 'D' );
            qas_blog_set_up_draft_field( $qa_content, $form[ 'fields' ], 'q_', $checked );
        }

        return $form;
    }

    /**
     * Processes a POSTed form for editing the question and returns true if successful
     *
     * @param $post
     * @param $commentsfollows
     * @param $closepost
     * @param $in
     * @param $errors
     *
     * @return bool
     */
    function qas_blog_page_p_edit_post_submit( $post, $commentsfollows, $closepost, &$in, &$errors )
    {
        $in = array();

        if ( $post[ 'editable' ] ) {
            $in[ 'title' ] = qa_post_text( 'q_title' );
            qa_get_post_content( 'q_editor', 'q_content', $in[ 'editor' ], $in[ 'content' ], $in[ 'format' ], $in[ 'text' ] );
            $in[ 'extra' ] = qa_opt( 'qas_blog_extra_field_active' ) ? qa_post_text( 'q_extra' ) : null;
        }

        if ( $post[ 'retagcatable' ] ) {
            if ( qas_blog_using_tags() )
                $in[ 'tags' ] = qa_get_tags_field_value( 'q_tags' );

            if ( qas_blog_using_categories() )
                $in[ 'categoryid' ] = qa_get_category_field_value( 'q_category' );
        }

        if ( array_key_exists( 'categoryid', $in ) ) { // need to check if we can move it to that category, and if we need moderation
            $categories = qa_db_select_with_pending( qa_db_category_nav_selectspec( $in[ 'categoryid' ], true ) );
            $categoryids = array_keys( qa_category_path( $categories, $in[ 'categoryid' ] ) );
            $userlevel = qa_user_level_for_categories( $categoryids );

        } else
            $userlevel = null;

        if ( $post[ 'isbyuser' ] ) {
            $in[ 'name' ] = qa_post_text( 'q_name' );
            $in[ 'notify' ] = qa_post_text( 'q_notify' ) !== null;
            $in[ 'email' ] = qa_post_text( 'q_email' );
        }

        if ( !qa_user_post_permit_error( 'qas_blog_permit_edit_silent', $post ) )
            $in[ 'silent' ] = qa_post_text( 'q_silent' );

        // here the $in array only contains values for parts of the form that were displayed, so those are only ones checked by filters

        $errors = array();

        if ( !qa_check_form_security_code( 'blog_edit-' . $post[ 'postid' ], qa_post_text( 'code' ) ) )
            $errors[ 'page' ] = qa_lang_html( 'misc/form_security_again' );

        else {

            $in[ 'save_draft' ] = qa_post_text( 'q_save_draft' );

            $in[ 'queued' ] = qa_opt( 'moderate_edited_again' ) && qa_user_moderation_reason( $userlevel );

            $filtermodules = qa_load_modules_with( 'filter', 'filter_blog_post' );
            foreach ( $filtermodules as $filtermodule ) {
                $oldin = $in;
                $filtermodule->filter_blog_post( $in, $errors, $post );

                if ( $post[ 'editable' ] )
                    qa_update_post_text( $in, $oldin );
            }

            if ( array_key_exists( 'categoryid', $in ) && strcmp( $in[ 'categoryid' ], $post[ 'categoryid' ] ) )
                if ( qa_user_permit_error( 'qas_blog_permit_post_b', null, $userlevel ) )
                    $errors[ 'categoryid' ] = qa_lang_html( 'qas_blog/category_post_not_allowed' );

            if ( empty( $errors ) ) {
                $userid = qa_get_logged_in_userid();
                $handle = qa_get_logged_in_handle();
                $cookieid = qa_cookie_get();

                // now we fill in the missing values in the $in array, so that we have everything we need for qa_question_set_content()
                // we do things in this way to avoid any risk of a validation failure on elements the user can't see (e.g. due to admin setting changes)

                if ( !$post[ 'editable' ] ) {
                    $in[ 'title' ] = $post[ 'title' ];
                    $in[ 'content' ] = $post[ 'content' ];
                    $in[ 'format' ] = $post[ 'format' ];
                    $in[ 'text' ] = qa_viewer_text( $in[ 'content' ], $in[ 'format' ] );
                    $in[ 'extra' ] = $post[ 'extra' ];
                }

                if ( !isset( $in[ 'tags' ] ) )
                    $in[ 'tags' ] = qa_tagstring_to_tags( $post[ 'tags' ] );

                if ( !array_key_exists( 'categoryid', $in ) )
                    $in[ 'categoryid' ] = $post[ 'categoryid' ];

                if ( !isset( $in[ 'silent' ] ) )
                    $in[ 'silent' ] = false;

                $setnotify = $post[ 'isbyuser' ] ? qa_combine_notify_email( $post[ 'userid' ], $in[ 'notify' ], $in[ 'email' ] ) : $post[ 'notify' ];

                qas_blog_b_post_set_content( $post, $in[ 'title' ], $in[ 'content' ], $in[ 'format' ], $in[ 'text' ], qa_tags_to_tagstring( $in[ 'tags' ] ),
                    $setnotify, $userid, $handle, $cookieid, $in[ 'extra' ], @$in[ 'name' ], $in[ 'queued' ], $in[ 'silent' ], $in[ 'categoryid' ], $in[ 'save_draft' ] );

                if ( qas_blog_using_categories() && strcmp( $in[ 'categoryid' ], $post[ 'categoryid' ] ) )
                    qas_blog_post_set_category( $post, $in[ 'categoryid' ], $userid, $handle, $cookieid,
                        $commentsfollows, $closepost, $in[ 'silent' ] );

                return true;
            }
        }

        return false;
    }

    /**
     * Returns a $qa_content form for closing the question and sets up other parts of $qa_content accordingly
     *
     * @param $qa_content
     * @param $post
     * @param $id
     * @param $in
     * @param $errors
     *
     * @return array
     */
    function qas_blog_page_b_close_post_form( &$qa_content, $post, $id, $in, $errors )
    {
        $form = array(
            'tags'    => 'method="post" action="' . qa_self_html() . '"',

            'id'      => $id,

            'style'   => 'tall',

            'title'   => qa_lang_html( 'question/close_form_title' ),

            'fields'  => array(
                'details' => array(
                    'tags'  => 'name="q_close_details" id="q_close_details"',
                    'label' =>
                        '<span id="close_label_other">' . qa_lang_html( 'question/close_reason_title' ) . '</span>',
                    'note'  => qa_lang_html( 'qas_blog/cant_receive_comments_further' ),
                    'value' => @$in[ 'details' ],
                    'error' => qa_html( @$errors[ 'details' ] ),
                ),
            ),

            'buttons' => array(
                'close'  => array(
                    'tags'  => 'onclick="qa_show_waiting_after(this, false);"',
                    'label' => qa_lang_html( 'question/close_form_button' ),
                ),

                'cancel' => array(
                    'tags'  => 'name="docancel"',
                    'label' => qa_lang_html( 'main/cancel_button' ),
                ),
            ),

            'hidden'  => array(
                'doclose' => '1',
                'code'    => qa_get_form_security_code( 'blog_close-' . $post[ 'postid' ] ),
            ),
        );

        qa_set_display_rules( $qa_content, array(
            'close_label_duplicate' => 'q_close_duplicate',
            'close_label_other'     => '!q_close_duplicate',
            'close_note_duplicate'  => 'q_close_duplicate',
        ) );

        $qa_content[ 'focusid' ] = 'q_close_details';

        return $form;
    }

    /**
     * Processes a POSTed form for closing the question and returns true if successful
     *
     * @param $post
     * @param $closepost
     * @param $in
     * @param $errors
     *
     * @return bool
     */
    function qas_blog_page_b_close_b_submit( $post, $closepost, &$in, &$errors )
    {
        $in = array(
            'duplicate' => qa_post_text( 'q_close_duplicate' ),
            'details'   => qa_post_text( 'q_close_details' ),
        );

        $userid = qa_get_logged_in_userid();
        $handle = qa_get_logged_in_handle();
        $cookieid = qa_cookie_get();

        if ( !qa_check_form_security_code( 'blog_close-' . $post[ 'postid' ], qa_post_text( 'code' ) ) )
            $errors[ 'details' ] = qa_lang_html( 'misc/form_security_again' );


        if ( strlen( $in[ 'details' ] ) > 0 ) {
            qas_blog_post_close_other( $post, $closepost, $in[ 'details' ], $userid, $handle, $cookieid );

            return true;

        } else
            $errors[ 'details' ] = qa_lang( 'main/field_required' );


        return false;
    }

    /**
     * Processes a request to add a comment to $parent, with antecedent $question, checking for permissions errors
     *
     * @param $post
     * @param $parent
     * @param $commentsfollows
     * @param $pagestart
     * @param $usecaptcha
     * @param $cnewin
     * @param $cnewerrors
     * @param $formtype
     * @param $formpostid
     * @param $error
     */
    function qas_blog_page_post_do_comment( $post, $parent, $commentsfollows, $pagestart, $usecaptcha, &$cnewin, &$cnewerrors, &$formtype, &$formpostid, &$error )
    {
        // The 'approve', 'login', 'confirm', 'userblock', 'ipblock' permission errors are reported to the user here
        // The other option ('level') prevents the comment button being shown, in qas_blog_page_q_post_rules(...)

        $parentid = $parent[ 'postid' ];

        switch ( qa_user_post_permit_error( 'qas_blog_permit_post_c', $parent, QA_LIMIT_COMMENTS ) ) {
            case 'login':
                $error = qa_insert_login_links( qa_lang_html( 'question/comment_must_login' ), qa_request() );
                break;

            case 'confirm':
                $error = qa_insert_login_links( qa_lang_html( 'question/comment_must_confirm' ), qa_request() );
                break;

            case 'approve':
                $error = qa_lang_html( 'question/comment_must_be_approved' );
                break;

            case 'limit':
                $error = qa_lang_html( 'question/comment_limit' );
                break;
 case 'verify':
                        $error ="Your identity must be verified before you can post a comment. Please wait if already uploaded identity proof or upload your proof <a href='".qa_path_absolute('verify-user-page')."'>here</a>";
                        break;

            default:
                $error = qa_lang_html( 'users/no_permission' );
                break;

            case false:
                if ( qa_clicked( 'c' . $parentid . '_doadd' ) ) {
                    $commentid = qas_blog_page_b_add_c_submit( $post, $parent, $commentsfollows, $usecaptcha, $cnewin[ $parentid ], $cnewerrors[ $parentid ] );

                    if ( isset( $commentid ) )
                        qas_blog_page_b_refresh( $pagestart, null, $parent[ 'basetype' ], $parentid );

                    else {
                        $formtype = 'c_add';
                        $formpostid = $parentid; // show form again
                    }

                } else {
                    $formtype = 'c_add';
                    $formpostid = $parentid; // show form first time
                }
                break;
        }
    }

    /**
     * Returns a $qa_content form for editing a comment and sets up other parts of $qa_content accordingly
     *
     * @param $qa_content
     * @param $id
     * @param $comment
     * @param $in
     * @param $errors
     *
     * @return array
     */
    function qas_blog_page_post_edit_c_form( &$qa_content, $id, $comment, $in, $errors )
    {
        $commentid = $comment[ 'postid' ];
        $prefix = 'c' . $commentid . '_';

        $content = isset( $in[ 'content' ] ) ? $in[ 'content' ] : $comment[ 'content' ];
        $format = isset( $in[ 'format' ] ) ? $in[ 'format' ] : $comment[ 'format' ];

        $editorname = isset( $in[ 'editor' ] ) ? $in[ 'editor' ] : qa_opt( 'qas_blog_editor_for_cs' );
        $editor = qa_load_editor( $content, $format, $editorname );

        $form = array(
            'tags'    => 'method="post" action="' . qa_self_html() . '"',

            'id'      => $id,

            'title'   => qa_lang_html( 'question/edit_c_title' ),

            'style'   => 'tall',

            'fields'  => array(
                'content' => array_merge(
                    qa_editor_load_field( $editor, $qa_content, $content, $format, $prefix . 'content', 4, true ),
                    array(
                        'error' => qa_html( @$errors[ 'content' ] ),
                    )
                ),
            ),

            'buttons' => array(
                'save'   => array(
                    'tags'  => 'onclick="qa_show_waiting_after(this, false); ' .
                        ( method_exists( $editor, 'update_script' ) ? $editor->update_script( $prefix . 'content' ) : '' ) . '"',
                    'label' => qa_lang_html( 'main/save_button' ),
                ),

                'cancel' => array(
                    'tags'  => 'name="docancel"',
                    'label' => qa_lang_html( 'main/cancel_button' ),
                ),
            ),

            'hidden'  => array(
                $prefix . 'editor' => qa_html( $editorname ),
                $prefix . 'dosave' => '1',
                $prefix . 'code'   => qa_get_form_security_code( 'blog_edit-' . $commentid ),
            ),
        );

        if ( $comment[ 'isbyuser' ] ) {
            if ( !qa_is_logged_in() )
                qa_set_up_name_field( $qa_content, $form[ 'fields' ], isset( $in[ 'name' ] ) ? $in[ 'name' ] : @$comment[ 'name' ], $prefix );

            qas_blog_set_up_notify_fields( $qa_content, $form[ 'fields' ], 'C', qa_get_logged_in_email(),
                isset( $in[ 'notify' ] ) ? $in[ 'notify' ] : !empty( $comment[ 'notify' ] ),
                isset( $in[ 'email' ] ) ? $in[ 'email' ] : @$comment[ 'notify' ], @$errors[ 'email' ], $prefix );
        }

        if ( !qa_user_post_permit_error( 'qas_blog_permit_edit_silent', $comment ) )
            $form[ 'fields' ][ 'silent' ] = array(
                'type'  => 'checkbox',
                'label' => qa_lang_html( 'question/save_silent_label' ),
                'tags'  => 'name="' . $prefix . 'silent"',
                'value' => qa_html( @$in[ 'silent' ] ),
            );

        return $form;
    }

    /**
     * Processes a POSTed form for editing a comment and returns true if successful
     *
     * @param $comment
     * @param $post
     * @param $parent
     * @param $in
     * @param $errors
     *
     * @return bool
     */
    function qas_page_blog_edit_c_submit( $comment, $post, $parent, &$in, &$errors )
    {
        $commentid = $comment[ 'postid' ];
        $prefix = 'c' . $commentid . '_';

        $in = array();

        if ( $comment[ 'isbyuser' ] ) {
            $in[ 'name' ] = qa_post_text( $prefix . 'name' );
            $in[ 'notify' ] = qa_post_text( $prefix . 'notify' ) !== null;
            $in[ 'email' ] = qa_post_text( $prefix . 'email' );
        }

        if ( !qa_user_post_permit_error( 'qas_blog_permit_edit_silent', $comment ) )
            $in[ 'silent' ] = qa_post_text( $prefix . 'silent' );

        qa_get_post_content( $prefix . 'editor', $prefix . 'content', $in[ 'editor' ], $in[ 'content' ], $in[ 'format' ], $in[ 'text' ] );

        // here the $in array only contains values for parts of the form that were displayed, so those are only ones checked by filters

        $errors = array();

        if ( !qa_check_form_security_code( 'blog_edit-' . $commentid, qa_post_text( $prefix . 'code' ) ) )
            $errors[ 'content' ] = qa_lang_html( 'misc/form_security_again' );

        else {
            $in[ 'queued' ] = qa_opt( 'moderate_edited_again' ) && qa_user_moderation_reason( qa_user_level_for_post( $comment ) );

            $filtermodules = qa_load_modules_with( 'filter', 'filter_blog_comment' );
            foreach ( $filtermodules as $filtermodule ) {
                $oldin = $in;
                $filtermodule->filter_blog_comment( $in, $errors, $post, $parent, $comment );
                qa_update_post_text( $in, $oldin );
            }

            if ( empty( $errors ) ) {
                $userid = qa_get_logged_in_userid();
                $handle = qa_get_logged_in_handle();
                $cookieid = qa_cookie_get();

                if ( !isset( $in[ 'silent' ] ) )
                    $in[ 'silent' ] = false;

                $setnotify = $comment[ 'isbyuser' ] ? qa_combine_notify_email( $comment[ 'userid' ], $in[ 'notify' ], $in[ 'email' ] ) : $comment[ 'notify' ];

                qas_blog_comment_set_content( $comment, $in[ 'content' ], $in[ 'format' ], $in[ 'text' ], $setnotify,
                    $userid, $handle, $cookieid, $post, $parent, @$in[ 'name' ], $in[ 'queued' ], $in[ 'silent' ] );

                return true;
            }
        }

        return false;
    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */

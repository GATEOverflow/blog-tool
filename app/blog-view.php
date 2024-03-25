<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    /**
     * Given a $post , its $childposts and its answers $achildposts from the database,
     * return a list of comments for that post
     *
     * @param $post
     * @param $childposts
     *
     * @return array
     */
    function qas_blog_page_b_load_c_follows( $post, $childposts )
    {
        $commentsfollows = array();

        foreach ( $childposts as $postid => $post )
            switch ( $post[ 'type' ] ) {
                case 'B':
                case 'C':
                case 'C_HIDDEN':
                case 'C_QUEUED':
                    $commentsfollows[ $postid ] = $post;
                    break;
            }

        return $commentsfollows;
    }

    /**
     *  Returns elements that can be added to $post which describe which operations the current user may perform on
     *  that
     * post. This function is a key part of Q2A's logic and is ripe for overriding by plugins. Pass $post's $parentpost
     * if there is one, or null otherwise. Pass an array which contains $post's siblings (i.e. other posts with the
     * same type and parent) in $siblingposts and $post's children in $childposts. Both of these latter arrays can
     * contain additional posts retrieved from the database, and these will be ignored.
     *
     * @param      $post
     * @param null $parentpost
     * @param null $siblingposts
     * @param null $childposts
     *
     * @return mixed
     */
    function qas_blog_page_b_post_rules( $post, $parentpost = null, $siblingposts = null, $childposts = null )
    {
        $userid = qa_get_logged_in_userid();
        $cookieid = qa_cookie_get();
        $userlevel = qa_user_level_for_post( $post );

        $userfields = qa_get_logged_in_user_cache();
        if (!isset($userfields)) {
            $userfields = array(
                'userid' => null,
                'level' => null,
                'flags' => null,
            );
        }

        $rules[ 'isbyuser' ] = qa_post_is_by_user( $post, $userid, $cookieid );
        $rules[ 'queued' ] = ( substr( $post[ 'type' ], 1 ) == '_QUEUED' );
        $rules[ 'closed' ] = ( $post[ 'basetype' ] == 'B' ) && ( isset( $post[ 'closedbyid' ] ) || ( isset( $post[ 'selchildid' ] ) && qa_opt( 'do_close_on_select' ) ) );

        //	Cache some responses to the user permission checks

        $permiterror_post_q = qa_user_permit_error( 'qas_blog_permit_post_b', null, $userlevel, true, $userfields);  // don't check limits here, so we can show error message
        $permiterror_post_a = false;
        $permiterror_post_c = qa_user_permit_error( 'qas_blog_permit_post_c', null, $userlevel, true, $userfields); 

        $edit_option = ( $post[ 'basetype' ] == 'B' ) ? 'qas_blog_permit_edit_p' : 'qas_blog_permit_edit_c';

        $permiterror_edit = qa_user_permit_error( $edit_option, null, $userlevel, true, $userfields); 
        $permiterror_retagcat = qa_user_permit_error( 'qas_blog_permit_retag_cat', null, $userlevel, true, $userfields); 
        $permiterror_flag = qa_user_permit_error( 'qas_blog_permit_flag', null, $userlevel, true, $userfields); 
        $permiterror_hide_show = qa_user_permit_error( $rules[ 'isbyuser' ] ? null : 'qas_blog_permit_hide_show', null, $userlevel, true, $userfields); 
        $permiterror_hide_show_self = $rules['isbyuser'] ? qa_user_permit_error(null, null, $userlevel, true, $userfields) : $permiterror_hide_show;

        $permiterror_close_open = qa_user_permit_error( $rules[ 'isbyuser' ] ? null : 'qas_blog_permit_close_p', null, $userlevel, true, $userfields); 
        $permiterror_draft_edit_view = qa_user_permit_error( $rules[ 'isbyuser' ] ? null : 'qas_blog_permit_view_edit_draft', null, $userlevel, true, $userfields); 
        $permiterror_moderate = qa_user_permit_error( 'qas_blog_permit_moderate', null, $userlevel, true, $userfields); 
        $permiterror_set_featured = qa_user_permit_error( 'qas_blog_permit_set_featred', null, $userlevel, true, $userfields); 

        //	General permissions

        $rules[ 'authorlast' ] = ( ( !isset( $post[ 'lastuserid' ] ) ) || ( $post[ 'lastuserid' ] === $post[ 'userid' ] ) );
        // $rules[ 'viewable' ] = $post[ 'hidden' ] ? ( !$permiterror_hide_show ) : ( $rules[ 'queued' ] ? ( $rules[ 'isbyuser' ] || !$permiterror_moderate ) : true );
        $rules['viewable'] = $post['hidden'] ? !$permiterror_hide_show_self : ($rules['queued'] ? ($rules['isbyuser'] || !$permiterror_moderate) : true);

        //	Answer, comment and edit might show the button even if the user still needs to do something (e.g. log in)

        $rules[ 'answerbutton' ] = false;

        $rules[ 'commentbutton' ] = ( ( $post[ 'type' ] == 'B' ) ) &&
            ( $permiterror_post_c != 'level' ) && qa_opt( ( $post[ 'type' ] == 'B' ) ? 'qas_blog_comment_on_ps' : '' );
        $rules[ 'commentable' ] = $rules[ 'commentbutton' ] && !$permiterror_post_c;

        $button_errors = array('login', 'level', 'approve');

        $rules['editbutton'] = !$post['hidden'] && !$rules['closed']
            && ($rules['isbyuser'] || (!in_array($permiterror_edit, $button_errors) && (!$rules['queued'])));

        $rules[ 'editable' ] = $rules[ 'editbutton' ] && ( $rules[ 'isbyuser' ] || !$permiterror_edit );

        $rules[ 'featuredbutton' ] = ( qas_is_featured_posts_enabled() && ( $post[ 'basetype' ] == 'B' ) && !$post[ 'hidden' ] ) && ( !$rules[ 'closed' ] ) &&
            ( !in_array($permiterror_set_featured, $button_errors) && ( !$rules[ 'queued' ] ) );

        $rules[ 'allow_featured' ] = $rules[ 'featuredbutton' ];

        if ( $rules[ 'featuredbutton' ] )
            $is_featured_post = qas_blog_is_featured_post( $post[ 'postid' ] );
        else
            $is_featured_post = false;

        $rules[ 'allow_set_featured' ] = $rules[ 'featuredbutton' ] && !$is_featured_post;
        $rules[ 'allow_unset_featured' ] = $rules[ 'featuredbutton' ] && $is_featured_post;

        $rules[ 'publishbutton' ] = ( !$post[ 'hidden' ] && $post[ 'basetype' ] == 'D' ) &&
            ( $rules[ 'isbyuser' ] || ( !in_array($permiterror_edit, $button_errors) && ( !$rules[ 'queued' ] ) ) );
        $rules[ 'publishable' ] = $rules[ 'publishbutton' ] && ( $rules[ 'isbyuser' ] || !$permiterror_draft_edit_view );

        $rules[ 'retagcatbutton' ] = ( $post[ 'basetype' ] == 'B' ) && ( qas_blog_using_tags() || qas_blog_using_categories() ) &&
            ( !$post[ 'hidden' ] ) && ( $rules[ 'isbyuser' ] || ( !in_array($permiterror_retagcat, $button_errors) ) );
        $rules[ 'retagcatable' ] = $rules[ 'retagcatbutton' ] && ( $rules[ 'isbyuser' ] || !$permiterror_retagcat );

        if ( $rules[ 'editbutton' ] && $rules[ 'retagcatbutton' ] ) { // only show one button since they lead to the same form
            if ( $rules[ 'retagcatable' ] && !$rules[ 'editable' ] )
                $rules[ 'editbutton' ] = false; // if we can do this without getting an error, show that as the title
            else
                $rules[ 'retagcatbutton' ] = false;
        }

        $rules[ 'aselectable' ] = false;

        //temporarily turing off the flagging feature due to few limitations . Work on this later
        $rules[ 'flagbutton' ] = false && qa_opt( 'qas_blog_flagging_of_posts' ) && ( !$rules[ 'isbyuser' ] ) && ( !$post[ 'hidden' ] ) && ( !$rules[ 'queued' ] ) &&
            ( !@$post[ 'userflag' ] ) && !in_array($permiterror_flag, $button_errors);

        $rules[ 'flagtohide' ] = false && $rules[ 'flagbutton' ] && ( !$permiterror_flag ) && ( ( $post[ 'flagcount' ] + 1 ) >= qa_opt( 'flagging_hide_after' ) );
        $rules[ 'unflaggable' ] = false && @$post[ 'userflag' ] && ( !$post[ 'hidden' ] );
        $rules[ 'clearflaggable' ] = false && ( $post[ 'flagcount' ] >= ( @$post[ 'userflag' ] ? 2 : 1 ) ) && !qa_user_permit_error( 'qas_blog_permit_hide_show', null, $userlevel, true, $userfields); 

        //	Other actions only show the button if it's immediately possible

        $notclosedbyother = !( $rules[ 'closed' ] && isset( $post[ 'closedbyid' ] ) && !$rules[ 'authorlast' ] );
        $nothiddenbyother = !( $post[ 'hidden' ] && !$rules[ 'authorlast' ] );

        $rules[ 'closeable' ] = qa_opt( 'qas_blog_allow_close_ps' ) && ( $post[ 'type' ] == 'B' ) && ( !$rules[ 'closed' ] ) && !$permiterror_close_open;
        $rules[ 'reopenable' ] = $rules[ 'closed' ] && isset( $post[ 'closedbyid' ] ) && ( !$permiterror_close_open ) && ( !$post[ 'hidden' ] ) &&
            ( $notclosedbyother || !qa_user_permit_error( 'qas_blog_permit_close_p', null, $userlevel, true, $userfields));
        // cannot reopen a question if it's been hidden, or if it was closed by someone else and you don't have global closing permissions
        $rules[ 'moderatable' ] = $rules[ 'queued' ] && !$permiterror_moderate;
        // cannot hide a question if it was closed by someone else and you don't have global hiding permissions
        $rules['hideable'] = !$post['hidden'] && ($rules['isbyuser'] || !$rules['queued']) && !$permiterror_hide_show_self
            && ($notclosedbyother || !$permiterror_hide_show);
        // means post can be reshown immediately without checking whether it needs moderation
        $rules['reshowimmed'] = $post['hidden'] && !$permiterror_hide_show;
        // cannot reshow a question if it was hidden by someone else, or if it has flags - unless you have global hide/show permissions
        $rules['reshowable'] = $post['hidden'] && (!$permiterror_hide_show_self) &&
            ($rules['reshowimmed'] || ($nothiddenbyother && !$post['flagcount']));

        // cannot reshow a post if it was hidden by someone else, or if it has flags - unless you have global hide/show permissions
        $rules[ 'deleteable' ] = $post[ 'hidden' ]
            ? !qa_user_permit_error( 'qas_blog_permit_delete_hidden', null, $userlevel, true, $userfields)
            : !qa_user_permit_error( 'qas_blog_permit_delete', null, $userlevel, true, $userfields); 

        $rules[ 'claimable' ] = ( !isset( $post[ 'userid' ] ) ) && isset( $userid ) && strlen( @$post[ 'cookieid' ] ) && ( strcmp( @$post[ 'cookieid' ], $cookieid ) == 0 ) &&
            !( ( $post[ 'basetype' ] == 'B' ) ? $permiterror_post_q : $permiterror_post_c );
        $rules[ 'followable' ] = false;

        //	Now make any changes based on the child posts

        if ( $rules[ 'closed' ] ) {
            $rules[ 'commentable' ] = false;
            $rules[ 'commentbutton' ] = false;
        }

        //	Return the resulting rules
        return $rules;
    }

    /**
     * Return the $qa_content['q_view'] element for $post as viewed by the current user. If the post
     * is closed, pass the post used to close this post in $closepost, otherwise null. $usershtml should be an array
     * which maps userids to HTML user representations, including the question's author and (if present) last editor.
     * If a form has been explicitly requested for the page, set $formrequested to true - this will hide the buttons.
     *
     * @param $post
     * @param $closepost
     * @param $usershtml
     * @param $formrequested
     *
     * @return array
     */
    function qas_blog_page_blog_view( $post, $closepost, $usershtml, $formrequested )
    {
        $postid = $post[ 'postid' ];
        $userid = qa_get_logged_in_userid();
        $cookieid = qa_cookie_get();

        $htmloptions = qas_blog_post_html_options( $post, null, true );

        $categorypathprefix = qas_get_blog_url_sub( qas_blog_url_plural_structure( '/' ) );
        if ( isset( $categorypathprefix ) ) {
            $htmloptions[ 'categorypathprefix' ] = $categorypathprefix;
        }

        $htmloptions[ 'answersview' ] = false; // answer count is displayed separately so don't show it here
        $htmloptions[ 'avatarsize' ] = qa_opt( 'avatar_q_page_q_size' );
        $htmloptions[ 'q_request' ] = qas_blog_request( $post[ 'postid' ], $post[ 'title' ] );
        $q_view = qas_blog_post_html_fields( $post, $userid, $cookieid, $usershtml, null, $htmloptions );


        $q_view[ 'main_form_tags' ] = 'method="post" action="' . qa_self_html() . '"';
        $q_view[ 'voting_form_hidden' ] = array( 'code' => qa_get_form_security_code( 'blog_vote' ) );
        $q_view[ 'buttons_form_hidden' ] = array( 'code' => qa_get_form_security_code( 'blog_buttons-' . $postid ), 'qa_click' => '' );


        //	Buttons for operating on the question

        if ( !$formrequested ) { // don't show if another form is currently being shown on page
            $clicksuffix = ' onclick="qa_show_waiting_after(this, false);"'; // add to operations that write to database
            $buttons = array();

            if ( $post[ 'editbutton' ] )
                $buttons[ 'edit' ] = array(
                    'tags'  => 'name="blog_doedit"',
                    'label' => qa_lang_html( 'question/edit_button' ),
                    'popup' => qa_lang_html( 'qas_blog/edit_post_popup' ),
                );

            $hascategories = qas_blog_using_categories();

            if ( $post[ 'retagcatbutton' ] )
                $buttons[ 'retagcat' ] = array(
                    'tags'  => 'name="blog_doedit"',
                    'label' => qa_lang_html( $hascategories ? 'question/recat_button' : 'question/retag_button' ),
                    'popup' => qa_lang_html( $hascategories
                        ? ( qas_blog_using_tags() ? 'qas_blog/retag_cat_popup' : 'qas_blog/recat_popup' )
                        : 'qas_blog/retag_popup'
                    ),
                );

            //temporarily turing off the flagging feature due to few limitations . Work on this later
            if ( false && $post[ 'flagbutton' ] )
                $buttons[ 'flag' ] = array(
                    'tags'  => 'name="blog_doflag"' . $clicksuffix,
                    'label' => qa_lang_html( $post[ 'flagtohide' ] ? 'question/flag_hide_button' : 'question/flag_button' ),
                    'popup' => qa_lang_html( 'question/flag_q_popup' ),
                );

            if ( false && $post[ 'unflaggable' ] )
                $buttons[ 'unflag' ] = array(
                    'tags'  => 'name="blog_dounflag"' . $clicksuffix,
                    'label' => qa_lang_html( 'question/unflag_button' ),
                    'popup' => qa_lang_html( 'question/unflag_popup' ),
                );

            if ( false && $post[ 'clearflaggable' ] )
                $buttons[ 'clearflags' ] = array(
                    'tags'  => 'name="blog_doclearflags"' . $clicksuffix,
                    'label' => qa_lang_html( 'question/clear_flags_button' ),
                    'popup' => qa_lang_html( 'question/clear_flags_popup' ),
                );

            if ( $post[ 'closeable' ] )
                $buttons[ 'close' ] = array(
                    'tags'  => 'name="blog_doclose"',
                    'label' => qa_lang_html( 'question/close_button' ),
                    'popup' => qa_lang_html( 'qas_blog/close_post_popup' ),
                );

            if ( $post[ 'reopenable' ] )
                $buttons[ 'reopen' ] = array(
                    'tags'  => 'name="blog_doreopen"' . $clicksuffix,
                    'label' => qa_lang_html( 'question/reopen_button' ),
                    'popup' => qa_lang_html( 'qas_blog/reopen_post_popup' ),
                );

            if ( $post[ 'moderatable' ] ) {
                $buttons[ 'approve' ] = array(
                    'tags'  => 'name="blog_doapprove"' . $clicksuffix,
                    'label' => qa_lang_html( 'question/approve_button' ),
                    'popup' => qa_lang_html( 'qas_blog/approve_post_popup' ),
                );

                $buttons[ 'reject' ] = array(
                    'tags'  => 'name="blog_doreject"' . $clicksuffix,
                    'label' => qa_lang_html( 'question/reject_button' ),
                    'popup' => qa_lang_html( 'qas_blog/reject_post_popup' ),
                );
            }

            if ( $post[ 'hideable' ] )
                $buttons[ 'hide' ] = array(
                    'tags'  => 'name="blog_dohide"' . $clicksuffix,
                    'label' => qa_lang_html( 'question/hide_button' ),
                    'popup' => qa_lang_html( 'qas_blog/hide_post_popup' ),
                );

            if ( $post[ 'reshowable' ] )
                $buttons[ 'reshow' ] = array(
                    'tags'  => 'name="blog_doreshow"' . $clicksuffix,
                    'label' => qa_lang_html( 'question/reshow_button' ),
                    'popup' => qa_lang_html( 'qas_blog/reshow_post_popup' ),
                );

            if ( $post[ 'deleteable' ] ) {
                $delete_clicksuffix = ' onclick="qas_blog_ask_user_confirmation(event) && qa_show_waiting_after(this, false);"'; // add to operations that write to database

                $buttons[ 'delete' ] = array(
                    'tags'  => 'name="blog_dodelete"' . ( empty( $post[ 'hidden' ] ) ? $delete_clicksuffix : $clicksuffix ),
                    'label' => qa_lang_html( 'question/delete_button' ),
                    'popup' => qa_lang_html( 'qas_blog/delete_post_popup' ),
                );
            }

            if ( $post[ 'publishable' ] )
                $buttons[ 'publish' ] = array(
                    'tags'  => 'name="blog_dopublish"' . $clicksuffix,
                    'label' => qa_lang_html( 'qas_blog/publish_button' ),
                    'popup' => qa_lang_html( 'qas_blog/publish_post_popup' ),
                );

            if ( $post[ 'allow_set_featured' ] )
                $buttons[ 'featured' ] = array(
                    'tags'  => 'name="blog_dosetfeatured"' . $clicksuffix,
                    'label' => qa_lang_html( 'qas_blog/featured_button' ),
                    'popup' => qa_lang_html( 'qas_blog/featured_post_popup' ),
                );

            if ( $post[ 'allow_unset_featured' ] )
                $buttons[ 'unfeatured' ] = array(
                    'tags'  => 'name="blog_dounsetfeatured"' . $clicksuffix,
                    'label' => qa_lang_html( 'qas_blog/unfeatured_button' ),
                    'popup' => qa_lang_html( 'qas_blog/unfeatured_post_popup' ),
                );

            if ( $post[ 'claimable' ] )
                $buttons[ 'claim' ] = array(
                    'tags'  => 'name="blog_doclaim"' . $clicksuffix,
                    'label' => qa_lang_html( 'question/claim_button' ),
                    'popup' => qa_lang_html( 'qas_blog/claim_post_popup' ),
                );

            if ( $post[ 'commentbutton' ] )
                $buttons[ 'comment' ] = array(
                    'tags'  => 'name="blog_docomment" onclick="return qa_toggle_element(\'c' . $postid . '\')"',
                    'label' => qa_lang_html( 'question/comment_button' ),
                    'popup' => qa_lang_html( 'qas_blog/comment_post_popup' ),
                );

            $q_view[ 'form' ] = array(
                'style'   => 'light',
                'buttons' => $buttons,
            );
        }

        //	Information about the question that this question is a duplicate of (if appropriate)

        if ( isset( $closepost ) ) {

            if ( $closepost[ 'basetype' ] == 'B' ) {
                $q_view[ 'closed' ] = array(
                    'state'   => qa_lang_html( 'main/closed' ),
                    'label'   => qa_lang_html( 'question/closed_as_duplicate' ),
                    'content' => qa_html( qa_block_words_replace( $closepost[ 'title' ], qa_get_block_words_preg() ) ),
                    'url'     => qa_q_path_html( $closepost[ 'postid' ], $closepost[ 'title' ] ),
                );

            } elseif ( $closepost[ 'type' ] == 'NOTE' ) {
                $viewer = qa_load_viewer( $closepost[ 'content' ], $closepost[ 'format' ] );

                $q_view[ 'closed' ] = array(
                    'state'   => qa_lang_html( 'main/closed' ),
                    'label'   => qa_lang_html( 'question/closed_with_note' ),
                    'content' => $viewer->get_html( $closepost[ 'content' ], $closepost[ 'format' ], array(
                        'blockwordspreg' => qa_get_block_words_preg(),
                    ) ),
                );
            }
        }


        //	Extra value display

        if ( strlen( @$post[ 'extra' ] ) && qa_opt( 'qas_blog_extra_field_active' ) && qa_opt( 'qas_blog_extra_field_display' ) )
            $q_view[ 'extra' ] = array(
                'label'   => qa_html( qa_opt( 'qas_blog_extra_field_label' ) ),
                'content' => qa_html( qa_block_words_replace( $post[ 'extra' ], qa_get_block_words_preg() ) ),
            );


        return $q_view;
    }

    /**
     * Returns an element to add to the appropriate $qa_content[...]['c_list']['cs'] array for $comment as viewed by the
     * current user. Pass the comment's $parent post and antecedent $post . $usershtml should be an array which maps
     * userids to HTML user representations, including the comments's author and (if present) last editor. If a form has
     * been explicitly requested for the page, set $formrequested to true - this will hide the buttons.
     *
     * @param $post
     * @param $parent
     * @param $comment
     * @param $usershtml
     * @param $formrequested
     *
     * @return array
     */
    function qas_blog_page_b_comment_view( $post, $parent, $comment, $usershtml, $formrequested )
    {
        $commentid = $comment[ 'postid' ];
        $postid = ( $parent[ 'basetype' ] == 'B' ) ? $parent[ 'postid' ] : $parent[ 'parentid' ];
        $userid = qa_get_logged_in_userid();
        $cookieid = qa_cookie_get();

        $htmloptions = qas_blog_post_html_options( $comment, null, true );
        $htmloptions[ 'avatarsize' ] = qa_opt( 'avatar_q_page_c_size' );
        $htmloptions[ 'q_request' ] = qa_q_request( $post[ 'postid' ], $post[ 'title' ] );
        $c_view = qas_blog_post_html_fields( $comment, $userid, $cookieid, $usershtml, null, $htmloptions );

        if ( $comment[ 'queued' ] )
            $c_view[ 'error' ] = $comment[ 'isbyuser' ] ? qa_lang_html( 'question/c_your_waiting_approval' ) : qa_lang_html( 'question/c_waiting_your_approval' );

        $c_view['main_form_tags'] = 'method="post" action="' . qa_self_html() . '"';
        $c_view['buttons_form_hidden'] = array('code' => qa_get_form_security_code('blog_buttons-' . $parent['postid']), 'qa_click' => '');

        //	Buttons for operating on this comment

        if ( !$formrequested ) { // don't show if another form is currently being shown on page
            $prefix = 'c' . qa_html( $commentid ) . '_';
            $clicksuffix = ' onclick="return qas_blog_comment_click(' . qa_js( $commentid ) . ', ' . qa_js( $postid ) . ', ' . qa_js( $parent[ 'postid' ] ) . ', this);"';

            $buttons = array();

            if ( $comment[ 'editbutton' ] )
                $buttons[ 'edit' ] = array(
                    'tags'  => 'name="' . $prefix . 'doedit"',
                    'label' => qa_lang_html( 'question/edit_button' ),
                    'popup' => qa_lang_html( 'question/edit_c_popup' ),
                );

            //temporarily turing off the flagging feature due to few limitations . Work on this later
            if ( false && $comment[ 'flagbutton' ] )
                $buttons[ 'flag' ] = array(
                    'tags'  => 'name="' . $prefix . 'doflag"' . $clicksuffix,
                    'label' => qa_lang_html( $comment[ 'flagtohide' ] ? 'question/flag_hide_button' : 'question/flag_button' ),
                    'popup' => qa_lang_html( 'question/flag_c_popup' ),
                );

            if ( false && $comment[ 'unflaggable' ] )
                $buttons[ 'unflag' ] = array(
                    'tags'  => 'name="' . $prefix . 'dounflag"' . $clicksuffix,
                    'label' => qa_lang_html( 'question/unflag_button' ),
                    'popup' => qa_lang_html( 'question/unflag_popup' ),
                );

            if ( false && $comment[ 'clearflaggable' ] )
                $buttons[ 'clearflags' ] = array(
                    'tags'  => 'name="' . $prefix . 'doclearflags"' . $clicksuffix,
                    'label' => qa_lang_html( 'question/clear_flags_button' ),
                    'popup' => qa_lang_html( 'question/clear_flags_popup' ),
                );

            if ( $comment[ 'moderatable' ] ) {
                $buttons[ 'approve' ] = array(
                    'tags'  => 'name="' . $prefix . 'doapprove"' . $clicksuffix,
                    'label' => qa_lang_html( 'question/approve_button' ),
                    'popup' => qa_lang_html( 'question/approve_c_popup' ),
                );

                $buttons[ 'reject' ] = array(
                    'tags'  => 'name="' . $prefix . 'doreject"' . $clicksuffix,
                    'label' => qa_lang_html( 'question/reject_button' ),
                    'popup' => qa_lang_html( 'question/reject_c_popup' ),
                );
            }

            if ( $comment[ 'hideable' ] )
                $buttons[ 'hide' ] = array(
                    'tags'  => 'name="' . $prefix . 'dohide"' . $clicksuffix,
                    'label' => qa_lang_html( 'question/hide_button' ),
                    'popup' => qa_lang_html( 'question/hide_c_popup' ),
                );

            if ( $comment[ 'reshowable' ] )
                $buttons[ 'reshow' ] = array(
                    'tags'  => 'name="' . $prefix . 'doreshow"' . $clicksuffix,
                    'label' => qa_lang_html( 'question/reshow_button' ),
                    'popup' => qa_lang_html( 'question/reshow_c_popup' ),
                );

            if ( $comment[ 'deleteable' ] ) {
                $delete_clicksuffix = ' onclick="qas_blog_ask_user_confirmation(event) && qa_comment_click(' . qa_js( $commentid ) . ', ' . qa_js( $postid ) . ', ' . qa_js( $parent[ 'postid' ] ) . ', this);"'; // add to operations that write to database

                $buttons[ 'delete' ] = array(
                    'tags'  => 'name="' . $prefix . 'dodelete"' . ( $post[ 'hidden' ] ? $delete_clicksuffix : $clicksuffix ),
                    'label' => qa_lang_html( 'question/delete_button' ),
                    'popup' => qa_lang_html( 'question/delete_c_popup' ),
                );
            }

            if ( $comment[ 'claimable' ] )
                $buttons[ 'claim' ] = array(
                    'tags'  => 'name="' . $prefix . 'doclaim"' . $clicksuffix,
                    'label' => qa_lang_html( 'question/claim_button' ),
                    'popup' => qa_lang_html( 'question/claim_c_popup' ),
                );

            if ( $parent[ 'commentbutton' ] && qa_opt( 'qas_blog_show_c_reply_buttons' ) && ( $comment[ 'type' ] == 'C' ) )
                $buttons[ 'comment' ] = array(
                    'tags'  => 'name="' . $prefix . 'docomment"
                                onclick="return qas_open_comment_form(\'c' . qa_html( $parent[ 'postid' ] ) . '\' , \'' . qa_js( $commentid ) . '\')"',
                    'label' => qa_lang_html( 'question/reply_button' ),
                    'popup' => qa_lang_html( 'question/reply_c_popup' ),
                );

            $c_view[ 'form' ] = array(
                'style'   => 'light',
                'buttons' => $buttons,
            );
        }

        return $c_view;
    }

    /**
     * Return an array for $qa_content[...]['c_list'] to display all of the comments and follow-on questions in
     * $commentsfollows which belong to post $parent with antecedent $post , as viewed by the current user. If
     * $alwaysfull then all comments will be included, otherwise the list may be shortened with a 'show previous x
     * comments' link. $usershtml should be an array which maps userids to HTML user representations, including all
     * comments' and follow on questions' authors and (if present) last editors. If a form has been explicitly requested
     * for the page, set $formrequested to true and pass the postid of the post for the form in $formpostid - this will
     * hide the buttons and remove the $formpostid comment from the list.
     *
     * @param $post
     * @param $parent
     * @param $commentsfollows
     * @param $alwaysfull
     * @param $usershtml
     * @param $formrequested
     * @param $formpostid
     *
     * @return array
     */
    function qas_blog_page_b_comment_follow_list( $post, $parent, $commentsfollows, $alwaysfull, $usershtml, $formrequested, $formpostid )
    {
        $parentid = $parent[ 'postid' ];
        $userid = qa_get_logged_in_userid();
        $cookieid = qa_cookie_get();

        $commentlist = array(
            'tags' => 'id="c' . qa_html( $parentid ) . '_list"',
            'cs'   => array(),
        );

        $showcomments = array();

        foreach ( $commentsfollows as $commentfollowid => $commentfollow ) {
            if ( ( $commentfollow[ 'parentid' ] == $parentid ) && $commentfollow[ 'viewable' ] && ( $commentfollowid != $formpostid ) ) {
                $showcomments[ $commentfollowid ] = $commentfollow;
            }
        }

        $commentlist['comment_count'] = count( $showcomments ) ; //save the actual comment count for further use

        // Below was the original comment count ,
        // It is modified to work with the nested comments
        //$countshowcomments = count( $showcomments );
        $countshowcomments = 0 ;

        foreach($showcomments as $showcomment){
            if(empty($showcomment['reply_to']))
                $countshowcomments++ ; //Lets consider only the primary comments for pagination
        }

        if ( ( !$alwaysfull ) && ( $countshowcomments > qa_opt( 'qas_blog_show_fewer_cs_from' ) ) )
            $skipfirst = $countshowcomments - qa_opt( 'qas_blog_show_fewer_cs_count' );
        else
            $skipfirst = 0;

        if ( $skipfirst == $countshowcomments ) { // showing none
            if ( $skipfirst == 1 )
                $expandtitle = qa_lang_html( 'question/show_1_comment' );
            else
                $expandtitle = qa_lang_html_sub( 'question/show_x_comments', $skipfirst );

        } else {
            if ( $skipfirst == 1 )
                $expandtitle = qa_lang_html( 'question/show_1_previous_comment' );
            else
                $expandtitle = qa_lang_html_sub( 'question/show_x_previous_comments', $skipfirst );
        }

        if ( $skipfirst > 0 ) {
            $commentlist[ 'cs' ][ $parentid ] = array(
                'url'         => qa_html( '?state=showcomments-' . $parentid . '&show=' . $parentid . '#' . urlencode( qa_anchor( $parent[ 'basetype' ], $parentid ) ) ),

                'expand_tags' => 'onclick="return qas_blog_show_comments(' . qa_js( $post[ 'postid' ] ) . ', ' . qa_js( $parentid ) . ', this);"',

                'title'       => $expandtitle,
            );
        }

        foreach ( $showcomments as $commentfollowid => $commentfollow ) {

            if ( !empty($commentfollow['reply_to']) && $skipfirst > 0) {
                continue; // Don't consider it as a comment count
            }
            elseif ( $skipfirst > 0 ) {
                $skipfirst--;
            }
            elseif ( $commentfollow[ 'basetype' ] == 'C' ) {
                $commentlist[ 'cs' ][ $commentfollowid ] = qas_blog_page_b_comment_view( $post, $parent, $commentfollow, $usershtml, $formrequested );
            }
            elseif ( $commentfollow[ 'basetype' ] == 'B' ) {
                $htmloptions = qas_blog_post_html_options( $commentfollow );
                $htmloptions[ 'avatarsize' ] = qa_opt( 'avatar_q_page_c_size' );

                $commentlist[ 'cs' ][ $commentfollowid ] = qas_blog_post_html_fields( $commentfollow, $userid, $cookieid, $usershtml, null, $htmloptions );
            }
        }

        if ( !count( $commentlist[ 'cs' ] ) )
            $commentlist[ 'hidden' ] = true;

        return $commentlist;
    }

    /**
     * Returns a $qa_content form for adding a comment to post $parent which is part of $post . Pass an HTML element id
     * to use for the form in $formid and the result of qa_user_captcha_reason() in $captchareason. Pass previous inputs
     * from a submitted version of this form in the array $in and resulting errors in $errors. If $loadfocusnow is true,
     * the form will be loaded and focused immediately.
     *
     * @param $qa_content
     * @param $post
     * @param $parent
     * @param $formid
     * @param $captchareason
     * @param $in
     * @param $errors
     * @param $loadfocusnow
     *
     * @return array
     */
    function qas_blog_page_b_add_c_form( &$qa_content, $post, $parent, $formid, $captchareason, $in, $errors, $loadfocusnow )
    {
        // The 'approve', 'login', 'confirm', 'userblock', 'ipblock' permission errors are reported to the user here
        // The other option ('level') prevents the comment button being shown, in qas_blog_page_q_post_rules(...)

        switch ( qa_user_post_permit_error( 'qas_blog_permit_post_c', $parent, QA_LIMIT_COMMENTS ) ) {
            case 'login':
                $form = array(
                    'title' => qa_insert_login_links( qa_lang_html( 'question/comment_must_login' ), qa_request() ),
                );
                break;

            case 'confirm':
                $form = array(
                    'title' => qa_insert_login_links( qa_lang_html( 'question/comment_must_confirm' ), qa_request() ),
                );
                break;

            case 'approve':
                $form = array(
                    'title' => qa_lang_html( 'question/comment_must_be_approved' ),
                );
                break;

            case 'limit':
                $form = array(
                    'title' => qa_lang_html( 'question/comment_limit' ),
                );
                break;
//arjun
/* case 'verify':
                        $form = array(
                                'title' => "Your identity must be verified before you can post a comment. Please wait if already uploaded identity proof or upload your proof <a href='".qa_path_absolute('verify-user-page')."'>here</a>",
                        );
		break;*/

            default:
                $form = array(
                    'title' => qa_lang_html( 'users/no_permission' ),
                );
                break;

            case false:
                $prefix = 'c' . $parent[ 'postid' ] . '_';

                $editorname = isset( $in[ 'editor' ] ) ? $in[ 'editor' ] : qa_opt( 'qas_blog_editor_for_cs' );
                $editor = qa_load_editor( @$in[ 'content' ], @$in[ 'format' ], $editorname );

                if ( method_exists( $editor, 'update_script' ) )
                    $updatescript = $editor->update_script( $prefix . 'content' );
                else
                    $updatescript = '';

                $custom = qa_opt( 'qas_blog_show_custom_comment' ) ? trim( qa_opt( 'qas_blog_custom_comment' ) ) : '';

                $form = array(
                    'tags'    => 'method="post" action="' . qa_self_html() . '" name="c_form_' . qa_html( $parent[ 'postid' ] ) . '"',

                    'title'   => qa_lang_html( 'qas_blog/your_comment_on_post' ),

                    'fields'  => array(
                        'custom'  => array(
                            'type' => 'custom',
                            'note' => $custom,
                        ),

                        'content' => array_merge(
                            qa_editor_load_field( $editor, $qa_content, @$in[ 'content' ], @$in[ 'format' ], $prefix . 'content', 4, $loadfocusnow, $loadfocusnow ),
                            array(
                                'error' => qa_html( @$errors[ 'content' ] ),
                            )
                        ),
                    ),

                    'buttons' => array(
                        'comment' => array(
                            'tags'  => 'onclick="' . $updatescript . ' return qas_blog_submit_comment(' . qa_js( $post[ 'postid' ] ) . ', ' . qa_js( $parent[ 'postid' ] ) . ', this);"',
                            'label' => qa_lang_html( 'question/add_comment_button' ),
                        ),

                        'cancel'  => array(
                            'tags'  => 'name="docancel"',
                            'label' => qa_lang_html( 'main/cancel_button' ),
                        ),
                    ),

                    'hidden'  => array(
                        $prefix . 'editor' => qa_html( $editorname ),
                        $prefix . 'doadd'  => '1',
                        $prefix . 'code'   => qa_get_form_security_code( 'blog_comment-' . $parent[ 'postid' ] ),
                    ),
                );

                if ( !strlen( $custom ) )
                    unset( $form[ 'fields' ][ 'custom' ] );

                if ( !qa_is_logged_in() )
                    qa_set_up_name_field( $qa_content, $form[ 'fields' ], @$in[ 'name' ], $prefix );

                qas_blog_set_up_notify_fields( $qa_content, $form[ 'fields' ], 'C', qa_get_logged_in_email(),
                    isset( $in[ 'notify' ] ) ? $in[ 'notify' ] : qa_opt( 'notify_users_default' ), @$in[ 'email' ], @$errors[ 'email' ], $prefix );

                $onloads = array();

                if ( $captchareason ) {
                    $captchaloadscript = qa_set_up_captcha_field( $qa_content, $form[ 'fields' ], $errors, qa_captcha_reason_note( $captchareason ) );

                    if ( strlen( $captchaloadscript ) )
                        $onloads[] = 'document.getElementById(' . qa_js( $formid ) . ').qa_show=function() { ' . $captchaloadscript . ' };';
                }

                if ( !$loadfocusnow ) {
                    if ( method_exists( $editor, 'load_script' ) )
                        $onloads[] = 'document.getElementById(' . qa_js( $formid ) . ').qa_load=function() { ' . $editor->load_script( $prefix . 'content' ) . ' };';
                    if ( method_exists( $editor, 'focus_script' ) )
                        $onloads[] = 'document.getElementById(' . qa_js( $formid ) . ').qa_focus=function() { ' . $editor->focus_script( $prefix . 'content' ) . ' };';

                    $form[ 'buttons' ][ 'cancel' ][ 'tags' ] .= ' onclick="return qa_toggle_element()"';
                }

                if ( count( $onloads ) )
                    $qa_content[ 'script_onloads' ][] = $onloads;
        }

        $form[ 'id' ] = $formid;
        $form[ 'collapse' ] = !$loadfocusnow;
        $form[ 'style' ] = 'tall';

        return $form;
    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */

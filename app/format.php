<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }
    
    /**
     * Based on the elements in $post , return HTML to be passed to theme layer to link
     * to the question, or to an associated answer, comment or edit.
     *
     * @param $post
     * @param $userid
     * @param $cookieid
     * @param $usershtml
     * @param $dummy
     * @param $options
     *
     * @return array
     */
    function qas_blog_any_to_b_html_fields( $post, $userid, $cookieid, $usershtml, $dummy, $options )
    {
        if ( isset( $post[ 'opostid' ] ) )
            $fields = qas_blog_other_to_b_html_fields( $post, $userid, $cookieid, $usershtml, null, $options );
        else
            $fields = qas_blog_post_html_fields( $post, $userid, $cookieid, $usershtml, null, $options );

        return $fields;
    }

    /**
     * Return array of mostly HTML to be passed to theme layer, to *link* to an answer, comment or edit on
     * $post , as retrieved from database, with fields prefixed 'o' for the answer, comment or edit.
     * $userid, $cookieid, $usershtml, $options are passed through to qa_post_html_fields(). If $post['opersonal']
     * is set and true then the item is displayed with its personal relevance to the user (for user updates page).
     *
     * @param $post
     * @param $userid
     * @param $cookieid
     * @param $usershtml
     * @param $dummy
     * @param $options
     *
     * @return array
     */
    function qas_blog_other_to_b_html_fields( $post, $userid, $cookieid, $usershtml, $dummy, $options )
    {
        $fields = qas_blog_post_html_fields( $post, $userid, $cookieid, $usershtml, null, $options );

        switch ( $post[ 'obasetype' ] . '-' . @$post[ 'oupdatetype' ] ) {
            case 'B-':
                $langstring = 'qas_blog/posted';
                break;

            case 'B-' . QA_UPDATE_VISIBLE:
                if ( @$post[ 'opersonal' ] )
                    $langstring = $post[ 'hidden' ] ? 'misc/your_q_hidden' : 'misc/your_q_reshown';
                else
                    $langstring = $post[ 'hidden' ] ? 'main/hidden' : 'main/reshown';
                break;

            case 'B-' . QA_UPDATE_CLOSED:
                if ( @$post[ 'opersonal' ] )
                    $langstring = isset( $post[ 'closedbyid' ] ) ? 'misc/your_q_closed' : 'misc/your_q_reopened';
                else
                    $langstring = isset( $post[ 'closedbyid' ] ) ? 'main/closed' : 'main/reopened';
                break;

            case 'B-' . QA_UPDATE_TAGS:
                $langstring = @$post[ 'opersonal' ] ? 'misc/your_q_retagged' : 'main/retagged';
                break;

            case 'B-' . QA_UPDATE_CATEGORY:
                $langstring = @$post[ 'opersonal' ] ? 'misc/your_q_recategorized' : 'main/recategorized';
                break;

            case 'B-' . QA_UPDATE_FOLLOWS:
                $langstring = @$post[ 'opersonal' ] ? 'misc/your_a_questioned' : 'main/asked_related_q';
                break;

            case 'C-':
                $langstring = 'main/commented';
                break;

            case 'C-' . QA_UPDATE_C_FOR_Q:
                $langstring = @$post[ 'opersonal' ] ? 'misc/your_q_commented' : 'main/commented';
                break;

            case 'C-' . QA_UPDATE_C_FOR_A:
                $langstring = @$post[ 'opersonal' ] ? 'misc/your_a_commented' : 'main/commented';
                break;

            case 'C-' . QA_UPDATE_FOLLOWS:
                $langstring = @$post[ 'opersonal' ] ? 'misc/your_c_followed' : 'main/commented';
                break;

            case 'C-' . QA_UPDATE_TYPE:
                $langstring = @$post[ 'opersonal' ] ? 'misc/your_c_moved' : 'main/comment_moved';
                break;

            case 'C-' . QA_UPDATE_VISIBLE:
                if ( @$post[ 'opersonal' ] )
                    $langstring = $post[ 'ohidden' ] ? 'misc/your_c_hidden' : 'misc/your_c_reshown';
                else
                    $langstring = $post[ 'ohidden' ] ? 'main/hidden' : 'main/comment_reshown';
                break;

            case 'C-' . QA_UPDATE_CONTENT:
                $langstring = @$post[ 'opersonal' ] ? 'misc/your_c_edited' : 'main/comment_edited';
                break;

            case 'B-' . QA_UPDATE_CONTENT:
            default:
                $langstring = @$post[ 'opersonal' ] ? 'misc/your_q_edited' : 'main/edited';
                break;
        }

        $fields[ 'what' ] = qa_lang_html( $langstring );

        if ( @$post[ 'opersonal' ] )
            $fields[ 'what_your' ] = true;

        if ( ( $post[ 'obasetype' ] != 'B' ) || ( @$post[ 'oupdatetype' ] == QA_UPDATE_FOLLOWS ) )
            $fields[ 'what_url' ] = qas_blog_post_path_html( $post[ 'postid' ], $post[ 'title' ], false, $post[ 'obasetype' ], $post[ 'opostid' ] );

        if ( @$options[ 'contentview' ] && !empty( $post[ 'ocontent' ] ) ) {
            $viewer = qa_load_viewer( $post[ 'ocontent' ], $post[ 'oformat' ] );

            $fields[ 'content' ] = $viewer->get_html( $post[ 'ocontent' ], $post[ 'oformat' ], array(
                'blockwordspreg' => @$options[ 'blockwordspreg' ],
                'showurllinks'   => @$options[ 'showurllinks' ],
                'linksnewwindow' => @$options[ 'linksnewwindow' ],
            ) );
        }

        if ( @$options[ 'whenview' ] )
            $fields[ 'when' ] = qa_when_to_html( $post[ 'otime' ], @$options[ 'fulldatedays' ] );

        if ( @$options[ 'whoview' ] ) {
            $isbyuser = qa_post_is_by_user( array( 'userid' => $post[ 'ouserid' ], 'cookieid' => @$post[ 'ocookieid' ] ), $userid, $cookieid );

            $fields[ 'who' ] = qa_who_to_html( $isbyuser, $post[ 'ouserid' ], $usershtml, @$options[ 'ipview' ] ? @$post[ 'oip' ] : null, false, @$post[ 'oname' ] );

            if ( isset( $post[ 'opoints' ] ) ) {
                if ( @$options[ 'pointsview' ] )
                    $fields[ 'who' ][ 'points' ] = ( $post[ 'opoints' ] == 1 ) ? qa_lang_html_sub_split( 'main/1_point', '1', '1' )
                        : qa_lang_html_sub_split( 'main/x_points', qa_html( number_format( $post[ 'opoints' ] ) ) );

                if ( isset( $options[ 'pointstitle' ] ) )
                    $fields[ 'who' ][ 'title' ] = qa_get_points_title_html( $post[ 'opoints' ], $options[ 'pointstitle' ] );
            }

            if ( isset( $post[ 'olevel' ] ) )
                $fields[ 'who' ][ 'level' ] = qa_html( qa_user_level_string( $post[ 'olevel' ] ) );
        }

        unset( $fields[ 'flags' ] );
        //temporarily disable the flagging feature
        if ( false && @$options[ 'flagsview' ] && @$post[ 'oflagcount' ] )
            $fields[ 'flags' ] = ( $post[ 'oflagcount' ] == 1 ) ? qa_lang_html_sub_split( 'main/1_flag', '1', '1' )
                : qa_lang_html_sub_split( 'main/x_flags', $post[ 'oflagcount' ] );

        unset( $fields[ 'avatar' ] );
        if ( @$options[ 'avatarsize' ] > 0 ) {
            if ( QA_FINAL_EXTERNAL_USERS )
                $fields[ 'avatar' ] = qa_get_external_avatar_html( $post[ 'ouserid' ], $options[ 'avatarsize' ], false );
            else
                $fields[ 'avatar' ] = qa_get_user_avatar_html( $post[ 'oflags' ], $post[ 'oemail' ], $post[ 'ohandle' ],
                    $post[ 'oavatarblobid' ], $post[ 'oavatarwidth' ], $post[ 'oavatarheight' ], $options[ 'avatarsize' ] );
        }

        return $fields;
    }

    /**
     * Given $post retrieved from database, return array of mostly HTML to be passed to theme layer.
     * $userid and $cookieid refer to the user *viewing* the page.
     * $usershtml is an array of [user id] => [HTML representation of user] built ahead of time.
     * $dummy is a placeholder (used to be $categories parameter but that's no longer needed)
     * $options is an array which sets what is displayed (see qa_post_html_defaults() in qa-app-options.php)
     * If something is missing from $post (e.g. ['content']), correponding HTML also omitted.
     *
     * @param       $post
     * @param       $userid
     * @param       $cookieid
     * @param       $usershtml
     * @param       $dummy
     * @param array $options
     *
     * @return array
     */
    function qas_blog_post_html_fields( $post, $userid, $cookieid, $usershtml, $dummy, $options = array() )
    {
        $fields = array( 'raw' => $post );

        //	Useful stuff used throughout function

        $postid = $post[ 'postid' ];
        $isblogpost = ( $post[ 'basetype' ] == 'B' || $post[ 'basetype' ] == 'D' );
        $isanswer = false;
        $isbyuser = qa_post_is_by_user( $post, $userid, $cookieid );
        $anchor = urlencode( qa_anchor( $post[ 'basetype' ], $postid ) );
        $elementid = isset( $options[ 'elementid' ] ) ? $options[ 'elementid' ] : $anchor;
        $microformats = @$options[ 'microformats' ];
        $isselected = @$options[ 'isselected' ];
        $favoritedview = @$options[ 'favoritedview' ];
        $favoritemap = $favoritedview ? qas_blog_get_favorite_non_bs_map() : array();

        //	High level information

        $fields[ 'hidden' ] = @$post[ 'hidden' ];
        $fields[ 'tags' ] = 'id="' . qa_html( $elementid ) . '"';

        $fields[ 'classes' ] = ( $isblogpost && $favoritedview && @$post[ 'userfavoriteq' ] ) ? 'qa-q-favorited' : '';
        if ( $isblogpost && isset( $post[ 'closedbyid' ] ) )
            $fields[ 'classes' ] = ltrim( $fields[ 'classes' ] . ' qa-q-closed' );

        if ( $microformats )
            $fields[ 'classes' ] .= ' hentry ' . ( $isblogpost ? 'question' : 'comment' );

        //	Question-specific stuff (title, URL, tags, answer count, category)

        if ( $isblogpost ) {
            if ( isset( $post[ 'title' ] ) ) {

                $fields[ 'url' ] = qas_blog_post_path_html( $postid, $post[ 'title' ] );

                if ( isset( $options[ 'blockwordspreg' ] ) )
                    $post[ 'title' ] = qa_block_words_replace( $post[ 'title' ], $options[ 'blockwordspreg' ] );

                $fields[ 'title' ] = qa_html( $post[ 'title' ] );
                if ( $microformats )
                    $fields[ 'title' ] = '<span class="entry-title">' . $fields[ 'title' ] . '</span>';

            }

            if ( @$options[ 'tagsview' ] && isset( $post[ 'tags' ] ) ) {
                $fields[ 'q_tags' ] = array();

                $tags = qa_tagstring_to_tags( $post[ 'tags' ] );
                foreach ( $tags as $tag ) {
                    if ( isset( $options[ 'blockwordspreg' ] ) && count( qa_block_words_match_all( $tag, $options[ 'blockwordspreg' ] ) ) ) // skip censored tags
                        continue;

                    $fields[ 'q_tags' ][] = qas_blog_tag_html( $tag, $microformats, @$favoritemap[ 'tag' ][ qa_strtolower( $tag ) ] );
                }
            }

            if ( @$options[ 'viewsview' ] && isset( $post[ 'views' ] ) ) {
                $fields[ 'views_raw' ] = $post[ 'views' ];

                $fields[ 'views' ] = ( $post[ 'views' ] == 1 ) ? qa_lang_html_sub_split( 'main/1_view', '1', '1' ) :
                    qa_lang_html_sub_split( 'main/x_views', number_format( $post[ 'views' ] ) );
            }

            if ( @$options[ 'categoryview' ] && isset( $post[ 'categoryname' ] ) && isset( $post[ 'categorybackpath' ] ) ) {
                $favoriteclass = '';

                if ( isset($favoritemap[ 'category' ]) && count( @$favoritemap[ 'category' ] ) ) {
                    if ( @$favoritemap[ 'category' ][ $post[ 'categorybackpath' ] ] ) {
                        $favoriteclass = ' qa-cat-favorited';
                    } else {
                        foreach ( $favoritemap[ 'category' ] as $categorybackpath => $dummy ) {
                            if ( substr( '/' . $post[ 'categorybackpath' ], -strlen( $categorybackpath ) ) == $categorybackpath ) {
                                $favoriteclass = ' qa-cat-parent-favorited';
                            }
                        }
                    }
                }
                
                $fields[ 'where' ] = qa_lang_html_sub_split( 'main/in_category_x',
                    '<a href="' . qa_path_html( @$options[ 'categorypathprefix' ] . implode( '/', array_reverse( explode( '/', $post[ 'categorybackpath' ] ) ) ) ) .
                    '" class="qa-category-link' . $favoriteclass . '">' . qa_html( $post[ 'categoryname' ] ) . '</a>' );
            }
        }

        //	Post content

        if ( @$options[ 'contentview' ] && !empty( $post[ 'content' ] ) ) {
            $viewer = qa_load_viewer( $post[ 'content' ], $post[ 'format' ] );

            $fields[ 'content' ] = $viewer->get_html( $post[ 'content' ], $post[ 'format' ], array(
                'blockwordspreg' => @$options[ 'blockwordspreg' ],
                'showurllinks'   => @$options[ 'showurllinks' ],
                'linksnewwindow' => @$options[ 'linksnewwindow' ],
            ) );

            if ( $microformats )
                $fields[ 'content' ] = '<div class="entry-content">' . $fields[ 'content' ] . '</div>';

            $fields[ 'content' ] = '<a name="' . qa_html( $postid ) . '"></a>' . $fields[ 'content' ];
            // this is for backwards compatibility with any existing links using the old style of anchor
            // that contained the post id only (changed to be valid under W3C specifications)
        }

        //	Flag count

        if ( @$options[ 'flagsview' ] && @$post[ 'flagcount' ] )
            $fields[ 'flags' ] = ( $post[ 'flagcount' ] == 1 ) ? qa_lang_html_sub_split( 'main/1_flag', '1', '1' )
                : qa_lang_html_sub_split( 'main/x_flags', $post[ 'flagcount' ] );

        //	Created when and by whom

        $fields[ 'meta_order' ] = qa_lang_html( 'main/meta_order' ); // sets ordering of meta elements which can be language-specific

        if ( @$options[ 'whatview' ] ) {
            $fields[ 'what' ] = qa_lang_html( $isblogpost ? 'qas_blog/posted' : 'main/commented' );

            if ( @$options[ 'whatlink' ] && strlen( @$options[ 'q_request' ] ) )
                $fields[ 'what_url' ] = ( $post[ 'basetype' ] == 'B' ) ? qa_path_html( $options[ 'q_request' ] )
                    : qas_blog_path_html( $options[ 'q_request' ], array( 'show' => $postid ), null, null, qa_anchor( $post[ 'basetype' ], $postid ) );
        }

        if ( isset( $post[ 'created' ] ) && @$options[ 'whenview' ] ) {
            $fields[ 'when' ] = qa_when_to_html( $post[ 'created' ], @$options[ 'fulldatedays' ] );

            if ( $microformats )
                $fields[ 'when' ][ 'data' ] = '<span class="published"><span class="value-title" title="' . gmdate( 'Y-m-d\TH:i:sO', $post[ 'created' ] ) . '"></span>' . $fields[ 'when' ][ 'data' ] . '</span>';
        }

        if ( @$options[ 'whoview' ] ) {
            $fields[ 'who' ] = qa_who_to_html( $isbyuser, @$post[ 'userid' ], $usershtml, @$options[ 'ipview' ] ? @$post[ 'createip' ] : null, $microformats, $post[ 'name' ] );

            if ( isset( $post[ 'points' ] ) ) {
                if ( @$options[ 'pointsview' ] )
                    $fields[ 'who' ][ 'points' ] = ( $post[ 'points' ] == 1 ) ? qa_lang_html_sub_split( 'main/1_point', '1', '1' )
                        : qa_lang_html_sub_split( 'main/x_points', qa_html( number_format( $post[ 'points' ] ) ) );

                if ( isset( $options[ 'pointstitle' ] ) )
                    $fields[ 'who' ][ 'title' ] = qa_get_points_title_html( $post[ 'points' ], $options[ 'pointstitle' ] );
            }

            if ( isset( $post[ 'level' ] ) )
                $fields[ 'who' ][ 'level' ] = qa_html( qa_user_level_string( $post[ 'level' ] ) );
        }

        if ( @$options[ 'avatarsize' ] > 0 ) {
            if ( QA_FINAL_EXTERNAL_USERS )
                $fields[ 'avatar' ] = qa_get_external_avatar_html( $post[ 'userid' ], $options[ 'avatarsize' ], false );
            else
                $fields[ 'avatar' ] = qa_get_user_avatar_html( @$post[ 'flags' ], @$post[ 'email' ], @$post[ 'handle' ],
                    @$post[ 'avatarblobid' ], @$post[ 'avatarwidth' ], @$post[ 'avatarheight' ], $options[ 'avatarsize' ] );
        }

        //	Updated when and by whom

        if (
            @$options[ 'updateview' ] && isset( $post[ 'updated' ] ) && // only show selected change if it's still selected
            ( // check if one of these conditions is fulfilled...
                ( !isset( $post[ 'created' ] ) ) || // ... we didn't show the created time (should never happen in practice)
                ( $post[ 'hidden' ] && ( $post[ 'updatetype' ] == QA_UPDATE_VISIBLE ) ) || // ... the post was hidden as the last action
                ( isset( $post[ 'closedbyid' ] ) && ( $post[ 'updatetype' ] == QA_UPDATE_CLOSED ) ) || // ... the post was closed as the last action
                ( abs( $post[ 'updated' ] - $post[ 'created' ] ) > 300 ) || // ... or over 5 minutes passed between create and update times
                ( $post[ 'lastuserid' ] != $post[ 'userid' ] ) // ... or it was updated by a different user
            )
        ) {
            switch ( $post[ 'updatetype' ] ) {
                case QA_UPDATE_TYPE:
                case QA_UPDATE_PARENT:
                    $langstring = 'main/moved';
                    break;

                case QA_UPDATE_CATEGORY:
                    $langstring = 'main/recategorized';
                    break;

                case QA_UPDATE_VISIBLE:
                    $langstring = $post[ 'hidden' ] ? 'main/hidden' : 'main/reshown';
                    break;

                case QA_UPDATE_CLOSED:
                    $langstring = isset( $post[ 'closedbyid' ] ) ? 'main/closed' : 'main/reopened';
                    break;

                case QA_UPDATE_TAGS:
                    $langstring = 'main/retagged';
                    break;

                case QA_UPDATE_SELECTED:
                    $langstring = 'main/selected';
                    break;

                default:
                    $langstring = 'main/edited';
                    break;
            }

            $fields[ 'what_2' ] = qa_lang_html( $langstring );

            if ( @$options[ 'whenview' ] ) {
                $fields[ 'when_2' ] = qa_when_to_html( $post[ 'updated' ], @$options[ 'fulldatedays' ] );

                if ( $microformats )
                    $fields[ 'when_2' ][ 'data' ] = '<span class="updated"><span class="value-title" title="' . gmdate( 'Y-m-d\TH:i:sO', $post[ 'updated' ] ) . '"></span>' . $fields[ 'when_2' ][ 'data' ] . '</span>';
            }

            if ( isset( $post[ 'lastuserid' ] ) && @$options[ 'whoview' ] )
                $fields[ 'who_2' ] = qa_who_to_html( isset( $userid ) && ( $post[ 'lastuserid' ] == $userid ), $post[ 'lastuserid' ], $usershtml, @$options[ 'ipview' ] ? $post[ 'lastip' ] : null, false );
        }

        //	That's it!

        return $fields;
    }

    /**
     * Set up $qa_content and add to $fields to allow user to set if they want to be notified regarding their post.
     * $basetype is 'Q', 'A' or 'C' for question, answer or comment. $login_email is the email of logged in user,
     * or null if this is an anonymous post. $innotify, $inemail and $errors_email are from previous
     * submission/validation. Pass $fieldprefix to add a prefix to the form field names and IDs used.
     *
     * @param        $qa_content
     * @param        $fields
     * @param        $basetype
     * @param        $login_email
     * @param        $innotify
     * @param        $inemail
     * @param        $errors_email
     * @param string $fieldprefix
     */
    function qas_blog_set_up_notify_fields( &$qa_content, &$fields, $basetype, $login_email, $innotify, $inemail, $errors_email, $fieldprefix = '' )
    {
        $fields[ 'notify' ] = array(
            'tags'  => 'name="' . $fieldprefix . 'notify"',
            'type'  => 'checkbox',
            'value' => qa_html( $innotify ),
        );

        switch ( $basetype ) {
            case 'B':
                $labelaskemail = qa_lang_html( 'qas_blog/post_notify_email' );
                $labelonly = qa_lang_html( 'qas_blog/post_notify_label' );
                $labelgotemail = qa_lang_html( 'qas_blog/post_notify_x_label' );
                break;

            case 'C':
                $labelaskemail = qa_lang_html( 'question/c_notify_email' );
                $labelonly = qa_lang_html( 'question/c_notify_label' );
                $labelgotemail = qa_lang_html( 'question/c_notify_x_label' );
                break;
        }

        if ( empty( $login_email ) ) {
            $fields[ 'notify' ][ 'label' ] =
                '<span id="' . $fieldprefix . 'email_shown">' . $labelaskemail . '</span>' .
                '<span id="' . $fieldprefix . 'email_hidden" style="display:none;">' . $labelonly . '</span>';

            $fields[ 'notify' ][ 'tags' ] .= ' id="' . $fieldprefix . 'notify" onclick="if (document.getElementById(\'' . $fieldprefix . 'notify\').checked) document.getElementById(\'' . $fieldprefix . 'email\').focus();"';
            $fields[ 'notify' ][ 'tight' ] = true;

            $fields[ 'email' ] = array(
                'id'    => $fieldprefix . 'email_display',
                'tags'  => 'name="' . $fieldprefix . 'email" id="' . $fieldprefix . 'email"',
                'value' => qa_html( $inemail ),
                'note'  => qa_lang_html( 'question/notify_email_note' ),
                'error' => qa_html( $errors_email ),
            );

            qa_set_display_rules( $qa_content, array(
                $fieldprefix . 'email_display' => $fieldprefix . 'notify',
                $fieldprefix . 'email_shown'   => $fieldprefix . 'notify',
                $fieldprefix . 'email_hidden'  => '!' . $fieldprefix . 'notify',
            ) );

        } else {
            $fields[ 'notify' ][ 'label' ] = str_replace( '^', qa_html( $login_email ), $labelgotemail );
        }
    }

    /**
     * setup the save as draft field for logged in users
     *
     * @param        $qa_content
     * @param        $fields
     * @param string $fieldprefix
     * @param bool   $checked
     */
    function qas_blog_set_up_draft_field( &$qa_content, &$fields, $fieldprefix = '', $checked = false )
    {
        if ( !qa_is_logged_in() ) {
            //if no one is logged in then go back from here
            return null;
        }

        $fields[ 'draft' ] = array(
            'tags'  => 'name="' . $fieldprefix . 'save_draft" id="' . $fieldprefix . 'save_draft"',
            'type'  => 'checkbox',
            'value' => $checked,
        );

        $labelonly = qa_lang_html( 'qas_blog/save_post_as_draft' );
        $lable_after_checked = qa_lang_html( 'qas_blog/this_will_be_draft' );

        $fields[ 'draft' ][ 'label' ] =
            '<span id="' . $fieldprefix . 'not_checked_msg">' . $labelonly . '</span>' .
            '<span id="' . $fieldprefix . 'checked_msg" style="display:none;">' . $lable_after_checked . '</span>';

        $fields[ 'draft' ][ 'tight' ] = true;

        qa_set_display_rules( $qa_content, array(
            $fieldprefix . 'not_checked_msg' => '!' . $fieldprefix . 'save_draft',
            $fieldprefix . 'checked_msg'     => $fieldprefix . 'save_draft',
        ) );
    }

    /**
     * Return a form to set in $qa_content['favorite'] for the favoriting button for entity $entitytype with $entityid.
     * Set $favorite to whether the entity is currently a favorite and a description title for the button in $title.
     *
     * @param $entitytype
     * @param $entityid
     * @param $favorite
     * @param $title
     *
     * @return array
     */
    function qas_blog_favorite_form( $entitytype, $entityid, $favorite, $title )
    {
        return array(
            'form_tags'                                                  => 'method="post" action="' . qa_self_html() . '"',
            'form_hidden'                                                => array( 'code' => qa_get_form_security_code( 'qas-blog-favorite-' . $entitytype . '-' . $entityid ) ),
            'favorite_tags'                                              => 'id="favoriting"',
            ( $favorite ? 'favorite_remove_tags' : 'favorite_add_tags' ) =>
                'title="' . qa_html( $title ) . '" name="' . qa_html( 'blogfavorite_' . $entitytype . '_' . $entityid . '_' . (int) !$favorite ) . '" onclick="return qas_blog_favorite_click(this);"',
        );
    }

    /**
     * Get an array listing all of the logged in user's favorite items, except their favorited questions (these are
     * excluded because users tend to favorite many more questions than other things.) The top-level array can contain
     * three keys - 'user' for favorited users, 'tag' for tags, 'category' for categories. The next level down has the
     * identifier for each favorited entity in the *key* of the array, and true for its value. If no user is logged in
     * the empty array is returned. The result is cached for future calls.
     *
     * @return array
     */
    function qas_blog_get_favorite_non_bs_map()
    {
        global $qas_blog_favorite_non_qs_map;

        if ( !isset( $qas_blog_favorite_non_qs_map ) ) {
            $qas_blog_favorite_non_qs_map = array();
            $loginuserid = qa_get_logged_in_userid();
            if ( isset( $loginuserid ) ) {
                $favoritenonqs = qa_db_get_pending_result( 'blog_favoritenonqs', qas_blog_db_user_favorite_non_bs_selectspec( $loginuserid ) );
                foreach ( $favoritenonqs as $favorite ) {
                    switch ( $favorite[ 'type' ] ) {
                        case QAS_BLOG_ENTITY_TAG:
                            $qas_blog_favorite_non_qs_map[ 'tag' ][ qa_strtolower( $favorite[ 'tags' ] ) ] = true;
                            break;

                        case QAS_BLOG_ENTITY_CATEGORY:
                            $qas_blog_favorite_non_qs_map[ 'category' ][ $favorite[ 'categorybackpath' ] ] = true;
                            break;
                    }
                }
            }
        }

        return $qas_blog_favorite_non_qs_map;
    }
<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    /**
     * Return the HTML representation of the URL for $postid - other parameters as for qa_q_path()
     *
     * @param      $postid
     * @param      $title
     * @param bool $absolute
     * @param null $showtype
     * @param null $showid
     *
     * @return mixed|string
     */
    function qas_blog_post_path_html( $postid, $title, $absolute = false, $showtype = null, $showid = null )
    {
        return qa_html( qas_blog_post_path( $postid, $title, $absolute, $showtype, $showid ) );
    }

    /**
     * Return the URL for blog post $postid with $title, possibly using $absolute URLs.
     * To link to a specific answer or comment in a question, set $showtype and $showid accordingly.
     *
     * @param      $postid
     * @param      $title
     * @param bool $absolute
     * @param null $showtype
     * @param null $showid
     *
     * @return string
     */
    function qas_blog_post_path( $postid, $title, $absolute = false, $showtype = null, $showid = null )
    {

        if ( ( ( $showtype == 'B' ) || ( $showtype == 'C' ) ) && isset( $showid ) ) {
            $params = array( 'show' => $showid); // due to pagination
            $anchor = qa_anchor( $showtype, $showid );

        } else {
            $params = null;
            $anchor = null;
	}

        return qa_path( qas_blog_request( $postid, $title ), $params, $absolute ? qa_opt( 'site_url' ) : null, null, $anchor );
    }

    /**
     * Return the Q2A request for question $postid , and make it search-engine friendly based on $title, which is
     * shortened if necessary by removing shorter words which are generally less meaningful.
     *
     * @param $postid
     * @param $title
     *
     * @return string
     */
    function qas_blog_request( $postid, $title )
    {

        $title = qa_block_words_replace( $title, qa_get_block_words_preg() );

        $words = qa_string_to_words( $title, true, false, false );

        $wordlength = array();
        foreach ( $words as $index => $word )
            $wordlength[ $index ] = qa_strlen( $word );

        $remaining = qa_opt( 'qas_blog_urls_title_length' );

        if ( array_sum( $wordlength ) > $remaining ) {
            arsort( $wordlength, SORT_NUMERIC ); // sort with longest words first

            foreach ( $wordlength as $index => $length ) {
                if ( $remaining > 0 )
                    $remaining -= $length;
                else
                    unset( $words[ $index ] );
            }
        }

        $title = implode( '-', $words );
        if ( qa_opt( 'qas_blog_urls_remove_accents' ) )
            $title = qa_string_remove_accents( $title );

        $postid = (int) $postid;

        return qas_get_blog_url_sub( "^/$postid/$title" );
    }
    
    /**
     *
     * Return HTML representation of relative URI path for $request - see qa_path() for other parameters
     *
     * @param      $request
     * @param null $params
     * @param null $rooturl
     * @param null $neaturls
     * @param null $anchor
     *
     * @return mixed|string
     */
    function qas_blog_path_html( $request, $params = null, $rooturl = null, $neaturls = null, $anchor = null )
    {
        $request = qas_get_blog_url_sub( "^/$request" );

        return qa_path_html( $request, $params, $rooturl, $neaturls, $anchor );
    }

    function qas_blog_url_plural_structure( $suffix = false )
    {
        return $suffix ? QAS_BLOG_URL_PLURAL_STRUCTURE . $suffix : QAS_BLOG_URL_PLURAL_STRUCTURE;
    }

    /**
     *
     * Return HTML representation of relative URI path for $request with a plural prefix
     *
     * @param      $request
     * @param null $params
     * @param null $rooturl
     * @param null $neaturls
     * @param null $anchor
     *
     * @return mixed|string
     */
    function qas_blog_path_html_plural( $request, $params = null, $rooturl = null, $neaturls = null, $anchor = null )
    {
        $request = qas_get_blog_url_sub( qas_blog_url_plural_structure( '/' ) . $request );

        return qa_path_html( $request, $params, $rooturl, $neaturls, $anchor );
    }

    /**
     * Convert textual $tag to HTML representation, with microformats if $microformats is true. Set $favorited to true
     * to show the tag as favorited.
     *
     * @param      $tag
     * @param bool $microformats
     * @param bool $favorited
     *
     * @return string
     */
    function qas_blog_tag_html( $tag, $microformats = false, $favorited = false )
    {
        return '<a href="' . qas_blog_path_html( 'tag/' . $tag ) . '"' . ( $microformats ? ' rel="tag"' : '' ) . ' class="qa-tag-link' .
        ( $favorited ? ' qa-tag-favorited' : '' ) . '">' . qa_html( $tag ) . '</a>';
    }
    
    /**
     * Set up $qa_content and $field (with HTML name $fieldname) for hierarchical category navigation, with the initial
     * value set to $categoryid (and $navcategories retrieved for $categoryid using
     * qa_db_category_nav_selectspec(...)). If $allownone is true, it will allow selection of no category. If
     * $allownosub is true, it will allow a category to be selected without selecting a subcategory within. Set
     * $maxdepth to the maximum depth of category that can be selected
     * (or null for no maximum) and $excludecategoryid to a category that should not be included.
     *
     * @param      $qa_content
     * @param      $field
     * @param      $fieldname
     * @param      $navcategories
     * @param      $categoryid
     * @param      $allownone
     * @param      $allownosub
     * @param null $maxdepth
     * @param null $excludecategoryid
     */
    function qas_blog_set_up_category_field( &$qa_content, &$field, $fieldname, $navcategories, $categoryid, $allownone, $allownosub, $maxdepth = null, $excludecategoryid = null )
    {
        $pathcategories = qa_category_path( $navcategories, $categoryid );

        $startpath = '';
        foreach ( $pathcategories as $category )
            $startpath .= '/' . $category[ 'categoryid' ];

        if ( isset( $maxdepth ) )
            $maxdepth = min( QA_CATEGORY_DEPTH, $maxdepth );
        else
            $maxdepth = QA_CATEGORY_DEPTH;

        $qa_content[ 'script_onloads' ][] = sprintf( 'qas_blog_category_select(%s, %s);', qa_js( $fieldname ), qa_js( $startpath ) );

        $qa_content[ 'script_var' ][ 'qa_cat_exclude' ] = $excludecategoryid;
        $qa_content[ 'script_var' ][ 'qa_cat_allownone' ] = (int) $allownone;
        $qa_content[ 'script_var' ][ 'qa_cat_allownosub' ] = (int) $allownosub;
        $qa_content[ 'script_var' ][ 'qa_cat_maxdepth' ] = $maxdepth;

        $field[ 'type' ] = 'select';
        $field[ 'tags' ] = sprintf( 'name="%s_0" id="%s_0" onchange="qas_blog_category_select(%s);"', $fieldname, $fieldname, qa_js( $fieldname ) );
        $field[ 'options' ] = array();

        // create the menu that will be shown if Javascript is disabled

        if ( $allownone )
            $field[ 'options' ][ '' ] = qa_lang_html( 'main/no_category' ); // this is also copied to first menu created by Javascript

        $keycategoryids = array();

        if ( $allownosub ) {
            $category = @$navcategories[ $categoryid ];

            $upcategory = @$navcategories[ $category[ 'parentid' ] ]; // first get supercategories
            while ( isset( $upcategory ) ) {
                $keycategoryids[ $upcategory[ 'categoryid' ] ] = true;
                $upcategory = @$navcategories[ $upcategory[ 'parentid' ] ];
            }

            $keycategoryids = array_reverse( $keycategoryids, true );

            $depth = count( $keycategoryids ); // number of levels above

            if ( isset( $category ) ) {
                $depth++; // to count category itself

                foreach ( $navcategories as $navcategory ) // now get siblings and self
                    if ( !strcmp( $navcategory[ 'parentid' ], $category[ 'parentid' ] ) )
                        $keycategoryids[ $navcategory[ 'categoryid' ] ] = true;
            }

            if ( $depth < $maxdepth )
                foreach ( $navcategories as $navcategory ) // now get children, if not too deep
                    if ( !strcmp( $navcategory[ 'parentid' ], $categoryid ) )
                        $keycategoryids[ $navcategory[ 'categoryid' ] ] = true;

        } else {
            $haschildren = false;

            foreach ( $navcategories as $navcategory ) // check if it has any children
                if ( !strcmp( $navcategory[ 'parentid' ], $categoryid ) ) {
                    $haschildren = true;
                    break;
                }

            if ( !$haschildren )
                $keycategoryids[ $categoryid ] = true; // show this category if it has no children
        }

        foreach ( $keycategoryids as $keycategoryid => $dummy )
            if ( strcmp( $keycategoryid, $excludecategoryid ) )
                $field[ 'options' ][ $keycategoryid ] = qa_category_path_html( $navcategories, $keycategoryid );

        $field[ 'value' ] = @$field[ 'options' ][ $categoryid ];
        $field[ 'note' ] =
            '<div id="' . $fieldname . '_note">' .
            '<noscript style="color:red;">' . qa_lang_html( 'question/category_js_note' ) . '</noscript>' .
            '</div>';
    }

    /**
     * Set up $qa_content and $field (with HTML name $fieldname) for tag auto-completion, where
     * $exampletags are suggestions and $completetags are simply the most popular ones. Show up to $maxtags.
     *
     * @param $qa_content
     * @param $field
     * @param $fieldname
     * @param $tags
     * @param $exampletags
     * @param $completetags
     * @param $maxtags
     */
    function qas_blog_set_up_tag_field( &$qa_content, &$field, $fieldname, $tags, $exampletags, $completetags, $maxtags )
    {
        $template = '<a href="#" class="qa-tag-link" onclick="return qas_blog_tag_click(this);">^</a>';

        $qa_content[ 'script_rel' ][] = 'qa-content/qa-ask.js?' . QA_VERSION;
        $qa_content[ 'script_var' ][ 'qa_tag_template' ] = $template;
        $qa_content[ 'script_var' ][ 'qa_tag_onlycomma' ] = (int) qa_opt( 'qas_blog_tag_separator_comma' );
        $qa_content[ 'script_var' ][ 'qa_tags_examples' ] = qa_html( implode( ',', $exampletags ) );
        $qa_content[ 'script_var' ][ 'qas_blog_tags_complete' ] = qa_html( implode( ',', $completetags ) );
        $qa_content[ 'script_var' ][ 'qa_tags_max' ] = (int) $maxtags;

        $separatorcomma = qa_opt( 'qas_blog_tag_separator_comma' );

        $field[ 'label' ] = qa_lang_html( $separatorcomma ? 'question/q_tags_comma_label' : 'question/q_tags_label' );
        $field[ 'value' ] = qa_html( implode( $separatorcomma ? ', ' : ' ', $tags ) );
        $field[ 'tags' ] = 'name="' . $fieldname . '" id="tags" autocomplete="off" onkeyup="qas_blog_tag_hints();" onmouseup="qas_blog_tag_hints();"';

        $sdn = ' style="display:none;"';

        $field[ 'note' ] =
            '<span id="tag_examples_title"' . ( count( $exampletags ) ? '' : $sdn ) . '>' . qa_lang_html( 'question/example_tags' ) . '</span>' .
            '<span id="tag_complete_title"' . $sdn . '>' . qa_lang_html( 'question/matching_tags' ) . '</span><span id="tag_hints">';

        foreach ( $exampletags as $tag )
            $field[ 'note' ] .= str_replace( '^', qa_html( $tag ), $template ) . ' ';

        $field[ 'note' ] .= '</span>';
        $field[ 'note_force' ] = true;
    }

    /**
     * Delete $postid from the database, hiding it first if appropriate.
     *
     * @param $postid
     */
    function qas_blog_post_delete( $postid )
    {
        $oldpost = qas_blog_post_get_full( $postid, 'BC' );

        if ( !$oldpost[ 'hidden' ] ) {
            qa_post_set_hidden( $postid, true, null );
            $oldpost = qas_blog_post_get_full( $postid, 'BC' );
        }

        switch ( $oldpost[ 'basetype' ] ) {
            case 'B':
                $commentsfollows = qas_blog_get_post_commentsfollows( $postid );
                $closepost = qas_blog_post_get_b_post_closepost( $postid );

                if ( count( $commentsfollows ) )
                    qa_fatal_error( 'Could not delete question ID due to dependents: ' . $postid );

                qas_blog_post_b_delete( $oldpost, null, null, null, $closepost );
                break;

            case 'C':
                $parent = qas_blog_post_get_full( $oldpost[ 'parentid' ], 'B' );
                $post = qas_blog_parent_to_post( $parent );
                qas_blog_comment_delete( $oldpost, $post, $parent, null, null, null );
                break;
        }
    }

    /**
     * Delete $postid from the database, hiding it first if appropriate.
     *
     * @param $postid
     */
    function qas_blog_draft_delete( $postid )
    {
        $oldpost = qas_blog_post_get_full( $postid, 'D' );

        switch ( $oldpost[ 'basetype' ] ) {
            case 'D':
                qas_blog_post_draft_delete( $oldpost, null, null, null );
                break;
        }
    }

    /**
     * Publish the post and reindex everything
     *
     * @param $postid
     */
    function qas_blog_post_publish( $postid )
    {
        $oldpost = qas_blog_post_get_full( $postid, 'D' );

        switch ( $oldpost[ 'basetype' ] ) {
            case 'D':
                qas_blog_db_post_set_type( $oldpost[ 'postid' ], 'B' );
                qas_blog_post_index( $oldpost[ 'postid' ], 'B', $oldpost[ 'postid' ], $oldpost[ 'parentid' ], $oldpost[ 'title' ], $oldpost[ 'content' ], $oldpost[ 'format' ], @$oldpost[ 'text' ], $oldpost[ 'tags' ], $oldpost[ 'categoryid' ] );
                qas_blog_update_counts_for_post( $oldpost[ 'postid' ] );
                qas_blog_db_posts_calc_category_path( $oldpost[ 'postid' ] );

                //Now fire the event for notification
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

                qa_report_event( 'qas_blog_b_post', $oldpost[ 'userid' ], $oldpost[ 'handle' ], $oldpost[ 'cookieid' ], $eventparams + array(
                        'notify'  => isset( $oldpost[ 'notify' ] ),
                        'email'   => qa_email_validate( $oldpost[ 'notify' ] ) ? $oldpost[ 'notify' ] : null,
                        'delayed' => $oldpost[ 'created' ],
                    ) );

                break;
        }
    }

    /**
     * Return the full information from the database for $postid in an array.
     *
     * @param      $postid
     * @param null $requiredbasetypes
     *
     * @return array
     */
    function qas_blog_post_get_full( $postid, $requiredbasetypes = null )
    {
        $post = qa_db_single_select( qas_blog_db_full_post_selectspec( null, $postid ) );

        if ( !is_array( $post ) )
            qa_fatal_error( 'Post ID could not be found: ' . $postid );

        if ( isset( $requiredbasetypes ) && !is_numeric( strpos( $requiredbasetypes, $post[ 'basetype' ] ) ) )
            qa_fatal_error( 'Post of wrong type: ' . $post[ 'basetype' ] );

        return $post;
    }

    /**
     * Return the full database records for all comments or follow-on questions for question $postid or its answers
     *
     * @param $postid
     *
     * @return array
     */
    function qas_blog_get_post_commentsfollows( $postid )
    {
        $commentsfollows = array();

        $childposts = qa_db_single_select(
            qas_blog_db_full_child_posts_selectspec( null, $postid )
        );

        foreach ( $childposts as $postid => $post )
            if ( $post[ 'basetype' ] == 'C' )
                $commentsfollows[ $postid ] = $post;

        return $commentsfollows;
    }

    /**
     * Return the full database record for the post which closed $postid , if there is any
     *
     * @param $postid
     *
     * @return array
     */
    function qas_blog_post_get_b_post_closepost( $postid )
    {
        return qa_db_single_select( qas_blog_db_post_close_post_selectspec( $postid ) );
    }

    /**
     * Return $parent if it's the database record for a question, otherwise return the database record for its parent
     *
     * @param $parent
     *
     * @return array
     */
    function qas_blog_parent_to_post( $parent )
    {
        if ( $parent[ 'basetype' ] == 'B' )
            $post = $parent;
        else
            $post = qas_blog_post_get_full( $parent[ 'parentid' ], 'B' );

        return $post;
    }

    /**
     * Hide $postid if $hidden is true, otherwise show the post. Pass the identify of the user making this change in
     * $byuserid (or null for a silent change). This function is included mainly for backwards compatibility.
     *
     * @param      $postid
     * @param bool $hidden
     * @param null $byuserid
     */
    function qas_blog_post_set_hidden( $postid, $hidden = true, $byuserid = null )
    {
        qas_blog_post_set_status( $postid, $hidden ? QA_POST_STATUS_HIDDEN : QA_POST_STATUS_NORMAL, $byuserid );
    }

    /**
     * Change the status of $postid to $status, which should be one of the QA_POST_STATUS_* constants defined in
     * qa-app-post-update.php. Pass the identify of the user making this change in $byuserid (or null for a silent
     * change).
     *
     * @param      $postid
     * @param      $status
     * @param null $byuserid
     */
    function qas_blog_post_set_status( $postid, $status, $byuserid = null )
    {
        $oldpost = qas_blog_post_get_full( $postid, 'BC' );
        $byhandle = qa_userid_to_handle( $byuserid );

        switch ( $oldpost[ 'basetype' ] ) {
            case 'B':
                $commentsfollows = qas_blog_get_post_commentsfollows( $postid );
                $closepost = qas_blog_post_get_b_post_closepost( $postid );
                qas_blog_post_b_set_status( $oldpost, $status, $byuserid, $byhandle, null, null, $commentsfollows, $closepost );
                break;

            case 'C':
                $parent = qas_blog_post_get_full( $oldpost[ 'parentid' ], 'B' );
                $post = qas_blog_parent_to_post( $parent );
                qas_blog_comment_set_status( $oldpost, $status, $byuserid, $byhandle, null, $post, $parent );
                break;
        }
    }

    /**
     * Update views  required by the fields in $qa_content, and return true if something was done
     */
    function qas_blog_update_views( $qa_content )
    {
        if ( isset( $qa_content[ 'blog_inc_views_postid' ] ) ) {
            qas_blog_db_post_increment_view( $qa_content[ 'blog_inc_views_postid' ] );

            return true;
        }

        return false;
    }

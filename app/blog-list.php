<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    /**
     *    Returns the $qa_content structure for a question list page showing $posts retrieved from the
     *    database. If $pagesize is not null, it sets the max number of questions to display. If $count is
     *    not null, pagination is determined by $start and $count. The page title is $sometitle unless
     *    there are no questions shown, in which case it's $nonetitle. $navcategories should contain the
     *    categories retrived from the database using qa_db_category_nav_selectspec(...) for $categoryid,
     *    which is the current category shown. If $categorypathprefix is set, category navigation will be
     *    shown, with per-category question counts if $categoryqcount is true. The nav links will have the
     *    prefix $categorypathprefix and possible extra $categoryparams. If $feedpathprefix is set, the
     *    page has an RSS feed whose URL uses that prefix. If there are no links to other pages, $suggest
     *    is used to suggest what the user should do. The $pagelinkparams are passed through to
     *    qa_html_page_links(...) which creates links for page 2, 3, etc..
     *
     * @param      $posts
     * @param      $pagesize
     * @param      $start
     * @param      $count
     * @param      $sometitle
     * @param      $nonetitle
     * @param      $navcategories
     * @param      $categoryid
     * @param      $categoryqcount
     * @param      $categorypathprefix
     * @param      $feedpathprefix
     * @param      $suggest
     * @param null $pagelinkparams
     * @param null $categoryparams
     * @param null $dummy
     *
     * @return array
     */
    function qas_blog_list_page_content( $posts, $pagesize, $start, $count, $sometitle, $nonetitle,
                                         $navcategories, $categoryid, $categoryqcount, $categorypathprefix, $feedpathprefix, $suggest,
                                         $pagelinkparams = null, $categoryparams = null, $dummy = null )
    {
        $userid = qa_get_logged_in_userid();


        //	Chop down to size, get user information for display

        if ( isset( $pagesize ) )
            $posts = array_slice( $posts, 0, $pagesize );

        $usershtml = qa_userids_handles_html( qa_any_get_userids_handles( $posts ) );


        //	Prepare content for theme

        $qa_content = qa_content_prepare( true, array_keys( qa_category_path( $navcategories, $categoryid ) ) );

        $qa_content[ 'q_list' ][ 'form' ] = array(
            'tags'   => 'method="post" action="' . qa_self_html() . '"',

            'hidden' => array(
                'code' => qa_get_form_security_code( 'blog_vote' ),
            ),
        );

        $qa_content[ 'q_list' ][ 'qs' ] = array();

        if ( count( $posts ) ) {
            $qa_content[ 'title' ] = $sometitle;

            $defaults = qas_blog_post_html_defaults( 'B', true, true );

            foreach ( $posts as $post ) {

                $fields = qas_blog_any_to_b_html_fields( $post, $userid, qa_cookie_get(), $usershtml, null, qas_blog_post_html_options( $post, $defaults ) );

                if ( !empty( $fields[ 'raw' ][ 'closedbyid' ] ) ) {
                    $fields[ 'closed' ] = array(
                        'state' => qa_lang_html( 'main/closed' ),
                    );
                }

                $qa_content[ 'q_list' ][ 'qs' ][] = $fields;
            }

        } else
            $qa_content[ 'title' ] = $nonetitle;
        
        if ( isset( $userid ) && isset( $categoryid ) ) {
            $favoritemap = qas_blog_get_favorite_non_bs_map();
            $categoryisfavorite = @$favoritemap[ 'category' ][ $navcategories[ $categoryid ][ 'backpath' ] ];

            $qa_content[ 'favorite' ] = qas_blog_favorite_form( QAS_BLOG_ENTITY_CATEGORY, $categoryid, $categoryisfavorite,
                qa_lang_sub( $categoryisfavorite ? 'main/remove_x_favorites' : 'main/add_category_x_favorites', $navcategories[ $categoryid ][ 'title' ] ) );
        }

        if ( isset( $count ) && isset( $pagesize ) )
            $qa_content[ 'page_links' ] = qa_html_page_links( qa_request(), $start, $pagesize, $count, qa_opt( 'pages_prev_next' ), $pagelinkparams );

        if ( empty( $qa_content[ 'page_links' ] ) )
            $qa_content[ 'suggest_next' ] = $suggest;

        if ( qa_using_categories() && count( $navcategories ) && isset( $categorypathprefix ) )
            $qa_content[ 'navigation' ][ 'cat' ] = qa_category_navigation( $navcategories, $categoryid, $categorypathprefix, $categoryqcount, $categoryparams );

        return $qa_content;
    }

    /**
     * Return the sub navigation structure common to question listing pages
     *
     * @param $sort
     * @param $categoryslugs
     *
     * @return array
     */
    function qas_blogs_sub_navigation( $sort, $categoryslugs )
    {
        $request = qas_get_blog_url_sub( qas_blog_url_plural_structure() );

        if ( isset( $categoryslugs ) ) {
            foreach ( $categoryslugs as $slug ) {
                $request .= '/' . $slug;
            }
        }

        $navigation = array(
            'featured' => array(
                'label' => qa_lang( 'qas_blog/nav_featured' ),
                'url'   => qa_path_html( $request, array( 'sort' => 'featured' ) ),
            ),

            'recent'   => array(
                'label' => qa_lang( 'main/nav_most_recent' ),
                'url'   => qa_path_html( $request, array( 'sort' => 'recent' ) ),
            ),

            'views'    => array(
                'label' => qa_lang( 'main/nav_most_views' ),
                'url'   => qa_path_html( $request, array( 'sort' => 'views' ) ),
            ),
        );

        if ( isset( $navigation[ $sort ] ) )
            $navigation[ $sort ][ 'selected' ] = true;
        else {
            $default_sort = qas_blog_get_default_sorting();
            if ( !empty( $default_sort ) ) {
                $navigation[ $default_sort ][ 'selected' ] = true;
            } else
                $navigation[ 'recent' ][ 'selected' ] = true;
        }

        if ( !empty( $categoryslugs ) && @$navigation[ 'featured' ][ 'selected' ] ) {
            $navigation[ 'featured' ][ 'selected' ] = false;
            $navigation[ 'recent' ][ 'selected' ] = true;
        }

        if ( !qa_opt( 'qas_blog_do_count_p_views' ) )
            unset( $navigation[ 'views' ] );

        if ( !qas_is_featured_posts_enabled() )
            unset( $navigation[ 'featured' ] );

        return $navigation;
    }
    /*
        Omit PHP closing tag to help avoid accidental output
    */
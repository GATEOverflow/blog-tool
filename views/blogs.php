<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    qa_set_template( 'blogs' );
    $categoryslugs = qa_request_parts( 1 );
    $countslugs = count( $categoryslugs );

    $sort = ( $countslugs && !QA_ALLOW_UNINDEXED_QUERIES ) ? null : qa_get( 'sort' );
    $start = qa_get_start();
    $userid = qa_get_logged_in_userid();

    if ( qas_is_featured_posts_enabled() && empty( $sort ) && !$countslugs ) {

        $sort = qas_blog_get_default_sorting();

    } else if ( !qas_is_featured_posts_enabled() && $sort === 'featured' ) {
        //if the featured is not enabled and clicked from history then unset this immidiately
        $sort = '';
    }

    //	Get list of questions, plus category information

    switch ( $sort ) {
        case 'featured':
            $selectsort = 'created';
            break;

        case 'views':
            $selectsort = 'views';
            break;

        default:
            $selectsort = 'created';
            break;
    }

    $featured_page = ( $sort === 'featured' && qas_is_featured_posts_enabled() && !$countslugs );


    if ( $featured_page ) {
        $featured_post_ids = qas_blog_get_all_featured_post_ids();
        $featured_post_count = count( $featured_post_ids );
        if ( count( $featured_post_ids ) ) {
            $featred_select_spec = qas_blog_db_featured_posts_selectspec( $featured_post_ids, qa_opt_if_loaded( 'qas_blog_featured_page_size_ps' ), $start );
        } else {
            $featred_select_spec = null;
        }
    }

    list( $posts, $categories, $categoryid ) = qa_db_select_with_pending(
        $featured_page ? $featred_select_spec : qas_blog_db_blogs_selectspec( $userid, $selectsort, $start, $categoryslugs, null, false, true, qa_opt_if_loaded( 'qas_blog_page_size_ps' ) ),
        qas_blog_db_category_nav_selectspec( $categoryslugs, false, false, true ),
        $countslugs ? qas_blog_db_slugs_to_category_id_selectspec( $categoryslugs ) : null
    );

    if ( $countslugs ) {
        if ( !isset( $categoryid ) )
            return include QA_INCLUDE_DIR . 'qa-page-not-found.php';

        $categorytitlehtml = qa_html( $categories[ $categoryid ][ 'title' ] );
        $nonetitle = qa_lang_html_sub( 'qas_blog/no_posts_in_x', $categorytitlehtml );

    } else if ( $featured_page ) {
        $nonetitle = qa_lang_html( 'qas_blog/no_featured_posts_found' );
    } else
        $nonetitle = qa_lang_html( 'qas_blog/no_posts_found' );

    switch ( $sort ) {
        case 'featured':
            if ( !$countslugs )
                $sometitle = qa_lang_html( 'qas_blog/featured_posts_title' );
            else
                $sometitle = $countslugs ? qa_lang_html_sub( 'qas_blog/recent_posts_in_x', $categorytitlehtml ) : qa_lang_html( 'qas_blog/recent_posts_title' );

            break;

        case 'views':
            $sometitle = $countslugs ? qa_lang_html_sub( 'qas_blog/viewed_posts_in_x', $categorytitlehtml ) : qa_lang_html( 'qas_blog/viewed_posts_title' );
            break;

        case 'recent':
            $sometitle = $countslugs ? qa_lang_html_sub( 'qas_blog/recent_posts_in_x', $categorytitlehtml ) : qa_lang_html( 'qas_blog/recent_posts_title' );
            break;

        default:
            $linkparams = array();
            $default_sort = qas_blog_get_default_sorting();

            if ( $default_sort === 'featured' && !$countslugs ) {

                $featured_page = true;
                $sometitle = qa_lang_html( 'qas_blog/featured_posts_title' );

            } else if ( $default_sort === 'recent' ) {

                $sometitle = $countslugs ? qa_lang_html_sub( 'qas_blog/recent_posts_in_x', $categorytitlehtml ) : qa_lang_html( 'qas_blog/recent_posts_title' );

            } else if ( $default_sort === 'views' ) {

                $sometitle = $countslugs ? qa_lang_html_sub( 'qas_blog/viewed_posts_in_x', $categorytitlehtml ) : qa_lang_html( 'qas_blog/viewed_posts_title' );

            } else {

                $sometitle = $countslugs ? qa_lang_html_sub( 'qas_blog/recent_posts_in_x', $categorytitlehtml ) : qa_lang_html( 'qas_blog/recent_posts_title' );

            }

            break;
    }

    $categorypathprefix = qas_get_blog_url_sub( qas_blog_url_plural_structure( '/' ) ); // this default is applied if sorted not by recent

    $feedpathprefix = null;
    $linkparams = array( 'sort' => $sort );

    $total_count = $featured_page ? $featured_post_count : ( $countslugs ? $categories[ $categoryid ][ 'qcount' ] : qa_opt( 'cache_blog_pcount' ) );

//	Prepare and return content for theme

    $qa_content = qas_blog_list_page_content(
        (array) $posts, // posts
        $featured_page ? qa_opt( 'qas_blog_featured_page_size_ps' ) : qa_opt( 'qas_blog_page_size_ps' ), // posts per page
        $start, // start offset
        $total_count, // total count
        $sometitle, // title if some posts
        $nonetitle, // title if no posts
        $categories, // categories for navigation
        $categoryid, // selected category id
        true, // show question counts in category navigation
        $categorypathprefix, // prefix for links in category navigation
        $feedpathprefix, // prefix for RSS feed paths
        $countslugs ? qa_html_suggest_qs_tags( qas_blog_using_tags() ) : qa_html_suggest_ask( $categoryid ), // suggest what to do next
        $linkparams, // extra parameters for page links
        $linkparams // category nav params
    );

    if ( QA_ALLOW_UNINDEXED_QUERIES || !$countslugs )
        $qa_content[ 'navigation' ][ 'sub' ] = qas_blogs_sub_navigation( $sort, $categoryslugs );

    //check and add the list layout information
    if ( $featured_page && qas_is_grid_view_enabled() && !$countslugs )
        $qa_content[ 'grid_view' ] = true;
    else
        $qa_content[ 'grid_view' ] = false;

    return $qa_content;


    /*
        Omit PHP closing tag to help avoid accidental output
    */
<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    $categoryslugs = qa_request_parts( 2 );
    $countslugs = count( $categoryslugs );
    $userid = qa_get_logged_in_userid();


//	Get list of comments with related questions, plus category information

    list( $posts, $categories, $categoryid ) = qa_db_select_with_pending(
        qas_blog_db_recent_c_bs_selectspec( $userid, 0, $categoryslugs ),
        qas_blog_db_category_nav_selectspec( $categoryslugs, false, false, true ),
        $countslugs ? qas_blog_db_slugs_to_category_id_selectspec( $categoryslugs ) : null
    );

    if ( $countslugs ) {
        if ( !isset( $categoryid ) )
            return include QA_INCLUDE_DIR . 'qa-page-not-found.php';

        $categorytitlehtml = qa_html( $categories[ $categoryid ][ 'title' ] );
        $sometitle = qas_blog_lang_html( 'recent_cs_in_x', $categorytitlehtml );
        $nonetitle = qa_lang_html_sub( 'main/no_comments_in_x', $categorytitlehtml );

    } else {
        $sometitle = qas_blog_lang_html('recent_cs_title' );
        $nonetitle = qa_lang_html( 'main/no_comments_found' );
    }


//	Prepare and return content for theme

    return qas_blog_list_page_content(
        qa_any_sort_and_dedupe( (array)$posts ), // questions
        qa_opt( 'page_size_activity' ), // questions per page
        0, // start offset
        null, // total count (null to hide page links)
        $sometitle, // title if some questions
        $nonetitle, // title if no questions
        $categories, // categories for navigation
        $categoryid, // selected category id
        false, // show question counts in category navigation
        'comments/', // prefix for links in category navigation
        null, // prefix for RSS feed paths (null to hide)
        qa_html_suggest_qs_tags( qas_blog_using_tags(), qas_blog_path_html_plural( qa_category_path_request( $categories, $categoryid ) ) ) // suggest what to do next
    );


    /*
        Omit PHP closing tag to help avoid accidental output
    */
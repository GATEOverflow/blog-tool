<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    qa_set_template( 'blog-search' );
//	Perform the search if appropriate

    if ( strlen( qa_get( 'q' ) ) ) {

        //	Pull in input parameters

        $inquery = trim( qa_get( 'q' ) );
        $userid = qa_get_logged_in_userid();
        $start = qa_get_start();

        $display = qa_opt_if_loaded( 'page_size_search' );
        $count = 2 * ( isset( $display ) ? $display : QA_DB_RETRIEVE_QS_AS ) + 1;
        // get enough results to be able to give some idea of how many pages of search results there are

        //	Perform the search using appropriate module

        $results = qas_blog_get_search_results( $inquery, $start, $count, $userid, false, false );

        //	Count and truncate results

        $pagesize = qa_opt( 'page_size_search' );
        $gotcount = count( $results );
        $results = array_slice( $results, 0, $pagesize );

        //	Retrieve extra information on users

        $fullposts = array();

        foreach ( $results as $result )
            if ( isset( $result[ 'question' ] ) )
                $fullposts[] = $result[ 'question' ];

        $usershtml = qa_userids_handles_html( $fullposts );

        //	Report the search event

        qa_report_event( 'qas_blog_search', $userid, qa_get_logged_in_handle(), qa_cookie_get(), array(
            'query' => $inquery,
            'start' => $start,
        ) );
    }


//	Prepare content for theme

    $qa_content = qa_content_prepare( true );

    if ( strlen( qa_get( 'q' ) ) ) {
        $qa_content[ 'search' ][ 'value' ] = qa_html( $inquery );

        if ( count( $results ) )
            $qa_content[ 'title' ] = qa_lang_html_sub( 'main/results_for_x', qa_html( $inquery ) );
        else
            $qa_content[ 'title' ] = qa_lang_html_sub( 'main/no_results_for_x', qa_html( $inquery ) );

        $qa_content[ 'q_list' ][ 'form' ] = array(
            'tags'   => 'method="post" action="' . qa_self_html() . '"',

            'hidden' => array(
                'code' => qa_get_form_security_code( 'vote' ),
            ),
        );

        $qa_content[ 'q_list' ][ 'qs' ] = array();

        $qdefaults = qas_blog_post_html_defaults( 'B' );

        foreach ( $results as $result )
            if ( !isset( $result[ 'question' ] ) ) { // if we have any non-question results, display with less statistics
                $qdefaults[ 'voteview' ] = false;
                $qdefaults[ 'answersview' ] = false;
                $qdefaults[ 'viewsview' ] = false;
                break;
            }

        foreach ( $results as $result ) {
            if ( isset( $result[ 'question' ] ) )
                $fields = qas_blog_post_html_fields( $result[ 'question' ], $userid, qa_cookie_get(),
                    $usershtml, null, qas_blog_post_html_options( $result[ 'question' ], $qdefaults ) );

            elseif ( isset( $result[ 'url' ] ) )
                $fields = array(
                    'what'       => qa_html( $result[ 'url' ] ),
                    'meta_order' => qa_lang_html( 'main/meta_order' ),
                );

            else
                continue; // nothing to show here

            if ( isset( $qdefaults[ 'blockwordspreg' ] ) )
                $result[ 'title' ] = qa_block_words_replace( $result[ 'title' ], $qdefaults[ 'blockwordspreg' ] );

            $fields[ 'title' ] = qa_html( $fields[ 'title' ] );
            $fields[ 'url' ] = qa_html( $fields[ 'url' ] );

            $qa_content[ 'q_list' ][ 'qs' ][] = $fields;
        }

        $qa_content[ 'page_links' ] = qa_html_page_links( qa_request(), $start, $pagesize, $start + $gotcount,
            qa_opt( 'pages_prev_next' ), array( 'q' => $inquery ), $gotcount >= $count );

        if ( empty( $qa_content[ 'page_links' ] ) )
            $qa_content[ 'suggest_next' ] = qa_html_suggest_qs_tags( qa_using_tags() );

    } else
        $qa_content[ 'error' ] = qa_lang_html( 'main/search_explanation' );


    return $qa_content;


    /*
        Omit PHP closing tag to help avoid accidental output
    */
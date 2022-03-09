<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    $categoryslugs = qa_request_parts( 2 );

    $countslugs = count( $categoryslugs );


//	Get information about appropriate categories and redirect to questions page if category has no sub-categories

    $userid = qa_get_logged_in_userid();
    list( $categories, $categoryid, $favoritecats ) = qa_db_select_with_pending(
        qas_blog_db_category_nav_selectspec( $categoryslugs, false, false, true ),
        $countslugs ? qas_blog_db_slugs_to_category_id_selectspec( $categoryslugs ) : null,
        isset( $userid ) ? qa_db_user_favorite_categories_selectspec( $userid ) : null
    );

    if ( $countslugs && !isset( $categoryid ) )
        return include QA_INCLUDE_DIR . 'qa-page-not-found.php';


//	Function for recursive display of categories

    function qas_blog_category_nav_to_browse( &$navigation, $categories, $categoryid, $favoritemap )
    {
        foreach ( $navigation as $key => $navlink ) {
            $category = $categories[ $navlink[ 'categoryid' ] ];

            if ( !$category[ 'childcount' ] )
                unset( $navigation[ $key ][ 'url' ] );
            elseif ( $navlink[ 'selected' ] ) {
                $navigation[ $key ][ 'state' ] = 'open';
                $navigation[ $key ][ 'url' ] = qa_path_html( qas_get_blog_url_sub( '^/categories' ) . qa_category_path_request( $categories, $category[ 'parentid' ] ) );
            } else
                $navigation[ $key ][ 'state' ] = 'closed';

            if ( @$favoritemap[ $navlink[ 'categoryid' ] ] )
                $navigation[ $key ][ 'favorited' ] = true;

            $navigation[ $key ][ 'note' ] = '';

            $navigation[ $key ][ 'note' ] .=
                ' - <a href="' . qa_path_html( qas_get_blog_url_sub( qas_blog_url_plural_structure( '/' ) ) . implode( '/', array_reverse( explode( '/', $category[ 'backpath' ] ) ) ) ) . '">' . ( ( $category[ 'qcount' ] == 1 )
                    ? qa_lang_html_sub( 'qas_blog/1_post', '1', '1' )
                    : qa_lang_html_sub( 'qas_blog/x_posts', number_format( $category[ 'qcount' ] ) )
                ) . '</a>';

            if ( strlen( $category[ 'content' ] ) )
                $navigation[ $key ][ 'note' ] .= qa_html( ' - ' . $category[ 'content' ] );

            if ( isset( $navlink[ 'subnav' ] ) )
                qas_blog_category_nav_to_browse( $navigation[ $key ][ 'subnav' ], $categories, $categoryid, $favoritemap );
        }
    }


//	Prepare content for theme

    $qa_content = qa_content_prepare( false, array_keys( qa_category_path( $categories, $categoryid ) ) );

    $qa_content[ 'title' ] = qa_lang_html( 'misc/browse_categories' );

    if ( count( $categories ) ) {
        $navigation = qa_category_navigation( $categories, $categoryid, qas_get_blog_url_sub( '^/categories/' ), false );

        unset( $navigation[ 'all' ] );

        $favoritemap = array();
        if ( isset( $favoritecats ) )
            foreach ( $favoritecats as $category )
                $favoritemap[ $category[ 'categoryid' ] ] = true;

        qas_blog_category_nav_to_browse( $navigation, $categories, $categoryid, $favoritemap );

        $qa_content[ 'nav_list' ] = array(
            'nav'  => $navigation,
            'type' => 'browse-cat',
        );

    } else {
        $qa_content[ 'title' ] = qa_lang_html( 'main/no_categories_found' );
        $qa_content[ 'suggest_next' ] = qa_html_suggest_qs_tags( qas_blog_using_tags() );
    }


    return $qa_content;


    /*
        Omit PHP closing tag to help avoid accidental output
    */
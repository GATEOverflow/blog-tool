<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    $categoryid = qa_post_text( 'categoryid' );
    if ( !strlen( $categoryid ) )
        $categoryid = null;

    list( $fullcategory, $categories ) = qa_db_select_with_pending(
        qas_blog_db_full_category_selectspec( $categoryid, true ),
        qas_blog_db_category_sub_selectspec( $categoryid )
    );

    echo "QA_AJAX_RESPONSE\n1\n";

    echo qa_html( strtr( @$fullcategory[ 'content' ], "\r\n", '  ' ) ); // category description

    foreach ( $categories as $category )
        echo "\n" . $category[ 'categoryid' ] . '/' . $category[ 'title' ]; // subcategory information


    /*
        Omit PHP closing tag to help avoid accidental output
    */
<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../../' );
        exit;
    }

    return array(
        'qas_blog_warn_on_leave' => (bool) qa_opt( 'qas_blog_warn_on_leave' ),
    );

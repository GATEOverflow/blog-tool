<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    return array(
        'are_you_sure'             => qas_blog_js_lang( 'are_you_sure' ),
        'warning_message_on_leave' => qas_blog_js_lang( 'warning_message_on_leave' ),
    );


    /*
        Omit PHP closing tag to help avoid accidental output
    */
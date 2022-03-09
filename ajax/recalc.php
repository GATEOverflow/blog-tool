<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    if ( qa_get_logged_in_level() >= QA_USER_LEVEL_ADMIN ) {

        if ( !qa_check_form_security_code( 'admin/blog_recalc', qa_post_text( 'code' ) ) ) {
            $state = '';
            $message = qa_lang( 'misc/form_security_reload' );

        } else {
            $state = qa_post_text( 'state' );
            $stoptime = time() + 3;

            while ( qas_blog_recalc_perform_step( $state ) && ( time() < $stoptime ) )
                ;

            $message = qas_blog_recalc_get_message( $state );
        }

    } else {
        $state = '';
        $message = qa_lang( 'admin/no_privileges' );
    }


    echo "QA_AJAX_RESPONSE\n1\n" . $state . "\n" . qa_html( $message );


    /*
        Omit PHP closing tag to help avoid accidental output
    */
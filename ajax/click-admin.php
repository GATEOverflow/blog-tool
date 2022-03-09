<?php

    $entityid = qa_post_text( 'entityid' );
    $action = qa_post_text( 'action' );

    if ( !qa_check_form_security_code( 'admin/blog_click', qa_post_text( 'code' ) ) )
        echo "QA_AJAX_RESPONSE\n0\n" . qa_lang( 'misc/form_security_reload' );
    elseif ( qas_blog_admin_single_click( $entityid, $action ) ) // permission check happens in here
        echo "QA_AJAX_RESPONSE\n1\n";
    else
        echo "QA_AJAX_RESPONSE\n0\n" . qa_lang( 'main/general_error' );


    /*
        Omit PHP closing tag to help avoid accidental output
    */
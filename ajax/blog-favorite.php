<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    $entitytype = qa_post_text( 'entitytype' );
    $entityid = qa_post_text( 'entityid' );
    $setfavorite = qa_post_text( 'favorite' );
    $userid = qa_get_logged_in_userid();

    if ( !qa_check_form_security_code( 'qas-blog-favorite-' . $entitytype . '-' . $entityid, qa_post_text( 'code' ) ) )
        echo "QA_AJAX_RESPONSE\n0\n" . qa_lang( 'misc/form_security_reload' );

    elseif ( isset( $userid ) ) {
        $cookieid = qa_cookie_get();

        qas_blog_user_favorite_set( $userid, qa_get_logged_in_handle(), $cookieid, $entitytype, $entityid, $setfavorite );

        $favoriteform = qas_blog_favorite_form( $entitytype, $entityid, $setfavorite, qa_lang( $setfavorite ? 'main/remove_favorites' : 'main/add_favorites' ) );

        $themeclass = qa_load_theme_class( qa_get_site_theme(), 'ajax-favorite', null, null );
        $themeclass->initialize();
        
        echo "QA_AJAX_RESPONSE\n1\n";

        $themeclass->favorite_inner_html( $favoriteform );
    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */
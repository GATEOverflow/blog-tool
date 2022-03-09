<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    //	Check admin privileges (do late to allow one DB query)

    if ( !qa_admin_check_privileges( $qa_content ) )
        return $qa_content;


    //	Prepare content for theme

    $qa_content = qa_content_prepare();

    $qa_content[ 'title' ] = qa_lang_html( 'qas_admin/admin_title' ) . ' - ' . qa_lang_html( 'admin/stats_title' );

    $qa_content[ 'error' ] = qa_admin_page_error();

    $qa_content[ 'form_2' ] = array(
        'tags'    => 'method="post" action="' . qa_path_html( qas_get_blog_url_sub( 'admin/^/recalc' ) ) . '"',

        'title'   => qa_lang_html( 'admin/database_cleanup' ),

        'style'   => 'basic',

        'buttons' => array(
            'recount_posts'     => array(
                'label' => qa_lang_html( 'admin/recount_posts' ),
                'tags'  => 'name="dorecountposts" onclick="return qas_blog_recalc_click(this.name, this, ' . qa_js( qa_lang( 'admin/recount_posts_stop' ) ) . ', \'recount_posts_note\');"',
                'note'  => '<span id="recount_posts_note"></span>',
            ),

            'reindex_content'   => array(
                'label' => qa_lang_html( 'admin/reindex_content' ),
                'tags'  => 'name="doreindexcontent" onclick="return qas_blog_recalc_click(this.name, this, ' . qa_js( qa_lang( 'admin/reindex_content_stop' ) ) . ', \'reindex_content_note\');"',
                'note'  => '<span id="reindex_content_note"></span>',
            ),

            'recalc_categories' => array(
                'label' => qa_lang_html( 'admin/recalc_categories' ),
                'tags'  => 'name="dorecalccategories" onclick="return qas_blog_recalc_click(this.name, this, ' . qa_js( qa_lang( 'admin/recalc_stop' ) ) . ', \'recalc_categories_note\');"',
                'note'  => '<span id="recalc_categories_note">' . qa_lang_html( 'admin/recalc_categories_note' ) . '</span>',
            ),

            'delete_hidden'     => array(
                'label' => qa_lang_html( 'admin/delete_hidden' ),
                'tags'  => 'name="dodeletehidden" onclick="return qas_blog_recalc_click(this.name, this, ' . qa_js( qa_lang( 'admin/delete_stop' ) ) . ', \'delete_hidden_note\');"',
                'note'  => '<span id="delete_hidden_note">' . qa_lang_html( 'qas_admin/delete_hidden_note' ) . '</span>',
            ),
        ),

        'hidden'  => array(
            'code' => qa_get_form_security_code( 'admin/blog_recalc' ),
        ),
    );

    if ( !qas_blog_using_categories() )
        unset( $qa_content[ 'form_2' ][ 'buttons' ][ 'recalc_categories' ] );

    $qa_content[ 'script_rel' ][] = qas_blog_plugin_folder() . '/js/blog-admin.js';

    $qa_content[ 'script_var' ][ 'qa_warning_recalc' ] = qa_lang( 'admin/stop_recalc_warning' );

    $qa_content[ 'navigation' ][ 'sub' ] = qa_admin_sub_navigation();


    return $qa_content;


    /*
        Omit PHP closing tag to help avoid accidental output
    */
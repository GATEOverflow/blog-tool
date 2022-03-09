<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../../' );
        exit;
    }

    function qas_blog_load_q2a_files( $files_or_file )
    {
        if ( is_array( $files_or_file ) && count( $files_or_file ) ) {
            foreach ( $files_or_file as $file ) {
                require_once QA_INCLUDE_DIR . $file;
            }
        } else {
            require_once QA_INCLUDE_DIR . $files_or_file;
        }
    }

    function qas_blog_load_blog_plugin_files( $files_or_file )
    {
        if ( is_array( $files_or_file ) && count( $files_or_file ) ) {
            foreach ( $files_or_file as $file ) {
                require_once QAS_BLOG_DIR . $file;
            }
        } else {
            require_once QAS_BLOG_DIR . $files_or_file;
        }
    }

    if ( qa_qa_version_below( '1.7.0' ) ) {
        //load the file for older qa version
        require_once QAS_BLOG_DIR . '/utils/autoload_16.php';
    } else {
        //load files for latest qa versions later than 1.7
        require_once QAS_BLOG_DIR . '/utils/autoload_17.php';
    }

    require_once QAS_BLOG_DIR . '/utils/autoload_blog_files.php';

<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../../' );
        exit;
    }

    if ( !class_exists( '\utilphp\util' ) ) {
        qas_blog_load_blog_plugin_files( '/libs/util.php' );
    }

    //load blog plugin related files
    $files = array(
        '/db/admin.php',
        '/db/selects.php',
        '/db/recalc.php',
        '/db/blog-create.php',
        '/db/blog-update.php',
        '/app/options.php',
        '/app/app.php',
        '/app/blog-create.php',
        '/app/blog-update.php',
        '/app/format.php',
        '/app/recalc.php',
        '/app/favorites.php',
        '/app/admin.php',
        '/app/blog-create.php',
        '/app/search.php',
        '/app/blog-list.php',
        '/app/blog-view.php',
        '/app/blog-submit.php',
        '/utils/functions.php',
    );

    qas_blog_load_blog_plugin_files( $files );

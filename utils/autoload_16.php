<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../../' );
        exit;
    }

    //load required Q2A base files
    $files = array(
        'qa-db.php',
        'qa-db-selects.php',
        'qa-db-admin.php',
        'qa-db-maxima.php',
        'qa-db-recalc.php',
        'qa-db-post-create.php',
        'qa-db-points.php',
        'qa-db-hotness.php',
        'qa-db-metas.php',
        'qa-db-post-update.php',
        'qa-db-install.php',
        'qa-db-users.php',
        'qa-db-votes.php',
        'qa-db-favorites.php',
        'qa-app-posts.php',
        'qa-app-admin.php',
        'qa-app-options.php',
        'qa-app-format.php',
        'qa-app-recalc.php',
        'qa-app-users.php',
        'qa-app-cookies.php',
        'qa-app-votes.php',
        'qa-app-limits.php',
        'qa-app-favorites.php',
        'qa-app-updates.php',
        'qa-app-post-create.php',
        'qa-app-emails.php',
        'qa-app-users.php',
        'qa-app-q-list.php',
        'qa-app-post-update.php',
        'qa-app-captcha.php',
        'qa-page-question-view.php',
        'qa-util-sort.php',
        'qa-util-string.php',
        'qa-page-question-submit.php',
    );
    
    qas_blog_load_q2a_files( $files );

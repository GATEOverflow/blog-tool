<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../../' );
        exit;
    }

    //load required Q2A base files
    $files = array(
        'qa-db.php',
        'db/selects.php',
        'db/admin.php',
        'db/maxima.php',
        'db/recalc.php',
        'db/post-create.php',
        'db/points.php',
        'db/hotness.php',
        'db/metas.php',
        'db/post-update.php',
        'db/install.php',
        'db/users.php',
        'db/votes.php',
        'db/favorites.php',
        'app/posts.php',
        'app/admin.php',
        'app/options.php',
        'app/format.php',
        'app/recalc.php',
        'app/users.php',
        'app/cookies.php',
        'app/votes.php',
        'app/limits.php',
        'app/favorites.php',
        'app/updates.php',
        'app/post-create.php',
        'app/emails.php',
        'app/users.php',
        'app/q-list.php',
        'app/post-update.php',
        'app/captcha.php',
        'pages/question-view.php',
        'util/sort.php',
        'util/string.php',
        'pages/question-submit.php',
    );
    
    qas_blog_load_q2a_files( $files );
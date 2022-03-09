<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    //	Check we have administrative privileges

    if ( !qa_admin_check_privileges( $qa_content ) )
        return $qa_content;


    //	Find out the operation

    $allowstates = array(
        'dorecountposts',
        'doreindexcontent',
        'dorecalccategories',
        'dodeletehidden',
    );

    $recalcnow = false;

    foreach ( $allowstates as $allowstate )
        if ( qa_post_text( $allowstate ) || qa_get( $allowstate ) ) {
            $state = $allowstate;
            $code = qa_post_text( 'code' );

            if ( isset( $code ) && qa_check_form_security_code( 'admin/blog_recalc', $code ) )
                $recalcnow = true;
        }

    if ( $recalcnow ) {
        ?>

        <html>
        <head>
            <meta http-equiv="content-type" content="text/html; charset=utf-8">
        </head>
        <body>
        <tt>

            <?php

                while ( $state ) {
                    set_time_limit( 60 );

                    $stoptime = time() + 2; // run in lumps of two seconds...

                    while ( qas_blog_recalc_perform_step( $state ) && ( time() < $stoptime ) )
                        ;

                    echo qa_html( qas_blog_recalc_get_message( $state ) ) . str_repeat( '    ', 1024 ) . "<br>\n";

                    flush();
                    sleep( 1 ); // ... then rest for one
                }

            ?>
        </tt>

        <a href="<?php echo qa_path_html( qas_get_blog_url_sub( 'admin/^/stats' ) ) ?>"><?php echo qa_lang_html( 'qas_admin/admin_title' ) . ' - ' . qa_lang_html( 'admin/stats_title' ) ?></a>
        </body>
        </html>

        <?php
        qa_exit();

    } elseif ( isset( $state ) ) {
        $qa_content = qa_content_prepare();

        $qa_content[ 'title' ] = qa_lang_html( 'admin/admin_title' );
        $qa_content[ 'error' ] = qa_lang_html( 'misc/form_security_again' );

        $qa_content[ 'form' ] = array(
            'tags'    => 'method="post" action="' . qa_self_html() . '"',

            'style'   => 'wide',

            'buttons' => array(
                'recalc' => array(
                    'tags'  => 'name="' . qa_html( $state ) . '"',
                    'label' => qa_lang_html( 'misc/form_security_again' ),
                ),
            ),

            'hidden'  => array(
                'code' => qa_get_form_security_code( 'admin/blog_recalc' ),
            ),
        );

        return $qa_content;

    } else {
        $qa_content = qa_content_prepare();

        $qa_content[ 'title' ] = qa_lang_html( 'admin/admin_title' );
        $qa_content[ 'error' ] = qa_lang_html( 'main/page_not_found' );

        return $qa_content;
    }


/*
	Omit PHP closing tag to help avoid accidental output
*/
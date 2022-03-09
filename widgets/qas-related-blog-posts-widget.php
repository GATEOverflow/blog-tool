<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    class qas_related_blog_posts_widget
    {

        public function allow_template( $template )
        {
            return ( $template == 'blog' );
        }


        public function allow_region( $region )
        {
            return in_array( $region, array( 'side', 'main', 'full' ) );
        }


        public function output_widget( $region, $place, $themeobject, $template, $request, $qa_content )
        {
            if ( @$qa_content[ 'q_view' ][ 'raw' ][ 'type' ] != 'B' ) // question might not be visible, etc...
                return;

            $postid = $qa_content[ 'q_view' ][ 'raw' ][ 'postid' ];

            $userid = qa_get_logged_in_userid();
            $cookieid = qa_cookie_get();

            $posts = qa_db_single_select( qas_blog_db_related_blogs_selectspec( $userid, $postid, qa_opt( 'qas_blog_related_post_widg_count' ) ) );

            $minscore = qa_match_to_min_score( qa_opt( 'match_related_qs' ) );

            foreach ( $posts as $key => $post )
                if ( $post[ 'score' ] < $minscore )
                    unset( $posts[ $key ] );

            $titlehtml = qa_lang_html( count( $posts ) ? 'qas_blog/related_posts_title' : 'qas_blog/no_related_posts_title' );

            if ( $region == 'side' ) {
                $themeobject->output(
                    '<div class="qa-related-qs">',
                    '<h2 style="margin-top:0; padding-top:0;">',
                    $titlehtml,
                    '</h2>'
                );

                $themeobject->output( '<ul class="qa-related-q-list">' );

                foreach ( $posts as $post )
                    $themeobject->output( '<li class="qa-related-q-item"><a href="' . qas_blog_post_path( $post[ 'postid' ], $post[ 'title' ], true ) . '">' . qa_html( $post[ 'title' ] ) . '</a></li>' );

                $themeobject->output(
                    '</ul>',
                    '</div>'
                );

            } else {
                $themeobject->output(
                    '<h2>',
                    $titlehtml,
                    '</h2>'
                );

                $q_list = array(
                    'form' => array(
                        'tags'   => 'method="post" action="' . qa_self_html() . '"',

                        'hidden' => array(
                            'code' => qa_get_form_security_code( 'vote' ),
                        ),
                    ),

                    'qs'   => array(),
                );

                $defaults = qas_blog_post_html_defaults( 'B' );
                $usershtml = qa_userids_handles_html( $posts );

                foreach ( $posts as $post )
                    $q_list[ 'qs' ][] = qas_blog_post_html_fields( $post, $userid, $cookieid, $usershtml, null, qas_blog_post_html_options( $post, $defaults ) );

                $themeobject->q_list_and_form( $q_list );
            }
        }

    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */
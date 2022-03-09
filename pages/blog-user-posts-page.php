<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    class qas_blog_user_posts
    {

        private $directory;
        private $urltoroot;


        public function load_module( $directory, $urltoroot )
        {
            $this->directory = $directory;
            $this->urltoroot = $urltoroot;
        }

        public function match_request( $request )
        {
            $requestparts = qa_request_parts();

            return ( !empty( $requestparts )
                && @$requestparts[ 0 ] === qas_get_blog_url_sub( 'user-' . qas_blog_url_plural_structure() )
                && !empty( $requestparts[ 1 ] )
            );
        }

        public function process_request( $request )
        {
            $handle = qa_request_part( 1 );

            if ( !strlen( $handle ) ) {
                $handle = qa_get_logged_in_handle();
                qa_redirect( isset( $handle ) ? 'user/' . $handle : 'users' );
            }

            if ( QA_FINAL_EXTERNAL_USERS ) {
                $userid = qa_handle_to_userid( $handle );
                if ( !isset( $userid ) )
                    return include QA_INCLUDE_DIR . 'qa-page-not-found.php';

                $usershtml = qa_get_users_html( array( $userid ), false, qa_path_to_root(), true );
                $userhtml = @$usershtml[ $userid ];

            } else
                $userhtml = qa_html( $handle );

            qa_set_template( 'blogs' );

            return require QAS_BLOG_DIR . '/views/blog-user-posts.php';
        }
    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */
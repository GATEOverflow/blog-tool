<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    class qas_blog_post_single
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
                && @$requestparts[ 0 ] === qas_get_blog_url_base()
                && is_numeric( @$requestparts[ 1 ] )
            );
        }

        public function process_request( $request )
        {
            qa_set_template( 'blog' );

            return require QAS_BLOG_DIR . '/views/blog-post.php';
        }
    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */
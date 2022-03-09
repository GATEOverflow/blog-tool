<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    class qas_blog_new
    {

        private $directory;
        private $urltoroot;


        public function load_module( $directory, $urltoroot )
        {
            $this->directory = $directory;
            $this->urltoroot = $urltoroot;
        }

        public function suggest_requests() // for display in admin interface
        {
            return array(
                array(
                    'title'   => qa_lang_html( 'qas_admin/new_blog' ),
                    'request' => qas_get_blog_url_base() . '/new',
                    'nav'     => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
                ),
            );
        }

        public function match_request( $request )
        {
            $requestparts = qa_request_parts();

            return ( !empty( $requestparts )
                && @$requestparts[ 0 ] === qas_get_blog_url_base()
                && @$requestparts[ 1 ] === 'new'
            );
        }

        public function process_request( $request )
        {
            qa_set_template( 'blog-new' );

            return require QAS_BLOG_DIR . '/views/blog-new.php';
        }
    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */
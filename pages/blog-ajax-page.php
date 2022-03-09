<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    class qas_blog_ajax
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
            return array();
        }


        public function match_request( $request )
        {
            $requestparts = qa_request_parts();

            return ( !empty( $requestparts )
                && @$requestparts[ 0 ] === qas_get_blog_url_base()
                && @$requestparts[ 1 ] === 'ajax'
            );
        }

        public function process_request( $request )
        {
            //  Output this header as early as possible
            header( 'Content-Type: text/plain; charset=utf-8' );
            //  Ensure no PHP errors are shown in the Ajax response
            @ini_set( 'display_errors', 0 );

            qa_report_process_stage( 'init_ajax' );

            qa_set_request( qa_post_text( 'qa_request' ), qa_post_text( 'qa_root' ) );

            $_GET = array(); // for qa_self_html()

            $operation = qa_post_text( 'qa_blog_operation' );

            if ( !empty( $operation ) ) {

                //load the function file for ajax calls
                require_once QAS_BLOG_DIR. '/ajax/functions.php';

                $action = 'qas_blog_' . $operation;

                if ( function_exists( $action ) ) {
                    //invoke the action if the hook exists
                    $action();
                }

            }

        }
    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */
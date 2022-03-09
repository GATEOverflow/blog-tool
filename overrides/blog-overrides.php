<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }
    /**
     * adds few more routes for the default routing
     *
     * @return mixed
     */
    function qa_page_routing()
    {
        $pages = qa_page_routing_base();

        // add your additional pages here for loading the blogs
        $new_pages = qas_blog_page_routing();

        return $pages + $new_pages;
    }

    /**
     * adds few more navigation links for admin panel
     *
     * @return mixed
     */
    function qa_admin_sub_navigation()
    {

        $navigation = qa_admin_sub_navigation_base();
        $blog_navigation = qas_blog_admin_sub_navigation();

        return $navigation + $blog_navigation;

    }

    /**
     * adds blog admin pages to the request handlers
     *
     * @return mixed
     */
    function qa_get_request_content()
    {

        $requestlower = strtolower( qa_request() );
        $requestparts = qa_request_parts();
        $firstlower = strtolower( @$requestparts[ 0 ] );
        $secondlower = strtolower( @$requestparts[ 1 ] );
        $routing = qa_page_routing();

        $route_part = '';

        if ( !empty( $firstlower ) && !empty( $secondlower ) ) {
            $route_part = $firstlower . '/' . $secondlower . '/';
        }

        if ( !isset( $routing[ $requestlower ] ) && $route_part === qas_get_blog_url_sub( 'admin/^/' ) ) {
            //for loading the default setting file
            qa_set_template( $firstlower );
            $qa_content = require QA_INCLUDE_DIR . $routing[ $route_part ];

            if ( $firstlower == 'admin' ) {
                $_COOKIE[ 'qa_admin_last' ] = $requestlower; // for navigation tab now...
                setcookie( 'qa_admin_last', $_COOKIE[ 'qa_admin_last' ], 0, '/', QA_COOKIE_DOMAIN ); // ...and in future
            }

        } else {
            //otherwise load the original qa_get_request_content function
            $qa_content = qa_get_request_content_base();
        }

        return $qa_content;
    }

    /**
     * Handels the favorite button click and invokes the base function after that
     */
    function qa_check_page_clicks()
    {
        global $qa_page_error_html;

        if ( qa_is_http_post() ) {
            foreach ( $_POST as $field => $value ) {
                if ( strpos( $field, 'blogfavorite_' ) === 0 ) { // blog favorites...
                    @list( $dummy, $entitytype, $entityid, $favorite ) = explode( '_', $field );

                    if ( isset( $entitytype ) && isset( $entityid ) && isset( $favorite ) ) {
                        if ( !qa_check_form_security_code( 'qas-blog-favorite-' . $entitytype . '-' . $entityid, qa_post_text( 'code' ) ) )
                            $qa_page_error_html = qa_lang_html( 'misc/form_security_again' );

                        else {
                            require_once QAS_BLOG_DIR . '/app/favorites.php';

                            qas_blog_user_favorite_set( qa_get_logged_in_userid(), qa_get_logged_in_handle(), qa_cookie_get(), $entitytype, $entityid, $favorite );
                            qa_redirect( qa_request(), $_GET );
                        }
                    }

                }
            }
        }

        qa_check_page_clicks_base();
    }
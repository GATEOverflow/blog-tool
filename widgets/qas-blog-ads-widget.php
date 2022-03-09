<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    class qas_blog_ads_widget
    {

        function allow_template( $template )
        {
            $allow = false;
            switch ( $template ) {
                case 'blogs':
                case 'blog':
                case 'blog-tag':
                case 'blog-tags':
                case 'blog-categories':
                    $allow = true;
                    break;
            }

            return $allow;
        }

        public function allow_region( $region )
        {
            return in_array( $region, array( 'side', 'main', 'full' ) );
        }


        public function output_widget( $region, $place, $themeobject, $template, $request, $qa_content )
        {
            if ( $region == 'side' ) {
                if ( qa_opt( 'qas_blog_adcode_w' ) && qa_opt( 'qas_blog_adcode_w_content' ) ) {
                    $themeobject->output( '<div class="qas-blog-ads">' );
                    $themeobject->output( qa_opt( 'qas_blog_adcode_w_content' ) );
                    $themeobject->output( '</div>' );
                }
            } else {
                if ( qa_opt( 'qas_blog_adcode_blog_top' ) && qa_opt( 'qas_blog_adcode_blog_top_content' ) ) {
                    $themeobject->output( '<div class="qas-blog-ads-full">' );
                    $themeobject->output( qa_opt( 'qas_blog_adcode_blog_top_content' ) );
                    $themeobject->output( '</div>' );
                }
            }
        }

    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */
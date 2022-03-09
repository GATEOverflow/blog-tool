<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    class blog_xml_sitemap
    {

        private $directory;
        private $urltoroot;

        public function load_module( $directory, $urltoroot )
        {
            $this->directory = $directory;
            $this->urltoroot = $urltoroot;
        }

        public function suggest_requests()
        {
            return array(
                array(
                    'title'   => qa_lang( 'qas_blog/xml_sitemap' ),
                    'request' => qas_get_blog_url_sub( '^/sitemap.xml' ),
                    'nav'     => null, // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
                ),
            );
        }


        public function match_request( $request )
        {
            return ( $request == qas_get_blog_url_sub( '^/sitemap.xml' ) );
        }


        public function process_request( $request )
        {
            @ini_set( 'display_errors', 0 ); // we don't want to show PHP errors inside XML

            $siteurl = qa_opt( 'site_url' );

            header( 'Content-type: text/xml; charset=utf-8' );

            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";


            //	posts pages

            if ( qa_opt( 'qas_blog_xml_sitemap_show_posts' ) ) {
                $hotstats = qa_db_read_one_assoc( qa_db_query_sub(
                    "SELECT MIN(hotness) AS base, MAX(hotness)-MIN(hotness) AS spread FROM ^blogs WHERE type='B'"
                ) );

                $nextpostid = 0;

                while ( 1 ) {
                    $posts = qa_db_read_all_assoc( qa_db_query_sub(
                        "SELECT postid, title, hotness FROM ^blogs WHERE postid>=# AND type='B' ORDER BY postid LIMIT 100",
                        $nextpostid
                    ) );

                    if ( !count( $posts ) )
                        break;

                    foreach ( $posts as $post ) {
                        $this->sitemap_output( qas_blog_request( $post[ 'postid' ], $post[ 'title' ] ),
                            0.1 + 0.9 * ( $post[ 'hotness' ] - $hotstats[ 'base' ] ) / ( 1 + $hotstats[ 'spread' ] ) );
                        $nextpostid = max( $nextpostid, $post[ 'postid' ] + 1 );
                    }
                }
            }

            //	Tag pages

            if ( qas_blog_using_tags() && qa_opt( 'qas_blog_xml_sitemap_show_tag_ps' ) ) {
                $nextwordid = 0;

                while ( 1 ) {
                    $tagwords = qa_db_read_all_assoc( qa_db_query_sub(
                        "SELECT wordid, word, tagcount FROM ^blog_words WHERE wordid>=# AND tagcount>0 ORDER BY wordid LIMIT 100",
                        $nextwordid
                    ) );

                    if ( !count( $tagwords ) )
                        break;
                    $tags_url = qas_get_blog_url_sub( '^/tag/' );
                    foreach ( $tagwords as $tagword ) {
                        $this->sitemap_output( $tags_url . $tagword[ 'word' ], 0.5 / ( 1 + ( 1 / $tagword[ 'tagcount' ] ) ) ); // priority between 0.25 and 0.5 depending on tag frequency
                        $nextwordid = max( $nextwordid, $tagword[ 'wordid' ] + 1 );
                    }
                }
            }


            //	Question list for each category

            if ( qas_blog_using_categories() && qa_opt( 'qas_blog_xml_sitemap_show_category_ps' ) ) {
                $nextcategoryid = 0;

                while ( 1 ) {
                    $categories = qa_db_read_all_assoc( qa_db_query_sub(
                        "SELECT categoryid, backpath FROM ^blog_categories WHERE categoryid>=# AND qcount>0 ORDER BY categoryid LIMIT 2",
                        $nextcategoryid
                    ) );

                    if ( !count( $categories ) )
                        break;
                    $plural_prefix = qas_get_blog_url_sub( qas_blog_url_plural_structure( '/' ) );
                    foreach ( $categories as $category ) {
                        $this->sitemap_output( $plural_prefix . implode( '/', array_reverse( explode( '/', $category[ 'backpath' ] ) ) ), 0.5 );
                        $nextcategoryid = max( $nextcategoryid, $category[ 'categoryid' ] + 1 );
                    }
                }
            }


            //	Pages in category browser

            if ( qas_blog_using_categories() && qa_opt( 'qas_blog_xml_sitemap_show_categories' ) ) {
                $this->sitemap_output( 'categories', 0.5 );

                $nextcategoryid = 0;

                while ( 1 ) { // only find categories with a child
                    $categories = qa_db_read_all_assoc( qa_db_query_sub(
                        "SELECT parent.categoryid, parent.backpath FROM ^blog_categories AS parent " .
                        "JOIN ^blog_categories AS child ON child.parentid=parent.categoryid WHERE parent.categoryid>=# GROUP BY parent.categoryid LIMIT 100",
                        $nextcategoryid
                    ) );

                    if ( !count( $categories ) )
                        break;

                    $category_url = qas_get_blog_url_sub( '^/categories/' );

                    foreach ( $categories as $category ) {
                        $this->sitemap_output( $category_url . implode( '/', array_reverse( explode( '/', $category[ 'backpath' ] ) ) ), 0.5 );
                        $nextcategoryid = max( $nextcategoryid, $category[ 'categoryid' ] + 1 );
                    }
                }
            }


            //	Finish up...

            echo "</urlset>\n";

            return null;
        }


        private function sitemap_output( $request, $priority )
        {
            echo "\t<url>\n" .
                "\t\t<loc>" . qa_xml( qa_path( $request, null, qa_opt( 'site_url' ) ) ) . "</loc>\n" .
                "\t\t<priority>" . max( 0, min( 1.0, $priority ) ) . "</priority>\n" .
                "\t</url>\n";
        }

    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */
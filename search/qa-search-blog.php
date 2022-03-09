<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    class qa_search_blog
    {

        public function index_blog_post( $postid, $type, $blog_postid, $parentid, $title, $content, $format, $text, $tagstring, $categoryid )
        {

            //	Get words from each textual element

            $titlewords = array_unique( qa_string_to_words( $title ) );
            $contentcount = array_count_values( qa_string_to_words( $text ) );
            $tagwords = array_unique( qa_string_to_words( $tagstring ) );
            $wholetags = array_unique( qa_tagstring_to_tags( $tagstring ) );

            //	Map all words to their word IDs

            $words = array_unique( array_merge( $titlewords, array_keys( $contentcount ), $tagwords, $wholetags ) );
            $wordtoid = qas_blog_db_word_mapto_ids_add( $words );

            //	Add to title words index

            $titlewordids = qa_array_filter_by_keys( $wordtoid, $titlewords );
            qas_blog_db_titlewords_add_post_wordids( $postid, $titlewordids );

            //	Add to content words index (including word counts)

            $contentwordidcounts = array();
            foreach ( $contentcount as $word => $count )
                if ( isset( $wordtoid[ $word ] ) )
                    $contentwordidcounts[ $wordtoid[ $word ] ] = $count;

            qas_blog_db_contentwords_add_post_wordidcounts( $postid, $type, $blog_postid, $contentwordidcounts );

            //	Add to tag words index

            $tagwordids = qa_array_filter_by_keys( $wordtoid, $tagwords );
            qas_blog_db_tagwords_add_post_wordids( $postid, $tagwordids );

            //	Add to whole tags index

            $wholetagids = qa_array_filter_by_keys( $wordtoid, $wholetags );
            qas_blog_db_posttags_add_post_wordids( $postid, $wholetagids );

            //	Update counts cached in database (will be skipped if qa_suspend_update_counts() was called

            qas_blog_db_word_titlecount_update( $titlewordids );
            qas_blog_db_word_contentcount_update( array_keys( $contentwordidcounts ) );
            qas_blog_db_word_tagwordcount_update( $tagwordids );
            qas_blog_db_word_tagcount_update( $wholetagids );
            qas_blog_db_tagcount_update();
        }


        public function unindex_blog_post( $postid )
        {
            $titlewordids = qas_blog_db_titlewords_get_post_wordids( $postid );
            qas_blog_db_titlewords_delete_post( $postid );
            qas_blog_db_word_titlecount_update( $titlewordids );

            $contentwordids = qas_blog_db_contentwords_get_post_wordids( $postid );
            qas_blog_db_contentwords_delete_post( $postid );
            qas_blog_db_word_contentcount_update( $contentwordids );

            $tagwordids = qas_blog_db_tagwords_get_post_wordids( $postid );
            qas_blog_db_tagwords_delete_post( $postid );
            qas_blog_db_word_tagwordcount_update( $tagwordids );

            $wholetagids = qas_blog_db_posttags_get_post_wordids( $postid );
            qas_blog_db_posttags_delete_post( $postid );
            qas_blog_db_word_tagcount_update( $wholetagids );
        }


        public function move_blog_post( $postid, $categoryid )
        {
            // for now, the blog search engine ignores categories
        }


        public function index_blog_page( $pageid, $request, $title, $content, $format, $text )
        {
            // for now, the blog search engine ignores custom pages
        }


        public function unindex_blog_page( $pageid )
        {
            // for now, the blog search engine ignores custom pages
        }


        public function process_blog_search( $query, $start, $count, $userid, $absoluteurls, $fullcontent )
        {
            $words = qa_string_to_words( $query );

            $posts = qa_db_select_with_pending(
                qas_blog_db_search_posts_selectspec( $userid, $words, $words, $words, $words, trim( $query ), $start, $fullcontent, $count )
            );

            $results = array();

            foreach ( $posts as $post ) {
                qas_blog_search_set_max_match( $post, $type, $postid ); // to link straight to best part

                $results[] = array(
                    'question'     => $post,
                    'match_type'   => $type,
                    'match_postid' => $postid,
                );
            }

            return $results;
        }

    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */
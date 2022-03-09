<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    /*
        A full list of redundant (non-normal) information in the database that can be recalculated:

        Recalculated in doreindexcontent:
        ================================
        ^blog_titlewords (all): index of words in titles of posts
        ^blog_contentwords (all): index of words in content of posts
        ^blog_tagwords (all): index of words in tags of posts (a tag can contain multiple words)
        ^blog_posttags (all): index tags of posts
        ^blog_words (all): list of words used for indexes
        ^options (title=cache_*): cached values for various things (e.g. counting questions)

        Recalculated in dorecalccategories:
        ===================================
        ^posts (categoryid): assign to answers and comments based on their antecedent question
        ^posts (catidpath1, catidpath2, catidpath3): hierarchical path to category ids (requires QA_CATEGORY_DEPTH=4)
        ^categories (qcount): number of (visible) questions in each category
        ^categories (backpath): full (backwards) path of slugs to that category

        [but these are not entirely redundant since they can contain historical information no longer in ^posts]
    */

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    /**
     * Advance the recalculation operation represented by $state by a single step.
     * $state can also be the name of a recalculation operation on its own.
     *
     * @param $state
     *
     * @return bool
     */
    function qas_blog_recalc_perform_step( &$state )
    {
        $continue = false;

        @list( $operation, $length, $next, $done ) = explode( "\t", $state );

        switch ( $operation ) {
            case 'doreindexcontent':
                qas_blog_recalc_transition( $state, 'doreindexcontent_pagereindex' );
                break;

            case 'doreindexcontent_pagereindex':
                qas_blog_recalc_transition( $state, 'doreindexcontent_postcount' );
                break;

            case 'doreindexcontent_postcount':
                qas_blog_recalc_transition( $state, 'doreindexcontent_postreindex' );
                break;

            case 'doreindexcontent_postreindex':
                $posts = qas_blog_db_posts_get_for_reindexing( $next, 10 );

                if ( count( $posts ) ) {
                    $lastpostid = max( array_keys( $posts ) );

                    qas_blog_db_prepare_for_reindexing( $next, $lastpostid );
                    qa_suspend_update_counts();

                    foreach ( $posts as $postid => $post ) {
                        qas_blog_post_unindex( $postid );
                        qas_blog_post_index( $postid, $post[ 'type' ], $post[ 'questionid' ], $post[ 'parentid' ], $post[ 'title' ], $post[ 'content' ],
                            $post[ 'format' ], qa_viewer_text( $post[ 'content' ], $post[ 'format' ] ), $post[ 'tags' ], $post[ 'categoryid' ] );
                    }

                    $next = 1 + $lastpostid;
                    $done += count( $posts );
                    $continue = true;

                } else {
                    qas_blog_db_truncate_indexes( $next );
                    qas_blog_recalc_transition( $state, 'doreindexposts_wordcount' );
                }
                break;

            case 'doreindexposts_wordcount':
                $wordids = qas_blog_db_words_prepare_for_recounting( $next, 1000 );

                if ( count( $wordids ) ) {
                    $lastwordid = max( $wordids );

                    qas_blog_db_words_recount( $next, $lastwordid );

                    $next = 1 + $lastwordid;
                    $done += count( $wordids );
                    $continue = true;

                } else {
                    qas_blog_db_tagcount_update();
                    qas_blog_recalc_transition( $state, 'doreindexposts_complete' );
                }
                break;

            case 'dorecountposts':
                qas_blog_recalc_transition( $state, 'dorecountposts_postcount' );
                break;

            case 'dorecountposts_postcount':
                qas_blog_db_post_count_update();
                qas_blog_db_ccount_update();

                qas_blog_recalc_transition( $state, 'dorecountposts_complete' );
                break;

            case 'dorecalccategories':
                qas_blog_recalc_transition( $state, 'dorecalccategories_postcount' );
                break;

            case 'dorecalccategories_postcount':
                qas_blog_db_ccount_update();

                qas_blog_recalc_transition( $state, 'dorecalccategories_postupdate' );
                break;

            case 'dorecalccategories_postupdate':
                $postids = qas_blog_db_posts_get_for_recategorizing( $next, 100 );

                if ( count( $postids ) ) {
                    $lastpostid = max( $postids );

                    qas_blog_db_posts_recalc_categoryid( $next, $lastpostid );
                    qas_blog_db_posts_calc_category_path( $next, $lastpostid );

                    $next = 1 + $lastpostid;
                    $done += count( $postids );
                    $continue = true;

                } else {
                    qas_blog_recalc_transition( $state, 'dorecalccategories_recount' );
                }
                break;

            case 'dorecalccategories_recount':
                $categoryids = qas_blog_db_categories_get_for_recalcs( $next, 10 );

                if ( count( $categoryids ) ) {
                    $lastcategoryid = max( $categoryids );

                    foreach ( $categoryids as $categoryid )
                        qas_blog_db_ifcategory_post_count_update( $categoryid );

                    $next = 1 + $lastcategoryid;
                    $done += count( $categoryids );
                    $continue = true;

                } else {
                    qas_blog_recalc_transition( $state, 'dorecalccategories_backpaths' );
                }
                break;

            case 'dorecalccategories_backpaths':
                $categoryids = qa_db_categories_get_for_recalcs( $next, 10 );

                if ( count( $categoryids ) ) {
                    $lastcategoryid = max( $categoryids );

                    qas_blog_db_categories_recalc_backpaths( $next, $lastcategoryid );

                    $next = 1 + $lastcategoryid;
                    $done += count( $categoryids );
                    $continue = true;

                } else {
                    qas_blog_recalc_transition( $state, 'dorecalccategories_complete' );
                }
                break;

            case 'dodeletehidden':
                qas_blog_recalc_transition( $state, 'dodeletehidden_comments' );
                break;

            case 'dodeletehidden_comments':
                $posts = qas_blog_db_posts_get_for_deleting( 'C', $next, 1 );

                if ( count( $posts ) ) {

                    $postid = $posts[ 0 ];

                    qas_blog_post_delete( $postid );

                    $next = 1 + $postid;
                    $done++;
                    $continue = true;

                } else
                    qas_blog_recalc_transition( $state, 'dodeletehidden_questions' );
                break;

            case 'dodeletehidden_questions':
                $posts = qas_blog_db_posts_get_for_deleting( 'B', $next, 1 );

                if ( count( $posts ) ) {

                    $postid = $posts[ 0 ];

                    qas_blog_post_delete( $postid );

                    $next = 1 + $postid;
                    $done++;
                    $continue = true;

                } else
                    qas_blog_recalc_transition( $state, 'dodeletehidden_complete' );
                break;

            default:
                $state = '';
                break;
        }

        if ( $continue )
            $state = $operation . "\t" . $length . "\t" . $next . "\t" . $done;

        return $continue && ( $done < $length );
    }

    /**
     * Change the $state to represent the beginning of a new $operation
     *
     * @param $state
     * @param $operation
     */
    function qas_blog_recalc_transition( &$state, $operation )
    {
        $length = qas_blog_recalc_stage_length( $operation );
        $next = ( QA_FINAL_EXTERNAL_USERS && ( $operation == 'dorecalcpoints_recalc' ) ) ? '' : 0;
        $done = 0;

        $state = $operation . "\t" . $length . "\t" . $next . "\t" . $done;
    }

    /**
     * Return how many steps there will be in recalculation $operation
     *
     * @param $operation
     *
     * @return null
     */
    function qas_blog_recalc_stage_length( $operation )
    {
        switch ( $operation ) {
            case 'doreindexcontent_pagereindex':
                $length = qa_db_count_pages();
                break;

            case 'doreindexcontent_postreindex':
                $length = qa_opt( 'cache_blog_pcount' ) + qa_opt( 'cache_blog_ccount' );
                break;

            case 'doreindexposts_wordcount':
                $length = qa_db_count_words();
                break;

            case 'dorecalcpoints_recalc':
                $length = qa_opt( 'cache_userpointscount' );
                break;

            case 'dorecountposts_votecount':
            case 'dorecountposts_acount':
            case 'dorecalccategories_postupdate':
                $length = qa_db_count_posts();
                break;

            case 'dorefillevents_refill':
                $length = qa_opt( 'cache_qcount' ) + qa_db_count_posts( 'Q_HIDDEN' );
                break;

            case 'dorecalccategories_recount':
            case 'dorecalccategories_backpaths':
                $length = qa_db_count_categories();
                break;

            case 'dodeletehidden_comments':
                $length = count( qa_db_posts_get_for_deleting( 'C' ) );
                break;

            case 'dodeletehidden_answers':
                $length = count( qa_db_posts_get_for_deleting( 'A' ) );
                break;

            case 'dodeletehidden_questions':
                $length = count( qa_db_posts_get_for_deleting( 'B' ) );
                break;

            case 'doblobstodisk_move':
                $length = qa_db_count_blobs_in_db();
                break;

            case 'doblobstodb_move':
                $length = qa_db_count_blobs_on_disk();
                break;

            default:
                $length = 0;
                break;
        }

        return $length;
    }

    /**
     *
     * Return a string which gives a user-viewable version of $state
     *
     * @param $state
     *
     * @return string
     */
    function qas_blog_recalc_get_message( $state )
    {
        @list( $operation, $length, $next, $done ) = explode( "\t", $state );

        $done = (int) $done;
        $length = (int) $length;

        switch ( $operation ) {
            case 'doreindexcontent_postcount':
            case 'dorecountposts_postcount':
            case 'dorecalccategories_postcount':
            case 'dorefillevents_qcount':
                $message = qa_lang( 'admin/recalc_posts_count' );
                break;

            case 'doreindexcontent_pagereindex':
                $message = strtr( qa_lang( 'admin/reindex_pages_reindexed' ), array(
                    '^1' => number_format( $done ),
                    '^2' => number_format( $length ),
                ) );
                break;

            case 'doreindexcontent_postreindex':
                $message = strtr( qa_lang( 'admin/reindex_posts_reindexed' ), array(
                    '^1' => number_format( $done ),
                    '^2' => number_format( $length ),
                ) );
                break;

            case 'doreindexposts_wordcount':
                $message = strtr( qa_lang( 'admin/reindex_posts_wordcounted' ), array(
                    '^1' => number_format( $done ),
                    '^2' => number_format( $length ),
                ) );
                break;

            case 'dorecountposts_votecount':
                $message = strtr( qa_lang( 'admin/recount_posts_votes_recounted' ), array(
                    '^1' => number_format( $done ),
                    '^2' => number_format( $length ),
                ) );
                break;

            case 'dorecountposts_acount':
                $message = strtr( qa_lang( 'admin/recount_posts_as_recounted' ), array(
                    '^1' => number_format( $done ),
                    '^2' => number_format( $length ),
                ) );
                break;

            case 'doreindexposts_complete':
                $message = qa_lang( 'admin/reindex_posts_complete' );
                break;

            case 'dorecountposts_complete':
                $message = qa_lang( 'admin/recount_posts_complete' );
                break;

            case 'dorecalcpoints_usercount':
                $message = qa_lang( 'admin/recalc_points_usercount' );
                break;

            case 'dorecalcpoints_recalc':
                $message = strtr( qa_lang( 'admin/recalc_points_recalced' ), array(
                    '^1' => number_format( $done ),
                    '^2' => number_format( $length ),
                ) );
                break;

            case 'dorecalcpoints_complete':
                $message = qa_lang( 'admin/recalc_points_complete' );
                break;

            case 'dorefillevents_refill':
                $message = strtr( qa_lang( 'admin/refill_events_refilled' ), array(
                    '^1' => number_format( $done ),
                    '^2' => number_format( $length ),
                ) );
                break;

            case 'dorefillevents_complete':
                $message = qa_lang( 'admin/refill_events_complete' );
                break;

            case 'dorecalccategories_postupdate':
                $message = strtr( qa_lang( 'admin/recalc_categories_updated' ), array(
                    '^1' => number_format( $done ),
                    '^2' => number_format( $length ),
                ) );
                break;

            case 'dorecalccategories_recount':
                $message = strtr( qa_lang( 'admin/recalc_categories_recounting' ), array(
                    '^1' => number_format( $done ),
                    '^2' => number_format( $length ),
                ) );
                break;

            case 'dorecalccategories_backpaths':
                $message = strtr( qa_lang( 'admin/recalc_categories_backpaths' ), array(
                    '^1' => number_format( $done ),
                    '^2' => number_format( $length ),
                ) );
                break;

            case 'dorecalccategories_complete':
                $message = qa_lang( 'admin/recalc_categories_complete' );
                break;

            case 'dodeletehidden_comments':
                $message = strtr( qa_lang( 'admin/hidden_comments_deleted' ), array(
                    '^1' => number_format( $done ),
                    '^2' => number_format( $length ),
                ) );
                break;

            case 'dodeletehidden_answers':
                $message = strtr( qa_lang( 'admin/hidden_answers_deleted' ), array(
                    '^1' => number_format( $done ),
                    '^2' => number_format( $length ),
                ) );
                break;

            case 'dodeletehidden_questions':
                $message = strtr( qa_lang( 'admin/hidden_questions_deleted' ), array(
                    '^1' => number_format( $done ),
                    '^2' => number_format( $length ),
                ) );
                break;

            case 'dodeletehidden_complete':
                $message = qa_lang( 'admin/delete_hidden_complete' );
                break;

            case 'doblobstodisk_move':
            case 'doblobstodb_move':
                $message = strtr( qa_lang( 'admin/blobs_move_moved' ), array(
                    '^1' => number_format( $done ),
                    '^2' => number_format( $length ),
                ) );
                break;

            case 'doblobstodisk_complete':
            case 'doblobstodb_complete':
                $message = qa_lang( 'admin/blobs_move_complete' );
                break;

            default:
                $message = '';
                break;
        }

        return $message;
    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */
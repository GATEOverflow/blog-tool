<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }
    
    /**
     * Create a new post in the database and return its ID (based on auto-incrementing)
     *
     * @param      $type
     * @param      $parentid
     * @param      $userid
     * @param      $cookieid
     * @param      $ip
     * @param      $title
     * @param      $content
     * @param      $format
     * @param      $tagstring
     * @param      $notify
     * @param null $categoryid
     * @param null $name
     *
     * @return mixed
     */
    function qas_blog_db_post_create( $type, $parentid , $reply_to , $userid, $cookieid, $ip, $title, $content, $format, $tagstring, $notify, $categoryid = null, $name = null )
    {
        qa_db_query_sub(
            'INSERT INTO ^blogs (categoryid, type , parentid, reply_to , userid, cookieid, createip, title, content, format, tags, notify, name, created) ' .
            'VALUES ( # , $, #, #, $, #, INET6_ATON($), $, $, $, $, $, $, NOW())',
            $categoryid, $type, $parentid, $reply_to, $userid, $cookieid, $ip, $title, $content, $format, $tagstring, $notify, $name
        );

        return qa_db_last_insert_id();
    }

    /**
     * Recalculate the full category path (i.e. columns catidpath1/2/3) for posts from $firstpostid to $lastpostid (if
     * specified)
     *
     * @param      $firstpostid
     * @param null $lastpostid
     */
    function qas_blog_db_posts_calc_category_path( $firstpostid, $lastpostid = null )
    {
        if ( !isset( $lastpostid ) )
            $lastpostid = $firstpostid;

        qa_db_query_sub(
            "UPDATE ^blogs AS x, (SELECT ^blogs.postid, " .
            "COALESCE(parent2.parentid, parent1.parentid, parent0.parentid, parent0.categoryid) AS catidpath1, " .
            "IF (parent2.parentid IS NOT NULL, parent1.parentid, IF (parent1.parentid IS NOT NULL, parent0.parentid, IF (parent0.parentid IS NOT NULL, parent0.categoryid, NULL))) AS catidpath2, " .
            "IF (parent2.parentid IS NOT NULL, parent0.parentid, IF (parent1.parentid IS NOT NULL, parent0.categoryid, NULL)) AS catidpath3 " .
            "FROM ^blogs LEFT JOIN ^blog_categories AS parent0 ON ^blogs.categoryid=parent0.categoryid LEFT JOIN ^blog_categories AS parent1 ON parent0.parentid=parent1.categoryid LEFT JOIN ^blog_categories AS parent2 ON parent1.parentid=parent2.categoryid WHERE ^blogs.postid BETWEEN # AND #) AS a SET x.catidpath1=a.catidpath1, x.catidpath2=a.catidpath2, x.catidpath3=a.catidpath3 WHERE x.postid=a.postid",
            $firstpostid, $lastpostid
        ); // requires QA_CATEGORY_DEPTH=4
    }

    /**
     * Update the cached count in the database of the number of blog posts which are queued for moderation
     */
    function qas_blog_db_queuedcount_update()
    {
        if ( qa_should_update_counts() )
            qa_db_query_sub( "REPLACE ^options (title, content) SELECT 'cache_blog_queuedcount', COUNT(*) FROM ^blogs WHERE type IN ('B_QUEUED' , 'C_QUEUED')" );
    }

    /**
     * Update the cached count of the number of flagged posts in the database
     */
    function qas_blog_db_flaggedcount_update()
    {
        if ( qa_should_update_counts() )
            qa_db_query_sub( "REPLACE ^options (title, content) SELECT 'cache_blog_flaggedcount', COUNT(*) FROM ^blogs WHERE flagcount>0 AND type IN ('B', 'C')" );
    }

    /**
     *  Recalculate the number of blog posts for each category in $path retrieved via
     *  qas_blog_db_post_get_category_path()
     *
     * @param $path
     */
    function qas_blog_db_category_path_post_count_update( $path )
    {
        qas_blog_db_ifcategory_post_count_update( $path[ 'categoryid' ] ); // requires QA_CATEGORY_DEPTH=4
        qas_blog_db_ifcategory_post_count_update( $path[ 'catidpath1' ] );
        qas_blog_db_ifcategory_post_count_update( $path[ 'catidpath2' ] );
        qas_blog_db_ifcategory_post_count_update( $path[ 'catidpath3' ] );
    }

    /**
     * Update the cached number of questions for category $categoryid in the database, including its subcategories
     *
     * @param $categoryid
     */
    function qas_blog_db_ifcategory_post_count_update( $categoryid )
    {
        if ( qa_should_update_counts() && isset( $categoryid ) ) {
            // This seemed like the most sensible approach which avoids explicitly calculating the category's depth in the hierarchy

            qa_db_query_sub(
                "UPDATE ^blog_categories SET qcount=GREATEST( (SELECT COUNT(*) FROM ^blogs WHERE categoryid=# AND type='B'), (SELECT COUNT(*) FROM ^blogs WHERE catidpath1=# AND type='B'), (SELECT COUNT(*) FROM ^blogs WHERE catidpath2=# AND type='B'), (SELECT COUNT(*) FROM ^blogs WHERE catidpath3=# AND type='B') ) WHERE categoryid=#",
                $categoryid, $categoryid, $categoryid, $categoryid, $categoryid
            ); // requires QA_CATEGORY_DEPTH=4
        }
    }

    /**
     * Return an array mapping each word in $words to its corresponding wordid in the database, adding any that are
     * missing
     *
     * @param $words
     *
     * @return array
     */
    function qas_blog_db_word_mapto_ids_add( $words )
    {
        $wordtoid = qas_blog_db_word_mapto_ids( $words );

        $wordstoadd = array();
        foreach ( $words as $word )
            if ( !isset( $wordtoid[ $word ] ) )
                $wordstoadd[] = $word;

        if ( count( $wordstoadd ) ) {
            qa_db_query_sub( 'LOCK TABLES ^blog_words WRITE' ); // to prevent two requests adding the same word

            $wordtoid = qas_blog_db_word_mapto_ids( $words ); // map it again in case table content changed before it was locked

            $rowstoadd = array();
            foreach ( $words as $word )
                if ( !isset( $wordtoid[ $word ] ) )
                    $rowstoadd[] = array( $word );

            qa_db_query_sub( 'INSERT IGNORE INTO ^blog_words (word) VALUES $', $rowstoadd );

            qa_db_query_sub( 'UNLOCK TABLES' );

            $wordtoid = qas_blog_db_word_mapto_ids( $words ); // do it one last time
        }

        return $wordtoid;
    }

    /**
     * Return an array mapping each word in $words to its corresponding wordid in the database
     *
     * @param $words
     *
     * @return array
     */
    function qas_blog_db_word_mapto_ids( $words )
    {
        if ( count( $words ) )
            return qa_db_read_all_assoc( qa_db_query_sub(
                'SELECT wordid, word FROM ^blog_words WHERE word IN ($)', $words
            ), 'word', 'wordid' );
        else
            return array();
    }

    /**
     * Add rows into the database title index, where $postid contains the words $wordids - this does the same sort
     * of thing as qa_db_posttags_add_post_wordids() in a different way, for no particularly good reason.
     *
     * @param $postid
     * @param $wordids
     */
    function qas_blog_db_titlewords_add_post_wordids( $postid, $wordids )
    {
        if ( count( $wordids ) ) {
            $rowstoadd = array();
            foreach ( $wordids as $wordid )
                $rowstoadd[] = array( $postid, $wordid );

            qa_db_query_sub(
                'INSERT INTO ^blog_titlewords (postid, wordid) VALUES #',
                $rowstoadd
            );
        }
    }

    /**
     * Add rows into the database content index, where $postid (of $type, with the antecedent $postid )
     * has words as per the keys of $wordidcounts, and the corresponding number of those words in the values.
     *
     * @param $postid
     * @param $type
     * @param $blog_postid
     * @param $wordidcounts
     */
    function qas_blog_db_contentwords_add_post_wordidcounts( $postid, $type, $blog_postid, $wordidcounts )
    {
        if ( count( $wordidcounts ) ) {
            $rowstoadd = array();
            foreach ( $wordidcounts as $wordid => $count )
                $rowstoadd[] = array( $postid, $wordid, $count, $type, $blog_postid );

            qa_db_query_sub(
                'INSERT INTO ^blog_contentwords (postid, wordid, count, type, questionid) VALUES #',
                $rowstoadd
            );
        }
    }

    /**
     * Add rows into the database index of individual tag words, where $postid contains the words $wordids
     *
     * @param $postid
     * @param $wordids
     */
    function qas_blog_db_tagwords_add_post_wordids( $postid, $wordids )
    {
        if ( count( $wordids ) ) {
            $rowstoadd = array();
            foreach ( $wordids as $wordid )
                $rowstoadd[] = array( $postid, $wordid );

            qa_db_query_sub(
                'INSERT INTO ^blog_tagwords (postid, wordid) VALUES #',
                $rowstoadd
            );
        }
    }

    /**
     * Add rows into the database index of whole tags, where $postid contains the tags $wordids
     *
     * @param $postid
     * @param $wordids
     */
    function qas_blog_db_posttags_add_post_wordids( $postid, $wordids )
    {
        if ( count( $wordids ) )
            qa_db_query_sub(
                'INSERT INTO ^blog_posttags (postid, wordid, postcreated) SELECT postid, wordid, created FROM ^blog_words, ^blogs WHERE postid=# AND wordid IN ($)',
                $postid, $wordids
            );
    }

    /**
     * Update the contentcount column in the database for the words in $wordids, based on how many posts they appear in
     * the content of
     *
     * @param $wordids
     */
    function qas_blog_db_word_contentcount_update( $wordids )
    {
        if ( qa_should_update_counts() && count( $wordids ) )
            qa_db_query_sub(
                'UPDATE ^blog_words AS x, (SELECT ^blog_words.wordid, COUNT(^contentwords.wordid) AS contentcount FROM ^blog_words LEFT JOIN ^contentwords ON ^contentwords.wordid=^blog_words.wordid WHERE ^blog_words.wordid IN (#) GROUP BY wordid) AS a SET x.contentcount=a.contentcount WHERE x.wordid=a.wordid',
                $wordids
            );
    }

    /**
     * Update the tagwordcount column in the database for the individual tag words in $wordids, based on how many posts
     * they appear in the tags of
     *
     * @param $wordids
     */
    function qas_blog_db_word_tagwordcount_update( $wordids )
    {
        if ( qa_should_update_counts() && count( $wordids ) )
            qa_db_query_sub(
                'UPDATE ^blog_words AS x, (SELECT ^blog_words.wordid, COUNT(^blog_tagwords.wordid) AS tagwordcount FROM ^blog_words LEFT JOIN ^blog_tagwords ON ^blog_tagwords.wordid=^blog_words.wordid WHERE ^blog_words.wordid IN (#) GROUP BY wordid) AS a SET x.tagwordcount=a.tagwordcount WHERE x.wordid=a.wordid',
                $wordids
            );
    }

    /**
     * Update the tagcount column in the database for the whole tags in $wordids, based on how many posts they appear
     * as tags of
     *
     * @param $wordids
     */
    function qas_blog_db_word_tagcount_update( $wordids )
    {
        if ( qa_should_update_counts() && count( $wordids ) )
            qa_db_query_sub(
                'UPDATE ^blog_words AS x, (SELECT ^blog_words.wordid, COUNT(^blog_posttags.wordid) AS tagcount FROM ^blog_words LEFT JOIN ^blog_posttags ON ^blog_posttags.wordid=^blog_words.wordid WHERE ^blog_words.wordid IN (#) GROUP BY wordid) AS a SET x.tagcount=a.tagcount WHERE x.wordid=a.wordid',
                $wordids
            );
    }

    /**
     * Update the cached count in the database of the number of different tags used
     */
    function qas_blog_db_tagcount_update()
    {
        if ( qa_should_update_counts() )
            qa_db_query_sub( "REPLACE ^options (title, content) SELECT 'cache_blog_tagcount', COUNT(*) FROM ^blog_words WHERE tagcount>0" );
    }

    /**
     * Set the metadata for post $postid with $key to $value. Keys beginning qa_ are reserved for the Q2A core.
     *
     * @param $postid
     * @param $key
     * @param $value
     */
    function qa_db_blogmeta_set( $postid, $key, $value )
    {
        qa_db_meta_set( 'blog_postmetas', 'postid', $postid, $key, $value );
    }

    /**
     * Update the cached count in the database of the number of questions (excluding hidden/queued)
     */
    function qas_blog_db_post_count_update()
    {
        if ( qa_should_update_counts() )
            qa_db_query_sub( "REPLACE ^options (title, content) SELECT 'cache_blog_pcount', COUNT(*) FROM ^blogs WHERE type='B'" );
    }

    /**
     * Update the cached count in the database of the number of comments (excluding hidden/queued)
     */
    function qas_blog_db_ccount_update()
    {
        if ( qa_should_update_counts() )
            qa_db_query_sub( "REPLACE ^options (title, content) SELECT 'cache_blog_ccount', COUNT(*) FROM ^blogs WHERE type='C'" );
    }

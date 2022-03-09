<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    //	For reindexing posts...
    /**
     * Return the information required to reindex up to $count posts starting from $startpostid in the database
     *
     * @param $startpostid
     * @param $count
     *
     * @return array
     */
    function qas_blog_db_posts_get_for_reindexing( $startpostid, $count )
    {
        return qa_db_read_all_assoc( qa_db_query_sub(
            "SELECT ^blogs.postid, ^blogs.title, ^blogs.content, ^blogs.format, ^blogs.tags, ^blogs.categoryid, ^blogs.type, IF (^blogs.type='B', ^blogs.postid, IF(parent.type='B', parent.postid, grandparent.postid)) AS questionid, ^blogs.parentid FROM ^blogs LEFT JOIN ^blogs AS parent ON ^blogs.parentid=parent.postid LEFT JOIN ^blogs as grandparent ON parent.parentid=grandparent.postid WHERE ^blogs.postid>=# AND ( (^blogs.type='B') OR (^blogs.type='A' AND parent.type<=>'B') OR (^blogs.type='C' AND parent.type<=>'B') OR (^blogs.type='C' AND parent.type<=>'A' AND grandparent.type<=>'B') ) ORDER BY postid LIMIT #",
            $startpostid, $count
        ), 'postid' );
    }

    /**
     * Prepare posts $firstpostid to $lastpostid for reindexing in the database by removing their prior index entries
     *
     * @param $firstpostid
     * @param $lastpostid
     */
    function qas_blog_db_prepare_for_reindexing( $firstpostid, $lastpostid )
    {
        qa_db_query_sub(
            'DELETE FROM ^blog_titlewords WHERE postid>=# AND postid<=#',
            $firstpostid, $lastpostid
        );

        qa_db_query_sub(
            'DELETE FROM ^blog_contentwords WHERE postid>=# AND postid<=#',
            $firstpostid, $lastpostid
        );

        qa_db_query_sub(
            'DELETE FROM ^blog_tagwords WHERE postid>=# AND postid<=#',
            $firstpostid, $lastpostid
        );

        qa_db_query_sub(
            'DELETE FROM ^blog_posttags WHERE postid>=# AND postid<=#',
            $firstpostid, $lastpostid
        );
    }

    /**
     * Remove any rows in the database word indexes with postid from $firstpostid upwards
     *
     * @param $firstpostid
     */
    function qas_blog_db_truncate_indexes( $firstpostid )
    {
        qa_db_query_sub(
            'DELETE FROM ^blog_titlewords WHERE postid>=#',
            $firstpostid
        );

        qa_db_query_sub(
            'DELETE FROM ^blog_contentwords WHERE postid>=#',
            $firstpostid
        );

        qa_db_query_sub(
            'DELETE FROM ^blog_tagwords WHERE postid>=#',
            $firstpostid
        );

        qa_db_query_sub(
            'DELETE FROM ^blog_posttags WHERE postid>=#',
            $firstpostid
        );
    }

    /**
     * Return the number of words currently referenced in the database
     *
     * @return null
     */
    function qas_blog_db_count_words()
    {
        return qa_db_read_one_value( qa_db_query_sub(
            'SELECT COUNT(*) FROM ^blog_words'
        ) );
    }

    /**
     * Return the ids of up to $count words in the database starting from $startwordid
     *
     * @param $startwordid
     * @param $count
     *
     * @return array
     */
    function qas_blog_db_words_prepare_for_recounting( $startwordid, $count )
    {
        return qa_db_read_all_values( qa_db_query_sub(
            'SELECT wordid FROM ^blog_words WHERE wordid>=# ORDER BY wordid LIMIT #',
            $startwordid, $count
        ) );
    }

    /**
     * Recalculate the cached counts for words $firstwordid to $lastwordid in the database
     *
     * @param $firstwordid
     * @param $lastwordid
     */
    function qas_blog_db_words_recount( $firstwordid, $lastwordid )
    {
        qa_db_query_sub(
            'UPDATE ^blog_words AS x, (SELECT ^blog_words.wordid, COUNT(^blog_titlewords.wordid) AS titlecount FROM ^blog_words LEFT JOIN ^blog_titlewords ON ^blog_titlewords.wordid=^blog_words.wordid WHERE ^blog_words.wordid>=# AND ^blog_words.wordid<=# GROUP BY wordid) AS a SET x.titlecount=a.titlecount WHERE x.wordid=a.wordid',
            $firstwordid, $lastwordid
        );

        qa_db_query_sub(
            'UPDATE ^blog_words AS x, (SELECT ^blog_words.wordid, COUNT(^blog_contentwords.wordid) AS contentcount FROM ^blog_words LEFT JOIN ^blog_contentwords ON ^blog_contentwords.wordid=^blog_words.wordid WHERE ^blog_words.wordid>=# AND ^blog_words.wordid<=# GROUP BY wordid) AS a SET x.contentcount=a.contentcount WHERE x.wordid=a.wordid',
            $firstwordid, $lastwordid
        );

        qa_db_query_sub(
            'UPDATE ^blog_words AS x, (SELECT ^blog_words.wordid, COUNT(^blog_tagwords.wordid) AS tagwordcount FROM ^blog_words LEFT JOIN ^blog_tagwords ON ^blog_tagwords.wordid=^blog_words.wordid WHERE ^blog_words.wordid>=# AND ^blog_words.wordid<=# GROUP BY wordid) AS a SET x.tagwordcount=a.tagwordcount WHERE x.wordid=a.wordid',
            $firstwordid, $lastwordid
        );

        qa_db_query_sub(
            'UPDATE ^blog_words AS x, (SELECT ^blog_words.wordid, COUNT(^blog_posttags.wordid) AS tagcount FROM ^blog_words LEFT JOIN ^blog_posttags ON ^blog_posttags.wordid=^blog_words.wordid WHERE ^blog_words.wordid>=# AND ^blog_words.wordid<=# GROUP BY wordid) AS a SET x.tagcount=a.tagcount WHERE x.wordid=a.wordid',
            $firstwordid, $lastwordid
        );

        qa_db_query_sub(
            'DELETE FROM ^blog_words WHERE wordid>=# AND wordid<=# AND titlecount=0 AND contentcount=0 AND tagwordcount=0 AND tagcount=0',
            $firstwordid, $lastwordid
        );
    }


    //	For recalculating numbers of votes for posts
    /**
     * Return the ids of up to $count posts in the database starting from $startpostid
     *
     * @param $startpostid
     * @param $count
     *
     * @return array
     */
    function qas_blog_db_posts_get_for_recounting( $startpostid, $count )
    {
        return qa_db_read_all_values( qa_db_query_sub(
            'SELECT postid FROM ^blogs WHERE postid>=# ORDER BY postid LIMIT #',
            $startpostid, $count
        ) );
    }

    //	For refilling event streams...
    /**
     * Return the ids of up to $count questions in the database starting from $startpostid
     *
     * @param $startpostid
     * @param $count
     *
     * @return array
     */
    function qas_blog_db_posts_get_for_event_refilling( $startpostid, $count )
    {
        return qa_db_read_all_values( qa_db_query_sub(
            "SELECT postid FROM ^blogs WHERE postid>=# AND LEFT(type, 1)='B' ORDER BY postid LIMIT #",
            $startpostid, $count
        ) );
    }


//	For recalculating categories...
    /**
     * Return the ids of up to $count posts (including queued/hidden) in the database starting from $startpostid
     *
     * @param $startpostid
     * @param $count
     *
     * @return array
     */
    function qas_blog_db_posts_get_for_recategorizing( $startpostid, $count )
    {
        return qa_db_read_all_values( qa_db_query_sub(
            "SELECT postid FROM ^blogs WHERE postid>=# ORDER BY postid LIMIT #",
            $startpostid, $count
        ) );
    }

    /**
     * Recalculate the (exact) categoryid for the posts (including queued/hidden) between $firstpostid and $lastpostid
     * in the database, where the category of comments and answers is set by the category of the antecedent question
     *
     * @param $firstpostid
     * @param $lastpostid
     */
    function qas_blog_db_posts_recalc_categoryid( $firstpostid, $lastpostid )
    {
        qa_db_query_sub(
            "UPDATE ^blogs AS x, (SELECT ^blogs.postid, IF(LEFT(parent.type, 1)='B', parent.categoryid, grandparent.categoryid) AS categoryid FROM ^blogs LEFT JOIN ^blogs AS parent ON ^blogs.parentid=parent.postid LEFT JOIN ^blogs AS grandparent ON parent.parentid=grandparent.postid WHERE ^blogs.postid BETWEEN # AND # AND LEFT(^blogs.type, 1)!='B') AS a SET x.categoryid=a.categoryid WHERE x.postid=a.postid",
            $firstpostid, $lastpostid
        );
    }

    /**
     * Return the ids of up to $count categories in the database starting from $startcategoryid
     *
     * @param $startcategoryid
     * @param $count
     *
     * @return array
     */
    function qas_blog_db_categories_get_for_recalcs( $startcategoryid, $count )
    {
        return qa_db_read_all_values( qa_db_query_sub(
            "SELECT categoryid FROM ^blog_categories WHERE categoryid>=# ORDER BY categoryid LIMIT #",
            $startcategoryid, $count
        ) );
    }


//	For deleting hidden posts...

    /**
     * Return the ids of up to $limit posts of $type that can be deleted from the database (i.e. have no dependents)
     *
     * @param      $type
     * @param int  $startpostid
     * @param null $limit
     *
     * @return array
     */
    function qas_blog_db_posts_get_for_deleting( $type, $startpostid = 0, $limit = null )
    {
        $limitsql = isset( $limit ) ? ( ' ORDER BY ^blogs.postid LIMIT ' . (int) $limit ) : '';

        return qa_db_read_all_values( qa_db_query_sub(
            "SELECT ^blogs.postid FROM ^blogs LEFT JOIN ^blogs AS child ON child.parentid=^blogs.postid WHERE ^blogs.type=$ AND ^blogs.postid>=# AND child.postid IS NULL" . $limitsql,
            $type . '_HIDDEN', $startpostid
        ) );
    }

    /*
        Omit PHP closing tag to help avoid accidental output
    */
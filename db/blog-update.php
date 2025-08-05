<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }
    
    /**
     * Return an array of wordids that were indexed in the database for the title of $postid
     *
     * @param $postid
     *
     * @return array
     */
    function qas_blog_db_titlewords_get_post_wordids( $postid )
    {
        return qa_db_read_all_values( qa_db_query_sub(
            'SELECT wordid FROM ^blog_titlewords WHERE postid=#',
            $postid
        ) );
    }

    /**
     * Remove all entries in the database index of title words for $postid
     *
     * @param $postid
     */
    function qas_blog_db_titlewords_delete_post( $postid )
    {
        qa_db_query_sub(
            'DELETE FROM ^blog_titlewords WHERE postid=#',
            $postid
        );
    }

    /**
     * Update the titlecount column in the database for the words in $wordids, based on how many posts they appear in
     * the title of
     *
     * @param $wordids
     */
    function qas_blog_db_word_titlecount_update( $wordids )
    {
        if ( qa_should_update_counts() && count( $wordids ) )
            qa_db_query_sub(
                'UPDATE ^blog_words AS x, (SELECT ^blog_words.wordid, COUNT(^blog_titlewords.wordid) AS titlecount FROM ^blog_words LEFT JOIN ^blog_titlewords ON ^blog_titlewords.wordid=^blog_words.wordid WHERE ^blog_words.wordid IN (#) GROUP BY wordid) AS a SET x.titlecount=a.titlecount WHERE x.wordid=a.wordid',
                $wordids
            );
    }

    /**
     * Return an array of wordids that were indexed in the database for the content of $postid
     *
     * @param $postid
     *
     * @return array
     */
    function qas_blog_db_contentwords_get_post_wordids( $postid )
    {
        return qa_db_read_all_values( qa_db_query_sub(
            'SELECT wordid FROM ^blog_contentwords WHERE postid=#',
            $postid
        ) );
    }

    /**
     * Remove all entries in the database index of content words for $postid
     *
     * @param $postid
     */
    function qas_blog_db_contentwords_delete_post( $postid )
    {
        qa_db_query_sub(
            'DELETE FROM ^blog_contentwords WHERE postid=#',
            $postid
        );
    }

    /**
     * Return an array of wordids that were indexed in the database for the individual words in tags of $postid
     *
     * @param $postid
     *
     * @return array
     */
    function qas_blog_db_tagwords_get_post_wordids( $postid )
    {
        return qa_db_read_all_values( qa_db_query_sub(
            'SELECT wordid FROM ^blog_tagwords WHERE postid=#',
            $postid
        ) );
    }

    /**
     * Remove all entries in the database index of individual words in tags of $postid
     *
     * @param $postid
     */
    function qas_blog_db_tagwords_delete_post( $postid )
    {
        qa_db_query_sub(
            'DELETE FROM ^blog_tagwords WHERE postid=#',
            $postid
        );
    }

    /**
     * Return an array of wordids that were indexed in the database for the whole tags of $postid
     *
     * @param $postid
     *
     * @return array
     */
    function qas_blog_db_posttags_get_post_wordids( $postid )
    {
        return qa_db_read_all_values( qa_db_query_sub(
            'SELECT wordid FROM ^blog_posttags WHERE postid=#',
            $postid
        ) );
    }

    /**
     * Remove all entries in the database index of whole tags for $postid
     *
     * @param $postid
     */
    function qas_blog_db_posttags_delete_post( $postid )
    {
        qa_db_query_sub(
            'DELETE FROM ^blog_posttags WHERE postid=#',
            $postid
        );
    }

    /**
     * Set the text fields in the database of $postid to $title, $content, $tagstring, $notify and $name, and record
     * that
     * $lastuserid did it from $lastip (if at least one is specified) with $updatetype. For backwards compatibility if
     * $name is null then the name will not be changed.
     *
     * @param        $postid
     * @param        $title
     * @param        $content
     * @param        $format
     * @param        $tagstring
     * @param        $notify
     * @param null   $lastuserid
     * @param null   $lastip
     * @param string $updatetype
     * @param null   $name
     */
    function qas_blog_db_post_set_content( $postid, $title, $content, $format, $tagstring, $notify, $lastuserid = null, $lastip = null, $updatetype = QA_UPDATE_CONTENT, $name = null )
    {
        if ( isset( $lastuserid ) || isset( $lastip ) ) // use COALESCE() for name since $name=null means it should not be modified (for backwards compatibility)
            qa_db_query_sub(
                'UPDATE ^blogs SET title=$, content=$, format=$, tags=$, name=COALESCE($, name), notify=$, updated=NOW(), updatetype=$, lastuserid=$, lastip=INET6_ATON($) WHERE postid=#',
                $title, $content, $format, $tagstring, $name, $notify, $updatetype, $lastuserid, $lastip, $postid
            );
        else
            qa_db_query_sub(
                'UPDATE ^blogs SET title=$, content=$, format=$, tags=$, name=COALESCE($, name), notify=$ WHERE postid=#',
                $title, $content, $format, $tagstring, $name, $notify, $postid
            );
    }

    /**
     * Set the type in the database of $postid to $type, and optionally record that $lastuserid did it from $lastip
     *
     * @param        $postid
     * @param        $type
     * @param null   $lastuserid
     * @param null   $lastip
     * @param string $updatetype
     */
    function qas_blog_db_post_set_type( $postid, $type, $lastuserid = null, $lastip = null, $updatetype = QA_UPDATE_TYPE )
    {
        if ( isset( $lastuserid ) || isset( $lastip ) ) {
            qa_db_query_sub(
                'UPDATE ^blogs SET type=$, updated=NOW(), updatetype=$, lastuserid=$, lastip=INET6_ATON($) WHERE postid=#',
                $type, $updatetype, $lastuserid, $lastip, $postid
            );
        } else
            qa_db_query_sub(
                'UPDATE ^blogs SET type=$ WHERE postid=#',
                $type, $postid
            );
    }

    /**
     * Set the (exact) category in the database of $postid to $categoryid, and optionally record that $lastuserid did
     * it from $lastip (if at least one is specified)
     *
     * @param      $postid
     * @param      $categoryid
     * @param null $lastuserid
     * @param null $lastip
     */
    function qas_blog_db_post_set_category( $postid, $categoryid, $lastuserid = null, $lastip = null )
    {
        if ( isset( $lastuserid ) || isset( $lastip ) )
            qa_db_query_sub(
                "UPDATE ^blogs SET categoryid=#, updated=NOW(), updatetype=$, lastuserid=$, lastip=INET6_ATON($) WHERE postid=#",
                $categoryid, QA_UPDATE_CATEGORY, $lastuserid, $lastip, $postid
            );
        else
            qa_db_query_sub(
                'UPDATE ^blogs SET categoryid=# WHERE postid=#',
                $categoryid, $postid
            );
    }

    /**
     * Set the category path in the database of each of $postids to $path retrieved via qa_db_post_get_category_path()
     *
     * @param $postids
     * @param $path
     */
    function qas_blog_db_posts_set_category_path( $postids, $path )
    {
        if ( count( $postids ) )
            qa_db_query_sub(
                'UPDATE ^blogs SET categoryid=#, catidpath1=#, catidpath2=#, catidpath3=# WHERE postid IN (#)',
                $path[ 'categoryid' ], $path[ 'catidpath1' ], $path[ 'catidpath2' ], $path[ 'catidpath3' ], $postids
            ); // requires QA_CATEGORY_DEPTH=4
    }

    /**
     * Set the created date of $postid to $created, which is a unix timestamp. If created is null, set to now.
     *
     * @param $postid
     * @param $created
     */
    function qas_blog_db_post_set_created( $postid, $created )
    {
        if ( isset( $created ) )
            qa_db_query_sub(
                'UPDATE ^blogs SET created=FROM_UNIXTIME(#) WHERE postid=#',
                $created, $postid
            );
        else
            qa_db_query_sub(
                'UPDATE ^blogs SET created=NOW() WHERE postid=#',
                $postid
            );
    }

    /**
     * Deletes post $postid from the database (will also delete any votes on the post due to foreign key cascading)
     *
     * @param $postid
     */
    function qas_blog_db_post_delete( $postid )
    {
        qa_db_query_sub(
            'DELETE FROM ^blogs WHERE postid=#',
            $postid
        );
    }

    /**
     * Set the last updated date of $postid to $updated, which is a unix timestamp. If updated is nul, set to now.
     *
     * @param $postid
     * @param $updated
     */
    function qas_blog_db_post_set_updated( $postid, $updated )
    {
        if ( isset( $updated ) )
            qa_db_query_sub(
                'UPDATE ^blogs SET updated=FROM_UNIXTIME(#) WHERE postid=#',
                $updated, $postid
            );
        else
            qa_db_query_sub(
                'UPDATE ^blogs SET updated=NOW() WHERE postid=#',
                $postid
            );
    }

    /**
     * incremnts the view count for a post
     *
     * @param     $postid
     * @param int $increment
     */
    function qas_blog_db_post_increment_view( $postid, $increment = 1 )
    {
        if ( isset( $postid ) && $increment )
            qa_db_query_sub(
                'UPDATE ^blogs SET views=views + # , lastviewip=INET6_ATON($) WHERE postid=#',
                $increment, qa_remote_ip_address(), $postid
            );
    }

    /**
     * set a post as featured post
     *
     * @param $postid
     */
    function qas_blog_set_featured_post( $postid )
    {
        qas_blog_db_postmeta_set( $postid, 'featured_post', 1 );
    }

    /**
     * remove a post from featured post list
     *
     * @param $postid
     */
    function qas_blog_unset_featured_post( $postid )
    {
        qas_blog_db_postmeta_clear( $postid, 'featured_post' );
    }

    /**
     * checks if a post is featured or not
     *
     * @param $postid
     *
     * @return bool
     */
    function qas_blog_is_featured_post( $postid )
    {
        return (bool) qas_blog_db_postmeta_get( $postid, 'featured_post' );
    }

    /**
     * fetch all featured post ids fom database
     *
     * @return array
     */
    function qas_blog_get_all_featured_post_ids()
    {
        return qa_db_read_all_assoc( qa_db_query_sub( 'SELECT postid from ^blog_postmetas where title = # ', 'featured_post' ) );
    }


    /**
     * fetch all favorite post ids fom database
     *
     * @return array
     */
    function qas_blog_get_all_favorite_post_ids($userid)
    {
		return qa_db_read_all_assoc( qa_db_query_sub( 'SELECT entityid from ^userfavorites where entitytype = # and userid = #', 'P', $userid ) );
    }


    /**
     * fetch count of all featured post counts fom database
     *
     * @return array
     */
    function qas_get_featured_posts_count()
    {
        return qa_db_read_one_value( qa_db_query_sub( 'SELECT count(*) from ^blog_postmetas where title = # ', 'featured_post' ) );
    }

    /**
     * Clear all entries from featured list
     */
    function qas_blog_clear_all_featured_posts()
    {
        qa_db_query_sub( 'DELETE from ^blog_postmetas where title = # ', 'featured_post' );
    }

    /**
     * retrives all featured posts fom the database
     *
     * @param int $count
     *
     * @return array
     */
    function qas_blog_get_all_featured_posts( $count = 10 )
    {
        $featured_post_ids = qas_blog_get_all_featured_post_ids();

        return qa_db_select_with_pending( qas_blog_db_featured_posts_selectspec( $featured_post_ids, $count ) );
    }

    /**
     * Set the metadata for post $postid with $key to $value. Keys beginning qa_ are reserved for the Q2A core.
     *
     * @param $postid
     * @param $key
     * @param $value
     */
    function qas_blog_db_postmeta_set( $postid, $key, $value )
    {
        qa_db_meta_set( 'blog_postmetas', 'postid', $postid, $key, $value );
    }

    /**
     * Clear the metadata for post $postid with $key ($key can also be an array of keys)
     *
     * @param $postid
     * @param $key
     */
    function qas_blog_db_postmeta_clear( $postid, $key )
    {
        qa_db_meta_clear( 'blog_postmetas', 'postid', $postid, $key );
    }

    /**
     * Return the metadata value for post $postid with $key ($key can also be an array of keys in which case this
     * returns an array of metadata key => value).
     *
     * @param $postid
     * @param $key
     *
     * @return array|null
     */
    function qas_blog_db_postmeta_get( $postid, $key )
    {
        return qa_db_meta_get( 'blog_postmetas', 'postid', $postid, $key );
    }

    /**
     * Fetches the next postid from the database
     *
     * @param $postid
     *
     * @return array|null
     */
    function qas_blog_db_get_nextpost_info( $postid ){
        $query = "SELECT postid , title FROM ^blogs WHERE postid > # AND type='B'
					ORDER BY created LIMIT 1";

        $result = qa_db_query_sub( $query, $postid );

	$myreturn  = qa_db_read_one_assoc( $result, true );
	if(!$myreturn) return array();
	else return $myreturn;
    }

    /**
     * Fetches the previous postid from the databse
     *
     * @param $postid
     *
     * @return array|null
     */
    function qas_blog_db_get_prevpost_info( $postid ){
        $query = "SELECT postid , title FROM ^blogs WHERE postid < # AND type='B'
					ORDER BY created DESC LIMIT 1";

        $result = qa_db_query_sub( $query, $postid );

        $myreturn = qa_db_read_one_assoc( $result, true );
	if(!$myreturn) return array();
	else return $myreturn;
    }

    function qas_blog_db_get_comment_reply_ids( $commentid ){

        $maxlevel = qa_opt('qas_blog_max_allow_nesting');
        $query = "SELECT" ;

        for( $level = 1 ; $level <= $maxlevel ; $level++ ){
            $query .= " c_$level.postid as reply_$level,";
        }

        $query = substr($query, 0, strrpos($query, ','));

        $query .= " FROM ^blogs as c_1";

        for( $level = 2 ; $level <= $maxlevel ; $level++ ){
            $prev_level = $level - 1 ;
            $query .= " LEFT JOIN ^blogs as c_$level ON c_$prev_level.postid = c_$level.reply_to ";
        }

        $query .= " WHERE c_1.postid = #" ;

        return qa_db_read_one_assoc( qa_db_query_sub( $query, $commentid ), true );
    }

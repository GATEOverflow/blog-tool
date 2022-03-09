<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }
    
    /**
     *  Return a selectspec to retrieve the full information on the category whose id is $slugsorid (if $isid is true),
     * otherwise whose backpath matches $slugsorid
     *
     * @param $slugsorid
     * @param $isid
     *
     * @return array
     */
    function qas_blog_db_full_category_selectspec( $slugsorid, $isid )
    {
        if ( $isid )
            $identifiersql = 'categoryid=#';
        else {
            $identifiersql = 'backpath=$';
            $slugsorid = qa_db_slugs_to_backpath( $slugsorid );
        }

        return array(
            'columns'   => array( 'categoryid', 'parentid', 'title', 'tags', 'qcount', 'content', 'backpath' ),
            'source'    => '^blog_categories WHERE ' . $identifiersql,
            'arguments' => array( $slugsorid ),
            'single'    => 'true',
        );
    }

    /**
     * Return the selectspec to retrieve ($full or not) info on the categories which "surround" the central category
     * specified by $slugsorid, $isid and $ispostid. The "surrounding" categories include all categories (even
     * unrelated) at the top level, any ancestors (at any level) of the category, the category's siblings and
     * sub-categories (to one level). The central category is specified as follows. If $isid AND $ispostid then
     * $slugsorid is the ID of a post with the category. Otherwise if $isid then $slugsorid is the category's own id.
     * Otherwise $slugsorid is the full backpath of the category.
     *
     * @param      $slugsorid
     * @param      $isid
     * @param bool $ispostid
     * @param bool $full
     *
     * @return array
     */
    function qas_blog_db_category_nav_selectspec( $slugsorid, $isid, $ispostid = false, $full = false )
    {
        if ( $isid ) {
            if ( $ispostid )
                $identifiersql = 'categoryid=(SELECT categoryid FROM ^blogs WHERE postid=#)';
            else
                $identifiersql = 'categoryid=#';

        } else {
            $identifiersql = 'backpath=$';
            $slugsorid = qa_db_slugs_to_backpath( $slugsorid );
        }

        $parentselects = array( // requires QA_CATEGORY_DEPTH=4
            'SELECT NULL AS parentkey', // top level
            'SELECT grandparent.parentid FROM ^blog_categories JOIN ^blog_categories AS parent ON ^blog_categories.parentid=parent.categoryid JOIN ^blog_categories AS grandparent ON parent.parentid=grandparent.categoryid WHERE ^blog_categories.' . $identifiersql, // 2 gens up
            'SELECT parent.parentid FROM ^blog_categories JOIN ^blog_categories AS parent ON ^blog_categories.parentid=parent.categoryid WHERE ^blog_categories.' . $identifiersql,
            // 1 gen up
            'SELECT parentid FROM ^blog_categories WHERE ' . $identifiersql, // same gen
            'SELECT categoryid FROM ^blog_categories WHERE ' . $identifiersql, // gen below
        );

        $selectspec = array(
            'columns'   => array( '^blog_categories.categoryid', '^blog_categories.parentid', 'title' => '^blog_categories.title', 'tags' => '^blog_categories.tags', '^blog_categories.qcount', '^blog_categories.position' ),
            'source'    => '^blog_categories JOIN (' . implode( ' UNION ', $parentselects ) . ') y ON ^blog_categories.parentid<=>parentkey' . ( $full ? ' LEFT JOIN ^blog_categories AS child ON child.parentid=^blog_categories.categoryid GROUP BY ^blog_categories.categoryid' : '' ) . ' ORDER BY ^blog_categories.position',
            'arguments' => array( $slugsorid, $slugsorid, $slugsorid, $slugsorid ),
            'arraykey'  => 'categoryid',
            'sortasc'   => 'position',
        );

        if ( $full ) {
            $selectspec[ 'columns' ][ 'childcount' ] = 'COUNT(child.categoryid)';
            $selectspec[ 'columns' ][ 'content' ] = '^blog_categories.content';
            $selectspec[ 'columns' ][ 'backpath' ] = '^blog_categories.backpath';
        }

        return $selectspec;
    }

    /**
     * Return the selectspec to retrieve information on all subcategories of $categoryid (used for Ajax navigation of
     * hierarchy)
     *
     * @param $categoryid
     *
     * @return array
     */
    function qas_blog_db_category_sub_selectspec( $categoryid )
    {
        return array(
            'columns'   => array( 'categoryid', 'title', 'tags', 'qcount', 'position' ),
            'source'    => '^blog_categories WHERE parentid<=># ORDER BY position',
            'arguments' => array( $categoryid ),
            'arraykey'  => 'categoryid',
            'sortasc'   => 'position',
        );
    }

    /**
     * Return the selectspec to retrieve a single category as specified by its $slugs (in order of hierarchy)
     *
     * @param $slugs
     *
     * @return array
     */
    function qas_blog_db_slugs_to_category_id_selectspec( $slugs )
    {
        return array(
            'columns'    => array( 'categoryid' ),
            'source'     => '^blog_categories WHERE backpath=$',
            'arguments'  => array( qa_db_slugs_to_backpath( $slugs ) ),
            'arrayvalue' => 'categoryid',
            'single'     => true,
        );
    }

    /**
     * Return the selectspec to retrieve the most popular tags. Return $count (if null, a default is used) tags,
     * starting from offset $start. The selectspec will produce a sorted array with tags in the key, and counts in the
     * values.
     *
     * @param      $start
     * @param null $count
     *
     * @return array
     */
    function qas_blog_db_popular_tags_selectspec( $start, $count = null )
    {
        $count = isset( $count ) ? $count : QA_DB_RETRIEVE_TAGS;

        return array(
            'columns'    => array( 'word', 'tagcount' ),
            'source'     => '^blog_words JOIN (SELECT wordid FROM ^blog_words WHERE tagcount>0 ORDER BY tagcount DESC LIMIT #,#) y ON ^blog_words.wordid=y.wordid',
            'arguments'  => array( $start, $count ),
            'arraykey'   => 'word',
            'arrayvalue' => 'tagcount',
            'sortdesc'   => 'tagcount',
        );
    }

    /**
     * @param $postid
     * Get the full category path (including categoryid) for $postid
     *
     * @return array|null
     */
    function qas_blog_db_post_get_category_path( $postid )
    {
        return qa_db_read_one_assoc( qa_db_query_sub(
            'SELECT categoryid, catidpath1, catidpath2, catidpath3 FROM ^blogs WHERE postid=#',
            $postid
        ) ); // requires QA_CATEGORY_DEPTH=4
    }

    /**
     * Return the selectspec to retrieve the full information for $postid, with the corresponding vote made by
     * $voteuserid (if not null)
     *
     * @param $voteuserid
     * @param $postid
     *
     * @return array
     */
    function qas_blog_db_full_post_selectspec( $voteuserid, $postid )
    {
        $selectspec = qas_blog_db_posts_basic_selectspec( $voteuserid, true );

        $selectspec[ 'source' ] .= " WHERE ^blogs.postid=#";
        $selectspec[ 'arguments' ][] = $postid;
        $selectspec[ 'single' ] = true;

        return $selectspec;
    }

    /**
     * Return the selectspec to retrieve the full information for all posts whose parent is $parentid, with the
     * corresponding vote made by $voteuserid (if not null)
     *
     * @param $voteuserid
     * @param $parentid
     *
     * @return array
     */
    function qas_blog_db_full_child_posts_selectspec( $voteuserid, $parentid )
    {
        $selectspec = qas_blog_db_posts_basic_selectspec( $voteuserid, true );

        $selectspec[ 'source' ] .= " WHERE ^blogs.parentid=#";
        $selectspec[ 'arguments' ][] = $parentid;

        return $selectspec;
    }

    /**
     *  Return the common selectspec used to build any selectspecs which retrieve posts from the database.
     *  If $voteuserid is set, retrieve the vote made by a particular that user on each post.
     *  If $full is true, get full information on the posts, instead of just information for listing pages.
     *  If $user is true, get information about the user who wrote the post (or cookie if anonymous).
     *
     * @param null $voteuserid
     * @param bool $full
     * @param bool $user
     *
     * @return array
     */
    function qas_blog_db_posts_basic_selectspec( $voteuserid = null, $full = false, $user = true )
    {
        $selectspec = array(
            'columns'   => array(
                '^blogs.postid', '^blogs.categoryid', '^blogs.type',
                'basetype'     => 'LEFT(^blogs.type, 1)',
                'hidden' => "INSTR(^blogs.type, '_HIDDEN')>0",
                '^blogs.acount', '^blogs.selchildid', '^blogs.closedbyid', '^blogs.upvotes', '^blogs.downvotes', '^blogs.netvotes', '^blogs.views', '^blogs.hotness',
                '^blogs.flagcount', '^blogs.title', '^blogs.tags',
                'created'        => 'UNIX_TIMESTAMP(^blogs.created)',
                '^blogs.name',
                'categoryname' => '^blog_categories.title',
                'categorybackpath' => "^blog_categories.backpath",
                'categoryids'  => "CONCAT_WS(',', ^blogs.catidpath1, ^blogs.catidpath2, ^blogs.catidpath3, ^blogs.categoryid)",
            ),

            'arraykey'  => 'postid',
            'source'    => '^blogs LEFT JOIN ^blog_categories ON ^blog_categories.categoryid=^blogs.categoryid',
            'arguments' => array(),
        );

        if ( isset( $voteuserid ) ) {

            $selectspec[ 'columns' ][ 'uservote' ] = '^uservotes.vote';
            $selectspec[ 'columns' ][ 'userflag' ] = '^uservotes.flag';
            $selectspec[ 'columns' ][ 'userfavoriteq' ] = '^userfavorites.entityid<=>^blogs.postid';
            $selectspec[ 'source' ] .= ' LEFT JOIN ^uservotes ON ^blogs.postid=^uservotes.postid AND ^uservotes.userid=$';
            $selectspec[ 'source' ] .= ' LEFT JOIN ^userfavorites ON ^blogs.postid=^userfavorites.entityid AND ^userfavorites.userid=$ AND ^userfavorites.entitytype=$';
            array_push( $selectspec[ 'arguments' ], $voteuserid, $voteuserid, QA_ENTITY_QUESTION );
        }

        if ( $full ) {
            $selectspec[ 'columns' ][ 'content' ] = '^blogs.content';
            $selectspec[ 'columns' ][ 'notify' ] = '^blogs.notify';
            $selectspec[ 'columns' ][ 'updated' ] = 'UNIX_TIMESTAMP(^blogs.updated)';
            $selectspec[ 'columns' ][ 'updatetype' ] = '^blogs.updatetype';
            $selectspec[ 'columns' ][] = '^blogs.format';
            $selectspec[ 'columns' ][] = '^blogs.lastuserid';
            $selectspec[ 'columns' ][ 'lastip' ] = 'INET6_NTOA(^blogs.lastip)';
            $selectspec[ 'columns' ][] = '^blogs.parentid';
            $selectspec[ 'columns' ][] = '^blogs.reply_to';
            $selectspec[ 'columns' ][ 'lastviewip' ] = 'INET6_NTOA(^blogs.lastviewip)';
        }

        if ( $user ) {
            $selectspec[ 'columns' ][] = '^blogs.userid';
            $selectspec[ 'columns' ][] = '^blogs.cookieid';
            $selectspec[ 'columns' ][ 'createip' ] = 'INET6_NTOA(^blogs.createip)';
            $selectspec[ 'columns' ][] = '^userpoints.points';

            if ( !QA_FINAL_EXTERNAL_USERS ) {
                $selectspec[ 'columns' ][] = '^users.flags';
                $selectspec[ 'columns' ][] = '^users.level';
                $selectspec[ 'columns' ][ 'email' ] = '^users.email';
                $selectspec[ 'columns' ][ 'handle' ] = '^users.handle';
                $selectspec[ 'columns' ][ 'avatarblobid' ] = 'BINARY ^users.avatarblobid';
                $selectspec[ 'columns' ][] = '^users.avatarwidth';
                $selectspec[ 'columns' ][] = '^users.avatarheight';
                $selectspec[ 'source' ] .= ' LEFT JOIN ^users ON ^blogs.userid=^users.userid';

                if ( $full ) {
                    $selectspec[ 'columns' ][ 'lasthandle' ] = 'lastusers.handle';
                    $selectspec[ 'source' ] .= ' LEFT JOIN ^users AS lastusers ON ^blogs.lastuserid=lastusers.userid';
                }
            }

            $selectspec[ 'source' ] .= ' LEFT JOIN ^userpoints ON ^blogs.userid=^userpoints.userid';
        }

        return $selectspec;
    }

    /**
     * Return the selectspec to retrieve the metadata value for $postid with key $title
     *
     * @param $postid
     * @param $title
     *
     * @return array
     */
    function qas_blog_db_post_meta_selectspec( $postid, $title )
    {
        $selectspec = array(
            'columns'    => array( 'title', 'content' ),
            'source'     => "^blog_postmetas WHERE postid=# AND " . ( is_array( $title ) ? "title IN ($)" : "title=$" ),
            'arguments'  => array( $postid, $title ),
            'arrayvalue' => 'content',
        );

        if ( is_array( $title ) )
            $selectspec[ 'arraykey' ] = 'title';
        else
            $selectspec[ 'single' ] = true;

        return $selectspec;
    }

    /**
     *
     * Return the selectspec to retrieve the antecedent questions for recent comments (of type $specialtype if
     * provided, or
     * 'C' by default), restricted to $createip (if not null) and the category for $categoryslugs (if not null), with
     * the corresponding vote on those questions made by $voteuserid (if not null). Return $count (if null, a default
     * is used) questions starting from offset $start. The selectspec will also retrieve some information about the
     * comments themselves (including the content if $fullcomments is true), in columns named with the prefix 'o'.
     *
     * @param      $voteuserid
     * @param      $start
     * @param null $categoryslugs
     * @param null $createip
     * @param bool $specialtype
     * @param bool $fullcomments
     * @param null $count
     *
     * @return array
     */
    function qas_blog_db_recent_c_bs_selectspec( $voteuserid, $start, $categoryslugs = null, $createip = null, $specialtype = false, $fullcomments = false, $count = null )
    {
        if ( ( $specialtype == 'C' ) || ( $specialtype == 'C_QUEUED' ) )
            $type = $specialtype;
        else
            $type = $specialtype ? 'C_HIDDEN' : 'C'; // for backwards compatibility

        $count = isset( $count ) ? min( $count, QA_DB_RETRIEVE_QS_AS ) : QA_DB_RETRIEVE_QS_AS;

        $selectspec = qas_blog_db_posts_basic_selectspec( $voteuserid );

        qa_db_add_selectspec_opost( $selectspec, 'cposts', false, $fullcomments );
        qa_db_add_selectspec_ousers( $selectspec, 'cusers', 'cuserpoints' );

        $selectspec[ 'source' ] .= " JOIN ^blogs AS parentposts ON" .
            " ^blogs.postid=(CASE LEFT(parentposts.type, 1) WHEN 'A' THEN parentposts.parentid ELSE parentposts.postid END)" .
            " JOIN ^blogs AS cposts ON parentposts.postid=cposts.parentid" .
            ( QA_FINAL_EXTERNAL_USERS ? "" : " LEFT JOIN ^users AS cusers ON cposts.userid=cusers.userid" ) .
            " LEFT JOIN ^userpoints AS cuserpoints ON cposts.userid=cuserpoints.userid" .
            " JOIN (SELECT postid FROM ^blogs WHERE " .
            qas_blog_db_categoryslugs_sql_args( $categoryslugs, $selectspec[ 'arguments' ] ) .
            ( isset( $createip ) ? "createip=INET6_ATON($) AND " : "" ) .
            "type=$ ORDER BY ^blogs.created DESC LIMIT #,#) y ON cposts.postid=y.postid" .
            ( $specialtype ? '' : " WHERE ^blogs.type='B' AND ((parentposts.type='B'))" );

        if ( isset( $createip ) )
            $selectspec[ 'arguments' ][] = $createip;

        array_push( $selectspec[ 'arguments' ], $type, $start, $count );

        $selectspec[ 'sortdesc' ] = 'otime';

        return $selectspec;
    }

    /**
     * Return SQL code that represents the constraint of a post being in the category with $categoryslugs, or any of
     * its subcategories
     *
     * @param $categoryslugs
     * @param $arguments
     *
     * @return string
     */
    function qas_blog_db_categoryslugs_sql_args( $categoryslugs, &$arguments )
    {
        if ( !is_array( $categoryslugs ) ) // accept old-style string arguments for one category deep
            $categoryslugs = strlen( $categoryslugs ) ? array( $categoryslugs ) : array();

        $levels = count( $categoryslugs );

        if ( ( $levels > 0 ) && ( $levels <= QA_CATEGORY_DEPTH ) ) {
            $arguments[] = qa_db_slugs_to_backpath( $categoryslugs );

            return ( ( $levels == QA_CATEGORY_DEPTH ) ? 'categoryid' : ( 'catidpath' . $levels ) ) . '=(SELECT categoryid FROM ^blog_categories WHERE backpath=$ LIMIT 1) AND ';
        }

        return '';
    }

    /**
     * Return the selectspec to retrieve the post (either duplicate post or explanatory note) which has closed postid ,
     * if any
     *
     * @param $postid
     *
     * @return array
     */
    function qas_blog_db_post_close_post_selectspec( $postid )
    {
        $selectspec = qas_blog_db_posts_basic_selectspec( null, true );

        $selectspec[ 'source' ] .= " WHERE ^blogs.postid=(SELECT closedbyid FROM ^blogs WHERE postid=#)";
        $selectspec[ 'arguments' ] = array( $postid );
        $selectspec[ 'single' ] = true;

        return $selectspec;
    }

    /**
     * Return the selectspec to retrieve questions (of type $specialtype if provided, or 'B' by default) sorted by
     * $sort, restricted to $createip (if not null) and the category for $categoryslugs (if not null), with the
     * corresponding vote made by $voteuserid (if not null) and including $full content or not. Return $count (if null,
     * a default is used) questions starting from offset $start.
     *
     * @param      $voteuserid
     * @param      $sort
     * @param      $start
     * @param null $categoryslugs
     * @param null $createip
     * @param bool $specialtype
     * @param bool $full
     * @param null $count
     *
     * @return array
     */
    function qas_blog_db_blogs_selectspec( $voteuserid, $sort, $start, $categoryslugs = null, $createip = null, $specialtype = false, $full = false, $count = null )
    {
        if ( ( $specialtype == 'B' ) || ( $specialtype == 'B_QUEUED' ) || ( $specialtype == 'D' ) )
            $type = $specialtype;
        else
            $type = $specialtype ? 'B_HIDDEN' : 'B'; // for backwards compatibility

        $count = isset( $count ) ? min( $count, QA_DB_RETRIEVE_QS_AS ) : QA_DB_RETRIEVE_QS_AS;

        switch ( $sort ) {
            case 'flagcount':
            case 'views':
                $sortsql = 'ORDER BY ^blogs.' . $sort . ' DESC, ^blogs.created DESC';
                break;

            case 'created':
                $sortsql = 'ORDER BY ^blogs.' . $sort . ' DESC';
                break;

            default:
                qa_fatal_error( 'qa_db_qs_selectspec() called with illegal sort value' );
                break;
        }

        $selectspec = qas_blog_db_posts_basic_selectspec( $voteuserid, $full );

        $selectspec[ 'source' ] .= " JOIN (SELECT postid FROM ^blogs WHERE " .
            qas_blog_db_categoryslugs_sql_args( $categoryslugs, $selectspec[ 'arguments' ] ) .
            ( isset( $createip ) ? "createip=INET6_ATON($) AND " : "" ) .
            "type=$ " . $sortsql . " LIMIT #,#) y ON ^blogs.postid=y.postid";

        if ( isset( $createip ) )
            $selectspec[ 'arguments' ][] = $createip;

        array_push( $selectspec[ 'arguments' ], $type, $start, $count );

        $selectspec[ 'sortdesc' ] = $sort;

        return $selectspec;
    }

    /**
     * Return the selectspec to retrieve the most recent questions with $tag, with the corresponding vote on those
     * questions made by $voteuserid (if not null) and including $full content or not. Return $count (if null, a
     * default is used) questions starting from $start.
     *
     * @param      $voteuserid
     * @param      $tag
     * @param      $start
     * @param bool $full
     * @param null $count
     *
     * @return array
     */
    function qas_blog_db_tag_recent_bs_selectspec( $voteuserid, $tag, $start, $full = false, $count = null )
    {
        $count = isset( $count ) ? min( $count, QA_DB_RETRIEVE_QS_AS ) : QA_DB_RETRIEVE_QS_AS;

        $selectspec = qas_blog_db_posts_basic_selectspec( $voteuserid, $full );

        // use two tests here - one which can use the index, and the other which narrows it down exactly - then limit to 1 just in case
        //$selectspec[ 'source' ] .= " JOIN (SELECT postid FROM ^blog_posttags WHERE wordid=(SELECT wordid FROM ^blog_words WHERE word=$ AND word=$ COLLATE utf8mb4_bin LIMIT 1) ORDER BY postcreated DESC LIMIT #,#) y ON ^blogs.postid=y.postid";
        $selectspec[ 'source' ] .= " JOIN (SELECT postid FROM ^blog_posttags WHERE wordid=(SELECT wordid FROM ^blog_words WHERE word=$ AND word=$ LIMIT 1) ORDER BY postcreated DESC LIMIT #,#) y ON ^blogs.postid=y.postid";//arjun
        array_push( $selectspec[ 'arguments' ], $tag, qa_strtolower( $tag ), $start, $count );
        $selectspec[ 'sortdesc' ] = 'created';

        return $selectspec;
    }

    /**
     * Return the selectspec to retrieve the number of questions tagged with $tag (single value)
     *
     * @param $tag
     *
     * @return array
     */
    function qas_blog_db_tag_word_selectspec( $tag )
    {
        return array(
            'columns'   => array( 'wordid', 'word', 'tagcount' ),
            'source'    => '^blog_words WHERE word=$',
            'arguments' => array( $tag ),
            'single'    => true,
        );
    }

    /**
     * Return the selectspec to retrieve information about all a user's favorited items except the questions. Depending
     * on the type of item, the array for each item will contain a userid, category backpath or tag word.
     *
     * @param $userid
     *
     * @return array
     */
    function qas_blog_db_user_favorite_non_bs_selectspec( $userid )
    {
        return array(
            'columns'   => array( 'type' => 'entitytype', 'userid' => 'IF (entitytype=$, entityid, NULL)', 'categorybackpath' => '^blog_categories.backpath', 'tags' => '^blog_words.word' ),
            'source'    => '^userfavorites LEFT JOIN ^blog_words ON entitytype=$ AND wordid=entityid LEFT JOIN ^blog_categories ON entitytype=$ AND categoryid=entityid WHERE userid=$ AND entitytype!=$',
            'arguments' => array( QA_ENTITY_USER, QAS_BLOG_ENTITY_TAG, QAS_BLOG_ENTITY_CATEGORY, $userid, QAS_BLOG_ENTITY_POST ),
        );
    }

    /**
     * Return the selectspec to retrieve recent questions by the user identified by $identifier, where $identifier is a
     * handle if we're using internal user management, or a userid if we're using external users. Also include the
     * corresponding vote on those questions made by $voteuserid (if not null). Return $count (if null, a default is
     * used) questions.
     *
     * @param      $voteuserid
     * @param      $identifier
     * @param null $count
     * @param int  $start
     *
     * @return array
     */
    function qas_blog_db_user_recent_posts_selectspec( $voteuserid, $identifier, $count = null, $start = 0 )
    {
        $count = isset( $count ) ? min( $count, QA_DB_RETRIEVE_QS_AS ) : QA_DB_RETRIEVE_QS_AS;

        $selectspec = qas_blog_db_posts_basic_selectspec( $voteuserid );

        $selectspec[ 'source' ] .= " WHERE ^blogs.userid=" . ( QA_FINAL_EXTERNAL_USERS ? "$" : "(SELECT userid FROM ^users WHERE handle=$ LIMIT 1)" ) . " AND type='B' ORDER BY ^blogs.created DESC LIMIT #,#";
        array_push( $selectspec[ 'arguments' ], $identifier, $start, $count );
        $selectspec[ 'sortdesc' ] = 'created';

        return $selectspec;
    }

    /**
     * Return the selectspec to retrieve recent questions by the user identified by $identifier, where $identifier is a
     * handle if we're using internal user management, or a userid if we're using external users. Also include the
     * corresponding vote on those questions made by $voteuserid (if not null). Return $count (if null, a default is
     * used) questions.
     *
     * @param      $voteuserid
     * @param      $identifier
     * @param null $count
     * @param int  $start
     *
     * @return array
     */
    function qas_blog_db_user_recent_drafts_selectspec( $voteuserid, $identifier, $count = null, $start = 0 )
    {
        $count = isset( $count ) ? min( $count, QA_DB_RETRIEVE_QS_AS ) : QA_DB_RETRIEVE_QS_AS;

        $selectspec = qas_blog_db_posts_basic_selectspec( $voteuserid );

        $selectspec[ 'source' ] .= " WHERE ^blogs.userid=" . ( QA_FINAL_EXTERNAL_USERS ? "$" : "(SELECT userid FROM ^users WHERE handle=$ LIMIT 1)" ) . " AND type='D' ORDER BY ^blogs.created DESC LIMIT #,#";
        array_push( $selectspec[ 'arguments' ], $identifier, $start, $count );
        $selectspec[ 'sortdesc' ] = 'created';

        return $selectspec;
    }

    /**
     * Returns no of posts by an user
     *
     * @param $idorhandle
     *
     * @return null
     */
    function qas_blog_db_user_recent_posts_count( $idorhandle )
    {
        return qa_db_read_one_value( qa_db_query_sub( "SELECT count(*) FROM ^blogs WHERE type='B' AND userid = " . ( QA_FINAL_EXTERNAL_USERS ? "$" : "(SELECT userid FROM ^users WHERE handle=$ LIMIT 1)" ), $idorhandle ) );
    }

    /**
     * Reutrns no of active drafts by an user
     *
     * @param $idorhandle
     *
     * @return null
     */
    function qas_blog_db_user_recent_drafts_count( $idorhandle )
    {
        return qa_db_read_one_value( qa_db_query_sub( "SELECT count(*) FROM ^blogs WHERE type='D' AND userid = " . ( QA_FINAL_EXTERNAL_USERS ? "$" : "(SELECT userid FROM ^users WHERE handle=$ LIMIT 1)" ), $idorhandle ) );
    }

    /**
     * Processes the matchparts column in $question which was returned from a search performed via
     * qa_db_search_posts_selectspec() Returns the id of the strongest matching answer or comment, or null if the
     * question itself was the strongest match
     *
     * @param $post
     * @param $type
     * @param $postid
     *
     * @return null
     */
    function qas_blog_search_set_max_match( $post, &$type, &$postid )
    {
        $type = 'B';
        $postid = $post[ 'postid' ];
        $bestscore = null;

        $matchparts = explode( ',', $post[ 'matchparts' ] );
        foreach ( $matchparts as $matchpart )
            if ( sscanf( $matchpart, '%1s:%f:%f', $matchposttype, $matchpostid, $matchscore ) == 3 )
                if ( ( !isset( $bestscore ) ) || ( $matchscore > $bestscore ) ) {
                    $bestscore = $matchscore;
                    $type = $matchposttype;
                    $postid = $matchpostid;
                }

        return null;
    }

    /**
     * Return the selectspec to retrieve the top question matches for a search, with the corresponding vote made by
     * $voteuserid (if not null) and including $full content or not. Return $count (if null, a default is used)
     * questions starting from offset $start. The search is performed for any of $titlewords in the title,
     * $contentwords in the content (of the question or an answer or comment for whom that is the antecedent question),
     * $tagwords in tags, for question author usernames which match a word in $handlewords or which match $handle as a
     * whole. The results also include a 'score' column based on the matching strength and post hotness, and a
     * 'matchparts' column that tells us where the score came from (since a question could get weight from a match in
     * the question itself, and/or weight from a match in its answers, comments, or comments on answers). The
     * 'matchparts' is a comma-separated list of tuples matchtype:matchpostid:matchscore to be used with
     * qa_search_set_max_match().
     *
     * @param      $voteuserid
     * @param      $titlewords
     * @param      $contentwords
     * @param      $tagwords
     * @param      $handlewords
     * @param      $handle
     * @param      $start
     * @param bool $full
     * @param null $count
     *
     * @return array
     */
    function qas_blog_db_search_posts_selectspec( $voteuserid, $titlewords, $contentwords, $tagwords, $handlewords, $handle, $start, $full = false, $count = null )
    {
        $count = isset( $count ) ? min( $count, QA_DB_RETRIEVE_QS_AS ) : QA_DB_RETRIEVE_QS_AS;

        // add LOG(postid)/1000000 here to ensure ordering is deterministic even if several posts have same score
        // The score also gives a bonus for hot questions, where the bonus scales linearly with hotness. The hottest
        // question gets a bonus equivalent to a matching unique tag, and the least hot question gets zero bonus.

        $selectspec = qas_blog_db_posts_basic_selectspec( $voteuserid, $full );

        $selectspec[ 'columns' ][] = 'score';
        $selectspec[ 'columns' ][] = 'matchparts';
        $selectspec[ 'source' ] .= " JOIN (SELECT questionid, SUM(score)+2*(LOG(#)*(^blogs.hotness-(SELECT MIN(hotness) FROM ^blogs WHERE type='B'))/((SELECT MAX(hotness) FROM ^blogs WHERE type='B')-(SELECT MIN(hotness) FROM ^blogs WHERE type='B')))+LOG(questionid)/1000000 AS score, GROUP_CONCAT(CONCAT_WS(':', matchposttype, matchpostid, ROUND(score,3))) AS matchparts FROM (";
        $selectspec[ 'sortdesc' ] = 'score';
        array_push( $selectspec[ 'arguments' ], QA_IGNORED_WORDS_FREQ );

        $selectparts = 0;

        if ( !empty( $titlewords ) ) {
            // At the indexing stage, duplicate words in title are ignored, so this doesn't count multiple appearances.

            $selectspec[ 'source' ] .= ( $selectparts++ ? " UNION ALL " : "" ) .
                "(SELECT postid AS questionid, LOG(#/titlecount) AS score, 'B' AS matchposttype, postid AS matchpostid FROM ^blog_titlewords JOIN ^blog_words ON ^blog_titlewords.wordid=^blog_words.wordid WHERE word IN ($) AND titlecount<#)";

            array_push( $selectspec[ 'arguments' ], QA_IGNORED_WORDS_FREQ, $titlewords, QA_IGNORED_WORDS_FREQ );
        }

        if ( !empty( $contentwords ) ) {
            // (1-1/(1+count)) weights words in content based on their frequency: If a word appears once in content
            // it's equivalent to 1/2 an appearance in the title (ignoring the contentcount/titlecount factor).
            // If it appears an infinite number of times, it's equivalent to one appearance in the title.
            // This will discourage keyword stuffing while still giving some weight to multiple appearances.
            // On top of that, answer matches are worth half a question match, and comment/note matches half again.

            $selectspec[ 'source' ] .= ( $selectparts++ ? " UNION ALL " : "" ) .
                "(SELECT questionid, (1-1/(1+count))*LOG(#/contentcount)*(CASE ^blog_contentwords.type WHEN 'B' THEN 1.0 WHEN 'A' THEN 0.5 ELSE 0.25 END) AS score, ^blog_contentwords.type AS matchposttype, ^blog_contentwords.postid AS matchpostid FROM ^blog_contentwords JOIN ^blog_words ON ^blog_contentwords.wordid=^blog_words.wordid WHERE word IN ($) AND contentcount<#)";

            array_push( $selectspec[ 'arguments' ], QA_IGNORED_WORDS_FREQ, $contentwords, QA_IGNORED_WORDS_FREQ );
        }

        if ( !empty( $tagwords ) ) {
            // Appearances in the tag words count like 2 appearances in the title (ignoring the tagcount/titlecount factor).
            // This is because tags express explicit semantic intent, whereas titles do not necessarily.

            $selectspec[ 'source' ] .= ( $selectparts++ ? " UNION ALL " : "" ) .
                "(SELECT postid AS questionid, 2*LOG(#/tagwordcount) AS score, 'B' AS matchposttype, postid AS matchpostid FROM ^blog_tagwords JOIN ^blog_words ON ^blog_tagwords.wordid=^blog_words.wordid WHERE word IN ($) AND tagwordcount<#)";

            array_push( $selectspec[ 'arguments' ], QA_IGNORED_WORDS_FREQ, $tagwords, QA_IGNORED_WORDS_FREQ );
        }

        if ( !empty( $handlewords ) ) {
            if ( QA_FINAL_EXTERNAL_USERS ) {
                $userids = qa_get_userids_from_public( $handlewords );

                if ( count( $userids ) ) {
                    $selectspec[ 'source' ] .= ( $selectparts++ ? " UNION ALL " : "" ) .
                        "(SELECT postid AS questionid, LOG(#/qposts) AS score, 'B' AS matchposttype, postid AS matchpostid FROM ^blogs JOIN ^userpoints ON ^blogs.userid=^userpoints.userid WHERE ^blogs.userid IN ($) AND type='B')";

                    array_push( $selectspec[ 'arguments' ], QA_IGNORED_WORDS_FREQ, $userids );
                }

            } else {
                $selectspec[ 'source' ] .= ( $selectparts++ ? " UNION ALL " : "" ) .
                    "(SELECT postid AS questionid, LOG(#/qposts) AS score, 'B' AS matchposttype, postid AS matchpostid FROM ^blogs JOIN ^users ON ^blogs.userid=^users.userid JOIN ^userpoints ON ^userpoints.userid=^users.userid WHERE handle IN ($) AND type='B')";

                array_push( $selectspec[ 'arguments' ], QA_IGNORED_WORDS_FREQ, $handlewords );
            }
        }

        if ( strlen( $handle ) ) { // to allow searching for multi-word usernames (only works if search query contains full username and nothing else)
            if ( QA_FINAL_EXTERNAL_USERS ) {
                $userids = qa_get_userids_from_public( array( $handle ) );

                if ( count( $userids ) ) {
                    $selectspec[ 'source' ] .= ( $selectparts++ ? " UNION ALL " : "" ) .
                        "(SELECT postid AS questionid, LOG(#/qposts) AS score, 'B' AS matchposttype, postid AS matchpostid FROM ^blogs JOIN ^userpoints ON ^blogs.userid=^userpoints.userid WHERE ^blogs.userid=$ AND type='B')";

                    array_push( $selectspec[ 'arguments' ], QA_IGNORED_WORDS_FREQ, reset( $userids ) );
                }

            } else {
                $selectspec[ 'source' ] .= ( $selectparts++ ? " UNION ALL " : "" ) .
                    "(SELECT postid AS questionid, LOG(#/qposts) AS score, 'B' AS matchposttype, postid AS matchpostid FROM ^blogs JOIN ^users ON ^blogs.userid=^users.userid JOIN ^userpoints ON ^userpoints.userid=^users.userid WHERE handle=$ AND type='B')";

                array_push( $selectspec[ 'arguments' ], QA_IGNORED_WORDS_FREQ, $handle );
            }
        }

        if ( $selectparts == 0 )
            $selectspec[ 'source' ] .= '(SELECT NULL as questionid, 0 AS score, NULL AS matchposttype, NULL AS matchpostid FROM ^blogs WHERE postid IS NULL)';

        $selectspec[ 'source' ] .= ") x LEFT JOIN ^blogs ON ^blogs.postid=questionid GROUP BY questionid ORDER BY score DESC LIMIT #,#) y ON ^blogs.postid=y.questionid";

        array_push( $selectspec[ 'arguments' ], $start, $count );

        return $selectspec;
    }

    /**
     * Return the selectspec to retrieve the posts in $postids, with the corresponding vote on those posts made by
     * $voteuserid (if not null). Returns full information if $full is true.
     *
     * @param      $voteuserid
     * @param      $postids
     * @param bool $full
     *
     * @return array
     */
    function qas_blog_db_posts_selectspec( $voteuserid, $postids, $full = false )
    {
        $selectspec = qas_blog_db_posts_basic_selectspec( $voteuserid, $full );

        $selectspec[ 'source' ] .= " WHERE ^blogs.postid IN (#)";
        $selectspec[ 'arguments' ][] = $postids;

        return $selectspec;
    }

    /**
     * Return the selectspec to retrieve the basetype for the posts in $postids, as an array mapping postid => basetype
     *
     * @param $postids
     *
     * @return array
     */
    function qas_blog_db_posts_basetype_selectspec( $postids )
    {
        return array(
            'columns'    => array( 'postid', 'basetype' => 'LEFT(type, 1)' ),
            'source'     => "^blogs WHERE postid IN (#)",
            'arguments'  => array( $postids ),
            'arraykey'   => 'postid',
            'arrayvalue' => 'basetype',
        );
    }

    /**
     * Return the selectspec to retrieve the basetype for the posts in $postids, as an array mapping postid => basetype
     *
     * @param      $voteuserid
     * @param      $postids
     * @param bool $full
     *
     * @return array
     */
    function qas_blog_db_posts_to_bs_selectspec( $voteuserid, $postids, $full = false )
    {
        $selectspec = qas_blog_db_posts_basic_selectspec( $voteuserid, $full );

        $selectspec[ 'columns' ][ 'obasetype' ] = 'LEFT(childposts.type, 1)';
        $selectspec[ 'columns' ][ 'opostid' ] = 'childposts.postid';

        $selectspec[ 'source' ] .= " JOIN ^blogs AS parentposts ON" .
            " ^blogs.postid=IF(LEFT(parentposts.type, 1)='B', parentposts.postid, parentposts.parentid)" .
            " JOIN ^blogs AS childposts ON parentposts.postid=IF(LEFT(childposts.type, 1)='B', childposts.postid, childposts.parentid)" .
            " WHERE childposts.postid IN (#)";

        $selectspec[ 'arraykey' ] = 'opostid';
        $selectspec[ 'arguments' ][] = $postids;

        return $selectspec;
    }

    /**
     * Returns select specifications for featured posts
     *
     * @param      $voteuserid
     * @param      $identifier
     * @param null $count
     * @param int  $start
     *
     * @return array
     */
    function qas_blog_db_featured_posts_selectspec( $postids, $count = null, $start = 0 )
    {
        $count = isset( $count ) ? min( $count, QA_DB_RETRIEVE_QS_AS ) : QA_DB_RETRIEVE_QS_AS;

        $selectspec = qas_blog_db_posts_basic_selectspec( null, true );

        $selectspec[ 'source' ] .= " WHERE ^blogs.type='B' AND ^blogs.postid IN ($) ORDER BY ^blogs.created DESC LIMIT #,#";
        array_push( $selectspec[ 'arguments' ], $postids, $start, $count );
        $selectspec[ 'sortdesc' ] = 'created';

        return $selectspec;
    }

    /**
     * Return the selectspec to retrieve the most closely related posts to $postid , with the corresponding vote
     * made by $voteuserid (if not null). Return $count (if null, a default is used) questions. This works by looking
     * for other questions which have title words, tag words or an (exact) category in common.
     *
     * @param      $voteuserid
     * @param      $postid
     * @param null $count
     *
     * @return array
     */
    function qas_blog_db_related_blogs_selectspec( $voteuserid, $postid, $count = null )
    {
        $count = isset( $count ) ? min( $count, QA_DB_RETRIEVE_QS_AS ) : QA_DB_RETRIEVE_QS_AS;

        $selectspec = qas_blog_db_posts_basic_selectspec( $voteuserid );

        $selectspec[ 'columns' ][] = 'score';

        $selectspec[ 'source' ] .= " JOIN (SELECT postid, SUM(score)+LOG(postid)/1000000 AS score FROM ((SELECT ^blog_titlewords.postid, LOG(#/titlecount) AS score FROM ^blog_titlewords JOIN ^blog_words ON ^blog_titlewords.wordid=^blog_words.wordid JOIN ^blog_titlewords AS source ON ^blog_titlewords.wordid=source.wordid WHERE source.postid=# AND titlecount<#) UNION ALL (SELECT ^blog_posttags.postid, 2*LOG(#/tagcount) AS score FROM ^blog_posttags JOIN ^blog_words ON ^blog_posttags.wordid=^blog_words.wordid JOIN ^blog_posttags AS source ON ^blog_posttags.wordid=source.wordid WHERE source.postid=# AND tagcount<#) UNION ALL (SELECT ^blogs.postid, LOG(#/^blog_categories.qcount) FROM ^blogs JOIN ^blog_categories ON ^blogs.categoryid=^blog_categories.categoryid AND ^blogs.type='B' WHERE ^blog_categories.categoryid=(SELECT categoryid FROM ^blogs WHERE postid=#) AND ^blog_categories.qcount<#)) x WHERE postid!=# GROUP BY postid ORDER BY score DESC LIMIT #) y ON ^blogs.postid=y.postid";

        array_push( $selectspec[ 'arguments' ], QA_IGNORED_WORDS_FREQ, $postid, QA_IGNORED_WORDS_FREQ, QA_IGNORED_WORDS_FREQ,
            $postid, QA_IGNORED_WORDS_FREQ, QA_IGNORED_WORDS_FREQ, $postid, QA_IGNORED_WORDS_FREQ, $postid, $count );

        $selectspec[ 'sortdesc' ] = 'score';

        return $selectspec;
    }

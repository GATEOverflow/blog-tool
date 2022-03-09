<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    /**
     * Return number of categories in the database
     */
    function qas_blog_db_count_categories()
    {
        return qa_db_read_one_value( qa_db_query_sub(
            'SELECT COUNT(*) FROM ^blog_categories'
        ) );
    }

    /**
     * Return number of questions in the database in $categoryid exactly, and not one of its subcategories
     *
     * @param $categoryid
     *
     * @return null
     */
    function qas_blog_db_count_categoryid_posts( $categoryid )
    {
        return qa_db_read_one_value( qa_db_query_sub(
            "SELECT COUNT(*) FROM ^blogs WHERE categoryid<=># AND type='B'",
            $categoryid
        ) );
    }

    /**
     * Return the maximum position of the categories with $parentid
     *
     * @param $parentid
     *
     * @return null
     */
    function qas_blog_db_category_last_pos( $parentid )
    {
        return qa_db_read_one_value( qa_db_query_sub(
            'SELECT COALESCE(MAX(position), 0) FROM ^blog_categories WHERE parentid<=>#',
            $parentid
        ) );
    }

    /**
     * Return how many levels of subcategory there are below $categoryid
     *
     * @param $categoryid
     *
     * @return int
     */
    function qas_blog_db_category_child_depth( $categoryid )
    {
        // This is potentially a very slow query since it counts all the multi-generational offspring of a particular category
        // But it's only used for admin purposes when moving a category around so I don't think it's worth making more efficient
        // (Incidentally, this could be done by keeping a count for every category of how many generations of offspring it has.)

        $result = qa_db_read_one_assoc( qa_db_query_sub(
            'SELECT COUNT(child1.categoryid) AS count1, COUNT(child2.categoryid) AS count2, COUNT(child3.categoryid) AS count3 FROM ^blog_categories AS child1 LEFT JOIN ^blog_categories AS child2 ON child2.parentid=child1.categoryid LEFT JOIN ^blog_categories AS child3 ON child3.parentid=child2.categoryid WHERE child1.parentid=#;', // requires QA_CATEGORY_DEPTH=4
            $categoryid
        ) );

        for ( $depth = QA_CATEGORY_DEPTH - 1 ; $depth >= 1 ; $depth-- )
            if ( $result[ 'count' . $depth ] )
                return $depth;

        return 0;
    }

    /**
     * Create a new category with $parentid, $title (=name) and $tags (=slug) in the database
     *
     * @param $parentid
     * @param $title
     * @param $tags
     *
     * @return mixed
     */
    function qas_blog_db_category_create( $parentid, $title, $tags )
    {
        $lastpos = qas_blog_db_category_last_pos( $parentid );

        qa_db_query_sub(
            'INSERT INTO ^blog_categories (parentid, title, tags, position) VALUES (#, $, $, #)',
            $parentid, $title, $tags, 1 + $lastpos
        );

        $categoryid = qa_db_last_insert_id();

        qas_blog_db_categories_recalc_backpaths( $categoryid );

        return $categoryid;
    }

    /**
     * Recalculate the backpath columns for all categories from $firstcategoryid to $lastcategoryid (if specified)
     *
     * @param      $firstcategoryid
     * @param null $lastcategoryid
     */
    function qas_blog_db_categories_recalc_backpaths( $firstcategoryid, $lastcategoryid = null )
    {
        if ( !isset( $lastcategoryid ) )
            $lastcategoryid = $firstcategoryid;

        qa_db_query_sub(
            "UPDATE ^blog_categories AS x, (SELECT cat1.categoryid, CONCAT_WS('/', cat1.tags, cat2.tags, cat3.tags, cat4.tags) AS backpath FROM ^blog_categories AS cat1 LEFT JOIN ^blog_categories AS cat2 ON cat1.parentid=cat2.categoryid LEFT JOIN ^blog_categories AS cat3 ON cat2.parentid=cat3.categoryid LEFT JOIN ^blog_categories AS cat4 ON cat3.parentid=cat4.categoryid WHERE cat1.categoryid BETWEEN # AND #) AS a SET x.backpath=a.backpath WHERE x.categoryid=a.categoryid",
            $firstcategoryid, $lastcategoryid // requires QA_CATEGORY_DEPTH=4
        );
    }

    /**
     * Set the name of $categoryid to $title and its slug to $tags in the database
     *
     * @param $categoryid
     * @param $title
     * @param $tags
     */
    function qas_blog_db_category_rename( $categoryid, $title, $tags )
    {
        qa_db_query_sub(
            'UPDATE ^blog_categories SET title=$, tags=$ WHERE categoryid=#',
            $title, $tags, $categoryid
        );

        qa_db_categories_recalc_backpaths( $categoryid ); // may also require recalculation of its offspring's backpaths
    }

    /**
     * Set the content (=description) of $categoryid to $content
     *
     * @param $categoryid
     * @param $content
     */
    function qas_blog_db_category_set_content( $categoryid, $content )
    {
        qa_db_query_sub(
            'UPDATE ^blog_categories SET content=$ WHERE categoryid=#',
            $content, $categoryid
        );
    }

    /**
     * Return the parentid of $categoryid
     *
     * @param $categoryid
     *
     * @return null
     */
    function qas_blog_db_category_get_parent( $categoryid )
    {
        return qa_db_read_one_value( qa_db_query_sub(
            'SELECT parentid FROM ^blog_categories WHERE categoryid=#',
            $categoryid
        ) );
    }

    /**
     * Move the category $categoryid into position $newposition under its parent
     *
     * @param $categoryid
     * @param $newposition
     */
    function qas_blog_db_category_set_position( $categoryid, $newposition )
    {
        qa_db_ordered_move( 'blog_categories', 'categoryid', $categoryid, $newposition,
            qa_db_apply_sub( 'parentid<=>#', array( qas_blog_db_category_get_parent( $categoryid ) ) ) );
    }

    /**
     * Set the parent of $categoryid to $newparentid, placing it in last position (doesn't do necessary recalculations)
     *
     * @param $categoryid
     * @param $newparentid
     */
    function qas_blog_db_category_set_parent( $categoryid, $newparentid )
    {
        $oldparentid = qas_blog_db_category_get_parent( $categoryid );

        if ( strcmp( $oldparentid, $newparentid ) ) { // if we're changing parent, move to end of old parent, then end of new parent
            $lastpos = qas_blog_db_category_last_pos( $oldparentid );

            qa_db_ordered_move( 'blog_categories', 'categoryid', $categoryid, $lastpos, qa_db_apply_sub( 'parentid<=>#', array( $oldparentid ) ) );

            $lastpos = qas_blog_db_category_last_pos( $newparentid );

            qa_db_query_sub(
                'UPDATE ^blog_categories SET parentid=#, position=# WHERE categoryid=#',
                $newparentid, 1 + $lastpos, $categoryid
            );
        }
    }

    /**
     * Change the categoryid of any posts with (exact) $categoryid to $reassignid
     *
     * @param $categoryid
     * @param $reassignid
     */
    function qas_blog_db_category_reassign( $categoryid, $reassignid )
    {
        qa_db_query_sub( 'UPDATE ^blogs SET categoryid=# WHERE categoryid<=>#', $reassignid, $categoryid );
    }

    /**
     * Delete the category $categoryid in the database
     *
     * @param $categoryid
     */
    function qas_blog_db_category_delete( $categoryid )
    {
        qa_db_ordered_delete( 'blog_categories', 'categoryid', $categoryid,
            qa_db_apply_sub( 'parentid<=>#', array( qas_blog_db_category_get_parent( $categoryid ) ) ) );
    }

    /**
     * Return the categoryid for the category with parent $parentid and $slug
     *
     * @param $parentid
     * @param $slug
     *
     * @return null
     */
    function qas_blog_db_category_slug_to_id( $parentid, $slug )
    {
        return qa_db_read_one_value( qa_db_query_sub(
            'SELECT categoryid FROM ^blog_categories WHERE parentid<=># AND tags=$',
            $parentid, $slug
        ), true );
    }

    /**
     * Return an array whose keys contain the $postids which exist, and whose elements contain the number of other
     * posts depending on each one
     *
     * @param $postids
     *
     * @return array
     */
    function qas_blog_db_postids_count_dependents( $postids )
    {
        if ( count( $postids ) )
            return qa_db_read_all_assoc( qa_db_query_sub(
                "SELECT postid, COALESCE(childcount, 0) AS count FROM ^blogs LEFT JOIN (SELECT parentid, COUNT(*) AS childcount FROM ^blogs WHERE parentid IN (#) AND LEFT(type, 1) IN ('C') GROUP BY parentid) x ON postid=x.parentid WHERE postid IN (#)",
                $postids, $postids
            ), 'postid', 'count' );
        else
            return array();
    }

    /**
     * Return list of postids of visible or queued posts by $userid
     *
     * @param $userid
     *
     * @return array
     */
    function qas_blog_db_get_user_visible_postids( $userid )
    {
        return qa_db_read_all_assoc( qa_db_query_sub(
            "SELECT postid , type FROM ^blogs WHERE userid=# AND type IN ('B','C', 'B_QUEUED', 'C_QUEUED')",
            $userid
        ) );
    }

    /**
     * Return number of blog posts by an userid
     *
     * @param $userid
     *
     * @return array
     */
    function qas_blog_db_get_user_visible_blog_post_count( $userid )
    {
        $all_posts = qas_blog_db_get_user_visible_postids( $userid );
        $blog_count = 0;
        foreach ( $all_posts as $post ) {
            if ( $post[ 'type' ] === 'B' ) {
                $blog_count++;
            }
        }

        return $blog_count;
    }

    /**
     * Return number of blog comments by an userid
     *
     * @param $userid
     *
     * @return array
     */
    function qas_blog_db_get_user_visible_blog_comment_count( $userid )
    {
        $all_posts = qas_blog_db_get_user_visible_postids( $userid );
        $comment_count = 0;
        foreach ( $all_posts as $post ) {
            if ( $post[ 'type' ] === 'C' ) {
                $comment_count++;
            }
        }

        return $comment_count;
    }


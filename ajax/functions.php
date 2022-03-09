<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    /**
     * handles category related ajax operations
     */
    function qas_blog_category()
    {
        require QAS_BLOG_DIR . '/ajax/blog-category.php';
    }

    /**
     * Handles Ajax requests for adding comments
     */
    function qas_blog_comment()
    {
        require QAS_BLOG_DIR . '/ajax/blog-comment.php';
    }

    /**
     * Handles Ajax requests for showing comments
     */
    function qas_blog_show_cs()
    {
        require QAS_BLOG_DIR . '/ajax/show-comments.php';
    }

    /**
     * Handles Ajax requests for Recalculation
     */
    function qas_blog_recalc()
    {
        require QAS_BLOG_DIR . '/ajax/recalc.php';
    }

    /**
     * Handles Admin Click Ajax requests
     */
    function qas_blog_click_admin()
    {
        require QAS_BLOG_DIR . '/ajax/click-admin.php';
    }

    /**
     * handles Ajax favriting requests
     */
    function qas_blog_favorite()
    {
        require QAS_BLOG_DIR . '/ajax/blog-favorite.php';
    }


    /**
     * Handles comment clicks
     */
    function qas_blog_click_comment()
    {
        require QAS_BLOG_DIR . '/ajax/click-comment.php';
    }


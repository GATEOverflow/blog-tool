<?php
    /*
        Plugin Name: Q2A Blog Tool
        Plugin URI: http://www.q2astore.com/
        Plugin Description: Ultimate Blogging Tool for Question2Answer websites
        Plugin Version: 1.6.0
        Plugin Date: 2018-08-31
        Plugin Author: Q2A-Store
        Plugin Author URI: http://www.q2a-store.com/
        Plugin License: GPLv2
        Plugin Minimum Question2Answer Version: 1.6
        Plugin Update Check URI:
    */

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../../' );
        exit;
    }

    //Define global constants
    @define( 'QAS_BLOG_VERSION', '1.6.0' );
    @define( 'QAS_BLOG_DIR', dirname( __FILE__ ) );
    @define( 'QAS_BLOG_FOLDER', basename( dirname( __FILE__ ) ) );

    /**
     * Base URL of the blog plugin
     */
    @define( 'QAS_BLOG_URL_BASE', 'blog' );
    @define( 'QAS_BLOG_URL_PLURAL_STRUCTURE', '^s' );

    @define( 'QAS_BLOG_SEARCH_MODULE', 'Blog Search' );
    @define( 'QAS_BLOG_FILTER_MODULE', 'Blog Filter Module' );

    //Define Entities for the posts , tags and categories
    @define( 'QAS_BLOG_ENTITY_POST', 'P' );
    @define( 'QAS_BLOG_ENTITY_TAG', 'G' );
    @define( 'QAS_BLOG_ENTITY_CATEGORY', 'Y' );
    @define( 'QAS_BLOG_DB_VERSION', 2 ); //will be useful to make changes to the database if any change occurs in later versions

    @define( 'QAS_BLOG_CURR_VERSION_ID', 3 ); //will be useful to make changes to mandatory options

    //ini_set('display_errors', '0');     // don't show any errors...
    //error_reporting(E_ALL | E_STRICT);  // ...but do log them

    //load necessary files for further processing
    require_once QAS_BLOG_DIR . '/utils/autoload.php';

    //register language modules
    qa_register_plugin_phrases( 'lang/blog-lang-*.php', 'qas_blog' );
    qa_register_plugin_phrases( 'lang/blog-email-lang-*.php', 'qas_emails' );
    qa_register_plugin_phrases( 'lang/blog-admin-lang-*.php', 'qas_admin' );
    qa_register_plugin_phrases( 'lang/blog-options-lang-*.php', 'qas_options' );
    qa_register_plugin_phrases( 'lang/blog-js-lang-*.php', 'qas_js' );

    //register override module
    qa_register_plugin_overrides( 'overrides/blog-overrides.php' );

    //register a layer for modifying the theme
    qa_register_plugin_layer( 'layer/blog-layer.php', 'Blog Layer' );

    //register the page modules
    qa_register_plugin_module( 'page', 'pages/blog-post-page.php', 'qas_blog_post_single', 'Single Blog Page' );
    qa_register_plugin_module( 'page', 'pages/blog-new-page.php', 'qas_blog_new', 'New Blog Page' );
    qa_register_plugin_module( 'page', 'pages/blog-tag-page.php', 'qas_blog_tag', 'Blog Tag Page' );
    qa_register_plugin_module( 'page', 'pages/blog-categories-page.php', 'qas_blog_categories', 'Blog Categories Page' );
    qa_register_plugin_module( 'page', 'pages/blog-user-posts-page.php', 'qas_blog_user_posts', 'Blog Users Post Page' );
    qa_register_plugin_module( 'page', 'pages/blog-user-drafts-page.php', 'qas_blog_user_drafts', 'Blog Users Drafts Page' );
    qa_register_plugin_module( 'page', 'pages/blog-xml-sitemap.php', 'blog_xml_sitemap', 'Blog XML SiteMap' );
    qa_register_plugin_module( 'page', 'pages/blog-comments.php', 'qas_blog_comments', 'Blog Comments page' );
    qa_register_plugin_module( 'page', 'pages/blog-ajax-page.php', 'qas_blog_ajax', 'Blog Ajax page' );

    //register the Event modules for handeling special blog events
    qa_register_plugin_module( 'event', 'event-listener/email-notifier.php', 'qas_blog_email_notifier', 'QAS Email Notifier' );
    qa_register_plugin_module( 'event', 'install/qas-blog-install.php', 'qas_blog_install', 'QAS Blog Installation Module' );

    //register search module for indexing and unindexing
    qa_register_plugin_module( 'search', 'search/qa-search-blog.php', 'qa_search_blog', QAS_BLOG_SEARCH_MODULE );

    //register filter module for publishing posts
    qa_register_plugin_module( 'filter', 'utils/blog-filter.php', 'qas_blog_filter_posts', QAS_BLOG_FILTER_MODULE );

    //register widgets
    qa_register_plugin_module( 'widget', 'widgets/qas-recent-blog-posts-widget.php', 'qas_recent_blog_posts_widget', 'QAS Recent Blog Posts Widget' );
    qa_register_plugin_module( 'widget', 'widgets/qas-recent-blog-comments-widget.php', 'qas_recent_blog_comments_widget', 'QAS Recent Blog Comments Widget' );
    qa_register_plugin_module( 'widget', 'widgets/qas-related-blog-posts-widget.php', 'qas_related_blog_posts_widget', 'QAS Related Blog Posts Widget' );
    qa_register_plugin_module( 'widget', 'widgets/qas-blog-ads-widget.php', 'qas_blog_ads_widget', 'QAS Ads Widget' );

    /*
        Omit PHP closing tag to help avoid accidental output
    */

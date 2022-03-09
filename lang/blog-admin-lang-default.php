<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }
    
    return array(
        'new_blog'                 => 'New Blog',
        'admin_title'              => 'Blog Tool Administration center',
        'blog_category'            => 'Category',
        'delete_category_reassign' => 'Delete this category and reassign its posts to:',
        'allow_no_category'        => 'Allow posts with no category',
        'allow_no_sub_category'    => 'Allow posts with a category but no sub-category',
        'category_no_sub_error'    => '^q posts/s in this category have no sub-category - ^1set sub-category^2',
        'category_no_sub_to'       => 'Move posts in ^ with no sub-category to:',
        'category_none_error'      => '^q posts/s currently have no category - ^1set category^2',
        'category_none_to'         => 'Move posts with no category to:',
        'total_posts'              => 'Total posts:',
        'blog_category_title'      => 'Blog/Categories',
        'blog_flagged_title'       => 'Blog/Flagged',
        'blog_hidden_title'        => 'Blog/Hidden',
        'blog_moderate_title'      => 'Blog/Moderate',
        'blog_recalc_title'        => 'Blog/Recalc',
        'blog_general_title'       => 'Blog/General',
        'blog_permissions_title'   => 'Blog/Permissions',
        'blog_posting_title'       => 'Blog/Posting',
        'blog_viewing_title'       => 'Blog/Viewing',
        'blog_layout_title'        => 'Blog/Layout',
        'blog_spam_title'          => 'Blog/Spam',
        'blog_stats_title'         => 'Blog/Stats',
        'blog_drafts_title'        => 'Blog/Drafts',
        'no_drafts_found'          => 'No Draft posts found',
        'recent_drafts_title'      => 'Recent Draft posts',
        'delete_draft_popup'       => 'Delete Draft',
        'delete_hidden_note'       => ' - all hidden posts and comments without dependents',
        'admin_notes'              => 'Please visit to the Admin Section for customizing the blog plugin . Here only you can only reset the blog plugin settings to its defaults',
    );


    /*
        Omit PHP closing tag to help avoid accidental output
    */
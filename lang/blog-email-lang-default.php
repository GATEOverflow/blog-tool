<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    return array(
        'blog_posted_body'       => "A new blog has been posted by ^q_handle:\n\n^open^q_title\n\n^q_content^close\n\nClick below to see the question:\n\n^url\n\nThank you,\n\n^site_title",
        'blog_posted_subject'    => '^site_title has a new post',

        'blog_commented_body'    => "Your post on ^site_title has a new comment by ^c_handle:\n\n^open^c_content^close\n\nYour post was:\n\n^open^c_context^close\n\nYou may respond by adding your own comment:\n\n^url\n\nThank you,\n\n^site_title",
        'blog_commented_subject' => 'Your ^site_title post has a new comment',

        'remoderate_body'        => "An edited post by ^p_handle requires your reapproval:\n\n^open^p_context^close\n\nClick below to approve or hide the edited post:\n\n^url\n\n\nClick below to review all queued posts:\n\n^a_url\n\n\nThank you,\n\n^site_title",
        'remoderate_subject'     => '^site_title blog moderation',

        'moderate_body'          => "A post by ^p_handle requires your approval:\n\n^open^p_context^close\n\nClick below to approve or reject the post:\n\n^url\n\n\nClick below to review all queued posts:\n\n^a_url\n\n\nThank you,\n\n^site_title",
        'moderate_subject'       => '^site_title blog moderation',

        'c_commented_body'       => "A new comment by ^c_handle has been added after your comment on ^site_title:\n\n^open^c_content^close\n\nThe discussion is following:\n\n^open^c_context^close\n\nYou may respond by adding another comment:\n\n^url\n\nThank you,\n\n^site_title",
        'c_commented_subject'    => 'Your ^site_title comment has been added to',

    );


    /*
        Omit PHP closing tag to help avoid accidental output
    */
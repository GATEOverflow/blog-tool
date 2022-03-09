<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }
    
    class qas_blog_install
    {
        const RESET_BTN = 'qas_blog_admin_reset_btn';

        public function init_queries( $table_list )
        {
            $queries = array();
            $useridcoltype = qa_db_user_column_type_verify();

            $tables = array(
                'blog_categories'    => array(
                    'categoryid' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
                    'parentid'   => 'INT UNSIGNED',
                    'title'      => 'VARCHAR(' . QA_DB_MAX_CAT_PAGE_TITLE_LENGTH . ') NOT NULL', // category name
                    'tags'       => 'VARCHAR(' . QA_DB_MAX_CAT_PAGE_TAGS_LENGTH . ') NOT NULL', // slug (url fragment) used to identify category
                    'content'    => 'VARCHAR(' . QA_DB_MAX_CAT_CONTENT_LENGTH . ') NOT NULL DEFAULT \'\'', // description of category
                    'qcount'     => 'INT UNSIGNED NOT NULL DEFAULT 0',
                    'position'   => 'SMALLINT UNSIGNED NOT NULL',
                    'backpath'   => 'VARCHAR(' . ( QA_CATEGORY_DEPTH * ( QA_DB_MAX_CAT_PAGE_TAGS_LENGTH + 1 ) ) . ') NOT NULL DEFAULT \'\'',
                    // full slug path for category, with forward slash separators, in reverse order to make index from effective
                    'PRIMARY KEY (categoryid)',
                    'UNIQUE parentid (parentid, tags)',
                    'UNIQUE parentid_2 (parentid, position)',
                    'KEY backpath (backpath(' . QA_DB_MAX_CAT_PAGE_TAGS_LENGTH . '))',
                ),

                'blogs'              => array(
                    'postid'     => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
                    'type'       => "ENUM('B', 'C', 'D' ,'B_HIDDEN', 'C_HIDDEN', 'B_QUEUED', 'C_QUEUED', 'NOTE') NOT NULL",
                    'parentid'   => 'INT UNSIGNED', // for comments
                    'categoryid' => 'INT UNSIGNED', // this is the canonical final category id
                    'catidpath1' => 'INT UNSIGNED', // the catidpath* columns are calculated from categoryid, for the full hierarchy of that category
                    'catidpath2' => 'INT UNSIGNED', // note that QA_CATEGORY_DEPTH=4
                    'catidpath3' => 'INT UNSIGNED',
                    'acount'     => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0', // number of answers (for questions)
                    'amaxvote'   => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0', // highest netvotes of child answers (for questions)
                    'selchildid' => 'INT UNSIGNED', // selected answer (for questions)
                    'closedbyid' => 'INT UNSIGNED', // not null means question is closed
                    // if closed due to being a duplicate, this is the postid of that other question
                    // if closed for another reason, that reason should be added as a comment on the question, and this field is the comment's id
                    'userid'     => $useridcoltype, // which user wrote it
                    'cookieid'   => 'BIGINT UNSIGNED', // which cookie wrote it, if an anonymous post
                    'createip'   => 'INT UNSIGNED', // INET_ATON of IP address used to create the post
                    'lastuserid' => $useridcoltype, // which user last modified it
                    'lastip'     => 'INT UNSIGNED', // INET_ATON of IP address which last modified the post
                    'upvotes'    => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
                    'downvotes'  => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
                    'netvotes'   => 'SMALLINT NOT NULL DEFAULT 0',
                    'lastviewip' => 'INT UNSIGNED', // INET_ATON of IP address which last viewed the post
                    'views'      => 'INT UNSIGNED NOT NULL DEFAULT 0',
                    'hotness'    => 'FLOAT',
                    'flagcount'  => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
                    'format'     => 'VARCHAR(' . QA_DB_MAX_FORMAT_LENGTH . ') CHARACTER SET ascii NOT NULL DEFAULT \'\'', // format of content, e.g. 'html'
                    'created'    => 'DATETIME NOT NULL',
                    'updated'    => 'DATETIME', // time of last update
                    'updatetype' => 'CHAR(1) CHARACTER SET ascii', // see qa-app-updates.php
                    'title'      => 'VARCHAR(' . QA_DB_MAX_TITLE_LENGTH . ')',
                    'content'    => 'VARCHAR(' . QA_DB_MAX_CONTENT_LENGTH . ')',
                    'tags'       => 'VARCHAR(' . QA_DB_MAX_TAGS_LENGTH . ')', // string of tags separated by commas
                    'name'       => 'VARCHAR(' . QA_DB_MAX_NAME_LENGTH . ')', // name of author if post anonymonus
                    'notify'     => 'VARCHAR(' . QA_DB_MAX_EMAIL_LENGTH . ')', // email address, or @ to get from user, or NULL for none
                    'PRIMARY KEY (postid)',
                    'KEY type (type, created)', // for getting recent questions, answers, comments
                    'KEY type_2 (type, acount, created)', // for getting unanswered questions
                    'KEY type_4 (type, netvotes, created)', // for getting posts with the most votes
                    'KEY type_5 (type, views, created)', // for getting questions with the most views
                    'KEY type_6 (type, hotness)', // for getting 'hot' questions
                    'KEY type_7 (type, amaxvote, created)', // for getting questions with no upvoted answers
                    'KEY parentid (parentid, type)', // for getting a question's answers, any post's comments and follow-on questions
                    'KEY userid (userid, type, created)', // for recent questions, answers or comments by a user
                    'KEY selchildid (selchildid, type, created)', // for counting how many of a user's answers have been selected, unselected qs
                    'KEY closedbyid (closedbyid)', // for the foreign key constraint
                    'KEY catidpath1 (catidpath1, type, created)', // for getting question, answers or comments in a specific level category
                    'KEY catidpath2 (catidpath2, type, created)', // note that QA_CATEGORY_DEPTH=4
                    'KEY catidpath3 (catidpath3, type, created)',
                    'KEY categoryid (categoryid, type, created)', // this can also be used for searching the equivalent of catidpath4
                    'KEY createip (createip, created)', // for getting posts created by a specific IP address
                    'KEY updated (updated, type)', // for getting recent edits across all categories
                    'KEY flagcount (flagcount, created, type)', // for getting posts with the most flags
                    'KEY catidpath1_2 (catidpath1, updated, type)', // for getting recent edits in a specific level category
                    'KEY catidpath2_2 (catidpath2, updated, type)', // note that QA_CATEGORY_DEPTH=4
                    'KEY catidpath3_2 (catidpath3, updated, type)',
                    'KEY categoryid_2 (categoryid, updated, type)',
                    'KEY lastuserid (lastuserid, updated, type)', // for getting posts edited by a specific user
                    'KEY lastip (lastip, updated, type)', // for getting posts edited by a specific IP address
                    'CONSTRAINT ^blogs_ibfk_2 FOREIGN KEY (parentid) REFERENCES ^blogs(postid)', // ^blogs_ibfk_1 is set later on userid
                    'CONSTRAINT ^blogs_ibfk_3 FOREIGN KEY (categoryid) REFERENCES ^blog_categories(categoryid) ON DELETE SET NULL',
                    'CONSTRAINT ^blogs_ibfk_4 FOREIGN KEY (closedbyid) REFERENCES ^blogs(postid)',
                ),

                'blog_words'         => array(
                    'wordid'       => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
                    'word'         => 'VARCHAR(' . QA_DB_MAX_WORD_LENGTH . ') NOT NULL',
                    'titlecount'   => 'INT UNSIGNED NOT NULL DEFAULT 0', // only counts one per post
                    'contentcount' => 'INT UNSIGNED NOT NULL DEFAULT 0', // only counts one per post
                    'tagwordcount' => 'INT UNSIGNED NOT NULL DEFAULT 0', // for words in tags - only counts one per post
                    'tagcount'     => 'INT UNSIGNED NOT NULL DEFAULT 0', // for tags as a whole - only counts one per post (though no duplicate tags anyway)
                    'PRIMARY KEY (wordid)',
                    'KEY word (word)',
                    'KEY tagcount (tagcount)', // for sorting by most popular tags
                ),

                'blog_titlewords'    => array(
                    'postid' => 'INT UNSIGNED NOT NULL',
                    'wordid' => 'INT UNSIGNED NOT NULL',
                    'KEY postid (postid)',
                    'KEY wordid (wordid)',
                    'CONSTRAINT ^blog_titlewords_ibfk_1 FOREIGN KEY (postid) REFERENCES ^blogs(postid) ON DELETE CASCADE',
                    'CONSTRAINT ^blog_titlewords_ibfk_2 FOREIGN KEY (wordid) REFERENCES ^blog_words(wordid)',
                ),

                'blog_contentwords'  => array(
                    'postid'     => 'INT UNSIGNED NOT NULL',
                    'wordid'     => 'INT UNSIGNED NOT NULL',
                    'count'      => 'TINYINT UNSIGNED NOT NULL', // how many times word appears in the post - anything over 255 can be ignored
                    'type'       => "ENUM('B', 'C', 'NOTE') NOT NULL", // the post's type (copied here for quick searching)
                    'questionid' => 'INT UNSIGNED NOT NULL', // the id of the post's antecedent parent (here for quick searching)
                    'KEY postid (postid)',
                    'KEY wordid (wordid)',
                    'CONSTRAINT ^blog_contentwords_ibfk_1 FOREIGN KEY (postid) REFERENCES ^blogs(postid) ON DELETE CASCADE',
                    'CONSTRAINT ^blog_contentwords_ibfk_2 FOREIGN KEY (wordid) REFERENCES ^blog_words(wordid)',
                ),

                'blog_tagwords'      => array(
                    'postid' => 'INT UNSIGNED NOT NULL',
                    'wordid' => 'INT UNSIGNED NOT NULL',
                    'KEY postid (postid)',
                    'KEY wordid (wordid)',
                    'CONSTRAINT ^blog_tagwords_ibfk_1 FOREIGN KEY (postid) REFERENCES ^blogs(postid) ON DELETE CASCADE',
                    'CONSTRAINT ^blog_tagwords_ibfk_2 FOREIGN KEY (wordid) REFERENCES ^blog_words(wordid)',
                ),

                'blog_posttags'      => array(
                    'postid'      => 'INT UNSIGNED NOT NULL',
                    'wordid'      => 'INT UNSIGNED NOT NULL',
                    'postcreated' => 'DATETIME NOT NULL', // created time of post (copied here for tag page's list of recent questions)
                    'KEY postid (postid)',
                    'KEY wordid (wordid,postcreated)',
                    'CONSTRAINT ^blog_posttags_ibfk_1 FOREIGN KEY (postid) REFERENCES ^blogs(postid) ON DELETE CASCADE',
                    'CONSTRAINT ^blog_posttags_ibfk_2 FOREIGN KEY (wordid) REFERENCES ^blog_words(wordid)',
                ),

                'blog_postmetas'     => array(
                    'postid'  => 'INT UNSIGNED NOT NULL',
                    'title'   => 'VARCHAR(' . QA_DB_MAX_META_TITLE_LENGTH . ') NOT NULL',
                    'content' => 'VARCHAR(' . QA_DB_MAX_META_CONTENT_LENGTH . ') NOT NULL',
                    'PRIMARY KEY (postid, title)',
                    'CONSTRAINT ^blog_postmetas_ibfk_1 FOREIGN KEY (postid) REFERENCES ^blogs(postid) ON DELETE CASCADE',
                ),

                'blog_categorymetas' => array(
                    'categoryid' => 'INT UNSIGNED NOT NULL',
                    'title'      => 'VARCHAR(' . QA_DB_MAX_META_TITLE_LENGTH . ') NOT NULL',
                    'content'    => 'VARCHAR(' . QA_DB_MAX_META_CONTENT_LENGTH . ') NOT NULL',
                    'PRIMARY KEY (categoryid, title)',
                    'CONSTRAINT ^blog_categorymetas_ibfk_1 FOREIGN KEY (categoryid) REFERENCES ^blog_categories(categoryid) ON DELETE CASCADE',
                ),

                'blog_tagmetas'      => array(
                    'tag'     => 'VARCHAR(' . QA_DB_MAX_WORD_LENGTH . ') NOT NULL',
                    'title'   => 'VARCHAR(' . QA_DB_MAX_META_TITLE_LENGTH . ') NOT NULL',
                    'content' => 'VARCHAR(' . QA_DB_MAX_META_CONTENT_LENGTH . ') NOT NULL',
                    'PRIMARY KEY (tag, title)',
                ),
            );

            foreach ( $tables as $table_name => $table_definition ) {

                $prefixed_table_name = qa_db_add_table_prefix( $table_name );

                if ( !in_array( $prefixed_table_name, $table_list ) ) {
                    //if the table does not exists then add that query to the list
                    $queries[] = qa_db_create_table_sql( $table_name, $table_definition );
                }

            }

            if ( count( $queries ) ) {
                return $queries;
            }

            //if all tables has been created successfully then check for any changes in the particular DB versions

            $current_db_version = (int) qa_opt( 'qas_blog_curr_db_version' );

            if ( $current_db_version < QAS_BLOG_DB_VERSION ) {
                
                $upgrade_queries = $this->qas_blog_upgrade_tables( $current_db_version );

                if ( count( $upgrade_queries ) ) {
                    return $upgrade_queries;
                }

            }

            $defauls_set = (int) qa_opt( 'qas_blog_defaults_set_ok' );

            if ( !$defauls_set ) { //if default options are not set , set it up
                qas_blog_reset_all_blog_options();
                qa_opt( 'qas_blog_defaults_set_ok', 1 );
            }

            $this->qas_blog_set_mandatory_options();

            qa_opt( 'qas_blog_curr_db_version', QAS_BLOG_DB_VERSION );

            return null;

        }

        private function qas_blog_upgrade_tables( $current_db_version )
        {
            $queries = [];

            for ( $version = ++$current_db_version ; $version <= QAS_BLOG_DB_VERSION ; $version++ ) {
                
                switch ($version) {
                    
                    case 1 :
                        //Nothing to do;
                        break ;
                        
                    case 2 : //changed added in version 2
                        //added reply to column
                        $columns=qa_db_read_all_values(qa_db_query_sub('describe ^blogs'));
                        if( !in_array('reply_to', $columns ) )
                        {
                            $queries[] = 'ALTER TABLE ^blogs ADD `reply_to` INT UNSIGNED DEFAULT NULL AFTER `parentid`';
                            $queries[] = 'ALTER TABLE ^blogs ADD INDEX qa_blogs_reply_to_idx (`reply_to`)';
                            $queries[] = 'ALTER TABLE ^blogs ADD CONSTRAINT `qa_blogs_ibfk_5` FOREIGN KEY (`reply_to`) REFERENCES ^blogs(`postid`) ON DELETE CASCADE ON UPDATE RESTRICT';
                        }

                        //omitting break is intentional
                        break;

                    default:
                        break;
                }

            }

            return $queries;
        }

        private function qas_blog_set_mandatory_options()
        {
            $current_opt_id = (int) qa_opt( 'qas_blog_curr_opt_id' );
            for ( $current_opt_id++ ; $current_opt_id <= QAS_BLOG_CURR_VERSION_ID ; $current_opt_id++ ) {
                $this->reset_opt_for_id( $current_opt_id );
            }
            //set the current version to the database
            qa_opt( 'qas_blog_curr_opt_id', QAS_BLOG_CURR_VERSION_ID );
        }

        private function reset_opt_for_id( $id )
        {
            $reset_options = array();

            switch ( $id ) {
                case 1 :
                    $reset_options = array( 'qas_blog_page_size_ps', 'qas_blog_featured_page_size_ps', 'qas_blog_show_post_updates', 'qas_blog_xml_sitemap_show_posts',
                        'qas_blog_xml_sitemap_show_tag_ps', 'qas_blog_xml_sitemap_show_category_ps', 'qas_blog_xml_sitemap_show_categories',
                        'qas_blog_recent_comments_w_trunc', 'qas_blog_recent_comments_w_trunc_len', 'qas_blog_recent_comments_widg_count' );
                    break;
                case 2 :
                    $reset_options = array( 'qas_blog_permit_delete' );
                    break;
                case 3 :
                    $reset_options = array('qas_blog_allow_nested_cmnts' , 'qas_blog_max_allow_nesting');
            }

            if ( count( $reset_options ) ) {
                qas_blog_reset_options( $reset_options );
            }
        }

        public function admin_form( &$qa_content )
        {
            $saved = false;
            $error = false;

            if ( qa_clicked( self::RESET_BTN ) ) {
                if ( qa_check_form_security_code( 'qas_admin/admin_options', qa_post_text( 'code' ) ) ) {
                    if ( qas_blog_reset_all_blog_options() ) {
                        $saved = true;
                        qa_opt( 'qas_blog_defaults_set_ok', 1 );
                    }
                } else {
                    $error = qa_lang_html( 'admin/form_security_expired' );
                }
            }

            $form = array(
                'ok'      => $saved ? qa_lang( 'admin/options_saved' ) : null,
                'fields'  => array(
                    'simple_note' => array(
                        'type'  => 'static',
                        'label' => qa_lang_html( 'qas_admin/admin_notes' ),
                        'error' => $error,
                    ),
                ),
                'buttons' => array(
                    array(
                        'label' => qa_lang_html( 'admin/reset_options_button' ),
                        'tags'  => 'NAME="' . self::RESET_BTN . '"',
                    ),
                ),
                'hidden'  => array(
                    'code' => qa_get_form_security_code( 'qas_admin/admin_options' ),
                ),
            );

            return $form;
        }

        public function process_event( $event, $userid, $handle, $cookieid, $params )
        {

        }

    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */
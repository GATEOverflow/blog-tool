<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }
    
    class qas_recent_blog_comments_widget
    {
        private $directory;

        public function load_module( $directory, $urltoroot )
        {
            $this->directory = $directory;
        }

        function allow_template( $template )
        {
            return ($template != 'admin');
		$allow = false;
            switch ( $template ) {
                case 'blogs':
                case 'blog':
                case 'blog-tag':
                case 'blog-tags':
                case 'blog-categories':
                    $allow = true;
                    break;
            }

            return $allow;
        }

        function allow_region( $region )
        {
            return ( $region == 'side' );
        }

        function output_widget( $region, $place, $themeobject, $template, $request, $qa_content )
        {
            $posts = $this->recent_comments( qa_opt( 'qas_blog_recent_comments_widg_count' ) );
            $titlehtml = qa_lang_html( count( $posts ) ? 'qas_blog/recent_comments_title' : 'qas_blog/no_recent_comments_title' );

            $themeobject->output( '
			  <div class="qa-related-qs" id="qas-recent-comments">
				<h2 style="margin-top:0; padding-top:0;">' . $titlehtml . '</h2>
				' );

            if ( count( $posts ) ) {
                $themeobject->output( '<ul class="qa-related-q-list">' );
                foreach ( $posts as $post ) {
                    $postlink = qas_blog_post_path( $post[ 'parentid' ], null, true, 'C', $post[ 'postid' ] );
                    //$post_content = qas_blog_strip_tags( $post[ 'content' ] );//arjun
                    $post_content = strip_tags( $post[ 'content' ] );

                    if ( qa_opt( 'qas_blog_recent_comments_w_trunc' ) ) {
                        $post_content = qas_blog_truncate_string( $post_content, qa_opt( 'qas_blog_recent_comments_w_trunc_len' ) );
                        $post_content = qas_blog_restore_tags( $post_content );
                    }

                    $themeobject->output( '<li class="qa-related-q-item">' );
                    $themeobject->output( '<a href="' . $postlink . '">' . $post_content . '</a>' );//arjun
//                    $themeobject->output( '<a href="' . $postlink . '">' . strip_tags($post_content) . '</a>' );
                    $themeobject->output( '</li>' );
                }
                $themeobject->output( '</ul>' );
            }

            $themeobject->output( '</div>' );
        }

        function recent_comments( $limit, $order = 'DESC' )
        {
            $order_by = ' ^blogs.created ';
            $type = 'C';

            return qa_db_read_all_assoc( qa_db_query_sub( 'SELECT UNIX_TIMESTAMP(^blogs.created) as unix_time, ^blogs.parentid , ^blogs.postid , ^blogs.content as content FROM ^blogs WHERE ^blogs.type=$ ORDER BY ' . $order_by . ' ' . $order . ' LIMIT #', $type, $limit ) );
        }

    }

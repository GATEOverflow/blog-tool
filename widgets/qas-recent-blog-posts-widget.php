<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    class qas_recent_blog_posts_widget
    {
        private $directory;

        public function load_module( $directory, $urltoroot )
        {
            $this->directory = $directory;
        }

        function allow_template( $template )
        {
           return ( $template != 'admin' );
        }

        function allow_region( $region )
        {
            return ( $region == 'side' );
        }

        function output_widget( $region, $place, $themeobject, $template, $request, $qa_content )
        {
            $userid = qa_get_logged_in_userid();
            $posts = qa_db_select_with_pending(
                qas_blog_db_blogs_selectspec( $userid, 'created', 0, '', null, false, false, qa_opt( 'qas_blog_recent_post_widg_count' ) )
            );

            $titlehtml = qa_lang_html( count( $posts ) ? 'qas_blog/recent_posts_title' : 'qas_blog/no_recent_posts_title' );
            $themeobject->output( '
			  <div class="qa-related-qs" id="qas-recent-posts">
				<h2 style="margin-top:0; padding-top:0;">' . $titlehtml . '</h2>
				' );

            $i = 0;
            if ( count( $posts ) ) {
                $themeobject->output( '<ul class="qa-related-q-list">' );
                foreach ( $posts as $post ) {
                    $i++;
                    $postid = $post[ 'postid' ];
                    $postlink = qas_blog_path_html( qa_q_request( $postid, $post[ 'title' ] ), null, qa_opt( 'site_url' ) );
                    $themeobject->output( '<li class="qa-related-q-item">' );
                    $themeobject->output( '<a href="' . $postlink . '">' . $post[ 'title' ] . '</a>' );
                    $themeobject->output( '</li>' );
                }
                $themeobject->output( '</ul>' );
            }

            $themeobject->output( '</div>' );
        }
    }

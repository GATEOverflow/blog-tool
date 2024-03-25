<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    class qa_html_theme_layer extends qa_html_theme_base
    {

        /**
         * Doctype function
         */
        function doctype()
        {
            if ( !property_exists( 'qa_html_theme_base', 'isRTL' ) ) {
                /*Fall back for the version 1.6.3*/
                $this->isRTL = isset( $content['direction'] ) && $content['direction'] === 'rtl';
            }

            $this->add_profile_blogs_sub_nav();
            $this->update_search_form_for_blogs();
            $this->show_post_count_on_profile();

            parent::doctype();
        }

        public function add_profile_blogs_sub_nav()
        {
            $content = &$this->content;

            if ( isset( $content['navigation']['sub']['profile'] ) ) {
                //means the control is on the profile page , so display here the user blogs menu
                $sub_nav = &$content['navigation']['sub'];
                $handle = qa_request_part( 1 );
                qas_blog_user_sub_navigation( $sub_nav, $handle ? $handle : qa_get_logged_in_handle() );
            }
        }

        public function update_search_form_for_blogs()
        {
            if ( qas_is_blog_page( $this->template ) && qa_opt( 'qas_blog_auto_update_search_box' ) ) {
                $this->content['search'] = array(
                    'form_tags'    => 'method="get" action="' . qa_path_html( qas_get_blog_url_sub( '^/search' ) ) . '" ',
                    'form_extra'   => qa_path_form_html( 'search' ),
                    'title'        => qa_lang_html( 'main/search_title' ),
                    'field_tags'   => 'name="q" class="qa-search-field form-control"',
                    'button_label' => qa_lang_html( 'qas_blog/blog_search_button' ),
                );
            }
        }

        public function show_post_count_on_profile()
        {
            if ( $this->template == 'user' &&
                qa_opt( 'qas_blog_show_user_post_count' ) &&
                !empty( $this->content["form_activity"]['fields']['questions'] )
            ) {

                $handle = qa_request_part( 1 );
                $user_id = qa_handle_to_userid( $handle );
                $num_of_posts = qas_blog_db_get_user_visible_blog_post_count( $user_id );
                $posts_info = array(
                    'type'  => 'static',
                    'id'    => 'posts',
                    'label' => qa_lang( 'qas_blog/posts' ),
                    'value' => '<span class="qa-uf-user-q-posts">' . $num_of_posts . '</span>',
                );
                qa_array_insert( $this->content["form_activity"]['fields'], 'questions', array( 'posts' => $posts_info ) );
            }
        }

        /**
         * Adding blog javascript file to the website
         *
         * @since Blog Tool 1.0
         */
        public function head_script()
        {

            if(qas_is_blog_page($this->template) || $this->template == 'admin'){
                $jsUrl = qas_blog_plugin_url() . '/js/blog.js?'.QAS_BLOG_VERSION;
                $this->content['script'][] = '<script src="' . $jsUrl . '"></script>';
            }

            parent::head_script();

        }

        public function q_view_content( $q_view )
        {
            if ( $this->template == 'blog' && qa_opt( 'site_theme' ) === 'Snow' )
                $this->output_split( @$q_view['views'], 'qa-view-count' );

            parent::q_view_content( $q_view );
        }

        /**
         * Adding javascript code to be used for ajax requestes
         *
         * @since Blog Tool 1.0
         */
        public function body_script()
        {
            parent::body_script();
            $blog_ajax_url = qa_js( qas_blog_path_html('ajax') );

            $js_lang = require QAS_BLOG_DIR . '/utils/blog-js-page-lang.php';
            $js_lang_json = json_encode( $js_lang );

            $js_settings = qas_blog_get_js_settings();
            $js_settings_js = json_encode( $js_settings );

            $this->output(
                '<script>',
                "var qas_blog_root = $blog_ajax_url;",
                "var qas_blog_language_obj = $js_lang_json ;",
                "var qas_blog_settings_obj = $js_settings_js ;",
                '</script>'
            );
        }

        /**
         * Adding css for the blog tool
         *
         * @since Blog Tool 1.0
         */
        public function head_css()
        {
            parent::head_css();

            if(qas_is_blog_page($this->template)) {
                $css_link = $this->get_css_file_for_theme( qa_opt( 'site_theme' ), qas_blog_plugin_url() . '/css/' );
                $this->output( '<link rel="stylesheet" type="text/css" href="' . $css_link . '"/>' );

                if ( $this->isRTL ) {
                    $rtl_css_link = qas_blog_plugin_url() . '/css/rtl.css?'.QAS_BLOG_VERSION;
                    $this->output( '<link rel="stylesheet" type="text/css" href="' . $rtl_css_link . '"/>' );
                }
            }
        }

        public function q_item_main( $q_item )
        {
            if ( $this->template == 'blogs' && !empty( $q_item['content'] ) ) {
                $this->output( '<div class="qa-q-item-main">' );

                $this->view_count( $q_item );
                $this->q_item_title( $q_item );

                $this->post_avatar_meta( $q_item, 'qa-q-item' );
                $this->post_tags( $q_item, 'qa-q-item' );
                $this->q_item_buttons( $q_item );

                $this->output( '</div>' );
            } else {
                parent::q_item_main( $q_item );
            }
        }

        /**
         * Add the first image (if exists) to the title in the blog list
         *
         * @param $q_item
         */
        public function q_item_title( $q_item )
        {
            if ( $this->template == 'blogs' && !empty( $q_item['content'] ) ) {
                $post_content = $q_item['content'];
                if ( qa_opt( 'qas_blog_show_image_on_list' ) ) {
                    $image = qas_blog_get_image_from_post( $post_content );
                } else {
                    $image = '';
                }

                $this->output(
                    '<div class="qa-q-item-title">',
                    '<a href="' . $q_item['url'] . '">' . ( !empty( $image ) ? '<img src="' . $image . '" class="featured-image" />' : '' ) . $q_item['title'] . '</a>',
                    // add closed note in title
                    empty( $q_item['closed'] ) ? '' : ' [' . $q_item['closed']['state'] . ']',
                    '</div>'
                );

                if ( qa_opt( 'qas_blog_show_content_on_list' ) ) {
                    //$content = qas_blog_strip_tags( $post_content );
                    $content =  $post_content;
                    if ( qa_opt( 'qas_blog_list_content_trunc' ) ) {
                        $trunc_length = qa_opt( 'qas_blog_show_content_on_list_len' );
			//$content = qas_blog_truncate_string( $content, $trunc_length );
		//	$content = qa_shorten_string_line($content, $trunc_length);
		//	$content = qa_shorten_string_line($content, $trunc_length);
		//	qa_viewer_text( $post[ 'content' ], $post[ 'format' ] )
                        //restore the opened tags
                       // $content = qas_blog_restore_tags( $content );
                    } else {
                        //other wise display full . No need to do anything
                    }

                    $this->output( '<div class="q-list-item-content" >' );
                    $this->output( $content );
                    $this->output( '</div>' );

                    if ( qa_opt( 'qas_blog_show_read_more_btn' ) ) {
                        $this->read_more_button( $q_item['url'] );
                    }
                }

            } else {
                parent::q_item_title( $q_item );
            }

        }

        function read_more_button( $url )
        {
            $this->output( '<div class="q-list-item-read-more" >' );
            $this->output( '<a href="' . $url . '" class="qa-form-light-button read-more-btn">' . qa_lang( 'qas_blog/read_more' ) . '</a>' );
            $this->output( '</div>' );
        }

        public function search_field( $search )
        {
            if ( qas_is_blog_page( $this->template ) && qa_opt( 'qas_blog_auto_update_search_box' ) ) {

                if(qa_opt('site_theme') == 'Donut-theme')
                    $this->output( '<div class="input-group">');

                $this->output( '<input type="text" ' . $search['field_tags'] . ' value="' . @$search['value'] . '" placeholder="' . $search['button_label'] . '" class="qa-search-field"/>' );

                if(qa_opt('site_theme') == 'Donut-theme') {
                    $this->search_button( $search );
                    $this->output( '</div>' );
                }

            } else {
                parent::search_field( $search );
            }
        }

        public function body_tags()
        {
            $class = 'qa-template-' . qa_html( $this->template );

            if ( isset( $this->content['categoryids'] ) ) {
                foreach ( $this->content['categoryids'] as $categoryid )
                    $class .= ' qa-category-' . qa_html( $categoryid );
            }

            if ( @$this->content['grid_view'] ) {
                $class .= ' grid masonry-disabled';
            }

            $this->output( 'class="' . $class . ' qa-body-js-off"' );
        }

        public function q_view( $q_view )
        {
            if ( qas_is_blog_page( $this->template ) ) {
                if ( qa_opt( 'qas_blog_adcode_before_post' ) && qa_opt( 'qas_blog_adcode_before_post_content' ) ) {
                    $this->output( '<div class="qas-blog-ads-before-post">' );
                    $this->output( qa_opt( 'qas_blog_adcode_before_post_content' ) );
                    $this->output( '</div>' );
                }
            }
            parent::q_view( $q_view );

        }

        public function post_tags( $post, $class )
        {
            parent::post_tags( $post, $class );

            // Next and previous buttons for blogs
            if( $this->template == 'blog' && qa_opt( 'qas_blog_show_next_prev' )  && isset($this->content['q_view']['raw']) && isset($this->content['q_view']['raw']['postid'])){

                $postid = $this->content['q_view']['raw']['postid'];
                $prev_post = qas_blog_db_get_prevpost_info( $postid );
                $next_post = qas_blog_db_get_nextpost_info( $postid );

                if(count($prev_post) || count($next_post)){
                    $this->output('<div class="qas-blog-next-prev-wrapper clearfix">');
                }

                if(count($prev_post)){
                    $prev_post_path = qas_blog_post_path( $prev_post[ 'postid' ], $prev_post[ 'title' ] );
                    $this->output('<a href="'. $prev_post_path .'" title="'. $prev_post[ 'title' ] .'" class="btn btn-default qas-blog-prev-post-btn">'.qas_blog_lang('prev_post').$prev_post[ 'title' ].'</a>');
                }

                if(count($next_post)){
                    $next_post_path = qas_blog_post_path( $next_post[ 'postid' ], $next_post[ 'title' ] );
                    $this->output('<a href="'. $next_post_path .'" title="'. $next_post[ 'title' ] .'" class="btn btn-default qas-blog-next-post-btn">'.qas_blog_lang('next_post').$next_post[ 'title' ].'</a>');
                }

                if(count($prev_post) || count($next_post)){
                    $this->output('</div>');
                }
            }
        }

        public function c_list( $c_list, $class )
        {
            if ( qas_is_blog_page( $this->template ) ) {
                if ( qa_opt( 'qas_blog_adcode_after_post' ) && qa_opt( 'qas_blog_adcode_after_post_content' ) ) {
                    $this->output( '<div class="qas-blog-ads-after-post">' );
                    $this->output( qa_opt( 'qas_blog_adcode_after_post_content' ) );
                    $this->output( '</div>' );
                }

                $this->qas_blog_c_part_title( $c_list );
            }

            parent::c_list( $c_list, $class );
        }

        public function c_list_items( $c_items )
        {
            if( qas_is_blog_page( $this->template ) && qa_opt('qas_blog_allow_nested_cmnts') && false) { //arjun
                //only for blog pages show nested comments
                foreach ( $c_items as $c_id => &$c_item ) {
                    //don't show the replies as a parent item
                    if(empty(@$c_item['raw']['reply_to'])){
                        $this->c_list_item( $c_item );
                        $this->c_list_reply_items( $c_items, $c_item , 1 );
                    }
                }
            } else {
                parent::c_list_items( $c_items );
            }
        }

        public function c_list_reply_items( &$c_items, $c_item, $level = 1 )
        {
            if(!$c_items || !isset($c_item['raw']))
                return ;

            //find if it has any replies or not
            $r_items = $this->search_reply_items( $c_items, @$c_item['raw']['postid'] );
            if ( count( $r_items ) ) {

                $this->output( '<div class="qas-blog-replies'.(@$c_item['raw']['hidden'] ? ' qas-blog-replies-hidden' : '').'">' );

                foreach ( $r_items as $r_id => $r_item ) {
                    $r_item[ 'what' ] = qas_blog_lang( 'replied' );

                    // for the last stage comment , disable the reply button
                    if($level == qa_opt('qas_blog_max_allow_nesting')){
                        unset($r_item['form']['buttons']['comment']);
                    }

                    $this->c_list_item( $r_item );
                    unset( $c_items[ $r_id ] ); //unset it from the top comment list

                    if( $level < qa_opt('qas_blog_max_allow_nesting') ) {
                        $this->c_list_reply_items( $c_items, $r_item, ++$level );
                    }
                }

                $this->output( '</div>' );
            }
        }

        public function search_reply_items( $c_items, $parentid ) {
            if(!isset($c_item['raw']))
                return ;
            $replies = [] ;
            foreach ($c_items as $c_id => $c_item){
                if(@$c_item['raw']['reply_to'] == $parentid){
                    $replies[$c_id] = $c_item ;
                }
            }
            return $replies;
        }

        public function qas_blog_c_part_title( $part )
        {
            if ( strlen( @$part['title'] ) || strlen( @$part['title_tags'] ) ) {
                $this->output( '<h4' . rtrim( ' ' . @$part['title_tags'] ) . '>' . @$part['title'] . '</h4>' );
            }
        }

        private function get_css_file_for_theme( $theme_name, $css_base_dir = null )
        {
            $mapper = $this->css_files_mapper();

            $css_file = array_key_exists( strtolower( $theme_name ), $mapper )
                ? $mapper[ strtolower( $theme_name ) ]
                : $mapper['default'];

            return !is_null( $css_base_dir )
                ? $css_base_dir . $css_file . '?' . QAS_BLOG_VERSION
                : $css_file . '?' . QAS_BLOG_VERSION;
        }

        private function css_files_mapper()
        {
            return array(
                'snow'        => 'Snow.css',
                'snowflat'    => 'SnowFlat.css',
                'donut'       => 'Donut.css?v=0.01',
                'donut-theme' => 'Donut.css?v=0.01',
                'cleanstrap'  => 'Cleanstrap.css',
                'default'     => 'SnowFlat.css',
            );
        }

        public function post_meta($post, $class, $prefix=null, $separator='<br/>')
        {
            parent::post_meta($post, $class, $prefix, $separator);

            if( qa_opt('site_theme') == 'SnowFlat' && qas_is_blog_page( $this->template )){
                $this->output_split(@$post['views'], 'blog-view-count');
            }
        }

        public function q_view_stats($q_view)
        {
            if(qa_opt('site_theme') == 'SnowFlat' && qas_is_blog_page( $this->template )){
                $this->output('<div class="qa-q-view-stats">');

                $this->voting($q_view);
                $this->a_count($q_view);

                $this->output('</div>');
            } else {
                parent::q_view_stats($q_view);
            }

        }

    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */

<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }

    /**
     * returns the base url for the blog
     *
     * @return string
     */
    function qas_get_blog_url_base()
    {
        return QAS_BLOG_URL_BASE;
    }

    /**
     * replaces the caret (^) with the base blog URL
     *
     * @param $string
     *
     * @return string
     */
    function qas_get_blog_url_sub( $string )
    {
        $replace = array( '^' => qas_get_blog_url_base() );

        return strtr( $string, $replace );
    }

    /**
     * returns the first image from a string (Generally from a post)
     *
     * @param $string
     *
     * @return null
     */
    function qas_blog_get_image_from_post( $string )
    {
        $regex = "/\<img.+src\s*=\s*\"([^\"]*)\"[^\>]*\>/Us";
        if ( $image = preg_match_all( $regex, $string, $matches ) ) {
            return $matches[ 1 ][ 0 ];
        } else {
            return null;
        }
    }

    /**
     * Adds few more links in the admin subnavigation
     *
     * @return array
     */
    function qas_blog_admin_sub_navigation()
    {
        $navigation = array();
        $level = qa_get_logged_in_level();

        if ( $level >= QA_USER_LEVEL_ADMIN ) {

            $url = qas_get_blog_url_sub( 'admin/^/posting' );

            $navigation[ $url ] = array(
                'label' => qa_lang_html( 'qas_admin/blog_posting_title' ),
                'url'   => qa_path_html( $url ),
            );

            $url = qas_get_blog_url_sub( 'admin/^/viewing' );

            $navigation[ $url ] = array(
                'label' => qa_lang_html( 'qas_admin/blog_viewing_title' ),
                'url'   => qa_path_html( $url ),
            );

            $url = qas_get_blog_url_sub( 'admin/^/permissions' );

            $navigation[ $url ] = array(
                'label' => qa_lang_html( 'qas_admin/blog_permissions_title' ),
                'url'   => qa_path_html( $url ),
            );

            $url = qas_get_blog_url_sub( 'admin/^/categories' );

            $navigation[ $url ] = array(
                'label' => qa_lang_html( 'qas_admin/blog_category_title' ),
                'url'   => qa_path_html( $url ),
            );

            /*
            $url = qas_get_blog_url_sub('admin/^/flagged');
            $navigation[ $url ] = array(
                'label' => qa_lang_html('qas_admin/blog_flagged_title'),
                'url'   => qa_path_html($url),
            );
            */

            $url = qas_get_blog_url_sub( 'admin/^/stats' );

            $navigation[ $url ] = array(
                'label' => qa_lang_html( 'qas_admin/blog_stats_title' ),
                'url'   => qa_path_html( $url ),
            );

        }

        if ( !( qa_user_maximum_permit_error( 'qas_blog_permit_hide_show' ) && qa_user_maximum_permit_error( 'qas_blog_permit_delete_hidden' ) ) ) {

            $url = qas_get_blog_url_sub( 'admin/^/hidden' );

            $navigation[ $url ] = array(
                'label' => qa_lang_html( 'qas_admin/blog_hidden_title' ),
                'url'   => qa_path_html( $url ),
            );

        }

        if ( !qa_user_maximum_permit_error( 'qas_blog_permit_moderate' ) ) {

            $url = qas_get_blog_url_sub( 'admin/^/moderate' );

            $navigation[ $url ] = array(
                'label' => qa_lang_html( 'qas_admin/blog_moderate_title' ),
                'url'   => qa_path_html( $url ),
            );

        }

        if ( !qa_user_maximum_permit_error( 'qas_blog_permit_view_edit_draft' ) ) {

            $url = qas_get_blog_url_sub( 'admin/^/drafts' );

            $navigation[ $url ] = array(
                'label' => qa_lang_html( 'qas_admin/blog_drafts_title' ),
                'url'   => qa_path_html( $url ),
            );

        }

        return $navigation;
    }

    /**
     * Returns additional routing elements
     *
     * @return array
     */
    function qas_blog_page_routing()
    {

        $plugin_folder = '../' . qas_blog_plugin_folder();
        $route_blogs = qas_get_blog_url_sub( qas_blog_url_plural_structure( '/' ) );
        $route_tags = qas_get_blog_url_sub( '^/tags' );
        $route_search = qas_get_blog_url_sub( '^/search' );
        $route_blog_admin_default = qas_get_blog_url_sub( 'admin/^/' );
        $route_blog_admin_cat = qas_get_blog_url_sub( 'admin/^/categories' );
        //$route_blog_admin_flagged = qas_get_blog_url_sub('admin/^/flagged');
        $route_blog_admin_hidden = qas_get_blog_url_sub( 'admin/^/hidden' );
        $route_blog_admin_moderate = qas_get_blog_url_sub( 'admin/^/moderate' );
        $route_blog_admin_drafts = qas_get_blog_url_sub( 'admin/^/drafts' );
        $route_blog_admin_recalc = qas_get_blog_url_sub( 'admin/^/recalc' );
        $route_blog_admin_stats = qas_get_blog_url_sub( 'admin/^/stats' );

        $new_pages = array(
            $route_blogs               => $plugin_folder . '/views/blogs.php',
            $route_tags                => $plugin_folder . '/views/blog-tags.php',
            $route_search              => $plugin_folder . '/views/blog-search.php',
            $route_blog_admin_default  => $plugin_folder . '/admin/blog-admin-options.php',
            $route_blog_admin_cat      => $plugin_folder . '/admin/blog-admin-categories.php',
            //$route_blog_admin_flagged => $plugin_folder . '/admin/blog-admin-flagged.php',
            $route_blog_admin_hidden   => $plugin_folder . '/admin/blog-admin-hidden.php',
            $route_blog_admin_moderate => $plugin_folder . '/admin/blog-admin-moderate.php',
            $route_blog_admin_drafts   => $plugin_folder . '/admin/blog-admin-drafts.php',
            $route_blog_admin_recalc   => $plugin_folder . '/admin/blog-admin-recalc.php',
            $route_blog_admin_stats    => $plugin_folder . '/admin/blog-admin-stats.php',
        );

        return $new_pages;
    }

    /**
     * Adds Extra sub navigation links to the user sub navigation pages
     *
     * @param $sub_nav
     * @param $handle
     */
    function qas_blog_user_sub_navigation( &$sub_nav, $handle )
    {

        $sub_nav[ 'user_blogs' ] = array(
            'label'    => qa_lang_html( 'qas_blog/all_blogs' ),
            'url'      => qa_path_html( qas_get_blog_url_sub( 'user-' . qas_blog_url_plural_structure( '/' ) . $handle ) ),
            'selected' => ( qa_request_part( 0 ) === qas_get_blog_url_sub( 'user-' . qas_blog_url_plural_structure() ) ) ? true : false,
        );

        $sub_nav[ 'user_drafts' ] = array(
            'label'    => qa_lang_html( 'qas_blog/all_drafts' ),
            'url'      => qa_path_html( qas_get_blog_url_sub( '^-drafts/' . $handle ) ),
            'selected' => ( qa_request_part( 0 ) === qas_get_blog_url_sub( '^-drafts' ) ) ? true : false,
        );

        $useraccount = qa_db_select_with_pending(
            QA_FINAL_EXTERNAL_USERS ? null : qa_db_user_account_selectspec( $handle, false )
        );

        if ( QA_FINAL_EXTERNAL_USERS ) {
            $userid = qa_handle_to_userid( $handle );
        }

        $loginuserid = qa_get_logged_in_userid();

        $ismyuser = isset( $loginuserid ) && $loginuserid == ( QA_FINAL_EXTERNAL_USERS ? $userid : $useraccount[ 'userid' ] );

        if ( !qas_is_draft_enabled() || !$ismyuser && qa_user_maximum_permit_error( 'qas_blog_permit_view_edit_draft' ) ) {
            //for now I am unsetting it all the time . It is for a new feature
            unset( $sub_nav[ 'user_drafts' ] );
        }
    }

    /**
     * Strips out Unnecessary HTML tags
     *
     * @param       $html
     * @param array $allowed_tags
     *
     * @return mixed
     */
    function qas_blog_strip_tags( $html, array $allowed_tags = array() )
    {
        if ( empty( $allowed_tags ) ) {
            $allowed_tags = array( 'a', 'abbr', 'acronym', 'blockquote',
                'code', 'del', 'em', 'i', 'strike',
                'strong', 'h1', 'h2', 'h3', 'h4',
                'h5', 'h6', 'p', 'br', 'span' );
        }

        $allowed_tags = array_map( 'strtolower', $allowed_tags );
        $pattern = '/<\/?([^>\s]+)[^>]*>/i';
        $callback = new StripTagCallBack( $allowed_tags );
        $stripped_html = preg_replace_callback( $pattern, array( $callback, 'strip_tag_callback' ), $html );

        return $stripped_html;
    }

    if ( !class_exists( 'StripTagCallBack' ) ) {
        /**
         * Strip tags call back function to support lower PHP Versions
         * Class StripTagCallBack
         */
        class StripTagCallBack
        {
            private $allowed_tags;

            function __construct( $allowed_tags )
            {
                $this->allowed_tags = $allowed_tags;
            }

            public function strip_tag_callback( $matches )
            {
                return in_array( strtolower( $matches[ 1 ] ), $this->allowed_tags ) ? $matches[ 0 ] : '';
            }
        }
    }

    /**
     * Truncate a string by using the limit
     *
     * @param        $string
     * @param        $limit
     * @param string $pad
     *
     * @return string
     */
    function qas_blog_truncate_string( $string, $limit, $pad = "..." )
    {
        return qas_blog_util::safe_truncate($string, $limit, $pad);
    }

    /**
     * Restore tags after truncating
     *
     * @param $input
     *
     * @return string
     */
    function qas_blog_restore_tags( $input )
    {
        $opened = array();

        // loop through opened and closed tags in order
        if ( preg_match_all( "/<(\/?[a-z]+)>?/i", $input, $matches ) ) {
            foreach ( $matches[ 1 ] as $tag ) {
                if ( preg_match( "/^[a-z]+$/i", $tag, $regs ) ) {
                    // a tag has been opened
                    if ( strtolower( $regs[ 0 ] ) != 'br' ) $opened[] = $regs[ 0 ];
                } elseif ( preg_match( "/^\/([a-z]+)$/i", $tag, $regs ) ) {
                    // a tag has been closed
                    $keys = array_keys( $opened, $regs[ 1 ] );
                    $poped_keys = array_pop( $keys );
                    unset( $opened[ $poped_keys ] );
                }
            }
        }

        // close tags that are still open
        if ( $opened ) {
            $tagstoclose = array_reverse( $opened );
            foreach ( $tagstoclose as $tag ) $input .= "</$tag>";
        }

        return $input;
    }

    /**
     * Reads the setting file and fetch the required settings from he database
     *
     * @return [type] [description]
     */
    function qas_blog_get_js_settings()
    {
        $settings_file = QAS_BLOG_DIR . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'javascript-options.php';

        if ( !file_exists( $settings_file ) ) {
            return array();
        }

        return require $settings_file;
    }

    /**
     * Globally namespaced version of the class.
     *
     * @author Brandon Wamboldt <brandon.wamboldt@gmail.com>
     */
    class qas_blog_util extends \utilphp\util
    {
        //Empty body to maintain global namespacing
    }

    if ( !function_exists( 'dd' ) ) {
        /**
         * Dumps the variable data and terminate the script
         *
         * @param  $var
         *
         * @return null
         */
        function dd( $var )
        {
            qas_blog_util::var_dump( $var );
            exit( 0 );
        }
    }

    if ( !function_exists( 'dump' ) ) {
        /**
         * Dumps the variable data
         *
         * @param  $var
         *
         * @return null
         */
        function dump( $var )
        {
            qas_blog_util::var_dump( $var );
        }
    }

    /**
     * @param  boolean if set to true , it returns the absolute plugin folder
     *
     * @return String
     */
    function qas_blog_plugin_folder( $absolute = false )
    {
        $path = basename( QA_PLUGIN_DIR ) . '/' . QAS_BLOG_FOLDER;

        return $absolute ? ( QA_BASE_DIR . $path ) : $path;
    }

    /**
     * returns the base url for the plugin
     *
     * @return string
     */
    function qas_blog_plugin_url()
    {
        return qa_path_to_root() . qas_blog_plugin_folder();
    }

    /**
     * Returns the language value as defined in blog-lang-*.php
     *
     * @param      $indentifier
     * @param null $subs
     *
     * @return mixed|string
     */
    function qas_blog_lang( $indentifier, $subs = null )
    {
        if ( !is_array( $subs ) )
            return empty( $subs ) ? qa_lang( 'qas_blog/' . $indentifier ) : qa_lang_sub( 'qas_blog/' . $indentifier, $subs );
        else
            return strtr( qa_lang( 'qas_blog/' . $indentifier ), $subs );
    }

    /**
     * Returns the language html value as defined in blog-lang-*.php
     *
     * @param      $indentifier
     * @param null $subs
     *
     * @return mixed|string
     */
    function qas_blog_lang_html( $indentifier, $subs = null )
    {
        if ( !is_array( $subs ) )
            return empty( $subs ) ? qa_lang_html( 'qas_blog/' . $indentifier ) : qa_lang_html_sub( 'qas_blog/' . $indentifier, $subs );
        else
            return strtr( qa_lang_html( 'qas_blog/' . $indentifier ), $subs );
    }

    /**
     * Returns the language value as defined in blog-js-lang-*.php
     *
     * @param      $indentifier
     * @param null $subs
     *
     * @return mixed|string
     */
    function qas_blog_js_lang( $indentifier, $subs = null )
    {
        if ( !is_array( $subs ) )
            return empty( $subs ) ? qa_lang( 'qas_js/' . $indentifier ) : qa_lang_sub( 'qas_js/' . $indentifier, $subs );
        else
            return strtr( qa_lang( 'qas_js/' . $indentifier ), $subs );
    }

    /**
     * Returns the language html value as defined in blog-js-lang-*.php
     *
     * @param      $indentifier
     * @param null $subs
     *
     * @return mixed|string
     */
    function qas_blog_js_lang_html( $indentifier, $subs = null )
    {
        if ( !is_array( $subs ) )
            return empty( $subs ) ? qa_lang_html( 'qas_js/' . $indentifier ) : qa_lang_html_sub( 'qas_js/' . $indentifier, $subs );
        else
            return strtr( qa_lang_html( 'qas_js/' . $indentifier ), $subs );
    }

    if(!function_exists('array_pluck')){
        function array_pluck ($toPluck, $arr) {
            return array_map(function ($item) use ($toPluck) {
                return $item[$toPluck];
            }, $arr);
        }
    }

<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }
    
    /**
     * Set an entity to be favorited or removed from favorites. Handles event reporting.
     *
     * @param int    $userid     ID of user assigned to the favorite
     * @param string $handle     Username of user
     * @param string $cookieid   Cookie ID of user
     * @param string $entitytype Entity type code (one of QA_ENTITY_* constants)
     * @param string $entityid   ID of the entity being favorited (e.g. postid for questions)
     * @param bool   $favorite   Whether to add favorite (true) or remove favorite (false)
     */
    function qas_blog_user_favorite_set( $userid, $handle, $cookieid, $entitytype, $entityid, $favorite )
    {

        // Make sure the user is not favoriting themselves
        if ( $entitytype == QA_ENTITY_USER && $userid == $entityid ) {
            return;
        }

        if ( $favorite )
            qa_db_favorite_create( $userid, $entitytype, $entityid );
        else
            qa_db_favorite_delete( $userid, $entitytype, $entityid );

        switch ( $entitytype ) {
            case QAS_BLOG_ENTITY_POST:
                $action = $favorite ? 'qas_blog_post_favorite' : 'qas_blog_post_unfavorite';
                $params = array( 'postid' => $entityid );
                break;

            case QAS_BLOG_ENTITY_TAG:
                $action = $favorite ? 'qas_blog_tag_favorite' : 'qas_blog_tag_unfavorite';
                $params = array( 'wordid' => $entityid );
                break;

            case QAS_BLOG_ENTITY_CATEGORY:
                $action = $favorite ? 'qas_blog_cat_favorite' : 'qas_blog_cat_unfavorite';
                $params = array( 'categoryid' => $entityid );
                break;

            default:
                break;
        }

        qa_report_event( @$action, $userid, $handle, $cookieid, @$params );
    }

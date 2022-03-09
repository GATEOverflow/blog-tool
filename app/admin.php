<?php
    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }
    
    /**
     * Returns true if admin (hidden/flagged/approve/moderate) page $action performed on $entityid is permitted by the
     * logged in user and was processed successfully
     *
     * @param $entityid
     * @param $action
     *
     * @return bool
     */
    function qas_blog_admin_single_click( $entityid, $action )
    {
        $userid = qa_get_logged_in_userid();

        $post = qas_blog_post_get_full( $entityid );

        if ( isset( $post ) ) {
            $queued = ( substr( $post[ 'type' ], 1 ) == '_QUEUED' );

            switch ( $action ) {
                case 'approve':
                    if ( $queued && !qa_user_post_permit_error( 'permit_moderate', $post ) ) {
                        qas_blog_post_set_hidden( $entityid, false, $userid );

                        return true;
                    }
                    break;

                case 'reject':
                    if ( $queued && !qa_user_post_permit_error( 'permit_moderate', $post ) ) {
                        qas_blog_post_set_hidden( $entityid, true, $userid );

                        return true;
                    }
                    break;

                case 'hide':
                    if ( ( !$queued ) && !qa_user_post_permit_error( 'permit_hide_show', $post ) ) {
                        qas_blog_post_set_hidden( $entityid, true, $userid );

                        return true;
                    }
                    break;

                case 'reshow':
                    if ( $post[ 'hidden' ] && !qa_user_post_permit_error( 'permit_hide_show', $post ) ) {
                        qas_blog_post_set_hidden( $entityid, false, $userid );

                        return true;
                    }
                    break;

                case 'delete':
                    if ( $post[ 'hidden' ] && !qa_user_post_permit_error( 'permit_delete_hidden', $post ) ) {
                        qas_blog_post_delete( $entityid );

                        return true;
                    }

                    if ( $post[ 'basetype' ] == 'D' && !qa_user_post_permit_error( 'qas_blog_permit_view_edit_draft', $post ) ) {
                        qas_blog_draft_delete( $entityid );

                        return true;
                    }
                    break;

                case 'publish':
                    if ( $post[ 'basetype' ] == 'D' && !qa_user_post_permit_error( 'qas_blog_permit_view_edit_draft', $post ) ) {
                        qas_blog_post_publish( $entityid );

                        return true;
                    }
                    break;

                /*case 'clearflags':
                    if ( !qa_user_post_permit_error('permit_hide_show', $post)) {
                        qa_flags_clear_all($post, $userid, qa_get_logged_in_handle(), null);

                        return true;
                    }
                    break;*/
            }

        }

        return false;
    }

    /**
     * Checks for a POSTed click on an admin (hidden/flagged/approve/moderate) page, and refresh the page if processed
     * successfully (non Ajax)
     *
     * @return mixed|null|string
     */
    function qas_blog_admin_check_clicks()
    {
        if ( qa_is_http_post() )
            foreach ( $_POST as $field => $value )
                if ( strpos( $field, 'admin_' ) === 0 ) {
                    @list( $dummy, $entityid, $action ) = explode( '_', $field );

                    if ( strlen( $entityid ) && strlen( $action ) ) {
                        if ( !qa_check_form_security_code( 'admin/blog_click', qa_post_text( 'code' ) ) )
                            return qa_lang_html( 'misc/form_security_again' );
                        elseif ( qas_blog_admin_single_click( $entityid, $action ) )
                            qa_redirect( qa_request() );
                    }
                }

        return null;
    }

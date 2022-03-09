<?php

    if ( !defined( 'QA_VERSION' ) ) { // don't allow this page to be requested directly from browser
        header( 'Location: ../' );
        exit;
    }


    /**
     * for validating the post contents
     * Class qas_blog_filter_posts
     */
    class qas_blog_filter_posts
    {

        /**
         * Process the user input and checks for the errors as per the admin panel settings
         *
         * @param      $post
         * @param      $errors
         * @param      $oldpost
         * @param bool $filter_module
         */
        public function filter_blog_post( &$post, &$errors, $oldpost )
        {
            $this->validate_length( $errors, 'title', @$post[ 'title' ], qa_opt( 'qas_blog_min_len_post_title' ),
                max( qa_opt( 'qas_blog_min_len_post_title' ), min( qa_opt( 'qas_blog_max_len_post_title' ), QA_DB_MAX_TITLE_LENGTH ) ) );

            $this->validate_length( $errors, 'content', @$post[ 'content' ], 0, QA_DB_MAX_CONTENT_LENGTH ); // for storage

            $this->validate_length( $errors, 'content', @$post[ 'text' ], qa_opt( 'qas_blog_min_len_post_content' ), null ); // for display

            if ( isset( $post[ 'tags' ] ) ) {
                $counttags = count( $post[ 'tags' ] );
                $mintags = min( qa_opt( 'qas_blog_min_num_post_tags' ), qa_opt( 'qas_blog_max_num_post_tags' ) );

                if ( $counttags < $mintags )
                    $errors[ 'tags' ] = qa_lang_sub( 'question/min_tags_x', $mintags );
                elseif ( $counttags > qa_opt( 'qas_blog_max_num_post_tags' ) )
                    $errors[ 'tags' ] = qa_lang_sub( 'question/max_tags_x', qa_opt( 'qas_blog_max_num_post_tags' ) );
                else
                    $this->validate_length( $errors, 'tags', qa_tags_to_tagstring( $post[ 'tags' ] ), 0, QA_DB_MAX_TAGS_LENGTH ); // for storage
            }

            $this->validate_post_email( $errors, $post );
        }

        /**
         * Add textual element $field to $errors if length of $input is not between $minlength and $maxlength
         *
         * @param $errors
         * @param $field
         * @param $input
         * @param $minlength
         * @param $maxlength
         */
        private function validate_length( &$errors, $field, $input, $minlength, $maxlength )
        {
            $length = isset( $input ) ? qa_strlen( $input ) : 0;

            if ( $length < $minlength )
                $errors[ $field ] = ( $minlength == 1 ) ? qa_lang( 'main/field_required' ) : qa_lang_sub( 'main/min_length_x', $minlength );
            elseif ( isset( $maxlength ) && ( $length > $maxlength ) )
                $errors[ $field ] = qa_lang_sub( 'main/max_length_x', $maxlength );
        }

        /**
         * validates the post emails
         *
         * @param $errors
         * @param $post
         */
        private function validate_post_email( &$errors, $post )
        {
            if ( @$post[ 'notify' ] && strlen( @$post[ 'email' ] ) ) {
                $error = $this->filter_email( $post[ 'email' ], null );
                if ( isset( $error ) )
                    $errors[ 'email' ] = $error;
            }
        }

        public function filter_email( &$email, $olduser )
        {
            if ( !strlen( $email ) )
                return qa_lang( 'users/email_required' );

            if ( !qa_email_validate( $email ) )
                return qa_lang( 'users/email_invalid' );

            if ( qa_strlen( $email ) > QA_DB_MAX_EMAIL_LENGTH )
                return qa_lang_sub( 'main/max_length_x', QA_DB_MAX_EMAIL_LENGTH );
        }

        /**
         * validates the comment
         *
         * @param $comment
         * @param $errors
         * @param $post
         * @param $parent
         * @param $oldcomment
         */
        public function filter_blog_comment( &$comment, &$errors, $post, $parent, $oldcomment )
        {
            $this->validate_length( $errors, 'content', @$comment[ 'content' ], 0, QA_DB_MAX_CONTENT_LENGTH ); // for storage
            $this->validate_length( $errors, 'content', @$comment[ 'text' ], qa_opt( 'qas_blog_min_len_c_content' ), null ); // for display
            $this->validate_post_email( $errors, $comment );
        }
    }


    /*
        Omit PHP closing tag to help avoid accidental output
    */
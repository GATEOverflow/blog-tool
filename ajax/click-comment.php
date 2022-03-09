<?php

// Load relevant information about this comment

$commentid = qa_post_text('commentid');
$postid = qa_post_text('postid');
$parentid = qa_post_text('parentid');

$userid = qa_get_logged_in_userid();

list($comment, $post, $parent, $children) = qa_db_select_with_pending(
	qas_blog_db_full_post_selectspec($userid, $commentid),
	qas_blog_db_full_post_selectspec($userid, $postid),
	qas_blog_db_full_post_selectspec($userid, $parentid),
	qas_blog_db_full_child_posts_selectspec($userid, $parentid)
);


// Check if there was an operation that succeeded

if (@$comment['basetype'] == 'C' && @$post['basetype'] == 'B' &&
	(@$parent['basetype'] == 'B')
) {
	$comment = $comment + qas_blog_page_b_post_rules($comment, $parent, $children, null); // array union

	if (qas_blog_page_post_single_click_c($comment, $post, $parent, $error)) {
		$comment = qa_db_select_with_pending(qas_blog_db_full_post_selectspec($userid, $commentid));

		// If so, page content to be updated via Ajax

		echo "QA_AJAX_RESPONSE\n1";

		// If the comment was not deleted...

		if (isset($comment)) {
			$parent = $parent + qas_blog_page_b_post_rules($parent, ($postid == $parentid) ? null : $post, null, $children);
			// in theory we should retrieve the parent's siblings for the above, but they're not going to be relevant
			$comment = $comment + qas_blog_page_b_post_rules($comment, $parent, $children, null);

			$usershtml = qa_userids_handles_html(array($comment), true);

			$c_view = qas_blog_page_b_comment_view($post, $parent, $comment, $usershtml, false);

			$themeclass = qa_load_theme_class(qa_get_site_theme(), 'blog-ajax-comments', null, null);
			$themeclass->initialize();


			// ... send back the HTML for it

			echo "\n";

			$themeclass->c_list_item($c_view);
		}

		return;
	}
}


echo "QA_AJAX_RESPONSE\n0\n"; // fall back to non-Ajax submission if something failed

=== Yet another ajax paged comments (YAAPC) ===
Contributors: Dean Lee
Tags: comments, ajax, paged,comment,pagging,posting,validation
Requires at least: 2.0.2
Tested up to: 2.6.2
Stable tag: trunk

This plugin provides AJAX enabled comments with paging,comment posting and fields validation to your WordPress blog.

== Description ==

This plugin provide Ajax enabled comments system with paging,posting and form validation to your WordPress blog.

*   **Ajax page navigation.**
  When the user navigate through the comments via page selector,only the comments area will be send back to the client and refreshed,not the full page.This actually save your server load and bandwidth thus making your blog faster and more responsive. The paging system works even if JavaScript is disabled.
*   **SEO friendly**.
  Pagination can cause a <a href="http://www.google.com/support/webmasters/bin/answer.py?hl=en&answer=66359">duplicate content issue</a> with search engines.YAAPC can automatically generate “noindex,follow” meta tag in your paged comments page to avoid duplicate content in search engines that may hurt your rankings.
*   **Ajax comment posting**.
  Posting comments via Ajax,without page refreshing.
*   **Comment form validation.**
  validate the user input in comment form before sending to server.

== Installation ==

1. Upload YAAPC to the `/wp-content/plugins/` directory. 
1. Activate the plugin through the 'Plugins' menu in WordPress. 
1. (Optional,but recommended) Modify your comments template(comments.php),add a div with id="yaapc-comments" to wrap the comment list area. Place <?php yaapc_pages()?>` in where you want the page selector to show.the following is an example of a modified comments.php:

== Screenshots ==

1. Ajax page navigation
2. Comment form validation
3. Ajax comment postiong
=== Post Ordering ===
Contributors: MaxBlogPress Revived
Tags: post, posts, category,taxonomy,category,ordering,post ordering, category ordering,ordering
Requires at least: 2.2
Tested up to: 2.8.5
Stable tag: trunk 

With this plugin you can sort and order posts under a category manually. 

== Description ==
This plugin gives you the flexibility to sort and order posts under any category in your blog.

**Features** 

* Easy to use and smart way to order posts.
* Posts listed categorically so easy to manage

== Installation ==

1. Download "Post Ordering" plugin.
2. Open /wp-content/plugins folder at your web server.
3. Upload the folder "post-ordering" there.
4. Goto Plugins page in your wordpress back office.
5. Activate "Post Ordering" plugin.
6. Go to Settings >> Post Ordering.
7. You need to add a line of code to the index.php and archive.php files of your template so that they display posts in your specified order:

Open each of these files and find the line that reads:
<?php while (have_posts()) : the_post(); ?>

Now, immediately before that line, paste the following code into archive.php:
<?php $offset = ($paged) ? (int) (get_option('posts_per_page') * ($paged - 1)) : 0; ?>
<?php query_posts("cat=".$cat. "&orderby=menu_order&order=ASC&offset={$offset}"); ?>

And the following code into index.php:
<?php $offset = ($paged) ? (int) (get_option('posts_per_page') * ($paged - 1)) : 0; ?>
<?php query_posts("orderby=menu_order&order=ASC&offset={$offset}"); ?>
Note: This plugin will require one time free registration.

== Screenshots ==

1. Post Ordering configuration page
2. Edit the two tempalte file 
3. Add code in archive.php template file
4. Add code in index.php template file

== Change Log ==
 
= Version 1.0 (11-01-2009) =
* New: First Release

== How to use it ==

* Go to Settings >> Post Ordering
* You will see a list of categories with the posts under it.
* Ordering is easy; simply click the [+] button for any article to move it down the list and [-] to move up the list. 
* Make sure you add the code mentioned in installation point 7 to get the desired result in the front end.


  
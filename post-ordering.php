<?php
/*
 * Plugin Name:   Post Ordering
 * Version:       1.0
 * Plugin URI:    http://wordpress.org/extend/plugins/posts-ordering/
 * Description:   This plugin gives you the flexibility to sort and order posts under any category in your blog. Adjust your settings <a href="options-general.php?page=post-ordering/post-ordering.php">here</a>.
 * Author:        MaxBlogPress
 * Author URI:    http://www.maxblogpress.com
 *
 * License:       GNU General Public License
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 * Copyright (C) 2007 www.maxblogpress.com
 *
 */

$mbppo_path      = preg_replace('/^.*wp-content[\\\\\/]plugins[\\\\\/]/', '', __FILE__);
$mbppo_path      = str_replace('\\','/',$mbppo_path);
$mbppo_dir       = substr($mban_path,0,strrpos($mbppo_path,'/'));
$mbppo_siteurl   = get_bloginfo('wpurl');
$mbppo_siteurl   = (strpos($mbppo_siteurl,'http://') === false) ? get_bloginfo('siteurl') : $mbppo_siteurl;
$mbppo_fullpath  = $mbppo_siteurl.'/wp-content/plugins/'.$mbppo_dir.'';
$mbppo_fullpath  = $mbppo_fullpath.'post-ordering/';
$mbppo_abspath   = str_replace("\\","/",ABSPATH); 

define('MBP_PO_ABSPATH', $mbppo_path);
define('MBP_PO_LIBPATH', $mbppo_fullpath);
define('MBP_PO_SITEURL', $mbppo_siteurl);
define('MBP_PO_NAME', 'Post Ordering');
define('MBP_PO_VERSION', '1.0');  
define('MBP_PO_LIBPATH', $mbppo_fullpath);

function mbp_po_postorder() {
	
	$mbp_po_activate = get_option('mbp_po_activate');
	$reg_msg = '';
	$mbp_po_msg = '';
	$form_1 = 'mbp_po_reg_form_1';
	$form_2 = 'mbp_po_reg_form_2';
		// Activate the plugin if email already on list
	if ( trim($_GET['mbp_onlist']) == 1 ) {
		$mbp_po_activate = 2;
		update_option('mbp_po_activate', $mbp_po_activate);
		$reg_msg = 'Thank you for registering the plugin. It has been activated'; 
	} 
	// If registration form is successfully submitted
	if ( ((trim($_GET['submit']) != '' && trim($_GET['from']) != '') || trim($_GET['submit_again']) != '') && $mbp_po_activate != 2 ) { 
		update_option('mbp_po_name', $_GET['name']);
		update_option('mbp_po_email', $_GET['from']);
		$mbp_po_activate = 1;
		update_option('mbp_po_activate', $mbp_po_activate);
	}
	if ( intval($mbp_po_activate) == 0 ) { // First step of plugin registration
		global $userdata;
		mbp_poRegisterStep1($form_1,$userdata);
	} else if ( intval($mbp_po_activate) == 1 ) { // Second step of plugin registration
		$name  = get_option('mbp_po_name');
		$email = get_option('mbp_po_email');
		mbp_poRegisterStep2($form_2,$name,$email);
	} else if ( intval($mbp_po_activate) == 2 ) { // Options page
		if ( trim($reg_msg) != '' ) {
			echo '<div id="message" class="updated fade"><p><strong>'.$reg_msg.'</strong></p></div>';
		}	
	
	global $wpdb, $wp_version;

	$relationships = array();

	if (isset($_POST['action']) && is_array($_POST['action'])) {
		foreach ($_POST['action'] as $catid => $act) {
			if ($wp_version < '2.3') {	
				$action_posts = $wpdb->get_results("SELECT p.ID, p.menu_order FROM $wpdb->posts AS p LEFT JOIN $wpdb->post2cat AS p2c ON (p.ID = p2c.post_id) WHERE p2c.category_id = $catid AND (p.post_status = 'publish' OR p.post_status = 'private' OR p.post_status = 'draft') AND p.post_type = 'post' AND p.post_parent = '0' ORDER BY p.menu_order, p.ID ASC");
			} else {
			$action_posts = $wpdb->get_results("
							SELECT 
									a.*
							FROM								
								$wpdb->posts a
							INNER JOIN $wpdb->term_relationships b ON(a.ID = b.object_id)
							INNER JOIN $wpdb->term_taxonomy c ON(b.term_taxonomy_id = c.term_taxonomy_id)
							WHERE
								a.post_status='publish'
								AND a.post_type='post'
								AND c.term_taxonomy_id='$catid' 
							ORDER BY 
									a.menu_order,a.ID ASC");
			}
			
			foreach ($act as $id => $dir) {
				foreach ($action_posts as $offset => $post) {
					$relationships[$post->ID] = $offset;
				}

				if ($dir == '+') {
					$end_onwards = $relationships[$id]+2;
					$start_array = array_slice($action_posts, 0, $relationships[$id]);
					$tmp_array = array_slice($action_posts, $relationships[$id], 2);
					$end_array = array_slice($action_posts, $end_onwards);
				} else {
					$end_onwards = $relationships[$id]+1;
					$start_array = array_slice($action_posts, 0, $relationships[$id]-1);
					$tmp_array = array_slice($action_posts, $relationships[$id]-1, 2);
					$end_array = array_slice($action_posts, $end_onwards);
				}

				if (($dir == '+' && count($tmp_array) > 1) || ($dir == '-' && count($tmp_array) > 1)) {
					$final_array = array();
					$i = 1;

					$tmp_array = array_reverse($tmp_array);
					$new_array = array_merge($start_array, $tmp_array, $end_array);

					foreach ($new_array as $item) {
						$item->menu_order = $i;
						$final_array[] = $item;
						$wpdb->query("UPDATE $wpdb->posts SET menu_order = $item->menu_order WHERE ID = $item->ID");
						$i++;
					}
				}

			} 
		} 
	}
	?>
		<div class="wrap">
		<h2><?php echo MBP_PO_NAME.' '.MBP_PO_VERSION; ?></h2>
	<strong><img src="<?php echo MBP_AIT_LIBPATH;?>image/how.gif" border="0" align="absmiddle" /> <a href="http://wordpress.org/extend/plugins/posts-ordering/other_notes/" target="_blank">How to use it</a>&nbsp;&nbsp;&nbsp;
			<img src="<?php echo MBP_AIT_LIBPATH;?>image/comment.gif" border="0" align="absmiddle" /> <a href="http://www.maxblogpress.com/forum/forumdisplay.php?f=32" target="_blank">Community</a></strong>
	<br/><br/>			
			
		
		<p>Below is a list of categories and the posts for each. Use the arrows to order your posts as desired. You will need to make changes to your templates if you wish to honour this ordering. Please see the bottom of this page for more instructions on setting up your templates.</p>
		<img src="" alt=" " />
		<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<ul style="list-style:none;margin-left: 0; margin-bottom: 40px; padding-left: 0">
		<?php
			if ($wp_version < '2.3') {
				$categories = $wpdb->get_results("SELECT * FROM $wpdb->categories WHERE category_count > 0 ORDER BY cat_ID");
			} else {
$categories = $wpdb->get_results("
				SELECT								
						a.term_id as cat_ID,
						a.name as cat_name,								
						b.term_taxonomy_id,
						b.parent,
						b.count
				FROM 
					$wpdb->terms a
				INNER JOIN $wpdb->term_taxonomy b ON(a.term_id=b.term_id)
				WHERE
					b.taxonomy='category' ORDER BY a.term_id");			
			}
			
			if (is_array($categories) && count($categories) > 0) {
				foreach ($categories as $category) {
			?>
				<li><h3><?php echo $category->cat_name; ?> </h3>
					<ul style="list-style:none">
						<?php
							$i = 0;
						if ($wp_version < '2.3') {	
							$posts = $wpdb->get_results("SELECT p.*, p2c.category_id FROM $wpdb->posts AS p LEFT JOIN $wpdb->post2cat AS p2c ON (p.ID = p2c.post_id) WHERE p2c.category_id = $category->cat_ID AND (p.post_status = 'publish' OR p.post_status = 'private' OR p.post_status = 'draft') AND p.post_type = 'post' AND p.post_parent = '0' ORDER BY p.menu_order, p.ID ASC");
						} else {	
$posts = $wpdb->get_results("SELECT 
									a.*
							FROM								
								$wpdb->posts a
							INNER JOIN $wpdb->term_relationships b ON(a.ID = b.object_id)
							INNER JOIN $wpdb->term_taxonomy c ON(b.term_taxonomy_id = c.term_taxonomy_id)
							WHERE
								a.post_status='publish'
								AND a.post_type='post'
								AND c.term_taxonomy_id='$category->cat_ID' 
							ORDER BY 
									a.menu_order,a.ID ASC");							
						}	
							if (is_array($posts) && count($posts) > 0) {
								$total = count($posts);
								foreach ($posts as $post) { ?>
									<li<?php if (($i % 2) == 0) { ?> class="alternate"<?php } ?> style="padding: .2em .5em"><?php echo $post->menu_order;?>.
										<?php if ($i > 0) { ?>
										<input type="image" name="action[<?php echo $category->cat_ID; ?>][<?php echo $post->ID; ?>]" value="-" src="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;image=up" class="up" style="background: none; border: 0" />
										<?php } else { ?>
										<img src="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;image=spacer" alt= "" width="16" height="16" style="margin: 5px;" class="spacer" />
										<?php } ?>
										<?php if ($i < ($total - 1)) { ?>
										<input type="image" name="action[<?php echo $category->cat_ID; ?>][<?php echo $post->ID; ?>]" value="+" src="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;image=down" class="down" style="background: none; border: 0" />
										<?php } else { ?><img src="<?php echo $_SERVER['REQUEST_URI']; ?>&amp;image=spacer" alt= "" width="16" height="16" style="margin: 5px;" class="spacer" /><?php } ?>
										<strong><a href="<?php echo get_settings('siteurl').'/wp-admin/post.php?action=edit&post='.$post->ID; ?>" title="Edit this page"><?php echo $post->post_title;?></a></strong>
									</li>
									<?php
									$i++;
								}
							} else {
							?>
								<li class="alternate">This category does not have any posts associated with it.</li>
							<?php
							}
						?>
					</ul>
				</li>
			<?php
				}
			} else {
			?>
				<li class="alternate">You do not have any categories or posts yet.</li>
			<?php
			}
		?>
		</ul>

		<h2>Setting up your templates</h2>
		<p>To see this method of ordering in affect on your site, you will need to add the following line to your templates:</p>
		<pre><code>query_posts("cat=" . $cat . "&orderby=menu_order&order=ASC");</code></pre>
		<p>For instance, paste that line into the archive.php just before:</p>
		<pre><code>while (have_posts()) : the_post();</code></pre>
		<p>And the following into index.php:</p>
		<pre><code>query_posts("orderby=menu_order&order=ASC");</code></pre>
		<p>This will ensure that your posts use your new ordering.</p>
		</form>
		
		<div align="center" style="background-color:#f1f1f1; padding:5px 0px 5px 0px" >
		<p align="center"><strong><?php echo MBP_PO_NAME.' '.MBP_PO_VERSION; ?> by <a href="http://www.maxblogpress.com" target="_blank">MaxBlogPress</a></strong></p>
		<p align="center">This plugin is the result of <a href="http://www.maxblogpress.com/blog/219/maxblogpress-revived/" target="_blank">MaxBlogPress Revived</a> project.</p>
		</div>		
		
		</div>
		<?php
	}
}


function mbp_po_direction_image($image = 'up') {
	$img = array();
	$img['up'] = 'R0lGODlhEAAQAOZOAAhotBFJdQxipgplrQZruxBcmA5fnxNYjhJakgldnwpiqA54y0eZ2htglxFKdrjV7Ct9vy+DxleYzEWEtafJ5D6U2CFcix52uwxhpRVck8ff8UiAq4u22Adsu+r0+0l+pyaByxZaj3+55XCv4CB6whxdkRFttwlYl9Hl9Q5fnhJakS+K0pjH7ARuwhNYjQpptR5ci+/2/AtuvAtyxMTb7TKJzRFhoB9UfDaO02mo2xlhmglXlRNemQlbm9Lk8wplrEeBsAlamejy+hBxvt/t+RB1xR9aiN3r9xBcl12f0/n8/gVvwhprqoWw0f///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAE4ALAAAAAAQABAAAAd5gE6Cg4SFhk5KLB6HhTEiDBVEjIJCIzgLCysojEc5NTMtS0UgGoY+SREyBKsdQyQPhDQSEC8AtrcmFxSCHAo9CQoDwgM/CSc7QU2FTALNAhg6kxM2BtUpDZNAPAXcSCWTGxkI4yowkx8hB+ouRpMWDgHxATeT9faHgQA7';
	$img['down'] = 'R0lGODlhEAAQAOZOAAhBbQk9ZgVIfQFVlwNPjAdEdQJSklWPvARMhAs1VhROfAo5Xh5EYgNPi0BvlQdOhZyvvTx5qwhAax1CXhNQgApGd1B/pFV/oPT19j11oAhFdgsuSQZRjejr7pmvwFaQvQg9ZxE7Xj5ZbVJ8n6u7yTFkjBRShG+CkQk8ZNzh5ihIYhBXkARXmAFVlsvT2gtAagoxUNrf5Dx3pwlLfwRMg+Xp7DhpkBFVi0l2mhU/YSBsp5awxQk9ZQo4XLbDzQJSkRJTh8vU21KNuylJYoKfuL/L1Ag5XwVUkzt8rw8/ZQk1V2SYwgo6YAo5Xf///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAE4ALAAAAAAQABAAAAd5gE6Cg4SFhoeISx8HjEI6iE5ILAOULSuQEUcGmz83kDIcBKINQJAZDwipNBSQFjMCsAImhTs4FyM2FQW7GgolDgpEgh4vEgDHyAAgTBCEJEkoAdIBPEYhPoZFOT0LC01KEy6IQQwJCTBDKZBOMSobIh3rgjUnGPL3gQA7';
	$img['refresh'] = 'R0lGODlhEAAQAPeiAP////7//wCH9uDy/vv+//T7/wCP/wmS/gB34gF/6+34/2m39f3+/wOB6gOA6Pn8/8Xo/8bp/9nv/q/X+Ah+39ju/gGL+ABx3giR+wuS++j3/1u1+ej2/wCR/wB/7+n2/2zD/2/D/wBeuQKR/wBtzABiwQl94QBpy/z+/9bv/9Tt/wmT/fT6/yKi/0St+7vg+6bb/wCE9YnE8vX6/wBYtGW9/Fys71ex+Mbn/xmB3DWW5QCI+SGM45jS/UOx/0u0/7Ld/bbb9+b1//f7/wCB7ZTQ/QBu1geC6TOr/wB55wBivo/R/+33/3nA+ABz3J3P9f7+/wV22dzx/wB+9gCM/QGH+Wm39ACK/fj8//b7/wBy2D+0/xuJ6CWm/9zw/gN/6wBu0dfw/63c/g6A3hSd/wBiwwSU/weU/gZ11AaW/4TD9DKi9AWC7Kvb/lm5/7zh/gaN+gBt2QB77d/x/QKD9ACH+XW37Rea/ABy3BZ/2F64+pPO/AB/7RyQ81yu8QCL/QBavgBz28/r/gCJ/ACG9zmj+gBz4D2t/wBqzQyT+ROb/wBnxvP6/1uz9gp313697i2I1AmP/Rad/+74/wBx1X7I/wCD8wiH72Wu5wB23gKR/QBmzJ7V/gOJ9wB97ABnyB6c+wB14f///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEAAKIALAAAAAAQABAAAAjhAEUJHCgQipcFOp4EIMgQSwgMoNZ8erSQoagHPhJ1UrSFAg0ZABgOYbRhEx4BSKIoQVNAVBYpbW700SPBxpEeLowgGlOAAAhNf1aQ6ZJigokaDRDEsbLww50YVMy00IApiQcinr4MCAkjQwJLgw5N4mHBwA46nEJyqJLJCZ8plQawOTPiChwhApuEWqQFSBEIKuqk6WAgkpiQXEiIUAMgTKMKbn4swcEERcgcJ/II2uOgDKQ5DACEHBikBCVHFwKBAWRnNEEALy4JICTHkJ8ZrhlGkHSg0JuKFgeyUEAgN8OAADs=';
	$img['spacer'] = 'R0lGODlhEAAQAIAAAP///wAAACH5BAEAAAAALAAAAAAQABAAAAIOhI+py+0Po5y02ouzPgUAOw==';

	if (array_key_exists($image, $img)) {
		header('Content-Type: image/gif');
		echo base64_decode($img[$image]);
		exit;
	}
} // mbp_po_direction_image()



/**
 * Add management page to the Wordpress system:
 */
function mbp_po_add_post()
{
	add_options_page('Post Ordering', 'Post Ordering', 10, __FILE__, 'mbp_po_postorder');
} // po_add_post()


// Srart Registration.

/**
 * Plugin registration form
 */
function mbp_poRegistrationForm($form_name, $submit_btn_txt='Register', $name, $email, $hide=0, $submit_again='') {
	$wp_url = get_bloginfo('wpurl');
	$wp_url = (strpos($wp_url,'http://') === false) ? get_bloginfo('siteurl') : $wp_url;
	$plugin_pg    = 'options-general.php';
	$thankyou_url = $wp_url.'/wp-admin/'.$plugin_pg.'?page='.$_GET['page'];
	$onlist_url   = $wp_url.'/wp-admin/'.$plugin_pg.'?page='.$_GET['page'].'&amp;mbp_onlist=1';
	if ( $hide == 1 ) $align_tbl = 'left';
	else $align_tbl = 'center';
	?>
	
	<?php if ( $submit_again != 1 ) { ?>
	<script><!--
	function trim(str){
		var n = str;
		while ( n.length>0 && n.charAt(0)==' ' ) 
			n = n.substring(1,n.length);
		while( n.length>0 && n.charAt(n.length-1)==' ' )	
			n = n.substring(0,n.length-1);
		return n;
	}
	function mbp_poValidateForm_0() {
		var name = document.<?php echo $form_name;?>.name;
		var email = document.<?php echo $form_name;?>.from;
		var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
		var err = ''
		if ( trim(name.value) == '' )
			err += '- Name Required\n';
		if ( reg.test(email.value) == false )
			err += '- Valid Email Required\n';
		if ( err != '' ) {
			alert(err);
			return false;
		}
		return true;
	}
	//-->
	</script>
	<?php } ?>
	<table align="<?php echo $align_tbl;?>">
	<form name="<?php echo $form_name;?>" method="post" action="http://www.aweber.com/scripts/addlead.pl" <?php if($submit_again!=1){;?>onsubmit="return mbp_poValidateForm_0()"<?php }?>>
	 <input type="hidden" name="unit" value="maxbp-activate">
	 <input type="hidden" name="redirect" value="<?php echo $thankyou_url;?>">
	 <input type="hidden" name="meta_redirect_onlist" value="<?php echo $onlist_url;?>">
	 <input type="hidden" name="meta_adtracking" value="mr-posr-ordering">
	 <input type="hidden" name="meta_message" value="1">
	 <input type="hidden" name="meta_required" value="from,name">
	 <input type="hidden" name="meta_forward_vars" value="1">	
	 <?php if ( $submit_again == 1 ) { ?> 	
	 <input type="hidden" name="submit_again" value="1">
	 <?php } ?>		 
	 <?php if ( $hide == 1 ) { ?> 
	 <input type="hidden" name="name" value="<?php echo $name;?>">
	 <input type="hidden" name="from" value="<?php echo $email;?>">
	 <?php } else { ?>
	 <tr><td>Name: </td><td><input type="text" name="name" value="<?php echo $name;?>" size="25" maxlength="150" /></td></tr>
	 <tr><td>Email: </td><td><input type="text" name="from" value="<?php echo $email;?>" size="25" maxlength="150" /></td></tr>
	 <?php } ?>
	 <tr><td>&nbsp;</td><td><input type="submit" name="submit" value="<?php echo $submit_btn_txt;?>" class="button" /></td></tr>
	 </form>
	</table>
	<?php
}

/**
 * Register Plugin - Step 2
 */
function mbp_poRegisterStep2($form_name='frm2',$name,$email) {
	$msg = 'You have not clicked on the confirmation link yet. A confirmation email has been sent to you again. Please check your email and click on the confirmation link to activate the plugin.';
	if ( trim($_GET['submit_again']) != '' && $msg != '' ) {
		echo '<div id="message" class="updated fade"><p><strong>'.$msg.'</strong></p></div>';
	}
	?>
	<style type="text/css">
	table, tbody, tfoot, thead {
		padding: 8px;
	}
	tr, th, td {
		padding: 0 8px 0 8px;
	}
	</style>
	<div class="wrap"><h2> <?php echo MBP_PO_NAME.' '.MBP_PO_VERSION; ?></h2>
	 <center>
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:1px solid #e3e3e3; padding: 8px; background-color:#f1f1f1;">
	 <tr><td align="center">
	 <table width="650" cellpadding="5" cellspacing="1" style="border:1px solid #e9e9e9; padding: 8px; background-color:#ffffff; text-align:left;">
	  <tr><td align="center"><h3>Almost Done....</h3></td></tr>
	  <tr><td><h3>Step 1:</h3></td></tr>
	  <tr><td>A confirmation email has been sent to your email "<?php echo $email;?>". You must click on the link inside the email to activate the plugin.</td></tr>
	  <tr><td><strong>The confirmation email will look like:</strong><br /><img src="http://www.maxblogpress.com/images/activate-plugin-email.jpg" vspace="4" border="0" /></td></tr>
	  <tr><td>&nbsp;</td></tr>
	  <tr><td><h3>Step 2:</h3></td></tr>
	  <tr><td>Click on the button below to Verify and Activate the plugin.</td></tr>
	  <tr><td><?php mbp_poRegistrationForm($form_name.'_0','Verify and Activate',$name,$email,$hide=1,$submit_again=1);?></td></tr>
	 </table>
	 </td></tr></table><br />
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:1px solid #e3e3e3; padding:8px; background-color:#f1f1f1;">
	 <tr><td align="center">
	 <table width="650" cellpadding="5" cellspacing="1" style="border:1px solid #e9e9e9; padding:8px; background-color:#ffffff; text-align:left;">
	   <tr><td><h3>Troubleshooting</h3></td></tr>
	   <tr><td><strong>The confirmation email is not there in my inbox!</strong></td></tr>
	   <tr><td>Dont panic! CHECK THE JUNK, spam or bulk folder of your email.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>It's not there in the junk folder either.</strong></td></tr>
	   <tr><td>Sometimes the confirmation email takes time to arrive. Please be patient. WAIT FOR 6 HOURS AT MOST. The confirmation email should be there by then.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>6 hours and yet no sign of a confirmation email!</strong></td></tr>
	   <tr><td>Please register again from below:</td></tr>
	   <tr><td><?php mbp_poRegistrationForm($form_name,'Register Again',$name,$email,$hide=0,$submit_again=2);?></td></tr>
	   <tr><td><strong>Help! Still no confirmation email and I have already registered twice</strong></td></tr>
	   <tr><td>Okay, please register again from the form above using a DIFFERENT EMAIL ADDRESS this time.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr>
		 <td><strong>Why am I receiving an error similar to the one shown below?</strong><br />
			 <img src="http://www.maxblogpress.com/images/no-verification-error.jpg" border="0" vspace="8" /><br />
		   You get that kind of error when you click on &quot;Verify and Activate&quot; button or try to register again.<br />
		   <br />
		   This error means that you have already subscribed but have not yet clicked on the link inside confirmation email. In order to  avoid any spam complain we don't send repeated confirmation emails. If you have not recieved the confirmation email then you need to wait for 12 hours at least before requesting another confirmation email. </td>
	   </tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>But I've still got problems.</strong></td></tr>
	   <tr><td>Stay calm. <strong><a href="http://www.maxblogpress.com/contact-us/" target="_blank">Contact us</a></strong> about it and we will get to you ASAP.</td></tr>
	 </table>
	 </td></tr></table>
	 </center>		
	<p style="text-align:center;margin-top:3em;"><strong><?php echo MBP_PO_NAME.' '.MBP_PO_VERSION; ?> by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>
	</div>
	<?php
}

/**
 * Register Plugin - Step 1
 */
function mbp_poRegisterStep1($form_name='frm1',$userdata) {
	$name  = trim($userdata->first_name.' '.$userdata->last_name);
	$email = trim($userdata->user_email);
	?>
	<style type="text/css">
	tabled , tbody, tfoot, thead {
		padding: 8px;
	}
	tr, th, td {
		padding: 0 8px 0 8px;
	}
	</style>
	<div class="wrap"><h2> <?php echo MBP_PO_NAME.' '.MBP_PO_VERSION; ?></h2>
	 <center>
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:2px solid #e3e3e3; padding: 8px; background-color:#f1f1f1;">
	  <tr><td align="center">
		<table width="548" align="center" cellpadding="3" cellspacing="1" style="border:1px solid #e9e9e9; padding: 8px; background-color:#ffffff;">
		  <tr><td align="center"><h3>Please register the plugin to activate it. (Registration is free)</h3></td></tr>
		  <tr><td align="left">In addition you'll receive complimentary subscription to MaxBlogPress Newsletter which will give you many tips and tricks to attract lots of visitors to your blog.</td></tr>
		  <tr><td align="center"><strong>Fill the form below to register the plugin:</strong></td></tr>
		  <tr><td align="center"><?php mbp_poRegistrationForm($form_name,'Register',$name,$email);?></td></tr>
		  <tr><td align="center"><font size="1">[ Your contact information will be handled with the strictest confidence <br />and will never be sold or shared with third parties ]</font></td></tr>
		</table>
	  </td></tr></table>
	 </center>
	<p style="text-align:center;margin-top:3em;"><strong><?php echo MBP_PO_NAME.' '.MBP_PO_VERSION; ?> by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>
	</div>
	<?php
}

if (isset($_GET['image']) && ($_GET['image'] == 'up' || $_GET['image'] == 'down' || $_GET['image'] == 'refresh' || $_GET['image'] == 'spacer')) {
	mbp_po_direction_image($_GET['image']);
} else {
	add_action('admin_menu', 'mbp_po_add_post');
}
?>
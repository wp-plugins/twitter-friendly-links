<?php
/*
Plugin Name: Twitter Friendly Links
Plugin URI: http://kovshenin.com/wordpress/plugins/twitter-friendly-links/
Description: Twitter Friendly Links
Author: Konstantin Kovshenin
Version: 0.3.3
Author URI: http://kovshenin.com/

*/

add_action("init", "twitter_friendly_links_init");

function twitter_friendly_links_init() {
	register_activation_hook(__FILE__, 'twitter_friendly_links_activate' );
	add_action('template_redirect', 'twitter_friendly_links');
	add_action("admin_menu", "twitter_friendly_links_menu");
	add_action("admin_menu", "twitter_friendly_links_box");
	
	$options = get_option("twitter_friendly_links");
	
	// Fix for the Twitter Tools plugin
	$twitter_tools_fix = ($options["twitter_tools_fix"] == "checked") ? true : false;
	if ($twitter_tools_fix)
		add_filter("tweet_blog_post_url", "permalink_to_twitter_link", 10, 1);
}

function twitter_friendly_links_activate() {
	$options = get_option("twitter_friendly_links");
	
	// Default plugin options
	$defaults = array(
		"style" => "",		// default style is example.com/123
		"redirect" => 302,		// temporary redirect by default
		"pages_enabled" => "",	// pages disabled by default
		"twitter_tools_fix" => "", // disabled by deafult
	);
	
	foreach($defaults as $key => $default_value)
		$options[$key] = (!isset($options[$key])) ? $default_value : $options[$key];
	
	update_option("twitter_friendly_links", $options);
}

function twitter_friendly_links() {
	$options = get_option("twitter_friendly_links");
	
	$style = $options["style"];
	$redirect = $options["redirect"];
	$pages_enabled = ($options["pages_enabled"] == "checked") ? true : false;
	
	$uri = $_SERVER["REQUEST_URI"];
	$home = get_option("home");
	$home = str_replace("http://".$_SERVER["SERVER_NAME"], "", $home);
	$uri = str_replace($home, "", $uri);
	if (ereg("^/{$style}([0-9]+)/?$", $uri, $regs))
	{
		$post_id = $regs[1];
		$post = new WP_Query("p=$post_id");
		if ($post->have_posts())
		{
			$post->the_post();
			if ($redirect == 301)
				header("HTTP/1.1 301 Moved Permanently");
			elseif ($redirect == 302)
				header("HTTP/1.1 302 Found");

			header("Location: ".get_permalink());
		}
		elseif ($pages_enabled)
		{
			$post = new WP_Query("page_id=$post_id"); // Search thru pages
			if ($post->have_posts())
			{
				$post->the_post();
				if ($redirect == 301)
					header("HTTP/1.1 301 Moved Permanently");
				elseif ($redirect == 302)
					header("HTTP/1.1 302 Found");
					
				header("Location: ".get_permalink());
			}
		}
	}
}

function twitter_friendly_links_admin_scripts() {
	$query = new WP_Query("posts_per_page=-1");
	$total_posts = $query->post_count;
	$query = new WP_Query("posts_per_page=-1&post_type=page");
	$total_pages = $query->post_count;

	$plugin_url = trailingslashit(get_bloginfo('wpurl')).PLUGINDIR.'/'. dirname(plugin_basename(__FILE__));
	wp_enqueue_script('twitterfriendly_navsettings', $plugin_url.'/js/nav_settings.js', array('jquery'));
	wp_localize_script('twitterfriendly_navsettings', 'TwitterFriendly', array(
		"plugin_url" => $plugin_url,
		"total_posts" => $total_posts,
		"total_pages" => $total_pages,		
	));
}

function twitter_friendly_links_menu() {
	$twitter_friendly_admin = add_options_page('Twitter Friendly Links', 'Twitter Friendly Links', 8, __FILE__, 'twitter_friendly_links_options');
	add_action("admin_print_scripts-$twitter_friendly_admin", "twitter_friendly_links_admin_scripts");
}

function twitter_friendly_links_options() {
	$options = get_option("twitter_friendly_links");
	
	if (isset($_POST["twitter-friendly-links-submit"]))
	{
		$options["style"] = $_POST["style"];
		$options["redirect"] = $_POST["redirect"];
		$options["pages_enabled"] = $_POST["pages_enabled"];
		$options["twitter_tools_fix"] = $_POST["twitter_tools_fix"];
		update_option("twitter_friendly_links", $options);
	}
	
	$style = $options["style"];
	$redirect = $options["redirect"];
	$pages_enabled = $options["pages_enabled"];
	$twitter_tools_fix = $options["twitter_tools_fix"];
	
	$selected[$redirect] = " selected=\"selected\"";
	
?>
<div class="wrap">
<h2>Twitter Friendly Links</h2>
<h3>Settings</h3>
<p>Make sure you get this right from the first time because changing this afterward will affect all your previous twitter friendly links and you're most likely to get 404 error messages on your old links. For more information please visit <a href="http://kovshenin.com/wordpress/plugins/twitter-friendly-links/">Twitter Friendly Links</a>. Oh and make sure you save changes before copying any links from the table below.</p>
<form method="post">
	<input type="hidden" value="1" name="twitter-friendly-links-submit"/>
	<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row"><label for="style">Links style</label></th>
			<td>
				<input type="text"  value="<?=$style;?>" id="style" name="style"/>
				<span class="setting-description"><?= get_option("home"); ?>/<strong><?=$style;?></strong>123</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="redirect">Redirection type</label></th>
			<td>
				<select name="redirect" id="redirect">
					<option value="302"<?=$selected[302];?>>302 Found (Temporary redirect)</option>
					<option value="301"<?=$selected[301];?>>301 Moved Permanently</option>
				</select>
				<span class="setting-description">302 by default</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="pages_enabled">Enable shortlinks for pages</label></th>
			<td>
				<input type="checkbox" value="checked" <?=$pages_enabled;?> id="pages_enabled" name="pages_enabled"/>
				<span class="setting-description">The style for pages will be the same as for posts</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="twitter_tools_fix">Twitter Tools plugin fix</label></th>
			<td>
				<input type="checkbox" value="checked" <?=$twitter_tools_fix;?> id="twitter_tools_fix" name="twitter_tools_fix"/>
				<span class="setting-description">Linking fix for the <a href="http://wordpress.org/extend/plugins/twitter-tools/">Twitter Tools</a> plugin. Described <a href="http://kovshenin.com/archives/compatibility-twitter-tools-twitter-friendly-links/">here</a></span>
			</td>
		</tr>
	</tbody>
	</table>
	<p class="submit">
		<input type="submit" value="Save Changes" class="button-primary" name="Submit"/>
	</p>
</form>
<h3>List of short links</h3>
<?php
	$query = new WP_Query("posts_per_page=-1");
	$total_posts = $query->post_count;
	$query = new WP_Query("posts_per_page=-1&post_type=page");
	$total_pages = $query->post_count;
?>
<div class="tablenav">
	<div class="alignleft actions">
		<a href="#" id="twitter_links_posts" class="twitter_links_postpage" style="font-weight: bold;">Posts</a> / 
<?php
	if ($pages_enabled == "checked")
		echo '<a href="#" id="twitter_links_pages" class="twitter_links_postpage">Pages</a>';
	else
		echo '<a href="#" id="twitter_links_pages"></a>Pages (disabled)';
?>
	
	</div>
	
	<div class="tablenav-pages"><span class="displaying-num">Displaying <span id="twitter_links_displaying">1-15</span> of <span id="twitter_links_displaying_total"><?=$total_posts;?></span></span>
	<a href="#" class="twitter_friendly first">First</a>
	<a href="#" class="twitter_friendly page-numbers post1 current">1</a>
	<span class="twitter_friendly posts-numbers">
<?php
	$pages = $total_posts / 15;
	for ($i = 2; $i <= $pages; $i++)
	{
		if ($i > 5) { $display = 'style="display: none"'; $firstpages = ""; }
		else { $display = ""; $firstpages = " firstpages"; }
		
		echo '<a href="#" class="twitter_friendly page-numbers'.$firstpages.' post'.$i.'" '.$display.'>'.$i.'</a> ';
	}
?>
	</span>
	<a href="#" class="twitter_friendly last">Last</a>
	
	<span class="twitter_friendly pages-numbers" style="display: none">
<?php
	$pages = $total_pages / 15;
	for ($i = 2; $i <= $pages; $i++)
	{
		echo '<a href="#" class="twitter_friendly page-numbers">'.$i.'</a> ';
	}
?>
	</span>

	<div class="clear"></div>
	</div>
</div>

<table class="widefat post fixed">
	<thead>
		<tr>
			<th class="manage-column column-name" id="title" scope="col" style="width: 3em;">&nbsp;</th>
			<th class="manage-column column-name" id="title" scope="col">Title</th>
			<th class="manage-column column-name" id="twitterlink" scope="col" style="width: 25em;">Friendly Link</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th class="manage-column column-name" id="title" scope="col">&nbsp;</th>
			<th class="manage-column column-name" id="title" scope="col">Title</th>
			<th class="manage-column column-name" id="twitterlink" scope="col">Friendly Link</th>
		</tr>
	</tfoot>
	<tbody id="twitter_links_table_body">
<?
	$query = new WP_Query("posts_per_page=15");
	$i = 0;
	while ($query->have_posts())
	{
		$query->the_post();
		$friendly_link = twitter_link($query->post->ID);
		$i++;
?>
			<tr class="alternate">
				<td style="text-align: right"><?=$i;?>.</td>
				<td><a href="<? the_permalink(); ?>"><? the_title();?></a><br /></td>
				<td><a href="<?= $friendly_link;?>"><?= $friendly_link; ?></a></td>
			</tr>
<?
	}
?>
	</tbody>
</table>

<?
}

function twitter_friendly_links_box() {
	$options = get_option("twitter_friendly_links");
	$pages_enabled = ($options["pages_enabled"] == "checked") ? true : false;
	 
	if (function_exists("add_meta_box")) {
		add_meta_box("twitter_friendly_id", "Twitter Stuff", "twitter_friendly_links_inner_box", "post", "side");
		if ($pages_enabled) add_meta_box("twitter_friendly_id", "Twitter Stuff", "twitter_friendly_links_inner_box", "page", "side");
	}
	else {
		add_action("dbx_post_advanced", "twitter_friendly_links_old_box");
		if ($pages_enabled) add_action("dbx_page_advanced", "twitter_friendly_links_old_box");
	}
}

function twitter_friendly_links_old_box() {
	echo '<div class="dbx-b-ox-wrapper">' . "\n";
	echo '<fieldset id="twitter_friendly_fieldsetid" class="dbx-box">' . "\n";
	echo '<div class="dbx-h-andle-wrapper"><h3 class="dbx-handle">' . 
	      "Twitter Friendly" . "</h3></div>";   
	echo '<div class="dbx-c-ontent-wrapper"><div class="dbx-content">';
	twitter_friendly_links_inner_box();
	echo "</div></div></fieldset></div>\n";
}

function twitter_friendly_links_inner_box($post) {
	if ($post->post_status != "publish") {
		echo "<p>Please publish to get Twitter links.</p>";
		return;
	}
	
	$post_id = $post->ID;
	
	$friendly_link = twitter_link($post_id);
	$title_length = utf8_strlen($post->post_title);
	$link_length = utf8_strlen($friendly_link);
	$overall_length = $title_length + $link_length;
	$excess = 140 - $overall_length;
	
	if ($excess < 0) {
		$excess *= -1;
		$title = mb_substr($post->post_title,0,$title_length-($excess+4),'UTF-8') . "... ";
	}
	else {
		$title = $post->post_title . " ";
	}
	
	echo "<p><strong>Friendly link:</strong> <a href=\"{$friendly_link}\">{$friendly_link}</a></p>";
	echo "<p><strong>Tweet:</strong> {$title}<a href=\"{$friendly_link}\">{$friendly_link}</a></p>";
	echo "<p style=\"text-align: right;\"><strong><a href=\"http://twitter.com/home/?status=" . urlencode($title) . urlencode($friendly_link) . "\">Tweet this</a> &raquo;</strong></p>";
}

if (!function_exists("utf8_strlen")) {
	function utf8_strlen($s) {
	    $c = strlen($s); $l = 0;
	    for ($i = 0; $i < $c; ++$i) if ((ord($s[$i]) & 0xC0) != 0x80) ++$l;
	    return $l;
	}
}

function twitter_link($id = 0) {
	$options = get_option("twitter_friendly_links");
	$style = $options["style"];
	
	if ($id == 0)
	{
		global $post;
		$post_id = $post->ID;
	}
	else
		$post_id = $id;
	
	$friendly_link = get_option("home") . "/" . $style . $post_id;
	
	return $friendly_link;
}

function permalink_to_twitter_link($permalink)
{
	$options = get_option("twitter_friendly_links");
	$style = $options["style"];
	$post_id = url_to_postid($permalink);
	$friendly_link = get_option("home") . "/" . $style . $post_id;
	return $friendly_link;
}

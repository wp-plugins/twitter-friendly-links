<?php
/*
Plugin Name: Twitter Friendly Links
Plugin URI: http://kovshenin.com/wordpress/plugins/twitter-friendly-links/
Description: Twitter Friendly Links
Author: Konstantin Kovshenin
Version: 0.3.6
Author URI: http://kovshenin.com/

*/

add_action("init", "twitter_friendly_links_init", 9);

function twitter_friendly_links_init() {
	register_activation_hook(__FILE__, 'twitter_friendly_links_activate');
	add_action('template_redirect', 'twitter_friendly_links', 9);
	add_action("admin_menu", "twitter_friendly_links_menu");
	add_action("admin_menu", "twitter_friendly_links_box");
	
	$options = get_option("twitter_friendly_links");
	
	// Fix for the Twitter Tools & Tweet This plugins
	$twitter_tools_fix = ($options["twitter_tools_fix"] == "checked") ? true : false;
	$tweet_this_fix = ($options["tweet_this_fix"] == "checked") ? true : false;
	
	if ($twitter_tools_fix)
		add_filter("tweet_blog_post_url", "permalink_to_twitter_link", 10, 1);
	if ($tweet_this_fix)
		add_filter("the_content", "tweet_this_fix", 10);
	
	// Link relations options
	$html_shortlink_rel = ($options["html_shortlink_rel"] == "checked") ? true : false;
	$rel_canonical = ($options["rel_canonical"] == "checked") ? true : false;
	$rev_canonical = ($options["rev_canonical"] == "checked") ? true : false;
	
	if ($html_shortlink_rel || $rel_canonical || $rev_canonical)
		add_action("wp_head", "twitter_friendly_links_relations");
}

function twitter_friendly_links_activate() {
	$options = get_option("twitter_friendly_links");
	
	// Default plugin options
	$defaults = array(
		"style" => "",		// default style is example.com/123
		"redirect" => 302,		// temporary redirect by default
		"pages_enabled" => "",	// pages disabled by default
		"twitter_tools_fix" => "", // disabled by deafult
		"askapache_google_404" => "",
		"tweet_this_fix" => "",
		
		"ga_tracking" => "",
		
		"html_shortlink_rel" => "",
		"http_shortlink_rel" => "",
		"rel_canonical" => "",
		"rev_canonical" => "",
	);
	
	foreach($defaults as $key => $default_value)
		$options[$key] = (!isset($options[$key])) ? $default_value : $options[$key];
	
	update_option("twitter_friendly_links", $options);
}

function twitter_friendly_links_relations()
{
	$options = get_option("twitter_friendly_links");
	$pages_enabled = ($options["pages_enabled"] == "checked") ? true : false;
	
	if (is_single() || ($pages_enabled && is_page()))
	{
		$options = get_option("twitter_friendly_links");
		$html_shortlink_rel = ($options["html_shortlink_rel"] == "checked") ? true : false;
		$rel_canonical = ($options["rel_canonical"] == "checked") ? true : false;
		$rev_canonical = ($options["rev_canonical"] == "checked") ? true : false;
		
		global $post;
		$post_id = $post->ID;
		$short_url = twitter_link($post_id);
		$permalink = get_permalink($post_id);
		
		if ($html_shortlink_rel)
			echo "<link rel=\"shortlink\" href=\"$short_url\" />\n";
		if ($rev_canonical)
			echo "<link rev=\"canonical\" href=\"$short_url\" />\n";
		if ($rel_canonical)
			echo "<link rel=\"canonical\" href=\"$permalink\" />\n";
	}
}	

function twitter_friendly_links() {
	$options = get_option("twitter_friendly_links");
	
	$style = $options["style"];
	$redirect = $options["redirect"];
	$pages_enabled = ($options["pages_enabled"] == "checked") ? true : false;
	$ga_tracking = (strlen($options["ga_tracking"]) > 1) ? "?".$options["ga_tracking"] : "";
	
	$uri = $_SERVER["REQUEST_URI"];
	$home = get_option("home");
	$home = str_replace("http://".$_SERVER["SERVER_NAME"], "", $home);
	$uri = str_replace($home, "", $uri);
	if (ereg("^/{$style}([0-9]+)/?$", $uri, $regs))
	{
		// Fix for the AskApache Google 404 plugin
		$askapache_google_404 = ($options["askapache_google_404"] == "checked") ? true : false;
		if ($askapache_google_404)
		{
			global $AskApacheGoogle404;
			remove_action("template_redirect", array($AskApacheGoogle404, 'template_redirect'));
		}
		
		$post_id = $regs[1];
		$post = new WP_Query("p=$post_id");
		if ($post->have_posts())
		{
			$post->the_post();
			if ($redirect == 301)
				header("HTTP/1.1 301 Moved Permanently");
			elseif ($redirect == 302)
				header("HTTP/1.1 302 Found");

			header("Location: ".get_permalink().$ga_tracking);
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
					
				header("Location: ".get_permalink().$ga_tracking);
			}
		}
	}
	else
	{
		global $post;
		
		// Link relations
		$http_shortlink_rel = ($options["http_shortlink_rel"] == "checked") ? true : false;
		if ($http_shortlink_rel)
			if (is_single() || ($pages_enabled && is_page()))
				header("Link: <" . twitter_link($post_id) . ">; rel=shortlink");
	}
}

function twitter_friendly_links_admin_scripts() {
	$count_posts = wp_count_posts();
	$total_posts = $count_posts->publish;
	$count_pages = wp_count_posts('page');
	$total_pages = $count_pages->publish;

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
		$options["askapache_google_404"] = $_POST["askapache_google_404"];
		$options["tweet_this_fix"] = $_POST["tweet_this_fix"];
		
		$options["ga_tracking"] = $_POST["ga_tracking"];
		
		$options["html_shortlink_rel"] = $_POST["html_shortlink_rel"];
		$options["http_shortlink_rel"] = $_POST["http_shortlink_rel"];
		$options["rel_canonical"] = $_POST["rel_canonical"];
		$options["rev_canonical"] = $_POST["rev_canonical"];
		
		update_option("twitter_friendly_links", $options);
	}
	
	$style = $options["style"];
	$redirect = $options["redirect"];
	$pages_enabled = $options["pages_enabled"];
	$twitter_tools_fix = $options["twitter_tools_fix"];
	$askapache_google_404 = $options["askapache_google_404"];
	$tweet_this_fix = $options["tweet_this_fix"];
	
	$ga_tracking = $options["ga_tracking"];
	
	$html_shortlink_rel = $options["html_shortlink_rel"];
	$http_shortlink_rel = $options["http_shortlink_rel"];
	$rel_canonical = $options["rel_canonical"];
	$rev_canonical = $options["rev_canonical"];
	
	$selected[$redirect] = " selected=\"selected\"";
	
?>
<div class="wrap">
<h2>Twitter Friendly Links</h2>
<h3>General Settings</h3>
<p>Make sure you get this right from the first time because changing this afterward will affect all your previous twitter friendly links and you're most likely to get 404 error messages on your old links. For more information please visit <a href="http://kovshenin.com/wordpress/plugins/twitter-friendly-links/">Twitter Friendly Links</a>. Oh and make sure you save changes before copying any links from the table below.</p>
<form method="post">
	<input type="hidden" value="1" name="twitter-friendly-links-submit"/>
	<table class="form-table" style="margin-bottom:10px;">
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
			<th scope="row"><label for="style">Tag destination links</label></th>
			<td>
				<input type="text" style="min-width:25em;" value="<?=$ga_tracking;?>" id="ga_tracking" name="ga_tracking" /><br />
				<span class="setting-description">You can tag your destination links for Google Analytics Tracking. For example: <code>utm_source=twitter&amp;utm_medium=shortlink&amp;utm_campaign=shortlinks</code>. You can generate a tagged link using the <a href="https://www.google.com/support/googleanalytics/bin/answer.py?hl=en&answer=55578">Google Analytics URL Builder</a>. Do not include the website address in the input box above. Start from utm_source. This string will be appended to the destination address. Leave blank to disable. This is still beta ;)</span>
			</td>
		</tr>
	</tbody>
	</table>

<h3>Link Relations</h3>
<p>Search engines and URL shorteners. Bunch of thoughts on linking relations, so here are the main options. A little note about Rev Canonical - <a href="http://www.mnot.net/blog/2009/04/14/rev_canonical_bad">it may hurt the web</a>, so use with caution.</p>

	<table class="form-table" style="margin-bottom:10px;">
	<tbody>
		<tr valign="top">
			<th scope="row"><label for="html_shortlink_rel">HTML Shortlink relation</label></th>
			<td>
				<input type="checkbox" value="checked" <?=$html_shortlink_rel;?> id="html_shortlink_rel" name="html_shortlink_rel" />
				<span class="setting-description">Adds a link rel=&quot;shortlink&quot; to the head section of your posts and (if enabled) pages. <a href="http://purl.org/net/shortlink">Specification</a>.</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="http_shortlink_rel">HTTP Shortlink relation</label></th>
			<td>
				<input type="checkbox" value="checked" <?=$http_shortlink_rel;?> id="http_shortlink_rel" name="http_shortlink_rel" />
				<span class="setting-description">Passes a link rel=&quot;shortlink&quot; along with the HTTP responses of your posts and (if enabled) pages. <a href="http://purl.org/net/shortlink">Specification</a>.</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="rel_canonical">Canonical relation</label></th>
			<td>
				<input type="checkbox" value="checked" <?=$rel_canonical;?> id="rel_canonical" name="rel_canonical" />
				<span class="setting-description">Adds a link rel=&quot;canonical&quot; href=&quot;permalink&quot; to your HTML head in posts and (if enabled) pages.</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="rev_canonical">Canonical reverse relation</label></th>
			<td>
				<input type="checkbox" value="checked" <?=$rev_canonical;?> id="rev_canonical" name="rev_canonical" />
				<span class="setting-description">Adds a link rev=&quot;canonical&quot; href=&quot;shortlink&quot; to your HTML head in posts and (if enabled) pages.</span>
			</td>
		</tr>
	</tbody>
	</table>
	
<h3>Compatibility</h3>
<p>If you use any of the plugins listed below and you are experiencing problems with short linking, enable the fixes. If there's no fix for a plugin you're using you may request it on the <a href="http://kovshenin.com/wordpress/plugins/twitter-friendly-links/">Twitter Friendly Links</a> page.</p>
	<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row"><label for="twitter_tools_fix">Twitter Tools plugin fix</label></th>
			<td>
				<input type="checkbox" value="checked" <?=$twitter_tools_fix;?> id="twitter_tools_fix" name="twitter_tools_fix"/>
				<span class="setting-description">Linking fix for the <a href="http://wordpress.org/extend/plugins/twitter-tools/">Twitter Tools</a> plugin. Described <a href="http://kovshenin.com/archives/compatibility-twitter-tools-twitter-friendly-links/">here</a></span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="tweet_this_fix">Tweet-This plugin fix</label></th>
			<td>
				<input type="checkbox" value="checked" <?=$tweet_this_fix;?> id="tweet_this_fix" name="tweet_this_fix"/>
				<span class="setting-description">Linking fix for the <a href="http://wordpress.org/extend/plugins/tweet-this/">Tweet This</a> plugin.</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="askapache_google_404">AskApache Google 404 fix</label></th>
			<td>
				<input type="checkbox" value="checked" <?=$askapache_google_404;?> id="askapache_google_404" name="askapache_google_404"/>
				<span class="setting-description">Fix for the <a href="http://wordpress.org/extend/plugins/askapache-google-404/">AskApache Google 404</a> plugin.</span>
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
	$count_posts = wp_count_posts();
	$total_posts = $count_posts->publish;
	$count_pages = wp_count_posts('page');
	$total_pages = $count_pages->publish;
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

function tweet_this_fix($content) {
	$twitter_link = twitter_link();
	$content = preg_replace("/href=\\\"http:\/\/twitter.com\/home\/\?status=([^\\\"]+)\\\"/", "href=\"http://twitter.com/home/?status=" . urlencode(get_the_title() . " " . $twitter_link) . "\"", $content);
	return $content;
}
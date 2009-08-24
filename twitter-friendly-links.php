<?php
/*
Plugin Name: Twitter Friendly Links
Plugin URI: http://kovshenin.com/wordpress/plugins/twitter-friendly-links/
Description: Your very own TinyURL within your OWN domain! If you DO promote your blog posts in Twitter, then you MUST make your links look cool!
Author: Konstantin Kovshenin
Version: 0.4.1
Author URI: http://kovshenin.com/

	License

    Twitter Friendly Links
    Copyright (C) 2009 Konstantin Kovshenin (kovshenin@live.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
    
*/

add_action("init", "twitter_friendly_links_init", 9);

function twitter_friendly_links_init() {
	add_action('template_redirect', 'twitter_friendly_links', 9);
	add_action("admin_menu", "twitter_friendly_links_menu");
	add_action("admin_menu", "twitter_friendly_links_box");
	
	$options = get_option("twitter_friendly_links");
	
	// Fix for the Twitter Tools & Tweet This plugins
	$twitter_tools_fix = ($options["twitter_tools_fix"] == "checked") ? true : false;
	$tweet_this_fix = ($options["tweet_this_fix"] == "checked") ? true : false;
	$sociable_fix = ($options["sociable_fix"] == "checked") ? true : false;
	
	if ($twitter_tools_fix)
		add_filter("tweet_blog_post_url", "permalink_to_twitter_link", 10, 1);
	if ($tweet_this_fix)
		add_filter("the_content", "tfl_tweet_this_fix", 10);
	if ($sociable_fix)
		add_filter("sociable_link", "tfl_sociable_fix");
	
	// Link relations options
	$html_shortlink_rel = ($options["html_shortlink_rel"] == "checked") ? true : false;
	$rel_canonical = ($options["rel_canonical"] == "checked") ? true : false;
	
	if ($html_shortlink_rel || $rel_canonical)
		add_action("wp_head", "twitter_friendly_links_relations");
		
	// Notice about core changes
	if ($options["tfl_core_notice"] == 1 && !isset($_GET["tfl_hide"]))
		add_action("admin_notices", "tfl_admin_notices_core");

	// Notify the administrator if permalinks are switched off
	$permalink_structure = (isset($_POST['permalink_structure'])) ? $_POST['permalink_structure'] : get_option("permalink_structure");
	if ($permalink_structure == "")
		add_action("admin_notices", "tfl_admin_notices");
}

function twitter_friendly_links_relations()
{
	$options = get_option("twitter_friendly_links");
	$posts_enabled = ($options["posts_enabled"] == "checked") ? true : false;
	$pages_enabled = ($options["pages_enabled"] == "checked") ? true : false;
	$attachments_enabled = ($options["attachments_enabled"] == "checked") ? true : false;
	
	if (($posts_enabled && is_single()) || ($pages_enabled && is_page()) || ($attachments_enabled && is_attachment()))
	{
		$options = get_option("twitter_friendly_links");
		$html_shortlink_rel = ($options["html_shortlink_rel"] == "checked") ? true : false;
		$rel_canonical = ($options["rel_canonical"] == "checked") ? true : false;
		
		global $post;
		$post_id = $post->ID;
		$short_url = twitter_link($post_id);
		$permalink = get_permalink($post_id);
		
		if ($html_shortlink_rel)
			echo "<link rel=\"shortlink\" href=\"$short_url\" />\n";
		if ($rel_canonical)
			echo "<link rel=\"canonical\" href=\"$permalink\" />\n";
	}
}	

function twitter_friendly_links() {
	$options = get_option("twitter_friendly_links");
	
	$style = $options["style"];
	$format = $options["format"];
	$redirect = $options["redirect"];
	$posts_enabled = ($options["posts_enabled"] == "checked") ? true : false;
	$pages_enabled = ($options["pages_enabled"] == "checked") ? true : false;
	$attachments_enabled = ($options["attachments_enabled"] == "checked") ? true : false;
	
	$ga_tracking = (strlen($options["ga_tracking"]) > 1) ? "?".$options["ga_tracking"] : "";
	
	$uri = $_SERVER["REQUEST_URI"];
	$home = get_option("home");
	$home = str_replace("http://".$_SERVER["SERVER_NAME"], "", $home);
	$uri = strtolower(str_replace($home, "", $uri));
	if (ereg("^/{$style}([0-9a-z]+)/?$", $uri, $regs))
	{
		// Fix for the AskApache Google 404 plugin
		$askapache_google_404 = ($options["askapache_google_404"] == "checked") ? true : false;
		if ($askapache_google_404)
		{
			global $AskApacheGoogle404;
			remove_action("template_redirect", array($AskApacheGoogle404, 'template_redirect'));
		}
		
		$post_id = $regs[1];
		if ($format == "base32")
			$post_id = tfl_base32($post_id, true);

		$posts = new WP_Query("p=$post_id&post_type=any");
		if ($posts->have_posts())
		{
			$posts->the_post();
			$post = $posts->post;
			
			if (!$posts_enabled && $post->post_type == "post") return;
			if (!$pages_enabled && $post->post_type == "page") return;
			if (!$attachments_enabled && $post->post_type == "attachment") return;
			
			if ($redirect == 301)
				header("HTTP/1.1 301 Moved Permanently");
			elseif ($redirect == 302)
				header("HTTP/1.1 302 Found");

			header("Location: ".get_permalink().$ga_tracking);
		}
	}
	else
	{
		global $post;
		
		// Link relations
		$http_shortlink_rel = ($options["http_shortlink_rel"] == "checked") ? true : false;
		if ($http_shortlink_rel)
			if (($posts_enabled && is_single()) || ($pages_enabled && is_page()) || ($attachments_enabled && is_attachment()))
				header("Link: <" . twitter_link($post_id) . ">; rel=shortlink");
	}
}

function twitter_friendly_links_admin_scripts() {
	$count_posts = wp_count_posts();
	$total_posts = $count_posts->publish;
	$count_pages = wp_count_posts('page');
	$total_pages = $count_pages->publish;
	$count_attachments = wp_count_posts('attachment');
	$total_attachments = $count_attachments->inherit;

	$plugin_url = trailingslashit(get_bloginfo('wpurl')).PLUGINDIR.'/'. dirname(plugin_basename(__FILE__));
	wp_enqueue_script('twitterfriendly_navsettings', $plugin_url.'/js/nav_settings.js', array('jquery'));
	wp_localize_script('twitterfriendly_navsettings', 'TwitterFriendly', array(
		"plugin_url" => $plugin_url,
		"total_posts" => $total_posts,
		"total_pages" => $total_pages,
		"total_attachments" => $total_attachments,		
	));
}

function twitter_friendly_links_menu() {
	$twitter_friendly_admin = add_options_page('Twitter Friendly Links', 'Twitter Friendly Links', 8, __FILE__, 'twitter_friendly_links_options');
	add_action("admin_print_scripts-$twitter_friendly_admin", "twitter_friendly_links_admin_scripts");
}

function twitter_friendly_links_options() {
	$options = get_option("twitter_friendly_links");
	
	if (isset($_GET["tfl_hide"]))
	{
		$options["tfl_core_notice"] = 0;
		update_option("twitter_friendly_links", $options);
	}
	
	if (isset($_POST["twitter-friendly-links-submit"]))
	{
		$options["style"] = $_POST["style"];
		$options["format"] = $_POST["format"];
		$options["redirect"] = $_POST["redirect"];
		$options["posts_enabled"] = $_POST["posts_enabled"];
		$options["pages_enabled"] = $_POST["pages_enabled"];
		$options["attachments_enabled"] = $_POST["attachments_enabled"];
		
		$options["twitter_tools_fix"] = $_POST["twitter_tools_fix"];
		$options["askapache_google_404"] = $_POST["askapache_google_404"];
		$options["tweet_this_fix"] = $_POST["tweet_this_fix"];
		$options["sociable_fix"] = $_POST["sociable_fix"];
		
		$options["ga_tracking"] = $_POST["ga_tracking"];
		
		$options["html_shortlink_rel"] = $_POST["html_shortlink_rel"];
		$options["http_shortlink_rel"] = $_POST["http_shortlink_rel"];
		$options["rel_canonical"] = $_POST["rel_canonical"];
		
		update_option("twitter_friendly_links", $options);
	}
	
	$style = $options["style"];
	$format = $options["format"];
	$redirect = $options["redirect"];
	$posts_enabled = $options["posts_enabled"];
	$pages_enabled = $options["pages_enabled"];
	$attachments_enabled = $options["attachments_enabled"];
	
	$twitter_tools_fix = $options["twitter_tools_fix"];
	$askapache_google_404 = $options["askapache_google_404"];
	$tweet_this_fix = $options["tweet_this_fix"];
	$sociable_fix = $options["sociable_fix"];
	
	$ga_tracking = $options["ga_tracking"];
	
	$html_shortlink_rel = $options["html_shortlink_rel"];
	$http_shortlink_rel = $options["http_shortlink_rel"];
	$rel_canonical = $options["rel_canonical"];
	
	$selected[$redirect] = " selected=\"selected\"";
	$selected[$format] = " selected=\"selected\"";
	
	if ($format == "generic") $link_preview = "123";
	elseif ($format = "base32") $link_preview = "7e1";
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
			<th scope="row"><label for="style">Shortlinks prefix</label></th>
			<td>
				<input type="text"  value="<?php echo$style; ?>" id="style" name="style"/>
				<span class="setting-description"><?php echo get_option("home"); ?>/<strong>prefix</strong><?php echo $link_preview; ?> (blank by default)</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="format">Format</label></th>
			<td>
				<select name="format" id="format">
					<option value="generic"<?php echo $selected["generic"]; ?>>Generic (numbers only)</option>
					<option value="base32"<?php echo $selected["base32"]; ?>>Alphanumeric (base32 encoded)</option>
				</select>
				<span class="setting-description">Generic by default</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label>Enable shortlinks for</label></th>
			<td>
				<input type="checkbox" value="checked" <?php echo $posts_enabled; ?> id="posts_enabled" name="posts_enabled"/>
				<span class="setting-description">Posts<br /></span>

				<input type="checkbox" value="checked" <?php echo $pages_enabled; ?> id="pages_enabled" name="pages_enabled"/>
				<span class="setting-description">Pages<br /></span>
				
				<input type="checkbox" value="checked" <?php echo $attachments_enabled; ?> id="attachments_enabled" name="attachments_enabled"/>
				<span class="setting-description">Attachments</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="redirect">Redirection type</label></th>
			<td>
				<select name="redirect" id="redirect">
					<option value="302"<?php echo $selected[302]; ?>>302 Found (Temporary redirect)</option>
					<option value="301"<?php echo $selected[301]; ?>>301 Moved Permanently</option>
				</select>
				<span class="setting-description">302 by default</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="style">Tag destination links</label></th>
			<td>
				<input type="text" style="min-width:25em;" value="<?php echo $ga_tracking; ?>" id="ga_tracking" name="ga_tracking" /><br />
				<span class="setting-description">You can tag your destination links for Google Analytics Tracking. For example: <code>utm_source=twitter&amp;utm_medium=shortlink&amp;utm_campaign=shortlinks</code>. You can generate a tagged link using the <a href="https://www.google.com/support/googleanalytics/bin/answer.py?hl=en&answer=55578">Google Analytics URL Builder</a>. Do not include the website address in the input box above. Start from utm_source. This string will be appended to the destination address. Leave blank to disable.</span>
			</td>
		</tr>
	</tbody>
	</table>

<h3>Link Relations</h3>
<p>Search engines and URL shorteners. Bunch of thoughts on linking relations, so here are the main options.</p>

	<table class="form-table" style="margin-bottom:10px;">
	<tbody>
		<tr valign="top">
			<th scope="row"><label for="html_shortlink_rel">HTML Shortlink relation</label></th>
			<td>
				<input type="checkbox" value="checked" <?php echo $html_shortlink_rel; ?> id="html_shortlink_rel" name="html_shortlink_rel" />
				<span class="setting-description">Adds a link rel=&quot;shortlink&quot; to the head section of your posts and (if enabled) pages. <a href="http://purl.org/net/shortlink">Specification</a>.</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="http_shortlink_rel">HTTP Shortlink relation</label></th>
			<td>
				<input type="checkbox" value="checked" <?php echo $http_shortlink_rel; ?> id="http_shortlink_rel" name="http_shortlink_rel" />
				<span class="setting-description">Passes a link rel=&quot;shortlink&quot; along with the HTTP responses of your posts and (if enabled) pages. <a href="http://purl.org/net/shortlink">Specification</a>.</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="rel_canonical">Canonical relation</label></th>
			<td>
				<input type="checkbox" value="checked" <?php echo $rel_canonical; ?> id="rel_canonical" name="rel_canonical" />
				<span class="setting-description">Adds a link rel=&quot;canonical&quot; href=&quot;permalink&quot; to your HTML head in posts and (if enabled) pages.</span>
			</td>
		</tr>
	</tbody>
	</table>
	
<h3>Compatibility</h3>
<p>If you use any of the plugins listed below and you are experiencing problems with short linking, enable the fixes. If there's no fix for a plugin you're using you may request it on the <a href="http://kovshenin.com/wordpress/plugins/twitter-friendly-links/">Twitter Friendly Links</a> page.</p>
	<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row"><label for="twitter_tools_fix">Twitter Tools</label></th>
			<td>
				<input type="checkbox" value="checked" <?php echo $twitter_tools_fix; ?> id="twitter_tools_fix" name="twitter_tools_fix"/>
				<span class="setting-description">Linking fix for the <a href="http://wordpress.org/extend/plugins/twitter-tools/">Twitter Tools</a> plugin. Described <a href="http://kovshenin.com/archives/compatibility-twitter-tools-twitter-friendly-links/">here</a></span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="tweet_this_fix">Tweet-This/SexyBookmarks</label></th>
			<td>
				<input type="checkbox" value="checked" <?php echo $tweet_this_fix; ?> id="tweet_this_fix" name="tweet_this_fix"/>
				<span class="setting-description">Linking fix for the <a href="http://wordpress.org/extend/plugins/tweet-this/">Tweet This</a> and <a href="http://sexybookmarks.net">SexyBookmarks</a> plugins.</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="askapache_google_404">AskApache Google 404</label></th>
			<td>
				<input type="checkbox" value="checked" <?php echo $askapache_google_404; ?> id="askapache_google_404" name="askapache_google_404"/>
				<span class="setting-description">Fix for the <a href="http://wordpress.org/extend/plugins/askapache-google-404/">AskApache Google 404</a> plugin.</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="sociable">Sociable</label></th>
			<td>
				<input type="checkbox" value="checked" <?php echo $sociable_fix; ?> id="sociable_fix" name="sociable_fix"/>
				<span class="setting-description">Fix for the <a href="http://wordpress.org/extend/plugins/sociable/">Sociable</a> plugin.</span>
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
	$count_attachments = wp_count_posts('attachment');
	$total_attachments = $count_attachments->inherit;
?>
<div class="tablenav">
	<div class="alignleft actions">
<?php
	if ($posts_enabled == "checked")
		echo '<a href="#" id="twitter_links_posts" class="twitter_links_postpage" style="font-weight: bold;">Posts</a> / ';
	else
		echo '<a href="#" id="twitter_links_posts"></a>Posts (disabled) / ';
	 
	if ($pages_enabled == "checked")
		echo '<a href="#" id="twitter_links_pages" class="twitter_links_postpage">Pages</a> / ';
	else
		echo '<a href="#" id="twitter_links_pages"></a>Pages (disabled) / ';
		
	if ($attachments_enabled == "checked")
		echo '<a href="#" id="twitter_links_attachments" class="twitter_links_postpage">Attachments</a>';
	else
		echo '<a href="#" id="twitter_links_attachments"></a>Attachments (disabled)';
?>
	
	</div>
	
	<div class="tablenav-pages"><span class="displaying-num">Displaying <span id="twitter_links_displaying">1-15</span> of <span id="twitter_links_displaying_total"><?php echo $total_posts; ?></span></span>
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
	<span class="twitter_friendly attachments-numbers" style="display: none">
<?php
	$pages = $total_attachments / 15;
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
<?php
	if ($posts_enabled != "checked")
	{
?>
			<tr class="alternate">
				<td style="text-align: right"></td>
				<td>Posts are disabled</td>
				<td></td>
			</tr>
<?php
	}
	else
	{

		$query = new WP_Query("posts_per_page=15");
		$i = 0;
		while ($query->have_posts())
		{
			$query->the_post();
			$friendly_link = twitter_link($query->post->ID);
			$i++;
?>
			<tr class="alternate">
				<td style="text-align: right"><?php echo $i; ?>.</td>
				<td><a href="<?php the_permalink(); ?>"><? the_title();?></a><br /></td>
				<td><a href="<?php echo $friendly_link; ?>"><?php echo $friendly_link; ?></a></td>
			</tr>
<?php
		}
	}
?>
	</tbody>
</table>

<?php
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
		
	if ($options["format"] == "base32")
		$friendly_link = get_option("home") . "/" . $style . tfl_base32($post_id);
	else	
		$friendly_link = get_option("home") . "/" . $style . $post_id;
	
	return $friendly_link;
}

function permalink_to_twitter_link($permalink)
{
	$post_id = url_to_postid($permalink);
	return twitter_link($post_id);
}

function tfl_tweet_this_fix($content) {
	global $post;
	$twitter_link = twitter_link();
	$content = preg_replace("/href=\\\"http:\/\/twitter.com\/home\/?\?status=([^\\\"]+)\\\"/", "href=\"http://twitter.com/home/?status=" . urlencode($post->post_title . " " . $twitter_link) . "\"", $content);
	return $content;
}

function tfl_sociable_fix($content) {
	global $post;
	$twitter_link = twitter_link();
	$content = preg_replace("/href=\\\"http:\/\/twitter.com\/home\/?\?status=([^\\\"]+)\\\"/", "href=\"http://twitter.com/home/?status=" . urlencode($post->post_title . " " . $twitter_link) . "\"", $content);
	return $content;
}

function tfl_admin_notices() {
	echo "<div id='tfl-warning' class='updated fade'><p>You have to <a href='options-permalink.php'>change your permalink structure</a> for <strong>Twitter Friendly Links</strong> to work (don't use default).</p></div>";
}

function tfl_admin_notices_core() {
	echo "<div id='tfl-warning' class='updated fade'><p>Hey, this version of <strong>Twitter Friendly Links</strong> had some core changes. Make sure you double check <a href='options-general.php?page=twitter-friendly-links/twitter-friendly-links.php'>your settings</a>, which may have not been saved from your previous version, okay? Sorry for any inconvenience caused. <a href='options-general.php?page=twitter-friendly-links/twitter-friendly-links.php&tfl_hide'>Hide this message</a>.</p></div>";
}

register_activation_hook(__FILE__, 'tfl_activate');
register_deactivation_hook(__FILE__, 'tfl_deactivate');

function tfl_deactivate() {
	//delete_option("twitter_friendly_links"); // Use this only for debug
	return true;
}

function tfl_activate() {
	$options = get_option("twitter_friendly_links");
	
	// Default plugin options
	$defaults = array(
		"style" => "",		// default style is example.com/123
		"format" => "generic", // default format is generic (numbers only)
		"redirect" => 302,		// temporary redirect by default
		"posts_enabled" => "checked", // posts enabled by default
		"pages_enabled" => "",	// pages disabled by default
		"attachments_enabled" => "", // attachments disabled by default
		
		"twitter_tools_fix" => "", // disabled by deafult
		"askapache_google_404" => "",
		"tweet_this_fix" => "",
		"sociable_fix" => "",
		
		"ga_tracking" => "",
		
		"html_shortlink_rel" => "",
		"http_shortlink_rel" => "",
		"rel_canonical" => "",
		
		"tfl_core_notice" => 0,
	);
	
	foreach($defaults as $key => $default_value)
		$options[$key] = (!isset($options[$key])) ? $default_value : $options[$key];
		
	update_option("twitter_friendly_links", $options);
	return true;
}

function tfl_base32($str, $reverse = false) {
	if (!$reverse)
	{
		$post_id = intval($str);
		return base_convert($post_id + 10000, 10, 36);
	}
	else
	{
		$post_id = base_convert($str, 36, 10) - 10000;
		return $post_id;
	}
}
?>
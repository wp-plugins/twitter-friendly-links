<?php
/*
Plugin Name: Twitter Friendly Links
Plugin URI: http://kovshenin.com/wordpress/plugins/twitter-friendly-links/
Description: Your very own TinyURL within your OWN domain! If you DO promote your blog posts in Twitter, then you MUST make your links look cool!
Author: Konstantin Kovshenin
Version: 0.4.2
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
global $tfl_version;
$tfl_version = 42;

class TwitterFriendlyLinks {
	var $settings = array();
	var $defaultsettings = array();

	function TwitterFriendlyLinks() {
		global $tfl_version;
		$this->defaultsettings = array(
			"version" => $tfl_version,
			"style" => "",		// default style is example.com/123
			"format" => "generic", // default format is generic (numbers only)
			"redirect" => 302,		// temporary redirect by default
			"posts_enabled" => true, // posts enabled by default
			"pages_enabled" => false,	// pages disabled by default
			"attachments_enabled" => false, // attachments disabled by default
			
			"twitter_tools_fix" => false, // disabled by deafult
			"askapache_google_404" => false,
			"tweet_this_fix" => false,
			"sociable_fix" => false,
			
			"ga_tracking" => "",
			
			"html_shortlink_rel" => false,
			"http_shortlink_rel" => false,
			"rel_canonical" => false,
			
			"tfl_core_notice" => 0,
		);

		// Setup the settings by using the default as a base and then adding in any changed values
		// This allows settings arrays from old versions to be used even though they are missing values
		$usersettings = (array) get_option("twitter_friendly_links");
		
		if (!isset($usersettings["version"]))
			$usersettings["version"] = 0;
		
		$this->settings = $this->defaultsettings;
		if ( $usersettings !== $this->defaultsettings ) {
			foreach ( (array) $usersettings as $key1 => $value1 ) {
				if ( is_array($value1) ) {
					foreach ( $value1 as $key2 => $value2 ) {
						$this->settings[$key1][$key2] = $value2;
					}
				} else {
					$this->settings[$key1] = $value1;
				}
			}
		}
		
		// Register general hooks
		add_action('template_redirect', array(&$this, 'template_redirect'), 9);
		add_action("admin_menu", array(&$this, "admin_menu"));
		add_action("admin_menu", array(&$this, "admin_menu_box"));
		
		// Filters and Actions
		if ($this->settings["twitter_tools_fix"])
			add_filter("tweet_blog_post_url", "permalink_to_twitter_link", 10, 1);
		if ($this->settings["tweet_this_fix"])
			add_filter("the_content", "tfl_tweet_this_fix", 10);
		if ($this->settings["sociable_fix"])
			add_filter("sociable_link", "tfl_sociable_fix");
			
		if ($this->settings["html_shortlink_rel"] || $this->settings["rel_canonical"])
			add_action("wp_head", array(&$this, "wp_head"));
			
		// Notify the administrator if permalinks are switched off
		$permalink_structure = (isset($_POST['permalink_structure'])) ? $_POST['permalink_structure'] : get_option("permalink_structure");
		if ($permalink_structure == "")
			add_action("admin_notices", array(&$this, "admin_notices"));
	}
	
	function wp_head()
	{
		// Mainly for relations
		if (($this->settings["posts_enabled"] && is_single()) || ($this->settings["pages_enabled"] && is_page()) || ($this->settings["attachments_enabled"] && is_attachment()))
		{
			global $post;
			$post_id = $post->ID;
		
			if ($this->settings["html_shortlink_rel"])
			{
				$short_url = twitter_link($post_id);
				echo "<link rel=\"shortlink\" href=\"$short_url\" />\n";
			}
			if ($this->settings["rel_canonical"])
			{
				$permalink = get_permalink($post_id);
				echo "<link rel=\"canonical\" href=\"$permalink\" />\n";
			}
		}
	}

	function template_redirect() {
		$style = $this->settings["style"];
		$format = $this->settings["format"];
		$redirect = $this->settings["redirect"];

		$ga_tracking = (strlen($this->settings["ga_tracking"]) > 1) ? "?".$this->settings["ga_tracking"] : "";
		
		$uri = $_SERVER["REQUEST_URI"];
		$home = get_option("home");
		$home = str_replace("http://".$_SERVER["SERVER_NAME"], "", $home);
		$uri = strtolower(str_replace($home, "", $uri));
		
		if (ereg("^/{$style}([0-9a-z]+)/?$", $uri, $regs))
		{
			// Fix for the AskApache Google 404 plugin
			$this->settings["askapache_google_404"] = ($this->settings["askapache_google_404"] == "checked") ? true : false;
			if ($this->settings["askapache_google_404"])
			{
				global $AskApacheGoogle404;
				remove_action("template_redirect", array($AskApacheGoogle404, 'template_redirect'));
			}

			$post_id = $regs[1];
			if ($format == "base32")
				$post_id = tfl_base32($post_id, true);

			if (is_numeric($post_id))
			{
				$posts = new WP_Query("p=$post_id&post_type=any");
				if ($posts->have_posts())
				{
					$posts->the_post();
					$post = $posts->post;
					
					if (!$this->settings["posts_enabled"] && $post->post_type == "post") return;
					if (!$this->settings["pages_enabled"] && $post->post_type == "page") return;
					if (!$this->settings["attachments_enabled"] && $post->post_type == "attachment") return;
					
					if ($redirect == 301)
						header("HTTP/1.1 301 Moved Permanently");
					elseif ($redirect == 302)
						header("HTTP/1.1 302 Found");
		
					header("Location: ".get_permalink().$ga_tracking);
				}
			}
		}

		global $post;

		// Link relations
		if ($this->settings["http_shortlink_rel"])
			if (($this->settings["posts_enabled"] && is_single()) || ($this->settings["pages_enabled"] && is_page()) || ($this->settings["attachments_enabled"] && is_attachment()))
				header("Link: <" . twitter_link($post_id) . ">; rel=shortlink");

	}

	function admin_scripts() {
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

	function admin_menu() {
		$twitter_friendly_admin = add_options_page('Twitter Friendly Links', 'Twitter Friendly Links', 8, __FILE__, array(&$this, 'options'));
		add_action("admin_print_scripts-$twitter_friendly_admin", array(&$this, "admin_scripts"));
	}

	function options() {
		$plugin_dir = "../".PLUGINDIR.'/'. dirname( plugin_basename(__FILE__) );
		require_once($plugin_dir . "/options.php");
	}

	function admin_menu_box() {
		if (function_exists("add_meta_box")) {
			add_meta_box("twitter_friendly_id", "Twitter Stuff", array(&$this, "admin_menu_inner_box"), "post", "side");
			if ($this->settings["pages_enabled"]) add_meta_box("twitter_friendly_id", "Twitter Stuff", "twitter_friendly_links_inner_box", "page", "side");
		}
		else {
			add_action("dbx_post_advanced", array(&$this, "admin_menu_old_box"));
			if ($this->settings["pages_enabled"]) add_action("dbx_page_advanced", array(&$this, "admin_menu_old_box"));
		}
	}

	function admin_menu_old_box() {
		echo '<div class="dbx-b-ox-wrapper">' . "\n";
		echo '<fieldset id="twitter_friendly_fieldsetid" class="dbx-box">' . "\n";
		echo '<div class="dbx-h-andle-wrapper"><h3 class="dbx-handle">' . 
		      "Twitter Friendly" . "</h3></div>";   
		echo '<div class="dbx-c-ontent-wrapper"><div class="dbx-content">';
		$this->admin_menu_inner_box();
		echo "</div></div></fieldset></div>\n";
	}

	function admin_menu_inner_box($post) {
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
	
	function admin_notices() {
		echo "<div id='tfl-warning' class='updated fade'><p>You have to <a href='options-permalink.php'>change your permalink structure</a> for <strong>Twitter Friendly Links</strong> to work (don't use default).</p></div>";
	}

}

add_action("init", "TwitterFriendlyLinks"); function TwitterFriendlyLinks() { global $TwitterFriendlyLinks; $TwitterFriendlyLinks = new TwitterFriendlyLinks(); }
register_activation_hook(__FILE__, 'tfl_activate');
register_deactivation_hook(__FILE__, 'tfl_deactivate');

function tfl_activate() {
	global $tfl_version;
	$settings = (array) get_option("twitter_friendly_links");
	
	if ($settings["version"] < $tfl_version) 
	{ // Upgrade ?
	
		if (isset($settings["format"]))
		{
			// Fix for the Twitter Tools & Tweet This plugins
			$settings["twitter_tools_fix"] = ($settings["twitter_tools_fix"] == "checked") ? true : false;
			$settings["tweet_this_fix"] = ($settings["tweet_this_fix"] == "checked") ? true : false;
			$settings["sociable_fix"] = ($settings["sociable_fix"] == "checked") ? true : false;
	
			// Link relations options
			$settings["html_shortlink_rel"] = ($settings["html_shortlink_rel"] == "checked") ? true : false;
			$settings["rel_canonical"] = ($settings["rel_canonical"] == "checked") ? true : false;
			$settings["http_shortlink_rel"] = ($settings["http_shortlink_rel"] == "checked") ? true : false;
		
			// Pages posts and attachments
			$settings["posts_enabled"] = ($settings["posts_enabled"] == "checked") ? true : false;
			$settings["pages_enabled"] = ($settings["pages_enabled"] == "checked") ? true : false;
			$settings["attachments_enabled"] = ($settings["attachments_enabled"] == "checked") ? true : false;
		}
		
		$settings["version"] = $tfl_version;

		// Other options
		
		// Update the settings in the database
		update_option("twitter_friendly_links", $settings);
	}
	return false;
}

function tfl_deactivate() {
	//delete_option("twitter_friendly_links"); // Use this only for debug
	return true;
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

function tfl_admin_notices_core() {
	echo "<div id='tfl-warning' class='updated fade'><p>Hey, this version of <strong>Twitter Friendly Links</strong> had some core changes. Make sure you double check <a href='options-general.php?page=twitter-friendly-links/twitter-friendly-links.php'>your settings</a>, which may have not been saved from your previous version, okay? Sorry for any inconvenience caused. <a href='options-general.php?page=twitter-friendly-links/twitter-friendly-links.php&tfl_hide'>Hide this message</a>.</p></div>";
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

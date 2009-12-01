<?php
/*
Plugin Name: Twitter Friendly Links
Plugin URI: http://kovshenin.com/wordpress/plugins/twitter-friendly-links/
Description: Your very own TinyURL within your OWN domain! If you DO promote your blog posts in Twitter, then you MUST make your links look cool!
Author: Konstantin Kovshenin
Version: 0.4.5
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

class TwitterFriendlyLinks {
	var $settings = array();
	var $defaultsettings = array();
	var $notices = array();

	function TwitterFriendlyLinks() {
		global $tfl_version;
		$this->defaultsettings = array(
			"version" => 45,
			"style" => "",		// default style is example.com/123
			"format" => "generic", // default format is generic (numbers only)
			"redirect" => 302,		// temporary redirect by default
			"posts_enabled" => true, // posts enabled by default
			"pages_enabled" => false,	// pages disabled by default
			"attachments_enabled" => false, // attachments disabled by default
			
			"shortlink_base" => get_option("home"),
			
			"twitter_tools_fix" => false, // disabled by deafult
			"askapache_google_404" => false,
			"tweet_this_fix" => false,
			"sociable_fix" => false,
			
			"ga_tracking" => "",
			
			"html_shortlink_rel" => false,
			"http_shortlink_rel" => false,
			"rel_canonical" => false,
			
			"tfl_core_notice" => 0,
			
			"cache_in_htaccess" => false,
			"rewrite_rules" => array(),
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
		
		
		if ($this->settings["cache_in_htaccess"])
		{
			add_action('generate_rewrite_rules', array(&$this, 'generate_rewrite_rules'));
			add_filter('mod_rewrite_rules', array(&$this, 'mod_rewrite_rules'));
			add_action('publish_post', array(&$this, 'publish_post'));
		}
		
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
			$this->notices[] = "You have to <a href='options-permalink.php'>change your permalink structure</a> for <strong>Twitter Friendly Links</strong> to work (don't use default).";
			
		if (!$this->settings["rewrite_rules_written"])
		{
			$this->notices[] = "Twitter Friendly Links has to rewrite your .htaccess file. Head over to the <a href='options-permalink.php'>Permalinks</a> section and click save.";
			add_filter('mod_rewrite_rules', array(&$this, 'mod_rewrite_rules_fake'));
		}

		add_action("admin_notices", array(&$this, "admin_notices"));
	}
	
	function generate_rewrite_rules()
	{
		global $wp_rewrite;
		$non_wp_rules = array();
		
		if ($this->settings["posts_enabled"] && $this->settings["pages_enabled"])
			$query = new WP_Query("posts_per_page=-1&post_type=any&post_status=publish");
		elseif ($this->settings["posts_enabled"])
			$query = new WP_Query("posts_per_page=-1&post_type=post&post_status=publish");
		elseif ($this->settings["pages_enabled"])
			$query = new WP_Query("posts_per_page=-1&post_type=page&post_status=publish");
		else
			return;
			
		$ga_tracking = (strlen($this->settings["ga_tracking"]) > 1) ? "?".$this->settings["ga_tracking"] : "";
			
		while ($query->have_posts())
		{
			$query->the_post();
			$post_id = $query->post->ID;
			$permalink = get_permalink();
			
			$non_wp_rules[$post_id . '/?$tfl_rewrite'] = $permalink.$ga_tracking;
		}
		
		$wp_rewrite->non_wp_rules = $non_wp_rules + $wp_rewrite->non_wp_rules;
	}
	
	function mod_rewrite_rules($rules)
	{
		$rules = preg_replace("/^(RewriteRule \^[0-9]+\/\?\\$)tfl_rewrite (\/)(.*) (\[QSA,L\])$/im", "\\1 \\3 [R=301,L]", $rules);
		return $rules;
	}
	
	function mod_rewrite_rules_fake($rules)
	{
		$this->settings["rewrite_rules_written"] = true;
		$this->save_settings();
		return $rules;
	}
	
	function save_settings()
	{
		update_option("twitter_friendly_links", $this->settings);
		return true;
	}
	
	function publish_post($post_id)
	{
		global $wp_rewrite;
		$wp_rewrite->flush_rules(true);
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
		$home = $this->settings["shortlink_base"];
		//$home = str_replace("http://".$_SERVER["SERVER_NAME"], "", $home);
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
					
					global $wp_query;
					$wp_query->is_404 = false;
					
					wp_redirect(get_permalink().$ga_tracking, $redirect);
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
			if ($this->settings["pages_enabled"]) add_meta_box("twitter_friendly_id", "Twitter Stuff", array(&$this, "admin_menu_inner_box"), "page", "side");
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
	
	function admin_notices()
	{
		$this->notices = array_unique($this->notices);
		foreach($this->notices as $key => $value)
		{
			echo "<div id='tfl-info' class='updated fade'><p>" . $value . "</p></div>";
		}
	}
}

add_action("init", "TwitterFriendlyLinks"); function TwitterFriendlyLinks() { global $TwitterFriendlyLinks; $TwitterFriendlyLinks = new TwitterFriendlyLinks(); }

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
	$home = $options["shortlink_base"];
	
	if ($id == 0)
	{
		global $post;
		$post_id = $post->ID;
	}
	else
		$post_id = $id;
		
	if ($options["format"] == "base32")
		$friendly_link = $home . "/" . $style . tfl_base32($post_id);
	else	
		$friendly_link = $home . "/" . $style . $post_id;
	
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
<?php
/*
Plugin Name: Twitter Friendly Links
Plugin URI: http://kovshenin.com/wordpress/plugins/twitter-friendly-links/
Description: Twitter Friendly Links
Author: Konstantin Kovshenin
Version: 0.1b
Author URI: http://kovshenin.com/

*/

add_action('template_redirect', 'twitter_friendly_links');

function twitter_friendly_links() {
	$options = get_option("twitter_friendly_links");
	if (!isset($options["style"]))
	{
		$options["style"] = "go"; // can change the default v
		update_option("twitter_friendly_links", $options);
	}
	
	$style = $options["style"];
	
	$uri = $_SERVER["REQUEST_URI"];
	$siteurl = get_option("siteurl");
	$siteurl = str_replace("http://".$_SERVER["SERVER_NAME"], "", $siteurl);
	$uri = str_replace($siteurl, "", $uri);
	if (ereg("^/{$style}([0-9]+)$", $uri, $regs))
	{
		$post_id = $regs[1];
		$post = new WP_Query("p=$post_id");
		if ($post->have_posts())
		{
			$post->the_post();
			header("Location: ".get_permalink());
		}
	}
}
<?php
/*
Plugin Name: Twitter Friendly Links
Plugin URI: http://kovshenin.com/wordpress/plugins/twitter-friendly-links/
Description: Twitter Friendly Links
Author: Konstantin Kovshenin
Version: 0.1
Author URI: http://kovshenin.com/

*/

add_action('template_redirect', 'twitter_friendly_links');
add_action("admin_menu", "twitter_friendly_links_menu");

function twitter_friendly_links() {
	$options = get_option("twitter_friendly_links");
	if (!isset($options["style"]))
	{
		$options["style"] = "go"; // can change the default v
		$options["redirect"] = 302; // Temporary redirect by default
		update_option("twitter_friendly_links", $options);
	}
	
	$style = $options["style"];
	$redirect = $options["redirect"];
	
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
			if ($redirect == 301)
				header("HTTP/1.1 301 Moved Permanently");
			elseif ($redirect == 302)
				header("HTTP/1.1 302 Found");

			header("Location: ".get_permalink());
		}
	}
}

function twitter_friendly_links_menu() {
	add_options_page('Twitter Friendly Links', 'Twitter Friendly Links', 8, __FILE__, 'twitter_friendly_links_options');
}

function twitter_friendly_links_options() {
	$options = get_option("twitter_friendly_links");
	if (!isset($options["style"]))
	{
		$options["style"] = "go";
		$options["redirect"] = 302;
	}
	
	if (isset($_POST["twitter-friendly-links-submit"]))
	{
		$options["style"] = $_POST["style"];
		$options["redirect"] = $_POST["redirect"];
		update_option("twitter_friendly_links", $options);
	}
	
	$style = $options["style"];
	$redirect = $options["redirect"];
	
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
				<input type="text" class="regular-text code" value="<?=$style;?>" id="style" name="style"/>
				<span class="setting-description"><?= get_option("siteurl"); ?>/<strong><?=$style;?></strong>123</span>
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
	</tbody>
	</table>
	<p class="submit">
		<input type="submit" value="Save Changes" class="button-primary" name="Submit"/>
	</p>
</form>
<h3>Links</h3>
<table class="widefat fixed">
	<thead>
		<tr>
			<th class="manage-column column-name" id="title" scope="col">Title</th>
			<th class="manage-column column-name" id="twitterlink" scope="col" style="width: 25em;">Friendly Link</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th class="manage-column column-name" id="title" scope="col">Title</th>
			<th class="manage-column column-name" id="twitterlink" scope="col">Friendly Link</th>
		</tr>
	</tfoot>
	<tbody>
<?
	$query = new WP_Query("posts_per_page=-1");
	while ($query->have_posts())
	{
		$query->the_post();
		$friendly_link = get_option("siteurl") . "/" . $style . $query->post->ID;
?>
			<tr class="alternate">
				<td><a href="<? the_permalink(); ?>"><? the_title();?></a><br /></td>
				<td><a href="<?= $friendly_link;?>"><?= $friendly_link; ?></a></td>
			</tr>
<?
	}
?>
	</tbody>
</table>
</div>
<?
}
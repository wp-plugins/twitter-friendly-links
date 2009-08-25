<?php
	if (isset($_POST["twitter-friendly-links-submit"]))
	{
		
		$this->settings["style"] = $_POST["style"];
		$this->settings["format"] = $_POST["format"];
		$this->settings["redirect"] = $_POST["redirect"];
		
		$this->settings["posts_enabled"] = ($_POST["posts_enabled"] == "checked") ? true : false;
		$this->settings["pages_enabled"] = ($_POST["pages_enabled"] == "checked") ? true : false;
		$this->settings["attachments_enabled"] = ($_POST["attachments_enabled"] == "checked") ? true : false;
		
		$this->settings["twitter_tools_fix"] = ($_POST["twitter_tools_fix"] == "checked") ? true : false;
		$this->settings["askapache_google_404"] = ($_POST["askapache_google_404"] == "checked") ? true : false;
		$this->settings["tweet_this_fix"] = ($_POST["tweet_this_fix"] == "checked") ? true : false;
		$this->settings["sociable_fix"] = ($_POST["sociable_fix"] == "checked") ? true : false;
		
		$this->settings["ga_tracking"] = $_POST["ga_tracking"];
		
		$this->settings["html_shortlink_rel"] = ($_POST["html_shortlink_rel"] == "checked") ? true : false;
		$this->settings["http_shortlink_rel"] = ($_POST["http_shortlink_rel"] == "checked") ? true : false;
		$this->settings["rel_canonical"] = ($_POST["rel_canonical"] == "checked") ? true : false;
		
		update_option("twitter_friendly_links", $this->settings);
	}
	
	$style = $this->settings["style"];
	$format = $this->settings["format"];
	$redirect = $this->settings["redirect"];
	$posts_enabled = ($this->settings["posts_enabled"]) ? "checked" : "";
	$pages_enabled = ($this->settings["pages_enabled"]) ? "checked" : "";
	$attachments_enabled = ($this->settings["attachments_enabled"]) ? "checked" : "";
	
	$twitter_tools_fix = ($this->settings["twitter_tools_fix"]) ? "checked" : "";
	$askapache_google_404 = ($this->settings["askapache_google_404"]) ? "checked" : "";
	$tweet_this_fix = ($this->settings["tweet_this_fix"]) ? "checked" : "";
	$sociable_fix = ($this->settings["sociable_fix"]) ? "checked" : "";

	$ga_tracking = $this->settings["ga_tracking"];
	
	$html_shortlink_rel = ($this->settings["html_shortlink_rel"]) ? "checked" : "";
	$http_shortlink_rel = ($this->settings["http_shortlink_rel"]) ? "checked" : "";
	$rel_canonical = ($this->settings["rel_canonical"]) ? "checked" : "";
	
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
<p>I would also like to thank <a href="http://twitter.com/eight7teen">Josh Jones</a> for his great implementation of TFL into his awesome <a href="http://sexybookmarks.net">SexyBookmarks</a> plugin. Well done Josh!</p>
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
			<th scope="row"><label for="tweet_this_fix">Tweet-This</label></th>
			<td>
				<input type="checkbox" value="checked" <?php echo $tweet_this_fix; ?> id="tweet_this_fix" name="tweet_this_fix"/>
				<span class="setting-description">Linking fix for the <a href="http://wordpress.org/extend/plugins/tweet-this/">Tweet This</a> plugin.</span>
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
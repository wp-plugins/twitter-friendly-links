<?php
	include('../../../../wp-config.php');
	
	if (current_user_can('level_10'))
	{
		$status = "";
		
		if (isset($_GET["pages"]))
			$type = "page";
		elseif (isset($_GET["attachments"]))
		{
			$type = "attachment";
			$status = "&post_status=inherit";
		}
		else
			$type = "post";
			
		//$type = isset($_GET["pages"]) ? "page" : "post";
		
		$query = new WP_Query("paged=".$_GET["page"]."&posts_per_page=15&post_type=$type".$status);
		$i = (intval($_GET["page"])-1)*15;
		//echo "</table><pre>" . print_r($query, true) . "</pre>";
		while ($query->have_posts())
		{
			$i++;
			$query->the_post();
			$friendly_link = twitter_link($query->post->ID); ?>
				<tr class="alternate">
					<td style="text-align: right"><?=$i;?>.</td>
					<td><a href="<? the_permalink(); ?>"><? the_title();?></a><br /></td>
					<td><a href="<?= $friendly_link;?>"><?= $friendly_link; ?></a></td>
				</tr><?
		}
	}
?>
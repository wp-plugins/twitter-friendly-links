<?php
	include('../../../../wp-config.php');
	
	if (current_user_can('level_10'))
	{
		$type = isset($_GET["pages"]) ? "page" : "post";
		$query = new WP_Query("paged=".$_GET["page"]."&posts_per_page=15&post_type=$type");
		$i = (intval($_GET["page"])-1)*15;
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
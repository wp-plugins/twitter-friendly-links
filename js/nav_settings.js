TwitterFriendly.viewing = "posts";

jQuery(document).ready(function(){
	jQuery("#twitter_links_posts").click(function(){
		jQuery(".twitter_links_postpage").attr("style", "");
		jQuery(this).attr("style", "font-weight: bold");
		TwitterFriendly.viewing = "posts";
		jQuery(".twitter_friendly.pages-numbers").hide();
		jQuery(".twitter_friendly.posts-numbers").show();
		jQuery(".twitter_friendly.page-numbers:first").click();
		jQuery("#twitter_links_displaying_total").html(TwitterFriendly.total_posts);
		return false;
	});
	
	jQuery("#twitter_links_pages").click(function(){
		jQuery(".twitter_links_postpage").attr("style", "");
		jQuery(this).attr("style", "font-weight: bold");
		TwitterFriendly.viewing = "pages";
		jQuery(".twitter_friendly.posts-numbers").hide();
		jQuery(".twitter_friendly.pages-numbers").show();
		jQuery(".twitter_friendly.page-numbers:first").click();
		jQuery("#twitter_links_displaying_total").html(TwitterFriendly.total_pages);
		return false;
	});
	
	jQuery(".twitter_friendly.first").click(function(){
		jQuery(".twitter_friendly.page-numbers:first").click();
		return false;
	});
	
	jQuery(".twitter_friendly.last").click(function(){
		jQuery(".twitter_friendly.page-numbers:last").click();
		return false;
	});
		
	jQuery(".twitter_friendly.page-numbers").click(function(){
		var page = this.innerHTML;
		jQuery(".twitter_friendly.page-numbers").removeClass("current");		
		jQuery(this).addClass("current");
		
		jQuery(".twitter_friendly.page-numbers").hide();
		
		jQuery(this).show();
		jQuery(".twitter_friendly.page-numbers.post" + (parseInt(page)+1) ).show();
		jQuery(".twitter_friendly.page-numbers.post" + (parseInt(page)+2) ).show();
		jQuery(".twitter_friendly.page-numbers.post" + (parseInt(page)-1) ).show();
		jQuery(".twitter_friendly.page-numbers.post" + (parseInt(page)-2) ).show();
		
		if (page < 5)
		{
			jQuery(".twitter_friendly.page-numbers.firstpages").show();
		}
				
		jQuery("#twitter_links_table_body").fadeTo("fast", 0.01, function(){
			jQuery(this).load(TwitterFriendly.plugin_url + "/ajax/nav.php?" + TwitterFriendly.viewing + "&page=" + page , null, function(){
				jQuery("#twitter_links_displaying").html(((page-1)*15+1) + "-" + (page*15));
				jQuery(this).fadeTo("slow", 1);
			});
		});
		return false;
	});
});
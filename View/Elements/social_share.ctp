<div class="social-share">

	<div class="social-share-fb">

<!--
		<iframe src="//www.facebook.com/plugins/like.php?href=<?php echo urlencode('http://'.$_SERVER['HTTP_HOST'].$this->here);  ?>&amp;width&amp;height=21&amp;colorscheme=light&amp;layout=button_count&amp;action=like&amp;show_faces=true&amp;send=true&amp;appId=<?php echo FACEBOOK_APP_ID; ?>" scrolling="no" frameborder="0" style="border:none; overflow:hidden; height:21px;" allowTransparency="true"></iframe>
-->
		<div id="fb-root"></div>
		<script>(function(d, s, id) {
		  var js, fjs = d.getElementsByTagName(s)[0];
		  if (d.getElementById(id)) return;
		  js = d.createElement(s); js.id = id;
		  js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1&appId=<?php echo FACEBOOK_APP_ID; ?>";
		  fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));</script>

		<div class="fb-like" data-colorscheme="light" data-layout="button_count" data-action="like" data-show-faces="false" data-send="false"></div>

	</div>

	<div class="social-share-tw">

		<a href="https://twitter.com/share" class="twitter-share-button">Tweet</a>
		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>

	</div>
	
	<div class="social-share-gp">

		<div class="g-plus" data-action="share" data-annotation="bubble"></div>
		<script type="text/javascript">
		  (function() {
		    var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
		    po.src = 'https://apis.google.com/js/plusone.js';
		    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
		  })();
		</script>

	</div>

</div>
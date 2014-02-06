<!doctype html>
<head>
</head>

<body>

<!-- 
<style type="text/css">
.content_column2_2 { display: none !important; }
.articleContent { width:980px; margin-left:0px; clear:both !important;}
.article_date {margin-left:0px !important;}
div.zoomMe {margin-left: 0px !important;}
li { float:left !important; }
div.articleLeftContainer,div.contentWidget,div.boxwidgetInner,ul.toplinks,p.introduction { width:980px !important; }
.likegoogle { width:70px !important; }
object { margin: 0 !important; }
div.content_column2_1, div.content_column2_1_row { width:978px !important; }
</style>



<div id="inauguration_map" style="width:980px; height:545px; margin:0 0 10px 0; padding:0">
	Loading...</div>

<script type="text/javascript" src="http://www.voanews.com/MediaAssets2/projects/fidget/inauguration_map_0.12.js"></script>
<div class="twitter" style="height:720px; width:980px; margin:0; padding:0;background:url('http://www.voanews.com/MediaAssets2/english/2013_01/capitol-wide.jpg') no-repeat;">
	<div style="width: 350px; height: 330px;float:right;margin:40px 50px 0 0">
		<a class="twitter-hashtag-button" data-lang="en" data-related="voa_news" data-size="large" href="https://twitter.com/intent/tweet?button_hashtag=2obama">Tweet #2obama</a> <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script><br />
		<strong>Put yourself in the crowd! Click the button above to tweet #2obama with your hopes for President Obama&#39;s second term. Your message will display here. </strong>
	</div>
</div>
<span style="font-size:x-small">Tweets sent to #2obama will be approved and then displayed on this image. There may be a delay of several minutes, so take a look at what others have said in the meantime.</span>
	</div>
</div>

-->
 
<script type="text/javascript" src="jquery.min.js"></script>
<script type="text/javascript" src="jquery.cookie.js"></script>
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>
<script type="text/javascript" src="livemap.js"></script>

<p>Use the interactive map to experience 2014 Sochi olympics day along with our reporters and attendees. See the sights and sounds that they&#39;re encountering during the games, and meet some of the people who have come to Sochi to take part.</p>

<div id="sochi_map" style="width:980px; height:545px">Loading...</div>

<div id="admin_panel"></div>
<button onclick="sochi.try_login()">try login</button>

<script type="text/javascript">
var sochi = new live_map({

	// make sure project = variable name instantiating this
	project: "sochi",
	div_map: "sochi_map",
	lat: 38.892302, 
	lng:-77.026391,
	
	json_url: "http://localhost/livemap/edit.php?project=inauguration&action=list&",
	edit_url: "http://localhost/livemap/"
});
sochi.init();

// removed: div_admin and name

function livemap_object_static(data) {
	sochi.actual_sync(sochi, data);
}

</script>


</body>

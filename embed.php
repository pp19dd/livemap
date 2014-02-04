<?php

// =======================================================================
// url samples
// =======================================================================
// twitter: https://twitter.com/pp19dd/status/289400502574276608
// instagram: http://instagram.com/p/UEPno6Fhq4/
// youtube: http://www.youtube.com/watch?v=vdX_OBUeHb4
// soundcloud: https://soundcloud.com/voa-urdu/har-dum-rawan-hai-zindagi-2
// =======================================================================

// https://api.twitter.com/1/statuses/oembed.json?url=https://twitter.com/pp19dd/status/289400502574276608
// http://api.instagram.com/oembed?url=http://instagram.com/p/UEPno6Fhq4/
// http://www.youtube.com/oembed?format=json&url=http://www.youtube.com/watch?v=vdX_OBUeHb4
// http://soundcloud.com/oembed/?format=json&url=https://soundcloud.com/voa-urdu/har-dum-rawan-hai-zindagi-2

function find_embed( $url ) {
	
	$filename = "data/" . md5($url) . ".json";
	
	if( stripos( $url, "twitter.com" ) != false ) {
		
		if( file_exists( $filename ) ) {
			$text = file_get_contents( $filename );
		} else {
			$text = file_get_contents( "https://api.twitter.com/1/statuses/oembed.json?url=" . urlencode($url) );
			file_put_contents( $filename, $text );
		}
		$data = json_decode( $text );
		$code = $data->html;
		$provider = "twitter";

	} elseif( stripos( $url, "instagram.com" ) != false ) {
	
		if( file_exists( $filename ) ) {
			$text = file_get_contents( $filename );
		} else {
			$text = file_get_contents( "http://api.instagram.com/oembed?url=" . urlencode($url) );
			file_put_contents( $filename, $text );
		}
		$data = json_decode( $text );
		$code = "<div style='width:300px'><img width='300' height='300' src='" . $data->url . "' /><br/>" . $data->title . " by " . $data->author_name . "</div>";
		$provider = "instagram";

	} elseif( stripos( $url, "youtube.com" ) != false ) {
	
		if( file_exists( $filename ) ) {
			$text = file_get_contents( $filename );
		} else {
			$text = file_get_contents( "http://www.youtube.com/oembed?format=json&url=" . urlencode($url) );
			file_put_contents( $filename, $text );
		}
		$data = json_decode( $text );
		$code = $data->html;
		$provider = "youtube";

	} elseif( stripos( $url, "soundcloud.com" ) != false ) {
	
		if( file_exists( $filename ) ) {
			$text = file_get_contents( $filename );
		} else {
			$text = file_get_contents( "http://soundcloud.com/oembed/?format=json&url=" . urlencode($url) );
			file_put_contents( $filename, $text );
		}
		$data = json_decode( $text );
		$code = $data->html;
		$provider = "soundcloud";
		
	}
	
	# $code = "<PRE>" . htmlentities(print_r($data, true)) . "</pre.";

	return( array(
		"provider" => $provider,
		"code" => $code
	));

	// https%3A%2F%2Ftwitter.com%2Ftwitter%2Fstatus%2F99530515043983360	
	
	
/*
<blockquote class="twitter-tweet"><p>Wat?</p>&mdash; Dino Beslagic (@pp19dd) <a href="https://twitter.com/pp19dd/status/289400502574276608" data-datetime="2013-01-10T15:57:11+00:00">January 10, 2013</a></blockquote>
<script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>	
*/
	
}


<?php
// =======================================================================
// given lat, lng and a url, returns embed code
// =======================================================================
require_once( "embed.php" );

// =======================================================================
// returns JSONP
// =======================================================================
$data = array();

$data['clock'] = time();

$data['entry'] = array();

$data['entry'] = array(
	"lat" => $_GET['lat'], // ex: "38.892302",
	"lng" => $_GET['lng'], // ex: "-77.026391",
	"embed" => find_embed( trim($_GET['url']) )
);

printf( "%s(%s)\n", $_GET['callback'], json_encode( $data ) );

<?php
// =======================================================================
// allows add, delete, list
// - add spawns an ftp upload process
// =======================================================================
require_once( "config.php" );
require_once( "voa.php" );
require_once( "voa_ftp.php" );
require_once( "embed.php" );

// =======================================================================
// sync list via ftp, only on add / delete
// =======================================================================
function commit() {
	$html = file_get_contents(
		EDIT_URL . "edit.php?action=list&callback=livemap_object_static&project=" . $_GET['project']
	);
	
	file_put_contents( "data/" . JSON_FILE, $html );

	// move via ftp
	// function voa_ftp_upload_file( $server, $user, $pass, $local_filename, $remote_destination ) 
	
	voa_ftp_upload_file(
		FTP_HOST,
		FTP_USER,
		FTP_PASS,
		"data/" . JSON_FILE,
		FTP_DIR . JSON_FILE
	);
	
}

$data = array();
$data['clock'] = time();

switch( $_GET['action'] ) {
	case 'add':

		// provider = twitter
		// provider = shout   <- same thing
		
		$embed = find_embed( trim($_GET['url']) );
		$destination = 'Top';
		
		if( $_GET['type'] == 'shout' ) $destination = 'Bottom';
	
		$VOA->query(
			"insert into `%s` (`project`, lat`, `lng`, `url`, `destination`, `provider`, `embed`) values ('%s', '%s', '%s', '%s', '%s', '%s', '%s')",
			STORAGE_TABLE,
			$_GET['project'],
			$_GET['lat'],
			$_GET['lng'],
			$_GET['url'],
			$destination,
			$embed['provider'],
			$embed['code']
		);
		
		$data['id'] = mysql_insert_id();
		
		commit();
		
	break;
	
	case 'delete':
		$VOA->query(
			"update `%s` set is_deleted='Yes' where id=%s limit 1",
			STORAGE_TABLE,
			intval( $_GET['id'] )
		);
		commit();
	
	break;
	
	// ADMIN can access this, but, normally this function is read-only via akamai
	case 'list':
		
		$data['list'] = $VOA->query(
			"select * from `%s` where `project`='%s' and `is_deleted`='No' order by `id`",
			STORAGE_TABLE,
			$_GET['project'],
			array("noempty")
		);
		
		$data['deleted'] = $VOA->query(
			"select id from `%s` where `project`='%s' and `is_deleted`='Yes' order by `id`",
			STORAGE_TABLE,
			$_GET['project'],
			array("noempty")
		);
		
	break;
	
}

printf( "%s(%s)\n", $_GET['callback'], json_encode( $data ) );


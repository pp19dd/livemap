<?php

/* 
	use: voa_ftp_upload_file( 
		"server.com", 
		"user", 
		"password", 
		"local_file.PNG", 
		"/var/www/directory/lol.jpg"
	);
*/

function voa_ftp_upload_file( $server, $user, $pass, $local_filename, $remote_destination ) {

	$dirname = dirname( $remote_destination );

	$conn_id = ftp_connect( $server, 21 );
	if( $conn_id == false ) return( false );

	$try_login = @ftp_login( $conn_id, $user, $pass );

	if( $try_login == false ) {
		@ftp_close( $conn_id );
		return( false );
	}

	
	$try_chdir = @ftp_chdir( $conn_id, $dirname );
	if( !$try_chdir ) {
		$try_mkdir = @ftp_mkdir( $conn_id, $dirname );
		
		if( !$try_mkdir ) {
			@ftp_close( $conn_id );
			return( false );
		}
	}

	$try_upload = ftp_put( $conn_id, $remote_destination, $local_filename, FTP_BINARY );
	if( $try_upload == false ) {
		@ftp_close( $conn_id );
		return( false );
	}
	
	@ftp_close( $conn_id );
	
	return( true );
}

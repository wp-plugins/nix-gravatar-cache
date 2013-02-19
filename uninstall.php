<?php

$upload_dir = wp_upload_dir();
$dir = $upload_dir['basedir'] . '/gravatar/';
$no_permision_to_delete = false;

if ( is_dir( $dir ) ) {
    if ( $opendir = opendir( $dir ) ) {
        $count = 0;
        while ( ( $file = readdir( $opendir ) ) !== false ) {
            if ( filetype( $dir . $file ) == 'file' ) {
                if ( @unlink( $dir . $file ) ) {
                    $count++;
                }else {
                    $no_permision_to_delete = true;
                }
            }
        }
        closedir( $opendir );
        rmdir( $dir );
    }
}

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
		exit ();
delete_option( 'nf_c_a_options' );
<?php

function smolblog_enqueue_styles() {
	wp_enqueue_style( 
		'smolblog-style', 
		get_stylesheet_uri()
	);
}
add_action( 'wp_enqueue_scripts', 'smolblog_enqueue_styles' );

function smolblog_enqueue_admin_styles() {
	wp_enqueue_style( 
		'smolblog-admin-style', 
		get_theme_file_uri( 'wp-admin.css' )
	);
}
add_action( 'admin_enqueue_scripts', 'smolblog_enqueue_admin_styles' );
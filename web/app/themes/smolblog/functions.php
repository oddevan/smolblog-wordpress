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
		'bootstrap-style', 
		'https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css',
	);
	wp_enqueue_style( 
		'smolblog-admin-style', 
		get_theme_file_uri( 'admin.css' ),
		'bootstrap-style',
		filemtime(__DIR__ . '/admin.css'),
	);
	wp_enqueue_script(
		'bootstrap-scripts',
		'https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js',
	);
}
add_action( 'admin_enqueue_scripts', 'smolblog_enqueue_admin_styles' );
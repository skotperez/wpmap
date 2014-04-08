<?php
/*
Plugin Name: OSM, CloudMade and Leaflet Maps in WordPress
Description: This plugin allow you to show data from any custom post type in a map.
Version: 0.1
Author: montera34
Author URI: http://montera34.com
License: GPLv2+
* To Do:
	+ ...
*/

	/* Load map JavaScript and styles */
	add_action( 'wp_enqueue_scripts', 'wpmap_scripts_styles' );

	// get coordinates from OSM when a post is created or saved
	//add_action( 'save_post', 'wpmap_geocoding' );
	add_action( 'wp_insert_post', 'wpmap_geocoding' );

	// delete row from wp_wpmap when a post is permanently deleted
	add_action('deleted_post', 'wpmap_delete_geocoding');

	// create map data table in db
	add_action("after_switch_theme", "wpmap_create_db_table");


// where to find styles and scripts
$wpmap_css = plugin_dir_url(__FILE__) . 'style/';
$wpmap_js = plugin_dir_url(__FILE__) . 'js/';

// Register styles and scripts
function wpmap_scripts_styles() {
	wp_enqueue_style( 'leaflet-css','http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.css' );
	wp_enqueue_style( 'wpmap-css',$wpmap_css. 'map.css' );
	wp_enqueue_script(
		'leaflet-js',
		'http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.js',
		array('jquery'),
		'0.7.2',
		TRUE
	);
	wp_enqueue_script(
		'wpmap-js',
		$wpmap_js. 'map.js',
		array( 'leaflet-js' ),
		'0.1',
		TRUE
	);
	wp_enqueue_script(
		'leaflet-hash',
		$wpmap_js. 'leaflet-hash.js',
		array( 'wpmap-js' ),
		'0.1',
		TRUE
	);
} // end map scripts and styles

// create map data table in db
global $wpmap_db_version;
$wpmap_db_version = "0.1";
function wpmap_create_db_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . "wpmap"; 
	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
	  id bigint(20) NOT NULL AUTO_INCREMENT,
	  post_id bigint(20) NOT NULL,
	  post_status varchar(20) NOT NULL,
	  lat double NOT NULL,
	  lon double NOT NULL,
	  colour varchar(10) NOT NULL,
	  imageid varchar(20) NOT NULL,
	  UNIQUE KEY id (id)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( "jal_db_version", $wpmap_db_version );
} // end create table in db

// Geocoding script using Nominatim http://nominatim.openstreetmap.org/
// to get coordinates using City, Country and Postal Code of the points
////
function wpmap_geocoding( $post_id ) {

	global $wpdb;

	// If this is just a revision, don't continue
	if ( wp_is_post_revision( $post_id ) )
		return;

	$city = urlencode(get_post_meta( $post_id, '_wpmap_city', true ));
	$country = urlencode(get_post_meta( $post_id, '_wpmap_country', true ));

	if ( $city != '' || $country != '' ) {

		$results_json = file_get_contents("http://nominatim.openstreetmap.org/search?format=json&city=" .$city. "&country=" .$country);
		$results = json_decode($results_json,TRUE); // if second parameter is set to TRUE, the output is ass. array

		$lat = $results[0]['lat'];
		$lon = $results[0]['lon'];
		$post_status = get_post_status( $post_id );

		$table = "wp_wpmap";
		$data = array( 
			//'id' => is autoincrement
			'post_id' => $post_id,
			'post_status' => $post_status,
			'lat' => $lat,
			'lon' => $lon,
			'colour' => '',
			'imageid' => ''
		);
		$format = array(
			//'%d',
			'%d',
			'%s',
			'%f',
			'%f',
			'%s',
			'%s'
		); 

		$dbquery = "SELECT * FROM $table WHERE post_id = $post_id";
		$sql = $wpdb->get_row($dbquery,ARRAY_A);
		if ( $sql != null ) {
			// update
			$where = array('post_id' => $post_id );
			$wpdb->update( $table, $data, $where, $format );
		} else {
			// insert
			$wpdb->insert( $table, $data, $format );
		}

	}

} // END geocoding script

// Delete row in wp_wpmap when a post is deleted
////
function wpmap_delete_geocoding( $post_id ) {

	global $wpdb;
	$table = "wp_wpmap";
	$where = array('post_id' => $post_id );

	// delete
	$sql = $wpdb->delete( $table, $where, $where_format = null );	

} // END delete row script


?>

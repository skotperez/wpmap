<?php
/*
Plugin Name: OSM, CloudMade and Leaflet Maps in WordPress
Description: This plugin allow you to show data from any custom post type in a map.
Version: 0.2
Author: montera34
Author URI: http://montera34.com
License: GPLv2+
*/

include "wpmap-config.php";

	if (!defined('WPMAP_CITY'))
	    define('WPMAP_CITY', $wpmap_city);

	if (!defined('WPMAP_COUNTRY'))
	    define('WPMAP_COUNTRY', $wpmap_country);

	if (!defined('WPMAP_LAYER'))
	    define('WPMAP_LAYER', $wpmap_layer);

	if (!defined('WPMAP_PT'))
	    define('WPMAP_PT', $default_pt);

	if (!defined('WPMAP_MAP_LAT'))
	    define('WPMAP_MAP_LAT', $default_start_lat);

	if (!defined('WPMAP_MAP_LON'))
	    define('WPMAP_MAP_LON', $default_start_lon);

	if (!defined('WPMAP_INI_ZOOM'))
	    define('WPMAP_INI_ZOOM', $default_zoom_level);

	if (!defined('WPMAP_MIN_ZOOM'))
	    define('WPMAP_MIN_ZOOM', $default_min_zoom);

	if (!defined('WPMAP_MAX_ZOOM'))
	    define('WPMAP_MAX_ZOOM', $default_max_zoom);

	if (!defined('WPMAP_LAYERS'))
	    define('WPMAP_LAYERS', $default_map_layers);

	if (!defined('WPMAP_LAYERS_COLORS'))
	    define('WPMAP_LAYERS_COLORS', $default_layers_colors);

	if (!defined('WPMAP_AJAX'))
	    define('WPMAP_AJAX', plugins_url( 'ajax/map.php' , __FILE__));


	/* Load map JavaScript and styles */
	add_action( 'wp_enqueue_scripts', 'wpmap_scripts_styles' );

	// get coordinates from OSM when a post is created or saved
	// the action to hook the geocoding must be save_post (not wp_insert_post) to keep geodata updated
	add_action( 'save_post', 'wpmap_geocoding' );

	// delete row from wp_wpmap when a post is permanently deleted
	add_action('deleted_post', 'wpmap_delete_geocoding');

	// create map data table in db
	register_activation_hook( __FILE__, 'wpmap_create_db_table' );


// Register styles and scripts
function wpmap_scripts_styles() {

	wp_enqueue_style( 'leaflet-css','http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.css' );
	wp_enqueue_style( 'wpmap-css',plugins_url( 'style/map.css' , __FILE__) );
	wp_enqueue_script(
		'leaflet-js',
		'http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.js',
		array('jquery'),
		'0.7.2',
		TRUE
	);
	wp_enqueue_script(
		'wpmap-js',
		plugins_url( 'js/map.js' , __FILE__),
		array( 'leaflet-js' ),
		'0.1',
		TRUE
	);
	wp_enqueue_script(
		'leaflet-hash',
		plugins_url( 'js/leaflet-hash.js' , __FILE__),
		array( 'wpmap-js' ),
		'0.1',
		TRUE
	);
} // end map scripts and styles

// create map data table in db
//global $wpmap_db_version;
//$wpmap_db_version = "0.1";
function wpmap_create_db_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . "wpmap"; 
	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
	  id bigint(20) NOT NULL AUTO_INCREMENT,
	  post_id bigint(20) NOT NULL,
	  post_type varchar(20) NOT NULL,
	  post_status varchar(20) NOT NULL,
	  lat double NOT NULL,
	  lon double NOT NULL,
	  colour varchar(100) NOT NULL,
	  imageid varchar(20) NOT NULL,
	  UNIQUE KEY id (id)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

//	add_option( "wpmap_db_version", $wpmap_db_version );
} // end create table in db

// Geocoding script using Nominatim http://nominatim.openstreetmap.org/
// to get coordinates using City, Country and Postal Code of the points
////
function wpmap_geocoding( $post_id ) {

	global $wpdb;

	// If this is just a revision, don't continue
	if ( wp_is_post_revision( $post_id ) )
		return;

	// get post city and country
	$city = urlencode(get_post_meta( $post_id, WPMAP_CITY, true ));
	$country = urlencode(get_post_meta( $post_id, WPMAP_COUNTRY, true ));
	if ( $city != '' || $country != '' ) {

		// use nominatim geocoding service to get coords
		$results_json = file_get_contents("http://nominatim.openstreetmap.org/search?format=json&city=" .$city. "&country=" .$country);
		$results = json_decode($results_json,TRUE); // if second parameter is set to TRUE, the output is ass. array

		// info to insert in db
		$post_type = get_post_type( $post_id );
		$post_status = get_post_status( $post_id );
		$lat = $results[0]['lat'];
		$lon = $results[0]['lon'];
		$post_layers = get_post_meta( $post_id, WPMAP_LAYER, true );
		if ( is_array($post_layers) ) { $post_layer = $post_layers[0]; }
		else { $post_layer = $post_layers; }

		// preparing data to insert
		$table = $wpdb->prefix . "wpmap"; 
		$data = array( 
			//'id' => is autoincrement
			'post_id' => $post_id,
			'post_type' => $post_type,
			'post_status' => $post_status,
			'lat' => $lat,
			'lon' => $lon,
			'colour' => $post_layer,
			'imageid' => ''
		);
		$format = array(
			//'%d',
			'%d',
			'%s',
			'%s',
			'%f',
			'%f',
			'%s',
			'%s'
		); 

		// query to know if there is already a row for this post
		$dbquery = "SELECT * FROM $table WHERE post_id = $post_id";
		$sql = $wpdb->get_row($dbquery,ARRAY_A);
		if ( $sql != null ) {
			// if yes, update
			$where = array('post_id' => $post_id );
			$wpdb->update( $table, $data, $where, $format );
		} else {
			// if no, insert
			$wpdb->insert( $table, $data, $format );
		}

	}

} // END geocoding script

// Delete row in wp_wpmap when a post is deleted
////
function wpmap_delete_geocoding( $post_id ) {

	global $wpdb;
	$table = $wpdb->prefix . "wpmap"; 
	$where = array('post_id' => $post_id );

	// delete
	$sql = $wpdb->delete( $table, $where, $where_format = null );	

} // END delete row script

// wpmap shortcode
add_shortcode('wpmap', 'wpmap_shortcode');
function wpmap_shortcode($atts) {
	extract( shortcode_atts( array(
		'pt' => WPMAP_PT,
		'centerLat' => WPMAP_MAP_LAT,
		'centerLon' => WPMAP_MAP_LON,
		'initialZoomLevel' => WPMAP_INI_ZOOM,
		'minZoomLevel' => WPMAP_MIN_ZOOM,
		'maxZoomLevel' => WPMAP_MAX_ZOOM,
		'layers' => WPMAP_LAYERS,
		'colors' => WPMAP_LAYERS_COLORS,
		'defaultColor' => "#000000",
	), $atts ) );
	$the_map = "
		<div id='map'></div>
		<script>
		var pt = '$pt';
		var centerLat = '$centerLat';
		var centerLon = '$centerLon';
		var initialZoomLevel = $initialZoomLevel;
		var minZoomLevel = $minZoomLevel;
		var maxZoomLevel = $maxZoomLevel;
		var pointLayers = [$layers];
		var pointColors = [$colors];
		var pointDefaultColor = '$defaultColor';
		var ajaxUrl = '".WPMAP_AJAX."';
		</script>
	";
	return $the_map;
} // END shortcode

// show map function
function wpmap_showmap( $args ) {
	$parameters = array("pt","center_lat","center_lon","zoom_ini","zoom_min","zoom_max","layers","colors","default_color");
	$defaults = array(WPMAP_PT,WPMAP_MAP_LAT,WPMAP_MAP_LON,WPMAP_INI_ZOOM,WPMAP_MIN_ZOOM,WPMAP_MAX_ZOOM,WPMAP_LAYERS,WPMAP_LAYERS_COLORS,"#000000");
	$count = 0;
	foreach ( $parameters as $parameter ) {
		if ( $args[$parameter] == null ) { $args[$parameter] = $defaults[$count]; }
		$count++;
	}
	$the_map = "
		<div id='map'></div>
		<script>
		var pt = '{$args['pt']}';
		var centerLat = '{$args['center_lat']}';
		var centerLon = '{$args['center_lon']}';
		var initialZoomLevel = {$args['zoom_ini']};
		var minZoomLevel = {$args['zoom_min']};
		var maxZoomLevel = {$args['zoom_max']};
		var pointLayers = [{$args['layers']}];
		var pointColors = [{$args['colors']}];
		var pointDefaultColor = '{$args['default_color']}';
		var ajaxUrl = '".WPMAP_AJAX."';
		</script>
	";
	echo $the_map;
} // END show map function
?>

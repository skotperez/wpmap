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

if (!defined('WPMAP_COUNTRY'))
    define('WPMAP_COUNTRY', $wpmap_country);

if (!defined('WPMAP_CITY'))
    define('WPMAP_CITY', $wpmap_city);

if (!defined('WPMAP_STREETNAME'))
    define('WPMAP_STREETNAME', $wpmap_street);

if (!defined('WPMAP_HOUSENUMBER'))
    define('WPMAP_HOUSENUMBER', $wpmap_number);

if (!defined('WPMAP_POSTALCODE'))
    define('WPMAP_POSTAL_CODE', $wpmap_code);

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
// update wpmap table in db, if there are changes
add_action( 'plugins_loaded', 'wpmap_update_db_table' );

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
global $wpmap_db_version;
$wpmap_db_version = "0.2";
function wpmap_create_db_table() {
	global $wpdb;
	global $wpmap_db_version;

	$charset_collate = '';
	if ( ! empty( $wpdb->charset ) ) {
		$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
	}
	if ( ! empty( $wpdb->collate ) ) {
		$charset_collate .= " COLLATE {$wpdb->collate}";
	}

	$table_name = $wpdb->prefix . "wpmap"; 
	$sql = "
	CREATE TABLE $table_name (
	  id bigint(20) unsigned NOT NULL auto_increment,
	  post_id bigint(20) unsigned NOT NULL default '',
	  post_type varchar(20) NOT NULL default 'post',
	  post_status varchar(20) NOT NULL default 'publish',
	  lat double NOT NULL default 0,
	  lon double NOT NULL default 0,
	  colour varchar(100) NOT NULL default '',
	  layer_group varchar(100) NOT NULL default '',
	  UNIQUE KEY id (id)
	) $charset_collate;
	";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	update_option( "wpmap_db_version", $wpmap_db_version );
	
} // end create table in db

// update map table in db
function wpmap_update_db_table() {
	global $wpmap_db_version;
	$current_db_version = get_option( "wpmap_db_version" );
	if ( $wpmap_db_version != $current_db_version ) { wpmap_create_db_table(); }

} // end update map table in db

// Geocoding script using Nominatim http://nominatim.openstreetmap.org/
// to get coordinates using City, Country, street name, house number and Postal Code
function wpmap_geocoding( $post_id ) {

	global $wpdb;
//	global $wpmap_layer_groups;

	// If this is just a revision, don't continue
	if ( wp_is_post_revision( $post_id ) )
		return;

	$city = urlencode(get_post_meta( $post_id, WPMAP_CITY, true ));
	$country = urlencode(get_post_meta( $post_id, WPMAP_COUNTRY, true ));
	if ( $city != '' || $country != '' ) {

		// do geocoding
		$street_name = urlencode(get_post_meta( $post_id, WPMAP_STREETNAME, true ));
		$house_number = urlencode(get_post_meta( $post_id, WPMAP_HOUSENUMBER, true ));
		$postal_code = urlencode(get_post_meta( $post_id, WPMAP_POSTALCODE, true ));
		// use nominatim geocoding service to get coords
		$results_json = file_get_contents("http://nominatim.openstreetmap.org/search?format=json&country=".$country."&city=".$city."&street=".$house_number."%20".$street_name."&postalcode=".$postal_code."&limit=1");
		$results = json_decode($results_json,TRUE); // if second parameter is set to TRUE, the output is ass. array

		if ( !array_key_exists('0',$results) ) {
			$results_json = file_get_contents("http://nominatim.openstreetmap.org/search?format=json&country=".$country."&city=".$city."&limit=1");
			$results = json_decode($results_json,TRUE); // if second parameter is set to TRUE, the output is ass. array
		}

		// do the insert in db
		$table = $wpdb->prefix . "wpmap"; 
//		$post_type = get_post_type( $post_id );
//		$post_status = get_post_status( $post_id );
		$lat = $results[0]['lat'];
		$lon = $results[0]['lon'];

			// preparing data to insert
			$data = array( 
				//'id' => is autoincrement
				'post_id' => $post_id,
				'post_type' => '',
				'post_status' => '',
				'lat' => $lat,
				'lon' => $lon,
				'colour' => '',
				'layer_group' => ''
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

		$dbquery = "SELECT post_id FROM $table WHERE post_id='$post_id' LIMIT 1";
		if ( $wpdb->get_row($dbquery,OBJECT) == NULL && $lat != '' && $lon != '' ) {
			/* create row */ $wpdb->insert( $table, $data, $format );

		} else {
			$where = array(
				'post_id' => $post_id
			);
			/* update row */ $wpdb->update( $table, $data, $where, $format );
			if ( $lat == '' || $lon == '' ) { /* delete row */ $wpdb->delete( $table, $where, $where_format = null ); }

		} // end if there is no coords for this post id

	} // if city and country are not empty

} // END geocoding script

// Delete row in wp_wpmap when a post is deleted
////
function wpmap_delete_geocoding( $post_id ) {

	global $wpdb;
	$table = $wpdb->prefix . "wpmap"; 
	$where = array('post_id' => $post_id );
	// delete
	$wpdb->delete( $table, $where, $where_format = null );	

} // END delete row script

// wpmap shortcode
add_shortcode('wpmap', 'wpmap_shortcode');
function wpmap_shortcode($atts) {
	extract( shortcode_atts( array(
		'pt' => '',
		'centerLat' => WPMAP_MAP_LAT,
		'centerLon' => WPMAP_MAP_LON,
		'initialZoomLevel' => WPMAP_INI_ZOOM,
		'minZoomLevel' => WPMAP_MIN_ZOOM,
		'maxZoomLevel' => WPMAP_MAX_ZOOM,
		'groups' => '',
		'layers' => '',
		'colors' => '',
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
		var layerGroups = [$groups];
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
	$parameters = array("pt","center_lat","center_lon","zoom_ini","zoom_min","zoom_max","groups","layers","colors","default_color");
	$defaults = array("",WPMAP_MAP_LAT,WPMAP_MAP_LON,WPMAP_INI_ZOOM,WPMAP_MIN_ZOOM,WPMAP_MAX_ZOOM,"","","","#000000");
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
		var layerGroups = [{$args['groups']}];
		var pointLayers = [{$args['layers']}];
		var pointColors = [{$args['colors']}];
		var pointDefaultColor = '{$args['default_color']}';
		var ajaxUrl = '".WPMAP_AJAX."';
		</script>
	";
	echo $the_map;
} // END show map function
?>

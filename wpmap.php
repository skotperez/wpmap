<?php
/*
Plugin Name: WPMap
Description: Put your content in a map. This plugin works using OpenStreetMaps and Leaflet.
Version: 0.4
Author: montera34
Author URI: http://montera34.com
License: GPLv2+
*/

include "wpmap-config.php";

if (!defined('WPMAP_COUNTRY'))
    define('WPMAP_COUNTRY', $wpmap_country);

if (!defined('WPMAP_CITY'))
    define('WPMAP_CITY', $wpmap_city);

if (!defined('WPMAP_STATE'))
    define('WPMAP_STATE', $wpmap_state);

if (!defined('WPMAP_STREETNAME'))
    define('WPMAP_STREETNAME', $wpmap_street);

if (!defined('WPMAP_HOUSENUMBER'))
    define('WPMAP_HOUSENUMBER', $wpmap_number);

if (!defined('WPMAP_POSTALCODE'))
    define('WPMAP_POSTAL_CODE', $wpmap_code);

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
//    define('WPMAP_AJAX', plugins_url( 'ajax/map.php' , __FILE__));
    define('WPMAP_AJAX', admin_url( 'admin-ajax.php' , __FILE__));

// GEOSEARCH MODULE
if (!defined('WPMAP_GEOSEARCH'))
    define('WPMAP_GEOSEARCH', $geosearch_enabled);
if (!defined('WPMAP_GEOSEARCH_ECHOED'))
    define('WPMAP_GEOSEARCH_ECHOED', $geosearch_echoed);
if (!defined('WPMAP_GEOSEARCH_LAT_ID'))
    define('WPMAP_GEOSEARCH_LAT_ID', $geosearch_lat_id);
if (!defined('WPMAP_GEOSEARCH_LON_ID'))
    define('WPMAP_GEOSEARCH_LON_ID', $geosearch_lon_id);


// Hook AJAX requests
add_action('wp_ajax_nopriv_wpmap_get_map_data','wpmap_get_map_data_callback');
add_action('wp_ajax_wpmap_get_map_data','wpmap_get_map_data_callback');

/* Load map JavaScript and styles */
add_action( 'wp_enqueue_scripts', 'wpmap_register_load_styles' );

// get coordinates from OSM when a post is created or saved
// the action to hook the geocoding must be save_post (not wp_insert_post) to keep geodata updated
add_action( 'save_post', 'wpmap_save_post_coords' );

// delete row from wp_wpmap when a post is permanently deleted
add_action('deleted_post', 'wpmap_delete_geocoding');

// create map data table in db
register_activation_hook( __FILE__, 'wpmap_create_db_table' );
// update wpmap table in db, if there are changes
add_action( 'plugins_loaded', 'wpmap_update_db_table' );

// Register and load styles
function wpmap_register_load_styles() {
	wp_enqueue_style( 'leaflet-css','http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.css' );
	wp_enqueue_style( 'wpmap-css',plugins_url( 'style/map.css' , __FILE__) );
	// L.GeoSearch MODULE
	if ( WPMAP_GEOSEARCH === 1 )
		wp_enqueue_style( 'wpmap-lgeosearch',plugins_url( 'L.GeoSearch/style/l.geosearch.css' , __FILE__) );

} // end register load map styles

// Register and load scripts
function wpmap_register_load_scripts() {
	wp_enqueue_script(
		'leaflet-js',
		'http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.js',
		array('jquery'),
		'0.7.2',
		TRUE
	);
	wp_enqueue_script(
		'leaflet-hash',
		plugins_url( 'js/leaflet-hash.js' , __FILE__),
		array( 'leaflet-js' ),
		'0.1',
		TRUE
	);
	wp_enqueue_script(
		'wpmap-js',
		plugins_url( 'js/map.js' , __FILE__),
		array( 'leaflet-hash' ),
		'',
		'0.1',
		TRUE
	);
} // end register load map scripts

// Load scripts for L.GeoSearch module
function wpmap_geosearch_register_load_scripts() {
	wp_enqueue_script(
		'leaflet-lgeosearch',
		plugins_url( 'L.GeoSearch/js/l.control.geosearch.js' , __FILE__),
		array('leaflet-js'),
		'0.1',
		TRUE
	);
	wp_enqueue_script(
		'leaflet-lgeosearch-osm',
		plugins_url( 'L.GeoSearch/js/l.geosearch.provider.openstreetmap.js' , __FILE__),
		array('leaflet-lgeosearch'),
		'0.1',
		TRUE
	);
	wp_register_script(
		'getplace-js',
		plugins_url( 'js/getplace.js' , __FILE__),
		array('leaflet-lgeosearch-osm'),
		'0.1',
		TRUE
	);
	// Localize the script with new data
	$geosearch_options = array(
	 	'echo' => WPMAP_GEOSEARCH_ECHOED,
		'lat_id' => WPMAP_GEOSEARCH_LAT_ID,
		'lon_id' => WPMAP_GEOSEARCH_LON_ID
	);
	wp_localize_script( 'getplace-js', 'geosearchOptions', $geosearch_options );
	wp_enqueue_script( 'getplace-js' );

}

// create map data table in db
global $wpmap_db_version;
$wpmap_db_version = "0.3";
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
	  post_id bigint(20) unsigned NOT NULL,
	  lat double NOT NULL default 0,
	  lon double NOT NULL default 0,
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
function wpmap_geocoding( $country='',$state='',$street_name='',$house_number='',$postal_code='' ) {
	// use nominatim geocoding service to get coords
	$results_json = file_get_contents("http://nominatim.openstreetmap.org/search?format=json&country=".$country."&city=".$city."&state=".$state."&street=".$house_number."%20".$street_name."&postalcode=".$postal_code."&limit=1");
	$results = json_decode($results_json,TRUE); // if second parameter is set to TRUE, the output is ass. array

	if ( !array_key_exists('0',$results) ) {
		$results_json = file_get_contents("http://nominatim.openstreetmap.org/search?format=json&country=".$country."&city=".$city."&limit=1");
		$results = json_decode($results_json,TRUE);
	}

	if ( array_key_exists('0',$results) )
		return $results[0];
	else return;
}

// save post coords got by geocoding city, address...
function wpmap_save_post_coords( $post_id ) {

	global $wpdb;

	// If this is just a revision, don't continue
	if ( wp_is_post_revision( $post_id ) )
		return;

	$city = urlencode(get_post_meta( $post_id, WPMAP_CITY, true ));
	$country = urlencode(get_post_meta( $post_id, WPMAP_COUNTRY, true ));
	if ( $city != '' || $country != '' ) {

		$state = urlencode(get_post_meta( $post_id, WPMAP_STREETNAME, true ));
		$street_name = urlencode(get_post_meta( $post_id, WPMAP_STREETNAME, true ));
		$house_number = urlencode(get_post_meta( $post_id, WPMAP_HOUSENUMBER, true ));
		$postal_code = urlencode(get_post_meta( $post_id, WPMAP_POSTALCODE, true ));
		// do geocoding
		$result = wpmap_geocoding($country,$state,$street_name,$house_number,$postal_code);

		if ( !array_key_exists('lat',$result) ) return;

		// do the insert in db
		$table = $wpdb->prefix . "wpmap"; 
		$lat = $result['lat'];
		$lon = $result['lon'];

		// preparing data to insert
		$data = array( 
			//'id' => is autoincrement
			'post_id' => $post_id,
			'lat' => $lat,
			'lon' => $lon
		);
		$format = array(
			//'%d',
			'%d',
			'%f',
			'%f'
		);

		$dbquery = "SELECT post_id FROM $table WHERE post_id='$post_id'";
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

function wpmap_geosearch() {

	wpmap_geosearch_register_load_scripts();
}

// wpmap shortcode
add_shortcode('wpmap', 'wpmap_shortcode');
function wpmap_shortcode($atts) {
	wpmap_register_load_scripts();
	extract( shortcode_atts( array(
		// query filters
		'post_type' => '',
		'post_status' => 'publish',
		'post_in' => '',
		'post_not_in' => '',
		'meta_key' => '',
		'meta_value' => '',
		'term_slug' => '',
		// layers
		'layers_by' => '',
		'layers' => '',
		'colors' => '',
		'default_color' => "#000000",
		// map vars
		'center_lat' => WPMAP_MAP_LAT,
		'center_lon' => WPMAP_MAP_LON,
		'zoom_ini' => WPMAP_INI_ZOOM,
		'zoom_min' => WPMAP_MIN_ZOOM,
		'zoom_max' => WPMAP_MAX_ZOOM,
		'map_width' => '',
		'map_height' => '',
		// popup content
		'popup_text' => '',
		'popup_max_width' => '300',
		'popup_max_height' => '300',
		// marker style
		'marker_radius' => '15',
		'marker_opacity' => '0.8',
		'marker_fillOpacity' => '0.8',
		// marker style
		'geosearch' => '1',
		
	), $atts ) );
	if ( $geosearch == '1' )
		wpmap_geosearch();

	$layers = "'".str_replace(",","','",$layers)."'";
	$colors = "'".str_replace(",","','",$colors)."'";
	$map_style = "";
	if ( $map_width != '' ) { $map_style .= "width:".$map_width.";"; }
	if ( $map_height != '' ) { $map_style .= "height:".$map_height.";"; }
	if ( $map_style != '' ) { $map_style = " style='".$map_style."'"; }
	$the_map = "
		<div id='map'".$map_style."></div>
		<script>
		var pType = '$post_type';
		var pStatus = '$post_status';
		var pIn = '$post_in';
		var pNotIn = '$post_not_in';
		var mKeys = '$meta_key';
		var mValues = '$meta_value';
		var tSlugs = '$term_slug';
		var layersBy = '$layers_by';
		var layers = [$layers];
		var colors = [$colors];
		var defaultColor = '$default_color';
		var centerLat = '$center_lat';
		var centerLon = '$center_lon';
		var initialZoomLevel = $zoom_ini;
		var minZoomLevel = $zoom_min;
		var maxZoomLevel = $zoom_max;
		var popupText = '$popup_text';
		var popupMaxWidth = '$popup_max_width';
		var popupMaxHeight = '$popup_max_height';
		var markerRadius = '$marker_radius';
		var markerOpacity = '$marker_opacity';
		var markerFillOpacity = '$marker_fillOpacity';
		var ajaxUrl = '".WPMAP_AJAX."';
		</script>
	";
	return $the_map;
} // END shortcode

// show map function
function wpmap_showmap( $args ) {
	wpmap_register_load_scripts();
	$parameters = array("post_type","post_status","post_in","post_not_in","meta_key","meta_value","term_slug","layers_by","layers","colors","default_color","center_lat","center_lon","zoom_ini","zoom_min","zoom_max","map_width","map_height","popup_text","popup_max_width","popup_max_height",'marker_radius','marker_opacity','marker_fillOpacity');
	$defaults = array("","publish","","","","","","","","","#000000",WPMAP_MAP_LAT,WPMAP_MAP_LON,WPMAP_INI_ZOOM,WPMAP_MIN_ZOOM,WPMAP_MAX_ZOOM,"","300","300","15","0.8","0.8");
	$count = 0;
	foreach ( $parameters as $parameter ) {
		if ( $args[$parameter] == null ) { $args[$parameter] = $defaults[$count]; }
		if ( $parameter == 'layers' || $parameter == 'colors' ) {
			$args[$parameter] = "'".str_replace(",","','",$args[$parameter])."'";
		}
		$count++;
	}
	$map_style = "";
	if ( $map_width != '' ) { $map_style .= "width:".$map_width.";"; }
	if ( $map_height != '' ) { $map_style .= "height:".$map_height.";"; }
	if ( $map_style != '' ) { $map_style = " style='".$map_style."'"; }
	$the_map = "
		<div id='map'></div>
		<script>
		var pType = '{$args['post_type']}';
		var pStatus = '{$args['post_status']}';
		var pIn = '{$args['post_in']}';
		var pNotIn = '{$args['post_not_in']}';
		var mKeys = '{$args['meta_key']}';
		var mValues = '{$args['meta_value']}';
		var tSlugs = '{$args['term_slug']}';
		var layersBy = '{$args['layers_by']}';
		var layers = [{$args['layers']}];
		var colors = [{$args['colors']}];
		var defaultColor = '{$args['default_color']}';
		var centerLat = '{$args['center_lat']}';
		var centerLon = '{$args['center_lon']}';
		var initialZoomLevel = {$args['zoom_ini']};
		var minZoomLevel = {$args['zoom_min']};
		var maxZoomLevel = {$args['zoom_max']};
		var popupText = '{$args['popup_text']};
		var popupMaxWidth = '{$args['popup_max_width']};
		var popupMaxHeight = '{$args['popup_max_height']};
		var markerRadius = '{$args['marker_radius']};
		var markerOpacity = '{$args['marker_opacity']};
		var markerFillOpacity = '{$args['marker_fillOpacity']};
		var ajaxUrl = '".WPMAP_AJAX."';
		</script>
	";
	echo $the_map;
} // END show map function

// Callback function for AJAX request
// Build the geoJSON response
function wpmap_get_map_data_callback() {

	global $wpdb;
// SET MAP BOUNDS
	if (array_key_exists('bbox', $_GET) ) {
		$bbox = sanitize_text_field($_GET['bbox']);

	}
	else {
		$response = array(
			'data'	=> 'error',
			'message' => 'Missing bounding box.'
		);
		header( "Content-Type: application/json" );
		echo json_encode($response);
		wp_die();
	}

	// split the bbox into it's parts
	list($left,$bottom,$right,$top)=explode(",",$bbox);

// PREPARE DB QUERY PARAMETERS
	$filters = array();
	foreach ( array(
		'post_type' => array('post_type','p'),
		'post_status' => array('post_status','p'),
		'ID' => array('post_in','p'),
		'post_id' => array('post_not_in','m'),
		'meta_key' => array('meta_key','pm'),
		'meta_values' => array('meta_value','pm'),
		'slug' => array('term_slug','t')
	) as $k => $p ) {
		if (array_key_exists($p[0], $_GET) ) { $$p[0] = sanitize_text_field($_GET[$p[0]]); }
		else { $$p[0] = ""; }
		$filters[$k] = array('values'=>$$p[0],'table'=>$p[1]);

	}

	// fields to select
	$extra_field = "p.post_title, p.post_content, p.post_type, p.post_status";

	$extra_where = "";
	foreach ( $filters as $column => $extra ) {
		if ( $extra['values'] != '' ) {
			$sql_extra = " AND {$extra['table']}.$column IN (";
			foreach ( explode(",",$extra['values']) as $value ) {
				$sql_extra .= "'$value', ";
			}
			$sql_extra = substr($sql_extra, 0, -2);
			$sql_extra .= ")";
			if ( $column == 'post_id' ) { $sql_extra = str_replace("IN","NOT IN",$sql_extra); }
	
		} else { $sql_extra = ""; }
		$extra_where .= $sql_extra;

	} // end foreach extra sql parametres

	if ( $meta_key != '' || $meta_value != '' ) { // if meta keys or meta values filters
		$table_postmeta = $wpdb->prefix."postmeta";
		$extra_join = "
		INNER JOIN $table_postmeta pm
		  ON m.post_id = pm.post_id
		";
		$extra_field .= ", pm.meta_value, pm.meta_key";

	} elseif ( $term_slug != '' ) { // if terms filters
		$table_term_rel = $wpdb->prefix."term_relationships";
		$table_term_tax = $wpdb->prefix."term_taxonomy";
		$table_terms = $wpdb->prefix."terms";
		$extra_join = "
		INNER JOIN $table_term_rel tr
		  ON m.post_id = tr.object_id
		INNER JOIN $table_term_tax tt
		  ON tr.term_taxonomy_id = tt.term_taxonomy_id
		INNER JOIN $table_terms t
		  ON tt.term_id = t.term_id
		";
		$extra_field .= ", t.name, t.slug";

	} else { $extra_join = ''; }

// MAP LAYERS
	if (array_key_exists('layers_by', $_GET) ) {
		$layers_by = sanitize_text_field($_GET['layers_by']); // possible values: post_type, post_status, meta_key, meta_value, term_slug
		if ( $layers_by == 'term_slug' ) { $layer_by = "slug"; }
		else { $layer_by = $layers_by; }
	} else { $layer_by = ""; }

// FIELDS IN POPUP
	if (array_key_exists('popup_text', $_GET) ) {
		$popup_text = sanitize_text_field($_GET['popup_text']); // values: content, excerpt
		//$popup_author = sanitize_text_field($_GET['popup_author']); // values: name
		//$popup_date = sanitize_text_field($_GET['popup_date']); // values: 
		//$popup_img = sanitize_text_field($_GET['popup_image']); // values: featured

	} else { $popup_text = ""; }
		
// INIT DB QUERY
	$table_map = $wpdb->prefix."wpmap";
	$table_posts = $wpdb->prefix."posts";
	$sql_query = "
	SELECT
	  m.lat,
	  m.lon,
	  p.ID,
	  $extra_field
	FROM $table_map m
	INNER JOIN $table_posts p
	  ON m.post_id = p.ID
	$extra_join
	WHERE m.lon>=$left AND m.lon<=$right
	  AND m.lat>=$bottom AND m.lat<=$top
	  $extra_where
	";
	$query_results = $wpdb->get_results( $sql_query , ARRAY_A );

// BUILD GEOJSON RESPONSE
	$response = array(); // place to store the geojson result
	$features = array(); // array to build up the feature collection
	$response['type'] = 'FeatureCollection';
	
	foreach ( $query_results as $row ) {
		$lat = $row['lat'];
		$lon = $row['lon'];
	
		$prop=array();
		$prop['tit'] = get_the_title($row['ID']);
		$prop['perma'] = get_permalink($row['ID']);
		if ( $popup_text == 'excerpt' ) { $post_desc = wp_trim_words( $row['post_content'], 55 ); }
		else { $post_desc = $row['post_content']; }
		$prop['desc'] = apply_filters('the_content', utf8_encode($post_desc));
		if ( $layer_by != '' ) { $prop['layer'] = $row[$layer_by]; }
	
		$f=array();
		$geom=array();
		$coords=array();
		
		$geom['type']='Point';
		$coords[0]=floatval($lon);
		$coords[1]=floatval($lat);
	
		$geom['coordinates']=$coords;
		$f['type']='Feature';
		$f['geometry']=$geom;
		$f['properties']=$prop;
	
		$features[]=$f;
	
	}
		
	// add the features array to the end of the ajxres array
	$response['features'] = $features;
	
	// Instantiate WP_Ajax_Response
//	$response = new WP_Ajax_Response;	
//	$response->add( array(
//		'data'	=> 'success',
//		'square'=> $bbox,
//		'supplemental' => array(
//			'message' => 'Ajax call received.'
//		),
//	) );
	// Whatever the outcome, send the Response back
//	$response->send();
	header( "Content-Type: application/json" );
	echo json_encode($response);

	// Always exit when doing Ajax
//	exit();
	wp_die();
}
?>

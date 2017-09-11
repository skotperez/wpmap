<?php
/*
Plugin Name: WPMap
Description: Put your content in a map. This plugin works using OpenStreetMaps and Leaflet.
Version: 0.42
Author: montera34
Author URI: https://montera34.com
License: GPLv2+
*/

$ver = "0.42";

include "wpmap-config.php";

if (!defined('WPMAP_ICONS_PATH'))
    define('WPMAP_ICONS_PATH', $wpmap_icons_path);

if (!defined('WPMAP_ICON'))
    define('WPMAP_ICON', $wpmap_icon);

if (!defined('WPMAP_ICON_WIDTH'))
    define('WPMAP_ICON_WIDTH', $wpmap_icon_width);

if (!defined('WPMAP_ICON_HEIGHT'))
    define('WPMAP_ICON_HEIGHT', $wpmap_icon_height);

if (!defined('WPMAP_LAT'))
    define('WPMAP_LAT', $wpmap_lat);

if (!defined('WPMAP_LON'))
    define('WPMAP_LON', $wpmap_lon);

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

// constants containing arrays only supported in PHP7
//if (!defined('WPMAP_FIELDS_IN_MAP'))
//    define('WPMAP_FIELDS_IN_MAP', $wpmap_fields_in_map);

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

// SIDEBAR MODULE
if (!defined('WPMAP_SIDEBAR'))
    define('WPMAP_SIDEBAR', $sidebar_enabled);

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
	//wp_enqueue_style( 'leaflet-css','http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.css' );
	wp_enqueue_style( 'leaflet-css',plugins_url( 'leaflet/leaflet.css' , __FILE__) );
	wp_enqueue_style( 'wpmap-css',plugins_url( 'style/map.css' , __FILE__) );
	// L.GeoSearch MODULE
	if ( WPMAP_GEOSEARCH === 1 )
		wp_enqueue_style( 'leaflet-geosearch-css',plugins_url( 'L.GeoSearch/style/l.geosearch.css' , __FILE__) );
	// L.Control.Sidebar MODULE
	if ( WPMAP_SIDEBAR === 1 )
		wp_enqueue_style( 'leaflet-sidebar-css',plugins_url( 'L.Control.Sidebar/L.Control.Sidebar.css' , __FILE__) );

} // end register load map styles

// Register and load scripts
function wpmap_register_load_scripts() {
	wp_enqueue_script(
		'leaflet-js',
		//'http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.js',
		plugins_url( 'leaflet/leaflet.js' , __FILE__),
		array('jquery'),
		'1.0.3',
		TRUE
	);
	wp_enqueue_script(
		'leaflet-hash',
		plugins_url( 'js/leaflet-hash.js' , __FILE__),
		array( 'leaflet-js' ),
		NULL,
		TRUE
	);
	// L.Control.Sidebar MODULE
	if ( WPMAP_SIDEBAR === 1 ) {
		wp_enqueue_script(
			'leaflet-sidebar-js',
			plugins_url( 'L.Control.Sidebar/L.Control.Sidebar.js' , __FILE__),
			array( 'leaflet-js' ),
			NULL,
			TRUE
		);
//		wp_enqueue_script(
//			'wpmap-sidebar-js',
//			plugins_url( 'js/sidebar.js' , __FILE__),
//			array( 'wpmap-js' ),
//			$ver,
//			TRUE
//		);
	}

	wp_enqueue_script(
		'wpmap-js',
		plugins_url( 'js/map.js' , __FILE__),
		array( 'leaflet-sidebar-js','leaflet-hash' ),
		$ver,
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
	wp_enqueue_script(
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

	$lat = get_post_meta( $post_id, WPMAP_LAT, true );
	$lon = get_post_meta( $post_id, WPMAP_LON, true );
	$city = urlencode(get_post_meta( $post_id, WPMAP_CITY, true ));
	$country = urlencode(get_post_meta( $post_id, WPMAP_COUNTRY, true ));
	if ( $city != '' || $country != '' || $lat != '' && $lon != '' ) {

		$state = urlencode(get_post_meta( $post_id, WPMAP_STREETNAME, true ));
		$street_name = urlencode(get_post_meta( $post_id, WPMAP_STREETNAME, true ));
		$house_number = urlencode(get_post_meta( $post_id, WPMAP_HOUSENUMBER, true ));
		$postal_code = urlencode(get_post_meta( $post_id, WPMAP_POSTALCODE, true ));
		// normally do geocoding
		// but
		// if lat and lon fields are filled in
		// there is no need to do it
		// just copy the values to wpmap table
		if ( $lat == '' && $lon == '' ) {
			$result = wpmap_geocoding($country,$state,$street_name,$house_number,$postal_code);

			if ( !array_key_exists('lat',$result) ) return;
			$lat = $result['lat'];
			$lon = $result['lon'];
		}

		// do the insert in db
		$table = $wpdb->prefix . "wpmap"; 

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

	} // if city and country are not empty or 

} // END geocoding script

// Delete row in wp_wpmap when a post is deleted
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

// get map function and shortcode parameters
$parameters = array(
	// query filters
	"post_type" => '',
	"post_status" => 'publish',
	"post_in" => '',
	"post_not_in" => '',
	"meta_key" => '',
	"meta_value" => '',
	"term_slug" => '',
	// layers control
	"layers_by" => '',
	"layers" => '',
	"colors" => '',
	"icons" => '',
	"default_color" => '#000',
	'default_icon' => 'icon.png',
	// map config
	"center_lat" => WPMAP_MAP_LAT,
	"center_lon" => WPMAP_MAP_LON,
	"zoom_ini" => WPMAP_INI_ZOOM,
	"zoom_min" => WPMAP_MIN_ZOOM,
	"zoom_max" => WPMAP_MAX_ZOOM,
	"map_width" => '100%',
	"map_height" => '300px',
	// popup
	"popup_text" => '',
	"popup_max_width" => '300',
	"popup_max_height" => '200',
	// markers
	'marker_type' => 'circle', // [circle|icon]
	'marker_radius' => '15',
	'marker_opacity' => '0.8',
	'marker_fillOpacity' => '0.8',
	'icon_width' => WPMAP_ICON_WIDTH,
	'icon_height' => WPMAP_ICON_HEIGHT,
	// activate geosearch on click
	'geosearch' => 1,
	// activate geosearch on click
	'sidebar' => 1
);

// wpmap shortcode
add_shortcode('wpmap', 'wpmap_shortcode');
function wpmap_shortcode($atts) {
	wpmap_register_load_scripts();
	global $parameters;
	extract( shortcode_atts( $parameters, $atts ) );

	wpmap_showmap($atts);

} // END shortcode

// show map function
function wpmap_showmap( $args ) {
	wpmap_register_load_scripts();

	global $parameters;

	foreach ( $parameters as $k => $param ) {
		if ( !array_key_exists($k,$args) ) { $args[$k] = $param; }
		if ( $k == 'layers' || $k == 'colors' ) {
			$args[$k] = "'".str_replace(",","','",$args[$k])."'";
		} elseif ( $k == 'icons' && $param != '' ) {
			$args[$k] = "'".WPMAP_ICONS_PATH.str_replace(",","','".WPMAP_ICONS_PATH,$args[$k])."'";
		}
	}

	// TO DO: width and height styles are overwriten by Leaflet, so they don't work
	$map_style = "";
	if ( $map_width != '' ) { $map_style .= "width:".$map_width.";"; }
	if ( $map_height != '' ) { $map_style .= "height:".$map_height.";"; }
	if ( $map_style != '' ) { $map_style = " style='".$map_style."'"; }

	if ( $geosearch == '1' )
		wpmap_geosearch();

	$sidebar_out = ( $args['sidebar'] == 1 ) ? '<div id="wpmap-sidebar"></div>' : '';

	$the_map = "
		<div id='map'".$map_style."></div>
		".$sidebar_out."
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
		var icons = [{$args['icons']}];
		var defaultColor = '{$args['default_color']}';
		var defaultIcon = '".WPMAP_ICONS_PATH."{$args['default_icon']}';
		var centerLat = '{$args['center_lat']}';
		var centerLon = '{$args['center_lon']}';
		var initialZoomLevel = {$args['zoom_ini']};
		var minZoomLevel = {$args['zoom_min']};
		var maxZoomLevel = {$args['zoom_max']};
		var popupText = '{$args['popup_text']}';
		var popupMaxWidth = '{$args['popup_max_width']}';
		var popupMaxHeight = '{$args['popup_max_height']}';
		var markerType = '{$args['marker_type']}';
		var markerRadius = '{$args['marker_radius']}';
		var markerOpacity = '{$args['marker_opacity']}';
		var markerFillOpacity = '{$args['marker_fillOpacity']}';
		var iconWidth = '{$args['icon_width']}';
		var iconHeight = '{$args['icon_height']}';
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
	} else {
		$extra_join = '';
	}

	// MAP LAYERS
	if (array_key_exists('layers_by', $_GET) ) {
		$layers_by = sanitize_text_field($_GET['layers_by']); // possible values: post_type, post_status, meta_key, meta_value, term_slug
		if ( $layers_by == 'term_slug' ) { $layer_by = "slug"; }
		else { $layer_by = $layers_by; }
	} else {
		$layer_by = "";
	}

	// FIELDS IN POPUP
	if (array_key_exists('popup_text', $_GET) ) {
		$popup_text = sanitize_text_field($_GET['popup_text']); // values: content, excerpt
		//$popup_author = sanitize_text_field($_GET['popup_author']); // values: name
		//$popup_date = sanitize_text_field($_GET['popup_date']); // values: 
		//$popup_img = sanitize_text_field($_GET['popup_image']); // values: featured

	} else {
		$popup_text = "";
	}
		
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
	global $wpmap_fields_in_map;
	$response = array(); // place to store the geojson result
	$features = array(); // array to build up the feature collection
	$response['type'] = 'FeatureCollection';
	
	foreach ( $query_results as $row ) {
		$lat = $row['lat'];
		$lon = $row['lon'];
	
		$prop=array();
		$prop['id'] = $row['ID'];
		// permalink
		$prop['perma'] = get_permalink($row['ID']);
		// title
		if ( array_key_exists('post_title',$wpmap_fields_in_map) ) {
//			$prop['post_title']['value'] = get_the_title($row['ID']);
			$f = $wpmap_fields_in_map['post_title'];
			if ( $f[4] == 1 ) { $v = '<a href="'.$prop['perma'].'">'.get_the_title($row['ID']).'</a>'; }
			else { $v = get_the_title($row['ID']); }
			$prop[$f[0]][$f[1]] = $f[2].$v.$f[3];
		}
		// content
		if ( array_key_exists('post_content',$wpmap_fields_in_map) ) {
			$f = $wpmap_fields_in_map['post_content'];
			if ( $popup_text == 'excerpt' ) { $post_desc = wp_trim_words( $row['post_content'], 55 ); }
			else { $post_desc = $row['post_content']; }
			//$prop['desc'] = apply_filters('the_content', utf8_encode($post_desc));
//			$prop['post_content']['value'] = apply_filters('the_content', $post_desc);
			$prop[$f[0]][$f[1]] = '<div class="popup-desc">'.apply_filters('the_content', $post_desc).'</div>';
		}
		// featured image
		if ( array_key_exists('featured_image',$wpmap_fields_in_map) && has_post_thumbnail($row['ID']) ) {
			$f = $wpmap_fields_in_map['featured_image'];
			$fid = get_post_thumbnail_id($row['ID']);
			$fimg = wp_get_attachment_image_src( $fid,$f[2] );
//			$prop['featured_image']['value'] = $fimg[0];
			$prop[$f[0]][$f[1]] = '<img src="'.$fimg[0].'" alt="'.get_the_title($fid).'" />';
		}
		// taxonomies
		foreach ( $wpmap_fields_in_map['taxonomies'] as $tax_id => $tax ) {
			$ts = get_the_terms($row['ID'],$tax_id);
			if ( $ts === false ) continue;
			$ts_out = array();
			foreach ( $ts as $t ) {
				$ts_out[] = $tax[4].$t->name.$tax[5];
			}
//			$prop['taxonomies'][$tax_id]['value'] = implode(', ',$ts_out);
			$prop[$tax[0]][$tax[1]] = $tax[2].implode(' ',$ts_out).$tax[3];
		}
		// custom fields
		foreach ( $wpmap_fields_in_map['custom_fields'] as $k => $cf ) {
//			$prop['custom_fields'][$k]['value'] = get_post_meta($row['ID'],$k,true);
			$prop[$cf[0]][$cf[1]] = $cf[2].get_post_meta($row['ID'],$k,true).$cf[3];
			
		}
//		// where and order values for each field
//		foreach ( $wpmap_fields_in_map as $k => $g ) {
//			$prop[$k]['where'] = $g[0];
//			$prop[$k]['order'] = $g[1];
//			if ( $k == 'taxonomies' || $k == 'custom_fields' ) {
//				foreach ( $g as $id => $f ) {
//					$prop[$k][$id]['where'] = $f[0];
//					$prop[$k][$id]['order'] = $f[1];
//				}
//			}
//		}
		// icon
		if ( $layer_by != '' ) { $prop['layer'] = $row[$layer_by]; }
		$icon_data = get_post_meta($row['ID'],WPMAP_ICON,true);
		$prop['icon'] = $icon_data['guid'];

		
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

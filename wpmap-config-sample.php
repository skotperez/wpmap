<?php
/* EDIT THIS VARS TO CONFIG THE PLUGIN */

// MAP CONTENT
// Which contents show in map
//// 

$wpmap_lat = "lat"; // custom field that stores latitude
$wpmap_lon = "lon"; // custom field that stores longitude
$wpmap_country = "country"; // custom field that stores country
$wpmap_city = "city"; // custom field that stores city
$wpmap_state = "state"; // custom field that stores state
$wpmap_street = "streetname"; // custom field that stores street name
$wpmap_number = "housenumber"; // custom field that stores house number
$wpmap_code = "postalcode"; // custom field that stores postal code
$wpmap_icon = 'icon'; // custom field that stores icon for each content
$wpmap_icon_width = '47'; // default width for map icons
$wpmap_icon_height = '64'; // default height for map icons
$wpmap_fields_in_map = array(
	// order must be a number between 1 and n
	// where allowed values [figure|header|body|footer]
	'post_title' => array('header','1','<h3>','</h3>',1),// array('where','order','HTML before','HTML after')
	'post_content' => array('body','1'),// array('where','order')
	'featured_image' => array('figure','1','large'),// array('where','order','wordpress image size')
	'taxonomies' => array(
		// 'tax_id' => array('where','order','HTML before','HTML after','HTML before each term','HTML after each term')
		'tax1_id' => array('body','2','<div class="popup-tax1">','</div>','<span>','</span>'),
		'tax2_id' => array('footer','1','<div class="popup-tax2">','</div>','<span>','</span>'),
	),
	'custom_fields' => array(
		// 'custom_field_id' => array('where','order','HTML before','HTML after')
		'cfield1_id' => array('body','3','<div class="popup-cfield1">','</div>'),
		'cfield2_id' => array('body','4','<div class="popup-cfield2">','</div>'),
		'cfield3_id' => array('footer','3','<div class="popup-cfield3">','</div>'),
	)
);


// MAP SETUP
//// 

$default_start_lat = '42.863690'; // default latitude for map center
$default_start_lon = '1.200625'; // default longitude for map center
$default_zoom_level = 10; // default initial zoom level: between 1 and 19
$default_min_zoom = 5; // default minimal (farest) zoom level: between 1 and 19
$default_max_zoom = 19; // default maximal (closer) zoom level: between 1 and 19
$wpmap_icons_path = plugins_url( 'images/' , __FILE__); // path for marker icons


// LEAFLET MODULES
//// 

// Geosearch Leaflet module
$geosearch_enabled = 1; // 1 to enable L.GeoSearch leaflet plugin. 0 to disable it
$geosearch_echoed = 1; // 1 to echo lat/lon values in a popup. 0 to disable the popup
$geosearch_lat_id = "geosearch-lat"; // DOM element id to get lat value to use in PHP
$geosearch_lon_id = "geosearch-lon"; // DOM element id to get lon value to use in PHP
// Sidebar Leaflet module
$sidebar_enabled = 1; // 1 to enable L.Control.Sibar leaflet plugin. 0 to disable it

/* STOP EDIT */
?>

<?php
/* EDIT THIS VARS TO CONFIG THE PLUGIN */
$wpmap_lat = "lat"; // custom field that stores latitude
$wpmap_lon = "lon"; // custom field that stores longitude
$wpmap_country = "country"; // custom field that stores country
$wpmap_city = "city"; // custom field that stores city
$wpmap_state = "state"; // custom field that stores state
$wpmap_street = "streetname"; // custom field that stores street name
$wpmap_number = "housenumber"; // custom field that stores house number
$wpmap_code = "postalcode"; // custom field that stores postal code
$wpmap_icon = 'icon'; // custom field that stores icon for each content
$default_start_lat = '42.863690'; // default latitude for map center
$default_start_lon = '1.200625'; // default longitude for map center
$default_zoom_level = 10; // default initial zoom level: between 1 and 19
$default_min_zoom = 5; // default minimal (farest) zoom level: between 1 and 19
$default_max_zoom = 19; // default maximal (closer) zoom level: between 1 and 19
$wpmap_icons_path = plugins_url( 'images/' , __FILE__); // path for marker icons
// geosearch module
$geosearch_enabled = 1; // 1 to enable L.GeoSearch leaflet plugin. 0 to disable it
$geosearch_echoed = 1; // 1 to echo lat/lon values in a popup. 0 to disable the popup
$geosearch_lat_id = "geosearch-lat"; // DOM element id to get lat value to use in PHP
$geosearch_lon_id = "geosearch-lon"; // DOM element id to get lon value to use in PHP
/* STOP EDIT */
?>

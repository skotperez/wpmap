<?php
/* EDIT THIS VARS TO CONFIG THE PLUGIN */
$wpmap_city = "city"; // the custom field that stores city
$wpmap_country = "country"; // the custom field that stores country
$default_pt = "post"; // default post type to show in the map
$default_start_lat = '42.863690'; // default latitude for map center
$default_start_lon = '1.200625'; // default longitude for map center
$default_zoom_level = 10; // default initial zoom level: between 1 and 19
$default_min_zoom = 5; // default minimal (farest) zoom level: between 1 and 19
$default_max_zoom = 19; // default maximal (closer) zoom level: between 1 and 19
$wpmap_layer_groups = array(
	"custom_field_key_1",
	"custom_field_key_2",
	"custom_field_key_3",
); // custom field keys that store the different values
//$default_map_layers = "'local','regional','national','international'"; // default layers: respect " and '. no limit
//$default_layers_colors = "'#00ff00','#ffff00','#0000ff','#ff0000'"; // default color for each layer above, in order.
/* STOP EDIT */
?>

wpmap
=====

wpmap plugin let you show in a map posts and other custom post types.

wpmap use OpenStreetMaps and Leaflet.

This is a very alpha version of wpmap. Things to be considered:

+ Many things cannot work.
+ This plugin may not suite your needs.
+ To make it works you may need to make any changes in your theme.

## Basic configuration

+ Upload the plugin to your plugins folder and activate it.
+ Set up the variables in the begining of wpmap/wpmap.php file.
+ Customize the styles of the map in wpmap/style/map.css

## Showing the map with the shortcode
If you to show the map inside the content of your post or pages, you can include the wpmap shortcode:

`[wpmap]`

You can pass the following attributes, all of them optional, to the shortcode:

+ **pt**. Post type to show in the map. Default: *post*
+ **centerLat**. Latitude for the center of the map. Default: *42.863690*
+ **centerLon**. Longitude for the center of the map. Default: *1.200625*
+ **initialZoomLevel**. Initial zoom level: between 1 and 19. Default: *10
+ **minZoomLevel**. Minimal (farest) zoom level: between 1 and 19. Default: *5*
+ **maxZoomLevel**. Maximal (closer) zoom level: between 1 and 19. Default: *19*
+ **layers**. Layers in the map: respect '. No limit. Default: *'local','regional','national','international'* 
+ **colors**. Color for each layer above, in order. Default: *'#00ff00','#ffff00','#0000ff','#ff0000'*

The shortcode with some attributes:

`[wpmap pt="page" layers="'parent','child'" colors="'red','blue'"]`

## Showing the map with the function
If you want to show the map in any place of your theme, other than the content of a post or page, you can use the wpmap function:

`wpmap_showmap();`

This functions accepts the same parameters than the shorcode above. All of them are optional. For example:

`$args = array(
	'layers' => "'local','national'",
	'colors' => "'red','blue'"
);`
`wpmap_showmap($args);`

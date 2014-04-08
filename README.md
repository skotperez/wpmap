wpmap
=====

Plugin to integrate Leaflet maps in WordPress.

This is a very alpha version in current development. Things to be considered:

+ Many things cannot work.
+ This plugin may not suite your needs.
+ To make it works you may need to make any changes in your theme.

## Basic installation

+ Upload the plugin to your plugins folder and activate it.
+ Create a div with the id "map" where you want to show the map.
+ Change map width and height in wpmap/style/map.css
+ Change CloudMade API key in wpmap/js/map.js
+ Change start var value in wpmap/js/map.js to set up center point of the map.
+ Change zoom var value in wpmap/js/map.js to set up the initial map zoom.
+ Edit WPMAP_CITY and WPMAP_CONUNTRY vars in wpmap.php to suite your theme custom fields or terms. 

## ToDo

+ Make shortcode to include the map in templates.

wpmap
=====

wpmap plugin let you show in a map posts and other custom post types.

wpmap use OpenStreetMaps and Leaflet.

This is a very alpha version of wpmap. Things to be considered:

+ Many things cannot work.
+ This plugin may not suite your needs.
+ To make it works you may need to make any changes in your theme.

## Basic configuration

### Install wpmap plugin
+ Upload the plugin to your plugins folder and activate it.

### Set up your WordPres to show the map
+ **Create a div with the id "map"**.
  - You can include the div directly in any template
  - You can include the div in any post or page content
+ **Edit wpmap.php plugin file to suit wpmap to your theme**.
  - change WPMAP_CITY to your city custom field
  - change WPMAP_CONUNTRY to your country custom field
  - change WPMAP_LAYER to your layer custom field. 

### Config how your map is shown
+ **Customize the styles of the map in wpmap/style/map.css**.
  - Change map width and height as you want.
  - Width and height properties are mandatory.
+ **Edit configuration variables in the begining of wpmap/js/map.js file**.

## ToDo

+ Make shortcode to include the map in templates and post content.

wpmap
=====

Latest version: 0.3 (this version is not compatible with the previous).

wpmap plugin lets you show in a map posts and other custom post types in your Wordpress posts and pages.

wpmap uses OpenStreetMaps and Leaflet.

This is a very alpha version of wpmap. Things to be considered:

+ Many things cannot work.
+ This plugin may not suite your needs.
+ To make it work you may need to make changes in your theme.

## Basic configuration

+ Rename config file wpmap/wpmap-config-sample.php to wpmap-config.php.
+ Set up the variables in wpmap/wpmap-config.php file.
 + Set the name of the custom fields that will store info to georeference the posts: country, city, state, street name, house number and postal code. Just country and city are required to do the geocoding.
 + Set the default center and zoom level of the map.
+ Customize the styles of the map in wpmap/style/map.css if you want.
+ Upload the plugin to your plugins folder and activate it.

## How does it work?
Every time you save a post (or Custom Post Type) with the Custom Fields properly filled (in the default example: "city" and "country") the plugin will geocode the location (longitude, latitude,) and store it in the database.
Once the geocode has worked properly for a content and the location is stored (latitude and latitude) in the database you could make that content to appear in the map.

Trick: you can update all your posts at once from the WordPress post admin.
1. Select all your posts.
2. Select "Edit" and "Apply".
3. Click "update" without changing anything.

## Showing the map with the shortcode
If you want to show the map inside the content of your post or pages, you can use the wpmap shortcode:

`[wpmap]`

You can pass the following attributes, all of them optional, to the shortcode:

+ **Filters**. This group of attributes will limit the posts to show in the map and they work in a similar way to WordPress Loop.
 + **post_type**. Post types to show in the map. Default: *any*
 + **post_status**. Post statuses to show in the map. Default: *any*.
 + **post_in**. Post IDs to include in the map. Default: *all*.
 + **post_not_in**. Post IDs to exclude from the map. Default: *none*.
 + **meta_key**. List of custom meta keys to show in the map. Default: *all*.
 + **meta_values**. List of custom meta values to show in the map. It could be from different meta keys. Default: *all*.
 + **term_slug**. List of terms to show in the map. It could be from different taxonomies. Default: *all*.
+ **Layers**. This group of attributes will define the layers of markers in the map.
 + **layers_by**. The criteria to group the markers in layers. Possible values: post_type, post_status, meta_key, meta_value, term_slug. Default: *none*
 + **layers**. The layers to show in the map. Default: *none*
 + **colors**. The color for each layers above, in order. Default: *#000000*
 + **default_color**. The default color for layers. Default: *#000000*
+ **Map config**. This group of attributes will set up the map itself.
 + **center_lat**. Latitude for the center of the map. Default: *42.863690*
 + **center_lon**. Longitude for the center of the map. Default: *1.200625*
 + **zoom_ini**. Initial zoom level: between 1 and 19. Default: *10
 + **zoom_min**. Minimal (farest) zoom level: between 1 and 19. Default: *5*
 + **zoom_max**. Maximal (closer) zoom level: between 1 and 19. Default: *19*
 + **map_width**. Map container width. It has to include units (px, %, em). Default: *100%*
 + **map_height**. Map container height. It has to include units (px, %, em). Default: *500px*
+ **Popup content**. This group of attributes will define the info to show in the marker popup.
 + **popup_text**. Possible values: content or excerpt. Default: *content*.
 + **popup_max_width**. Max width of the popup, without units. Default: *300*.
 + **popup_max_height**. Max height of the popup, without units. Default: *300*.

Filter are not all of them compatible:
+ post_in must to be alone.
+ term_slug is not compatible with meta_value or meta_key.
+ meta_key and meta_value will not work together.

### Examples

`[wpmap post_status="publish" post_post_type="post,page" post_not_in="12,18" layers_by="post_type" layers="post,page" colors="#f00,#0f0" center_lat="40.416705" center_lon="-3.703582"]`

It will display all publish posts from 'post' and 'page' post types, except posts with ID 12 and 18. It will create two layers, one for posts, one for pages. Markers in posts layer will be red and markers in pages layer will be green. The map will be center in Madrid.

## Showing the map with the function
If you want to show the map in any place of your theme, other than the content of a post or page, you can use the wpmap function:

`wpmap_showmap();`

This functions accepts the same parameters than the shorcode above, and they can be passed as an associative array to the function. All of them are optional. For example:

`$args = array(
	'post_status' => "publish",
	'layers_by' => "post_type",
	'layers' => "post,page,book",
	'colors' => "#f00,#0f0,#0ff",
	'center_lat' => "40.417",
	'center_lon' => "-3.704",
	'zoom_ini' => "6"
);`
`wpmap_showmap($args);`

This function will show a map of Spain center in Madrid with all the publish contents, separated in three layers by post type: red layers for posts, green layer for pages and cyan layer for books.

## What you cannot do with this plugin
+ Insert a map in a popup. Because of that if you use wpmap shortcode in the content of a post that you'll see in other map, the plugin will not work.
+ Show more than one selected post types in the same map. You can show just one post type or all of them.

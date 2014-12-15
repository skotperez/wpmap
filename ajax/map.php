<?php
/*
 * returns map points as geojson 
 */

// get the server credentials
$idb= $_SERVER['DOCUMENT_ROOT']."/wp-config.php";
require_once($idb);

if (array_key_exists('bbox', $_GET) ) { $bbox = sanitize_text_field($_GET['bbox']); }
else {
	// invalid request
	$ajxres=array();
	$ajxres['resp']=4;
	$ajxres['dberror']=0;
	$ajxres['msg']='missing bounding box';
	sendajax($ajxres);
}
// split the bbox into it's parts
list($left,$bottom,$right,$top)=explode(",",$bbox);

// SQL QUERY extra param.
$ptype = sanitize_text_field($_GET['post_type']);
$pstatus = sanitize_text_field($_GET['post_status']);
$pin = sanitize_text_field($_GET['post_in']);
$pnotin = sanitize_text_field($_GET['post_not_in']);
$mkeys = sanitize_text_field($_GET['meta_key']);
$mvalues = sanitize_text_field($_GET['meta_value']);
$tslugs = sanitize_text_field($_GET['term_slug']);
$filters = array(
	'post_type' => array('values'=>$ptype,'table'=>'p'),
	'post_status' => array('values'=>$pstatus,'table'=>'p'),
	'ID' => array('values'=>$pin,'table'=>'p'),
	'post_id' => array('values'=>$pnotin,'table'=>'m'),
	'meta_key' => array('values'=>$mkeys,'table'=>'pm'),
	'meta_values' => array('values'=>$mvalues,'table'=>'pm'),
	'slug' => array('values'=>$tslugs,'table'=>'t'),
);

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

if ( $mkeys != '' || $mvalues != '' ) { // if meta keys or meta values filters
	$table_postmeta = $wpdb->prefix."postmeta";
	$extra_join = "
	INNER JOIN $table_postmeta pm
	  ON m.post_id = pm.post_id
	";
	$extra_field .= ", pm.meta_value, pm.meta_key";

} elseif ( $tslugs != '' ) { // if terms filters
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
	$extra_field .= ", t.name";

} else { $extra_join = ''; }

// LAYERS
$layers_by = sanitize_text_field($_GET['layers_by']); // possible values: post_type, post_status, meta_key, meta_value, term_slug
if ( $layers_by == 'term_slug' ) { $layer_by = "name"; }
else { $layer_by = $layers_by; }

// FIELDS IN POPUP
$popup_text = sanitize_text_field($_GET['popup_text']); // values: content, excerpt
//$popup_author = sanitize_text_field($_GET['popup_author']); // values: name
//$popup_date = sanitize_text_field($_GET['popup_date']); // values: 
//$popup_img = sanitize_text_field($_GET['popup_image']); // values: featured

// open the database
try { $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=' .DB_CHARSET, DB_USER, DB_PASSWORD); }
catch(PDOException $e) {
	// send the PDOException message
	$ajxres=array();
	$ajxres['resp']=40;
	$ajxres['dberror']=$e->getCode();
	$ajxres['msg']=$e->getMessage();
	sendajax($ajxres);
}

global $wpdb;
$table_map = $wpdb->prefix."wpmap";
$table_posts = $wpdb->prefix."posts";
try {
	$sql="
	SELECT
	  m.lat,
	  m.lon,
	  p.ID,
	  $extra_field
	FROM $table_map m
	INNER JOIN $table_posts p
	  ON m.post_id = p.ID
	$extra_join
	WHERE m.lon>=:left AND m.lon<=:right
	  AND m.lat>=:bottom AND m.lat<=:top
	  $extra_where
	";

	$stmt = $db->prepare($sql);
	$stmt->bindParam(':left', $left, PDO::PARAM_STR);
	$stmt->bindParam(':right', $right, PDO::PARAM_STR);
	$stmt->bindParam(':bottom', $bottom, PDO::PARAM_STR);
	$stmt->bindParam(':top', $top, PDO::PARAM_STR);
	$stmt->execute();

} catch(PDOException $e) {
	// send the PDOException message
	$ajxres=array();
	$ajxres['resp']=40;
	$ajxres['dberror']=$e->getCode();
	$ajxres['msg']=$e->getMessage();
	sendajax($ajxres);
}

$ajxres=array(); // place to store the geojson result
$features=array(); // array to build up the feature collection
$ajxres['type']='FeatureCollection';

// go through the list adding each one to the array to be returned	
//$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
$ajxres['features']=$features;
// tidy up the DB
$db = null;
sendajax($ajxres); // no return from there
function sendajax($ajx) {
	// encode the ajx array as json and return it.
	$encoded = json_encode($ajx);
	exit($encoded);
}
//wp_send_json($ajxres);
?>

<?php
/*
 * returns map points as geojson 
 */

// it is not necessary, already loaded
//require_once($_SERVER['DOCUMENT_ROOT']."/wp-load.php");
//require($_SERVER['DOCUMENT_ROOT']."/wp-load.php");

// uncomment below to turn error reporting on
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// get the server credentials
$idb= $_SERVER['DOCUMENT_ROOT']."/wp-config.php";
require_once($idb);

if (array_key_exists('bbox', $_GET) ) {
	$bbox = sanitize_text_field($_GET['bbox']);

} else {
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

//} elseif ( $taxs != '' ) { // if taxonomies filters
//	$table_term_rel = $wpdb->prefix."term_relationships";
//	$table_term_tax = $wpdb->prefix."term_taxonomy";
//	$extra_join = "
//	INNER JOIN $table_term_rel tr
//	  ON m.post_id = tr.object_id
//	INNER JOIN (
//		SELECT term_taxonomy_id, taxonomy FROM $table_term_tax LIMIT 1) AS tt
//	  ON tr.term_taxonomy_id = tt.term_taxonomy_id
//	";
////SELECT IDadd,MIN(Name) Name FROM TABLE2 GROUP BY IDadd) AS B
////ON B.IDadd = A.ID
////	  ON tr.term_taxonomy_id = (
////		SELECT tt.taxonomy FROM $table_term_tax tt
////		WHERE tt.term_taxonomy_id = $table_term_rel.term_taxonomy_id
////		LIMIT 1
////	)
////
////
////SELECT ID,MIN(COUNTRY) FROM TABLE1 A
////LEFT JOIN TABLE2 B ON A.ID=B.IDadd
////GROUP BY ID
//	  //ON tr.term_taxonomy_id = tt.term_taxonomy_id
////	$extra_field .= ", MIN(tt.taxonomy)"; // to get just one result
//	$extra_field .= ", tt.taxonomy";

} else { $extra_join = ''; }

// LAYERS
$layers_by = sanitize_text_field($_GET['layers_by']); // possible values: post_type, post_status, meta_key, meta_value, term_slug
if ( $layers_by == 'term_slug' ) { $layer_by = "name"; }
else { $layer_by = $layers_by; }

// FIELDS IN POPUP
// possible values: title, content, permalink, featured_image
$popup_header = sanitize_text_field($_GET['popup_header']);
$popup_body = sanitize_text_field($_GET['popup_body']);
$popup_footer = sanitize_text_field($_GET['popup_footer']);
if ( $popup_header == '' ) { $popup_header = "title,permalink"; }
$popup_fields = explode(",",trim($popup_header));

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
//	$count++;
//	echo "<pre>";
//		echo "<strong>";
//	echo $count;
//	echo "</strong><br>";
//	print_r($row);
//	echo "</pre>";

	$lat = $row['lat'];
	$lon = $row['lon'];

	
	$prop=array();
	$prop['tit'] = get_the_title($row['ID']);
	$prop['perma'] = get_permalink($row['ID']);
	$post_desc = substr($row['post_content'], 0, 55 )."...";
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

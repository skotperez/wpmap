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
// fields to get
$fields_extra = "p.post_title, p.post_content";
// build sql query extra parameters
$ptype = sanitize_text_field($_GET['post_type']);
$pstatus = sanitize_text_field($_GET['post_status']);
$mkeys = sanitize_text_field($_GET['meta_key']);
$mvalues = sanitize_text_field($_GET['meta_value']);
$extras = array(
	'post_type' => array('values'=>$ptype,'table'=>'p'),
	'post_status' => array('values'=>$pstatus,'table'=>'p'),
	'meta_key' => array('values'=>$mkeys,'table'=>'pm'),
	'post_status' => array('values'=>$mvalues,'table'=>'pm'),
);

$sql_extras = "";
foreach ( $extras as $colum => $extra ) {
	if ( $extra['values'] != '' ) {
		$sql_extra = " AND {$extra['table']}.$colum IN (";
		foreach ( explode(",",$extra['values']) as $value ) {
			$sql_extra .= "'$value', ";
		}
		$sql_extra = substr($sql_extra, 0, -2);
		$sql_extra .= ")";

	} else { $sql_extra = ""; }
	$sql_extras .= $sql_extra;

} // end foreach extra sql parametres

if ( $mkeys != '' || $mvalues != '' ) {
	$table_postmeta = $wpdb->prefix."postmeta";
	$pm_join = "INNER JOIN $table_postmeta pm ON m.post_id = pm.post_id";
	$fields_extra .= ", pm.meta_value";
} else { $pm_join = ''; }


if ( array_key_exists('layers', $_GET) ) { $layers = sanitize_text_field($_GET['layers']); } else { $layers = ""; }

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
	  $fields_extra
	FROM $table_map m
	INNER JOIN $table_posts p
	  ON m.post_id = p.ID
	$pm_join
	WHERE m.lon>=:left AND m.lon<=:right
	  AND m.lat>=:bottom AND m.lat<=:top
	  $sql_extras
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
//	echo "<pre>";
//echo $sql;
//	echo "</pre>";
$ajxres=array(); // place to store the geojson result
$features=array(); // array to build up the feature collection
$ajxres['type']='FeatureCollection';


// go through the list adding each one to the array to be returned	
//$table_posts = $wpdb->prefix."posts";
$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
//$count++;
//	echo "<pre>";
//		echo "<strong>";
//echo $count;
//	echo "</strong><br>";
//	print_r($row);
//	echo "</pre>";
	$post_layer = 'nacional';

	$lat = $row['lat'];
	$lon = $row['lon'];

//	$post_id = $row['ID'];
	$post_tit = $row['post_title'];
//	$post_perma = get_permalink($post_id);
	$post_desc = apply_filters('the_content', $row['post_content']);
//	$post_meta_value = $row['meta_value'];
	$post_meta_value = "";
	$prop=array();
//	$prop['plaqueid'] = $post_id;
	//$prop['plaquedesc'] = "<h3><a href='" .$post_perma. "' title='" .$post_tit. "' rel='bookmark'>" .$post_tit. "</a></h3>" .$post_desc;
	$prop['plaquedesc'] = $post_tit. " " .$post_meta_value;
	//$prop['colour'] = $post_layer;

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

//	echo "<pre>";print_r($f);echo "</pre>";
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
?>

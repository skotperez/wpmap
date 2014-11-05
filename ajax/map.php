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

// build sql query extra parameters
if ( array_key_exists('pt', $_GET) ) { $pt = sanitize_text_field($_GET['pt']); } else { $pt = ""; }
if ( array_key_exists('layers', $_GET) ) { $layers = sanitize_text_field($_GET['layers']); } else { $layers = ""; }
if ( array_key_exists('groups', $_GET) ) { $groups = sanitize_text_field($_GET['groups']); } else { $groups = ""; }
$extras = array(
	'post_type' => $pt,
	'colour' => $layers,
	'layer_group' => $groups
);

$sql_extras = "";
foreach ( $extras as $colum => $extra ) {
//if ( $pt != '' ) { $sql_pt = " AND post_type='$pt'"; }
//else { $sql_pt = ""; }

if ( $extra != '' ) {
	$sql_extra = " AND $colum IN (";
	foreach ( explode(",",$extra) as $layer ) {
		$sql_extra .= "'$layer', ";
	}
	$sql_extra = substr($sql_extra, 0, -2);
	$sql_extra .= ")";
} else { $sql_extra = ""; }

$sql_extras .= $sql_extra;
} // end foreach extra sql parametres

// open the database
try {
	$db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=' .DB_CHARSET, DB_USER, DB_PASSWORD);
} catch(PDOException $e) {
	// send the PDOException message
	$ajxres=array();
	$ajxres['resp']=40;
	$ajxres['dberror']=$e->getCode();
	$ajxres['msg']=$e->getMessage();
	sendajax($ajxres);
}

	global $wpdb;
	$table = $wpdb->prefix."wpmap";
try {
	$sql="SELECT post_id,lat,lon,colour,layer_group FROM $table WHERE lon>=:left AND lon<=:right AND lat>=:bottom AND lat<=:top AND post_status='publish'$sql_extras";
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
$table_posts = $wpdb->prefix."posts";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

	$post_layer = $row['colour'];

	$lat = $row['lat'];
	$lon = $row['lon'];

	$post_id = $row['post_id'];
	$dbquery = "SELECT * FROM $table_posts WHERE ID = $post_id";
	$post_info = $wpdb->get_row($dbquery,ARRAY_A);

	$post_tit = $post_info['post_title'];
	$post_perma = get_permalink($post_id);
	$post_desc = apply_filters('the_content', $post_info['post_content']);

	$prop=array();
	$prop['plaqueid']=$row['post_id'];
	//$prop['plaquedesc']='This description is not dynamic yet.';
	//$prop['plaquedesc'] = "<h3>" .$post_info['post_title']. "</h3>" .$post_info['post_content'];
	$prop['plaquedesc'] = "<h3><a href='" .$post_perma. "' title='" .$post_tit. "' rel='bookmark'>" .$post_tit. "</a></h3>" .$post_desc;

	$prop['colour']=$post_layer;

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
//	$ajxres[$post_layer]['features'][] = $f;
	

}
	
// add the features array to the end of the ajxres array
 $ajxres['features']=$features;
// tidy up the DB
$db = null;
sendajax($ajxres); // no return from there

function sendajax($ajx) {
	// encode the ajx array as json and return it.
	$encoded = json_encode($ajx);
//	$encoded = "var groupsToLayers = [" .json_encode($ajx). "];";
	exit($encoded);
}
?>

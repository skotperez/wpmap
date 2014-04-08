<?php
// it is not necessary, already loaded
//require_once($_SERVER['DOCUMENT_ROOT']."/wp-load.php");

// uncomment below to turn error reporting on
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

/*
 * ajxplaque.php
 * returns plaque points as geojson 
 */

// get the server credentials from a shared import file
//$idb= $_SERVER['DOCUMENT_ROOT']."/db.php";
$idb= $_SERVER['DOCUMENT_ROOT']."/wp-config.php";
//include $idb;
require_once($idb);

if (isset($_GET['bbox'])) {
	$bbox=$_GET['bbox'];
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

// open the database
try {
//	$db = new PDO('mysql:host='.$dbhost.';dbname='.$dbname.';charset=utf8', $dbuser, $dbpass);
	$db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=' .DB_CHARSET, DB_USER, DB_PASSWORD);
} catch(PDOException $e) {
	// send the PDOException message
	$ajxres=array();
	$ajxres['resp']=40;
	$ajxres['dberror']=$e->getCode();
	$ajxres['msg']=$e->getMessage();
	sendajax($ajxres);
}

//	global $wpdb;
try {
	$sql="SELECT post_id,lat,lon,colour,imageid FROM wp_wpmap WHERE lon>=:left AND lon<=:right AND lat>=:bottom AND lat<=:top AND post_status='publish' ORDER BY colour";
//	$stmt = $wpdb->get_results($sql,ARRAY_A);
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
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

//	if ( $post_layer != $row['colour'] ) {
//		$post_layer = $row['colour'];
//		$ajxres[$post_layer] = array();
//		$ajxres[$post_layer] = array();
//		$ajxres[$post_layer]['type'] = 'FeatureCollection';
//	} else {

//	}

	$post_layer = $row['colour'];

	$lat = $row['lat'];
	$lon = $row['lon'];

	$post_id = $row['post_id'];
	$dbquery = "SELECT * FROM wp_posts WHERE ID = $post_id";
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
	//$prop['imageid']=$row['imageid'];

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

/* EDIT MAP VARS */

// center of the map
var centerLat = "42.863690";
var centerLon = "1.200625";

// zoom
var initialZoomLevel = 10; // between 1 and 19
var minZoomLevel = 5; // between 1 and 19
var maxZoomLevel = 19; // between 1 and 19

// layers names and colors
var pointLayers = [
	"local",
	"regional",
	"national",
	"international"
];
var pointColors = [
	"#00ff00",
	"#ffff00",
	"#0000ff",
	"#ff0000"
];
var pointDefaultColor = "#000000";
/* END EDIT MAP VARS */

/*global variables */
var map;	// global map object
var lyrOsm;	// the Mapnik base layer of the map
var lyrClm;	// the Mapnik base layer of the map
var lyrPlq;	// the geoJson layer to display plaques with

// when the whole document has loaded call the init function
jQuery(document).ready(init);

function init() {	

	// base layer
	var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';    
//	var clmUrl='http://{s}.tile.cloudmade.com/5d2e2d0008c6418f8cee12211e8abb7f/997/256/{z}/{x}/{y}.png';    
	var osmAttrib='Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
//	var clmAttrib='Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://cloudmade.com">CloudMade</a>';
	
	lyrOsm = new L.TileLayer(osmUrl, {
		minZoom: minZoomLevel,
		maxZoom: maxZoomLevel,
		attribution: osmAttrib
	});
//	lyrClm = new L.TileLayer(clmUrl, {
//		minZoom: 1, 
//		maxZoom: 19, 
//		attribution: clmAttrib
//	});

	function onEachFeature(feature, layer) {
		var popupContent = feature.properties.plaquedesc;

		layer.bindPopup(popupContent);
	}
	
	// a geojson layer
	lyrPlq = L.geoJson(null, {
		// marker style
		style: setIcon,

		// marker function
		pointToLayer: function (feature, ll) {
			//return L.marker(latlng);
			return L.circleMarker(ll);
		},

		// popup function
		onEachFeature: onEachFeature,
	});

	// set the starting location for the centre of the map
	var start = new L.LatLng(centerLat,centerLon);
	
	// create the map
	map = new L.Map('map', {		// use the div called map
		center: start,			// centre the map as above
		zoom: initialZoomLevel,		// start up zoom level
		layers: [lyrOsm,lyrPlq]		// layers to add 
	});

	// create a layer control and add the base layers
	var baseLayers = {
		"OpenStreetMap": lyrOsm,
		//"OSM + CloudMade": lyrClm
	};

	// add the overlays
	var overlays = {
		"GWP": lyrPlq,
	};

	// add the layers to a layer control
	// L.control.layers(baseLayers, overlays).addTo(map);
	
	// create the hash url on the browser address line
	var hash = new L.Hash(map);
	
	map.on('moveend', whenMapMoves);

	askForPlaques();

} // end init function

function whenMapMoves(e) {
	askForPlaques();
}

function setIcon(feature) {
	var pointStyle = {
			    radius: 18,
			    weight: 1,
			    opacity: 1,
			    fillOpacity: 0.8
	};
	jQuery.each( pointLayers, function ( i,layer ) {
		if (feature.properties.colour==layer) {
			colorHex = pointColors[i];
			pointStyle.fillColor = colorHex,
			pointStyle.color = colorHex
			return false; // this break out of the each
		}

	});
	if ( !pointStyle.color ) {
		pointStyle.color = pointDefaultColor;
		pointStyle.fillcolor = pointDefaultColor;
	}
	return pointStyle;
}

function askForPlaques() {
	var data='bbox=' + map.getBounds().toBBoxString();
	jQuery.ajax({
		url: 'wp-content/plugins/wpmap/ajax/map.php',
		dataType: 'json',
		data: data,
		success: showPlaques
	});
}

function showPlaques(ajxresponse) {
	lyrPlq.clearLayers();
	lyrPlq.addData(ajxresponse);
}

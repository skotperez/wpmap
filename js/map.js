/*
 * global variables
 */
var map;			// global map object
var lyrOsm;			// the Mapnik base layer of the map
var lyrClm;			// the Mapnik base layer of the map
var lyrPlq;			// the geoJson layer to display plaques with
	var local;
	var regional;
	var national;
	var international;

// when the whole document has loaded call the init function
jQuery(document).ready(init);

function init() {	

	// base layer
	var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';    
	var clmUrl='http://{s}.tile.cloudmade.com/5d2e2d0008c6418f8cee12211e8abb7f/997/256/{z}/{x}/{y}.png';    
	var osmAttrib='Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
	var clmAttrib='Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://cloudmade.com">CloudMade</a>';
	
	lyrOsm = new L.TileLayer(osmUrl, {
		minZoom: 1, 
		maxZoom: 19, 
		attribution: osmAttrib
	});
	lyrClm = new L.TileLayer(clmUrl, {
		minZoom: 1, 
		maxZoom: 19, 
		attribution: clmAttrib
	});

	function onEachFeature(feature, layer) {
		var popupContent = feature.properties.plaquedesc;

		layer.bindPopup(popupContent);
	}
	
//jQuery.each(askForPlaques(), function(index, value){
	

	// a geojson layer
	lyrPlq = L.geoJson(null, {
//	L.geoJson(null, {
		// marker style
//		style: pointStyle,
		style: setIcon,
		// marker function
//		pointToLayer: setIcon,
		pointToLayer: function (feature, ll) {
//			return L.marker(latlng);
			return L.circleMarker(ll);
		},
		// popup function
		onEachFeature: onEachFeature,
//		bindPopup(feature.properties.plaquedesc);

//		filter: function(feature, layer) {
//			if (feature.properties.colour=='local') {
//				var local = L.LayerGroup(layer);
//			} else if (feature.properties.colour=='regional') {
//				var regional = L.LayerGroup(layer);
//			} else if (feature.properties.colour=='national') {
//				var national = L.LayerGroup(layer);
//			} else if (feature.properties.colour=='international') {
//				var international = L.LayerGroup(layer);
//			}
//			switch (feature.properties.colour) {
//				case 'local': var local = L.LayerGroup(layer);
//				case 'regional': var regional = L.LayerGroup(layer);
//				case 'national': var national = L.LayerGroup(layer);
//				case 'international': var international = L.LayerGroup(layer);
//	     		}
//		}
	});

//});
	console.log(local);
	
	// set the starting location for the centre of the map
	var start = new L.LatLng(42.863690,1.200625);
	
	// create the map
	map = new L.Map('map', {		// use the div called map
		center: start,			// centre the map as above
		zoom: 14,			// start up zoom level
//		layers: [lyrOsm,lyrPlq]		// layers to add 
		layers: [lyrOsm]		// layers to add 
	});

	// create a layer control
	// add the base layers
	var baseLayers = {
		"OpenStreetMap": lyrOsm,
		"OSM + CloudMade": lyrClm
	};

	// add the overlays
	var overlays = {
		"GWP": lyrPlq,
//		"Local": local,
//		"Regional": regional,
//		"National": national,
//		"International": international
	};

	// add the layers to a layer control
	L.control.layers(baseLayers, overlays).addTo(map);
	
	// create the hash url on the browser address line
	var hash = new L.Hash(map);
	
	map.on('moveend', whenMapMoves);

	askForPlaques();

} // end init function

function whenMapMoves(e) {
	askForPlaques();
}

function setIcon(feature,ll) {
//	var colorHex;
	if (feature.properties.colour=='local') {
//		plq=L.marker(ll, {icon: greenicon});
		var colorHex = "#00ff00";
//		L.circleMarker(ll);
	}
	else if (feature.properties.colour=='regional') {
//		plq=L.marker(ll, {icon: blueicon});
		var colorHex = "#00ffff";
	}
	else if (feature.properties.colour=='national') {
//		plq=L.marker(ll, {icon: redicon});
		var colorHex = "#ff0000";
	}
	else if (feature.properties.colour=='international') {
//		plq=L.marker(ll, {icon: yellowicon});
		var colorHex = "#ffff00";
	}
//	plq=L.marker(ll);
//	plq.bindPopup(feature.properties.plaquedesc);
//	return plq;
	// style 
	var pointStyle = {
	    radius: 18,
	    fillColor: colorHex,
	    color: colorHex,
	    weight: 1,
	    opacity: 1,
	    fillOpacity: 0.8
	};
	console.log(pointStyle);
	return pointStyle;
}

function askForPlaques() {
	var data='bbox=' + map.getBounds().toBBoxString();
	jQuery.ajax({
		url: 'wp-content/plugins/wpmap/ajax/map.php',
		//url: 'map.php',
		dataType: 'json',
		data: data,
		success: showPlaques
	});
}

function showPlaques(ajxresponse) {
	lyrPlq.clearLayers();
	lyrPlq.addData(ajxresponse);
}

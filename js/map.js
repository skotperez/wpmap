/*global variables */
var map;	// global map object
var lyrOsm;	// the Mapnik base layer of the map
var lyrPlq;	// the geoJson layer to display plaques with

// when the whole document has loaded call the init function
//jQuery(document).ready(init);

//function init() {	

	// base layer
	var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';    
	var osmAttrib='Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
	
	lyrOsm = new L.TileLayer(osmUrl, {
		minZoom: minZoomLevel,
		maxZoom: maxZoomLevel,
		attribution: osmAttrib
	});

	// popup general styles
	var popupStyle = {
		maxWidth: popupMaxWidth,
		maxHeight: popupMaxHeight,
		autoPan: false
	}

	function onEachFeature(feature, layer) {
		var popupContent = "<h3><a href='" + feature.properties.perma + "'>" + feature.properties.tit + "</a></h3><div>" + feature.properties.desc + "</div>";
		//layer.bindPopup(popupContent,popupStyle);
		if( window.location.hash == '#'+layer.feature.properties.id ) {
			sidebar.setContent(popupContent);
			sidebar.show();
		}
		layer.on({
			click: prepareSidebar,
		});

	}

	// geojson layer
	lyrPlq = L.geoJson(null, {
		// marker style
		//style: setIcon
		// marker function
		pointToLayer: function (feature, ll) {
			if ( markerType == 'icon' ) {
				iconUrl = feature.properties.icon;
				if ( iconUrl == '' && icons != '' ) {
					jQuery.each( layers, function ( i,layer ) {
						if (feature.properties.layer==layer) {
							iconUrl = icons[i];
							return false; // this break out of the each
						}
					});
				}
				if ( iconUrl == '' ) { iconUrl = defaultIcon; }
				
				var iconStyle = new L.icon({
					iconUrl: iconUrl,
					//iconSize: [70, 96],
					iconAnchor: [22, 94],
					popupAnchor: [-3, -76],
					//shadowUrl: 'my-icon-shadow.png',
					//shadowSize: [68, 95],
					//shadowAnchor: [22, 94]
				});
				return L.marker(ll, {icon: iconStyle});

			} else {
				var pointStyle = {
				    radius: markerRadius,
				    weight: 1,
				    opacity: markerOpacity,
				    fillOpacity: markerFillOpacity
				};
			
				jQuery.each( layers, function ( i,layer ) {
					if (feature.properties.layer==layer) {
						colorHex = colors[i];
						pointStyle.fillColor = colorHex,
						pointStyle.color = colorHex
						return false; // this break out of the each
					}
				});
				if ( !pointStyle.color ) {
					pointStyle.color = defaultColor;
					pointStyle.fillcolor = defaultColor;
				}
				return L.circleMarker(ll,pointStyle);
			}
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
	};

	// add the overlays
	var overlays = {
		"GWP": lyrPlq,
	};

	// create the hash url on the browser address line
	var hash = new L.Hash(map);

	// sidebar
	var sidebar = L.control.sidebar('wpmap-sidebar', {
		closeButton: true,
		position: 'left',
		autoPan: false
	});
	map.addControl(sidebar);
//	setTimeout(function() {
//		sidebar.show();
//	}, 500);
map.on('click', function () {
	sidebar.hide();
	window.location.hash = '';
})
L.DomEvent.on(sidebar.getCloseButton(), 'click', function () {
	window.location.hash = '';
});
function sidebarContent(layer) {
	popupContent = "<h3><a href='" + layer.feature.properties.perma + "'>" + layer.feature.properties.tit + "</a></h3><div>" + layer.feature.properties.desc + "</div>";
	return popupContent;
}

function prepareSidebar(e) {
	var layer = e.target;
	//resetMarkersStyle(dataLayer);
	//layer.setStyle(styleClick);

//	if (!L.Browser.ie && !L.Browser.opera) {
//		layer.bringToFront();
//	}
	sidebar.setContent(sidebarContent(layer));
	if ( !sidebar.isVisible() ) {
		sidebar.toggle();
	}
	window.location.hash = layer.feature.properties.id;
}


	map.on('moveend', whenMapMoves);

	askForPlaques();

//} // end init function

function whenMapMoves(e) {
	askForPlaques();
}

//function setIcon(feature) {
//	var pointStyle = {
//		//iconUrl: 'http://huertos.wpmap.localhost/wp-content/uploads/sites/4/2017/06/icon.huerto.01.png',
//		iconUrl: feature.properties.icon,
//	    iconSize: [70, 96],
//	    iconAnchor: [22, 94],
//	    popupAnchor: [-3, -76],
//	    //shadowUrl: 'my-icon-shadow.png',
//	    //shadowSize: [68, 95],
//	    //shadowAnchor: [22, 94]
//	};
////	var pointStyle = {
////	    radius: markerRadius,
////	    weight: 1,
////	    opacity: markerOpacity,
////	    fillOpacity: markerFillOpacity
////	};
////
////	jQuery.each( layers, function ( i,layer ) {
////		if (feature.properties.layer==layer) {
////			colorHex = colors[i];
////			pointStyle.fillColor = colorHex,
////			pointStyle.color = colorHex
////			return false; // this break out of the each
////		}
////	});
////	if ( !pointStyle.color ) {
////		pointStyle.color = defaultColor;
////		pointStyle.fillcolor = defaultColor;
////	}
//	return pointStyle;
//}

function askForPlaques() {
	//var data='action=wpmap_get_map_data&bbox=' + map.getBounds().toBBoxString() + '&post_type=' + pType + '&post_status=' + pStatus + '&post_in=' + pIn + '&post_not_in=' + pNotIn + '&meta_key=' + mKeys + '&meta_value=' + mValues + '&term_slug=' + tSlugs + '&layers_by=' + layersBy + '&popup_text=' + popupText;
	var data = {
		action: 'wpmap_get_map_data',
		bbox: map.getBounds().toBBoxString(),
		post_type: pType,
		post_status: pStatus,
		post_in: pIn,
		post_not_in: pNotIn,
		meta_key: mKeys,
		meta_value: mValues,
		term_slug: tSlugs,
		layers_by: layersBy,
		popup_text: popupText
		//nonce: nonce
	}
	jQuery.ajax({
		url: ajaxUrl,
		dataType: 'json',
		data: data,
		success: showPlaques
	});

}

function showPlaques(ajxresponse) {
	lyrPlq.clearLayers();
	lyrPlq.addData(ajxresponse);
}

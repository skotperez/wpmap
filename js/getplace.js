// SEARCH: Reverse geoCoding
new L.Control.GeoSearch({
	provider: new L.GeoSearch.Provider.OpenStreetMap()
}).addTo(map);

// ADD COORDS CONTROL
var wpmapCoordsControl = L.Control.extend({
	options: {
		position: 'bottomleft'
	},
	onAdd: function (map) {
		// create the control container with a particular class name
		var container = L.DomUtil.create('div', 'wpmap-coords-control');

		// ... initialize other DOM elements, add listeners, etc.
		return container;
	}
});
map.addControl(new wpmapCoordsControl());

// COORDS MARKER
var marker;
function addCoordsToDOM(markerLat,markerLon) {

	if ( geosearchOptions.echo == 1 ) {
		if ( jQuery("#wpmap-coords-lat").length == 0) {
			jQuery('.wpmap-coords-control').append('<span id="wpmap-coords-lat"></span><br /><span id="wpmap-coords-lon"></span>');
		}
		jQuery('#wpmap-coords-lat').text( "LAT: " + markerLat );
		jQuery('#wpmap-coords-lon').text( "LON: " + markerLon );
	}
	jQuery('#'+geosearchOptions.lat_id).attr('value',markerLat);
	jQuery('#'+geosearchOptions.lon_id).attr('value',markerLon);

}
// get Lat Lon
function getCoords(e) {
	if( marker ) { map.removeLayer(marker); }
	marker = new L.Marker(e.latlng, {draggable:'true'}).addTo(map);
	markerLat = e.latlng.lat.toString();
	markerLon = e.latlng.lng.toString();
	addCoordsToDOM(markerLat,markerLon);
	marker.on('dragend', function(event){
		var marker = event.target;
		var position = marker.getLatLng();
		markerLat = position.lat;
		markerLon = position.lng;
		addCoordsToDOM(markerLat,markerLon);
	});
}
map.on('click', getCoords);


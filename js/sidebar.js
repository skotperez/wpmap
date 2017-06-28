var sidebar = L.control.sidebar('wpmap-sidebar', {
	closeButton: true,
	position: 'left'
});
map.addControl(sidebar);

map.on('click', function () {
	sidebar.hide();
	window.location.hash = '';
})
L.DomEvent.on(sidebar.getCloseButton(), 'click', function () {
	window.location.hash = '';
});
function sidebarContent(layer) {
	// link to About cadaveres
	linkAbout = "<div class='btn-bar'><a class='cadaver-btn' href='#' onClick='jQuery(aboutSidebar())'><span class='icon icon-info'></span> Sobre Cadáveres Inmobiliarios</a></div>";

	return linkAbout;
}
function prepareSidebar(e) {
	var layer = e.target;
	resetMarkersStyle(dataLayer);
	layer.setStyle(styleClick);

	if (!L.Browser.ie && !L.Browser.opera) {
		layer.bringToFront();
	}
	sidebar.setContent(sidebarContent(layer));
	if ( !sidebar.isVisible() ) {
		sidebar.toggle();
	}
	window.location.hash = layer.feature.properties.cartodb_id;
}


function BikeshareOverlay(map) { this.setMap(map); }

BikeshareOverlay.prototype = new google.maps.OverlayView();
BikeshareOverlay.prototype.draw = function() {
  if (!this.ready) {
    this.ready = true;
    google.maps.event.trigger(this, 'ready');
  }
};

function getPoints (map, url) {
  markers = [];
  d3.json(url, function(error, data) {
    data.forEach(function(d){
      marker = createMarker(map, d);
      //markers.push(marker);
    });
  });
}

function markerSize(breakpoint) {
  return (map.getZoom() > breakpoint ? 'large' : 'small');
}

function createMarker (map, station) {
  // var size = markerSize(ICON_BREAKPOINT),
  //   thisMarkerImg = markerDetails.getMarkerImage(station.phase, size),
  //   thisShape = markerDetails.getShape(station.phase, size);

  var opts = {
    strokeColor: "#FF0000",
    strokeOpacity: 0.45,
    strokeWeight: 0,
    fillColor: "#FF0000",
    fillOpacity: 0.25,
    map: map,
    center: new google.maps.LatLng(station.lat, station.lon),
    radius: station.availableDocks * 7
  };
  return new google.maps.Circle(opts);
}

function infoWindowD (m) {
  var out = '<div class="infowindow-container">' +
       '<h4 class="infowindow-header">' + m.stationName + '</h4>' +
        '<p><small>availableDocks = ' + m.availableDocks + '<br>'+
        'availableBikes = ' + m.availableBikes + '<br>'+
        'totalDocks = ' + m.totalDocks + '<br>'+
        'fullFlag = ' + m.fullFlag + '<br>'+
        'emptyFlag = ' + m.emptyFlag + '<br>'+
        'statusValue = ' + m.statusValue + '</small></p></div>';
  console.log(out);
  infowindow.setContent(out);
  infowindow.open(map, m);
}
function bikemapinit(endpoint) {
  var myLatlng = new google.maps.LatLng(40.7259, -73.99),
    options = {
      zoom: 13,
      // zoomControlOptions: {
      //   style: google.maps.ZoomControlStyle.LARGE
      // },
      disableDefaultUI: true,
      streetViewControl: false,
      panControl: false,
      center: myLatlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      scrollwheel: true,
      maxZoom: 18,
      minZoom: 12
    },
    map = new google.maps.Map(document.getElementById("the-map"), options),
    overlay = new BikeshareOverlay(map),
    bikeLayer = new google.maps.BicyclingLayer();
    bikeLayer.setMap(map);
    getPoints(map, endpoint);
}

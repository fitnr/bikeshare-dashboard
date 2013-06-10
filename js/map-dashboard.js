// declare globals
var infoWindow,
  map,
  color = d3.scale.ordinal()
    .domain([0, 1])
    .range(["#67001f","#b2182b","#d6604d","#f4a582","#d1e5f0","#92c5de","#4393c3","#2166ac","#053061"]);

function BikeshareOverlay(map) { this.setMap(map); }

BikeshareOverlay.prototype = new google.maps.OverlayView();
BikeshareOverlay.prototype.draw = function() {
  if (!this.ready) {
    this.ready = true;
    google.maps.event.trigger(this, 'ready');
  }
};

function getPoints (map, url) {
  d3.json(url, function(error, data) {
    data.forEach(function(d){
      createMarker(map, d);
    });
  });
}

function markerSize(breakpoint) { return (map.getZoom() > breakpoint ? 'large' : 'small'); }

function createMarker (map, d) {
  var strokeColor = color(d.availableBikes / d.totalDocks),
    strokeWeight = 0.88,
    full = ( +d.fullFlag == 1 ) ? '<p><small>Full</small></p>' : '',
    empty = ( +d.emptyFlag == 1) ? '<p><small>Empty</small></p>' : '',
    content =
      '<h4><a href="../station-dashboard/?station=' + d.id + '">' + d.stationName + '</a></h4>' +
      '<p><small>Available docks: ' + d.availableDocks + '<br>'+
      'Available bikes: ' + d.availableBikes + '<br>'+
      'Total docks: ' + d.totalDocks + '<br>' +
      'Status: ' + d.statusValue + '</small></p>'+
      full + empty;

  if (d.fullFlag == 1) {
    strokeColor = "#FF0000";
    strokeWeight = 1;
  } else if (d.emptyFlag == 1) {
    strokeColor = "#00FF00";
    strokeWeight = 1;
  }

  var opts = {
    strokeColor: strokeColor,
    strokeOpacity: 0.85,
    strokeWeight: strokeWeight,
    fillColor: color(d.availableBikes / d.totalDocks),
    fillOpacity: 0.69,
    map: map,
    center: new google.maps.LatLng(d.lat, d.lon),
    radius: d.totalDocks * 4.5,
    content: content, // for infowindow
  };
  var marker = new google.maps.Circle(opts);
  google.maps.event.addListener(marker, 'click', function() { infoWindowOpen(marker); });
}

function infoWindowOpen (marker) {
  infoWindow.setPosition(marker.getCenter());
  infoWindow.setContent(marker.content);
  infoWindow.open(map);
}

function bikemapinit(endpoint) {
  var myLatlng = new google.maps.LatLng(40.7258, -73.9889),
    options = {
      zoom: 13,
      disableDefaultUI: true,
      keyboardShortcuts: true,
      streetViewControl: false,
      panControl: false,
      center: myLatlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      scrollwheel: true,
      maxZoom: 18,
      minZoom: 12
    };
  infoWindow = new google.maps.InfoWindow({
      content: "",
      disableAutoPan: false,
      zIndex: null
  });
  map = new google.maps.Map(document.getElementById("the-map"), options);
  var overlay = new BikeshareOverlay(map),
    bikeLayer = new google.maps.BicyclingLayer();
    bikeLayer.setMap(map);
    getPoints(map, endpoint);
}

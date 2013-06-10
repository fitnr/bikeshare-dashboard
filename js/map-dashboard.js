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
      marker = createMarker(map, d);
    });
  });
}

function markerSize(breakpoint) { return (map.getZoom() > breakpoint ? 'large' : 'small'); }

var color = d3.scale.ordinal()
    .domain([0, 1])
    .range(["#67001f","#b2182b","#d6604d","#f4a582","#d1e5f0","#92c5de","#4393c3","#2166ac","#053061"]);

function createMarker (map, d) {

  var strokeColor = color(d.availableBikes / d.availableDocks),
    strokeWeight = 0.88;

  if (d.fullFlag == 1) {
    strokeColor = "#FF0000";
    strokeWeight = 1;
  } else if (d.emptyFlag == 1) {
    strokeColor = "#00FF00";
    strokeWeight = 1;
  }
  var opts = {
    strokeColor: strokeColor,
    strokeOpacity: 1,
    strokeWeight: strokeWeight,
    fillColor: color(d.availableBikes / d.availableDocks),
    fillOpacity: 0.69,
    map: map,
    center: new google.maps.LatLng(d.lat, d.lon),
    radius: d.availableDocks * 7
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
  var myLatlng = new google.maps.LatLng(40.729, -73.99),
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
    },
    map = new google.maps.Map(document.getElementById("the-map"), options),
    overlay = new BikeshareOverlay(map),
    };
  infoWindow = new google.maps.InfoWindow({
      content: "",
      disableAutoPan: false,
      zIndex: null
  });
    bikeLayer = new google.maps.BicyclingLayer();
    bikeLayer.setMap(map);
    getPoints(map, endpoint);
}

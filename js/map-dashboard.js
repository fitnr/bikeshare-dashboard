function BikeshareOverlay(map) {
  this.setMap(map);
}

BikeshareOverlay.prototype = new google.maps.OverlayView();
BikeshareOverlay.prototype.draw = function() {
  if (!this.ready) {
    this.ready = true;
    google.maps.event.trigger(this, 'ready');
  }
};

function getPoints (url) {
  markers = [];
  d3.json(url, function(error, data) {
    data.forEach(function(d){
      marker = createMarker(d);
      markers.push(marker);
      google.maps.event.addListener(marker, 'click', function () {markerClicked(this); });
    });
  });
}

// to do: add a function to this object that create/returns a markerimage
// when that's done, add to google.maps.event.addListener(map, 'zoom_changed', function() {
var markerDetails = {
  1: {
    'small': {
      'img': 'dot.png',
      'iconSize': new google.maps.Size(18,18),
      'anchor': new google.maps.Point(4,11),
      'shape': {
        coords: [9,9,5],
        type: 'circle'
      }
    },
    'large': {
      'img': 'suggestion-icon.png',
      'iconSize': new google.maps.Size(25,25),
      'anchor': new google.maps.Point(10,15),
      'shape' : {
          coords: [23,0,24,1,24,2,24,3,24,4,24,5,24,6,24,7,24,8,24,9,24,10,24,11,24,12,24,13,24,14,24,15,24,16,24,17,24,18,6,18,6,17,6,16,6,15,6,14,6,13,6,12,6,11,6,10,6,9,6,8,6,7,6,6,6,5,6,4,6,3,6,2,6,1,6,0,23,0],
          type: 'poly'
        }
      }
  },
  2: {
    'small': {
      'img': 'dot-gray.png',
      'iconSize': new google.maps.Size(18,18),
      'anchor': new google.maps.Point(4,11),
      'shape': {
        coords: [9,9,5],
        type: 'circle'
      }
    },
    'large': {
      'img': 'suggestion-icon-gray.png',
      'iconSize': new google.maps.Size(25,25),
      'anchor': new google.maps.Point(10,15),
      'shape' : {
        coords: [23,0,24,1,24,2,24,3,24,4,24,5,24,6,24,7,24,8,24,9,24,10,24,11,24,12,24,13,24,14,24,15,24,16,24,17,24,18,6,18,6,17,6,16,6,15,6,14,6,13,6,12,6,11,6,10,6,9,6,8,6,7,6,6,6,5,6,4,6,3,6,2,6,1,6,0,23,0],
        type: 'poly'
      }
    }
  },
  getMarkerImage: function(phase, size) {
    if (!phase)
      phase = 1;
    if (!this[phase][size].hasOwnProperty('markerImg')) {
      this[phase][size].markerImg = new google.maps.MarkerImage(
        DIRECTORY_URI + '/images/' + this[phase][size]['img'], //url
        this[phase][size].iconSize, //size
        new google.maps.Point(0,0), //origin
        this[phase][size].anchor // anchor point
      );
    }
    return this[phase][size]['markerImg'];
  },
  getShape: function(phase, size) {
    if (!phase)
      phase = 1;
    return this[phase][size].shape;
  }
};

function markerSize(breakpoint) {
  return (map.getZoom() > breakpoint ? 'large' : 'small');
}

function createMarker (station) {
  var size = markerSize(ICON_BREAKPOINT),
    thisMarkerImg = markerDetails.getMarkerImage(station.phase, size),
    thisShape = markerDetails.getShape(station.phase, size);

  m = new google.maps.Marker({
    id: station.id,
    position: new google.maps.LatLng(station.lat, station.lon),
    map: map,
    draggable: false,
    icon: thisMarkerImg,
    shape: thisShape,
    stationName: station.stationName,
    availableDocks: station.availableDocks,
    availableBikes: station.availableBikes,
    totalDocks: station.totalDocks,
    fullFlag: station.fullFlag,
    emptyFlag: station.emptyFlag
  });

  return m;
}

function infoWindowStatic (m) {
  var out = '<div class="infowindow-container station-location clearfix">' +
       '<h4 class="infowindow-header">' + m.stationName + '</h4>' +
        'availableDocks = ' + m.availableDocks + '<br>'+
        'availableBikes = ' + m.availableBikes + '<br>'+
        'totalDocks = ' + m.totalDocks + '<br>'+
        'fullFlag = ' + m.fullFlag + '<br>'+
        'emptyFlag = ' + m.emptyFlag + '<br>'+
        'statusValue = ' + m.statusValue + '</div>';
  console.log(out);
  infowindow.setContent(out);
  infowindow.open(map, marker);
}

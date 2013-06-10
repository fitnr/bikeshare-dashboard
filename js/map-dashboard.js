// declare globals
var infoWindow,
  map,
  color = d3.scale.quantize()
    .domain([0, 1])
    .range(["#bcbddc","#9e9ac8","#807dba","#6a51a3","#54278f","#3f007d","#2c0057"]),
    // .range(["#67001f","#b2182b","#d6604d","#f4a582","#d1e5f0","#92c5de","#4393c3","#2166ac","#053061"]),
  legend = d3.select("#legend").append('svg').append('g').attr('class', 'legend');

legend
  .append('text')
  .text('station fullness')
  .attr('class', 'legtitle')
  .attr('x', 0)
  .attr('y', 11);

legend.selectAll('rect.full')
  .data([0, 0.2, 0.4, 0.6, 0.8, 0.999])
.enter().append('rect')
  .attr('class', 'full')
  .style('fill', function(d){ return color(d); })
  .style('opacity', 0.85)
  .style('stroke', function(d){ return color(d); })
  .style('stroke-opacity', 0.9)
  .attr('x', 0)
  .attr('y', function(d, i){ return 18 + (i * 20); })
  .attr('width', 15)
  .attr('height', 15);

function circ(c) { c.attr('r', 7).attr('cx', 8); }

legend
  .append('circle')
  .call(circ)
  .attr('cy', 160)
  .style('stroke', '#ff0000');

legend
  .append('circle')
  .call(circ)
  .attr('cy', 180)
  .style('stroke', '#023858');

legend
  .append('text')
  .attr('class', 'empty')
  .attr('x', 20)
  .attr('y', 162)
  .text('empty');

legend
  .append('text')
  .attr('class', 'full')
  .attr('x', 20)
  .attr('y', 182)
  .text('full');

legend.selectAll('text.label')
  .data([0, 0.2, 0.4, 0.6, 0.8, 0.999])
.enter().append('text')
  .attr('class', 'label')
  .attr('x', 20)
  .attr('y', function(d, i){ return 30 + (i * 20); })
  .text(function (d) { return (100 * d).toFixed(0) + '%'; });

function getPoints (map, url) {
  d3.json(url, function(error, data) {
    data.forEach(function(d){
      createMarker(map, d);
    });
  });
}

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
    strokeWeight = 1.24;
  } else if (d.emptyFlag == 1) {
    strokeColor = "#023858";
    strokeWeight = 1.24;
  }

  var opts = {
    strokeColor: strokeColor,
    strokeOpacity: 0.85,
    strokeWeight: strokeWeight,
    fillColor: color(d.availableBikes / d.totalDocks),
    fillOpacity: 0.69,
    map: map,
    center: new google.maps.LatLng(d.lat, d.lon),
    radius: d.totalDocks * 4.25,
    content: content // for infowindow
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
  var bikeLayer = new google.maps.BicyclingLayer();
    bikeLayer.setMap(map);
    getPoints(map, endpoint);
}
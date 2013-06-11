// declare globals
var infoWindow,
  map,
  markers = {},
  legendCircles = [
    {text:'empty', 'stroke': '#ff0000', 'fill': 'none'},
    {text:'full', 'stroke': '#023858', 'fill': 'none'},
    {text:'not in service', 'stroke': 'none', 'fill': '#ff0000'},
    {text: 'planned', 'stroke': '#0033dd', 'fill': 'none', 'strokewidth': 2.25}
  ],
  color = d3.scale.quantize()
    .domain([0, 1])
    .range(["#bcbddc","#9e9ac8","#807dba","#6a51a3","#54278f","#3f007d","#2c0057"]),

  legend = d3.select("#legend svg g");

// Create data labels with arbitratry values
[0, 0.2, 0.4, 0.6, 0.8, 0.999].forEach(function(d, i){
  legend.append('rect')
    .attr('class', 'full')
    .style('fill', color(d) )
    .style('opacity', 0.85)
    .style('stroke', color(d) )
    .style('stroke-opacity', 0.9)
    .attr('x', 0)
    .attr('y', 18 + (i * 20) )
    .attr('width', 15)
    .attr('height', 15);

  legend.append('text')
    .attr('class', 'label')
    .attr('x', 20)
    .attr('y', 30 + (i * 20) )
    .text( (100 * d).toFixed(0) + '%' );
});

function getPoints (map, url) {
  d3.json(url, function(error, data) {
    data.forEach(function(d){
      marker = new circleMarker(map, d);
      markers[d.id] = marker;
    });
  });
}

function updateMarkers(url) {
  d3.json(url, function(data){
    data.forEach(function(d) {
      try {
        markers[d.id].update(d);
      } catch (err) {
        console.log(d.id, err.message);
      }
    });
  });
}

function setRadius(r) { return r * 4.05; }

function setStrokeColor(d) {
  if (d.fullFlag == 1)
    return "#023858";
  if (d.emptyFlag == 1)
    return "#FF0000";
  if (d.totalDocks === 0)
    return '#000000';
  if (d.statusValue == 'Planned')
    return '#0033dd';
  return color(d.availableBikes / d.totalDocks);
}

function setStrokeWeight(d) {
  if (d.availableBikes === 0)
    return 0;
  if (d.statusValue === 'Planned')
    return 2.25;
  return (d.fullFlag == 1 || d.emptyFlag == 1) ? 1.00 : 0.88;
}

function setContent(d) {
  var full = ( +d.fullFlag == 1 ) ? '<br>Station is full' : '',
      empty = ( +d.emptyFlag == 1) ? '<br>Station is empty' : '',
      status = ( d.statusValue == 'Not In Service' ) ? '<br>Station out of service' : (
        ( d.statusValue == 'Planned' ) ? '<br>Planned station' : ''
      ),
      content =
      '<h5><a href="../station-dashboard/?station=' + d.id + '">' + d.stationName + '</a></h5>' +
      '<p class="infowindow"><small>Available docks: ' + d.availableDocks + '<br>'+
      'Available bikes: ' + d.availableBikes + '<br>'+
      'Total docks: ' + d.totalDocks +
      status +
      full + empty +
      '</small></p>';
  return content;
}

function setFillColor(d) {
  if (d.statusValue == 'Not In Service') return '#ff0000';
  if (d.statusValue == 'Planned') return 'transparent';
  return color(d.availableBikes / d.totalDocks);
}

circleMarker.prototype = new google.maps.MVCObject();

circleMarker.prototype.update = function(d) {
  this.circle.set('radius', setRadius(d.totalDocks));
  this.circle.set('fillColor', setFillColor(d));
  this.circle.set('strokeColor', setStrokeColor(d));
  this.circle.set('strokeWeight', setStrokeWeight(d));
  this.circle.set('content', setContent(d));
};

function circleMarker (map, d) {
  // Set the circle options
  var opts = {
    strokeColor: setStrokeColor(d),
    strokeOpacity: 0.85,
    strokeWeight: setStrokeWeight(d),
    fillColor: setFillColor(d),
    fillOpacity: 0.69,
    map: map,
    planned: d.statusValue === 'Planned',
    center: new google.maps.LatLng(d.lat, d.lon),
    radius: (d.statusValue === 'Planned') ? 75 : setRadius(d.totalDocks),
    content: setContent(d) // for infowindow
  };

  // Create the circle
  this.circle = new google.maps.Circle(opts);
  google.maps.event.addListener(this.circle, 'click', function(){ infoWindowOpen(this); });
}

function infoWindowOpen (circle) {
  infoWindow.setPosition(circle.getCenter());
  infoWindow.setContent(circle.content);
  infoWindow.open(map);
}

function bikemapinit(endpoint) {
  var myLatlng = new google.maps.LatLng(40.7258, -73.9889),
    options = {
      zoom: 13,
      disableDefaultUI: true,
      keyboardShortcuts: true,
      streetViewControl: false,
      zoomControl: true,
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
  setInterval(function() {updateMarkers(endpoint); }, 120000);
}
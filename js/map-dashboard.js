// declare globals
var infoWindow,
  map,
  markers = {},
  legendCircles = [
    {text:'empty', 'stroke': '#ff0000', 'fill': 'none'},
    {text:'full', 'stroke': '#023858', 'fill': 'none'},
    {text:'not in service', 'stroke': 'none', 'fill': '#ff0000'}
  ],
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

legend.selectAll('circle')
  .data(legendCircles)
  .enter().append('circle')
  .call(circ)
  .attr('cy', function(d, i) {return (i * 20) + 160; })
  .style('stroke', function(d) { return d.stroke; })
  .style('fill', function(d) { return d.fill; });

legend.selectAll('text.circle')
  .data(legendCircles)
  .enter().append('text')
  .attr('x', 20)
  .attr('y', function(d, i) { return (i * 20) + 163; })
  .text(function(d){ return d.text; });

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
      if (d.statusValue == 'Planned') { return; }
      marker = new circleMarker(map, d);
      markers[marker.id] = marker;
    });
  });
}

function updateMarkers(url) {
  d3.json(url, function(data){
    data.forEach(function(d) {
      markers[d.id].update(d);
    });
  });
}

function setRadius(r) { return r * 4.05; }

function setStrokeColor(d) {
  if (d.fullFlag == 1) {
    return "#FF0000";
  } else if (d.emptyFlag == 1) {
    return "#023858";
  }
  if (d.totalDocks === 0) { return '#000000'; }
  return color(d.availableBikes / d.totalDocks);
}

function setStrokeWeight(avail, fullFlag, emptyFlag) {
  if (avail === 0) { return 0; }
  return (fullFlag == 1 || emptyFlag == 1) ? 1.24 : 0.88;
}

function setContent(d) {
  var full = ( +d.fullFlag == 1 ) ? '<br>Station is full' : '',
      empty = ( +d.emptyFlag == 1) ? '<br>Station is empty' : '',
      content =
      '<h5><a href="../station-dashboard/?station=' + d.id + '">' + d.stationName + '</a></h5>' +
      '<p class="infowindow"><small>Available docks: ' + d.availableDocks + '<br>'+
      'Available bikes: ' + d.availableBikes + '<br>'+
      'Total docks: ' + d.totalDocks + '<br>' +
      'Status: ' + d.statusValue +
      full + empty +
      '</small></p>';
  return content;
}

function setFillColor(d) {
  if (d.statusValue == 'Not In Service') return '#ff0000';
  return color(d.availableBikes / d.totalDocks);
}

circleMarker.prototype = new google.maps.MVCObject();

circleMarker.prototype.update = function(d) {
  this.set('radius', setRadius(d.totalDocks));
  this.set('fillColor', color(d.availableBikes / d.totalDocks));
  this.set('strokeColor', color(d.availableBikes / d.totalDocks));
  this.set('strokeWeight', setStrokeWeight(d.fullFlag, d.emptyFlag));
};

function circleMarker (map, d) {
  // set variables in google MVC fashion
  this.content = setContent(d);
  this.set('strokeWeight', setStrokeWeight(d.availableBikes, d.fullFlag, d.emptyFlag));
  this.set('strokeColor', setStrokeColor(d));
  this.set('radius', setRadius(d.totalDocks));
  this.set('fillColor', color(d.availableBikes / d.totalDocks));
  this.set('position', new google.maps.LatLng(d.lat, d.lon));
  this.set('fillColor', setFillColor(d));
  this.id = d.id;
  console.log(this);

  // Set the circle options
  opts = {
    strokeColor: this.strokeColor,
    strokeOpacity: 0.85,
    strokeWeight: this.strokeWeight,
    fillColor: this.fillColor,
    fillOpacity: 0.69,
    map: map,
    center: this.position,
    radius: this.radius,
    content: this.content // for infowindow
  };

  circle = new google.maps.Circle(opts);
  // Create the circle

  // bind things to our circle
  circle.bindTo('radius', this);
  circle.bindTo('fillColor', this);
  circle.bindTo('strokeWeight', this);
  circle.bindTo('strokeColor', this);
  console.log(circle);

  google.maps.event.addListener(circle, 'click', function() {
    infoWindow.setPosition(this.getCenter());
    infoWindow.setContent(this.content);
    infoWindow.open(map);
  });
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
  // setInterval("updateMarkers", 6000);
}
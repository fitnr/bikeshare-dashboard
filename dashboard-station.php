<?php
/**
 * Template Name: Single Station Dashboard
 * @package WordPress
 * @subpackage bikeshare-dashboard
*/
global $wp_query;

if(isset($wp_query->query_vars['station'])) {
    $station_id = $wp_query->query_vars['station'];
    // Get the station name. Station Info is called with XHR request by JS.
    $station = get_station_meta($station_id);
    $since = isset($wp_query->query_vars['since']) && $wp_query->query_vars['since'] != NULL ? $wp_query->query_vars['since'] : 0;
    $starttime = isset($wp_query->query_vars['starttime']) && $wp_query->query_vars['starttime'] != NULL ? $wp_query->query_vars['starttime'] : NULL;
    $endtime = isset($wp_query->query_vars['endtime']) && $wp_query->query_vars['endtime'] != NULL ? $wp_query->query_vars['endtime'] : NULL;
}
$post->stationName = (isset($station->stationName)) ? $station->stationName : "Couldn't find that station";
get_header();
?>

<h2><?php echo $post->stationName; ?></h2>

<p>Showing the last <?php echo pluralize($since); ?>.</p>

<form action="./" class="form-inline">
  <input type="hidden" name="station" value="<?php echo $station_id ?>">
  <p>
    Show me the last <input type="text" class="input-mini" name="since" id="since"> hours <button type="submit" class="btn btn-info">Go</button>
  </p>
</form>

<form action="./" class="form-inline">
  <input type="hidden" name="station" value="<?php echo $station_id ?>">
  <p>
    Show me from <input type="date" class="input-medium" name="starttime" id="starttime"> to <input type="date" class="input-medium" name="endtime" id="endtime"> <button type="submit" class="btn btn-info">Go</button>
  </p>
</form>

<p>There are <?php echo $station->totalDocks; ?> docks here.</p>
<div id="activity" class="d3-graph"></div>

<p><a href="../map/?station=<?php echo $station_id ?>">View on map</a></p>
<p>"Null" docks aren't shown as either being filled with bikes or available.</p>

<p><a href="../station_activity_csv/<?php echo $station_id . '/?since=' . $since; ?>">Download data (csv)</a></p>

<hr>

<p>Average for recent weeks.</p>

<div id="pattern" class="d3-graph"></div>

<p><a href="../bikeshare-dashboard/">⃪ System Dashboard</a></p>

<script>
    function pad(n, width, z) {
      z = z || '0';
      n = n + '';
      return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
    }

    var since = <?php echo $since; ?>,
        starttime = '<?php echo $starttime; ?>',
        endtime = '<?php echo $endtime; ?>',
        station_id = <?php echo $station_id; ?>;

    var margin = {top: 20, right: 92, bottom: 30, left: 50},
        width = 1096 - margin.left - margin.right,
        height = 500 - margin.top - margin.bottom;

    var parseDate = d3.time.format("%Y-%m-%d %H:%M:%S").parse;

    var x = d3.time.scale()
        .range([0, width]);

    var y = d3.scale.linear()
        .range([height, 0]);

    var color = d3.scale.category10();

    var xAxis = d3.svg.axis()
        .scale(x)
        .orient("bottom");

    var yAxis = d3.svg.axis()
        .scale(y)
        .orient("left");

    var line = d3.svg.line()
        .interpolate("basis")
        .x(function(d) { return x(d.date); })
        .y(function(d) { return y(d.number); });

    var svg = d3.select("#activity").append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
      .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    if (since) {
      var e = new Date();
      endtime = e.getFullYear() +"-"+ pad(e.getMonth()+1, 2) +"-"+ pad(e.getDay()+1, 2);
      endtime = e.getFullYear() +"-"+ pad(e.getMonth()+1, 2) +"-"+ pad(e.getDay()+1, 2);

      s = new Date(e.getTime() + (since * 60000 * 60));
    }

    d3.json("../station_trips/" + station_id + '/?starttime=' + starttime + '&endtime=' + endtime, function(error, data){
      if (!data) return;

      data.forEach(function(d) {
          d.stamp = parseDate(d.datetime);
      });

      var nodes = svg.selectAll('.trip')
        .data(data)
      .enter().append('g')
        .attr('class', 'node')
        .text(function(d){ return d.datetime; });

      nodes.append("circle")
        .attr("class", "end")
        .attr("r", function(d) { return d.ends; })
        .attr("transform", function(d) { return "translate(" + x(d.stamp) +",1)" });

      nodes.append("circle")
        .attr("class", "start")
        .attr("r", function(d) { return d.starts; })
        .attr("transform", function(d) { return "translate(" + x(d.stamp) +",0)" });

    });    

    d3.json("../station_activity/" + station_id + '/?starttime=' + starttime + '&endtime=' + endtime, function(error, data){
      if (!data) return;

      data.forEach(function(d) {
          d.stamp = parseDate(d.datetime);
      });

      color.domain(d3.keys(data[0]).filter(function(key) { return key !== "stamp" && key !== "datetime"; }));

      var bikes = color.domain().map(function(name) {
          return {
            name: name,
            values: data.map(function(d) {
              return {date: d.stamp, number: +d[name]};
            })
          };
      });

      x.domain(d3.extent(data, function(d) { return d.stamp; }));

      y.domain([ // make sure 0 is at bottom of scale.
          d3.min(bikes, function(c) { return d3.min(c.values, function(v) { return 0; }); }),
          d3.max(bikes, function(c) { return d3.max(c.values, function(v) { return v.number; }); })
      ]);

      svg.append("g")
        .attr("class", "x axis")
        .attr("transform", "translate(0," + height + ")")
        .call(xAxis);

      svg.append("g")
          .attr("class", "y axis")
          .call(yAxis)
        .append("text")
          // .attr("transform", "rotate(-90)")
          .attr("y", 6)
          .attr("dy", "-1.33em")
          .style("text-anchor", "end")
          .text("Docks");
   
      var bike = svg.selectAll(".bike")
        .data(bikes)
      .enter().append("g")
        .attr("class", "bike")
        .attr('name', function(d){ return d.name; });

        bike.append("path")
            .attr("class", "line")
            .attr("d", function(d) { return line(d.values ); })
            .style("stroke", function(d) { return color(d.name); });

        bike.append("text")
            .datum(function(d) { return {name: d.name.replace('_', ' ') + ': '+ d.values[d.values.length-1].number, value: d.values[d.values.length-1]}; })
            .attr("transform", function(d) { return "translate(" + x(d.value.date) + "," + y(d.value.number) + ")"; })
            .attr("x", 1)
            .attr("dy", ".35em")
            .text(function(d) { return d.name; });
    });
    
  //
  // Activity Pattern
  //
  var patternParseDate = d3.time.format("%Y-%m-%d %H:%M").parse;

  var patternLine = d3.svg.line()
      .interpolate("basis")
      .x(function(d) { return x(d.date); })
      .y(function(d) { return y(d.number); });

  var patternXAxis = d3.svg.axis()
      .scale(x)
      .orient("bottom")
      .tickFormat(d3.time.format('%a %I %p'));

  svg2 = d3.select("#pattern").append("svg")
      .attr("width", width + margin.left + margin.right)
      .attr("height", height + margin.top + margin.bottom)
    .append("g")
      .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

  d3.json("../station_pattern/" + station_id + '/', function(error, data){
    data.forEach(function(d) {
      d.stamp = patternParseDate(d.datetime);
    });

    color.domain(d3.keys(data[0]).filter(function(key) { return key !== "stamp" && key !== "datetime"; }));

    var patterns = color.domain().map(function(name) {
        return {
          name: name,
          values: data.map(function(d) {
            return {date: d.stamp, number: +d[name]};
          })
        };
    });

    x.domain(d3.extent(data, function(d) { return d.stamp; }));

    y.domain([ // make sure 0 is at bottom of scale.
        d3.min(patterns, function(c) { return d3.min(c.values, function(v) { return 0; }); }),
        d3.max(patterns, function(c) { return d3.max(c.values, function(v) { return v.number; }); })
    ]);

    svg2.append("g")
        .attr("class", "x axis")
        .attr("transform", "translate(0," + height + ")")
        .call(patternXAxis);

    svg2.append("g")
        .attr("class", "y axis")
        .call(yAxis)
      .append("text")
        // .attr("transform", "rotate(-90)")
        .attr("y", 6)
        .attr("dy", "-1.33em")
        .style("text-anchor", "end")
        .text("Docks");
 
    var group = svg2.selectAll(".bike")
      .data(patterns)
    .enter().append("g")
      .attr("class", "bike")
      .attr('name', function(d){ return d.name; });

      group.append("path")
          .attr("class", "line")
          .attr("d", function(d) { return patternLine(d.values); })
          .style("stroke", function(d) { return color(d.name); });

      group.append("text")
          .datum(function(d) { return {name: d.name.replace('_', ' ') + ': '+ d.values[d.values.length-1].number, value: d.values[d.values.length-1]}; })
          .attr("transform", function(d) { return "translate(" + x(d.value.date) + "," + y(d.value.number) + ")"; })
          .attr("x", 1)
          .attr("dy", ".35em")
          .text(function(d) { return d.name; });
    });
</script>

<?php get_footer(); ?>

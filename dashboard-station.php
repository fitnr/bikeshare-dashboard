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
    $post->stationName = get_station_name($station_id);
    $since = ($wp_query->query_vars['since']) ? $wp_query->query_vars['since'] : 6;
}

get_header('dashboard');
?>

<h2><?php echo (isset($post->stationName)) ? $post->stationName : "Couldn't find that station"; ?></h2>

<p>Showing the last <?php echo pluralize($since); ?>.</p>

<form action="./" class="form-inline">
  <input type="hidden" name="station" value="<?php echo $station_id ?>">
  Show me the last <input type="text" class="input-mini" name="since" id="since"> hours
</form>

<div id="activity" class="d3-graph"></div>

<hr>

<p><a href="../station_activity_csv/<?php echo $station_id . '/?since=' . $since ?>">Download data (csv)</a></p>

<p><a href="../bikeshare-dashboard/">System Dashboard</a></p>

<script>
    var since = <?php echo $since; ?>,
        station_id = <?php echo $station_id; ?>;

    var margin = {top: 20, right: 80, bottom: 30, left: 50},
        width = 1000 - margin.left - margin.right,
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

    d3.json("../station_activity/" + station_id + '/?since=' + since, function(error, data){
      data = data['activity'];
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
            .attr("d", function(d) { return line(d.values); })
            .style("stroke", function(d) { return color(d.name); });

        bike.append("text")
            .datum(function(d) { return {name: d.name.replace('_', ' '), value: d.values[d.values.length-1]}; })
            .attr("transform", function(d) { return "translate(" + x(d.value.date) + "," + y(d.value.number) + ")"; })
            .attr("x", 3)
            .attr("dy", ".35em")
            .text(function(d) { return d.name; });
      });
</script>

<?php get_footer('dashboard'); ?>
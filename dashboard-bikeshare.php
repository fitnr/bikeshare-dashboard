<?php
/**
 * Template Name: Bikeshare Dashboard
 * @package WordPress
 * @subpackage bikeshare-dashboard
*/
get_header();

global $wpdb;

$kwargs = station_overview();
$station_data = $wpdb->get_results(sprintf($kwargs['q'], $kwargs['since']));
usort($station_data, 'diffockcmp');
$since = (int) $kwargs['since'];

// Get status of stations
$station_status = array('1'=>0, '2'=>0, '3'=>0);
foreach($station_data as $stn)
  $station_status[$stn->status]++;

// Get number of active stations without activity lately
$active_stations = array_filter($station_data, 'status1');
$inactive_stations = array_filter($station_data, 'status3');
$planned_stations = array_filter($station_data, 'status2');

// Amazingly, this is faster than doing array_filter
$count_no_active = 0;
foreach ($active_stations as $s)
  $count_no_active += ($s->diffDocks <= 0) ? 1 : 0;

?>

<h1>Activity overview for last <?php echo pluralize($since); ?></h1>

<form action="./" class="form-inline">
  Show me the last <input type="text" class="input-mini" name="since" id="since"> hours
</form>

<!-- Overview charts -->
<div class="row">

  <div class="span3">
    <h3>Docks and bikes</h3>

    <div id="overview" class="d3-graph"></div>

    <p><small>"Null" docks aren't shown as either being filled with bikes or available.</small></p>

    <p><a href="./system_activity_csv/<?php echo "?since=".$since ;?>">Download data (csv)</a></p>

  </div>

  <div class="span3">
    <h3>Stations</h3>
    <div id="fullempty" class="d3-graph"></div>
  </div>

</div>

<hr>

<h3>Map</h3>
<p><a href="./map/">View map →</a></p>

<hr>

<h3>Station list</h3>

<p>In the last <?php echo pluralize($since); ?>:</p>
<ul>
  <li><?php echo $station_status['3']?> stations have been listed as <a href="#inactive">not in service</a>.</li>
  <li><?php echo $station_status['2']?> stations have been listed as <a href="#planned">planned</a>.</li>
  <li><?php echo $station_status['1']?> stations have been <a href="#active">active</a>.</li>
</ul>

<p>The maximum and minimum number of available docks in the last <?php echo pluralize($since); ?> are shown in parentheses.</p>
<p><span class="label label-important">Red</span> stations have no recorded activity in the last <?php echo pluralize($since); ?>.</p>

<?php if (count($inactive_stations) > 0): ?>

<h4 id="inactive">Not In Service</h4>

<ul class="cols-three">
<?php echo station_list($inactive_stations); ?>
</ul>

<?php endif; ?>

<h4 id="active">Active Stations</h4>

<p>Stations with no activity in the last <?php echo pluralize($since); ?>: <span class="label label-important"><?php echo $count_no_active; ?></span></p>

<ul class="station-list cols-three">
<?php echo station_list($active_stations); ?>
</ul>

<?php if (count($planned_stations) > 0): ?>
<h4 id="planned">Planned Stations</h4>

<ul class="station-list cols-three">
<?php echo station_list($planned_stations); ?>
</ul>

<?php endif; ?>

<script>
    var since = <?php echo $kwargs['since'] ;?>;
    var margin = {top: 13, right: 30, bottom: 30, left: 42},
        width = 540 - margin.left - margin.right,
        height = 300 - margin.top - margin.bottom;

    var parseDate = d3.time.format("%Y-%m-%d %H:%M:%S").parse;

    var x = d3.time.scale()
        .range([0, width]);

    var y = d3.scale.linear()
        .range([height, 0]);

    var y1 = y, y2 = y;

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

    d3.selectAll('.d3-graph').append('svg')
        .attr("height", height + margin.top + margin.bottom);

    var svg1 = d3.select("#overview").select("svg")
      .attr("width", width + margin.left + margin.right)
      .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    var svg2 = d3.select("#fullempty").select("svg")
      .attr("width", width + margin.left - 12 + margin.right + 70)
      .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    d3.json("<?php bloginfo('home'); ?>/system_activity/?since=" + since, function(error, data){

      data.forEach(function(d) {
          d.stamp = parseDate(d.datetime);
      });

      color.domain(d3.keys(data[0]).filter(function(key) { return key !== "datetime" && key !== "stamp"; }));

      var dataLines = color.domain().map(function(name) {
        return {
          name: name,
          values: data.map(function(d) { return {date: d.stamp, number: +d[name]}; })
        }
      });

      // Overview Data
      overviewData = dataLines.filter(function(e){
        return (e.name != 'Empty_Stations' &&
          e.name != 'Full_Stations' &&
          e.name != 'Planned_Stations' &&
          e.name != 'Inactive_Stations'
        );
      });

      // Full or Empty data
      fullEmptyData = dataLines.filter(function(e){
        return (e.name == 'Empty_Stations' ||
          e.name == 'Full_Stations' ||
          e.name == 'Planned_Stations' ||
          e.name == 'Inactive_Stations'
        );
      });

      x.domain(d3.extent(data, function(d) { return d.stamp; }));
      y1.domain([
          d3.min(overviewData, function(c) { return d3.min(c.values, function(v) { return v.number-250; }); }),
          d3.max(overviewData, function(c) { return d3.max(c.values, function(v) { return v.number; }); })
      ]);

        svg1.append("g")
            .attr("class", "x axis")
            .attr("transform", "translate(0," + height + ")")
            .call(xAxis);

        svg1.append("g")
            .attr("class", "y axis")
            .call(yAxis)
          .append("text")
            // .attr("transform", "rotate(-90)")
            .attr("y", 6)
            .attr("dy", ".71em")
            .style("text-anchor", "end")
   
      var bike1 = svg1.selectAll(".bike")
        .data(overviewData)
      .enter().append("g")
        .attr("class", "bike")
        .attr('name', function(d){ return d.name; });

        bike1.append("path")
            .attr("class", "line")
            .attr("d", function(d) { return line(d.values); })
            .style("stroke", function(d) { return color(d.name); });

        bike1.append("text")
            .datum(function(d) { return {name: d.name.replace('_',  ' ') +': '+ d.values[d.values.length-1].number, value: d.values[d.values.length-1]}; })
            .attr("transform", function(d) { return "translate(" + x(d.value.date) + "," + y1(d.value.number) + ")"; })
            .attr("x", -70)
            .attr("dy", "-0.33em")
            .text(function(d) { return d.name; });

        // Full or Empty graph

        y2.domain([
            d3.min(fullEmptyData, function(c) { return d3.min(c.values, function(v) { return v.number; }); }),
            d3.max(fullEmptyData, function(c) { return d3.max(c.values, function(v) { return v.number; }); })
        ]);

        svg2.append("g")
            .attr("class", "x axis")
            .attr("transform", "translate(0," + height + ")")
            .call(xAxis);

        svg2.append("g")
            .attr("class", "y axis")
            .call(yAxis)
          .append("text")
            // .attr("transform", "rotate(-90)")
            .attr("y", 6)
            .attr("dy", ".71em")
            .style("text-anchor", "end")
   
      var bike2 = svg2.selectAll(".bike")
        .data(fullEmptyData)
      .enter().append("g")
        .attr("class", "bike")
        .attr('name', function(d){ return d.name; });

        bike2.append("path")
            .attr("class", "line")
            .attr("d", function(d) { return line(d.values); })
            .style("stroke", function(d) { return color(d.name); });

        bike2.append("text")
            .datum(function(d) { return {name: d.name.replace('_', ' ') + ': ' + d.values[d.values.length-1].number, value: d.values[d.values.length-1]}; })
            .attr("transform", function(d) { return "translate(" + x(d.value.date) + "," + y1(d.value.number) + ")"; })
            .attr("x", 2)
            .attr("dy", "0.35em")
            .text(function(d) { return d.name; });
      });
</script>

<?php get_footer(); ?>
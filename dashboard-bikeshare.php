<?php
/*
Template Name: Bikeshare Dashboard
*/
get_header('dashboard');

function objcmp($a, $b) { return $a->diffDocks > $b->diffDocks; }

function status1($x) { return ($x->status == '1'); }
function status2($x) { return ($x->status == '2'); }
function status3($x) { return ($x->status == '3'); }

function top_stations($data, $line) {
    $output = '';
    foreach ($data as $value):
        if ($value->diffDocks > 0):
            $format = $line;
        else:
            $format = '<span class="badge badge-important">' . $line . '</span>';
        endif;          
        $output .= '<li>' . sprintf($format, $value->id, $value->stationName, $value->minDocks, $value->maxDocks) . '</li>';

    endforeach;
    return $output;
}

global $wpdb;

$kwargs = station_overview();
$station_data = $wpdb->get_results(sprintf($kwargs['q'], $kwargs['since']));
usort($station_data, 'objcmp');
$since = (int) $kwargs['since'];

// Get status of stations
$station_status = array('1'=>0, '2'=>0, '3'=>0);
foreach($station_data as $stn)
  $station_status[$stn->status]++;

?>
<h2>Activity Overview for last <?php echo pluralize($since); ?></h2>

<form action="./" class="form-inline">
  Show me the last <input type="text" class="input-mini" name="since" id="since"> hours
</form>

<div id="overview" class="d3-graph"></div>

<hr>

<div id="fullempty" class="d3-graph"></div>

<hr>

<h2>Stations</h2>

<div id="gmap"></div>

<p>According to NYCBS, there are:</p>
<ul>
  <li><?php echo $station_status['1']?> active stations.</li>
  <li><?php echo $station_status['2']?> planned stations.</li>
  <li><?php echo $station_status['3']?> stations not in service.</li>
</ul>

<p>The minimum and maximum number of available docks in the last <?php echo pluralize($since); ?> are shown in parentheses.</p>
<p><span class="label label-important">Red</span> stations have no activity in the last <?php echo pluralize($since); ?>.</p>

<h3>Not In Service</h3>

<ul class="cols-three">
    <?php echo top_stations(array_filter($station_data, 'status3'), '<a href="../station-dashboard/?station=%s">%s (%s, %s)</a>') ?>
</ul>

<h3>Active Stations</h3>

<ul class="cols-three">
    <?php echo top_stations(array_filter($station_data, 'status1'), '<a href="../station-dashboard/?station=%s">%s (%s, %s)</a>') ?>
</ul>

<h3>Planned Stations</h3>

<ul class="cols-three">
    <?php echo top_stations(array_filter($station_data, 'status2'), '<a href="../station-dashboard/?station=%s">%s (%s, %s)</a>') ?>
</ul>


<script>
    var since = <?php echo $kwargs['since'] ;?>;
    var margin = {top: 20, right: 100, bottom: 30, left: 50},
        width = 1000 - margin.left - margin.right,
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

    var svg = d3.select("#overview").append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
      .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    var svg2 = d3.select("#fullempty").append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
      .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    d3.json("../system_activity/?since=" + since, function(error, data){

      data.forEach(function(d) {
          d.stamp = parseDate(d.stamp);
      });

      color.domain(d3.keys(data[0]).filter(function(key) { return key !== "stamp"; }));

      var dataLines = color.domain().map(function(name) {
        return {
          name: name,
          values: data.map(function(d) { return {date: d.stamp, number: +d[name]}; })
        }
      });

      // Overview Data
      overviewData = dataLines.filter(function(e){
        return (e.name != 'Empty_Stations' && e.name != 'Full_Stations');
      });

      x.domain(d3.extent(data, function(d) { return d.stamp; }));
      y1.domain([
          d3.min(overviewData, function(c) { return d3.min(c.values, function(v) { return v.number-1000; }); }),
          d3.max(overviewData, function(c) { return d3.max(c.values, function(v) { return v.number; }); })
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
            .attr("dy", ".71em")
            .style("text-anchor", "end")
   
      var bike = svg.selectAll(".bike")
        .data(overviewData)
      .enter().append("g")
        .attr("class", "bike")
        .attr('name', function(d){ return d.name; });

        bike.append("path")
            .attr("class", "line")
            .attr("d", function(d) { return line(d.values); })
            .style("stroke", function(d) { return color(d.name); });

        bike.append("text")
            .datum(function(d) { return {name: d.name.replace('_',  ' '), value: d.values[0]}; })
            .attr("transform", function(d) { return "translate(" + x(d.value.date) + "," + y1(d.value.number) + ")"; })
            .attr("x", 3)
            .attr("dy", ".35em")
            .text(function(d) { return d.name; });

        // Full or Empty data
        fullEmptyData = dataLines.filter(function(e){
          return (e.name == 'Empty_Stations' || e.name == 'Full_Stations');
        });

        y2.domain([
            d3.min(fullEmptyData, function(c) { return d3.min(c.values, function(v) { return 0; }); }),
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
            .datum(function(d) { return {name: d.name.replace('_', ' '), value: d.values[0]}; })
            .attr("transform", function(d) { return "translate(" + x(d.value.date) + "," + y1(d.value.number) + ")"; })
            .attr("x", 3)
            .attr("dy", ".35em")
            .text(function(d) { return d.name; });


      });
</script>

<?php get_footer('dashboard'); ?>
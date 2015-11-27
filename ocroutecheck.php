<?php
if (!empty ($_GET["stop"]) && !empty($_GET["route"])){
$stop = $_GET["stop"];
$routeNo = $_GET["route"];

$appId = '';
$apiKey = '';

$url = "http://api.octranspo1.com/v1.2/GetNextTripsForStop";
$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_POST,5);
curl_setopt($ch,CURLOPT_POSTFIELDS,"appID=$appId&routeNo=$routeNo&apiKey=$apiKey&stopNo=$stop");
ob_start();
$curl_ret = curl_exec($ch);
$result = ob_get_contents();
ob_end_clean();
curl_close($ch);

$result = preg_replace("/soap:/","",$result);
$result = preg_replace('/ xmlns:[^"]+"[^"]+"/',"",$result);
$result = preg_replace('/ xmlns="[^"]+"/',"",$result);
$xml = simplexml_load_string($result);
}
else 
	echo "Please enter all fields";
	
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/form.css" rel="stylesheet">
	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
	
	<title>OC Stop Checker</title>
    <style>
      html, body {
        height: 100%;
        margin: 0;
        padding: 0;
		padding-top:5px;
      }
      #map {
        height: 100%;
      }
    </style>
  </head>
  <body>
	<form action="ocroutecheck.php" method="get" class="form-horizontal" role="form">
  <div class="form-group">
    <label class="control-label col-sm-2" for="stop">Stop:</label>
    <div class="col-sm-10">
      <input type="stop" class="form-control" id="stop" name="stop" placeholder="Enter stop">
    </div>
  </div>
  <div class="form-group">
    <label class="control-label col-sm-2" for="route">Route:</label>
    <div class="col-sm-10"> 
      <input type="route" class="form-control" id="route" name="route" placeholder="Enter route">
    </div>
  </div>
  <div class="form-group"> 
    <div class="col-sm-offset-2 col-sm-10">
      <button type="submit" class="btn btn-default">Submit</button>
    </div>
  </div>
</form>
<?php
if (!empty ($_GET["stop"]) && !empty($_GET["route"])){
	$json = json_encode($xml);
	$jsond = json_decode($json, true);
	$validRoute= gettype($jsond['Body']['GetNextTripsForStopResponse']['GetNextTripsForStopResult']['Error']);
	$lat = array();
	$lng = array();
	if($validRoute != 'array')
		echo "Invalid Route";
	else{
		$stopNo=$jsond['Body']['GetNextTripsForStopResponse']['GetNextTripsForStopResult']['StopNo'];
		$singleDirection = isset($jsond['Body']['GetNextTripsForStopResponse']['GetNextTripsForStopResult']['Route']['RouteDirection']['RouteLabel']);
		if ($singleDirection){
			$routeLabel = $jsond['Body']['GetNextTripsForStopResponse']['GetNextTripsForStopResult']['Route']['RouteDirection']['RouteLabel'];
			$direction = $jsond['Body']['GetNextTripsForStopResponse']['GetNextTripsForStopResult']['Route']['RouteDirection']['Direction'];
			$route_arr=$jsond['Body']['GetNextTripsForStopResponse']['GetNextTripsForStopResult']['Route']['RouteDirection']['Trips']['Trip'];

			echo "Schedule for Stop: "    . $stopNo . " " . $routeLabel . " " . $direction . "<br><br>";
			foreach ($route_arr as $route){
			echo "Next trip in: " . $route['AdjustedScheduleTime'] . " minutes";
				if ($route['AdjustmentAge']>0)
				{
					array_push($lat,$route['Latitude']);
					array_push($lng,$route['Longitude']);
					echo " --GPS--";
					
				}
				else
					echo "<br>";
				echo "<br>";
			}
		}	
		else
		{
			$route_arr = $jsond['Body']['GetNextTripsForStopResponse']['GetNextTripsForStopResult']['Route']['RouteDirection'];
			
			foreach ($route_arr as $route)
			{
				if (isset($route['Trips']['Trip']))
				{
					echo "Schedule for Stop: "    . $stopNo . " " . $route['RouteLabel'] . " " . $route['Direction']  . "<br>";
					foreach($route['Trips']['Trip'] as $trip)
					{
						echo "Next trip in: " . $trip['AdjustedScheduleTime'] . " minutes";
							if ($trip['AdjustmentAge']>0)
							{
								array_push($lat,$trip['Latitude']);
								array_push($lng,$trip['Longitude']);					
								echo " --GPS--";
							}
							else
								echo "<br>";
							echo "<br>";
					}
					echo "<br>";
				}
			}	
		}
	}
}
?>	
	
    <div id="map"></div>
	<?php
if (!empty ($_GET["stop"]) && !empty($_GET["route"])){
?>
<script>
function initMap() {
<?php 
	$arrlength = count($lat);
	for($x = 0; $x < $arrlength; $x++) {
		?>
		var myLatLng = {lat: <?php echo $lat[$x];?> , lng: <?php echo $lng[$x];?>};
		<?php
		if ($x==0)
		{?>
			var map = new google.maps.Map(document.getElementById('map'), {
			zoom: 12,
			center: myLatLng
			});
			var marker = new google.maps.Marker({
			position: myLatLng,
			map: map,
			title: ''
			});
		<?php
		}
		else{
		?>
			var marker = new google.maps.Marker({
			position: myLatLng,
			map: map,
			title: ''
			});
			
		<?php
		}
		}
	?>
  }
</script>
<?php 
} ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=&callback=initMap"
        async defer></script>
  </body>
</html>
<!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="UTF-8">
  <meta name="Generator" content="Notepad++">
  <meta name="Don Wilson" content="3-hr time series of Weather Rack sensors">
  <meta name="Keywords" content="">
  <meta name="Description" content="">
  <title>3-hr Weather Time Series</title>
  <link rel="icon" type="image/png" sizes="16x16" href="pics/favicon-16x16.png">
  <style>
	body {
		font-family: arial;
		font-size: 1.1em;
		background-color: #d8dfe5;
		}
		
	h1 {font-size: 1.4em}
	
	table {
		border: 2px solid black;
		border-collapse: collapse;
		}
		
	th {background-color: lightblue;}
		
	th, td {
		border: 1px solid black;
		padding: 8px;
		}
		
	tr:nth-child(odd) {
		background-color: #ffffff;
		}	
	
  </style>
 </head>
 <body>
  <h1 style="text-align: center;">Don's 3-hr Time Series of Weather Data in Greensburg, PA</h1>
  <h4>Temp = &#176;F, DP = Dew Point &#176;F, RH = Relative Humidity %, mb = Atmospheric Pressure (SLP), Trend = mb change</h4>
  
<?php

	$HrsFromZ = 4;
	$servername = "localhost";
	$username = "root";
	$password = "password";
	$dbname = "Weather";


	$link = mysqli_connect($servername, $username, $password, $dbname);
	
		/* check connection */
	if (mysqli_connect_errno()) {
		printf("Connect failed: %s\n", mysqli_connect_error());
		exit();
		}

		/* $query = "select TimeStamp, currentWindSpeed, currentWindDirection, insideTemperature, insideHumidity, bmp180Pressure, bmp180SeaLevel, totalRain FROM WeatherData order by 1 desc limit 1"; */
		
	$query0 = "select Date_Format(Date_sub(BMEData.UTCDateTime, interval $HrsFromZ hour), '%a, %d %b') as TD, date_format(Date_sub(BMEData.UTCDateTime, interval $HrsFromZ hour), '%H:%i') as ET, LetterDir as Direction, WindSpeed as Knots, WindGust as Gust, TempFOut as 'TempF', DewPoint as 'DP', RHOut as 'RH', AtmPressure as Pressure, DeltaPressure as Delta, Rain as Rain FROM BMEData INNER JOIN WindRainData ON BMEData.UTCDateTime = WindRainData.UTCDateTime INNER JOIN Wind ON WindRainData.WindNumDir = Wind.NumDir INNER JOIN SHTData on BMEData.UTCDateTime = SHTData.UTCDateTime where Date(Date_sub(BMEData.UTCDateTime, interval 4 hour)) = Date(now()) order by BMEData.UTCDateTime desc limit 36;";
	
	$result = mysqli_query($link, $query0);
	
	if (mysqli_num_rows($result) > 0) {
		echo '<table>
				<tr>
					<th>Date</th>
					<th>Time</th>
					<th>Wind</th>
					<th>Knots</th>
					<th>Gust</th>
					<th>Temp</th>
					<th>DP</th>
					<th>RH%</th>
					<th>mb</th>
					<th>Trend</th>
					<th>Rain</th>
				</tr>';

	
	
		while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
			printf('<tr>
						<td>%s</td>
						<td>%s</td>
						<td>%s</td>
						<td>%s</td>
						<td>%s</td>
						<td>%s</td>
						<td>%s</td>
						<td>%s</td>
						<td>%s</td>
						<td>%s</td>
						<td>%s</td>
					</tr>',
					
				$row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $row[8], $row[9], $row[10]); 
			}
			echo '</table>';
	} else {
		echo '0 results found.';
	}
	/* close connection */
	mysqli_close($link);
?>

</body>
</html>

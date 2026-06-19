<!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="UTF-8">
  <meta name="Generator" content="Notepad++">
  <meta name="Don Wilson" content="5-min update of Weather Rack sensors">
  <meta name="Keywords" content="">
  <meta name="Description" content="">
  <title>Weather Rack Sensor Data</title>
  <link rel="icon" type="image/png" sizes="16x16" href="pics/favicon-16x16.png">
  <style>
	body {font-family: arial; font-size: 1.1em; background-color: #d8dfe5;}
	h1 {font-size: 1.4em}
	h2 {font-size: 1.2em}

	dd {font-size: 1.1em; color: black; line-height: 1.2; font-weight: 300;}
	dd#padtop {padding-top: 0.5em;}
	dd#indent {text-indent: 1em;}

	red {color: red; font-weight: bold;}
	blue {color: blue; font-weight: bold;}
	orange {color: orange; font-weight: bold;}

	a {text-decoration: none;}
	a:link, a:visited, a:active {color: black;}
  </style>
 </head>
 <body>
  <h1 style="text-align: center;">Don's Weather Data in Greensburg, PA . . . updated every 5 minutes</h1>
  <h3 style="text-align: center; font-style: italic;">(40.24°N 79.56°W, 1047ft ASL)</h3>
  <p style="text-align: center; font-style: italic; font-size: 1.1em;">Re-established with new sensor interfaces on 10 Feb 2026</p>
  
<?php
	// 1. Set the default timezone
	date_default_timezone_set('America/New_York');

	// 2. Set your location's latitude and longitude (Greensburg, PA)
	$latitude = 40.3015;
	$longitude = -79.5389; // Note: Longitude is negative for West

	// 3. Get current timestamp and sun information
	$timestamp = time();
	$sun_info = date_sun_info($timestamp, $latitude, $longitude);

	$sunrise_timestamp = $sun_info['sunrise'];
	$sunset_timestamp = $sun_info['sunset'];

	// 4. Format the times for display
	$sunrise_time = date("H:i a T", $sunrise_timestamp);
	$sunset_time = date("H:i a T", $sunset_timestamp);

	// 5. Calculate the interval between sunrise and sunset
	$total_seconds = $sunset_timestamp - $sunrise_timestamp;
	$total_minutes = $total_seconds / 60;

	$hours = floor($total_minutes / 60);
	$minutes = $total_minutes % 60;

	$time_difference_string = sprintf("%d hours and %d minutes", $hours, $minutes);

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
		
	$query0 = "select Date_Format(Date_sub(SHTData.UTCDateTime, interval $HrsFromZ hour), '%W, %d %M %Y') as Today_Date FROM SHTData order by UTCDateTime desc Limit 1;";
		
	$query1 = "select Date_Format(Date_sub(BMEData.UTCDateTime, interval $HrsFromZ hour), '%a, %d %b') as TD, date_format(Date_sub(BMEData.UTCDateTime, interval $HrsFromZ hour), '%H:%i') as ET, WindSpeed as Knots, WindGust as GKnots, WindNumDir as DirNum, LetterDir as DirLetter, TempFOut as TFout, DeltaF as DF, TempFin as TFin, RHIn as RHin, DewPoint as DP, RHOut as RHout,  AtmPressure as BP, DeltaPressure as DBP, Rain as Rain, DATE_FORMAT(DATE_SUB(NOW(), INTERVAL LastRain MINUTE), '%a, %d %b %H:%i') as LRain FROM BMEData INNER JOIN WindRainData ON BMEData.UTCDateTime = WindRainData.UTCDateTime INNER JOIN Wind ON WindRainData.WindNumDir = Wind.NumDir INNER JOIN SHTData on BMEData.UTCDateTime = SHTData.UTCDateTime order by BMEData.UTCDateTime desc Limit 1;";
		
	$query2 = "select Date_sub(UTCDateTime, interval $HrsFromZ hour) as DT, sum(Rain) as 1hr_rain, max(WindGust) as 1hr_Gust from WindRainData where Date_sub(UTCDateTime, interval $HrsFromZ hour) > DATE_SUB(NOW(), INTERVAL 1 HOUR);";
	
	$query2a = "select Date_sub(UTCDateTime, interval $HrsFromZ hour) as DT, sum(Rain) as 10min_rain, max(WindGust) as 10min_Gust from WindRainData where Date_sub(UTCDateTime, interval $HrsFromZ hour) > DATE_SUB(NOW(), INTERVAL 10 MINUTE);";
		
	$query2b = "select Date_sub(UTCDateTime, interval $HrsFromZ hour) as DT, sum(Rain) as 20min_rain, max(WindGust) as 20min_Gust from WindRainData where Date_sub(UTCDateTime, interval $HrsFromZ hour) > DATE_SUB(NOW(), INTERVAL 20 MINUTE);";
	
	$query2c = "select Date_sub(UTCDateTime, interval $HrsFromZ hour) as DT, sum(Rain) as 30min_rain, max(WindGust) as 30min_Gust from WindRainData where Date_sub(UTCDateTime, interval $HrsFromZ hour) > DATE_SUB(NOW(), INTERVAL 30 MINUTE);";
		
	$query3 = "select Date_sub(UTCDateTime, interval $HrsFromZ hour) as DT, sum(Rain) as 3hr_rain, max(WindGust) as 3hr_Gust from WindRainData where Date_sub(UTCDateTime, interval $HrsFromZ hour) > DATE_SUB(NOW(), INTERVAL 3 HOUR);";
		
	$query4 = "select Date_sub(UTCDateTime, interval $HrsFromZ hour) as DT, sum(Rain) as 6hr_rain, max(WindGust) as 6hr_Gust from WindRainData where Date_sub(UTCDateTime, interval $HrsFromZ hour) > DATE_SUB(NOW(), INTERVAL 6 HOUR);";
		
	$query5 = "select Date_sub(UTCDateTime, interval $HrsFromZ hour) as DT, sum(Rain) as 12hr_rain, max(WindGust) as 12hr_Gust from WindRainData where Date_sub(UTCDateTime, interval $HrsFromZ hour) > DATE_SUB(NOW(), INTERVAL 12 HOUR);";
		
	$query6 = "select Date_sub(UTCDateTime, interval $HrsFromZ hour) as DT, sum(Rain) as 24hr_rain, max(WindGust) as 24hr_Gust from WindRainData where Date_sub(UTCDateTime, interval $HrsFromZ hour) > DATE_SUB(NOW(), INTERVAL 24 HOUR);";
		
	$query7 = "select DATE_FORMAT(Date_sub(UTCDateTime, interval $HrsFromZ hour), '%H:%i') as DT, WindGust as Gust from WindRainData where Date(Date_sub(UTCDateTime, interval $HrsFromZ hour)) = DATE(NOW()) order by WindGust DESC limit 1;";
	
	$query7a = "select DATE_FORMAT(Date_sub(UTCDateTime, interval $HrsFromZ hour), '%H:%i') as DT, TempFOut as MaxTemp from SHTData where Date(Date_sub(UTCDateTime, interval $HrsFromZ hour)) = DATE(NOW()) order by TempFOut DESC limit 1;";
	
	$query7b = "select DATE_FORMAT(Date_sub(UTCDateTime, interval $HrsFromZ hour), '%H:%i') as DT, TempFOut as MinTemp from SHTData where Date(Date_sub(UTCDateTime, interval $HrsFromZ hour)) = DATE(NOW()) order by TempFOut ASC limit 1;";
	
	$query7c = "select MaxTemp as AvgMaxTemp, MinTemp as AvgMinTemp from PITMaxMin where month(now()) = MM and day(now()) = DD;";
	
	$query7d = "select MaxRecord, MaxYear, MinRecord, MinYear from PITMaxMin where month(now()) = MM and day(now()) = DD;";
		
	$query8 = "select sum(DeltaPressure) as 1hr_DeltaMb from BMEData where Date_sub(UTCDateTime, interval $HrsFromZ hour) > DATE_SUB(NOW(), INTERVAL 1 HOUR);";
		
	$query9 = "select sum(DeltaPressure) as 3hr_DeltaMb from BMEData where Date_sub(UTCDateTime, interval $HrsFromZ hour) > DATE_SUB(NOW(), INTERVAL 3 HOUR);";
		
	$query10 = "select sum(DeltaPressure) as 6hr_DeltaMb from BMEData where Date_sub(UTCDateTime, interval $HrsFromZ hour) > DATE_SUB(NOW(), INTERVAL 6 HOUR);";
		
	$query11 = "SELECT DATE_FORMAT(Date_sub(UTCDateTime, interval $HrsFromZ hour), '%a, %e %b') AS D1, sum(Rain) as Rain1, max(WindGust) as Gust1 FROM WindRainData Where Date(Date_sub(UTCDateTime, interval $HrsFromZ hour)) = DATE(DATE_SUB(NOW(), INTERVAL 1 Day));";
	
	$query11a = "SELECT max(TempFOut) as MaxTemp, min(TempFOut) as MinTemp, FORMAT((max(TempFOut)+min(TempFOut))/2,1) as MeanTemp FROM SHTData Where Date(Date_sub(UTCDateTime, interval $HrsFromZ hour)) = DATE(DATE_SUB(NOW(), INTERVAL 1 Day));";

	$query12 = "SELECT DATE_FORMAT(Date_sub(UTCDateTime, interval $HrsFromZ hour), '%a, %e %b') AS D2, sum(Rain) as Rain2, max(WindGust) as Gust2 FROM WindRainData Where Date(Date_sub(UTCDateTime, interval $HrsFromZ hour)) = DATE(DATE_SUB(NOW(), INTERVAL 2 Day));";
	
	$query12a = "SELECT max(TempFOut) as MaxTemp, min(TempFOut) as MinTemp, FORMAT((max(TempFOut)+min(TempFOut))/2,1) as MeanTemp FROM SHTData Where Date(Date_sub(UTCDateTime, interval $HrsFromZ hour)) = DATE(DATE_SUB(NOW(), INTERVAL 2 Day));";
		
	$query13 = "SELECT DATE_FORMAT(Date_sub(UTCDateTime, interval $HrsFromZ hour), '%a, %e %b') AS D3, sum(Rain) as Rain3, max(WindGust) as Gust3 FROM WindRainData Where Date(Date_sub(UTCDateTime, interval $HrsFromZ hour)) = DATE(DATE_SUB(NOW(), INTERVAL 3 Day));";
	
	$query13a = "SELECT max(TempFOut) as MaxTemp, min(TempFOut) as MinTemp, FORMAT((max(TempFOut)+min(TempFOut))/2,1) as MeanTemp FROM SHTData Where Date(Date_sub(UTCDateTime, interval $HrsFromZ hour)) = DATE(DATE_SUB(NOW(), INTERVAL 3 Day));";
		
	$query14 = "SELECT DATE_FORMAT(Date_sub(UTCDateTime, interval $HrsFromZ hour), '%a, %e %b') AS D4, sum(Rain) as Rain4, max(WindGust) as Gust4 FROM WindRainData Where Date(Date_sub(UTCDateTime, interval $HrsFromZ hour)) = DATE(DATE_SUB(NOW(), INTERVAL 4 Day));";
	
	$query14a = "SELECT max(TempFOut) as MaxTemp, min(TempFOut) as MinTemp, FORMAT((max(TempFOut)+min(TempFOut))/2,1) as MeanTemp FROM SHTData Where Date(Date_sub(UTCDateTime, interval $HrsFromZ hour)) = DATE(DATE_SUB(NOW(), INTERVAL 4 Day));";
		
	$query15 = "SELECT DATE_FORMAT(Date_sub(UTCDateTime, interval $HrsFromZ hour), '%a, %e %b') AS D5, sum(Rain) as Rain5, max(WindGust) as Gust5 FROM WindRainData Where Date(Date_sub(UTCDateTime, interval $HrsFromZ hour)) = DATE(DATE_SUB(NOW(), INTERVAL 5 Day));";
	
	$query15a = "SELECT max(TempFOut) as MaxTemp, min(TempFOut) as MinTemp, FORMAT((max(TempFOut)+min(TempFOut))/2,1) as MeanTemp FROM SHTData Where Date(Date_sub(UTCDateTime, interval $HrsFromZ hour)) = DATE(DATE_SUB(NOW(), INTERVAL 5 Day));";
	
	if ($result = mysqli_query($link, $query0)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<h2 style="text-align: center;">Today: %s</h2>',
				 $row['Today_Date']);
			}
			/* free result set */
			mysqli_free_result($result);
		}
		
	// Display the results
	echo "<h3 style='text-align: center;'>Sunrise: {$sunrise_time} Sunset: {$sunset_time}<br>Daylight Duration: {$time_difference_string}</h3>";
	
	if ($result = mysqli_query($link, $query1)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<h2>Latest Observation: %s, %s ET</h2>
				<dl>
					<dd><a href="https://en.wikipedia.org/wiki/Wind_direction" target="_blank" title="Atmospheric wind direction explained.  I measure wind direction with a wind vane. Wind direction points to where wind is blowing from, not going to.">Wind Direction</a>: %s (%s&#176;)</dd>
					
					<dd><a href="https://en.wikipedia.org/wiki/Wind_speed" target="_blank" title="Atmospheric wind speed explained. I measure wind speed with a spinning aneometer. Knots are nautical miles per hours. Add 15&#37; to knots to convert to miles per hour.">Wind Speed:</a> %s knots, Gust: %s knots</dd>
					
					<dd id="padtop"><a href="https://en.wikipedia.org/wiki/Atmospheric_pressure" target="_blank" title="Earth&#39; atmospheric pressure explained and why adjustments to sea-level are necessary. I measure atmospheric pressure with electronic devices sensitive to ambient atmospheric pressure.">Atmospheric Pressure (Sea-Level Adjusted):</a> %s mb</dd>
					
					<dd>5-min Delta Pressure: %s mb</dd>
					
					<dd id="padtop">5-min Rain Amount: %s inches</dd>
					<dd>Last Rain Ended: %s ET</dd>
					
					<dd id="padtop">Air Temperature: %s&#176;F</dd>
					
					<dd>5-min Delta Temperature: %s&#176;F</dd>					
					
					<dd id="padtop"><a shref="https://www.davisinstruments.com/pages/what-is-dew-point?_pos=1&_psq=dew+p&_ss=e&_v=1.0" target="_blank" title="Dew point explained and its relationship to  relative humidity.">Dew Point</a>: %s&#176;F</dd>
					<dd>Relative Humidity: %s&#37;</dd>
					
					<dd id="padtop">Attic Temperature: %s&#176;F</dd>
					<dd>Attic Relative Humidity: %s&#37;</dd>
					
				</dl>', $row['TD'], $row['ET'], $row['DirLetter'], $row['DirNum'], $row['Knots'], $row['GKnots'], $row['BP'], $row['DBP'], $row['Rain'], $row['LRain'], $row['TFout'], $row['DF'], $row['DP'], $row['RHout'], $row['TFin'], $row['RHin'],); 
			}
			/* free result set */
			mysqli_free_result($result);
		}
	
	if ($result = mysqli_query($link, $query2a)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<h2>Past Rain Amounts and Peak Wind</h2>
				<dl><dd>10-min: %s inches, %s knots</dd>',
				 $row['10min_rain'], $row['10min_Gust']);
			}
			/* free result set */
			mysqli_free_result($result);
		}
		
	if ($result = mysqli_query($link, $query2b)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<dd>20-min: %s inches, %s knots</dd>',
				 $row['20min_rain'], $row['20min_Gust']);
			}
			/* free result set */
			mysqli_free_result($result);
		}
		
	if ($result = mysqli_query($link, $query2c)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<dd>30-min: %s inches, %s knots</dd>',
				 $row['30min_rain'], $row['30min_Gust']);
			}
			/* free result set */
			mysqli_free_result($result);
		}

	if ($result = mysqli_query($link, $query2)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<dd>1-hr: %s inches, %s knots</dd>',
				 $row['1hr_rain'], $row['1hr_Gust']);
			}
			/* free result set */
			mysqli_free_result($result);
		}
		
	if ($result = mysqli_query($link, $query3)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<dd>3-hr: %s inches, %s knots</dd>',
				 $row['3hr_rain'], $row['3hr_Gust']);
			}
			/* free result set */
			mysqli_free_result($result);
		}
		
	if ($result = mysqli_query($link, $query4)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<dd>6-hr: %s inches, %s knots</dd>',
				 $row['6hr_rain'], $row['6hr_Gust']);
			}
			/* free result set */
			mysqli_free_result($result);
		}
		
	if ($result = mysqli_query($link, $query5)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<dd>12-hr: %s inches, %s knots</dd>',
				 $row['12hr_rain'], $row['12hr_Gust']);
			}
			/* free result set */
			mysqli_free_result($result);
		}

	if ($result = mysqli_query($link, $query6)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<dd>24-hr: %s inches, %s knots</dd></dl>',
				 $row['24hr_rain'], $row['24hr_Gust']);
			}
			/* free result set */
			mysqli_free_result($result);
		}
		
	if ($result = mysqli_query($link, $query7)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<h2>Today&#39s Notable Weather Data</h2>
			 <dl><dd>Peak Gust: <orange>%s (knots)</orange> at %s</dd>',
					$row['Gust'], $row['DT']);
			}
			/* free result set */
			mysqli_free_result($result);
		}

	if ($result = mysqli_query($link, $query7a)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<dd>Max Temp: <red>%s&#176;F</red> at %s</dd>',
					$row['MaxTemp'], $row['DT']);
			}
			/* free result set */
			mysqli_free_result($result);
		}
		
	if ($result = mysqli_query($link, $query7b)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<dd>Min Temp: <blue>%s&#176;F</blue> at %s</dd>',
					$row['MinTemp'], $row['DT']);
			}
			/* free result set */
			mysqli_free_result($result);
		}

	if ($result = mysqli_query($link, $query7c)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<dd id="padtop"><u>Pittsburgh Airport 30-year Average</u></dd>
			<dd id="indent">Max Temp: %s&#176;F and Min Temp: %s&#176;F</dd>',
					$row['AvgMaxTemp'], $row['AvgMinTemp']);
			}	
			/* free result set */
			mysqli_free_result($result);
		}

	if ($result = mysqli_query($link, $query7d)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<dd><u>Record Temps and Year</u></dd>
			<dd id="indent">Max/Year: %s&#176;F / %s and Min/Year: %s&#176;F / %s</dd></dl>',
					$row['MaxRecord'], $row['MaxYear'], $row['MinRecord'], $row['MinYear']);
			}	
			/* free result set */
			mysqli_free_result($result);
		}


	if ($result = mysqli_query($link, $query8)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<h2>Past Atmospheric Pressure Changes (mb)</h2>
				<dl><dd>1-hour: %s</dd>',
				 $row['1hr_DeltaMb']);
			}
			/* free result set */
			mysqli_free_result($result);
		}
			
	if ($result = mysqli_query($link, $query9)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<dd>3-hour: %s</dd>',
				 $row['3hr_DeltaMb']);
			}
			/* free result set */
			mysqli_free_result($result);
		}

	if ($result = mysqli_query($link, $query10)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<dd>6-hour: %s</dd></dl>',
				 $row['6hr_DeltaMb']);
			}
			/* free result set */
			mysqli_free_result($result);
		}
		
	
		
	if ($result = mysqli_query($link, $query11)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<h2>Past Daily Rain Amounts, Peak Gust, Max, Min, Mean Temp</h2>
				<dl><dd>%s, %s inches, %s knots, ',
				$row['D1'], $row['Rain1'], $row['Gust1']);
			}
			/* free result set */
			mysqli_free_result($result);
		}

	if ($result = mysqli_query($link, $query11a)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('%s&#176;F, %s&#176;F, %s&#176;F</dd>',
				$row['MaxTemp'], $row['MinTemp'], $row['MeanTemp']);
			}
			/* free result set */
			mysqli_free_result($result);
		}

	if ($result = mysqli_query($link, $query12)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<dd>%s, %s inches, %s knots, ',
				$row['D2'], $row['Rain2'], $row['Gust2']);
			}
			/* free result set */
			mysqli_free_result($result);
		}
		
	if ($result = mysqli_query($link, $query12a)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('%s&#176;F, %s&#176;F, %s&#176;F</dd>',
				$row['MaxTemp'], $row['MinTemp'], $row['MeanTemp']);
			}
			/* free result set */
			mysqli_free_result($result);
		}
		
	if ($result = mysqli_query($link, $query13)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<dd>%s, %s inches, %s knots, ',
				$row['D3'], $row['Rain3'], $row['Gust3']);
			}
			/* free result set */
			mysqli_free_result($result);
		}

	if ($result = mysqli_query($link, $query13a)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('%s&#176;F, %s&#176;F, %s&#176;F</dd>',
				$row['MaxTemp'], $row['MinTemp'], $row['MeanTemp']);
			}
			/* free result set */
			mysqli_free_result($result);
		}

	if ($result = mysqli_query($link, $query14)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<dd>%s, %s inches, %s knots, ',
				$row['D4'], $row['Rain4'], $row['Gust4']);
			}
			/* free result set */
			mysqli_free_result($result);
		}

	if ($result = mysqli_query($link, $query14a)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('%s&#176;F, %s&#176;F, %s&#176;F</dd>',
				$row['MaxTemp'], $row['MinTemp'], $row['MeanTemp']);
			}
			/* free result set */
			mysqli_free_result($result);
		}
		
	if ($result = mysqli_query($link, $query15)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('<dd>%s, %s inches, %s knots, ',
				$row['D5'], $row['Rain5'], $row['Gust5']);
			}
			/* free result set */
			mysqli_free_result($result);
		}

	if ($result = mysqli_query($link, $query15a)) {
		while ($row = mysqli_fetch_assoc($result)) {
			printf ('%s&#176;F,  %s&#176;F, %s&#176;F</dd></dl>',
				$row['MaxTemp'], $row['MinTemp'], $row['MeanTemp']);
			}
			/* free result set */
			mysqli_free_result($result);
		}

		/* close connection */
		mysqli_close($link);
?>

</body>
</html>

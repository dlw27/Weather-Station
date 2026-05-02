# WR-01 JSON parsing demo
# S. Miller 3 June 2025
#
# This Python script provides a simple demonstration of reading and parsing
# data in JSON format from the Argent Data Systems WR-01 wind/rain sensor
# interface.
#
# To use with a Raspberry Pi, first ensure you have the serial port enabled
# (under 'interfaces' in Control Centre). Wire header pin 2 to VIN, pin 6
# to GND, pin 8 to RX, and pin 10 to TX. This will power the WR-01 from the
# Raspberry Pi's 5v supply and use the TTL serial port.
#
# This script demonstrates the basics of reading a single line from the
# serial port, parsing the line as JSON, and extracting a few values.
# wind_dir and wind_speed values are grouped by averaging intervals under
# "instant", "2min", "10min", and "custom". "gust" gives the peak wind speed
# in the 2min, 10min, and custom groups. Rainfall values are reported
# as "rain_1hr", "rain24_hr", "rain_total", and "last rain". Units are
# meters/second for speeds, degrees for directions, millimeters for rain,
# and minutes for time since last rain.

import serial
import json
from datetime import datetime, UTC, timedelta
import time
import sys
import mariadb


def truncate_to_minute(dt):
  """Truncates a datetime object to the minute."""
  return dt.replace(second=0, microsecond=0)


def delta(current_value, previous_value):
    if previous_value == 0:
        return 0
    return current_value - previous_value


def roundup_dt_to_5min(dt):
    """
    Rounds up a datetime object to the next 5-minute interval.
    """

    # Calculate the time to add to reach the next 5-minute mark
    # Total seconds in the current time
    total_seconds = (dt.hour * 3600) + (dt.minute * 60) + dt.second + (dt.microsecond / 1e6)

    # Total seconds in a 5-minute interval
    interval_seconds = 300.0

    # Calculate the number of seconds to the next interval
    # (interval_seconds - (total_seconds % interval_seconds)) % interval_seconds handles
    # cases where the time is already on an interval (result should be 0)
    seconds_to_add = (interval_seconds - (total_seconds % interval_seconds)) % interval_seconds

    return dt + timedelta(seconds=seconds_to_add)


previous = 0
now = datetime.now(UTC)
print(f"Start of Python code timestamp: {now}\n")

while True:
        now = datetime.now(UTC)
        current_minute = now.minute
        current_second = now.second
        print(f"{current_minute}:{current_second}")
        # Check if the current minute is a multiple of 5.
        if current_minute % 5 == 0 and current_second == 0:
            print(f"Configured at {now}")
            break  # Exit the while loop after minute is multiple of 5 and second is zero
        else:
            time.sleep(0.5)

now = datetime.now(UTC)
print(f"Start of cycle every 5 min: {now}\n")

# Open serial port - adjust port and baud rate as needed
ser = serial.Serial('/dev/ttyAMA0', 115200, timeout=1)

# Set interface to JSON mode
ser.write(b'!20=32\r')
ser.write(b'!19=300\r')
ser.write(b'!9=300\r')
ser.flush()

while True:
    try:

        # Read a line (assumes JSON is newline-terminated)
        line = ser.readline().decode('utf-8').strip()

        if not line:
            continue
        with open("output.txt", "a") as f:
            f.write(f"{line}\n")
        # Parse the JSON object
        data = json.loads(line)

        # Access nested and flat values
        instant = data.get("instant", {})
        avg_2min = data.get("2min", {})
        avg_10min = data.get("10min", {})
        custom = data.get("custom", {})
        rain_1hr = data.get("rain_1hr")*0.0393701
        rain_24hr = round(data.get("rain_24hr")*0.0393701,ndigits=2)
        rain_total = data.get("rain_total")*0.0393701
        last_rain = data.get("last_rain")
        Avg_Dir = custom.get('wind_dir', '?')
        Avg_Speed = round(custom.get('wind_speed', '?') * 1.94384)
        Gust_Speed = round(custom.get('gust', '?') * 1.94384)

        rain = delta(rain_total, previous)
        if rain <0:
           rain = 0
        previous = rain_total

        now = datetime.now(UTC)
        current_minute = now.minute
        print(f"timestamp: {now}")
        if current_minute % 5 == 0:
            truncated_time = truncate_to_minute(now)
        else:
            rounded_time = roundup_dt_to_5min(now)
            truncated_time = truncate_to_minute(rounded_time)

        print(f"timestamp: {truncated_time}")

        yyyy = (truncated_time.strftime("%Y"))
        print(truncated_time.strftime("%m"))
        mm = (truncated_time.strftime("%m"))
        print(truncated_time.strftime("%d"))
        dd = (truncated_time.strftime("%d"))
        print(truncated_time.strftime("%H"))
        hh = int((truncated_time.strftime("%H")))
        print(truncated_time.strftime("%M"))
        mn = (truncated_time.strftime("%M"))
        hh3 = int(3 + int(hh / 3) * 3)
        hh6 = int(6 + int(hh / 6) * 6)
        hh12 = int(12 + int(hh / 12) * 12)
        print(yyyy, mm, dd, hh, mn, hh3, hh6, hh12)


        # Print out a few things
        print(f"Instant Wind: {round(instant.get('wind_speed', '?')*1.94384)} knots from {instant.get('wind_dir', '?')}°")
        print(f"2-min Avg Wind: {round(avg_2min.get('wind_speed', '?')*1.94384)} knots")
        print(f"10-min Avg Wind: {round(avg_10min.get('wind_speed', '?')*1.94384)} knots")
        print(f"Custom Wind: {round(custom.get('gust', '?')*1.94384)} knots")
        print(
            f'Rain (1h): {rain_1hr} inch\nRain (24h): {rain_24hr} inch\nRain (Total): {rain_total} inch\nLast (Rain): {last_rain} min ago\n')
        print("trying database")
        try:
            con = mariadb.connect(host="localhost", user="root", password="password", database="Weather")
            cur = con.cursor()
            cur.execute(
                "INSERT INTO WindRainData (UTCDateTime, YYYY, MM, DD, HH, HH3, HH6, HH12, MN, WindNumDir, WindSpeed, WindGust, Rain, LastRain) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                (truncated_time, yyyy, mm, dd, hh, hh3, hh6, hh12, mn, Avg_Dir, Avg_Speed, Gust_Speed, rain, last_rain))
            con.commit()
            con.close()

        except mariadb.Error as e:
            print(f"Error connecting to MariaDB Platform: {e}")
            sys.exit(1)
    #except json.JSONDecodeError as e:
    #print(f"JSON parse error: {e}")
    except Exception as e:
        print(f"Unexpected error: {e}")
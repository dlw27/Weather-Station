#1. Red (VCC): Connect to a 3.3V or 5V pin (e.g., Pin 2 or 4).
#2. Black (GND): Connect to a Ground pin (e.g., Pin 6).
#3. Yellow (SCL) (Clock): Connect to the SCL pin (GPIO 3, Pin 5).
#4. White (SDA) (Data): Connect to the SDA pin (GPIO 2, Pin 3).

# Check I2C address as 0x44 with i2cdetect -y 1
import sched
import time
from datetime import datetime, timedelta, UTC
import smbus2
import math
import mariadb
import sys

bus = smbus2.SMBus(1)

# Create a scheduler instance
scheduler = sched.scheduler(time.time, time.sleep)
previous_temp = 0


def truncate_to_minute(dt):
  """Truncates a datetime object to the minute."""
  return dt.replace(second=0, microsecond=0)


def delta(current_value, previous_value):
    if previous_value == 0:
        return 0
    return current_value - previous_value


def job_function(name):
    """The function to be executed by the scheduler."""
    global previous_temp
    # SHT30 address, 0x44(68)
    bus.write_i2c_block_data(0x44, 0x2C, [0x06])

    time.sleep(0.5)

    # SHT31 address, 0x44(68)
    # Read data back from 0x00(00), 6 bytes
    # Temp MSB, Temp LSB, Temp CRC, Humidity MSB, Humidity LSB, Humidity CRC
    data = bus.read_i2c_block_data(0x44, 0x00, 6)

    # Convert the data
    temp = data[0] * 256 + data[1]
    temp_c = -45 + (175 * temp / 65535.0)
    temp_C = round(temp_c, 1)
    temp_f = -49 + (315 * temp / 65535.0)
    # Convert Celsius to Fahrenheit to nearest whole degree
    temp_F = round(temp_f)
    humidity = 100 * (data[3] * 256 + data[4]) / 65535.0
    humidity_percent = round(humidity)

    # Calculate dewpoint using Magnus formula parameters
    a = 17.625
    b = 243.04

    # Calculate intermediate alpha value
    alpha = ((a * temp_c) / (b + temp_c)) + math.log(humidity / 100.0)

    # Calculate Dew Point in Celsius
    dewpoint_c = (b * alpha) / (a - alpha)

    # Convert Celsius to Fahrenheit
    dewpoint_F = round((dewpoint_c * 9 / 5) + 32)

    now = datetime.now(UTC)
    truncated_time = truncate_to_minute(now)

    # Output data to screen
    print(f"\ntimestamp: {now}")
    print(f"\nDateTime on 5-min intervals: {truncated_time}")
    print(f"Outside Temperature in Celsius is: {temp_C} C")
    print(f"Outside Temperature in Fahrenheit is: {temp_F} F")
    print(f"Outside Relative Humidity is: {humidity_percent} %")
    print(f"Outside Dew Point: {dewpoint_F} F")

    yyyy = (now.strftime("%Y"))
    print(now.strftime("%m"))
    mm = (now.strftime("%m"))
    print(now.strftime("%d"))
    dd = (now.strftime("%d"))
    print(now.strftime("%H"))
    hh = int((now.strftime("%H")))
    print(now.strftime("%M"))
    mn = (now.strftime("%M"))
    hh3 = int(3 + int(hh / 3) * 3)
    hh6 = int(6 + int(hh / 6) * 6)
    hh12 = int(12 + int(hh / 12) * 12)
    print(yyyy, mm, dd, hh, mn, hh3, hh6, hh12)

    print(f"\nDateTime on 5-min intervals: {truncated_time}")

    precise_temp = temp_f
    delta_temp = delta(precise_temp, previous_temp)
    print(f"Delta: {delta_temp}, New Value: {precise_temp}")
    previous_temp = precise_temp



    #BME280----------------------------------------------------------
    try:
        con = mariadb.connect(host="localhost", user="root", password="password", database="Weather")
        cur = con.cursor()
        cur.execute("INSERT INTO SHTData (UTCDateTime, YYYY, MM, DD, HH, HH3, HH6, HH12, MN, TempFOut, DeltaF, TempCOut, RHOut, DewPoint) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    (truncated_time, yyyy, mm, dd, hh, hh3, hh6, hh12, mn, temp_F, delta_temp, temp_C, humidity_percent, dewpoint_F))
        con.commit()
        con.close()

    except mariadb.Error as e:
        print(f"Error connecting to MariaDB Platform: {e}")
        sys.exit(1)

    print(f"[{datetime.now(UTC).strftime('%Y-%m-%d %H:%M:%S')}] Running job for {name}")
    # Schedule the next occurrence after this job finishes
    schedule_next_run()


def schedule_next_run():
    """Calculates and schedules the very next run time."""
    now = datetime.now(UTC)

    # Calculate the next full hour
    next_hour = now.replace(minute=0, second=0, microsecond=0) + timedelta(hours=1)

    # Define the target minutes past the hour
    target_minutes = [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55]

    next_run_time = None

    # Find the soonest time in the future that matches one of the target minutes
    for minute in target_minutes:
        potential_time = now.replace(minute=minute, second=0, microsecond=0)
        if potential_time > now:
            next_run_time = potential_time
            break

    # If no time this hour works (e.g., it's already past 15 min past the hour),
    # use the first time of the *next* hour (5 min past the next hour)
    if next_run_time is None:
        next_run_time = next_hour.replace(minute=target_minutes[0])

    # Calculate the delay in seconds until the next run time
    delay_seconds = (next_run_time - now).total_seconds()

    # Schedule the event
    print(f"Scheduling next run for {next_run_time.strftime('%Y-%m-%d %H:%M:%S')} (in {delay_seconds:.1f} seconds)")
    scheduler.enter(delay_seconds, 1, job_function, ('Scheduled Task',))


if __name__ == "__main__":
    print("Scheduler started. Waiting for the next scheduled run...")
    # Initial call to start the scheduling process
    schedule_next_run()
    # Start the scheduler loop (this blocks the main thread)
    scheduler.run()
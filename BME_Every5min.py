import sched
import time
from datetime import datetime, timedelta, UTC
import smbus2
import bme280
import mariadb
import sys

# Create a scheduler instance
scheduler = sched.scheduler(time.time, time.sleep)
previous_mb = 0


def truncate_to_minute(dt):
  """Truncates a datetime object to the minute."""
  return dt.replace(second=0, microsecond=0)


def delta(current_value, previous_value):
    if previous_value == 0:
        return 0
    return current_value - previous_value


def job_function(name):
    """The function to be executed by the scheduler."""
    global previous_mb

    port = 1
    address = 0x77  # Adafruit BME280 address. Other BME280s may be different
    bmebus = smbus2.SMBus(port)
    bme280.load_calibration_params(bmebus, address)
    pressure_offset = 38

    bme280_data = bme280.sample(bmebus,address)
    humidity_in = round(bme280_data.humidity)
    temp_c_in = round(bme280_data.temperature,1)


    # Output data to screen
    now = datetime.now(UTC)
    truncated_time = truncate_to_minute(now)
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

    print( f"Celsius Temp: {temp_c_in}")
    pressure  = round(bme280_data.pressure + pressure_offset,1)
    precise_pressure  = bme280_data.pressure

    delta_pressure = delta(precise_pressure, previous_mb)
    print(f"Delta: {delta_pressure}, New Value: {precise_pressure}")
    previous_mb = precise_pressure

    temp_f_in = round((bme280_data.temperature * 9/5) + 32)
    print(f"Indoor Relative Humidity: {humidity_in} %")
    print(f"Atmospheric sea-level pressure: {pressure} mb")
    print(f"Atmospheric precise sea-level pressure: {precise_pressure} mb")
    print(f"Indoor Air temperature: {temp_f_in} F")

    #BME280----------------------------------------------------------
    try:
        con = mariadb.connect(host="localhost", user="root", password="password", database="Weather")
        cur = con.cursor()
        cur.execute("INSERT INTO BMEData (UTCDateTime, YYYY, MM, DD, HH, HH3, HH6, HH12, MN, TempFIn, RHIn, AtmPressure, DeltaPressure) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    (truncated_time, yyyy, mm, dd, hh, hh3, hh6, hh12, mn, temp_f_in, humidity_in, pressure, delta_pressure))
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



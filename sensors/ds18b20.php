<?php
/*
    phpTempReader - reading temperatures from sensors and put them in arrays and variables.
    Copyright (C) 2017-2022  Ole-Henrik Jakobsen

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
    
    
    This is a PHP-CLI script made to pick up data from the DS18B20 temperature sensor
    and perhaps similar types.


	Configure the sensor in the config.ini file.
	
	Last updated: 2022-08-09
*/
// start the code
$tempreader = false;
$sensor_name = "DS18B20";
$sensor_name_lwr = strtolower($sensor_name);
$sensor_directory = "/sys/bus/w1/devices";
if(!is_dir($sensor_directory)) {
	die("\nError [" . $sensor_name . "]: Can't find any sensors. Please check the configuration file, kernel modules, sensors and cable connection.\n\n");
}

// put array data into variables
if(@$ini_array[$sensor_name_lwr]["SENSORSERIAL"]) {
	$ds18b20_serial_array = $ini_array[$sensor_name_lwr]["SENSORSERIAL"];
}
$ds18b20_unit = $ini_array[$sensor_name_lwr]["SENSORUNIT"];
$ds18b20_dec = $ini_array[$sensor_name_lwr]["SENSORDECIMALS"];
$ds18b20_req = $ini_array[$sensor_name_lwr]["SENSORREQUESTS"];
$ds18b20_retry = $ini_array[$sensor_name_lwr]["SENSORMAXRETRIES"];
$ds18b20_delay = $ini_array[$sensor_name_lwr]["SENSORRETRYDELAY"];

if(!$ds18b20_dec) { $ds18b20_dec = 0; }
if(!$ds18b20_delay) { $ds18b20_delay = 0; }
$divide = str_pad("1", $ds18b20_dec+1, "0", STR_PAD_RIGHT);

if(!@$ds18b20_serial_array) {
	$ds18b20_serial_array = array_values(array_diff(scandir($sensor_directory), array('w1_bus_master1','..', '.')));
}

$ds18b20_temp_array = array();

// get temperature(s) from sensor(s)
$serial_length = count($ds18b20_serial_array);

for($i=0;$i<$serial_length;$i++) {
	if(preg_match("/^(10|22|28)-[0-9a-f]{12}/", $ds18b20_serial_array[$i])) { // check if the file matches the serial number of DS18B20 sensors, else skip to next.
		$sensorfile = "" . $sensor_directory . "/" . $ds18b20_serial_array[$i] . "/w1_slave";
		
		// reset for next sensor
		$rawtemp = "";
		$ds18b20_yes = 0;
		
		for($isub=0;$isub<$ds18b20_retry;$isub++) {
			$handle = fopen($sensorfile, "r");
			$contents = fread($handle, filesize($sensorfile));
			if(preg_match("/YES/", $contents) && !preg_match("/t=85000/", $contents)) { // t=85000 (85°C) is usually a sign of an error, or a reset. We re-read it instead to get a new value.
				$ds18b20_yes++;
				$rawtemp[] = preg_replace("/(.*)[\r\n](.*) t=(-?[0-9]{1,})/", "$3", $contents);
			}
			fclose($handle);
			// stop the subloop if we got enough successfull requests
			if($ds18b20_yes == $ds18b20_req) {
				$isub = $ds18b20_retry;
			}
			sleep($ds18b20_delay);
		}
		if($ds18b20_yes < $ds18b20_req) {
			die("Error [" . $sensor_name . "]: read error from the sensor (" . $ds18b20_serial_array[$i] . "): $ds18b20_yes of $ds18b20_req successfull requests. Try to increase the maximum retries or set longer delay between each request.");
		}
		else {
			$rawtemp = array_filter($rawtemp);
			if(count($rawtemp) == $ds18b20_req) {
				// get the average from the array
				$tempavg = array_sum($rawtemp)/$ds18b20_req;
				// convert input from sensor to correct decimals
				$ds18b20_temp_array[] = number_format($tempavg/$divide, $ds18b20_dec , '.', '');
			}
			else {
				die("Error [" . $sensor_name . "]: did not get enough readings, try to run the script again.");
			}
		}
	}
}

if($test) {
	print("\nTempReader TEST MODE [" . $sensor_name . "]:\nCheck your data if it looks OK, disable test mode in the configuration file.\n\n");
	print("Configuration file data:\n");
	print_r($ini_array);
	print("Temperature value(s):\n");
	print_r($ds18b20_temp_array);
	print("Serial number value(s):\n");
	print_r($ds18b20_serial_array);
	print("Temperature sensors with serialnumbers and its value(s):\n");
	$ds18b20_sensorserialtemp_array = array_combine($ds18b20_serial_array, $ds18b20_temp_array);
	print_r($ds18b20_sensorserialtemp_array);
	print("********************************************************************************\n");
}
else if(!$test && $ds18b20_unit && array_key_exists(0, $ds18b20_temp_array)) {
	$tempreader = true;
}
else {
	die("\nError [" . $sensor_name . "]: Can't find any sensors. Please check the configuration file, kernel modules, sensors and cable connection.\n\n");
}
?>

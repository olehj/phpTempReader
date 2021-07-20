<?php
/*
    phpTempReader - reading temperatures from sensors and put them in arrays and variables.
    Copyright (C) 2017-2020  Ole-Henrik Jakobsen

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
    
    
    This is a PHP-CLI script made to pick up data from the dummy temperature sensor.
    This is just for testing in case you haven't got any sensors yet.
    Dummy is based upon the data from DS18B20 sensors.
    

	Configure the sensor in the config.ini file.
	
	Last updated: 2020-12-25
*/
// start the code
$tempreader = false;
$sensor_name = "Dummy";
$sensor_directory = "sensors/dummies";

// put array data into variables
if(@$ini_array["dummy"]["SENSORSERIAL"]) {
	$dummy_serial_array = $ini_array["dummy"]["SENSORSERIAL"];
}
$dummy_unit = "C";
$dummy_dec = 3;

if(!$dummy_dec) { $dummy_dec = 0; }

if(!@$dummy_serial_array) {
	$dummy_serial_array = array_values(array_diff(scandir($sensor_directory), array('..', '.')));
}

// get temperature from sensor
$serial_length = count($dummy_serial_array);
for($i=0;$i<$serial_length;$i++) {
	$sensorfile = "" . $sensor_directory . "/" . $dummy_serial_array[$i] . "";
	$handle = fopen($sensorfile, "r");
	$contents = fread($handle, filesize($sensorfile));
	$rawtemp = preg_replace("/(.*)[\r\n](.*) t=(-?[0-9]{1,})/", "$3", $contents);
	
	// convert input from sensor to correct decimals
	$divide = str_pad("1", $dummy_dec+1, "0", STR_PAD_RIGHT);
	$dummy_temp_array[] = number_format($rawtemp/$divide, $dummy_dec , '.', '');
}

if($test) {
	print("\nTempReader TEST MODE [" . $sensor_name . "]:\nCheck your data if it looks OK, disable test mode in the configuration file.\n\n");
	print("Configuration file data:\n");
	print_r($ini_array);
	print("Temperature value(s):\n");
	print_r($dummy_temp_array);
	print("Serial number value(s):\n");
	print_r($dummy_serial_array);
	print("Temperature sensors with serialnumbers and its value(s):\n");
	$dummy_sensorserialtemp_array = array_combine($dummy_serial_array, $dummy_temp_array);
	print_r($dummy_sensorserialtemp_array);
	print("********************************************************************************\n");
}
else if(!$test && $dummy_unit && $dummy_temp_array[0]) {
	$tempreader = true;
}
else {
	die("\nError [" . $sensor_name . "]: please check the configuration file and fill out everything missing.\n\n");
}
?>

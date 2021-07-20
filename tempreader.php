<?php
/*
    phpTempReader - reading temperatures from sensors and put them in arrays and variables.
    Copyright (C) 2017  Ole-Henrik Jakobsen

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
    
    
    This is a PHP-CLI script made to pick up data from temperature sensors.
 
	Configure config.ini if necessary.
	
	Requirements: php-cli
	
	Last updated: 2017-12-01

*/
chdir(dirname(__FILE__));

$ini_file = "config.ini";
$ini_array = parse_ini_file($ini_file, true);

// put array data into variables
$sensors_array = @$ini_array["SENSORS"];
$test = @$ini_array["TEST"];

function tempreader($separate = 0) {
	global $ini_array;
	global $sensors_array;
	global $test;
	
	if(!$test) { $test = 0; }
	
	$sensors_length = count($sensors_array);
	for($int=0;$int<$sensors_length;$int++) {
		include("sensors/" . $sensors_array[$int] . ".php");
		$tempreader_array[$sensors_array[$int]]['unit'] = ${ $sensors_array[$int] . '_unit' };
		if($separate) {
			$tempreader_array[$sensors_array[$int]]['serialnumber'] = ${ $sensors_array[$int] . '_serial_array' };
			$tempreader_array[$sensors_array[$int]]['temperature'] = ${ $sensors_array[$int] . '_temp_array' };
		}
		else {
			$tempreader_array[$sensors_array[$int]]['data'] = array_combine(${ $sensors_array[$int] . '_serial_array' }, ${ $sensors_array[$int] . '_temp_array' });
		}
	}
	
	if(@$tempreader) {
		return $tempreader_array;
	}
	else {
		return false;
	}
}

if(!@$test && @$argc && @is_file("" . @dirname(__FILE__) . "/sensors/" . $argv[1] . ".php")) {
	$use_sensor = $argv[1];
	$tempreader_cli = tempreader(1);
	$tempreader_length = count($tempreader_cli[$use_sensor]["temperature"]);
	for($int=0;$int<$tempreader_length;$int++) {
		$unit = $tempreader_cli[$use_sensor]["unit"];
		$temp_str = $tempreader_cli[$use_sensor]["temperature"][$int];
		$serial_str = $tempreader_cli[$use_sensor]["serialnumber"][$int];
		print("" . $temp_str . "°" . $unit . " [" . $serial_str . "]\n");
	}
}

if($test) {
	tempreader();
}
?>

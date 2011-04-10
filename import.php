<?php
/*********************************************************

* DO NOT REMOVE *

Project: PHPWeby ip2country software version 1.0.2
Url: http://phpweby.com/
Copyright: (C) 2008 Blagoj Janevski - bl@blagoj.com
Project Manager: Blagoj Janevski

More info, sample code and code implementation can be found here:
http://phpweby.com/software/ip2country

This software uses GeoLite data created by MaxMind, available from
http://maxmind.com

This file is part of i2pcountry module for PHP.

For help, comments, feedback, discussion ... please join our
Webmaster forums - http://forums.phpweby.com

**************************************************************************
*  If you like this software please link to us!                          *
*  Use this code:						         *
*  <a href="http://phpweby.com/software/ip2country">ip to country</a>    *
*  More info can be found at http://phpweby.com/link                     *
**************************************************************************

License:
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

*********************************************************/
/*
* Additional work by Menny Even Danan http://www.evendanan.net
* Supports SQL servers with packet size limitation (where the 'max_allowed_packet' is lower than the size of the input CSV file)
*/
require_once('ip2country.php');

$ip2c=new ip2country();

//change the following values to match your hosting
//the mysql server host - ask your web host for more info
//this is usually localhost or mysqlhost
$ip2c->mysql_host='localhost';
//the database name
//the database have to be created by your host
$ip2c->db_name='ip2l_db';
//the user name for accessing mysql
$ip2c->db_user='ip2l_user';
//the password for accessing mysql
$ip2c->db_pass='your password';
//table name
//change this if needed
$ip2c->table_name='ip2l';


////////CHANGE NOTHING BELOW HERE//////////
ini_set('display_errors',1);
error_reporting(E_ALL);
set_time_limit(300);

if(!$ip2c->mysql_con())
die('Could not connect to database ' . mysql_error());

if(!($r=mysql_query("SHOW VARIABLES LIKE 'max_allowed_packet'",$ip2c->get_mysql_con())))die( 'mysql_error:' . mysql_error($ip2c->get_mysql_con()) . '<br />');
$row=mysql_fetch_assoc($r);
$max_packet_size = $row['Value'];

$fsize=@filesize('GeoIPCountryWhois.csv');
if(!$fsize)
die('1. PHP does not have read permission to or the file GeoIPCountryWhois.csv does not exists.<br /><a href="http://forums.phpweby.com" target="_blank">Need help?</a><br /><a href="http://phpweby.com/software/ip2country" target="_blank">More info here</a>');
unset($row,$r);

mysql_query("DROP TABLE ".$ip2c->table_name,$ip2c->get_mysql_con());

$ip2c->create_mysql_table();

$f=@fopen('GeoIPCountryWhois.csv','r');

if(!$f)
die('PHP does not have read permission to or the file GeoIPCountryWhois.csv does not exists.<br /><a href="http://forums.phpweby.com" target="_blank">Need help?</a><br /><a href="http://phpweby.com/software/ip2country" target="_blank">More info here</a>');

$str=@fread($f,$fsize);

@fclose($f);

$rows_array = explode ("\n", $str);
unset($str);

echo 'Have '.count($rows_array).' rows to insert.<br/>';
$blank_lines = 0;

$current_row_index = 0;
while($current_row_index < count($rows_array))
{
	$str_to_insert = "";

	while(($current_row_index < count($rows_array)) && ((strlen($str_to_insert) + strlen($rows_array[$current_row_index]) + 1000) < $max_packet_size))
	{
		if (strlen($rows_array[$current_row_index]) > 10)
		{
			if (strlen($str_to_insert) > 0)
			{
				$str_to_insert .= ",";
			}
			$str_to_insert .= "(".$rows_array[$current_row_index].")";
		}
		else
		{
			$blank_lines++;
		}
		$current_row_index++;
	}

	if (strlen($str_to_insert) > 10)
	{
		if(!mysql_query("INSERT into " .$ip2c->table_name . " (`begin_ip`,`end_ip`,`begin_ip_num`,`end_ip_num`,`country_code`,`country_name`) values ".$str_to_insert,$ip2c->get_mysql_con()))die ('mysql_error: ' . mysql_error($ip2c->get_mysql_con()) . '<br />');
	}
	unset($str_to_insert);
}

unset($fsize);
unset($uploaded_so_far);
unset($current_row_index);

if(!($r=mysql_query("SELECT COUNT(begin_ip) AS IpsCount FROM ".$ip2c->table_name,$ip2c->get_mysql_con())))die( 'mysql_error:' . mysql_error($ip2c->get_mysql_con()) . '<br />');
$row=mysql_fetch_assoc($r);
$total_rows_count = $row['IpsCount'];
echo 'The ip2location table now has '.$total_rows_count.' rows. Original array had '.count($rows_array).' lines with '.$blank_lines.' blank lines.<br/>';
unset($row,$r,$total_rows_count,$blank_lines,$rows_array);

$ip2c->close();

//@unlink('import.php');
//@unlink('GeoIPCountryWhois.csv');
echo 'Successfully inserted data.';
echo '<br />If you like this software please link to us!<br />Use this code:<br />
    '. htmlspecialchars('<a href="http://phpweby.com/software/ip2country">ip to country</a>') .'<br />
	More info and links can be found at <a href="http://phpweby.com/link" target="_blank">http://phpweby.com/link</a><br /> ' ;
echo 'For help, comments, feedback, discussion ... please join our
	<a href="http://forums.phpweby.com" target="_blank" style="color:blue;font-weight:bold;">Webmaster Forums</a>';
?>
<?php
/*
 * kirchzettel
 *
 * Copyright (c) 2019 Christoph Fischer, https://christoph-fischer.org
 * Author: Christoph Fischer, chris@toph.de
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once('vendor/autoload.php');


require_once('vendor/autoload.php');

use Peregrinus\Kirchzettel\Utility\DateUtility;

ini_set('display_errors', 1);

// check which format is wanted (default: Kirchzettel)
$format = filter_var($_REQUEST['format'], FILTER_SANITIZE_STRING) ?: 'Kirchzettel';
$outputClass = '\\Peregrinus\\Kirchzettel\\Output\\'.ucfirst($format).'Document';
if (!class_exists($outputClass)) die ('<b>Fehler:</b> Unbekanntes Format "'.$format.'"');


// get the start date
DateUtility::setDefaultLocale();
$start = DateUtility::getDateStringFromRequest('start', 'next Sunday');
$weekStart = new DateTime($start, new DateTimeZone('Europe/Berlin'));


// get the configuration
$config = yaml_parse_file('Configuration/Kirchzettel.yaml');
if (!isset($config['output'][$format])) die('<b>Fehler:</b> Ausgabeformat "'.$format.'" ist nicht konfiguriert.');

// get the output document
$doc = new $outputClass($config['output'][$format]);

// determine period end for this output format
$weekEnd = $doc->getPeriodEnd($weekStart);

// get the events in the desired period
$calendar = new \Peregrinus\Kirchzettel\EventCalendar($config['calendar']);
$filteredEvents = $calendar->getEvents($weekStart, $weekEnd);

// render and send the output file
$filename = $weekStart->format('Ymd') . ' '.$format;
$doc->render($weekStart, $weekEnd, $filteredEvents);
$doc->send($filename);


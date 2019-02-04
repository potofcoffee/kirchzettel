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

namespace Peregrinus\Kirchzettel;

class EventCalendar
{

    /** @var array $config Configuration */
    protected $config = [];

    /**
     * EventCalendar constructor.
     * @param array $config Configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Retrieve all events for this calendar for a given period
     * @param \DateTime $weekStart Start of the event period
     * @param \DateTime $weekEnd End of the event period
     * @return array
     */
    public function getEvents(\DateTime $weekStart, \DateTime $weekEnd): array {
        $url = str_replace('###GUID###', $this->config['sharepoint']['guid'], $this->config['sharepoint']['url']);
        $events = json_decode(file_get_contents($url), true);

        $filteredEvents = [];
        foreach ($events as $key => $event) {
            $event['start'] = $this->sanitizeTimeString($event['start']);
            $event['end'] = $this->sanitizeTimeString($event['end']);

            // sanitize title
            $lines = explode("\r\n", $event['title']);
            $event['title'] = $this->sanitizeTitle($lines[0], $event);
            if (substr(trim($lines[1]), 0, 4) == 'Ort:') {
                $event['place'] = $this->sanitizeLocation($lines[1]);
            }

            if (($event['start'] >= $weekStart) and ($event['start'] <= $weekEnd)) {
                $filteredEvents[] = $event;
            }
        }
        return $filteredEvents;
    }

    /**
     * Wandelt die Zeitangabe in ein DateTime-Objekt um und korrigiert die Zeitzone
     * @param string $time
     * @return DateTime
     */
    protected function sanitizeTimeString(string $time): \DateTime
    {
        return new \DateTime(substr($time, 0, -1), new \DateTimeZone('Europe/Berlin'));
    }

    /**
     * Formatiert den Titel einer Veranstaltung
     * @param string $title
     * @param array $event
     * @return string
     */
    protected function sanitizeTitle(string $title, array &$event): string
    {
        $title = trim($title);
        $title = strtr($title, $this->config['events']['sanitize']['title']);
        foreach (['P', 'O', 'M', 'L'] as $function) {
            $regex = '/' . $function . ':\s?(\w*)/';
            preg_match($regex, $title, $tmp);
            if (count($tmp)) {
                $event[$function] = $tmp[1];
                $title = str_replace($tmp[0], '', $title);
            }
        }

        while (false !== strpos($title, '  ')) {
            $title = str_replace('  ', ' ', $title);
        }

        return $title;
    }

    /**
     * Formatiert die Ortsangabe einer Veranstaltung
     * @param string $location
     * @return string
     */
    protected function sanitizeLocation(string $location): string
    {
        $location = trim(str_replace('Ort:', '', $location));
        $location = strtr($location, $this->config['events']['sanitize']['location']);
        $location = (strpos(strtolower($location), 'kirche') > 0 ? 'in der ' . $location : 'im ' . $location);
        return $location;
    }


}



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

namespace Peregrinus\Kirchzettel\Utility;

class DateUtility
{
    public static function convertGermanDate(string $dateString, $asDateTime = false) {
        if (false !== strpos($dateString, '.')) {
            $dateString = str_replace(' ', '', join('-', array_reverse(explode('.', $dateString))));
        }
        return $asDateTime ? new \DateTime($dateString, new \DateTimeZone('Europe/Berlin')) : $dateString;
    }

    public static function getDateStringFromRequest(string $key, string $default)
    {
        $dateString = filter_var($_REQUEST[$key], FILTER_SANITIZE_STRING);
        if ($dateString) {
            $dateString = self::convertGermanDate($dateString);
        } else {
            $dateString = $default;
        }
        return $dateString;
    }

    public static function setDefaultLocale() {
        setlocale(LC_ALL, 'de_DE.utf8');
        date_default_timezone_set('Europe/Berlin');
    }

}
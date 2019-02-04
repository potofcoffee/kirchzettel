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

namespace Peregrinus\Kirchzettel\Output;

use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\Tab;

class KirchzettelDocument extends AbstractDocument
{

    protected const DEFAULT = 'Kirchzettel';
    protected const HEADING1 = 'Kirchzettel Überschrift 1';
    protected const HEADING2 = 'Kirchzettel Überschrift 2';

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->getArguments(
            ['start', 'offeringGoal'],
            ['offeringGoal']
        );

        $this->addParagraphStyle(
            self::DEFAULT,
            array(
                'indentation' => [
                    'left' => Converter::cmToTwip(5.5),
                    'hanging' => Converter::cmToTwip(5.5),
                ],
                'tabs' => [
                    new Tab('left', Converter::cmToTwip(5.5)),
                    new Tab('right', Converter::cmToTwip(18)),
                ],
                'spaceAfter' => 0,
            )
        );

        $this->addParagraphStyle(
            self::HEADING1,
            array(
                'indentation' => [
                    'left' => Converter::cmToTwip(5.5),
                    'firstLine' => Converter::cmToTwip(1.25),
                ],
                'spaceAfter' => 0,
            )
        );

        $this->addParagraphStyle(
            self::HEADING2,
            array(
                'indentation' => [
                    'left' => Converter::cmToTwip(7.5),
                ],
                'spaceAfter' => 0,
            )
        );
    }

    public function render(\DateTime $weekStart, \DateTime $weekEnd, array $filteredEvents)
    {
        // headings
        $this->renderParagraph(self::HEADING1, [['Evang. Kirchengemeinde', ['size' => 27]]]);
        $this->renderParagraph(self::HEADING2, [["   \t      Tailfingen", ['size' => 27]]]);
        $this->renderParagraph();

        $lastDay = '';
        $ctr = 0;
        foreach ($filteredEvents as $event) {
            $dateFormat = $ctr ? '%A, %d. %B' : '%A, %d. %B %Y';

            $done = false;
            if ($lastDay != $event['start']->format('Ymd')) {
                $this->renderParagraph();

                if ($weekEnd->format('Ymd') == $event['start']->format('Ymd')) {
                    $this->renderParagraph(self::DEFAULT, [['Vorschau', self::BOLD_UNDERLINE]]);
                }

                $this->renderParagraph(self::DEFAULT, [
                    [strftime($dateFormat, $event['start']->getTimestamp()), self::BOLD_UNDERLINE],
                    ["\t", []],
                    [($event['allDay'] ? $event['title'] : ''), self::BOLD_UNDERLINE],
                    [($event['allDay'] && ($event['start']->format('Ymd') == $weekStart->format('Ymd')) ? '<w:br />(Opfer für '.$this->arguments['offeringGoal'].')' : ''), []]
                ]);
                $done = $event['allDay'];
            }

            if (!$done) {
                $this->renderParagraph(self::DEFAULT, [
                    [strftime('%H.%M Uhr', $event['start']->getTimestamp()) . "\t", []],
                    [$event['title'], self::BOLD],
                    [' '.$event['place'], []],
                    [($event['P'] ? "\t".$event['P']: ''), self::BOLD]
                ]);
            }

            $lastDay = $event['start']->format('Ymd');
            $ctr++;
        }

    }

    public function getPeriodEnd(\DateTime $weekStart): \DateTime
    {
        return new \DateTime($weekStart->format('Y-m-d') . ' +8 days -1 second', new \DateTimeZone('Europe/Berlin'));
    }

}
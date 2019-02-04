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

use Peregrinus\Kirchzettel\Utility\DateUtility;
use PhpOffice\PhpWord\Style\Font;

class BekanntgabenDocument extends AbstractDocument
{

    protected const INDENT = 'Bekanntgaben';
    protected const NO_INDENT = 'Bekanntgaben ohne Einrückung';

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->getArguments(
            ['start', 'baptisms', 'weddings', 'lastService', 'offerings', 'offeringGoal', 'funerals1', 'funerals2'],
            ['offerings', 'lastService', 'offeringGoal']
        );

        $this->addParagraphStyle(
            self::INDENT,
            array(
                'indentation' => [
                    'left' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(3),
                    'hanging' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(3),
                ],
                'tabs' => [
                    new \PhpOffice\PhpWord\Style\Tab('left', \PhpOffice\PhpWord\Shared\Converter::cmToTwip(3)),
                ],
                'spaceAfter' => 0,
            )
        );

        $this->addParagraphStyle(
            self::NO_INDENT,
            array(
                'tabs' => [
                    new \PhpOffice\PhpWord\Style\Tab('left', \PhpOffice\PhpWord\Shared\Converter::cmToTwip(3)),
                ],
                'spaceAfter' => 0,
            )
        );
    }

    public function render(\DateTime $weekStart, \DateTime $weekEnd, array $filteredEvents)
    {
        $liturgicalTexts = $this->config['liturgicalTexts'];

        $textRun = $this->section->addTextRun('Bekanntgaben');
        $textRun->addText('Bekanntgaben für ' . strftime('%A, %d. %B %Y', $weekStart->getTimestamp()),
            ['bold' => true]);

        $lastDay = '';
        $ctr = 0;
        $offeringsDone = false;
        foreach ($filteredEvents as $event) {
            $dateFormat = $ctr ? '%A, %d. %B' : '%A, %d. %B %Y';

            $done = false;

            if ($weekStart->format('Ymd') == $event['start']->format('Ymd')) {
                if ($event['allDay']) {
                    $textRun = $this->section->addTextRun('Bekanntgaben ohne Einrückung');
                    $textRun->addText($event['title']);
                    $done = true;
                }
            }

            if (!$offeringsDone) {
                $this->renderParagraph(self::NO_INDENT, [
                    ['**************************************************************************', []],
                ]);

                $this->renderParagraph(self::NO_INDENT, [
                    [
                        'Herzlichen Dank für das Opfer der Gottesdienste vom '
                        . $this->arguments['lastService']
                        . ' in Höhe von ' . $this->arguments['offerings'] . ' Euro.',
                        []
                    ]
                ]);

                $textRun = $this->renderParagraph(self::NO_INDENT, [
                    [
                        'Das heutige Opfer ist für ' . $this->arguments['offeringGoal'] . ' bestimmt.',
                        []
                    ]
                ], 1);

                $offeringsDone = true;
            }

            if (!$done) {
                if ($lastDay != $event['start']->format('Ymd')) {
                    $this->renderParagraph();
                    if ($weekEnd->format('Ymd') == $event['start']->format('Ymd')) {
                        $this->renderParagraph(self::NO_INDENT, [
                            [
                                'Vorschau',
                                self::BOLD_UNDERLINE,
                            ]
                        ], 1);
                    }

                    $this->renderParagraph(self::NO_INDENT, [
                        [
                            ($weekStart->format('Ymd') == $event['start']->format('Ymd')) ?
                                'Heute' : strftime($dateFormat, $event['start']->getTimestamp()),
                            self::BOLD_UNDERLINE,
                        ]
                    ]);
                }

                $textRun = $this->renderParagraph(self::INDENT, [
                    [
                        ($event['allDay']) ? '' : strftime('%H.%M Uhr', $event['start']->getTimestamp()) . "\t",
                        []
                    ],
                    [
                        trim($event['title'] . ' ' . $event['place']),
                        []
                    ]
                ]);

                if ($event['allDay']) {
                    $textRun = $this->renderParagraph();
                }

                $lastDay = $event['start']->format('Ymd');
            }

            $ctr++;
        }

        $this->renderParagraph();
        $this->renderLiteral($this->config['otherTexts']['afterEvents']);

        // Kasualien
        if ($this->arguments['funerals1'] || $this->arguments['funerals2'] || $this->arguments['weddings'] || $this->arguments['baptisms']) {
            $this->renderParagraph();
            $this->renderParagraph(self::NO_INDENT, [['Kasualien', self::BOLD_UNDERLINE]], 1);

            // Taufen
            if ($this->arguments['baptisms']) {
                $this->renderParagraph(self::NO_INDENT, [['Taufen', self::BOLD_UNDERLINE]], 1);
                $this->renderTimedList($this->arguments['baptisms'], $weekStart, 'getauft');
                $this->renderLiteral($liturgicalTexts['baptism']);
            }

            // Trauungen
            if ($this->arguments['weddings']) {
                $this->renderParagraph(self::NO_INDENT, [['Trauungen', self::BOLD_UNDERLINE]], 1);
                $this->renderTimedList($this->arguments['weddings'], $weekStart, 'kirchlich getraut', true, '');
                $this->renderLiteral($liturgicalTexts['wedding']);
            }

            // Bestattungen
            if ($this->arguments['funerals1'] || $this->arguments['funerals2']) {
                $this->renderParagraph(self::NO_INDENT, [['Bestattungen', self::BOLD_UNDERLINE]], 1);

                if ($this->arguments['funerals1']) {
                    $this->renderParagraph(self::NO_INDENT,
                        [['Aus unserer Gemeinde sind verstorben:', self::UNDERLINE]],
                        1);
                    $this->renderList($this->arguments['funerals1']);
                }
                if ($this->arguments['funerals2']) {
                    $this->renderParagraph(self::NO_INDENT,
                        [['Aus unserer Gemeinde sind verstorben und wurden kirchlich bestattet:', self::UNDERLINE]],
                        1);
                    $this->renderList($this->arguments['funerals2']);
                }
                $this->renderLiteral($liturgicalTexts['funeral']);
            }

        }
    }

    protected function renderList(string $list, $prefix = '- ')
    {
        if ($list != '') {
            $list = explode("\n", $list);
            $list = $prefix . join('<w:br />'.$prefix, $list);
        }
        $this->renderParagraph(self::NO_INDENT, [[$list, []]], 1);
    }

    protected function renderTimedList(string $list, \DateTime $weekStart, string $verb, $plural = null, $listPrefix='- ')
    {
        $list = explode("\n", $list);
        $cases = [];
        foreach ($list as $key => $element) {
            // take care of times noted as hh:mm instead of hh.mm (this would clash with : as delimiter)
            $element = preg_replace('/(\d+)\:(\d+)/', '$1.$2', $element);
            $tmp = explode(':', $element);
            $cases[trim($tmp[0])][] = trim($tmp[1]);
        }
        foreach ($cases as $key => $list) {
            $plu = is_null($plural) ? (count($list) > 1) : $plural;
            list($church, $date, $time) = explode(',', $key);
            if (($time) && (false === strpos($time, 'Uhr'))) $time .= ' Uhr';
            $auxVerb = DateUtility::convertGermanDate($date, true)->format('Ymd') <= $weekStart->format('Ymd') ?
                ($plu ? 'wurden': 'wurde') : ($plu ? 'werden': 'wird');
            $date = DateUtility::convertGermanDate($date, true)->format('Ymd') == $weekStart->format('Ymd') ?
                'heute' : 'am '.trim($date);
            $this->renderParagraph(self::NO_INDENT, [
                [sprintf('In der %s %s %s '.($time ? 'um %s' : '').' '.$verb.':', trim($church), $auxVerb, trim($date), trim($time)), self::UNDERLINE]
            ], 1);
            $this->renderList(join("\n", $list), $listPrefix);
        }
    }

    protected function renderLiteral($text) {
        if (!is_array($text)) $text = [$text];
        foreach ($text as $paragraph) {
            switch (substr($paragraph, 0, 1)) {
                case '*':
                    $format = self::BOLD;
                    $paragraph = substr($paragraph, 1);
                    break;
                case '_':
                    $format = self::UNDERLINE;
                    $paragraph = substr($paragraph, 1);
                    break;
                default:
                    $format = [];
            }
            $paragraph = strtr($paragraph, [
                "\r" => '',
                "\n" => '<w:br />'
            ]);
            $this->renderParagraph(self::NO_INDENT, [[$paragraph, $format]], 1);
        }

    }

    public function getPeriodEnd(\DateTime $weekStart): \DateTime
    {
        return new \DateTime($weekStart->format('Y-m-d') . ' next Sunday +1 day -1 second', new \DateTimeZone('Europe/Berlin'));
    }

}
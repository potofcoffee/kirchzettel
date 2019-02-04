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

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Section;

class AbstractDocument extends PhpWord
{

    protected const BOLD = ['bold' => true];
    protected const UNDERLINE = ['underline' => Font::UNDERLINE_SINGLE];
    protected const BOLD_UNDERLINE = ['bold' => true, 'underline' => Font::UNDERLINE_SINGLE];

    /** @var array $config Configuration */
    protected $config = [];

    /** @var \PhpOffice\PhpWord\Element\Section $section Section  */
    protected $section = null;

    /** @var array $arguments Request arguments */
    protected $arguments = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
        parent::__construct();

        $this->setLayout();
        if (isset($this->config['defaults']['fontName'])) $this->setDefaultFontName($this->config['defaults']['fontName']);
        if (isset($this->config['defaults']['fontSize'])) $this->setDefaultFontSize($this->config['defaults']['fontSize']);
    }

    protected function setLayout() {
        $layout = [];
        foreach (($this->config['layout']['page'] ?: []) as $key => $val) {
            if ($key == 'orientation') {
                $layout['orientation'] = $val == 'portrait' ? Section::ORIENTATION_PORTRAIT : Section::ORIENTATION_LANDSCAPE;
            } else {
                $layout[$key] = Converter::cmToTwip($val);
            }
        }
        $this->section = $this->addSection($layout);
    }

    protected function getArguments(array $keys = [], array $underlineEmpty = []) {
        foreach ($keys as $key) {
            if (isset($_REQUEST[$key])) {
                $this->arguments[$key] = str_replace('&', '&amp;', filter_var($_REQUEST[$key], FILTER_SANITIZE_STRING));
            }
        }
        foreach ($underlineEmpty as $key) {
            if ((!isset($this->arguments[$key]) || ($this->arguments[$key] == ''))) {
                $this->arguments[$key] = '_______________';
            }
        }
    }


    public function render() {

    }


    public function renderParagraph($template = '', array $blocks = [], $emptyParagraphsAfter = 0, $existingTextRun = null) {
        $textRun = $existingTextRun ?: $this->section->addTextRun($template);
        foreach ($blocks as $block) {
            $textRun->addText($block[0], $block[1]);
        }
        for ($i=0; $i<$emptyParagraphsAfter; $i++) {
            $textRun = $this->section->addTextRun($template);
        }
        return $textRun;
    }

    public function send(string $filename) {
        header("Content-Description: File Transfer");
        header('Content-Disposition: attachment; filename="' . $filename . '.docx"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($this, 'Word2007');
        $objWriter->save('php://output');
    }

    public function getPeriodEnd(\DateTime $weekStart): \DateTime {
        return $weekStart;
    }

}
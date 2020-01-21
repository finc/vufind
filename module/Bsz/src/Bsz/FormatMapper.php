<?php

/*
 * Copyright (C) 2015 Bibliotheks-Service Zentrum, Konstanz, Germany
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Bsz;

use Zend\Config\Reader\Ini;

/**
 * Formate einheitlich mappen für alle Quellen
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class FormatMapper
{

    protected $_config;

    public function __construct() {
//        $this->_config = $this->getConfig();
    }

    /**
     *
     * @return array
     */
    protected function getConfig() {
        if(null === $this->_config) {
            $baseDir = '/usr/local/boss';
            $Reader = new Ini();
            $config = $Reader->fromFile($baseDir . '/config/vufind/formats.ini');
            $this->_config = $config;
        }
        return $this->_config;
    }


    /**
     * Maps an array of format strings
     * @param array $inputs
     * @return array
     */
    protected function mapArray($inputs) {
        $formats = array();
        foreach($inputs as $i) {
            $formats[] = $this->map($i);
        }
        return array_unique($formats);
    }


    /**
     * Maps formats from formats.ini to icon file names
     * @param string $formats
     * @return string
     */
    public function mapIcon($formats) {

        //this function uses simplifies formats as we can only show one icon
        $formats = $this->simplify($formats);
        foreach($formats as $k => $format) {
            $formats[$k] = strtolower($format);
        }
        $return = '';
        if(is_array($formats)) {
            if(in_array('electronicresource', $formats) && in_array('e-book',$formats)) {$return = 'ebook';} elseif(in_array('videodisc', $formats) && in_array('video',$formats)) {$return = 'movie';} elseif(in_array('electronicresource', $formats) && in_array('journal',$formats)) {$return = 'ejournal';} elseif(in_array('opticaldisc', $formats) && in_array('e-book',$formats)) {$return = 'disc';} elseif(in_array('cd', $formats) && in_array('soundrecording',$formats)) {$return = 'music-cd';} elseif(in_array('book', $formats) && in_array('compilation',$formats)) {$return = 'serial';} elseif(in_array('musicalscore', $formats)) {$return = 'partitur';} elseif(in_array('atlas', $formats)) {$return = 'map';} elseif(in_array('serial', $formats)) {$return = 'collection';} elseif(in_array('journal', $formats)) {$return = 'journal';} elseif(in_array('conference proceeding', $formats)) {$return = 'journal';} elseif(in_array('e-journal', $formats)) {$return = 'ejournal';} elseif(in_array('text', $formats)) {$return = 'article';} elseif(in_array('pdf', $formats)) {$return = 'article';} elseif(in_array('book', $formats)) {$return = 'book';} elseif(in_array('book chapter', $formats)) {$return = 'book';} elseif(in_array('e-book', $formats)) {$return = 'ebook';} elseif(in_array('e-book', $formats)) {$return = 'ebook';} elseif(in_array('ebook', $formats)) {$return = 'ebook';} elseif(in_array('vhs', $formats)) {$return = 'vhs';} elseif(in_array('video', $formats)) {$return = 'video-disc';} elseif(in_array('microfilm', $formats)) {$return = 'microfilm';} elseif(in_array('platter', $formats)) {$return = 'platter';} elseif(in_array('dvd/bluray', $formats)) {$return = 'video-disc';} elseif(in_array('music-cd', $formats)) {$return = 'music-disc';} elseif(in_array('cd-rom', $formats)) {$return = 'disc';} elseif(in_array('article', $formats)) {$return = 'article';} elseif(in_array('magazine article', $formats)) {$return = 'article';} elseif(in_array('journal article', $formats)) {$return = 'article';} elseif(in_array('band', $formats)) {$return = 'book';} elseif(in_array('cassette', $formats)) {$return = 'cassette';} elseif(in_array('soundrecording', $formats)) {$return = 'sound';} elseif(in_array('norm', $formats)) {$return = 'norm';} elseif(in_array('thesis', $formats)) {$return = 'thesis';} elseif(in_array('proceedings', $formats)) {$return = 'books';} elseif(in_array('electronic', $formats)) {$return = 'globe';} else {$return =  'article'; }


        }


        return 'bsz bsz-'. $return;
    }

    /**
     * Returns physical medium from Marc21 field 007 - char 0 and 1
     * @param char $code1 char 0
     * @param char $code2 char 1
     * @return string
     */
    public function marc21007($code1, $code2) {
        $medium = '';
        $code1 = strtoupper($code1);
        $code2 = strtoupper($code2);
        $mappings = [];
        $mappings['A']['D'] = 'Atlas';
        $mappings['A']['default'] = 'Map';
        $mappings['C']['A'] = 'TapeCartridge';
        $mappings['C']['B'] = 'ChipCartridge';
        $mappings['C']['C'] = 'DiscCartridge';
        $mappings['C']['F'] = 'TapeCassette';
        $mappings['C']['H'] = 'TapeReel';
        $mappings['C']['J'] = 'FloppyDisk';
        $mappings['C']['M'] = 'MagnetoOpticalDisc';
        $mappings['C']['Z'] = 'E-Journal on Disc';
        $mappings['C']['O'] = 'OpticalDisc';
        // Do not return - this will cause anything with an
        // 856 field to be labeled as "Electronic"
        $mappings['C']['R'] = 'ElectronicResource';
        $mappings['C']['default'] = 'ElectronicResource'; //Software passt aber bei eBooks nicht?
        $mappings['D']['default'] = 'Globe';
        $mappings['F']['default'] = 'Braille';
        $mappings['G']['C'] = 'FilmstripCartridge';
        $mappings['G']['D'] = 'Filmstrip';
        $mappings['G']['S'] = 'Slide';
        $mappings['G']['T'] = 'Transparency';
        $mappings['G']['default'] = 'Slide';
        $mappings['H']['default'] = 'Microfilm';
        $mappings['K']['C'] = 'Collage';
        $mappings['K']['D'] = 'Drawing';
        $mappings['K']['E'] = 'Painting';
        $mappings['K']['F'] = 'Print';
        $mappings['K']['G'] = 'Photonegative';
        $mappings['K']['J'] = 'Print';
        $mappings['K']['L'] = 'Drawing';
        $mappings['K']['O'] = 'FlashCard';
        $mappings['K']['N'] = 'Chart';
        $mappings['K']['default'] = 'Photo';
        $mappings['M']['F'] = 'VideoCassette';
        $mappings['M']['R'] = 'Filmstrip';
        $mappings['M']['default'] = 'MotionPicture';
        $mappings['O']['default'] = 'Kit';
        $mappings['Q']['U'] = 'SheetMusic';
        $mappings['Q']['default'] = 'MusicalScore';
        $mappings['R']['default'] = 'SensorImage';
        $mappings['S']['D'] = 'CD';
        $mappings['S']['O'] = 'SoundRecording'; // SO ist not specified
        $mappings['S']['S'] = 'SoundCassette';
        $mappings['S']['Z'] = 'Platter'; //Undefined aber sind meist Schallplatten        
        $mappings['S']['default'] = 'SoundRecording'; // eigentlich unspecified
        $mappings['T']['A'] = 'Printed'; //Text               
        $mappings['T']['D'] = 'LooseLeaf'; //Text               
        $mappings['T']['default'] = null; //Text               
        $mappings['V']['C'] = 'VideoCartridge';
        $mappings['V']['D'] = 'VideoDisc';
        $mappings['V']['F'] = 'VideoCassette';
        $mappings['V']['R'] = 'VideoReel';
        $mappings['V']['default'] = 'Video';
        $mappings['Z']['default'] = 'Kit';


        if (isset($mappings[$code1])) {
            if (!empty($mappings[$code1][$code2])) {
                $medium = $mappings[$code1][$code2];
            } elseif(!empty($mappings[$code1]['default'])) {
                $medium = $mappings[$code1]['default'];
            }

        }
//        var_dump($code1);
//        var_dump($code2);
        return $medium;
    }

    /**
     * Returns content/format from Marc21 field 007
     * @param char $leader7
     * @param char $f007
     * @return string
     */
    public function marc21leader7($leader7, $f007, $f008 ) {
        $format = '';
        $leader7 = strtoupper($leader7);
        $f007 = strtoupper($f007);
        $f008 = strtoupper($f008);
        $mappings = [];
        $mappings['A']['default'] = 'Article'; // Artikel aus Zeitschrift
        $mappings['B']['default'] = 'Article';
        $mappings['M']['C'] = 'E-Book';
        $mappings['M']['V'] = 'Video';
        $mappings['M']['S'] = 'SoundRecording';
        $mappings['M']['default'] = 'Book';
        $mappings['S']['N'] = 'Newspaper';
        $mappings['S']['P'] = 'Journal';
        $mappings['S']['M'] = 'Serial';
        $mappings['S']['default'] = 'Serial';

        if (isset($mappings[$leader7])) {
            if ($leader7 == 'S' && isset($mappings[$leader7][$f008])) {
                $format = $mappings[$leader7][$f008];
            } elseif ($leader7 != 'S' && isset($mappings[$leader7][$f007])) {
                $format = $mappings[$leader7][$f007];
            } elseif(isset($mappings[$leader7]['default'])) {
                $format = $mappings[$leader7]['default'];
            }
        }
//        var_dump($leader7);
//        var_dump($f007);
//        var_dump($f008);
        return $format;
    }

    /**
     * Return content/format from Marc21 field 006
     * @param char $leader6
     * @param char $f007
     * @return string
     */
    public function marc21leader6($leader6) {
        $format = '';
        $leader6 = strtoupper($leader6);

        $mappings = [];
        $mappings['C'] = 'MusicalScore';
        $mappings['D'] = 'MusicalScore';
        $mappings['E'] = 'Map';
        $mappings['F'] = 'Map';
        $mappings['G'] = 'Slide';
        $mappings['I'] = 'Sound';
        $mappings['J'] = 'MusicRecording';
        $mappings['K'] = 'Photo';
        $mappings['M'] = 'Electronic';
        $mappings['O'] = 'Kit';
        $mappings['P'] = 'Kit';
        $mappings['R'] = 'PhysicalObject';
        $mappings['T'] = 'Manuscript';


        if (isset($mappings[$leader6])) {
            $format = $mappings[$leader6];
        }
        return $format;
    }

    /**
     * Simplify format array
     * @param array $formats
     * @return array
     */
    public function simplify($formats) {
        $formats = array_unique($formats);
        foreach($formats as $k => $format) {

            if (!empty($format)) {
                $formats[$k] = ucfirst($format);
            }
        }
        if(in_array('SoundRecording', $formats) && in_array('MusicRecording', $formats)) {return ['Musik']; } elseif(in_array('SheetMusic', $formats) && in_array('Book', $formats)) {return ['MusicalScore']; } elseif(in_array('Map', $formats) && in_array('Book', $formats)) {return ['mapmaterial']; } elseif(in_array('Platter', $formats) && in_array('SoundRecording', $formats)) {return ['Platter']; } elseif(in_array('E-Journal', $formats) && in_array('E-Book', $formats)) {return ['E-Book']; } elseif(in_array('E-Journal on Disc', $formats) && in_array('Journal', $formats)) {return ['E-Journal']; } elseif(in_array('VideoDisc', $formats) && in_array('Video', $formats)) {return ['DVD/BluRay']; } elseif(in_array('CD', $formats) && in_array('SoundRecording', $formats)) {return ['Music-CD']; } elseif(in_array('OpticalDisc', $formats) && in_array('E-Book', $formats)) {return ['CD-ROM']; } elseif(in_array('E-Journal', $formats) && in_array('Journal', $formats)) {return ['E-Journal']; } elseif(in_array('E-Journal', $formats) && in_array('Article', $formats)) {return ['Article']; } elseif(in_array('Journal', $formats) && in_array('Printed', $formats)) {return ['E-Journal']; } //elseif(in_array('E-Journal', $formats) && in_array('Newspaper', $formats)) {return ['Newspaper']; }
        elseif(in_array('VideoCassette', $formats) && in_array('Video', $formats)) {return ['VHS']; } elseif(in_array('Microfilm', $formats) && in_array('Book', $formats)) {return ['Book']; } elseif(in_array('Microfilm', $formats) && in_array('Journal', $formats)) {return ['Journal']; } elseif(in_array('SoundCassette', $formats) && in_array('SoundRecording', $formats)) {return ['Cassette']; } elseif(in_array('SoundRecording', $formats) && in_array('Article', $formats)) {return ['Music-CD']; } //Kommt im GBV vor
        elseif(in_array('E-Journal', $formats) && in_array('Newspaper', $formats)) {return ['Newspaper']; } elseif(in_array('Compilation', $formats) && in_array('Book', $formats)) {return ['Compilation']; }

        return $formats;

    }


}


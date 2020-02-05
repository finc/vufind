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

/**
 * Class FormatMapper
 * @package Bsz
 * @category boss
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class FormatMapper {

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
            // order is important: the second hit is ignored!
            // multiple formats
            if(in_array('electronicresource', $formats) && in_array('e-book',$formats)) {$return = 'ebook';}
            elseif(in_array('videodisc', $formats) && in_array('video',$formats)) {$return = 'movie';} 
            elseif(in_array('electronicresource', $formats) && in_array('journal',$formats)) {$return = 'ejournal';} 
            elseif(in_array('opticaldisc', $formats) && in_array('e-book',$formats)) {$return = 'disc';} 
            elseif(in_array('cd', $formats) && in_array('soundrecording',$formats)) {$return = 'music-disc';}
            elseif(in_array('book', $formats) && in_array('compilation',$formats)) {$return = 'serial';}
            // single formats:
            elseif(in_array('atlas', $formats)) {$return = 'map';}
            elseif(in_array('article', $formats)) {$return = 'article';}
            elseif(in_array('audiodisc', $formats)) {$return = 'music-disc';}
            elseif(in_array('audiocassette', $formats)) {$return = 'cassette';}
            elseif(in_array('band', $formats)) {$return = 'book';}
            elseif(in_array('book', $formats)) {$return = 'book';}
            elseif(in_array('book chapter', $formats)) {$return = 'book';}
            elseif(in_array('bookcomponentpart', $formats)) {$return = 'book';}
            elseif(in_array('cassette', $formats)) {$return = 'cassette';}
            elseif(in_array('cartographicimage', $formats)) {$return = 'map';}
            elseif(in_array('cd', $formats)) {$return = 'disc';}
            elseif(in_array('cdrom', $formats)) {$return = 'disc';}
            elseif(in_array('cd-rom', $formats)) {$return = 'disc';}
            elseif(in_array('comicbook', $formats)) {$return = 'book';}
            elseif(in_array('conference proceeding', $formats)) {$return = 'journal';}
            elseif(in_array('dvd/bluray', $formats)) {$return = 'video-disc';}
            elseif(in_array('e-journal', $formats)) {$return = 'ejournal';}
            elseif(in_array('electronic', $formats)) {$return = 'globe';}
            elseif(in_array('electronicarticle', $formats)) {$return = 'article';}
            elseif(in_array('electronicbookpart', $formats)) {$return = 'ebook';}
            elseif(in_array('electronicbookcomponentpart', $formats)) {$return = 'ebook';}
            elseif(in_array('electronicjournal', $formats)) {$return = 'ejournal';}
            elseif(in_array('electronicserialcomponentpart', $formats)) {$return = 'article';}
            elseif(in_array('e-book', $formats)) {$return = 'ebook';}
            elseif(in_array('ebook', $formats)) {$return = 'ebook';}
            elseif(in_array('exhibitioncatalogue', $formats)) {$return = 'ebook';}
            elseif(in_array('illustratedbook', $formats)) {$return = 'book';}
            elseif(in_array('journal', $formats)) {$return = 'journal';}
            elseif(in_array('journal article', $formats)) {$return = 'article';}
            elseif(in_array('magazine article', $formats)) {$return = 'article';}
            elseif(in_array('mikroform', $formats)) {$return = 'microfilm';}
            elseif(in_array('microfiche', $formats)) {$return = 'microfilm';}
            elseif(in_array('microfilm', $formats)) {$return = 'microfilm';}
            elseif(in_array('notatedmusic', $formats)) {$return = 'partitur';}
            elseif(in_array('monographseries', $formats)) {$return = 'book';}
            elseif(in_array('musicalscore', $formats)) {$return = 'partitur';}
            elseif(in_array('music-cd', $formats)) {$return = 'music-disc';}
            elseif(in_array('norm', $formats)) {$return = 'norm';}
            elseif(in_array('vinylrecord', $formats)) {$return = 'platter';}
            elseif(in_array('otheraudiocarrier', $formats)) {$return = 'sound';}
            elseif(in_array('performedmusic', $formats)) {$return = 'sound';}
            elseif(in_array('pdf', $formats)) {$return = 'article';}
            elseif(in_array('platter', $formats)) {$return = 'platter';}
            elseif(in_array('proceedings', $formats)) {$return = 'article';}
            elseif(in_array('serial', $formats)) {$return = 'collection';}
            elseif(in_array('serialcomponentpart', $formats)) {$return = 'article';}
            elseif(in_array('sheet', $formats)) {$return = 'partitur';}
            elseif(in_array('soundrecording', $formats)) {$return = 'sound';}
            elseif(in_array('specialprint', $formats)) {$return = 'book';}
            elseif(in_array('stillimage', $formats)) {$return = 'image';}
            elseif(in_array('text', $formats)) {$return = 'article';}
            elseif(in_array('textbook', $formats)) {$return = 'book';}
            elseif(in_array('thesis', $formats)) {$return = 'thesis';}
            elseif(in_array('twodemensionalmovingimage', $formats)) {$return = 'movie';}
            elseif(in_array('video', $formats)) {$return = 'video-disc';}
            elseif(in_array('vhs', $formats)) {$return = 'vhs';}
            elseif(in_array('newspaper', $formats)) {$return = 'newspaper';}
            // fallback: besser neutral als article
            else {$return =  'unknown'; }
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
        $mappings = [];
        $mappings['a']['d'] = 'Atlas';
        $mappings['a']['default'] = 'Map';
        $mappings['c']['a'] = 'TapeCartridge';
        $mappings['c']['b'] = 'ChipCartridge';
        $mappings['c']['c'] = 'DiscCartridge';
        $mappings['c']['f'] = 'TapeCassette';
        $mappings['c']['h'] = 'TapeReel';
        $mappings['c']['j'] = 'FloppyDisk';
        $mappings['c']['m'] = 'MagnetoOpticalDisc';
        $mappings['c']['z'] = 'E-Journal on Disc';
        $mappings['c']['o'] = 'OpticalDisc';
        // Do not return - this will cause anything with an
        // 856 field to be labeled as "Electronic"
        $mappings['c']['r'] = 'E-Journal';
        $mappings['c']['default'] = 'ElectronicResource'; //Software passt aber bei eBooks nicht?
        $mappings['d']['default'] = 'Globe';
        $mappings['f']['default'] = 'Braille';
        $mappings['g']['c'] = 'FilmstripCartridge';
        $mappings['g']['d'] = 'Filmstrip';
        $mappings['g']['s'] = 'Slide';
        $mappings['g']['t'] = 'Transparency';
        $mappings['g']['default'] = 'Slide';
        $mappings['h']['default'] = 'Microfilm';
        $mappings['k']['c'] = 'Collage';
        $mappings['k']['d'] = 'Drawing';
        $mappings['k']['e'] = 'Painting';
        $mappings['k']['f'] = 'Print';
        $mappings['k']['g'] = 'Photonegative';
        $mappings['k']['j'] = 'Print';
        $mappings['k']['l'] = 'Drawing';
        $mappings['k']['o'] = 'FlashCard';
        $mappings['k']['n'] = 'Chart';
        $mappings['k']['default'] = 'Photo';
        $mappings['m']['f'] = 'VideoCassette';
        $mappings['m']['r'] = 'Filmstrip';
        $mappings['m']['default'] = 'MotionPicture';
        $mappings['o']['default'] = 'Kit';
        $mappings['q']['u'] = 'SheetMusic';
        $mappings['q']['default'] = 'MusicalScore';
        $mappings['r']['default'] = 'SensorImage';
        $mappings['s']['d'] = 'CD';
        $mappings['s']['o'] = 'SoundRecording'; // SO ist not specified
        $mappings['s']['s'] = 'SoundCassette';
        $mappings['s']['z'] = 'Platter'; //Undefined aber sind meist Schallplatten        
        $mappings['s']['default'] = 'SoundRecording'; // eigentlich unspecified
        $mappings['t']['a'] = 'Printed'; //Text               
        $mappings['t']['d'] = 'LooseLeaf'; //Text               
        $mappings['t']['default'] = null; //Text               
        $mappings['v']['c'] = 'VideoCartridge';
        $mappings['v']['d'] = 'VideoDisc';
        $mappings['v']['f'] = 'VideoCassette';
        $mappings['v']['r'] = 'VideoReel';
        $mappings['v']['default'] = 'Video';     
        $mappings['z']['default'] = 'Kit';     


        if (isset($mappings[$code1])) {
            if (!empty($mappings[$code1][$code2])) {
                $medium = $mappings[$code1][$code2];
            } elseif(!empty($mappings[$code1]['default'])) {
                $medium = $mappings[$code1]['default'];                        
            }

        }
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
        $mappings = [];
        $mappings['a']['default'] = 'Article'; // Artikel aus Zeitschrift
        $mappings['b']['default'] = 'Article'; 
        $mappings['m']['c'] = 'E-Book';
        $mappings['m']['v'] = 'Video';
        $mappings['m']['s'] = 'SoundRecording';
        $mappings['m']['default'] = 'Book';
        $mappings['s']['n'] = 'Newspaper';
        $mappings['s']['p'] = 'Journal';
        $mappings['s']['m'] = 'Serial';
        $mappings['s']['default'] = 'Serial';       

        if (isset($mappings[$leader7])) {
            if ($leader7 == 's' && isset($mappings[$leader7][$f008])) {
                $format = $mappings[$leader7][$f008];                
            } elseif ($leader7 != 's' && isset($mappings[$leader7][$f007])) {
                $format = $mappings[$leader7][$f007];
            } elseif(isset($mappings[$leader7]['default'])) {
                $format = $mappings[$leader7]['default'];                        
            }
        }
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

        $mappings = [];
        $mappings['c'] = 'MusicalScore';
        $mappings['d'] = 'MusicalScore';
        $mappings['e'] = 'Map';
        $mappings['f'] = 'Map';
        $mappings['g'] = 'Slide';
        $mappings['i'] = 'Sound';
        $mappings['j'] = 'MusicRecording';
        $mappings['k'] = 'Photo';
        $mappings['m'] = 'Electronic';
        $mappings['o'] = 'Kit';
        $mappings['p'] = 'Kit';
        $mappings['r'] = 'PhysicalObject';
        $mappings['t'] = 'Manuscript';
        
     

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
        foreach($formats as$k => $format) {
            
            if (!empty($format)) {
                $formats[$k] = ucfirst($format);                
            }
        }
        if(in_array('SoundRecording', $formats) && in_array('MusicRecording', $formats)) {return ['Musik']; }
        elseif(in_array('SheetMusic', $formats) && in_array('Book', $formats)) {return ['MusicalScore']; }
        elseif(in_array('Map', $formats) && in_array('Book', $formats)) {return ['mapmaterial']; }
        elseif(in_array('Platter', $formats) && in_array('SoundRecording', $formats)) {return ['Platter']; }
        elseif(in_array('E-Journal', $formats) && in_array('E-Book', $formats)) {return ['E-Book']; }
        elseif(in_array('E-Journal on Disc', $formats) && in_array('Journal', $formats)) {return ['E-Journal']; }
        elseif(in_array('VideoDisc', $formats) && in_array('Video', $formats)) {return ['DVD/BluRay']; }
        elseif(in_array('CD', $formats) && in_array('SoundRecording', $formats)) {return ['Music-CD']; }
        elseif(in_array('OpticalDisc', $formats) && in_array('E-Book', $formats)) {return ['CD-ROM']; }
        elseif(in_array('E-Journal', $formats) && in_array('Journal', $formats)) {return ['E-Journal']; }
        elseif(in_array('E-Journal', $formats) && in_array('Article', $formats)) {return ['Article']; }
        elseif(in_array('Journal', $formats) && in_array('Printed', $formats)) {return ['E-Journal']; }
        //elseif(in_array('E-Journal', $formats) && in_array('Newspaper', $formats)) {return ['Newspaper']; }
        elseif(in_array('VideoCassette', $formats) && in_array('Video', $formats)) {return ['VHS']; }
        elseif(in_array('Microfilm', $formats) && in_array('Book', $formats)) {return ['Book']; }
        elseif(in_array('Microfilm', $formats) && in_array('Journal', $formats)) {return ['Journal']; }
        elseif(in_array('SoundCassette', $formats) && in_array('SoundRecording', $formats)) {return ['Cassette']; }
        elseif(in_array('SoundRecording', $formats) && in_array('Article', $formats)) {return ['Music-CD']; } //Kommt im GBV vor
        elseif(in_array('E-Journal', $formats) && in_array('Newspaper', $formats)) {return ['Newspaper']; }
        elseif(in_array('Compilation', $formats) && in_array('Book', $formats)) {return ['Compilation']; }
        
        return $formats;
    }
    
}


<?php
/*
 * Copyright 2021 (C) Bibliotheksservice-Zentrum Baden-
 * Württemberg, Konstanz, Germany
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
 *
 */

namespace Bsz\RecordDriver;

use Bsz\Exception;
use Zend\Config\Config;

trait MarcFormatTrait
{
    protected $formatConfig;
    protected $formatConfigRda;

    /**
     * @param Config $marc
     * @param Config $rda
     */
    public function attachFormatConfig(array $marc, array $rda)
    {
        $this->formatConfig = $marc;
        $this->formatConfigRda = $rda;
    }

    public function getFormatMarc()
    {
        foreach ($this->formatConfig as $format => $settings) {
            foreach ($settings as $setting) {
                if (!isset($setting['field'])) {
                    throw new Exception('Format mappings must have a field entry.');
                }

                $params = isset($setting['position']) ? [$setting['position']] : [];
                $method = 'get'.$setting['field'];

                $result = $this->tryMethod($method, $params);

                if ($this->checkValue($result, $setting['value'])) {
                    return $format;
                }
            }
        }
        return '';
    }

    /**
     * Recursive method to determine if a value matches the given strings
     *
     * @param $value
     * @param $allowedValues
     *
     * @return bool
     */
    protected function checkValue($value, $allowedValues)
    {
        $allowed = explode(', ', $allowedValues);
        if (is_array($value)) {
            $result = [];
            foreach ($value as $v) {
                $result[] = $this->checkValues($v);
            }
            return in_array(true, $result);
        }
        foreach ($allowed as $a) {
            $regex = '/^'.$a.'$/';
            if (preg_match($regex, $value)) {
                return true;
            }
        }
        return false;
    }

    public function simplifyFormats(array $formats)
    {
        if ($this->isElectronic()) {
            $formats[] = 'Online';
        }

        $formats = array_filter($formats);
        $formats = array_unique($formats);
        $formats = array_values($formats);

        // Convenience
        if (in_array('Online', $formats)
            && in_array('Book', $formats)
        ) {
            $formats = ['EBook'];
        }

        return $formats;
    }

    public function getFormatRda()
    {
    }

    /**
     * Is this record an electronic item
     *
     * @return boolean
     */

    public function isElectronic() : bool
    {
        $f007 = $this->get007('/^c/i');
        $f008 = $this->get008(23);
        $f338 = $this->getRdaCarrier();

        if (count($f007) > 0 || $f008 === 'o' || $f338 == 'cr') {
            return true;
        }
        return false;
    }

    /**
     * Everything that is not electronical is automatically physical.
     *
     * @return bool
     */
    public function isPhysical() : bool
    {
        return !$this->isElectronic();
    }

    /**
     * Get all 007 fields
     *
     * @param string $pattern
     *
     * @return array
     */

    protected function get007($pattern = '/.*/') : array
    {
        $f007 = $this->getMarcRecord()->getFields("007");
        $retval = [];
        foreach ($f007 as $field) {
            $tmp = $field->getData();
            $tmp = substr($tmp, 0, 2);
            if (preg_match($pattern, $tmp)) {
                $retval[] = strtolower($tmp);
            }
        }
        return $retval;
    }

    /**
     * GEt RDA carrier code (field 336)
     *
     * @return string
     */

    protected function getRdaContent() : string
    {
        $sub = '';
        $field = $this->getMarcRecord()->getField(336);
        if (is_object($field)) {
            $sub = $field->getSubfield('b');
        }
        return strtolower($sub);
    }

    /**
     * GEt RDA media code (field 337)
     *
     * @return string
     */

    protected function getRdaMedia() : string
    {
        $sub = '';
        $field = $this->getMarcRecord()->getField(337);
        if (is_object($field)) {
            $sub = $field->getSubfield('b');
        }
        return strtolower($sub);
    }

    /**
     * Get RDA content code (field 338)
     *
     * @return string
     */

    protected function getRdaCarrier(): string
    {
        $sub = '';
        $field = $this->getMarcRecord()->getField(338);
        if (is_object($field)) {
            $sub = $field->getSubfield('b');
        }
        return strtolower($sub);
    }

    /**
     * Get a specified position of 008 or empty string
     *
     * @param int $pos
     *
     * @return string
     */

    protected function get008(int $pos = null) : string
    {
        $f008 = $this->getMarcRecord()->getField("008", false);
        $data = $f008->getData();
        $retval = $data ?? '';

        if (isset($pos) && strlen($data) >= $pos + 1) {
            $retval = $data{$pos};
        }
        return strtolower($retval);
    }

    /**
     * Get Leader at $pos
     *
     * @param int $pos
     *
     * @return string
     */
    protected function getLeader(int $pos) : string
    {
        $leader = $this->getMarcRecord()->getLeader();
        $retval= $leader ?? '';
        if (strlen($leader) > $pos - 1) {
            $retval = $leader{$pos};
        }
        return $retval;
    }

    /**
     * Nach der Dokumentation des Fernleihportals
     * @return boolean
     */

    public function isArticle()
    {

        // A = Aufsätze aus Monographien
        // B = Aufsätze aus Zeitschriften (wird aber wohl nicht genutzt))
        $leader = $this->getLeader(7);
        if ($leader == 'a' || $leader == 'b') {
            return true;
        }
        return false;
    }

    /**
     * Is this a book serie?
     * @return boolean
     */
    public function isMonographicSerial()
    {
        $f008 = null;
        $f008_21 = '';
        $f008 = $this->get008(21);
        if ($this->isSerial() && $f008 == 'm') {
            return true;
        }
        return false;
    }

    /**
     * General serial items. More exact is:
     * isJournal(), isNewspaper() isMonographicSerial()
     *
     * @return boolean
     */
    public function isSerial()
    {
        $leader = $this->getMarcRecord()->getLeader();
        $leader_7 = $leader{7};
        if ($leader_7 === 's') {
            return true;
        }
        return false;
    }

    /**
     * Ist der Titel ein EBook?
     * Wertet die Felder 007/00, 007/01 und Leader 7 aus
     *
     * @return boolean
     */
    public function isEBook()
    {
        $f007 = $this->get007('/^cr/i');
        $leader = $this->getLeader(7);

        if ($leader == 'm') {
            if (count($f007) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Ist der Titel ein Buch, das schließt auch eBooks mit ein!
     * Wertet den Leader aus
     *
     * @return boolean
     */
    public function isPhysicalBook()
    {
        $f007 = $this->get007('/^t/i');
        $leader = $this->getLeader(7);

        if ($leader == 'm' && count($f007) > 0) {
            return true;
        }
        return false;
    }

    /**
     * is this a Journal, implies it's a serial
     *
     * @return boolean
     */
    public function isJournal()
    {
        $f008 = $this->get008(21);

        if ($this->isSerial() && $f008 == 'p') {
            return true;
        }
        return false;
    }

    /**
     * iIs this a Newspaper?
     *
     * @return boolean
     */
    public function isNewspaper()
    {
        $f008 = $this->get008(21);

        if ($this->isSerial() && $f008 == 'n') {
            return true;
        }
        return false;
    }

    /**
     * Determine  if a record is freely available.
     * Indicator 2 references to the record itself.
     *
     * @return boolean
     */
    public function isFree()
    {
        $f856 = $this->getMarcRecord()->getFields(856);
        foreach ($f856 as $field) {
            $z = $field->getSubfield('z');
            if (is_string($z) && $field->getIndicator(2) == 0
                && preg_match('/^kostenlos|kostenfrei$/i', $z)
            ) {
                return true;
            }
        }
        return false;
    }
}

<?php
/**
 * Factory for record driver data formatting view helper
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2016.
 * Copyright (C) The National Library of Finland 2017.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
namespace finc\View\Helper\Root;

use VuFind\View\Helper\Root\RecordDataFormatter;

/**
 * Factory for record driver data formatting view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
class RecordDataFormatterFactory
{
    /**
     * Create the helper.
     *
     * @return RecordDataFormatter
     */
    public function __invoke()
    {
        $helper = new RecordDataFormatter();

        $helper->setDefaults(
            'collection-info', [$this, 'getDefaultCollectionInfoSpecs']
        );
        $helper->setDefaults(
            'collection-record', [$this, 'getDefaultCollectionRecordSpecs']
        );
        $helper->setDefaults('core', [$this, 'getDefaultCoreSpecs']);
        $helper->setDefaults(
            'description', [$this, 'getDefaultDescriptionSpecs']
        );

        $helper->setDefaults('core-ai', [$this, 'getAiCoreSpecs']);

        $helper->setDefaults('core-lido', [$this, 'getLidoCoreSpecs']);
        $helper->setDefaults(
            'description-lido', [$this, 'getLidoDescriptionSpecs']
        );

        $helper->setDefaults('core-marc', [$this, 'getMarcCoreSpecs']);
        return $helper;
    }

    /**
     * Get default specifications for displaying data in core metadata.
     *
     * @return array
     */
    public function getAiCoreSpecs()
    {
        $spec = new RecordDataFormatter\SpecBuilder();
        $spec->setTemplateLine(
            'Journal Title', 'getContainerTitle', 'data-containerTitle.phtml'
        );
        $spec->setTemplateLine(
            'Authors/Corporations',
            'getDeduplicatedAuthors',
            'data-authors.phtml',
            [
                'useCache' => true,
                'context' => [
                    'requiredDataFields' => [
                        ['name' => 'role', 'prefix' => 'CreatorRoles::']
                    ]
                ]
            ]
        );
        $spec->setLine(
            'Edition', 'getEdition', null,
            ['prefix' => '<span property="bookEdition">', 'suffix' => '</span>']
        );
        $spec->setTemplateLine(
            'In',
            'getJTitle',
            'data-jTitle.phtml'
        );
        $spec->setLine(
            'Format', 'getFormats', 'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        $spec->setTemplateLine(
            'Language', 'getLanguages', 'data-transEscCommaSep.phtml'
        );
        $spec->setTemplateLine(
            'Published',
            'getPublicationDetails',
            'data-publicationDetails.phtml'
        );
        $spec->setLine(
            'Series', 'getSeries', null, ['recordLink' => 'series']
        );
        $spec->setTemplateLine(
            'Subjects', 'getAllSubjectHeadings', 'data-allSubjectHeadings.phtml'
        );
        $spec->setTemplateLine(
            'child_records', 'getChildRecordCount', 'data-childRecords.phtml',
            ['allowZero' => false]
        );
        $spec->setTemplateLine(
            'Online Access', true, 'data-onlineAccess.phtml'
        );
        $spec->setTemplateLine(
            'Related Items', 'getAllRecordLinks', 'data-allRecordLinks.phtml'
        );
        $spec->setTemplateLine('Tags', true, 'data-tags.phtml');
        return $spec->getArray();
    }

    /**
     * Get default specifications for displaying data in collection-info metadata.
     *
     * @return array
     */
    public function getDefaultCollectionInfoSpecs()
    {
        $spec = new RecordDataFormatter\SpecBuilder();
        $spec->setTemplateLine(
            'Authors/Corporations',
            'getDeduplicatedAuthors',
            'data-authors.phtml',
            [
                'useCache' => true,
                'context' => [
                    'requiredDataFields' => [
                        ['name' => 'role', 'prefix' => 'CreatorRoles::']
                    ]
                ]
            ]
        );
        $spec->setTemplateLine(
            'Title', 'getTitleDetails', 'data-titleDetails.phtml'
        );
        $spec->setTemplateLine(
            'Dates of publication', 'getDateSpan', 'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Summary', 'getSummary', 'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Published', 'getPublicationDetails', 'data-publicationDetails.phtml'
        );
        $spec->setLine(
            'Edition', 'getEdition', null,
            ['prefix' => '<span property="bookEdition">', 'suffix' => '</span>']
        );
        $spec->setTemplateLine(
            'Subjects', 'getAllSubjectHeadings', 'data-allSubjectHeadings.phtml'
        );
        $spec->setTemplateLine(
            'Online Access', true, 'data-onlineAccess.phtml'
        );
        $spec->setTemplateLine(
            'Item Description', 'getGeneralNotes', 'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Production Credits',
            'getProductionCredits',
            'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Set Multipart',
            'getHierarchyParentTitle',
            'data-hierarchyParentTitle.phtml'
        );
        $spec->setTemplateLine(
            'ISBN', 'getISBNs', 'data-isbn.phtml'
        );
        $spec->setTemplateLine(
            'ISSN', 'getISSNs', 'data-issn.phtml'
        );
        $spec->setTemplateLine(
            'Notes',
            'getAdditionalNotes',
            'data-escapeHtml.phtml',
            [
                'useCache' => true
            ]
        );
        $spec->setLine(
            'Format', 'getFormats', 'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        $spec->setTemplateLine(
            'Language', 'getLanguages', 'data-transEscCommaSep.phtml'
        );
        $spec->setTemplateLine(
            'Additionals',
            'getAdditionals',
            'data-additionals.phtml',
            [
                'labelFunction' => function() { return null; }
            ]
        );
        return $spec->getArray();
    }

    /**
     * Get default specifications for displaying data in collection-record metadata.
     *
     * @return array
     */
    public function getDefaultCollectionRecordSpecs()
    {
        $spec = new RecordDataFormatter\SpecBuilder();
        $spec->setLine('Summary', 'getSummary');
        $spec->setTemplateLine(
            'Authors/Corporations',
            'getDeduplicatedAuthors',
            'data-authors.phtml',
            [
                'useCache' => true,
                'context' => [
                    'requiredDataFields' => [
                        ['name' => 'role', 'prefix' => 'CreatorRoles::']
                    ]
                ]
            ]
        );
        $spec->setTemplateLine(
            'Language', 'getLanguages', 'data-transEscCommaSep.phtml'
        );
        $spec->setLine(
            'Format', 'getFormats', 'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        $spec->setTemplateLine(
            'Access', 'getAccessRestrictions', 'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Related Items', 'getRelationshipNotes', 'data-escapeHtml.phtml'
        );
        return $spec->getArray();
    }

    /**
     * Get default specifications for displaying data in core metadata.
     *
     * @return array
     */
    public function getDefaultCoreSpecs()
    {
        $spec = new RecordDataFormatter\SpecBuilder();
        $spec->setTemplateLine(
            'Published in', 'getContainerTitle', 'data-containerTitle.phtml'
        );
        $spec->setLine(
            'New Title', 'getNewerTitles', null, ['recordLink' => 'title']
        );
        $spec->setLine(
            'Previous Title', 'getPreviousTitles', null, ['recordLink' => 'title']
        );
        $spec->setTemplateLine(
            'Authors/Corporations', 'getDeduplicatedAuthors', 'data-authors.phtml',
            [
                'useCache' => true,
                'context' => [
                    'requiredDataFields' => [
                        ['name' => 'role', 'prefix' => 'CreatorRoles::']
                    ]
                ]
            ]
        );
        $spec->setTemplateLine(
            'Title', 'getTitleDetails', 'data-titleDetails.phtml'
        );
        $spec->setTemplateLine(
            'Title Uniform', 'getTitleUniform', 'data-titleUniform.phtml',
            [
                'labelFunction' => function() { return null; }
            ]
        );
        $spec->setLine(
            'Edition', 'getEdition', null,
            ['prefix' => '<span property="bookEdition">', 'suffix' => '</span>']
        );
        $spec->setLine(
            'Dissertation Note',
            'getDissertationNote',
            'data-escapeHtmlCommaSep.phtml'
        );
        $spec->setLine(
            'Format', 'getFormats', 'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        $spec->setTemplateLine(
            'Language', 'getLanguages', 'data-transEscCommaSep.phtml'
        );
        $spec->setTemplateLine(
            'Published',
            'getPublicationDetails',
            'data-publicationDetails.phtml'
        );
        $spec->setTemplateLine(
            'Set Multipart',
            'getHierarchyParentTitle',
            'data-hierarchyParentTitle.phtml'
        );
        $spec->setTemplateLine(
            'Subjects', 'getAllSubjectHeadings', 'data-allSubjectHeadings.phtml'
        );
        $spec->setTemplateLine(
            'child_records', 'getChildRecordCount', 'data-childRecords.phtml',
            ['allowZero' => false]
        );
        $spec->setTemplateLine('Online Access', true, 'data-onlineAccess.phtml');
        $spec->setTemplateLine(
            'Related Items', 'getAllRecordLinks', 'data-allRecordLinks.phtml'
        );
        $spec->setTemplateLine(
            'Additionals',
            'getAdditionals',
            'data-additionals.phtml',
            [
                'labelFunction' => function() { return null; }
            ]
        );
        $spec->setTemplateLine(
            'Source',
            'getMegaCollection',
            'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            null,
            'getOtherRelationshipEntry',
            'data-otherRelationshipEntry.phtml',
            [

            ]
        );
        $spec->setTemplateLine(
            'Notes',
            'getAdditionalNotes',
            'data-escapeHtml.phtml',
            [
                'useCache' => true
            ]
        );
        $spec->setTemplateLine('Tags', true, 'data-tags.phtml');
        return $spec->getArray();
    }

    /**
     * Get default specifications for displaying data in the description tab.
     *
     * @return array
     */
    public function getDefaultDescriptionSpecs()
    {
        $spec = new RecordDataFormatter\SpecBuilder();
        $spec->setTemplateLine(
            'Summary', 'getSummary', 'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Dates of publication', 'getDateSpan', 'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Item Description', 'getGeneralNotes', 'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Physical Description',
            'getPhysicalDescriptions',
            'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Publication Frequency',
            'getPublicationFrequency',
            'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Playing Time', 'getPlayingTimes', 'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Format', 'getSystemDetails', 'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Audience', 'getTargetAudienceNotes', 'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Awards', 'getAwards', 'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Production Credits',
            'getProductionCredits',
            'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Bibliography', 'getBibliographyNotes', 'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'ISBN', 'getISBNs', 'data-isbn.phtml'
        );
        $spec->setTemplateLine(
            'ISSN', 'getISSNs', 'data-issn.phtml'
        );
        $spec->setTemplateLine(
            'DOI', 'getCleanDOI', 'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'EISSN', 'getEISSNs', 'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Related Items', 'getRelationshipNotes', 'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Access', 'getAccessRestrictions', 'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Finding Aid', 'getFindingAids', 'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Publication_Place',
            'getHierarchicalPlaceNames',
            'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'Author Notes', true, 'data-authorNotes.phtml'
        );
        $spec->setTemplateLine(
            'Call Number',
            'getLocalSignature',
            'data-localSignature.phtml'
        );
        $spec->setTemplateLine(
            'Notes',
            'getAdditionalNotes',
            'data-escapeHtml.phtml',
            [
                'useCache' => true
            ]
        );
        return $spec->getArray();
    }

    /**
     * Get default specifications for displaying data in collection-info metadata.
     *
     * @return array
     */
    public function getLidoCoreSpecs()
    {
        $spec = new RecordDataFormatter\SpecBuilder();
        $spec->setTemplateLine(
            'Originators', 'getDeduplicatedAuthors', 'data-authors.phtml',
            [
                'useCache' => true,
                'labelFunction' => function ($data) {
                    return count($data['main']) > 1
                        ? 'Originators' : 'Originator';
                },
                'context' => [
                    'type' => 'main',
                    'schemaLabel' => 'author',
                    'requiredDataFields' => [
                        ['name' => 'role', 'prefix' => 'CreatorRoles::']
                    ]
                ],
            ]
        );
        $spec->setTemplateLine(
            'Corporate Originator', 'getDeduplicatedAuthors', 'data-authors.phtml',
            [
                'useCache' => true,
                'labelFunction' => function ($data) {
                    return count($data['corporate']) > 1
                        ? 'Corporate Originators' : 'Corporate Originator';
                },
                'context' => [
                    'type' => 'corporate',
                    'schemaLabel' => 'creator',
                    'requiredDataFields' => [
                        ['name' => 'role', 'prefix' => 'CreatorRoles::']
                    ]
                ],
            ]
        );
        $spec->setTemplateLine(
            'Other Originators', 'getDeduplicatedAuthors', 'data-authors.phtml',
            [
                'useCache' => true,
                'context' => [
                    'type' => 'secondary',
                    'schemaLabel' => 'contributor',
                    'requiredDataFields' => [
                        ['name' => 'role', 'prefix' => 'CreatorRoles::']
                    ]
                ],
            ]
        );
        $spec->setTemplateLine(
            'Subject Detail', 'getSubjectDetails', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordSubjects']
            ]
        );
        $spec->setTemplateLine(
            'Subject Place', 'getSubjectPlaces', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordSubjects']
            ]
        );
        $spec->setTemplateLine(
            'Subject Date', 'getSubjectDates', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordSubjects']
            ]
        );

        $spec->setTemplateLine(
            'Subject Actor', 'getSubjectActors', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordSubjects']
            ]
        );
        $spec->setTemplateLine(
            'Institution', 'getInstitutions', 'data-institutions.phtml',
            [
                'context' => ['class' => 'recordInstitution']
            ]
        );
        $spec->setTemplateLine(
            'Inventory ID', 'getIdentifier', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordIdentifier']
            ]
        );
        $spec->setTemplateLine(
            'Inventory ID', 'getIdentifier', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordIdentifier']
            ]
        );
        $spec->setTemplateLine(
            'Measurements', 'getMeasurements', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordMeasurements']
            ]
        );
        $spec->setTemplateLine(
            'Measurements',
            'getMeasurementsDescription',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordMeasurements']
            ]
        );
        $spec->setTemplateLine(
            'Collection', 'getCollections', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordCollection']
            ]
        );
        $spec->setLine(
            'Object type', 'getFormats', 'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        $spec->setTemplateLine(
            'Other Classification',
            'getFormatClassifications',
            'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordClassifications']
            ]
        );
        $spec->setTemplateLine(
            'Other ID', 'getLocalIdentifiers', 'data-escapeHtml.phtml',
            [
                'context' => ['class' => 'recordIdentifiers']
            ]
        );
        $spec->setTemplateLine(
            'Events', true, 'data-events.phtml',
            [
                'labelFunction' => function() { return null; }
            ]
        );
        // , context: "recordEvents"
        $spec->setTemplateLine(
            'Language', 'getLanguages','data-transEscCommaSep.phtml'
        );
        $spec->setTemplateLine(
            'Time of origin', 'getDateSpan', 'data-dateSpan.phtml'
        );
        $spec->setTemplateLine('Edition', 'getEdition', 'data-escapeHtml.phtml',
            [
                'prefix' => '<span property="bookEdition">',
                'suffix' => '</span>'
            ]
        );
        $spec->setTemplateLine(
            'Subjects', 'getAllSubjectHeadings', 'data-allSubjectHeadings.phtml'
        );
        $spec->setTemplateLine('Online Access', true, 'data-onlineAccess.phtml');
        $spec->setTemplateLine(
            'Related Items', 'getAllRecordLinks', 'data-allRecordLinks.phtml'
        );
        return $spec->getArray();
    }

    /**
     * Get default specifications for displaying data in collection-record metadata.
     *
     * @return array
     */
    public function getLidoDescriptionSpecs()
    {
        $spec = new RecordDataFormatter\SpecBuilder();
        $spec->setTemplateLine(
            'Time of origin', 'getDateSpan', 'data-dateSpan.phtml'
        );
        $spec->setTemplateLine(
            'Access', 'getAccessNote','data-accessNote.phtml'
        );
        return $spec->getArray();
    }

    /**
     * Get marc specifications for displaying data in core metadata.
     *
     * @return array
     */
    public function getMarcCoreSpecs()
    {
        $spec = new RecordDataFormatter\SpecBuilder();
        $spec->setTemplateLine(
            'Published in', 'getContainerTitle', 'data-containerTitle.phtml'
        );
        $spec->setLine(
            'New Title', 'getNewerTitles', 'data-linkViaFincId.phtml'
        );
        $spec->setLine(
            'Previous Title', 'getPreviousTitles', 'data-linkViaFincId.phtml'
        );
        $spec->setTemplateLine(
            'Authors/Corporations',
            'getDeduplicatedAuthors',
            'data-authors.phtml',
            [
                'useCache' => true,
                'context' => [
                    'requiredDataFields' => [
                        ['name' => 'role', 'prefix' => 'CreatorRoles::']
                    ]
                ]
            ]
        );
        $spec->setTemplateLine(
            'Title', 'getTitleDetails', 'data-titleDetails.phtml'
        );
        $spec->setTemplateLine(
            'Title Uniform', 'getTitleUniform', 'data-titleUniform.phtml',
            [
                'labelFunction' => function() { return null; }
            ]
        );
        $spec->setLine(
            'Edition', 'getEdition', null,
            ['prefix' => '<span property="bookEdition">', 'suffix' => '</span>']
        );
        $spec->setLine(
            'Dissertation Note',
            'getDissertationNote',
            'data-escapeHtmlCommaSep.phtml'
        );
        $spec->setLine(
            'Format', 'getFormats', 'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        $spec->setTemplateLine(
            'Language', 'getLanguages', 'data-transEscCommaSep.phtml'
        );
        $spec->setTemplateLine(
            'Published',
            'getPublicationDetails',
            'data-publicationDetails.phtml'
        );
        $spec->setTemplateLine(
            'German Prints Index Number',
            'getIndexOfGermanPrints',
            'data-indexOfGermanPrints.phtml'
        );
        $spec->setTemplateLine(
            'Set Multipart',
            'getHierarchyParentTitle',
            'data-hierarchyParentTitle.phtml'
        );
        $spec->setTemplateLine(
            'Subjects', 'getAllSubjectHeadings', 'data-allSubjectHeadings.phtml'
        );
        $spec->setTemplateLine(
            'child_records', 'getChildRecordCount', 'data-childRecords.phtml',
            ['allowZero' => false]
        );
        $spec->setTemplateLine('Online Access', true, 'data-onlineAccess.phtml');
        $spec->setTemplateLine(
            'Related Items', 'getAllRecordLinks', 'data-allRecordLinks.phtml'
        );
        $spec->setTemplateLine(
            'Local Subjects',
            'getLocalSubjects',
            'data-localSubjects.phtml',
            [
                'labelFunction' => function() { return 'Subject'; }
            ]
        );
        $spec->setTemplateLine(
            'Source',
            'getMegaCollection',
            'data-escapeHtml.phtml'
        );
        $spec->setTemplateLine(
            'OtherRelationshipEntry',
            'getOtherRelationshipEntry',
            'data-otherRelationshipEntry.phtml',
            [
                'labelFunction' => function() { return null; }
            ]
        );
        $spec->setTemplateLine(
            'Notes',
            'getAdditionalNotes',
            'data-escapeHtml.phtml',
            [
                'useCache' => true
            ]
        );
        $spec->setTemplateLine('Tags', true, 'data-tags.phtml');
        return $spec->getArray();
    }


}

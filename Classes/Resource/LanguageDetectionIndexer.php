<?php
namespace ApacheSolrForTypo3\Tika\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Phuong Doan <phuong.doan@dkd.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Resource;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\CommandUtility;

/**
 * A Language Indexer from files and updates FAL Meta data
 *
 * @author	Phuong Doan <phuong.doan@dkd.de>
 * @package TYPO3
 * @subpackage tika
 */
class LanguageDetectionIndexer implements ExtractorInterface {

    /**
     * @var	array
     */
    protected $tikaConfiguration;

    /**
     * Default constructor.
     */
    public function __construct() {
        $this->tikaConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tika']);

        if ($this->tikaConfiguration['extractor'] == 'tika' && !is_file(GeneralUtility::getFileAbsFileName($this->tikaConfiguration['tikaPath'], FALSE))) {
            throw new Exception(
                'Invalid path or filename for tika application jar.',
                1266864929
            );
        }
    }

    /**
     * Returns an array of supported file types;
     * An empty array indicates all filetypes
     *
     * @return array
     */
    public function getFileTypeRestrictions()
    {
        return array();
    }

    /**
     * Get all supported DriverClasses
     *
     * Since some extractors may only work for local files, and other extractors
     * are especially made for grabbing data from remote.
     *
     * Returns array of string with driver names of Drivers which are supported,
     * If the driver did not register a name, it's the classname.
     * empty array indicates no restrictions
     *
     * @return array
     */
    public function getDriverRestrictions()
    {
        return array();
    }

    /**
     * Returns the data priority of the extraction Service.
     * Defines the precedence of Data if several extractors
     * extracted the same property.
     *
     * Should be between 1 and 100, 100 is more important than 1
     *
     * @return integer
     */
    public function getPriority()
    {
        return 60;
    }

    /**
     * Returns the execution priority of the extraction Service
     * Should be between 1 and 100, 100 means runs as first service, 1 runs at last service
     *
     * @return integer
     */
    public function getExecutionPriority()
    {
        return 60;
    }

    /**
     * Checks if the given file can be processed by this Extractor
     *
     * @param Resource\File $file
     * @return boolean
     */
    public function canProcess(Resource\File $file)
    {
        return true;
    }

    /**
     * The actual processing TASK
     *
     * Should return an array with database properties for sys_file_metadata to write
     *
     * @param Resource\File $file
     * @param array $previousExtractedData optional, contains the array of already extracted data
     * @return array
     */
    public function extractMetaData(Resource\File $file, array $previousExtractedData = array())
    {
        $metaData = array();
        if (is_object($serviceObj = GeneralUtility::makeInstanceService(
            'textLang'))) {
            $fileName = $file->getForLocalProcessing(FALSE);
            $serviceObj->setInputFile($fileName, $file->getProperty('extension'));
            if ($serviceObj->process()) {
                $output = $serviceObj->getOutput();
                if (!empty($output)) {
                    if ($this->tikaConfiguration['logging']) {
                        GeneralUtility::devLog('Extracting/Detecting language with Tika', 'tika', 0, array(
                            'file'         => $fileName,
                            'shell output' => $output
                        ));
                    }
                    $output = substr($output, 0, 2);
                    $lng = array('language' => $output);
                    $metaData = array_merge($lng, $previousExtractedData);
                }
            }
        }

        return $metaData;
    }
}
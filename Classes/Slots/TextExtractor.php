<?php
namespace ApacheSolrForTypo3\Tika\Slots;
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\File;

/**
 * A service to extract meta data from files using Apache Tika
 *
 * @author	Phuong Doan <phuong.doan@dkd.de>
 * @package TYPO3
 * @subpackage tika
 */
class TextExtractor {

    /**
     * Extracts the content from a given file and updates tika_content in sys_file_metadata
     * representation.
     *
     * @param File $file
     * @return	void
     */
    public function extractContent(File $file) {
        $tikaContent = $this->getContent($file);
        $this->updateMetaData($file, $tikaContent);
    }

    /**
     * Gets a file's textual content or if it is not a text file it's textual
     * representation.
     *
     * @param File $file
     * @return	void
     */
    public function emptyTikaContent(File $file) {
        $this->updateMetaData($file, '');
    }

    /**
     * Gets a file's textual content or if it is not a text file it's textual
     * representation.
     *
     * @param File $file
     * @return string
     */
    protected function getContent(File $file) {
        if('text/plain' == $file->getMimeType()) {
            $content = $file->getContents();
        }
        else if ($this->canExtractText($file)) {
            $extension = $file->getProperty('extension');
            // other subtypes should be handled by the text service
            $service = GeneralUtility::makeInstanceService('textExtract',$extension);
            if (!is_object($service) || is_array($service)) {
                throw new RuntimeException(
                    'Failed to initialize a text extraction service.',
                    '1291394251'
                );
            }

            $service->setInputFile($file->getForLocalProcessing(FALSE), $extension);
            $serviceConfiguration = array('wantedCharset' => 'utf-8');
            $service->process('', '', $serviceConfiguration);

            $content = $service->getOutput();
        } else {
            // return an empty string
            $content = '';
        }

        if($content != '') {
            $content = \Tx_Solr_HtmlContentExtractor::cleanContent($content);
        }


        return $content;
    }

    /**
     * Checks if the given file can be processed by this Extractor
     *
     * @param File $file
     * @return boolean
     */
    protected function canExtractText(File $file)
    {
        $canProcess = FALSE;

        $subtypes = '';
        if ($GLOBALS['T3_SERVICES']['textExtract']) {
            // get the subtypes (allowed file extensions)
            foreach($GLOBALS['T3_SERVICES']['textExtract'] as $key => $info) {
                $subtypes .= $info['subtype'] . ',';
            }
            $subtypes = GeneralUtility::trimExplode(',', $subtypes, TRUE);

            $canProcess = in_array($file->getProperty('extension'), $subtypes);
        }

        return $canProcess;
    }

    /**
     * @param File $file
     * @param string $tikaContent
     */
    public function updateMetaData(File $file, $tikaContent = '') {
        $metadataRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository');
        $newMetadata = array();
        $newMetadata['tika_content'] = $tikaContent;
        $metadataRepository->update($file->getUid(), $newMetadata);
    }

} 
<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance()->registerExtractionService('ApacheSolrForTypo3\\Tika\\Resource\\MetaDataIndexer');
\TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance()->registerExtractionService('ApacheSolrForTypo3\\Tika\\Resource\\LanguageDetectionIndexer');


$PATH_tika = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY);

$tikaConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tika']);
$requiredExecutable = '';
if ($tikaConfiguration['extractor'] == 'tika') {
	$requiredExecutable = 'java';
}

// meta data extraction service
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
	$_EXTKEY,
	'metaExtract',
	'Tx_Tika_MetaExtract',
	array(
		'title' => 'Tika meta data extraction',
		'description' => 'Uses Apache Tika to extract meta data.',

		'subtype' => 'aiff,au,bmp,doc,docx,epub,flv,gif,htm,html,image:exif,jpg,jpeg,mid,mp3,msg,odf,odt,pdf,png,ppt,pptx,rtf,svg,sxw,tgz,tiff,txt,wav,xls,xlsx,xml,zip',

		'available' => FALSE,
		'priority' => 100,
		'quality' => 80,

		'os' => '',
		'exec' => $requiredExecutable,

		'className' => 'ApacheSolrForTypo3\\Tika\\Service\\MetaDataExtractionService',
	)
);

// text extraction service
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
	$_EXTKEY,
	'textExtract',
	'Tx_Tika_TextExtract',
	array(
		'title' => 'Tika text extraction',
		'description' => 'Uses Apache Tika to extract text from files.',

		'subtype' => 'doc,docx,epub,htm,html,msg,odf,odt,pdf,ppt,pptx,rtf,sxw,tgz,txt,xls,xlsx,xml,zip',

		'available' => FALSE,
		'priority' => 60,
		'quality' => 80,

		'os' => '',
		'exec' => $requiredExecutable,

		'className' => 'ApacheSolrForTypo3\\Tika\\Service\\TextExtractionService',
	)
);

// language detection service
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
	$_EXTKEY,
	'textLang',
	'Tx_Tika_TextLang',
	array(
		'title' => 'Tika language detection',
		'description' => 'Uses Apache Tika to detect a document\'s language.
							Currently supports Danish, Dutch, English, Finnish,
							French, German, Italian, Portuguese, Spanish,
							and Swedish',

		'subtype' => '',

		'available' => FALSE,
		'priority' => 60,
		'quality' => 60,

		'os' => '',
		'exec' => $requiredExecutable,

		'className' => 'ApacheSolrForTypo3\\Tika\\Service\\LanguageDetectionService',
	)
);

/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');

$signalSlotDispatcher->connect(
    'TYPO3\\Solr\\Solrfal\\Indexing\\DocumentFactory',
    'addedSolrFileInformation',
    'ApacheSolrForTypo3\\Tika\\Slots\\TextExtractor',
    'extractContent'
);

$signalSlotDispatcher->connect(
    'TYPO3\\Solr\\Solrfal\\Indexing\\Indexer',
    'indexedFileToSolr',
    'ApacheSolrForTypo3\\Tika\\Slots\\TextExtractor',
    'emptyTikaContent'
);

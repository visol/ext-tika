<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {
	// adding the Solr connection status to the status report
	$statusSection = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('solr') ? 'solr' : 'tika';

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'][$statusSection][] = 'ApacheSolrForTypo3\\Tika\\Report\\TikaStatus';
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = 'ApacheSolrForTypo3\\Tika\\StatusCheck->updateStatus';

// checking availability. Must do this here, DB connection is not available yet when ext_localconf.php is loaded
#$registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_Registry');
#$servicesAvailable = $registry->get('Tx_Tika', 'available', FALSE);

// Permanently enabled for now
#TODO use registry with TYPO3 CMS 6.x, too. Must figure out earliest availability of DB
$servicesAvailable = true;

$GLOBALS['T3_SERVICES']['metaExtract']['Tx_Tika_MetaExtract']['available'] = $servicesAvailable;
$GLOBALS['T3_SERVICES']['textExtract']['Tx_Tika_TextExtract']['available'] = $servicesAvailable;
$GLOBALS['T3_SERVICES']['textLang']['Tx_Tika_TextLang']['available'] = $servicesAvailable;

$GLOBALS['T3_SERVICES']['Tx_Tika_MetaExtract']['Tx_Tika_MetaExtract']['available'] = $servicesAvailable;
$GLOBALS['T3_SERVICES']['Tx_Tika_TextExtract']['Tx_Tika_TextExtract']['available'] = $servicesAvailable;
$GLOBALS['T3_SERVICES']['Tx_Tika_TextLang']['Tx_Tika_TextLang']['available'] = $servicesAvailable;


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
       $_EXTKEY,
       'Configuration/TypoScript',
       'Apache Tika for TYPO3 - Extracting Content');

$tmp_tika_columns = array(

    'tika_content' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:tika/Resources/Private/Language/locallang_db.xlf:tx_tika_domain_model_tika.tika_content',
        'config' => array(
            'type' => 'text',
            'cols' => 40,
            'rows' => 15,
            'eval' => 'trim',
            'wizards' => array(
                'RTE' => array(
                    'icon' => 'wizard_rte2.gif',
                    'notNewRecords'=> 1,
                    'RTEonly' => 1,
                    'script' => 'wizard_rte.php',
                    'title' => 'LLL:EXT:cms/locallang_ttc.:bodytext.W.RTE',
                    'type' => 'script'
                )
            )
        ),
        'defaultExtras' => 'richtext[]',
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_file_metadata',$tmp_tika_columns);

$TCA['sys_file_metadata']['columns'][$TCA['sys_file_metadata']['ctrl']['type']]['config']['items'][] = array('LLL:EXT:tika/Resources/Private/Language/locallang_db.xlf:sys_file_metadata.tx_extbase_type.Tx_Tika_Tika','Tx_Tika_Tika');

$TCA['sys_file_metadata']['types']['Tx_Tika_Tika']['showitem'] = $TCA['sys_file_metadata']['types']['1']['showitem'];
$TCA['sys_file_metadata']['types']['Tx_Tika_Tika']['showitem'] .= ',--div--;LLL:EXT:tika/Resources/Private/Language/locallang_db.xlf:tx_tika_domain_model_tika,';
$TCA['sys_file_metadata']['types']['Tx_Tika_Tika']['showitem'] .= 'tika_content';

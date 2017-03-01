<?php
defined('TYPO3_MODE') or die();

require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('folder_cobj') . 'Classes/ContentObject/FolderContentObject.php');

// Register new content objects
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['FOLDER'] = \Smichaelsen\FolderCobj\ContentObject\FolderContentObject::class;

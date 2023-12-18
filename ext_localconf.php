<?php
defined('TYPO3') or die();

// Register new content objects
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['FOLDER'] = \Smichaelsen\FolderCobj\ContentObject\FolderContentObject::class;

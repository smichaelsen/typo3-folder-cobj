<?php

declare(strict_types=1);

namespace Smichaelsen\FolderCobj\ContentObject;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class FolderContentObject extends AbstractContentObject
{
    public function render($conf = []): string
    {
        $content = '';
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $cObj->setParent($this->cObj->data, $this->cObj->currentRecord);
        $this->cObj->currentRecordNumber = 0;
        foreach ($this->loadFolders($conf) as $folderRecord) {
            $cObj->start($folderRecord, 'pages');
            $content .= $cObj->cObjGetSingle($conf['renderObj'], $conf['renderObj.']);
        }
        if (!empty($conf['stdWrap.'])) {
            $cObj->start($this->getTyposcriptFrontendController()->cObj->data, 'pages');
            $content = $cObj->stdWrap($content, $conf['stdWrap.']);
        }
        return $content;
    }

    protected function loadFolders(array $conf): \Generator
    {
        $conf = $this->resolveStdWrapProperties(
            $conf,
            [
                'containsModule',
                'recursive',
                'restrictToRootPage',
                'doktypes',
                'limit',
            ]
        );

        $doktypes = GeneralUtility::intExplode(',', $conf['doktypes'] ?: PageRepository::DOKTYPE_SYSFOLDER);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');

        if ($conf['limit']) {
            $queryBuilder->setMaxResults((int)$conf['limit']);
        }

        $constraints = [
            $queryBuilder->expr()->in('doktype', $queryBuilder->createNamedParameter($doktypes, Connection::PARAM_INT_ARRAY)),
        ];
        if ($conf['containsModule']) {
            $constraints[] = $queryBuilder->expr()->eq('module', $queryBuilder->createNamedParameter($conf['containsModule'], \PDO::PARAM_STR));
        }
        if ($conf['restrictToRootPage']) {
            $rootPage = (int)$this->cObj->getData('leveluid:0');
            $pidList = [$rootPage];
            if ((int)$conf['recursive'] > 0) {
                $pidList = array_merge(
                    GeneralUtility::intExplode(',', $this->cObj->getTreeList($rootPage, $conf['recursive'])),
                    $pidList
                );
            }
            $constraints[] = $queryBuilder->expr()->in('pid', $queryBuilder->createNamedParameter($pidList, Connection::PARAM_INT_ARRAY));
        }

        $result = $queryBuilder->select('*')->from('pages')->where(...$constraints)->execute();
        while ($folderRecord = $result->fetchAssociative()) {
            yield $folderRecord;
        }
    }

    protected function resolveStdWrapProperties(array $conf, array $propertyNames): array
    {
        foreach ($propertyNames as $propertyName) {
            $conf[$propertyName] = trim(
                isset($conf[$propertyName . '.'])
                ? $this->cObj->stdWrap($conf[$propertyName] ?? null, $conf[$propertyName . '.'])
                : $conf[$propertyName] ?? ''
            );
            if ($conf[$propertyName] === '') {
                unset($conf[$propertyName]);
            }
            if (isset($conf[$propertyName . '.'])) {
                // stdWrapping already done, so remove the sub-array
                unset($conf[$propertyName . '.']);
            }
        }
        return $conf;
    }

    protected function getTyposcriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }
}

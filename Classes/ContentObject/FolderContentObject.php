<?php
namespace Smichaelsen\FolderCobj\ContentObject;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Typo3DbLegacy\Database\DatabaseConnection;

class FolderContentObject extends AbstractContentObject
{

    /**
     * Renders the content object.
     *
     * @param array $conf
     * @return string
     */
    public function render($conf = [])
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

    /**
     * @param array $conf
     * @return \Generator
     */
    protected function loadFolders(array $conf)
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $conf = $this->resolveStdWrapProperties(
            $conf,
            [
                'containsModule',
                'recursive',
                'restrictToRootPage',
                'doktypes',
                'limit'
            ]
        );
        if (!empty($conf['doktypes'] ?? null)) {
            $doktypes = \implode(',', GeneralUtility::intExplode(',', $conf['doktypes']));
            $constraints = [
                'pages.doktype IN (' . $doktypes . ')',
            ];
        } else {
            $constraints = [
                'pages.doktype = ' . PageRepository::DOKTYPE_SYSFOLDER,
            ];
        }

        if ($conf['containsModule'] ?? false) {
            $constraints[] = 'pages.module = ' . $this->getDatabaseConnection()->fullQuoteStr($conf['containsModule'], 'pages');
        }
        if ($conf['restrictToRootPage'] ?? false) {
            $rootPage = (int) $this->cObj->getData('leveluid:0');
            $pidList = [$rootPage];
            if ((int)$conf['recursive'] > 0) {
                $pidList = array_merge(
                    GeneralUtility::intExplode(',', $this->cObj->getTreeList($rootPage, $conf['recursive'])),
                    $pidList
                );
            }
            $constraints[] = 'pages.pid IN (' . join(',', $pidList) . ')';
        }
        $where = join(' AND ', $constraints) . $pageRepository->enableFields('pages');
        if (empty($conf['limit'])) {
            $limit = '';
        } else {
            $limit = $conf['limit'];
        }
        $res = $this->getDatabaseConnection()->exec_SELECTquery('*', 'pages', $where, '', '', $limit);
        while ($folderRecord = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
            yield $folderRecord;
        }
    }

    protected function resolveStdWrapProperties(array $conf, array $propertyNames)
    {
        foreach ($propertyNames as $propertyName) {
            $conf[$propertyName] = trim(isset($conf[$propertyName . '.'])
                ? $this->cObj->stdWrap($conf[$propertyName] ?? null, $conf[$propertyName . '.'])
                : $conf[$propertyName] ?? null
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

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTyposcriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}

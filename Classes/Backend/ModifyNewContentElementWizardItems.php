<?php

declare(strict_types=1);

namespace TYPO3\CMS\ContentBlocks\Backend;

/*
 * This file is part of TYPO3 CMS-based extension "container" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\Event\ModifyNewContentElementWizardItemsEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

class ModifyNewContentElementWizardItems
{
    #[AsEventListener('evoweb/sitepackage/add-foreign-table-parent-uid-to-new-content-element-wizard-items')]
    public function __invoke(ModifyNewContentElementWizardItemsEvent $event): void
    {
        $parent = $this->getParentIdFromRequest();
        if ($parent !== null) {
            $wizardItems = $event->getWizardItems();
            foreach ($wizardItems as $key => $wizardItem) {
                $wizardItems[$key]['defaultValues']['foreign_table_parent_uid'] = $parent;
            }
            $event->setWizardItems($wizardItems);
        }
    }

    protected function getParentIdFromRequest(): ?int
    {
        $request = $this->getServerRequest();
        if ($request === null) {
            return null;
        }
        $queryParams = $request->getQueryParams();
        if (isset($queryParams['foreign_table_parent_uid']) && (int)$queryParams['foreign_table_parent_uid'] > 0) {
            return (int)$queryParams['foreign_table_parent_uid'];
        }
        return null;
    }

    protected function getServerRequest(): ?ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? null;
    }
}

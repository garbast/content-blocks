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
    #[AsEventListener('content-blocksadd-foreign-table-parent-uid-to-new-content-element-wizard-items')]
    public function __invoke(ModifyNewContentElementWizardItemsEvent $event): void
    {
        $foreignTableParentUid = $this->getParentIdFromRequest($event->getRequest());
        if ($foreignTableParentUid > 0) {
            $wizardItems = $event->getWizardItems();
            foreach ($wizardItems as $key => $wizardItem) {
                $wizardItems[$key]['defaultValues']['foreign_table_parent_uid'] = $foreignTableParentUid;
            }
            $event->setWizardItems($wizardItems);
        }
    }

    protected function getParentIdFromRequest(ServerRequestInterface $request): int
    {
        return (int)($request->getQueryParams()['foreign_table_parent_uid'] ?? 0);
    }
}

<?php

declare(strict_types=1);

namespace TYPO3\CMS\ContentBlocks\ViewHelpers\Link;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\ContentBlocks\DataProcessing\ContentBlockData;
use TYPO3\CMS\ContentBlocks\Definition\ButtonDefinition;

#[Autoconfigure(public: true)]
class NewContentBeforeChildUrlViewHelper extends AbstractNewContentViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('container', ContentBlockData::class, 'Container element', true);
        $this->registerArgument('identifier', 'string', 'identifier', true);
        $this->registerArgument('record', ContentBlockData::class, 'Record');
    }

    public function render(): ButtonDefinition
    {
        /** @var ContentBlockData $container */
        $container = $this->arguments['container'];
        /** @var string $identifier */
        $identifier = $this->arguments['identifier'];
        /** @var ContentBlockData $record */
        $record = $this->arguments['record'];

        $columnNumber = 0;
        $defVals = $this->getDefValsIfOneSpecificContentTypeAllowed($container, $identifier);
        $target = -($record->getUid());
        if ($defVals !== null) {
            $onlyOneContentTypeAllowed = true;
            $newContentUrl = $this->getNewContentEditUrl($container, $columnNumber, $target, $defVals);
        } else {
            $onlyOneContentTypeAllowed = false;
            $newContentUrl = $this->getNewContentWizardUrl($container, $columnNumber, $target);
        }

        return new ButtonDefinition($newContentUrl, $onlyOneContentTypeAllowed);
    }
}

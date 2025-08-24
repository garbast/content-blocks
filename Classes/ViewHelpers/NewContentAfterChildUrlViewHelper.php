<?php

declare(strict_types=1);

namespace TYPO3\CMS\ContentBlocks\ViewHelpers;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\ContentBlocks\DataProcessing\ContentBlockData;
use TYPO3\CMS\ContentBlocks\Definition\ButtonDefinition;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\ContentBlocks\FieldType\CollectionFieldType;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

#[Autoconfigure(public: true)]
class NewContentAfterChildUrlViewHelper extends AbstractViewHelper
{
    protected const string TABLE_NAME = 'tt_content';

    public function __construct(
        private readonly TcaSchemaFactory $tcaSchemaFactory,
        private readonly TableDefinitionCollection $tableDefinitionCollection,
        private readonly UriBuilder $uriBuilder,
    ) {
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('container', ContentBlockData::class, 'Container element', true);
        $this->registerArgument('identifier', 'string', 'identifier', true);
        $this->registerArgument('record', GridColumnItem::class, 'Record', true);
    }

    public function render(): ButtonDefinition
    {
        /** @var ContentBlockData $container */
        $container = $this->arguments['container'];
        /** @var string $identifier */
        $identifier = $this->arguments['identifier'];
        /** @var GridColumnItem $record */
        $record = $this->arguments['record'];

        $columnNumber = 0;
        $defVals = $this->getDefValsIfOneSpecificContentTypeAllowed($container, $identifier);
        if ($defVals !== null) {
            $onlyOneContentTypeAllowed = true;
            $newContentUrl = $this->getNewContentEditUrl($container, $columnNumber, -($record->getRecord()['uid'] ?? 0), $defVals);
        } else {
            $onlyOneContentTypeAllowed = false;
            $newContentUrl = $this->getNewContentWizardUrl($container, $columnNumber, -($record->getRecord()['uid'] ?? 0));
        }

        return new ButtonDefinition($newContentUrl, $onlyOneContentTypeAllowed);
    }

    protected function getDefValsIfOneSpecificContentTypeAllowed(ContentBlockData $container, string $identifier): ?array
    {
        $inlineParentTableName = $container->getMainType();
        if (!$this->tableDefinitionCollection->hasTable($inlineParentTableName)) {
            return null;
        }
        $parentTableDefinition = $this->tableDefinitionCollection->getTable($inlineParentTableName);
        $definitionByIdentifier = null;
        foreach ($parentTableDefinition->tcaFieldDefinitionCollection as $definition) {
            if ($definition->identifier === $identifier) {
                $definitionByIdentifier = $definition;
            }
        }
        if ($definitionByIdentifier === null) {
            return null;
        }
        $inlineParentFieldName = $definitionByIdentifier->uniqueIdentifier;
        $fieldDefinition = $parentTableDefinition->tcaFieldDefinitionCollection->getField($inlineParentFieldName);
        if ($fieldDefinition->fieldType instanceof CollectionFieldType === false) {
            return null;
        }
        $allowedRecordTypes = $fieldDefinition->fieldType->getAllowedRecordTypes();

        if (count($allowedRecordTypes) === 1) {
            return ['CType' => $allowedRecordTypes[0]];
        }
        return null;
    }

    protected function getNewContentEditUrl(ContentBlockData $container, int $columnNumber, int $target, array $defVals): string
    {
        $ttContentDefVals = array_merge($defVals, [
            'colPos' => $columnNumber,
            'sys_language_uid' => $container->getComputedProperties()->getLocalizedUid(),
            'foreign_table_parent_uid' => $this->getLiveUid($container),
        ]);
        $urlParameters = [
            'edit' => [
                'tt_content' => [
                    $target => 'new',
                ],
            ],
            'defVals' => [
                'tt_content' => $ttContentDefVals,
            ],
            'returnUrl' => $this->getReturnUrl(),
        ];
        return (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
    }

    protected function getNewContentWizardUrl(ContentBlockData $container, int $columnNumber, int $uidPid): string
    {
        $urlParameters = [
            'id' => $container->getPid(),
            'sys_language_uid' => $container->getLanguageId(),
            'colPos' => $columnNumber,
            'uid_pid' => $uidPid,
            'foreign_table_parent_uid' => $this->getLiveUid($container),
            'returnUrl' => $this->getReturnUrl(),
        ];
        return (string)$this->uriBuilder->buildUriFromRoute('new_content_element_wizard', $urlParameters);
    }

    protected function getLiveUid(ContentBlockData $container): int
    {
        $uid = $container->getUid() ?? 0;
        if (
            $this->tcaSchemaFactory->has(self::TABLE_NAME)
            && $this->tcaSchemaFactory->get(self::TABLE_NAME)->hasCapability(TcaSchemaCapability::Workspace)
            && (int)($container->getRawRecord()->get('t3ver_oid') ?? 0) > 0
        ) {
            $uid = $container->getRawRecord()->get('t3ver_oid');
        }
        return $uid;
    }

    protected function getReturnUrl(): string
    {
        return (string)$this->getRequest()?->getAttribute('normalizedParams')->getRequestUri();
    }

    protected function getRequest(): ?ServerRequestInterface
    {
        if (!$this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
            return null;
        }
        return $this->renderingContext->getAttribute(ServerRequestInterface::class);
    }
}

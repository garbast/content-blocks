<?php

declare(strict_types=1);

namespace TYPO3\CMS\ContentBlocks\ViewHelpers\Link;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\ContentBlocks\DataProcessing\ContentBlockData;
use TYPO3\CMS\ContentBlocks\Definition\TableDefinitionCollection;
use TYPO3\CMS\ContentBlocks\FieldType\CollectionFieldType;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class AbstractNewContentViewHelper extends AbstractViewHelper
{
    protected const string TABLE_NAME = 'tt_content';

    public function __construct(
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly TableDefinitionCollection $tableDefinitionCollection,
        protected readonly UriBuilder $uriBuilder,
    ) {
    }

    protected function getDefValsIfOneSpecificContentTypeAllowed(ContentBlockData $container, string $identifier): ?array
    {
        $inlineParentTableName = $container->getMainType();
        $containerRecordType = $container->getRecordType();

        if (!$this->tableDefinitionCollection->hasTable($inlineParentTableName)) {
            return null;
        }
        $containerTableDefinition = $this->tableDefinitionCollection->getTable($inlineParentTableName);

        if ($containerTableDefinition->contentTypeDefinitionCollection->hasType($containerRecordType)) {
            $containerTypeDefinition = $containerTableDefinition->contentTypeDefinitionCollection
                ->getType($containerRecordType);
            $overriddenFieldDefinition = null;
            foreach ($containerTypeDefinition->getOverrideColumns() as $overrideColumn) {
                if ($overrideColumn->identifier === $identifier) {
                    $overriddenFieldDefinition = $overrideColumn;
                }
            }
            $allowedRecordTypes = $overriddenFieldDefinition ?
                $overriddenFieldDefinition->fieldType->getAllowedRecordTypes()
                : [];
            if (count($allowedRecordTypes) === 1) {
                return ['CType' => array_values($allowedRecordTypes)[0]];
            }
        }

        $definitionByIdentifier = null;
        foreach ($containerTableDefinition->tcaFieldDefinitionCollection as $definition) {
            if ($definition->identifier === $identifier) {
                $definitionByIdentifier = $definition;
            }
        }
        if ($definitionByIdentifier === null) {
            return null;
        }
        $inlineParentFieldName = $definitionByIdentifier->uniqueIdentifier;
        $fieldDefinition = $containerTableDefinition->tcaFieldDefinitionCollection->getField($inlineParentFieldName);
        if ($fieldDefinition->fieldType instanceof CollectionFieldType === false) {
            return null;
        }
        $allowedRecordTypes = $fieldDefinition->fieldType->getAllowedRecordTypes();
        if (count($allowedRecordTypes) === 1) {
            return ['CType' => array_values($allowedRecordTypes)[0]];
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

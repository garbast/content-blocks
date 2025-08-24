<?php

declare(strict_types=1);

namespace TYPO3\CMS\ContentBlocks\Definition;

final readonly class ButtonDefinition
{
    public function __construct(
        public string $newContentUrl,
        public bool $onlyOneContentTypeAllowed,
    ) { }
}

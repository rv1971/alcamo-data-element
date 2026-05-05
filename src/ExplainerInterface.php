<?php

namespace alcamo\data_element;

use alcamo\rdf_literal\{Lang, LiteralInterface};

/**
 * @brief Class that explains a data element instance
 *
 * @date Last reviewed 2026-05-05
 */
interface ExplainerInterface
{
    public function getLang(): ?Lang;

    public function getDataElementLabel(
        DataElementInterface $dataElement
    ): string;

    public function getLiteralLabel(LiteralInterface $literal): ?string;

    public function explainAsMarkdownText(
        DataElementInstanceInterface $instance
    ): MarkdownText;
}

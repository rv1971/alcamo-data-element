<?php

namespace alcamo\data_element;

use alcamo\markdown\MarkdownText;
use alcamo\rdf_literal\{Lang, LiteralInterface};

/**
 * @brief Class that explains a data element instance
 *
 * @date Last reviewed 2026-05-05
 */
interface ExplainerInterface
{
    public function getLang(): ?Lang;

    public function getFlags(): int;

    /// Data element label, taking into account language and flags
    public function getDataElementLabel(
        DataElementInterface $dataElement
    ): ?string;

    /// Literal label, e.g. if the literal is an enumerator
    public function getLiteralLabel(LiteralInterface $literal): ?string;

    /// Create markdown text that explains the data element instance
    public function explainAsMarkdownText(
        DeInstanceInterface $deInstance
    ): MarkdownText;
}

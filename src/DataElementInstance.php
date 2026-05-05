<?php

namespace alcamo\data_element;

use alcamo\rdf_literal\LiteralInterface;

/**
 * @brief Data element instance
 *
 * @date Last reviewed 2026-05-05
 */
class DataElementInstance implements DataElementInstanceInterface
{
    private $dataElement_; ///< DataElementInterface
    private $literal_;     ///< LiteralInterface

    public function __construct(
        DataElementInterface $dataElement,
        LiteralInterface $literal
    ) {
        $this->dataElement_ = $dataElement;
        $this->literal_ = $literal;
    }

    public function getDataElement(): DataElementInterface
    {
        return $this->dataElement_;
    }

    public function getLiteral(): LiteralInterface
    {
        return $this->literal_;
    }
}

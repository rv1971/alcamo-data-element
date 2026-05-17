<?php

namespace alcamo\data_element;

use alcamo\rdf_literal\LiteralInterface;

/**
 * @brief Data element instance
 *
 * @date Last reviewed 2026-05-05
 */
class DeInstance implements DeInstanceInterface
{
    private $dataElement_; ///< DataElementInterface
    private $literal_;     ///< LiteralInterface

    public function __construct(
        DataElementInterface $dataElement,
        LiteralInterface $literal
    ) {
        $this->dataElement_ = clone $dataElement;
        $this->literal_ = clone $literal;
    }

    public function __clone()
    {
        $this->dataElement_ = clone $this->dataElement_;
        $this->literal_ = clone $this->literal_;
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

<?php

namespace alcamo\data_element;

use alcamo\rdf_literal\LiteralInterface;

/**
 * @brief Instance of a data element with a literal as its value
 *
 * @date Last reviewed 2026-05-04
 */
interface DataElementInstanceInterface
{
    public function getDataElement(): DataElementInterface;

    public function getLiteral(): LiteralInterface;
}

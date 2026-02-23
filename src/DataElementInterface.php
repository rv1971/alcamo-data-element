<?php

namespace alcamo\data_element;

use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\rdfa\{HavingRdfaDataInterface, LiteralInterface};

interface DataElementInterface extends HavingRdfaDataInterface
{
    public function getDatatype(): SimpleTypeInterface;

    public function createLiteral($value = null): LiteralInterface;
}

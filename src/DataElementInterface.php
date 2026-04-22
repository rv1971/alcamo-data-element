<?php

/**
 * @namespace alcamo::data_element
 *
 * @brief Modelling and (de)serialization of data elements
 */

namespace alcamo\data_element;

use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\rdfa\HavingRdfaDataInterface;

/**
 * @brief Data element with XSD type and metadata
 *
 * @date Last reviewed 2026-04-21
 */
interface DataElementInterface extends HavingRdfaDataInterface
{
    public function getDatatype(): SimpleTypeInterface;
}

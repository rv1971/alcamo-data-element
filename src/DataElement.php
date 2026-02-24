<?php

namespace alcamo\data_element;

use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\rdfa\RdfaData;

/**
 * @brief Data element with XSD type and metadata
 *
 * @date Last reviewed 2026-02-24
 */
class DataElement implements DataElementInterface
{
    private $datatype_; ///< SimpleTypeInterface
    private $rdfaData_; ///< RdfaData

    /**
     * @param $datatype XSD datatype of the data element
     *
     * @param RdfaData|array RDFa data about the data element
     */
    public function __construct(
        SimpleTypeInterface $datatype,
        $rdfaData = null
    ) {
        $this->datatype_ = $datatype;

        $this->rdfaData_ = isset($rdfaData)
            ? (clone $datatype->getRdfaData())->replace(
                $rdfaData instanceof RdfaData
                    ? $rdfaData
                    : RdfaData::newFromIterable($rdfaData)
            )
            : clone $datatype->getRdfaData();
    }

    public function getDatatype(): SimpleTypeInterface
    {
        return $this->datatype_;
    }

    public function getRdfaData(): ?RdfaData
    {
        return $this->rdfaData_;
    }
}

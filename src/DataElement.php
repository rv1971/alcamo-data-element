<?php

namespace alcamo\data_element;

use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\rdfa\{AbstractRdfaData, HavingLabelInterface, ImmutableRdfaData};

/**
 * @brief Data element with XSD type and metadata
 *
 * @invariant Immutable class.
 *
 * @date Last reviewed 2026-05-04
 */
class DataElement implements DataElementInterface
{
    private $datatype_; ///< SimpleTypeInterface
    private $rdfaData_; ///< ImmutableRdfaData

    /**
     * @param $datatype XSD datatype of the data element
     *
     * @param ImmutableRdfaData|RdfaData|array|null RDFa data about the data
     * element
     */
    public function __construct(
        SimpleTypeInterface $datatype,
        $rdfaData = null
    ) {
        $this->datatype_ = $datatype;

        $this->rdfaData_ = isset($rdfaData)
            ? ($datatype->getRdfaData()->toMutable()
               ->replace(ImmutableRdfaData::newFromData($rdfaData))
               ->toImmutable())
            : $datatype->getRdfaData();
    }

    public function getDatatype(): SimpleTypeInterface
    {
        return $this->datatype_;
    }

    public function getRdfaData(): ImmutableRdfaData
    {
        return $this->rdfaData_;
    }

    public function getLabel($lang = null, ?int $flags = null): string
    {
        return $this->rdfaData_->getLabel($lang, $flags);
    }
}

<?php

namespace alcamo\data_element;

use alcamo\binary_data\ImmutableBinaryString;
use alcamo\dom\schema\component\EnumerationTypeInterface;
use alcamo\markdown\MarkdownText;
use alcamo\rdf_literal\{Lang, LiteralInterface};
use alcamo\rdfa\HavingLabelInterface;
use Ds\Set;

/**
 * @brief Class that explains a data element instance
 *
 * @date Last reviewed 2026-05-05
 */
class Explainer implements ExplainerInterface
{
    /// Whether to show the extended name of type
    public const SHOW_DATATYPE_XNAME = 0x100;

    /// Whether to show dc:identifier
    public const SHOW_IDENTIFIER = 0x200;

    public const DEFAULT_FLAGS =
        HavingLabelInterface::FALLBACK_TO_DIFFERENT_LANG;

    protected $lang_;        ///< ?Lang
    protected $flags_;       ///< int
    protected $deWorkbench_; ///< DeWorkbench

    public function __construct(
        $lang = null,
        ?int $flags = null,
        ?DeWorkbench $deWorkbench = null
    ) {
        if (isset($lang)) {
            $this->lang_ =
                $lang instanceof Lang ? $lang : Lang::newFromString($lang);
        }

        $this->flags_ = $flags ?? static::DEFAULT_FLAGS;

        $this->deWorkbench_ = $deWorkbench ?? DeWorkbench::getMainInstance();
    }

    public function getLang(): ?Lang
    {
        return $this->lang_;
    }

    public function getFlags(): int
    {
        return $this->flags_;
    }

    public function getDeWorkbench(): DeWorkbench
    {
        return $this->deWorkbench_;
    }

    public function getDataElementLabel(
        DataElementInterface $dataElement
    ): ?string {
        return $dataElement->getLabel($this->lang_, $this->flags_);
    }

    public function getDataTypeXName(
        DataElementInterface $dataElement
    ): string {
        return 'Type: ' . $dataElement->getDatatype()->getXName();
    }

    public function getDataElementIdentifier(
        DataElementInterface $dataElement
    ): ?string {
        return isset($dataElement->getRdfaData()['dc:identifier'])
            ? ('ID: '
               . $dataElement->getRdfaData()
               ->getFirstValueOrUri('dc:identifier'))
            : null;
    }

    /** The label for the literal value taken based on the literal data type
     *  may be richer than that from the datatype type since it is possible
     *  that the latter is an enumeration while the former is not.
     *
     * If the type is an enumeration or has a link to an enumeration type,
     * look for a label in the relevant enumerator.
     */
    public function getLiteralLabel(
        LiteralInterface $literal
    ): ?string {
        $datatype = $this->deWorkbench_->validateLiteral($literal);

        if ($datatype instanceof EnumerationTypeInterface) {
            if (isset($datatype->getEnumerators()[(string)$literal])) {
                return $datatype->getEnumerators()[(string)$literal]
                ->getRdfaData()->getLabel($this->lang_, $this->flags_);
            } else {
                return "unknown enumerator $literal";
            }
        }

        $enumerationType = $datatype->getEnumerationType();

        if (isset($enumerationType)) {
            $enumerator =
                $enumerationType->getEnumerators()[(string)$literal] ?? null;

            if (isset($enumerator)) {
                return $enumerator->getRdfaData()
                    ->getLabel($this->lang_, $this->flags_);
            }
        }

        return null;
    }

    public function explainAsMarkdownText(
        DeInstanceInterface $deInstance
    ): MarkdownText {
        $result = new MarkdownText();

        $dataElement = $deInstance->getDataElement();

        /** Use the label taken from the data element, which may be richer
         *  than that from the literal type since the former may have
         *  additional RDFa data. */
        $dataElementLabel = $this->getDataElementLabel($dataElement);

        if (!$deInstance->hasChildren()) {
            $literalLabel = $this->getLiteralLabel($deInstance->getLiteral());
        }

        if (isset($literalLabel)) {
            $result->append("$dataElementLabel: $literalLabel");
        } else {
            $result->append($dataElementLabel);
        }

        if ($this->flags_ & self::SHOW_DATATYPE_XNAME) {
            $result->append($this->getDataTypeXName($dataElement));
        }

        if ($this->flags_ & self::SHOW_IDENTIFIER) {
            $result->append($this->getDataElementIdentifier($dataElement));
        }

        if (isset($literalLabel)) {
            return $result;
        }

        if ($deInstance->hasChildren()) {
            $i = 1;
            foreach ($deInstance->getChildren() as $item) {
                $result->append(
                    $this->explainAsMarkdownText($item)->toOrderedListItem($i++)
                );
            }

            return $result;
        }

        $datatype = $dataElement->getDatatype();

        /** If the data type is derived from `xsd:hexBinary` and has a link to
         *  an enumeration type, the enumerators in the latter are considered
         *  to represent bits or sets of bits that may be present in a data
         *  element instance. For each enumerator, if all of its 1-bits are
         *  set in the instance, it is listed as machting enumerator in the
         *  output.
         *
         * The enumerators are tested in XSD document order. An enumerator is
         * discarded when it has bits in common with an already tested
         * matching enumerator. For example, given the enumerators 08, 04, 03,
         * 02 and 01, in that order, for an instance value 0B, the
         * explanations of the enumerators 08 and 03 will be listed, but not
         * 02 and 01. This allows to document cases where a group of bits is
         * considered as a subfield having enumerators. */
        if (
            $datatype->getPrimitiveType()->getXName()->getLocalName()
                == 'hexBinary'
        ) {
            $enumerationType = $datatype->getEnumerationType();

            if (isset($enumerationType)) {
                $literalValue = $deInstance->getLiteral()->getValue();

                $usedValues = new Set();

                foreach (
                    $enumerationType->getEnumerators() as $value => $enumerator
                ) {
                    $value = ImmutableBinaryString::newFromHex($value);

                    foreach ($usedValues as $usedValue) {
                        if (!$value->bitwiseAnd($usedValue)->isZero()) {
                            continue 2;
                        }
                    }

                    if ($literalValue->bitwiseAnd($value) == $value) {
                        $result->append(
                            '* ' . $enumerator->getRdfaData()
                                ->getLabel($this->lang_, $this->flags_)
                        );

                        $usedValues->add($value);
                    }
                }
            }
        }

        return $result;
    }
}

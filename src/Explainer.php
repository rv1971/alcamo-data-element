<?php

namespace alcamo\data_element;

use alcamo\dom\schema\component\EnumerationTypeInterface;
use alcamo\rdf_literal\{Lang, LiteralInterface};
use alcamo\rdfa\HavingLabelInterface;

/**
 * @brief Class that explains a data element instance
 *
 * @date Last reviewed 2026-05-05
 */
class Explainer implements ExplainerInterface
{
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

    public function getDataElementLabel(
        DataElementInterface $dataElement
    ): ?string {
        return $dataElement->getLabel($this->lang_, $this->flags_);
    }

    /** The label for the literal value taken based on the literal data type
     *  may be richer than that from the datatype type since it is possible
     *  that the latter is an enumeration while the former is not. */
    public function getLiteralLabel(
        LiteralInterface $literal
    ): ?string {
        $datatype = $this->deWorkbench_->validateLiteral($literal);

        if ($datatype instanceof EnumerationTypeInterface) {
            return $datatype->getEnumerators()[(string)$literal]
            ->getRdfaData()->getLabel($this->lang_, $this->flags_);
        }

        return null;
    }

    public function explainAsMarkdownText(
        DeInstanceInterface $deInstance
    ): MarkdownText {
        $result = new MarkdownText();

        /** Use the label taken from the data element, which may be richer
         *  than that from the literal type since the former may have
         *  additional RDFa data. */
        $dataElementLabel =
            $this->getDataElementLabel($deInstance->getDataElement());

        if ($deInstance instanceof ConstructedDeInstance) {
            $result->appendLine($dataElementLabel);

            $i = 1;
            foreach ($deInstance as $item) {
                $result->appendMarkdownText(
                    $this->explainAsMarkdownText($item)->toOrderedListItem($i++)
                );
            }
        } else {
            $literalLabel = $this->getLiteralLabel($deInstance->getLiteral());

            $result->appendLine(
                isset($literalLabel)
                    ? "$dataElementLabel: $literalLabel"
                    : $dataElementLabel
            );
        }

        return $result;
    }
}

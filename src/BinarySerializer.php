<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\LiteralInterface;

/**
 * @brief (De)Serializer for binary data
 *
 * @date Last reviewed 2026-04-21
 */
class BinarySerializer extends AbstractSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        self::XSD_NS . ' hexBinary',
        self::XSD_NS . ' base64Binary'
    ];

    public static function newFromProps(object $props): SerializerInterface
    {
        return new static(
            $props->datatypeXName ?? null,
            $props->lengthRange ?? null,
            $props->flags ?? null,
            $props->literalWorkbench ?? null
        );
    }

    /**
     * @param $datatypeXName Datatype to use for deserialized literals
     * [default first item in SUPPORTED_DATATYPE_XNAMES]
     *
     * @param $lengthRange Allowed length of serialized data, in
     * encoding-dependent units (bytes or nibbles).
     *
     * @param $flags Bitwise-OR-combination of the constants in
     * alcamo::data_element::SerializerInterface.
     *
     * @param $literalWorkbench Workbench used in deserialize() and in
     * validateLiteralClass(). [default
     * alcamo::data_element::LiteralWorkbench::getMainInstance()]
     */
    public function __construct(
        ?string $datatypeXName = null,
        ?NonNegativeRange $lengthRange = null,
        ?int $flags = null,
        ?LiteralWorkbench $literalWorkbench = null
    ) {
        parent::__construct(
            $datatypeXName,
            $lengthRange,
            "\x00",
            STR_PAD_RIGHT,
            $flags,
            $literalWorkbench
        );
    }

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        /* getValue() must return BinaryString. */
        return $this->adjustOutputLength($literal->getValue()->getData());
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        $this->validateInputLength($input);

        return $this->literalWorkbench_->createLiteral(
            new BinaryString($input),
            $datatype ?? $this->datatype_
        );
    }
}

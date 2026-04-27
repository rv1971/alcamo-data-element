<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\{FourBitCharStringLiteral, LiteralInterface};
use alcamo\exception\InvalidEnumerator;

/**
 * @brief (De)Serializer for four-bit string data
 *
 * @date Last reviewed 2026-04-21
 */
class FourBitCharStringSerializer extends AbstractSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES =
        [ FourBitCharStringLiteral::DEFAULT_DATATYPE_XNAME ];

    public const ENCODING_TO_BITS = [
        'ASCII'    => 8,
        'FOUR-BIT' => 4
    ];

    public const ENCODING_TO_PAD_STRING = [
        'ASCII'    => ' ',
        'FOUR-BIT' => 'F'
    ];

    public static function newFromProps(object $props): SerializerInterface
    {
        return new static(
            $props->datatypeXName ?? null,
            $props->lengthRange ?? null,
            $props->flags ?? null,
            $props->encoding ?? null,
            $props->literalWorkbench ?? null
        );
    }

    /**
     * @param $datatypeXName Datatype for deserialized literals [default first
     * item in SUPPORTED_DATATYPE_XNAMES)
     *
     * @param $lengthRange Allowed length of serialized data, in
     * encoding-dependent units (bytes or nibbles).
     *
     * @param $flags Bitwise-OR-combination of the constants in
     * alcamo::data_element::SerializerInterface.
     *
     * @parm $encoding [default
     * alcamo::data_element::AbstractSerializerWithEncoding::DEFAULT_ENCODING]
     *
     * @param $literalWorkbench Workbench used in deserialize() and in
     * validateLiteralClass(). [default
     * alcamo::data_element::LiteralWorkbench::getMainInstance()]
     */
    public function __construct(
        ?string $datatypeXName = null,
        ?NonNegativeRange $lengthRange = null,
        ?int $flags = null,
        ?string $encoding = null,
        ?FactoryGroup $literalWorkbench = null
    ) {
        $padString = static::ENCODING_TO_PAD_STRING[
            $encoding ?? static::DEFAULT_ENCODING
        ] ?? null;

        if (!isset($padString)) {
            throw (new InvalidEnumerator())->setMessageContext(
                [
                    'value' => $encoding ?? static::DEFAULT_ENCODING,
                    'expectedOneOf' =>
                        array_keys(static::ENCODING_TO_PAD_STRING)
                ]
            );
        }

        parent::__construct(
            $datatypeXName,
            $lengthRange,
            $padString,
            STR_PAD_RIGHT,
            $flags,
            $encoding,
            $literalWorkbench
        );
    }

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        switch ($this->encoding_) {
            case 'ASCII':
                return $this->adjustOutputLength($literal);

            case 'FOUR-BIT':
                return hex2bin(
                    $this->adjustOutputLength(
                        strtr($literal, ':;<=>?', 'ABCDEF')
                    )
                );
        }
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        switch ($this->encoding_) {
            case 'FOUR-BIT':
                $input = bin2hex($input);

                $this->validateInputLength($input);

                return $this->literalWorkbench_->createLiteral(
                    BinaryString::newFromHex($input)->toFourBitCharString(),
                    $datatype ?? $this->datatype_
                );

            default:
                $this->validateInputLength($input);

                /** Remove trailing spaces from input. */
                return $this->literalWorkbench_->createLiteral(
                    rtrim($input),
                    $datatype ?? $this->datatype_
                );
        }
    }
}

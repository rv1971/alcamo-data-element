<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\LiteralInterface;
use alcamo\exception\InvalidEnumerator;

/**
 * @brief (De)Serializer for integers
 *
 * @date Last reviewed 2026-04-21
 */
class IntegerSerializer extends AbstractSerializerWithEncoding
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        self::XSD_NS . ' integer',
        self::XSD_NS . ' boolean',
        self::XSD_NS . ' gDay',
        self::XSD_NS . ' gMonth',
        self::XSD_NS . ' gYear'
    ];

    public const ENCODING_TO_BITS = [
        'ASCII'      => 8,
        'BIG-ENDIAN' => 8,
        'EBCDIC'     => 8
    ];

    public const ENCODING_TO_PAD_STRING = [
        'ASCII'      => '0',
        'BIG-ENDIAN' => "\x00",
        'EBCDIC'     => "\x40"
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
            STR_PAD_LEFT,
            $flags,
            $encoding,
            $literalWorkbench
        );
    }

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        $value = $literal->toInt();

        $minLength = isset($this->lengthRange_)
            ? $this->lengthRange_->getMin()
            : 0;

        /* sprintf() is needed to put the padding 0s after a sign, if the
         * value is negative. adjustOutputLength() then only checks the
         * maximum length since the minimum length is already guaranteed. */
        switch ($this->encoding_) {
            case 'ASCII':
                return $this->adjustOutputLength(
                    sprintf("%0{$minLength}d", $value)
                );

            case 'BIG-ENDIAN':
                return $this->adjustOutputLength(
                    BinaryString::newFromInt($value, $minLength)->getData()
                );

            case 'EBCDIC':
                return $this->adjustOutputLength(
                    strtr(
                        sprintf("%0{$minLength}d", $value),
                        '-0123456789',
                        "\x60\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9"
                    )
                );
        }
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        $this->validateInputLength($input);

        switch ($this->encoding_) {
            case 'ASCII':
                $value = (int)$input;
                break;

            case 'BIG-ENDIAN':
                $value = (new BinaryString($input))
                    ->toInt($this->datatype_->isSigned());
                break;

            case 'EBCDIC':
                $value = (int)strtr(
                    $input,
                    "\x60\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9",
                    '-0123456789'
                );
                break;
        }

        return $this->literalWorkbench_
            ->createLiteral($value, $datatype ?? $this->datatype_);
    }
}

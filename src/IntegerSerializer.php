<?php

namespace alcamo\data_element;

use alcamo\binary_data\ImmutableBinaryString;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\rdf_literal\LiteralInterface;

/**
 * @brief (De)Serializer for integers
 *
 * @date Last reviewed 2026-04-21
 */
class IntegerSerializer extends AbstractSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        self::XSD_NS . ' integer',
        self::XSD_NS . ' boolean',
        self::XSD_NS . ' gDay',
        self::XSD_NS . ' gMonth',
        self::XSD_NS . ' gYear'
    ];

    public const ENCODINGS = [
        'ASCII'      => [ 8, '0',    STR_PAD_LEFT ],
        'BIG-ENDIAN' => [ 8, "\x00", STR_PAD_LEFT ],
        'EBCDIC'     => [ 8, "\x40", STR_PAD_LEFT ]
    ];

    public function serialize(
        LiteralInterface $literal,
        ?int $length = null
    ): string {
        $this->validateLiteralClass($literal);

        $value = $literal->toInt();

        $minLength = $length ?? (isset($this->lengthRange_)
                                 ? $this->lengthRange_->getMin()
                                 : 0);

        /* sprintf() is needed to put the padding 0s after a sign, if the
         * value is negative. adjustOutputLength() then only checks the
         * maximum length since the minimum length is already guaranteed in
         * all cases. */
        switch ($this->encodingParams_->getEncoding()) {
            case 'ASCII':
                return $this->adjustOutputLength(
                    sprintf("%0{$minLength}d", $value),
                    $length
                );

            case 'BIG-ENDIAN':
                return $this->adjustOutputLength(
                    ImmutableBinaryString::newFromInt($value, $minLength)
                        ->getData(),
                    $length
                );

            case 'EBCDIC':
                return $this->adjustOutputLength(
                    strtr(
                        sprintf("%0{$minLength}d", $value),
                        EncodingParams::ASCII_CHARS,
                        EncodingParams::EBCDIC_CHARS
                    ),
                    $length
                );
        }
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        $input = $this->preprocessInput($input);

        switch ($this->encodingParams_->getEncoding()) {
            case 'ASCII':
                $value = (int)$input;
                break;

            case 'BIG-ENDIAN':
                $value = (new ImmutableBinaryString($input))
                    ->toInt($this->datatype_->isSigned());
                break;

            case 'EBCDIC':
                $value = (int)strtr(
                    $input,
                    EncodingParams::EBCDIC_CHARS,
                    EncodingParams::ASCII_CHARS
                );
                break;
        }

        return $this->deWorkbench_
            ->createLiteral($value, $datatype ?? $this->datatype_);
    }

    public function dump(LiteralInterface $literal): string
    {
        return (new Dumper())
            ->dumpInt($literal->toInt(), $this->encodingParams_->getEncoding());
    }

    public function dedump(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        return $this->deWorkbench_->createLiteral(
            (new Dumper())->dedumpInt(
                $input,
                $this->encodingParams_->getEncoding(),
                ($datatype ?? $this->datatype_)->isSigned()
            ),
            $datatype ?? $this->datatype_
        );
    }
}

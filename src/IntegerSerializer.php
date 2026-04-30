<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\exception\SyntaxError;
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
        'ASCII'      => [ 8, ' ' ],
        'BIG-ENDIAN' => [ 8, "\x00" ],
        'DUMP'       => [ 8, '' ],
        'EBCDIC'     => [ 8, "\x40" ]
    ];

    public const PAD_TYPE = STR_PAD_LEFT;

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        if ($this->encoding_ == 'DUMP') {
            return $this->dump($literal);
        }

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

            case 'DUMP':
                return $this->dedump($input, $datatype);

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

    public function dump(LiteralInterface $literal): string
    {
        return $literal->toInt();
    }

    public function dedump(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        if (!is_numeric($input) || (int)$input != $input) {
            /** @throw alcamo::exception::SyntaxError on attempt to
             *  deserialize with DUMP encoding an input which is not
             *  an integer. */
            throw (new SyntaxError())->setMessageContext(
                [ 'inData' => $input ]
            );
        }

        return $this->literalWorkbench_
            ->createLiteral($input, $datatype ?? $this->datatype_);
    }
}

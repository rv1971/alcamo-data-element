<?php

namespace alcamo\data_element;

use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\exception\{SyntaxError, Unsupported};
use alcamo\rdf_literal\LiteralInterface;

/**
 * @brief (De)Serializer for string data
 *
 * @date Last reviewed 2026-04-21
 */
class StringSerializer extends AbstractSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        self::XSD_NS . ' string',
        self::XSD_NS . ' anyURI',
        self::XSD_NS . ' NOTATION',
        self::XSD_NS . ' QName'
    ];

    public const ENCODINGS = [
        'UTF-8' => [ 8, ' ' ], // default encoding
        'DUMP'  => [ 8, '' ],
        '*'     => [ 8, ' ' ]
    ];

    /// String encoding used internally
    public const INTERNAL_ENCODING = 'UTF-8';

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        if ($this->encoding_ == 'DUMP') {
            return $this->dump($literal);
        }

        if (static::INTERNAL_ENCODING == $this->encoding_) {
            return $this->adjustOutputLength($literal->getValue());
        }

        $value = $literal->getValue();

        /* Pad to minimum length in internal encoding before character set
         * conversion takes place, because output encoding might have a
         * different representation of the padding character. */
        if (isset($this->lengthRange_)) {
            $value = str_pad(
                $value,
                $this->lengthRange_->getMin(),
                $this->padString_,
                $this->padType_
            );
        }

        return $this->adjustOutputLength(
            iconv(static::INTERNAL_ENCODING, $this->encoding_, $value)
        );
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        if ($this->encoding_ == 'DUMP') {
            return $this->dedump($input, $datatype);
        }

        $this->validateInputLength($input);

        /** Remove trailing spaces from input. */
        return $this->literalWorkbench_->createLiteral(
            rtrim(
                static::INTERNAL_ENCODING == $this->encoding_
                    ? $input
                    : iconv($this->encoding_, static::INTERNAL_ENCODING, $input)
            ),
            $datatype ?? $this->datatype_
        );
    }

    public function dump(LiteralInterface $literal): string
    {
        if (strpos($literal, '"') !== false) {
            /** @throw alcamo::exception::Unsupported on attempt to dump a
             *  literal containing a double quote character. */
                throw (new Unsupported())->setMessageContext(
                    [
                        'feature'
                            => "dumping a literal containing a double quote",
                        'inData' => (string)$literal
                    ]
                );
        }

        return "\"$literal\"";
    }

    public function dedump(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        if (!preg_match('/^"[^"]*"$/', $input)) {
            /** @throw alcamo::exception::SyntaxError on attempt to
             *  deserialize with DUMP encoding an input which is not a string
             *  without double quotes enclosed in double quotes. */
            throw (new SyntaxError())->setMessageContext(
                [ 'inData' => $input ]
            );
        }

        return $this->literalWorkbench_->createLiteral(
            trim($input, '"'),
            $datatype ?? $this->datatype_
        );
    }
}

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
        '*'     => [ 8, ' ' ]
    ];

    /// String encoding used internally
    public const INTERNAL_ENCODING = 'UTF-8';

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

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

    /** @copydoc alcamo::data_element::SerializerInterface::dump() */
    public function dump(LiteralInterface $literal): string
    {
        $value = (string)$literal;

        if (strpos($value, '"') !== false) {
            /** @throw alcamo::exception::Unsupported on attempt to dump a
             *  literal containing a double quote character. */
                throw (new Unsupported())->setMessageContext(
                    [
                        'feature'
                            => "dumping a literal containing a double quote",
                        'inData' => (string)$value
                    ]
                );
        }

        switch (true) {
            /** If the literal has more than three characters and consists of
             *  a repetition of the same character, represent it as a
             *  repetition, e.g. `AAAA` as `4 * "A"`. */
            case strlen($value) > 3
                && $value == str_repeat($value[0], strlen($value)):
                return strlen($value) . " * \"{$value[0]}\"";

            /** If the literal has more than six characters and consists of a
             *  repetition of the same two characters, represent it as a
             *  repetition, e.g. `ABABABAB` as `4 * "AB"`. */
            case strlen($value) > 6 && $value == str_repeat(
                $value[0] . $value[1],
                strlen($value) >> 1
            ):
                return (strlen($value) >> 1) . " * \"{$value[0]}{$value[1]}\"";

            default:
                return "\"$value\"";
        }
    }

    public function dedump(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        if (preg_match('/^(\d+)\s*\*\s*"([^"]+)"$/', $input, $matches)) {
            return $this->literalWorkbench_->createLiteral(
                str_repeat($matches[2], $matches[1]),
                $datatype ?? $this->datatype_
            );
        }

        if (!preg_match('/^"[^"]*"$/', $input)) {
            /** @throw alcamo::exception::SyntaxError on attempt to dedump an
             *  input which is neither a repetition as explained above nor a
             *  string without double quotes enclosed in double quotes. */
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

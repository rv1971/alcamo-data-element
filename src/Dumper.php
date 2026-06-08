<?php

namespace alcamo\data_element;

use alcamo\binary_data\{AbstractBinaryString, ImmutableBinaryString};
use alcamo\exception\{SyntaxError, Unsupported};

class Dumper
{
    public function dumpString(string $value, ?string $encoding = null): string
    {
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

        switch ($encoding) {
            case 'BCD':
                return $value;

            case 'COMPRESSED-BCD':
            case 'FOUR-BIT':
                if (strlen($value) & 1) {
                    $value .= '?';
                }

                return $this->dumpBinary(
                    ImmutableBinaryString::newFromHex(
                        strtr($value, ':;<=>?', 'ABCDEF')
                    )
                );

            case 'EBCDIC':
                return $this->dumpBinary(
                    new ImmutableBinaryString(
                        strtr(
                            $value,
                            EncodingParams::ASCII_CHARS,
                            EncodingParams::EBCDIC_CHARS
                        )
                    )
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

    public function dedumpString(
        string $input,
        ?string $encoding = null
    ): string {
        switch ($encoding) {
            case 'BCD':
                return $input;

            case 'COMPRESSED-BCD':
                return rtrim(
                    strtr($this->dedumpBinary($input), 'ABCDEF', ':;<=>?'),
                    '?'
                );

            case 'EBCDIC':
                return strtr(
                    $this->dedumpBinary($input)->getData(),
                    EncodingParams::EBCDIC_CHARS,
                    EncodingParams::ASCII_CHARS
                );

            case 'FOUR-BIT':
                return strtr($this->dedumpBinary($input), 'ABCDEF', ':;<=>?');
        }

        if (preg_match('/^(\d+)\s*\*\s*"([^"]+)"$/', $input, $matches)) {
            return str_repeat($matches[2], $matches[1]);
        }

        if (!preg_match('/^"[^"]*"$/', $input)) {
            /** @throw alcamo::exception::SyntaxError on attempt to dedump an
             *  input which is neither a repetition as explained above nor a
             *  string without double quotes enclosed in double quotes. */
            throw (new SyntaxError())->setMessageContext(
                [ 'inData' => $input ]
            );
        }

        return trim($input, '"');
    }

    public function dumpBinary(AbstractBinaryString $value): string
    {
        $data = $value->getData();

        switch (true) {
            /** If the data have more than three bytes and consist of
             *  a repetition of the same byte, represent it as a
             *  repetition, e.g. `01010101` as `4 * '01'`. */
            case strlen($data) > 3
                && $data == str_repeat($data[0], strlen($data)):
                return strlen($data)
                    . " * '" . strtoupper(bin2hex($data[0])) . "'";

            /** If the data have have more than four bytes and consist of a
             *  repetition of the same two bytes, represent it as a
             *  repetition, e.g. `ABCDABCDABCD` as `3 * 'ABCD'`. */
            case strlen($data) > 4 && $data == str_repeat(
                $data[0] . $data[1],
                strlen($data) >> 1
            ):
                return (strlen($data) >> 1) . " * '"
                    . strtoupper(bin2hex($data[0] . $data[1]))
                    . "'";

            default:
                return "'$value'";
        }
    }

    public function dedumpBinary(string $input): ImmutableBinaryString
    {
        if (preg_match('/^(\d+)\s*\*\s*\'([^\']+)\'$/', $input, $matches)) {
            return ImmutableBinaryString::newFromHex(
                str_repeat($matches[2], $matches[1])
            );
        }

        if (!preg_match("/^'[0-9A-Fa-f]*'$/", $input)) {
            /** @throw alcamo::exception::SyntaxError on attempt to dedump an
             *  input which is neither a repetition as explained above nor a
             *  hex string enclosed in single quotes. */
            throw (new SyntaxError())->setMessageContext(
                [ 'inData' => $input ]
            );
        }

        return ImmutableBinaryString::newFromHex(trim($input, "'"));
    }

    public function dumpInt(int $value, string $encoding): string
    {
        switch ($encoding) {
            case 'ASCII':
                return $this->dumpString($value);

            case 'BIG-ENDIAN':
                return $this
                    ->dumpBinary(ImmutableBinaryString::newFromInt($value));

            case 'EBCDIC':
                return $this->dumpString($value, $encoding);

            default:
                return $value;
        }
    }

    public function dedumpInt(
        string $input,
        string $encoding,
        ?bool $isSigned = null
    ): int {
        switch ($encoding) {
            case 'ASCII':
                $value = $this->dedumpString($input);
                break;

            case 'BIG-ENDIAN':
                return $this->dedumpBinary($input)->toInt($isSigned);

            case 'EBCDIC':
                $value = $this->dedumpString($input, $encoding);
                break;

            default:
                $value = $input;
        }

        if (!is_numeric($value) || (int)$value != $value) {
            /** @throw alcamo::exception::SyntaxError on attempt to dedump an
             *  input which is not an integer. */
            throw (new SyntaxError())->setMessageContext(
                [ 'inData' => $value ]
            );
        }

        return (int)$value;
    }
}

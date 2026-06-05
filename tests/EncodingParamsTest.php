<?php

namespace alcamo\data_element;

use alcamo\exception\{
    DataValidationFailed,
    InvalidEnumerator,
    LengthOutOfRange,
    SyntaxError
};
use PHPUnit\Framework\TestCase;

class EncodingParamsTest extends TestCase
{
    /**
     * @dataProvider alignProvider
     */
    public function testAlign(
        $bitsPerCharacter,
        $padString,
        $padType,
        $value,
        $expectedResult
    ): void {
        $encodingParams =
            new EncodingParams('FOO', $bitsPerCharacter, $padString, $padType);

        $this->assertSame($expectedResult, $encodingParams->align($value));
    }

    public function alignProvider(): array
    {
        return [
            [ 8, '0', STR_PAD_LEFT, 'foo', 'foo' ],
            [ 4, '0', STR_PAD_LEFT, '', '' ],
            [ 4, '0', STR_PAD_LEFT, '1A', '1A' ],
            [ 4, null, null, '1A', '1A' ],
            [ 4, 'E', STR_PAD_LEFT, '1AF', 'E1AF' ],
            [ 4, 'D', STR_PAD_RIGHT, '7', '7D' ],
            [ 1, '1', STR_PAD_LEFT, '1010', '11111010' ],
            [ 1, '0', STR_PAD_RIGHT, '101010101', '1010101010000000' ]
        ];
    }

    /**
     * @dataProvider padProvider
     */
    public function testPad(
        $bitsPerCharacter,
        $padString,
        $padType,
        $value,
        $minLength,
        $expectedResult
    ): void {
        $encodingParams =
            new EncodingParams('BAR', $bitsPerCharacter, $padString, $padType);

        $this->assertSame(
            $expectedResult,
            $encodingParams->pad($value, $minLength)
        );
    }

    public function padProvider(): array
    {
        return [
            [ 8, null, null, 'foo', 0, 'foo' ],
            [ 8, null, null, 'bar', 3, 'bar' ],
            [ 8, '*', STR_PAD_LEFT, 'baz', 5, '**baz' ],
            [ 8, '#', STR_PAD_RIGHT, 'bazbaz', 7, 'bazbaz#' ],
            [ 4, 'E', STR_PAD_RIGHT, '', 3, 'EEE' ],
            [ 4, null, null, 'ABC', null, 'ABC' ],
            [ 4, null, null, 'ABC12', 5, 'ABC12' ],
            [ 1, '1', STR_PAD_LEFT, '000', 6, '111000' ]
        ];
    }

    /**
     * @dataProvider unpadProvider
     */
    public function testUnpad(
        $bitsPerCharacter,
        $padString,
        $padType,
        $value,
        $maxLength,
        $expectedResult
    ): void {
        $encodingParams =
            new EncodingParams('BAZ', $bitsPerCharacter, $padString, $padType);

        $this->assertSame(
            $expectedResult,
            $encodingParams->unpad($value, $maxLength)
        );
    }

    public function unpadProvider(): array
    {
        return [
            [ 1, null, null, '0', null, '0' ],
            [ 4, null, null, '12', 2, '12' ],
            [ 4, 'a', STR_PAD_LEFT, 'AAA123', 4, 'A123' ],
            [ 8, '=', STR_PAD_RIGHT, 'foo/bar===', 7, 'foo/bar' ]
        ];
    }

    /**
     * @dataProvider truncateProvider
     */
    public function testTruncate(
        $bitsPerCharacter,
        $padType,
        $value,
        $maxLength,
        $expectedResult
    ): void {
        $encodingParams = new EncodingParams(
            'QUX',
            $bitsPerCharacter,
            isset($padType) ? '0' : null,
            $padType
        );

        $this->assertSame(
            $expectedResult,
            $encodingParams->truncate($value, $maxLength)
        );
    }

    public function truncateProvider(): array
    {
        return [
            [ 4, null, 'abcd', null, 'abcd' ],
            [ 1, null, '00', 2, '00' ],
            [ 4, STR_PAD_LEFT, 'FDB973', 4, 'B973' ],
            [ 8, STR_PAD_RIGHT, 'corge', 3, 'cor' ]
        ];
    }

    public function testConstructException1(): void
    {
        $this->expectException(InvalidEnumerator::class);
        $this->expectExceptionMessage(
            'Invalid value 2, expected one of [1, 4, 8]'
        );

        new EncodingParams('FOO', 2);
    }

    public function testConstructException2(): void
    {
        $this->expectException(DataValidationFailed::class);
        $this->expectExceptionMessage(
            'Validation failed; padding string "x" contradicts '
                . 'unset padding type'
        );

        new EncodingParams('BAR', 8, 'x', null);
    }

    public function testConstructException3(): void
    {
        $this->expectException(DataValidationFailed::class);
        $this->expectExceptionMessage(
            'Validation failed; padding type 0 contradicts unset padding string'
        );

        new EncodingParams('BAZ', 4, null, STR_PAD_LEFT);
    }

    public function testConstructException4(): void
    {
        $this->expectException(DataValidationFailed::class);
        $this->expectExceptionMessage(
            'Validation failed; empty padding string'
        );

        new EncodingParams('QUX', 8, '', STR_PAD_RIGHT);
    }

    public function testConstructException5(): void
    {
        $this->expectException(DataValidationFailed::class);
        $this->expectExceptionMessage(
            'Validation failed; invalid padding string "2" for one-bit-encoding'
        );

        new EncodingParams('QUUX', 1, '2', STR_PAD_LEFT);
    }

    public function testConstructException6(): void
    {
        $this->expectException(DataValidationFailed::class);
        $this->expectExceptionMessage(
            'Validation failed; invalid padding string "G" '
                . 'for four-bit-encoding'
        );

        new EncodingParams('CORGE', 4, 'G', STR_PAD_LEFT);
    }

    public function testConstructException7(): void
    {
        $this->expectException(InvalidEnumerator::class);
        $this->expectExceptionMessage(
            'Invalid value -42, expected one of [0, 1]'
        );

        new EncodingParams('FOO-BAR', 8, '@', -42);
    }

    public function testAlignException1(): void
    {
        $encodingParams = new EncodingParams('FOO-BAZ', 1);

        $this->expectException(DataValidationFailed::class);
        $this->expectExceptionMessage(
            'Validation failed; length of "0001111" not a multiple of 8'
        );

        $encodingParams->align('0001111');
    }

    public function testAlignException2(): void
    {
        $encodingParams = new EncodingParams('FOO-QUX', 4);

        $this->expectException(DataValidationFailed::class);
        $this->expectExceptionMessage(
            'Validation failed; length of "ABC" odd'
        );

        $encodingParams->align('ABC');
    }

    public function testpadException(): void
    {
        $encodingParams = new EncodingParams('FOO-QUUX', 8);

        $this->expectException(LengthOutOfRange::class);
        $this->expectExceptionMessage(
            'Length 7 of "foo bar" out of range [8, "∞"]'
        );

        $encodingParams->pad('foo bar', 8);
    }

    public function testUnpadException1(): void
    {
        $encodingParams = new EncodingParams('FOO-CORGE', 8);

        $this->expectException(LengthOutOfRange::class);
        $this->expectExceptionMessage(
            'Length 3 of "baz" out of range [0, 2]'
        );

        $encodingParams->unpad('baz', 2);
    }

    public function testUnpadException2(): void
    {
        $encodingParams = new EncodingParams('BAR-FOO', 4, 'B', STR_PAD_LEFT);

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage(
            'Syntax error in "BA12"; invalid left padding data "BA"'
        );

        $encodingParams->unpad('BA12', 2);
    }

    public function testUnpadException3(): void
    {
        $encodingParams = new EncodingParams('BAR-BAZ', 8, '+', STR_PAD_RIGHT);

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage(
            'Syntax error in "baz+qux+"; invalid right padding data "x+"'
        );

        $encodingParams->unpad('baz+qux+', 6);
    }

    public function testTruncateException(): void
    {
        $encodingParams = new EncodingParams('BAR-QUX', 8);

        $this->expectException(LengthOutOfRange::class);
        $this->expectExceptionMessage(
            'Length 5 of "corge" out of range [0, 4]'
        );

        $encodingParams->truncate('corge', 4);
    }
}

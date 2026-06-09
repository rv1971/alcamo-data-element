<?php

namespace alcamo\data_element;

use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\input_stream\StringInputStream;
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\LiteralInterface;
use alcamo\xml\NamespaceConstantsInterface;

/**
 * @brief (De)Serializer for literal objects
 *
 * Each serializer object has at least the following properties:
 * - A datatype. Upon serialization, it checks whether the literal to
 *   serialize has a datatype derived from this datatype. Upon
 *   deserialization, it creates a literal with this datatype.
 * - Optionally a length range that limits the length of the serialized
 *   representation or the representation to unserialize. The units in which
 *   length is measured depend on the concrete seralizer class.
 * - Optionally flags that may rule, among others, how strictly the length
 *   range is enforced.
 *
 * Hence, each serializer instance is used to (de)serialize a specific data
 * element with a specific format.
 *
 * @date Last reviewed 2026-02-24
 */
interface SerializerInterface extends NamespaceConstantsInterface
{
    /// Whether to truncate silently upon serialization if needed
    public const TRUNCATE_SILENTLY = 1;

    /// Whether to not check length upon deserialization
    public const SKIP_LENGTH_CHECK = 2;

    /**
     * @brief Create from named properties an instance of this class
     *
     * Useful to create from configuration parameters an instance of the class
     * for which this method s called.
     *
     * @param $props object or array of named properties corresponding to the
     * constructor parameters
     */
    public static function newFromProps($props): self;

    public function getDatatype(): SimpleTypeInterface;

    public function getLengthRange(): ?NonNegativeRange;

    public function getFlags(): int;

    /**
     * @param $length Desired output length in encoding-dependent units (bytes
     * or nibbles or bits).
     */
    public function serialize(
        LiteralInterface $literal,
        ?int $length = null
    ): string;

    /**
     * @brief Serialize to hexadecimal string
     *
     * @param $length Desired output length *in encoding-dependent units*
     * (bytes or nibbles or bits), not in hexadecimal characters.
     */
    public function serializeToHex(
        LiteralInterface $literal,
        ?int $length = null
    ): string;

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface;

    /// Deserialize from hexadecimal string
    public function deserializeFromHex(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface;

    /// Serialize in a simple machine- and human-readable format
    public function dump(LiteralInterface $literal): string;

    /// The opposite of dump()
    public function dedump(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface;

    public function dedumpFromStream(
        StringInputStream $istream,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface;
}

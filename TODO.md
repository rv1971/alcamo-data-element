* Serializer should contain only type URI, not type
* implement other serializer classes
  * DateSerializer family with POSIX format for output and encodings
    ASCII, BCD, EBCDIC
* implement further serializers
  * DigitsStringSerializer (encoding like
    NonNegativeIntegerSerializer plus COMPRESSED-BCD)
  * FourBitStringSerializer (encodings ASCII, FOUR-BIT)
* check in weat/core/tlv for further needed features
* model bitmaps as union of enumeration and hexBinary of given length
* tag the current version of alcamo-rdfa

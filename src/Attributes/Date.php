<?php
namespace EntityORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Date
{
    public const string DATE = "Y-m-d";
    public const string TIME = "H:i:s";
    public const string DATETIME = Date::DATE." ".Date::TIME;
    public const string YEAR = "Y";

    public string $format;

    public function __construct(string $format)
    {
        $this->format = $format;
    }
}
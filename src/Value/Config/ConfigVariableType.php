<?php

declare(strict_types=1);

namespace App\Administering\Value\Config;

final class ConfigVariableType
{
    public const STRING = 'string';
    public const BOOL = 'bool';
    public const INT = 'int';
    public const FLOAT = 'float';
    public const ENUM = 'enum';
    public const LIST = 'list';
    public const MAP = 'map';
    public const JSON = 'json';
    public const YAML = 'yaml';
    public const SECRET_REF = 'secret_ref';
}

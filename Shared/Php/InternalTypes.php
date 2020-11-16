<?php
namespace Shared\Php;

class InternalTypes
{
    /*****************
     *   Constants   *
     ****************/

    /**
     * @const  string[]
     */
    public const IN_DOCCOMMENTS = [
        '',         'array',    'bool',     'callable', 'false',    'float',    'int',      'mixed',
        'object',   'resource', 'string',   'true',     'void',
    ];

    /**
     * @const  string[]
     */
    public const TYPE_LIST = [
        'array',
        'bool',
        'callable',
        'float',
        'int',
        'iterable',
        'null',
        'object',
        'string',
        'void',
    ];
}

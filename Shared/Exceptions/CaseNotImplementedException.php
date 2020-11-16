<?php
namespace Shared\Exceptions;

/**
 * (summary missing)
 */
class CaseNotImplementedException extends AbstractBaseException
{
    /************************
     *   Instance methods   *
     ***********************/

    /**
     * CaseNotImplementedException constructor
     *
     * @param  mixed  $caseValue
     */
    public function __construct($caseValue)
    {
        parent::__construct($caseValue);
    }

    public function getPattern(): string
    {
        return 'Case %s is not implemented';
    }
}

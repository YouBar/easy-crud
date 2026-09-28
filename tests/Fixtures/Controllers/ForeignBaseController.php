<?php

namespace Youbar\EasyCrud\Tests\Fixtures\Controllers;

/**
 * Stands in for an application's own base controller.
 */
abstract class ForeignBaseController
{
    public function somethingApplicationSpecific(): string
    {
        return 'still here';
    }
}

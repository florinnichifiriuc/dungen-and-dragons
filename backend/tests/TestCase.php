<?php

namespace Tests;

use DateTimeInterface;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function freezeTime($date = null, $callback = null)
    {
        if ($date instanceof DateTimeInterface) {
            return $this->travelTo($date, $callback);
        }

        if ($date === null) {
            return parent::freezeTime($callback);
        }

        return parent::freezeTime($date);
    }
}

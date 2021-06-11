<?php

/**
 * Class ServeboltWPUnitTestCase
 */
class ServeboltWPUnitTestCase extends WP_UnitTestCase
{
    protected function allowPersistenceInDatabase(): void
    {
        remove_filter('query', [$this, '_create_temporary_tables']);
        remove_filter('query', [$this, '_drop_temporary_tables']);
    }

    protected function disallowPersistenceInDatabase(): void
    {
        add_filter('query', [$this, '_create_temporary_tables']);
        add_filter('query', [$this, '_drop_temporary_tables']);
    }

    protected function multisiteOnly()
    {
        if (!is_multisite()) {
            $this->markTestSkipped('Test can only be run on a multisite.');
        }
    }
}

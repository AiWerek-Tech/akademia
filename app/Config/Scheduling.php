<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Scheduling extends BaseConfig
{
    /**
     * Disabled until the normalized schedule-entry teacher contract is
     * migrated and accepted. Disabled means fail closed, never flatten data.
     */
    public bool $teamTeachingEnabled = false;
}

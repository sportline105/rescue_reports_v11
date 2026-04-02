<?php

namespace In2code\RescueReports\Utility;

class TestDebugUtility
{
    public function debugItems(array &$config): void
    {
        $config['items'][] = ['Debug funktioniert 🎉', 1234];
    }
}

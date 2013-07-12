<?php

if(!isset($vendorDir)) {
    throw new Exception('The [$vendorDir] variable is not available anymore from the composer ComposerAutoloaderInit class');
}

set_include_path(get_include_path() . PATH_SEPARATOR . $vendorDir . PATH_SEPARATOR . realpath(__DIR__ . '/../..'));

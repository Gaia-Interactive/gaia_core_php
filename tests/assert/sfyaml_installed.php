<?php
use Gaia\Test\Tap;

if( ! class_exists('sfYaml')){
    Tap::plan('skip_all', 'sfYaml class not loaded. check vendors/yaml git submodule');
}

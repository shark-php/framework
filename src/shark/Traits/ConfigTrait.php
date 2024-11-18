<?php

namespace Shark\Traits;

trait ConfigTrait
{
    protected array $configs = [];

    protected function setConfigs(array $configs,array $defaults = []): void
    {
        $this->configs = count($configs) ? $configs: $defaults;
    }

    private function getConfig(string $key, mixed $default = null) : mixed
    {
        return $this->configs[$key]??$default;
    }
}
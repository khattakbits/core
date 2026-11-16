<?php

namespace KhattakBits\Core\Support;

final class ModuleRegistry
{
    private ?array $modules = null;

    /** @return array<string, array{name: string, path: string, def: array}> */
    public function all(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        $modules = [];
        foreach (glob(app_path('Modules/*'), GLOB_ONLYDIR) ?: [] as $dir) {
            $file = $dir.'/menu.php';
            if (! is_file($file)) {
                continue;
            }
            $def = require $file;
            $modules[$def['key']] = ['name' => basename($dir), 'path' => $dir, 'def' => $def];
        }

        uasort($modules, fn ($a, $b) => ($a['def']['order'] ?? 100) <=> ($b['def']['order'] ?? 100));

        return $this->modules = $modules;
    }

    /**
     * Module migration paths, context ke hisaab se (docs/platform/07 §7.2).
     *
     * Convention (`07` §7.1): `{module}/Database/Migrations/{central,tenant}`.
     * Do hi valid folders hain — flat `Database/Migrations/*.php` support nahi.
     *
     * @param  'central'|'tenant'  $context
     * @return array<string, string> module key => absolute path
     */
    public function migrationPaths(string $context): array
    {
        if (! in_array($context, ['central', 'tenant'], true)) {
            throw new \InvalidArgumentException("Unknown migration context [{$context}].");
        }

        $paths = [];

        foreach ($this->all() as $key => $module) {
            $dir = $module['path'].'/Database/Migrations/'.$context;

            if (is_dir($dir)) {
                $paths[$key] = $dir;
            }
        }

        return $paths;
    }
}

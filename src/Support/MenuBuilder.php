<?php

namespace KhattakBits\Core\Support;

use KhattakBits\Core\Models\User;

/**
 * Menu payload builder — `docs/platform/07` §5.3, `03` D3.2.
 *
 * Har item ke do keys hain:
 *
 * - `path` — **static** route URI, slug-free. Host ka `routes.php` isi par route
 *   register karta hai (aur `{tenant}` group ke andar rakhta hai).
 * - `href` — **runtime** link jo sidebar render karta hai. Aaj yeh `path` hi hai;
 *   tenancy on hone par slug prefix `tenantUrl()` se aayega (07 §5.2, P5) — us waqt
 *   sirf `hrefFor()` badalna hai, `menu.php` files nahi.
 *
 * **Trap jo yeh contract band karta hai:** pehle `menu.php` ka `href` hi route URI
 * tha (`AccountingDemo/routes.php` us par `Route::inertia()` karta tha). Agar `href`
 * mein slug daal diya jaye to route registration mein slug bake ho jata — tenant A
 * ki routes banti, tenant B ki 404. Is liye registration `path` par, rendering
 * `href` par.
 *
 * **Backward compatible:** purani `menu.php` jis mein sirf `href` hai, woh chalti
 * rahegi (`path` = `href`), magar `routes.php` tab bhi `path` par switch karna
 * hoga — warna wohi trap wapas (`UPGRADE.md` C4/C5).
 */
final class MenuBuilder
{
    public function __construct(private ModuleRegistry $registry) {}

    public function for(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $menu = [];
        foreach ($this->registry->all() as $module) {
            $def = $module['def'];
            $sections = [];

            foreach ($def['sections'] as $section) {
                $items = array_values(array_filter(
                    $section['items'],
                    fn ($i) => $i['can'] === null || $user->can($i['can'])
                ));
                if ($items) {
                    $sections[] = [
                        'title' => $section['title'],
                        'items' => array_map(fn ($i) => [
                            'label' => $i['label'],
                            'path' => $this->pathFor($i),
                            'href' => $this->hrefFor($this->pathFor($i)),
                            'group' => $i['group'] ?? null,
                        ], $items),
                    ];
                }
            }

            if ($sections) {
                $first = $sections[0]['items'][0];

                $menu[] = [
                    'key' => $def['key'],
                    'label' => $def['label'],
                    'icon' => $def['icon'] ?? 'layers',
                    'prefix' => $def['prefix'],
                    'path' => $first['path'],   // module ka pehla item jis ka haq ho
                    'href' => $first['href'],
                    'sections' => $sections,
                ];
            }
        }

        return $menu;
    }

    /**
     * Static, slug-free route URI.
     *
     * `href` fallback jaan boojh kar hai: purani `menu.php` (v1.x) mein `path` nahi
     * tha, aur `href` hi static path tha — us waqt woh dono kaam karta tha. Ab
     * `href` runtime hai, is liye naye menus `path` likhein.
     *
     * @param  array{label?: string, path?: string, href?: string|null, can?: string|null}  $item
     */
    private function pathFor(array $item): string
    {
        $path = $item['path'] ?? $item['href'] ?? '/';

        return is_string($path) && $path !== '' ? $path : '/';
    }

    /**
     * Runtime href. Aaj (v2.0.0) yeh `path` hi hai — slug prefixing P5 mein
     * `tenantUrl()` se aayegi (07 §5.2). Yeh seam is liye maujood hai ke us waqt
     * sirf yeh ek function badle, aur `href` ka contract pehle se tay ho.
     */
    private function hrefFor(string $path): string
    {
        return $path;
    }
}

<?php

declare(strict_types=1);

namespace Naxas\RestaurantOps\Http\Controllers\MenuConfiguration;

use Igniter\Cart\Models\Menu;
use Igniter\Cart\Models\MenuItemOption;
use Igniter\Cart\Models\MenuItemOptionValue;
use Igniter\Cart\Models\MenuOption;
use Igniter\Cart\Models\MenuOptionValue;
use Igniter\Cart\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Naxas\RestaurantOps\Contracts\AuditLogger;
use Naxas\RestaurantOps\Contracts\LocationContextContract;
use Naxas\RestaurantOps\Http\Controllers\AdminPageController;
use Naxas\RestaurantOps\Models\Combo;
use Naxas\RestaurantOps\Models\ItemVariant;
use Naxas\RestaurantOps\Models\MenuModifierGroup;
use Naxas\RestaurantOps\Models\ModifierMetadata;
use Naxas\RestaurantOps\Models\ModifierGroup;

final class MenuConfigurations extends AdminPageController
{
    public function __construct(private readonly AuditLogger $audit)
    {
        parent::__construct();
    }

    public function catalog(): string
    {
        return $this->renderAdminPage('Naxas.RestaurantOps::menu-configuration-catalog', [
            'menus' => Menu::query()->with(['restaurant_ops_metadata', 'restaurant_ops_variants'])->orderBy('menu_name')->paginate(30),
        ], lang('Naxas.RestaurantOps::default.navigation.menu_configuration'), 'restaurant-ops-menu-config');
    }

    public function index(string $menuId): string
    {
        $menu = Menu::query()->with(['menu_options.option', 'menu_options.menu_option_values.option_value'])->findOrFail($menuId);
        $officialOptions = $menu->menu_options->sortBy('priority')->values();

        return $this->renderAdminPage('Naxas.RestaurantOps::menu-configuration', ['menu' => $menu, 'variants' => ItemVariant::query()->where('menu_id', $menu->getKey())->orderBy('display_order')->get(), 'groups' => MenuModifierGroup::query()->where('menu_id', $menu->getKey())->orderBy('display_order')->get(), 'sharedGroups' => ModifierGroup::query()->where('is_active', true)->orderBy('display_order')->get(), 'officialOptions' => $officialOptions, 'combo' => Combo::query()->where('menu_id', $menu->getKey())->first()], lang('Naxas.RestaurantOps::default.navigation.menu_configuration'), 'restaurant-ops-menu-config');
    }

    public function storeVariant(string $menuId): JsonResponse
    {
        $menu = Menu::query()->findOrFail($menuId);
        $data = request()->validate(['id' => ['nullable', 'integer'], 'code' => ['required', 'alpha_dash', 'max:64'], 'name' => ['required', 'string', 'max:255'], 'kitchen_name' => ['nullable', 'string', 'max:255'], 'price_mode' => ['required', 'in:adjustment,absolute'], 'price_value' => ['required', 'decimal:0,4', 'min:-9999999999'], 'is_default' => ['required', 'boolean'], 'is_active' => ['required', 'boolean'], 'display_order' => ['nullable', 'integer', 'min:0'], 'version' => ['nullable', 'integer', 'min:1']]);
        $variant = DB::transaction(function () use ($data, $menu): ItemVariant {
            $variant = isset($data['id']) ? ItemVariant::query()->where('menu_id', $menu->getKey())->findOrFail($data['id']) : new ItemVariant(['menu_id' => $menu->getKey()]);
            if ($variant->exists && isset($data['version']) && (int) $variant->version !== (int) $data['version']) {
                abort(409, 'Menu configuration changed; refresh before saving.');
            }
            if ($data['is_default']) {
                ItemVariant::query()->where('menu_id', $menu->getKey())->when($variant->exists, fn ($q) => $q->whereKeyNot($variant->getKey()))->update(['is_default' => false]);
            }
            $variant->fill($data);
            $variant->version = $variant->exists ? $variant->version + 1 : 1;
            $variant->saveOrFail();

            return $variant;
        });
        $this->audit->info('restaurant_ops.variant.saved', ['staff_id' => app('admin.auth')->user()?->getKey(), 'menu_id' => $menu->getKey(), 'variant_id' => $variant->getKey(), 'fields' => array_keys($data)]);

        return response()->json(['data' => $variant, 'configuration_hash' => hash('sha256', $variant->toJson())], $variant->wasRecentlyCreated ? 201 : 200);
    }

    public function archiveVariant(string $menuId, string $variantId): JsonResponse
    {
        $menu = Menu::query()->findOrFail($menuId);
        $variant = ItemVariant::query()->findOrFail($variantId);
        abort_unless((int) $variant->menu_id === (int) $menu->getKey(), 404);
        $variant->forceFill(['is_active' => false, 'is_default' => false, 'archived_at' => now(), 'version' => $variant->version + 1])->saveOrFail();
        $this->audit->info('restaurant_ops.variant.archived', ['staff_id' => app('admin.auth')->user()?->getKey(), 'menu_id' => $menu->getKey(), 'variant_id' => $variant->getKey()]);

        return response()->json(['data' => $variant]);
    }

    public function storeOptionGroup(string $menuId): JsonResponse
    {
        $menu = Menu::query()->findOrFail($menuId);
        $data = request()->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_type' => ['required', 'in:radio,select,checkbox,quantity'],
            'is_required' => ['required', 'boolean'],
            'min_selected' => ['nullable', 'integer', 'min:0', 'max:99'],
            'max_selected' => ['nullable', 'integer', 'min:0', 'max:99'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'values' => ['required', 'array', 'min:1'],
            'values.*.name' => ['required', 'string', 'max:255'],
            'values.*.price' => ['nullable', 'decimal:0,4', 'min:0'],
            'values.*.is_default' => ['nullable', 'boolean'],
            'values.*.stock_qty' => ['nullable', 'integer', 'min:0'],
        ]);

        $summary = DB::transaction(function () use ($menu, $data): array {
            $locationId = app(LocationContextContract::class)->currentId();
            $isSingle = in_array($data['display_type'], ['radio', 'select'], true);
            $min = (int) ($data['min_selected'] ?? ($data['is_required'] ? 1 : 0));
            $max = $isSingle ? 1 : (int) ($data['max_selected'] ?? count($data['values']));
            $max = max($min, $max ?: count($data['values']));

            $option = MenuOption::query()->create([
                'option_name' => $data['name'],
                'display_type' => $data['display_type'],
            ]);
            $option->forceFill(['priority' => (int) ($data['priority'] ?? 0)])->save();
            if ($locationId && method_exists($option, 'locations')) {
                $option->locations()->syncWithoutDetaching([$locationId]);
            }

            $menuOption = MenuItemOption::query()->create([
                'menu_id' => $menu->getKey(),
                'option_id' => $option->getKey(),
                'is_required' => (bool) $data['is_required'],
                'min_selected' => $min,
                'max_selected' => $max,
                'priority' => (int) ($data['priority'] ?? 0),
                'free_quantity' => 0,
            ]);

            foreach (array_values($data['values']) as $index => $row) {
                $price = (string) ($row['price'] ?? '0');
                $optionValue = MenuOptionValue::query()->create([
                    'option_id' => $option->getKey(),
                    'name' => $row['name'],
                    'price' => $price,
                    'priority' => $index,
                ]);
                MenuItemOptionValue::query()->create([
                    'menu_option_id' => $menuOption->getKey(),
                    'option_value_id' => $optionValue->getKey(),
                    'override_price' => $price,
                    'priority' => $index,
                    'is_default' => (bool) ($row['is_default'] ?? false),
                    'free_quantity' => 0,
                ]);

                if ($locationId && array_key_exists('stock_qty', $row) && $row['stock_qty'] !== null && $row['stock_qty'] !== '') {
                    $stock = $optionValue->getStockByLocation($locationId);
                    $stock->forceFill(['is_tracked' => true, 'low_stock_alert' => false, 'low_stock_threshold' => 0])->save();
                    $stock->updateStock((int) $row['stock_qty'], Stock::STATE_RECOUNT);
                }
            }

            return $this->syncMenuOfficialOptions($menu->fresh(['menu_options.menu_option_values.option_value', 'menu_options.option']));
        });

        $this->audit->info('restaurant_ops.option_group.saved', ['staff_id' => app('admin.auth')->user()?->getKey(), 'menu_id' => $menu->getKey()] + $summary);

        return response()->json(['data' => $summary], 201);
    }

    public function syncOfficialOptions(string $menuId): JsonResponse
    {
        $menu = Menu::query()->with(['menu_options.menu_option_values.option_value', 'menu_options.option'])->findOrFail($menuId);
        $summary = DB::transaction(fn (): array => $this->syncMenuOfficialOptions($menu));

        $this->audit->info('restaurant_ops.official_options.synced', ['staff_id' => app('admin.auth')->user()?->getKey(), 'menu_id' => $menu->getKey()] + $summary);

        return response()->json(['data' => $summary]);
    }

    private function syncMenuOfficialOptions(Menu $menu): array
    {
            $groups = 0;
            $modifiers = 0;
            foreach ($menu->menu_options as $menuOption) {
                $option = $menuOption->option;
                if (! $option) {
                    continue;
                }

                $type = in_array($option->display_type, ['radio', 'select'], true) ? 'single' : 'multiple';
                $group = ModifierGroup::query()->updateOrCreate(
                    ['option_id' => $option->getKey()],
                    [
                        'code' => $this->uniqueCode('official-option-'.$option->getKey(), $option->option_name),
                        'name' => $option->option_name,
                        'selection_type' => $type,
                        'is_required' => (bool) $menuOption->is_required,
                        'min_selections' => (int) $menuOption->min_selected,
                        'max_selections' => $type === 'single' ? 1 : ($menuOption->max_selected ?: null),
                        'free_quantity' => (int) $menuOption->free_quantity,
                        'allow_quantity' => $option->display_type === 'quantity',
                        'max_quantity_per_modifier' => $option->display_type === 'quantity' ? 99 : 1,
                        'display_order' => (int) $menuOption->priority,
                        'is_active' => true,
                        'pos_visible' => true,
                        'storefront_visible' => true,
                        'delivery_visible' => true,
                        'collection_visible' => true,
                        'dine_in_visible' => true,
                        'version' => 1,
                    ],
                );

                MenuModifierGroup::query()->updateOrCreate(
                    ['menu_id' => $menu->getKey(), 'variant_id' => null, 'modifier_group_id' => $group->getKey()],
                    [
                        'required_override' => (bool) $menuOption->is_required,
                        'min_override' => (int) $menuOption->min_selected,
                        'max_override' => $type === 'single' ? 1 : ($menuOption->max_selected ?: null),
                        'free_quantity_override' => (int) $menuOption->free_quantity,
                        'display_order' => (int) $menuOption->priority,
                        'is_active' => true,
                    ],
                );
                $groups++;

                foreach ($menuOption->menu_option_values as $menuValue) {
                    $optionValue = $menuValue->option_value;
                    if (! $optionValue) {
                        continue;
                    }

                    ModifierMetadata::query()->updateOrCreate(
                        ['option_value_id' => $optionValue->getKey()],
                        [
                            'code' => $this->uniqueCode('official-value-'.$optionValue->getKey(), $optionValue->name),
                            'kitchen_name' => $optionValue->name,
                            'price_adjustment' => $menuValue->override_price ?? $optionValue->price ?? 0,
                            'min_quantity' => 0,
                            'max_quantity' => $option->display_type === 'quantity' ? 99 : 1,
                            'allow_quantity' => $option->display_type === 'quantity',
                            'is_default' => (bool) $menuValue->is_default,
                            'is_active' => true,
                            'is_sold_out' => false,
                            'display_order' => (int) ($menuValue->priority ?: $optionValue->priority),
                            'pos_visible' => true,
                            'storefront_visible' => true,
                            'kitchen_visible' => true,
                            'receipt_visible' => true,
                            'version' => 1,
                        ],
                    );
                    $modifiers++;
                }
            }

            return ['groups' => $groups, 'modifiers' => $modifiers];
    }

    private function uniqueCode(string $prefix, string $label): string
    {
        return Str::limit($prefix.'-'.Str::slug($label), 64, '');
    }
}

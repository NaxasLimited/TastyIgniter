<?php

declare(strict_types=1);

namespace Tests\Unit;

require_once __DIR__.'/../../src/Support/RawSql.php';

use Naxas\RestaurantOps\Support\RawSql;
use PHPUnit\Framework\TestCase;

final class RawSqlTest extends TestCase
{
    public function test_it_qualifies_raw_query_aliases_for_prefixed_installations(): void
    {
        $expression = 'COALESCE(menu_values.override_price, modifiers.price_adjustment, option_values.price, 0) as price';

        self::assertSame(
            'COALESCE(ti_menu_values.override_price, ti_modifiers.price_adjustment, ti_option_values.price, 0) as price',
            RawSql::qualifyAliases($expression, ['menu_values', 'modifiers', 'option_values'], 'ti_'),
        );
    }

    public function test_it_does_not_double_prefix_or_modify_similar_aliases(): void
    {
        $expression = 'shift_payments.paid_total + payments.paid_total + ti_payments.refund_total';

        self::assertSame(
            'ti_shift_payments.paid_total + ti_payments.paid_total + ti_payments.refund_total',
            RawSql::qualifyAliases($expression, ['payments', 'shift_payments'], 'ti_'),
        );
    }

    public function test_unprefixed_installations_remain_unchanged(): void
    {
        self::assertSame(
            'orders.order_total',
            RawSql::qualifyAliases('orders.order_total', ['orders'], ''),
        );
    }
}

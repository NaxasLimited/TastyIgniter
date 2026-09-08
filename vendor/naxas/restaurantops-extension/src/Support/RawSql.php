<?php

declare(strict_types=1);

namespace Naxas\RestaurantOps\Support;

use Illuminate\Support\Facades\DB;

final class RawSql
{
    /**
     * Query Builder prefixes aliases in normal column references, but it cannot
     * inspect aliases embedded in raw SQL expressions. Qualify those aliases so
     * the expression works with both prefixed and unprefixed installations.
     *
     * @param  list<string>  $aliases
     */
    public static function qualifyAliases(string $expression, array $aliases, ?string $prefix = null): string
    {
        $prefix ??= DB::connection()->getTablePrefix();
        if ($prefix === '') {
            return $expression;
        }

        usort($aliases, fn (string $left, string $right): int => strlen($right) <=> strlen($left));
        foreach ($aliases as $alias) {
            $expression = (string) preg_replace(
                '/(?<![A-Za-z0-9_])'.preg_quote($alias, '/').'(?=\.)/',
                $prefix.$alias,
                $expression,
            );
        }

        return $expression;
    }
}

<?php

declare(strict_types=1);

namespace Naxas\RestaurantOps\Pos\Contracts;

use Naxas\RestaurantOps\Models\PosOrder;
use Naxas\RestaurantOps\Models\PosOrderItem;

interface PosOrderServiceContract
{
    public function createDraft(mixed $actor, array $data, string $idempotencyKey): PosOrder;

    public function addItem(PosOrder $order, mixed $actor, array $selection, int $version, string $idempotencyKey): PosOrderItem;

    public function updateDetails(PosOrder $order, mixed $actor, array $data, int $version): PosOrder;

    public function updateItem(PosOrder $order, PosOrderItem $item, mixed $actor, array $data, int $version): PosOrder;

    public function removeItem(PosOrder $order, PosOrderItem $item, mixed $actor, int $version, ?string $reason = null): PosOrder;

    public function hold(PosOrder $order, mixed $actor, int $version, ?string $reason = null): PosOrder;

    public function recall(PosOrder $order, mixed $actor, int $version): PosOrder;

    public function confirm(PosOrder $order, mixed $actor, int $version): PosOrder;

    public function requestKitchen(PosOrder $order, mixed $actor, int $version): PosOrder;

    public function lockForPayment(PosOrder $order, mixed $actor, int $version): PosOrder;

    public function cancel(PosOrder $order, mixed $actor, int $version, string $reason): PosOrder;
}

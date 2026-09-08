# Full System Integration Audit

Date: 2026-09-07

## Goal

Restaurant Operations should not feel like a separate side system. POS, official orders, menu items, inventory, kitchen, reservations, tables, staff roles, payments, receipts, and reports must operate from the same business records wherever possible.

## Current Integration State

### Orders

POS orders are stored in `naxas_restaurant_ops_pos_orders` and linked to official TastyIgniter `orders` through `order_id`.

Integrated:
- POS confirms into an official order.
- POS payment marks the official order payment as processed.
- POS receipt/payment details are available from the Restaurant Ops side.
- Restaurant Ops active order list now shows POS order and official order information together.

Remaining:
- The official order list should visually surface POS table, waiter, shift, cashier, and receipt without needing a separate Restaurant Ops page.
- Kitchen/order status changes should stay synchronized both ways.

### Menu And Inventory

Official TastyIgniter menu items remain the source of truth for menu item name, price, category, location, image/media, and stock.

Integrated:
- Restaurant Ops menu configuration attaches variants and modifier groups to official menu items.
- POS reads official menu items and Restaurant Ops variants/modifiers together.
- Inventory permission uses the official `Admin.Inventory` permission.

Remaining:
- Menu image upload depends on core `Admin.MediaManager`.
- A safer limited upload flow is needed if managers should upload menu photos but not manage the whole media library.
- Variant stock and option stock should be clearly reportable in the inventory view.

### Tables And Dine-in

Restaurant Ops has table/floor/session records and links dine-in POS orders to a table session.

Integrated:
- Dine-in can require table, waiter, and guest count.
- Table session links to POS order and later official order.

Remaining:
- Table map should open/continue the linked POS order directly.
- Official reservation table data and Restaurant Ops table sessions need a clearer shared view.

### Kitchen

Kitchen permissions and order events exist, but the current kitchen screen is still mostly operational placeholder/workspace.

Integrated:
- POS can send order data toward kitchen flow.
- Kitchen permissions exist separately from cashier/waiter permissions.

Remaining:
- Kitchen ticket board should read live POS/official order items.
- Ticket status changes should update item/order status and be visible in official order detail.

### Reservations

Official reservation routes and permissions exist in the core system.

Integrated:
- Branch Manager can be granted official `Admin.Reservations` without settings access.

Remaining:
- Reservations are not yet unified with Restaurant Ops table sessions.
- A reservation converted to dine-in should open the same table/POS flow.

### Payments And Reports

Restaurant Ops stores POS payments/tenders and syncs paid state to official orders.

Integrated:
- Tender reporting exists for cash, bKash, Nagad, card, and related payment methods.
- Dashboard widgets can read Restaurant Ops payment/order data.

Remaining:
- Official order detail/list should expose the same tender breakdown.
- Refund/reversal flow should remain manager-controlled and audited.

### Staff Permissions

TastyIgniter `UserRole` is the control point. Staff groups are not authorization roles.

Integrated:
- Standard roles exist: Owner, Branch Manager, Cashier, Waiter, Kitchen Staff.
- Role permissions can be edited manually in the admin panel.
- Sync creates missing standard roles and adds required Restaurant Ops permissions only when requested.

Remaining:
- A human-friendly role matrix page would make it easier to manage permissions without hunting through every checkbox.

## Media Upload Finding

TastyIgniter core media upload checks `Admin.MediaManager` directly. Without that permission, a staff user cannot upload logo/menu images.

Local environment check:
- Upload max size: 64M
- Post max size: 64M
- `storage/app/public` writable: yes
- `public/storage` exists and writable: yes

Decision needed:
- Owner/admin uploads all logos and menu images; managers do not get media access.
- Or create a limited Restaurant Ops menu-image uploader that allows menu image upload only, without giving full media manager access.

## Recommended Next Steps

1. Make official order detail/list show POS fields: table, waiter, shift, cashier, receipt, tender.
2. Build a true kitchen ticket board connected to POS order items.
3. Connect reservation-to-table-to-POS flow.
4. Add a limited menu image uploader if managers should upload menu item photos without full media manager access.
5. Add a role matrix screen for Owner to control Manager, Cashier, Waiter, and Kitchen permissions safely.

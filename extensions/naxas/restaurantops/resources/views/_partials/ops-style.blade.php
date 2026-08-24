<style>
    .rops-shell {
        --rops-ink: #172033;
        --rops-muted: #667085;
        --rops-line: #d9dee8;
        --rops-soft: #f6f8fb;
        --rops-panel: #fff;
        --rops-accent: #f05a28;
        --rops-green: #12805c;
        color: var(--rops-ink);
    }
    .rops-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 16px;
    }
    .rops-title h1 {
        font-size: 24px;
        margin: 0 0 4px;
        font-weight: 700;
    }
    .rops-title p {
        color: var(--rops-muted);
        margin: 0;
    }
    .rops-card {
        background: var(--rops-panel);
        border: 1px solid var(--rops-line);
        border-radius: 8px;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
    }
    .rops-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--rops-line);
        font-weight: 700;
    }
    .rops-card-body { padding: 16px; }
    .rops-grid { display: grid; gap: 12px; }
    .rops-grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .rops-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .rops-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .rops-stat {
        padding: 14px;
        border: 1px solid var(--rops-line);
        border-radius: 8px;
        background: var(--rops-soft);
    }
    .rops-stat span {
        display: block;
        color: var(--rops-muted);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0;
    }
    .rops-stat strong { display: block; font-size: 22px; margin-top: 4px; }
    .rops-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border: 1px solid var(--rops-line);
        border-radius: 999px;
        background: #fff;
        font-weight: 700;
        font-size: 12px;
    }
    .rops-pill.is-open { color: var(--rops-green); border-color: #a9decf; background: #edf9f5; }
    .rops-pill.is-closed { color: #b42318; border-color: #f3b8b1; background: #fff3f1; }
    .rops-menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(168px, 1fr));
        gap: 10px;
        max-height: 548px;
        overflow: auto;
        padding-right: 4px;
    }
    .rops-menu-tile {
        text-align: left;
        border: 1px solid var(--rops-line);
        border-radius: 8px;
        background: #fff;
        padding: 12px;
        min-height: 114px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        cursor: pointer;
    }
    .rops-menu-tile:hover { border-color: var(--rops-accent); box-shadow: 0 6px 18px rgba(16, 24, 40, .08); }
    .rops-menu-tile[disabled] { cursor: not-allowed; opacity: .55; }
    .rops-menu-name { font-weight: 700; line-height: 1.25; }
    .rops-menu-price { color: var(--rops-green); font-weight: 700; margin-top: 8px; }
    .rops-order-line {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 8px;
        padding: 10px 0;
        border-bottom: 1px solid var(--rops-line);
    }
    .rops-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .rops-table {
        width: 100%;
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }
    .rops-table th {
        color: var(--rops-muted);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0;
        background: var(--rops-soft);
    }
    .rops-table th,
    .rops-table td {
        padding: 12px;
        border-bottom: 1px solid var(--rops-line);
        vertical-align: middle;
    }
    .rops-table tr:last-child td { border-bottom: 0; }
    .rops-muted { color: var(--rops-muted); }
    .rops-service {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }
    .rops-service input { position: absolute; opacity: 0; pointer-events: none; }
    .rops-service label {
        border: 1px solid var(--rops-line);
        border-radius: 8px;
        padding: 10px 12px;
        text-align: center;
        font-weight: 700;
        cursor: pointer;
        background: #fff;
    }
    .rops-service input:checked + label {
        border-color: var(--rops-accent);
        color: var(--rops-accent);
        background: #fff6f2;
    }
    .rops-toast {
        display: none;
        margin-bottom: 12px;
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid var(--rops-line);
        background: #fff;
    }
    .rops-toast.is-error { border-color: #f3b8b1; color: #b42318; background: #fff3f1; }
    .rops-toast.is-ok { border-color: #a9decf; color: var(--rops-green); background: #edf9f5; }
    .rops-pos-full {
        background: #f4f4f7;
        margin: -16px -16px 0;
        min-height: calc(100vh - 92px);
    }
    .rops-pos-topbar {
        display: grid;
        grid-template-columns: minmax(360px, 1fr) auto minmax(320px, 1fr);
        align-items: center;
        gap: 16px;
        background: #304259;
        color: #fff;
        padding: 6px 10px;
        min-height: 54px;
    }
    .rops-pos-nav,
    .rops-pos-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .rops-pos-actions { justify-content: flex-end; }
    .rops-pos-service input { position: absolute; opacity: 0; pointer-events: none; }
    .rops-pos-service label,
    .rops-pos-create,
    .rops-pos-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 40px;
        border: 0;
        border-radius: 4px;
        padding: 0 16px;
        color: #fff;
        background: transparent;
        font-weight: 700;
        cursor: pointer;
    }
    .rops-pos-create { background: #4da7b4; }
    .rops-pos-fullscreen-button {
        border: 1px solid rgba(255, 255, 255, .22);
        background: rgba(255, 255, 255, .08);
        white-space: nowrap;
    }
    .rops-pos-fullscreen-button:hover { background: rgba(255, 255, 255, .16); }
    .rops-pos-service label:hover { background: rgba(255, 255, 255, .08); }
    .rops-pos-service input:checked + label { background: #4da7b4; }
    .rops-pos-icon {
        width: 18px;
        text-align: center;
        font-weight: 800;
        line-height: 1;
    }
    .rops-count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 26px;
        height: 26px;
        border-radius: 999px;
        background: #d51224;
        color: #fff;
        font-size: 13px;
    }
    .rops-pos-brand {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-weight: 800;
        letter-spacing: 0;
    }
    .rops-pos-brand-mark {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #111827;
        display: grid;
        place-items: center;
        color: #ff7a3d;
        font-size: 12px;
    }
    .rops-pos-main {
        display: grid;
        grid-template-columns: minmax(660px, 1fr) 610px;
        gap: 0;
        height: calc(100vh - 146px);
        min-height: 650px;
    }
    .rops-pos-catalog {
        display: grid;
        grid-template-rows: auto 1fr;
        min-width: 0;
        border-right: 1px solid #d8dde6;
    }
    .rops-pos-search {
        padding: 8px 10px;
        background: #f6f6f9;
    }
    .rops-pos-search input {
        height: 48px;
        border-radius: 5px;
        border: 1px solid #d8dde6;
        background: #fff;
        font-size: 15px;
    }
    .rops-pos-browser {
        display: grid;
        grid-template-columns: 208px 1fr;
        gap: 10px;
        min-height: 0;
        padding: 0 10px 10px;
    }
    .rops-category-rail {
        overflow: auto;
        background: #34475f;
        border-radius: 7px;
        padding: 8px 0;
    }
    .rops-category-button {
        width: 100%;
        min-height: 52px;
        border: 0;
        border-left: 4px solid transparent;
        background: transparent;
        color: #fff;
        text-align: left;
        padding: 9px 16px;
        font-weight: 700;
        line-height: 1.35;
    }
    .rops-category-button.is-active {
        background: #4da7b4;
        border-left-color: #b7d35c;
    }
    .rops-pos-item-grid {
        align-content: start;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
        gap: 8px;
        overflow: auto;
        padding-right: 2px;
    }
    .rops-pos-item {
        border: 1px solid #cfd6e2;
        border-radius: 7px;
        min-height: 168px;
        background: #d7f0d4;
        overflow: hidden;
        padding: 0;
        text-align: left;
        cursor: pointer;
        display: grid;
        grid-template-rows: 1fr;
        position: relative;
    }
    .rops-pos-item:nth-child(4n+2) { background: #cfe4f8; }
    .rops-pos-item:nth-child(4n+3) { background: #ead8c6; }
    .rops-pos-item:nth-child(4n+4) { background: #f6cbdc; }
    .rops-pos-item:hover { border-color: #4da7b4; box-shadow: 0 10px 24px rgba(16, 24, 40, .08); }
    .rops-pos-item[disabled] { opacity: .55; cursor: not-allowed; }
    .rops-pos-photo {
        background: transparent;
        display: grid;
        place-items: center;
        overflow: hidden;
        min-height: 0;
    }
    .rops-pos-photo img {
        width: 100%;
        height: 86px;
        object-fit: cover;
        display: block;
    }
    .rops-pos-photo span {
        color: #172033;
        font-size: 13px;
        font-weight: 800;
        position: absolute;
        right: 10px;
        top: 10px;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: rgba(23, 32, 51, .88);
        color: #fff;
    }
    .rops-pos-item-body {
        min-height: 168px;
        padding: 24px 16px 16px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 10px;
    }
    .rops-pos-item-title {
        color: #111827;
        font-size: 16px;
        font-weight: 800;
        line-height: 1.25;
    }
    .rops-pos-item-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .rops-pos-item-price {
        color: #007a5e;
        font-weight: 800;
    }
    .rops-pos-chip {
        display: inline-flex;
        align-items: center;
        min-height: 22px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .62);
        color: #2d7c88;
        padding: 0 8px;
        font-size: 12px;
        font-weight: 700;
    }
    .rops-pos-cart {
        display: grid;
        grid-template-rows: auto auto 1fr auto;
        min-height: 0;
        background: #fff;
    }
    .rops-pos-cart-tools {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 8px;
        padding: 10px 16px;
        border-bottom: 1px solid #e1e5ec;
    }
    .rops-pos-cart-tool {
        border: 1px solid #d8dde6;
        border-radius: 5px;
        background: #fff;
        min-height: 40px;
        font-weight: 700;
        color: #344054;
    }
    .rops-pos-cart-list {
        overflow: auto;
        padding: 0 16px;
    }
    .rops-pos-cart-row {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 10px;
        padding: 12px 0;
        border-bottom: 1px solid #e1e5ec;
    }
    .rops-pos-cart-row strong { font-size: 15px; }
    .rops-pos-cart-summary {
        border-top: 1px solid #d8dde6;
        padding: 16px;
    }
    .rops-pos-total-line {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        font-size: 16px;
    }
    .rops-pos-total-line strong { font-size: 22px; }
    .rops-pos-bottom-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    .rops-pos-bottom-actions .btn {
        min-height: 56px;
        font-size: 18px;
        font-weight: 800;
    }
    .rops-pos-modal {
        position: fixed;
        inset: 0;
        z-index: 1050;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }
    .rops-pos-modal.is-open { display: flex; }
    .rops-pos-modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(17, 24, 39, .58);
    }
    .rops-pos-modal-panel {
        position: relative;
        width: min(560px, 100%);
        max-height: calc(100vh - 48px);
        display: grid;
        grid-template-rows: auto 1fr auto;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 24px 70px rgba(17, 24, 39, .26);
        overflow: hidden;
    }
    .rops-pos-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 20px 24px;
        border-bottom: 1px solid #e1e5ec;
    }
    .rops-pos-modal-title {
        font-size: 22px;
        font-weight: 800;
    }
    .rops-pos-modal-close {
        width: 42px;
        height: 42px;
        border: 1px solid #d8dde6;
        border-radius: 6px;
        background: #fff;
        font-size: 24px;
        line-height: 1;
    }
    .rops-customer-panel {
        width: min(520px, calc(100vw - 40px));
        grid-template-rows: auto auto auto;
    }
    .rops-service-panel {
        width: min(520px, calc(100vw - 40px));
        grid-template-rows: auto auto;
    }
    .rops-select-panel {
        width: min(520px, calc(100vw - 40px));
        grid-template-rows: auto minmax(0, 1fr);
    }
    .rops-orders-panel {
        width: min(760px, calc(100vw - 40px));
        grid-template-rows: auto minmax(0, 1fr);
    }
    .rops-service-orders-list {
        display: grid;
        gap: 10px;
        max-height: 560px;
        overflow: auto;
        padding: 18px;
        background: #f8fafc;
    }
    .rops-order-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 14px;
        padding: 14px;
        border: 1px solid #d8dde6;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
    }
    .rops-order-card-main strong {
        display: block;
        font-size: 18px;
        font-weight: 800;
        color: #111827;
        text-transform: capitalize;
    }
    .rops-order-card-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
        color: #667085;
        font-size: 12px;
        font-weight: 700;
    }
    .rops-order-card-meta span {
        padding: 4px 8px;
        border-radius: 999px;
        background: #f1f5f9;
    }
    .rops-order-card-actions {
        display: grid;
        gap: 8px;
        min-width: 142px;
        align-content: start;
    }
    .rops-order-card-actions button {
        min-height: 36px;
        border: 1px solid #d8dde6;
        border-radius: 6px;
        background: #fff;
        color: #172033;
        font-weight: 800;
    }
    .rops-order-card-actions button:hover { border-color: #49a8b5; }
    .rops-order-card-actions .is-primary {
        background: #49a8b5;
        border-color: #49a8b5;
        color: #fff;
    }
    .rops-order-card-actions .is-success {
        background: #10a75a;
        border-color: #10a75a;
        color: #fff;
    }
    .rops-guests-panel {
        width: min(420px, calc(100vw - 40px));
        grid-template-rows: auto auto auto;
    }
    .rops-guests-body {
        display: grid;
        grid-template-columns: 84px 1fr 84px;
        align-items: center;
        gap: 16px;
        padding: 28px 34px;
        text-align: center;
    }
    .rops-guests-body button {
        width: 84px;
        height: 64px;
        border: 1px solid #d8dde6;
        border-radius: 8px;
        background: #fff;
        color: #172033;
        font-size: 28px;
        font-weight: 800;
    }
    .rops-guests-body strong {
        display: grid;
        place-items: center;
        min-height: 64px;
        border: 1px solid #49a8b5;
        border-radius: 8px;
        background: #e8f7fa;
        color: #172033;
        font-size: 30px;
    }
    .rops-select-list {
        display: grid;
        gap: 10px;
        max-height: 430px;
        overflow: auto;
        padding: 18px;
    }
    .rops-select-choice {
        min-height: 52px;
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: center;
        gap: 12px;
        border: 1px solid #d8dde6;
        border-radius: 6px;
        background: #fff;
        padding: 0 14px;
        color: #344054;
        font-weight: 800;
        text-align: left;
    }
    .rops-select-choice small {
        color: #667085;
        font-weight: 700;
    }
    .rops-select-choice.is-active,
    .rops-select-choice:hover {
        border-color: #49a8b5;
        background: #e8f7fa;
    }
    .rops-select-choice[disabled] {
        opacity: .48;
        cursor: not-allowed;
    }
    .rops-service-modal-body {
        padding: 24px;
    }
    .rops-service-section-title {
        margin: 0 0 12px;
        color: #344054;
        font-weight: 800;
    }
    .rops-service-section-title:not(:first-child) { margin-top: 18px; }
    .rops-service-choice {
        min-width: 186px;
        min-height: 50px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 0 16px;
        border: 1px solid #49a8b5;
        border-radius: 6px;
        background: #fff;
        color: #344054;
        font-size: 18px;
        font-weight: 700;
    }
    .rops-service-choice span {
        width: 16px;
        height: 16px;
        border: 1px solid #d8dde6;
        border-radius: 50%;
        background: #fff;
        box-shadow: inset 0 0 0 4px #fff;
    }
    .rops-service-choice.is-active {
        background: #e8f7fa;
    }
    .rops-service-choice.is-active span {
        border-color: #49a8b5;
        background: #49a8b5;
    }
    .rops-customer-body {
        padding: 24px 18px;
    }
    .rops-field-label {
        display: block;
        margin: 0 0 8px;
        color: #344054;
        font-weight: 800;
    }
    .rops-phone-entry {
        display: grid;
        grid-template-columns: 78px 1fr;
        align-items: center;
        min-height: 42px;
        margin: 0 0 24px;
        border: 1px solid #d8dde6;
        border-radius: 6px;
        font-size: 18px;
        overflow: hidden;
    }
    .rops-phone-entry span {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        border-right: 1px solid #b8c0cc;
        color: #172033;
    }
    .rops-phone-entry input,
    .rops-customer-text {
        width: 100%;
        min-height: 46px;
        border: 0;
        outline: 0;
        padding: 0 12px;
        font-size: 18px;
        color: #172033;
        background: #fff;
    }
    .rops-customer-text {
        border: 1px solid #4da7b4;
        border-radius: 6px;
    }
    .rops-phone-entry input::placeholder,
    .rops-customer-text::placeholder { color: #a7adb7; }
    .rops-customer-name-row {
        display: grid;
        grid-template-columns: 78px 1fr;
        align-items: center;
        min-height: 62px;
        margin: 10px 0 0;
        color: #667085;
        font-weight: 700;
    }
    .rops-customer-summary {
        min-height: 140px;
    }
    .rops-customer-summary[hidden] { display: none; }
    .rops-customer-summary-top,
    .rops-customer-summary-bottom {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
    }
    .rops-customer-summary-top strong {
        display: block;
        margin-bottom: 8px;
        font-size: 18px;
    }
    .rops-customer-summary-top span {
        display: block;
        color: #667085;
        font-size: 16px;
    }
    .rops-customer-summary-top button,
    .rops-customer-summary-bottom button {
        border: 0;
        background: transparent;
        color: #2f8f9d;
        font-size: 18px;
        font-weight: 700;
    }
    .rops-customer-summary-bottom {
        margin-top: 18px;
        align-items: center;
    }
    .rops-customer-summary-bottom button {
        color: #e11d48;
        font-size: 15px;
    }
    .rops-customer-summary-bottom span {
        font-size: 18px;
    }
    .rops-customer-summary-bottom a {
        color: #2f8f9d;
        text-decoration: underline;
    }
    .rops-customer-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        border-top: 1px solid #e1e5ec;
    }
    .rops-customer-actions button {
        min-height: 56px;
        border: 0;
        background: #fff;
        color: #172033;
        font-size: 18px;
        font-weight: 700;
    }
    .rops-customer-actions button + button {
        border-left: 1px solid #e1e5ec;
        color: #98a2b3;
    }
    .rops-customer-actions button + button.is-ready {
        color: #fff;
        background: #4da7b4;
    }
    .rops-pos-option-body {
        overflow: auto;
        padding: 18px 24px 8px;
    }
    .rops-option-group {
        margin-bottom: 18px;
    }
    .rops-option-group-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
    }
    .rops-option-group-head strong { font-size: 16px; }
    .rops-option-group-head span {
        color: var(--rops-muted);
        font-size: 12px;
        font-weight: 700;
    }
    .rops-option-choices {
        display: grid;
        gap: 8px;
    }
    .rops-option-choice {
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 12px;
        min-height: 54px;
        border: 1px solid #d8dde6;
        border-radius: 7px;
        padding: 10px 12px;
        cursor: pointer;
        background: #fff;
    }
    .rops-option-choice:has(input:checked) {
        border-color: #4da7b4;
        background: #eef9fb;
    }
    .rops-option-choice small {
        display: block;
        color: #007a5e;
        font-weight: 800;
    }
    .rops-option-qty {
        width: 64px;
        height: 36px;
        border: 1px solid #d8dde6;
        border-radius: 5px;
        text-align: center;
    }
    .rops-pos-option-footer {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
        border-top: 1px solid #e1e5ec;
    }
    .rops-pos-option-footer .btn {
        min-height: 58px;
        border-radius: 0;
        font-size: 17px;
        font-weight: 800;
    }
    body.rops-pos-kiosk-mode {
        overflow: hidden;
    }
    body.rops-pos-kiosk-mode .rops-pos-full {
        position: fixed;
        inset: 0;
        z-index: 1040;
        margin: 0;
        min-height: 100vh;
        height: 100vh;
        display: grid;
        grid-template-rows: auto auto 1fr;
    }
    body.rops-pos-kiosk-mode .rops-pos-main {
        height: auto;
        min-height: 0;
    }
    body.rops-pos-kiosk-mode .rops-pos-browser,
    body.rops-pos-kiosk-mode .rops-pos-cart,
    body.rops-pos-kiosk-mode .rops-pos-catalog {
        min-height: 0;
    }
    .rops-pos-full {
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        background: #f3f5f8;
    }
    .rops-pos-topbar {
        grid-template-columns: minmax(430px, 1.15fr) auto minmax(430px, 1fr);
        min-height: 58px;
        padding: 7px 10px;
        background: #2f4054;
        box-shadow: 0 1px 0 rgba(17, 24, 39, .2);
    }
    .rops-pos-nav,
    .rops-pos-actions {
        gap: 6px;
        min-width: 0;
    }
    .rops-pos-create,
    .rops-pos-service label,
    .rops-pos-link {
        min-height: 42px;
        border-radius: 5px;
        padding: 0 14px;
        font-size: 14px;
    }
    .rops-pos-create {
        min-width: 128px;
        justify-content: center;
        background: #49a8b5;
        box-shadow: inset 0 -1px 0 rgba(0, 0, 0, .08);
    }
    .rops-pos-service input:checked + label {
        background: #49a8b5;
        box-shadow: inset 0 -1px 0 rgba(0, 0, 0, .08);
    }
    .rops-pos-brand {
        min-width: 260px;
        font-size: 15px;
    }
    .rops-pos-brand-mark {
        width: 30px;
        height: 30px;
        font-size: 11px;
    }
    .rops-pos-actions .rops-pos-link:not(.rops-pos-fullscreen-button) {
        max-width: 128px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .rops-pos-main {
        grid-template-columns: minmax(700px, 1fr) minmax(430px, 34vw);
        height: calc(100vh - 118px);
        min-height: 620px;
    }
    .rops-pos-search {
        position: relative;
        padding: 10px 12px;
        background: #f3f5f8;
        border-bottom: 1px solid #dde3ed;
    }
    .rops-pos-search::before {
        content: "";
        position: absolute;
        left: 26px;
        top: 50%;
        transform: translateY(-50%);
        width: 14px;
        height: 14px;
        border: 2px solid #667085;
        border-radius: 50%;
        pointer-events: none;
    }
    .rops-pos-search::after {
        content: "";
        position: absolute;
        left: 38px;
        top: calc(50% + 7px);
        width: 7px;
        height: 2px;
        background: #667085;
        transform: rotate(45deg);
        transform-origin: left center;
        pointer-events: none;
    }
    .rops-pos-search input {
        height: 46px;
        padding-left: 40px;
        border-radius: 6px;
        border-color: #cfd7e4;
        color: #1f2937;
        box-shadow: 0 1px 1px rgba(16, 24, 40, .03);
    }
    .rops-pos-search input:focus {
        border-color: #49a8b5;
        box-shadow: 0 0 0 3px rgba(73, 168, 181, .16);
    }
    .rops-pos-browser {
        grid-template-columns: 216px 1fr;
        gap: 10px;
        padding: 10px 12px 12px;
    }
    .rops-category-rail {
        border-radius: 6px;
        background: #304258;
        padding: 0;
        box-shadow: inset -1px 0 0 rgba(255, 255, 255, .04);
    }
    .rops-category-button {
        min-height: 54px;
        border-left-width: 5px;
        border-bottom: 1px solid rgba(255, 255, 255, .06);
        padding: 10px 16px;
        font-size: 14px;
        color: #f8fafc;
        transition: background .14s ease, border-color .14s ease;
    }
    .rops-category-button:hover {
        background: rgba(255, 255, 255, .08);
    }
    .rops-category-button.is-active {
        background: #4ba8b4;
        border-left-color: #8bc34a;
    }
    .rops-pos-item-grid {
        grid-template-columns: repeat(auto-fill, minmax(236px, 1fr));
        gap: 10px;
        padding: 0 2px 2px 0;
    }
    .rops-pos-item {
        min-height: 164px;
        border: 1px solid rgba(33, 56, 84, .18);
        border-radius: 6px;
        background: #cfeecf;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
        transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
    }
    .rops-pos-item:nth-child(4n+2) { background: #cfe5f8; }
    .rops-pos-item:nth-child(4n+3) { background: #ecd9c4; }
    .rops-pos-item:nth-child(4n+4) { background: #f4c6dc; }
    .rops-pos-item:hover {
        transform: translateY(-1px);
        border-color: rgba(47, 64, 84, .55);
        box-shadow: 0 10px 24px rgba(16, 24, 40, .12);
    }
    .rops-pos-item-body {
        min-height: 164px;
        padding: 26px 16px 16px;
        align-items: stretch;
    }
    .rops-pos-item-title {
        display: block;
        min-height: 42px;
        color: #101828;
        font-size: 17px;
        font-weight: 800;
        line-height: 1.25;
    }
    .rops-pos-item .rops-muted {
        color: rgba(23, 32, 51, .68);
        font-size: 12px;
        font-weight: 700;
        margin-top: 4px;
    }
    .rops-pos-photo span {
        right: 12px;
        top: 12px;
        width: 27px;
        height: 27px;
        font-size: 12px;
        background: rgba(16, 24, 40, .9);
    }
    .rops-pos-item-meta {
        min-height: 28px;
    }
    .rops-pos-item-price {
        color: #006b56;
        font-size: 14px;
        font-weight: 800;
    }
    .rops-pos-chip {
        min-height: 24px;
        background: rgba(255, 255, 255, .72);
        color: #256f79;
        font-size: 11px;
        text-transform: uppercase;
    }
    .rops-pos-cart {
        border-left: 1px solid #dde3ed;
        box-shadow: -8px 0 28px rgba(16, 24, 40, .04);
    }
    .rops-pos-cart .rops-card-header {
        min-height: 58px;
        padding: 14px 18px;
        background: #fff;
        border-bottom-color: #dde3ed;
        font-size: 14px;
    }
    .rops-pos-cart-tools {
        gap: 8px;
        padding: 12px 16px;
        background: #fbfcfe;
    }
    .rops-pos-cart-tool {
        min-height: 42px;
        border-radius: 6px;
        color: #344054;
        background: #fff;
        font-size: 14px;
        transition: border-color .12s ease, box-shadow .12s ease;
    }
    .rops-pos-cart-tool:hover {
        border-color: #49a8b5;
        box-shadow: 0 1px 5px rgba(16, 24, 40, .08);
    }
    .rops-selected-tool {
        border-color: #49a8b5;
        background: #e8f7fa;
    }
    .rops-pos-cart .rops-card-body {
        padding: 12px 16px;
        background: #fbfcfe;
    }
    .rops-pos-cart .form-control {
        border-radius: 5px;
        border-color: #d6dde8;
        font-size: 14px;
    }
    .rops-pos-cart-list {
        padding: 0 16px;
        background: #fff;
    }
    .rops-pos-cart-row {
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: start;
        gap: 12px;
        padding: 14px 0;
        border-bottom-color: #e4e8ef;
    }
    .rops-pos-cart-row {
        grid-template-columns: minmax(0, 1fr) auto auto;
        align-items: center;
    }
    .rops-cart-main {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 10px;
        min-width: 0;
    }
    .rops-cart-qty {
        display: inline-grid;
        place-items: center;
        min-width: 34px;
        height: 28px;
        border-radius: 5px;
        background: #f2f4f7;
        color: #344054;
        font-weight: 800;
        font-size: 13px;
    }
    .rops-cart-copy {
        min-width: 0;
    }
    .rops-pos-cart-row strong {
        color: #101828;
        font-size: 14px;
        line-height: 1.3;
    }
    .rops-cart-modifiers {
        color: #667085;
        font-size: 12px;
        line-height: 1.35;
        margin-top: 2px;
    }
    .rops-cart-note {
        color: #8a4b00;
        font-size: 12px;
        line-height: 1.35;
        margin-top: 2px;
    }
    .rops-cart-controls {
        display: grid;
        grid-template-columns: 30px 28px 30px;
        align-items: center;
        gap: 4px;
        justify-items: center;
    }
    .rops-cart-control {
        width: 30px;
        height: 30px;
        border: 1px solid #d8dde6;
        border-radius: 6px;
        background: #fff;
        color: #1f2937;
        font-size: 18px;
        font-weight: 800;
        line-height: 1;
    }
    .rops-cart-control:hover {
        border-color: #49a8b5;
        background: #eef9fb;
    }
    .rops-cart-control:disabled {
        opacity: .45;
        cursor: not-allowed;
    }
    .rops-cart-price {
        display: grid;
        justify-items: end;
        gap: 2px;
        min-width: 72px;
    }
    .rops-cart-link {
        border: 0;
        background: transparent;
        color: #327f8b;
        padding: 0;
        font-size: 12px;
        font-weight: 700;
    }
    .rops-cart-link.is-danger {
        color: #d92d20;
    }
    .rops-cart-link:disabled {
        opacity: .45;
        cursor: not-allowed;
    }
    .rops-pos-cart-summary {
        padding: 16px;
        background: #fff;
        box-shadow: 0 -8px 24px rgba(16, 24, 40, .06);
    }
    .rops-pos-total-line {
        margin-bottom: 10px;
        color: #344054;
        font-size: 14px;
    }
    .rops-pos-total-line strong {
        color: #101828;
        font-size: 23px;
    }
    .rops-pos-cart-summary .btn-link {
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
    }
    .rops-pos-bottom-actions {
        gap: 12px;
    }
    .rops-pos-bottom-actions .btn,
    #rops-kitchen-order {
        min-height: 54px;
        border: 0;
        border-radius: 6px;
        font-size: 17px;
        font-weight: 800;
        box-shadow: inset 0 -1px 0 rgba(0, 0, 0, .08);
    }
    #rops-payment-order {
        background: #4ba8b4;
    }
    #rops-submit-order {
        background: #10a75a;
    }
    #rops-kitchen-order {
        background: #ffad5a;
        color: #2b1a05;
    }
    .rops-toast {
        position: relative;
        z-index: 1;
        margin: 8px 12px 0;
        border-radius: 6px;
        box-shadow: 0 8px 20px rgba(16, 24, 40, .08);
    }
    .rops-pos-modal-panel {
        border-radius: 7px;
    }
    .rops-pos-modal-title {
        color: #101828;
    }
    .rops-option-choice {
        border-radius: 6px;
    }
    @media (max-width: 991px) {
        .rops-toolbar { align-items: flex-start; flex-direction: column; }
        .rops-grid-2, .rops-grid-3, .rops-grid-4 { grid-template-columns: 1fr; }
        .rops-pos-topbar { grid-template-columns: 1fr; }
        .rops-pos-brand { justify-content: flex-start; }
        .rops-pos-main { grid-template-columns: 1fr; height: auto; }
        .rops-pos-browser { grid-template-columns: 1fr; }
        .rops-category-rail { display: flex; gap: 6px; overflow-x: auto; }
        .rops-category-button { width: auto; min-width: 150px; border-left: 0; border-bottom: 4px solid transparent; }
    }
</style>

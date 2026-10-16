const confirmation = document.getElementById('confirm-action');
let pendingConfirmation = null;
const busyButtons = new Map();
let feedbackTimer;
const toast = (message, type = 'success', persistent = false) => {
    const box = document.getElementById('feedback');
    if (!box) return;
    clearTimeout(feedbackTimer);
    box.hidden = false;
    box.classList.remove('success', 'info', 'warning', 'error');
    box.classList.add(type);
    box.setAttribute('role', type === 'error' ? 'alert' : 'status');
    box.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
    box.querySelector('[data-feedback-text]').textContent = message;
    if (!persistent) feedbackTimer = setTimeout(() => { if (!box.matches(':focus-within')) box.hidden = true; }, 6000);
};
const reveal = field => {
    for (let parent = field.parentElement; parent; parent = parent.parentElement) {
        if (parent.tagName === 'DETAILS') parent.open = true;
    }
};
const fieldError = (field, message) => {
    if (!field.id) field.id = 'field-' + field.name;
    const id = field.id + '-error';
    let error = document.getElementById(id);
    if (!error) {
        error = document.createElement('p'); error.id = id; error.className = 'field-error';
        const check = field.closest('.check');
        if (check) check.after(error); else (field.closest('.field') || field.parentElement).append(error);
    }
    error.textContent = message;
    field.setAttribute('aria-invalid', 'true');
    const descriptions = new Set((field.getAttribute('aria-describedby') || '').split(' ').filter(Boolean));
    descriptions.add(id); field.setAttribute('aria-describedby', [...descriptions].join(' '));
    reveal(field);
};
const clearFieldError = field => {
    field.removeAttribute('aria-invalid');
    const id = field.id + '-error'; document.getElementById(id)?.remove();
    field.setAttribute('aria-describedby', (field.getAttribute('aria-describedby') || '').split(' ').filter(value => value !== id).join(' '));
};
const phoneValidity = field => {
    let value = field.value.replace(/[\s()\-]/gu, '');
    if (value.startsWith('08')) value = '62' + value.slice(1);
    value = value.replace(/^\++/, '');
    field.setCustomValidity(value && !/^[1-9][0-9]{7,14}$/.test(value) ? 'Isi nomor WhatsApp valid, misalnya 081234567890.' : '');
};
let firstInvalid;
document.addEventListener('invalid', event => {
    const field = event.target; event.preventDefault();
    const label = field.labels?.[0]?.textContent.trim().split('(opsional)')[0].trim() || 'Data ini';
    const message = field.validity.customError ? field.validationMessage : field.validity.valueMissing ? (field.type === 'checkbox' ? 'Konfirmasikan persetujuan ini sebelum melanjutkan.' : `${label} wajib diisi.`) : field.validity.typeMismatch ? `${label} tidak valid. Periksa formatnya.` : `${label} tidak sesuai ketentuan pada formulir.`;
    fieldError(field, message);
    if (!firstInvalid) {
        firstInvalid = field;
        requestAnimationFrame(() => { const field = firstInvalid; firstInvalid = null; field?.focus(); });
    }
}, true);
document.addEventListener('input', event => {
    const field = event.target;
    if (field.name === 'phone') phoneValidity(field);
    if (field.validity?.valid) clearFieldError(field);
});
document.addEventListener('focusout', event => {
    if (event.target.name !== 'phone') return;
    phoneValidity(event.target);
    if (!event.target.validity.valid) fieldError(event.target, event.target.validationMessage);
});
const summary = document.querySelector('[data-errors]');
if (summary) {
    let scope = summary.dataset.section ? document.getElementById(summary.dataset.section) || document : document;
    let focus;
    for (const item of summary.querySelectorAll('[data-error-field]')) {
        const candidates = [...scope.querySelectorAll(`[name="${CSS.escape(item.dataset.errorField)}"]`)].filter(field => field.type !== 'hidden');
        const field = candidates.find(field => {
            const record = field.form?.querySelector('[name="id"]')?.value || '';
            return !summary.dataset.section || record === summary.dataset.record;
        });
        const link = item.querySelector('a');
        if (field) {
            fieldError(field, link.textContent); link.href = '#' + field.id;
            link.addEventListener('click', () => { reveal(field); field.focus(); });
            focus ||= field;
        } else link.replaceWith(document.createTextNode(link.textContent));
    }
    (focus || summary).focus();
}
const restoreForm = form => {
    for (const button of form.querySelectorAll('button[type="submit"]')) {
        const original = busyButtons.get(button);
        if (original) { button.innerHTML = original.html; button.disabled = original.disabled; busyButtons.delete(button); }
    }
    delete form.dataset.submitting; form.removeAttribute('aria-busy');
};
document.addEventListener('submit', event => {
    const form = event.target;
    if (form.hasAttribute('wire:submit') || event.defaultPrevented) return;
    if (form.dataset.submitting) { event.preventDefault(); return; }
    form.querySelectorAll('[name="phone"]').forEach(phoneValidity);
    if (!form.reportValidity()) { event.preventDefault(); return; }
    if (!navigator.onLine) {
        event.preventDefault(); toast('Koneksi terputus. Periksa status transaksi sebelum mengirim ulang.', 'warning', true); return;
    }
    const confirmingInactiveBranch = form.dataset.confirmInactiveTitle && form.dataset.wasActive === '1' && !form.querySelector('[name="active"]')?.checked;
    const confirmTitle = confirmingInactiveBranch ? form.dataset.confirmInactiveTitle : form.dataset.confirmTitle;
    const confirmMessage = confirmingInactiveBranch ? form.dataset.confirmInactiveMessage : form.dataset.confirmMessage;
    const confirmButton = confirmingInactiveBranch ? form.dataset.confirmInactiveButton : form.dataset.confirmButton;
    if (confirmTitle && !form.dataset.confirmed) {
        if (confirmation?.showModal) {
            event.preventDefault();
            pendingConfirmation = { form, submitter: event.submitter };
            confirmation.querySelector('h2').textContent = confirmTitle;
            confirmation.querySelector('[data-confirm-message]').textContent = confirmMessage;
            const details = confirmation.querySelector('dl'); details.replaceChildren();
            const fixed = form.dataset.confirmContext;
            if (fixed) { const term = document.createElement('dt'); term.textContent = 'Konteks'; const description = document.createElement('dd'); description.textContent = fixed; details.append(term, description); }
            for (const field of form.querySelectorAll('[data-confirm-label]')) {
                const term = document.createElement('dt'); const value = document.createElement('dd');
                term.textContent = field.dataset.confirmLabel;
                value.textContent = field.tagName === 'SELECT' ? field.selectedOptions[0]?.textContent : field.type === 'datetime-local' ? field.value.replace(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}:\d{2}(?::\d{2})?)$/, '$3/$2/$1 $4 WIB') : field.value;
                if (field.type === 'number') value.textContent = 'Rp' + new Intl.NumberFormat('id-ID').format(Number(field.value));
                details.append(term, value);
            }
            confirmation.querySelector('[data-confirm-accept]').textContent = confirmButton;
            confirmation.showModal(); confirmation.querySelector('[data-confirm-cancel]').focus();
            return;
        }
        if (!window.confirm(confirmMessage)) { event.preventDefault(); return; }
    }
    delete form.dataset.confirmed;
    if (form.action.endsWith('/checkout') && form.querySelector('[name="marketing_consent"]')?.checked && window.fbq) {
        window.fbq('track', 'InitiateCheckout', { currency: 'IDR', value: Number(document.querySelector('[data-price]')?.dataset.price) });
    }
    form.dataset.submitting = '1'; form.setAttribute('aria-busy', 'true');
    for (const button of form.querySelectorAll('button[type="submit"]')) {
        busyButtons.set(button, { html: button.innerHTML, disabled: button.disabled });
        button.disabled = true; button.textContent = 'Memproses…';
    }
    queueMicrotask(() => { if (event.defaultPrevented) restoreForm(form); });
});
confirmation?.querySelector('[data-confirm-cancel]').addEventListener('click', () => confirmation.close());
confirmation?.addEventListener('close', () => { pendingConfirmation = null; });
confirmation?.querySelector('[data-confirm-accept]').addEventListener('click', () => {
    if (!pendingConfirmation) return;
    const { form, submitter } = pendingConfirmation;
    form.dataset.confirmed = '1';
    if (submitter) form.requestSubmit(submitter); else form.requestSubmit();
    confirmation.close(); delete form.dataset.confirmed;
});
window.addEventListener('pageshow', event => {
    if (event.persisted && document.querySelector('form[data-submitting]')) {
        document.querySelectorAll('form[data-submitting]').forEach(restoreForm);
        toast('Halaman dipulihkan. Periksa status sebelum mengirim ulang tindakan sebelumnya.', 'warning', true);
    }
});
document.addEventListener('click', async event => {
    const close = event.target.closest('[data-dismiss-feedback]');
    if (close) { close.closest('.feedback-toast').hidden = true; return; }
    const copy = event.target.closest('[data-copy]');
    if (copy) {
        const status = document.getElementById('copy-status');
        copy.disabled = true;
        try {
            await navigator.clipboard.writeText(copy.dataset.copy);
            if (status) status.textContent = '';
            toast(copy.dataset.copyLabel || 'Nilai berhasil disalin.');
        } catch {
            const message = 'Belum berhasil disalin. Salin nilai berikut secara manual.';
            if (status) status.textContent = `${message} ${copy.dataset.copy}`;
            toast(message, 'warning');
        } finally { copy.disabled = false; }
    }
    const scan = event.target.closest('[data-start-scanner]');
    if (scan && !scan.disabled) {
        scan.disabled = true;
        const status = document.getElementById('scanner-status');
        status.textContent = 'Membuka kamera. Izinkan akses kamera atau gunakan input kode.';
        try { const { startScanner } = await import('./scanner.js'); await startScanner(); }
        catch { scan.disabled = false; status.textContent = 'Pemindai belum dapat dimuat. Masukkan kode voucher secara manual.'; }
    }
});
const adminMenu = document.getElementById('admin-menu');
document.querySelector('[data-open-menu]')?.addEventListener('click', () => adminMenu?.showModal());
document.querySelector('[data-close-menu]')?.addEventListener('click', () => adminMenu?.close());
adminMenu?.addEventListener('click', event => { if (event.target === adminMenu && event.clientX > adminMenu.getBoundingClientRect().right) adminMenu.close(); });
matchMedia('(min-width: 1024px)').addEventListener('change', event => { if (event.matches) adminMenu?.close(); });
const settingsGroups = [...document.querySelectorAll('[data-settings-group]')];
const selectSettings = id => {
    const selected = settingsGroups.find(group => group.id === id); if (!selected) return;
    for (const group of settingsGroups) group.open = group === selected;
    for (const link of document.querySelectorAll('[data-settings-link]')) {
        if (link.hash === '#' + id) link.setAttribute('aria-current', 'true'); else link.removeAttribute('aria-current');
    }
};
for (const link of document.querySelectorAll('[data-settings-link]')) link.addEventListener('click', () => selectSettings(link.hash.slice(1)));
for (const group of settingsGroups) group.addEventListener('toggle', () => {
    if (group.open) { selectSettings(group.id); history.replaceState(null, '', '#' + group.id); }
});
if (settingsGroups.length) {
    selectSettings(location.hash.slice(1) || settingsGroups.find(group => group.open)?.id || settingsGroups[0].id);
    window.addEventListener('hashchange', () => selectSettings(location.hash.slice(1)));
}
const connectListRecovery = (noticeId, retrySelector) => {
    const notice = document.getElementById(noticeId); if (!notice) return;
    const stale = () => {
        notice.hidden = false;
        document.querySelector('[data-order-results]')?.setAttribute('inert', '');
        notice.querySelector('p').textContent = 'Daftar belum berhasil diperbarui. Hasil sebelumnya belum sesuai pilihan terbaru. Periksa kembali daftar sebelum melanjutkan.';
    };
    window.Livewire.interceptRequest(({ onSend, onCancel, onFailure, onError, onSuccess }) => {
        onSend(() => document.querySelector('[data-order-results]')?.setAttribute('inert', ''));
        onCancel(() => { if (notice.hidden) document.querySelector('[data-order-results]')?.removeAttribute('inert'); });
        onFailure(stale);
        onError(({ preventDefault, response }) => {
            preventDefault(); stale();
            if ([401,403,419].includes(response.status)) notice.querySelector('p').textContent = 'Sesi atau akses sudah berubah. Muat ulang halaman untuk masuk kembali dan memeriksa daftar.';
        });
        onSuccess(() => { notice.hidden = true; document.querySelector('[data-order-results]')?.removeAttribute('inert'); });
    });
    notice.querySelector(retrySelector)?.addEventListener('click', () => window.Livewire.all()[0]?.$wire.$refresh());
};
const connectListRecoveries = () => {
    connectListRecovery('orders-network-error', '[data-retry-orders]');
    connectListRecovery('voucher-history-network-error', '[data-retry-voucher-history]');
};
if (window.Livewire) connectListRecoveries(); else document.addEventListener('livewire:init', connectListRecoveries, { once: true });

const tracking = document.querySelector('[data-meta-pixel]');
if (tracking && /^\d+$/.test(tracking.dataset.metaPixel)) {
    const marketing = document.querySelector('[name="marketing_consent"]');
    const enable = () => {
        if (window.fbq) return;
        const fbq = window.fbq = function () { fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments); };
        if (!window._fbq) window._fbq = fbq;
        fbq.push = fbq; fbq.loaded = true; fbq.version = '2.0'; fbq.queue = [];
        fbq('init', tracking.dataset.metaPixel);
        fbq('track', tracking.dataset.event, { currency: 'IDR', value: Number(tracking.dataset.price) });
        const script = document.createElement('script'); script.async = true; script.src = 'https://connect.facebook.net/en_US/fbevents.js'; document.head.append(script);
    };
    marketing?.addEventListener('change', () => { if (marketing.checked) enable(); });
    if (tracking.dataset.consented === '1' || marketing?.checked) enable();
}

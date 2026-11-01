import './bootstrap';
import './sound';

// ---------- Salin teks ke clipboard (dengan cadangan untuk browser yang menolak) ----------
function fallbackCopy(text) {
    const area = document.createElement('textarea');
    area.value = text;
    area.setAttribute('readonly', '');
    area.style.position = 'fixed';
    area.style.opacity = '0';
    document.body.appendChild(area);
    area.select();
    let ok = false;
    try { ok = document.execCommand('copy'); } catch {}
    document.body.removeChild(area);
    return ok;
}

async function copyText(text) {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return true;
        }
    } catch {}
    return fallbackCopy(text);
}

// ---------- Satu pendengar klik untuk menu mobile dan tombol salin ----------
document.addEventListener('click', async (event) => {
    // 1. data-toggle="id": tampil/sembunyikan elemen (menu mobile)
    const toggle = event.target.closest('[data-toggle]');
    if (toggle) {
        document.getElementById(toggle.dataset.toggle)?.classList.toggle('hidden');
    }

    // 2. data-copy="teks": salin teks
    const copy = event.target.closest('[data-copy]');
    if (copy) {
        event.preventDefault();
        if (!copy.dataset.label) copy.dataset.label = copy.textContent.trim();

        const ok = await copyText(copy.dataset.copy);
        copy.textContent = ok ? 'Tersalin ✓' : 'Gagal, salin manual';

        clearTimeout(copy._timer);
        copy._timer = setTimeout(() => {
            copy.textContent = copy.dataset.label;
        }, 1800);
    }
});

// Panduan penggunaan (onboarding tour).
// Semua pendengar dipasang di document (event delegation), sehingga tetap bekerja
// walau isi halaman diganti oleh sound.js saat bermain misi.

const MODAL_ID = 'onboarding';
let step = 0;

const modal = () => document.getElementById(MODAL_ID);
const stepsOf = (m) => [...m.querySelectorAll('[data-ob-step]')];
const isOpen = () => {
    const m = modal();
    return !!m && !m.classList.contains('hidden');
};

// ---------- Titik indikator ----------
function buildDots(m, total) {
    const wrap = m.querySelector('[data-ob-dots]');
    if (!wrap) return;
    wrap.replaceChildren();
    for (let i = 0; i < total; i++) {
        const dot = document.createElement('span');
        dot.dataset.obDot = String(i);
        dot.className = 'h-1.5 w-1.5 rounded-full bg-white/20 transition-all';
        wrap.append(dot);
    }
}

// ---------- Tampilkan langkah ke-n ----------
function render() {
    const m = modal();
    if (!m) return;

    const list = stepsOf(m);
    const last = list.length - 1;
    step = Math.max(0, Math.min(step, last));

    list.forEach((el, i) => el.classList.toggle('hidden', i !== step));

    m.querySelector('[data-ob-count]').textContent = `${step + 1} / ${list.length}`;
    m.querySelector('[data-ob-bar]').style.width = `${((step + 1) / list.length) * 100}%`;

    m.querySelectorAll('[data-ob-dot]').forEach((dot, i) => {
        dot.classList.toggle('bg-guard-500', i === step);
        dot.classList.toggle('w-4', i === step);
        dot.classList.toggle('bg-white/20', i !== step);
    });

    m.querySelector('[data-ob-prev]').classList.toggle('invisible', step === 0);
    m.querySelector('[data-ob-skip]').classList.toggle('invisible', step === last);
    m.querySelector('[data-ob-next]').textContent = step === last ? 'Selesai ✓' : 'Lanjut →';

    m.querySelector('[data-ob-body]').scrollTop = 0;
    m.querySelector('[data-ob-next]').focus({ preventScroll: true });
}

// ---------- Kembalikan latihan interaktif ke kondisi awal ----------
function resetDemos(m) {
    m.querySelectorAll('[data-ob-demo]').forEach((demo) => {
        if (demo.dataset.orig === undefined) {
            demo.dataset.orig = demo.innerHTML;
        } else {
            demo.innerHTML = demo.dataset.orig;
        }
    });
}

// ---------- Catat bahwa panduan sudah dilihat ----------
function markDone() {
    const m = modal();
    if (!m || m.dataset.completed === 'true') return;

    m.dataset.completed = 'true';

    fetch(m.dataset.completeUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': m.dataset.token,
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
        credentials: 'same-origin',
        keepalive: true, // tetap terkirim walau halaman langsung berpindah
    }).catch(() => {
        // Gagal diam-diam: panduan hanya muncul lagi di login berikutnya.
    });
}

// ---------- Buka dan tutup ----------
function open(startAt = 0) {
    const m = modal();
    if (!m) return;

    resetDemos(m);
    step = startAt;
    buildDots(m, stepsOf(m).length);

    m.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    render();
}

function close() {
    const m = modal();
    if (!m) return;

    m.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    markDone();
}

// ---------- Muncul otomatis saat pertama kali login ----------
document.addEventListener('DOMContentLoaded', () => {
    const m = modal();
    if (!m || m.dataset.auto !== 'true') return;

    // Jangan mengganggu saat sedang bermain misi.
    if (document.querySelector('[data-game]')) return;

    open(0);
});

// ---------- Klik ----------
document.addEventListener('click', (event) => {
    const target = event.target;

    // Tombol "❓ Panduan" di navbar
    if (target.closest('[data-onboarding-open]')) {
        open(0);
        return;
    }

    if (!isOpen()) return;
    const m = modal();
    const last = stepsOf(m).length - 1;

    if (target.closest('[data-ob-next]')) {
        if (step >= last) close();
        else { step += 1; render(); }
        return;
    }

    if (target.closest('[data-ob-prev]')) {
        step -= 1;
        render();
        return;
    }

    if (target.closest('[data-ob-skip]') || target.closest('[data-ob-finish]')) {
        close();
        return;
    }

    // Tautan di slide terakhir: tandai selesai, lalu biarkan browser berpindah halaman.
    if (target.closest('[data-ob-done]')) {
        markDone();
        return;
    }

    // Latihan 1: pilih tindakan di mini misi
    const choice = target.closest('[data-ob-choice]');
    if (choice) {
        const demo = choice.closest('[data-ob-demo]');
        const box = demo.querySelector('[data-ob-feedback]');
        const points = parseInt(choice.dataset.points, 10) || 0;

        const style = points > 0
            ? 'border-safe/40 bg-safe/10 text-emerald-100'
            : points < 0
                ? 'border-danger/40 bg-danger/10 text-rose-100'
                : 'border-white/15 bg-white/5 text-slate-100';

        const head = document.createElement('div');
        head.className = 'font-semibold';
        head.textContent = (points > 0 ? '✅ Pilihan bagus' : points < 0 ? '⚠️ Hati-hati' : '😐 Netral')
            + ` (${points > 0 ? '+' : ''}${points} poin)`;

        const body = document.createElement('div');
        body.className = 'mt-1';
        body.textContent = choice.dataset.text;

        box.className = `rise mt-3 rounded-xl border p-3 text-sm ${style}`;
        box.replaceChildren(head, body);

        demo.querySelectorAll('[data-ob-choice]').forEach((b) => {
            b.classList.remove('border-guard-500', 'bg-guard-500/10');
        });
        choice.classList.add('border-guard-500', 'bg-guard-500/10');
        return;
    }

    // Latihan 2: tandai aksi selesai
    const actionBtn = target.closest('[data-ob-action-btn]');
    if (actionBtn) {
        const demo = actionBtn.closest('[data-ob-demo]');
        const xp = parseInt(actionBtn.dataset.xp, 10) || 0;

        demo.querySelector('[data-ob-card]').classList.add('border-safe/40', 'bg-safe/10');

        const done = document.createElement('span');
        done.className = 'chip text-safe';
        done.textContent = '✓ Sudah dilakukan';
        actionBtn.replaceWith(done);

        demo.querySelector('[data-ob-demo-count]').textContent = '1/2 selesai';
        demo.querySelector('[data-ob-demo-bar]').style.width = '50%';
        demo.querySelector('[data-ob-demo-xp]').textContent = `⭐ ${xp} XP`;

        const pop = demo.querySelector('[data-ob-pop]');
        pop.textContent = `+${xp} XP! 🎉`;
        pop.classList.remove('hidden');
        pop.classList.add('rise');
    }
});

// ---------- Papan ketik: panah kiri/kanan dan Esc ----------
document.addEventListener('keydown', (event) => {
    if (!isOpen()) return;
    const last = stepsOf(modal()).length - 1;

    if (event.key === 'ArrowRight' && step < last) { step += 1; render(); }
    else if (event.key === 'ArrowLeft' && step > 0) { step -= 1; render(); }
    else if (event.key === 'Escape') { close(); }
});
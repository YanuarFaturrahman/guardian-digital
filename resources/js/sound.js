// Suara game Guardian Digital (Web Audio API, tanpa file audio)

// ===== PENGATUR VOLUME (ubah di sini) =====
const MASTER_VOLUME = 2.5;  // penguat keseluruhan. Naikkan jika masih pelan (maks sekitar 5)
const SFX_VOLUME = 1.6;     // efek suara benar/salah/klik
const MUSIC_VOLUME = 1.4;   // backsound
// ==========================================

const KEY = 'gd-sound';
let ctx = null;
let master = null;
let sfxBus = null;
let musicBus = null;
let musicTimer = null;
let chordIndex = 0;
let pendingSfx = null;

const isOn = () => {
    try { return localStorage.getItem(KEY) !== 'off'; } catch { return true; }
};
const save = (on) => {
    try { localStorage.setItem(KEY, on ? 'on' : 'off'); } catch {}
};
const gameRoot = () => document.querySelector('[data-game]');

function getCtx() {
    if (!ctx) {
        ctx = new (window.AudioContext || window.webkitAudioContext)();

        // Compressor: mencegah suara pecah saat dikeraskan
        const compressor = ctx.createDynamicsCompressor();
        compressor.threshold.value = -14;
        compressor.knee.value = 24;
        compressor.ratio.value = 8;
        compressor.attack.value = 0.003;
        compressor.release.value = 0.25;

        master = ctx.createGain();
        master.gain.value = MASTER_VOLUME;

        sfxBus = ctx.createGain();
        sfxBus.gain.value = SFX_VOLUME;

        musicBus = ctx.createGain();
        musicBus.gain.value = MUSIC_VOLUME;

        // sfx/musik -> master (penguat) -> compressor -> speaker
        sfxBus.connect(master);
        musicBus.connect(master);
        master.connect(compressor);
        compressor.connect(ctx.destination);
    }
    return ctx;
}

async function unlock() {
    const c = getCtx();
    if (c.state === 'suspended') {
        try { await c.resume(); } catch {}
    }
    return c.state === 'running';
}

// ---------- Efek suara ----------
function tone(freq, start, dur, {
    type = 'sine', gain = 0.3, glide = null,
    vibrato = null, lowpass = null, attack = 0.02,
} = {}) {
    const c = getCtx();
    const t = c.currentTime + start;
    const osc = c.createOscillator();
    const g = c.createGain();

    osc.type = type;
    osc.frequency.setValueAtTime(freq, t);
    if (glide) osc.frequency.exponentialRampToValueAtTime(glide, t + dur);

    g.gain.setValueAtTime(0.0001, t);
    g.gain.exponentialRampToValueAtTime(gain, t + attack);
    g.gain.exponentialRampToValueAtTime(0.0001, t + dur);

    let node = osc;
    if (lowpass) {
        const filter = c.createBiquadFilter();
        filter.type = 'lowpass';
        filter.frequency.value = lowpass;
        osc.connect(filter);
        node = filter;
    }
    node.connect(g).connect(sfxBus);

    if (vibrato) {
        const lfo = c.createOscillator();
        const depth = c.createGain();
        lfo.frequency.value = vibrato.rate;
        depth.gain.value = vibrato.depth;
        lfo.connect(depth).connect(osc.frequency);
        lfo.start(t);
        lfo.stop(t + dur + 0.05);
    }

    osc.start(t);
    osc.stop(t + dur + 0.05);
}

const SFX = {
    click: () => tone(520, 0, 0.08, { type: 'square', gain: 0.12 }),

    // CERIA: arpeggio naik cepat + kilau
    good: () => {
        [523.25, 659.25, 783.99, 1046.5, 1318.5].forEach((f, i) => {
            tone(f, i * 0.07, 0.35, { type: 'triangle', gain: 0.35 });
            tone(f * 2, i * 0.07, 0.25, { type: 'sine', gain: 0.1 });
        });
        [1046.5, 1318.5, 1568].forEach((f) =>
            tone(f, 0.4, 0.5, { type: 'triangle', gain: 0.25 }));
    },

    // NETRAL: dua nada lembut
    neutral: () => {
        tone(440, 0, 0.22, { type: 'sine', gain: 0.35 });
        tone(392, 0.2, 0.3, { type: 'sine', gain: 0.3 });
    },

    // SEDIH: turun, redup, bergetar
    bad: () => {
        [[233.08, 0.00, 0.32], [220.00, 0.36, 0.32], [207.65, 0.72, 0.32]]
            .forEach(([f, s, d]) =>
                tone(f, s, d, { type: 'sawtooth', gain: 0.3, lowpass: 800, attack: 0.05 }));
        tone(196, 1.08, 1.0, {
            type: 'sawtooth', gain: 0.32, lowpass: 750, glide: 150, attack: 0.05,
            vibrato: { rate: 6, depth: 6 },
        });
    },

    // MENANG
    win: () => {
        [523.25, 523.25, 659.25, 783.99, 1046.5].forEach((f, i) =>
            tone(f, i * 0.12, 0.3, { type: 'triangle', gain: 0.35 }));
        [523.25, 659.25, 783.99, 1046.5].forEach((f) =>
            tone(f, 0.65, 0.9, { type: 'triangle', gain: 0.22 }));
        [1568, 2093].forEach((f, i) =>
            tone(f, 0.7 + i * 0.1, 0.5, { type: 'sine', gain: 0.1 }));
    },

    // KALAH
    lose: () => {
        [440, 392, 349.23, 329.63].forEach((f, i) =>
            tone(f, i * 0.28, 0.45, { type: 'sawtooth', gain: 0.25, lowpass: 850, attack: 0.05 }));
        tone(220, 1.2, 1.4, {
            type: 'sawtooth', gain: 0.26, lowpass: 650, glide: 165, attack: 0.08,
            vibrato: { rate: 5, depth: 5 },
        });
    },
};

function play(name) {
    if (!isOn() || !ctx || ctx.state !== 'running') return;
    SFX[name]?.();
}

// ---------- Backsound ambient ----------
const CHORDS = [
    [220, 261.63, 329.63],
    [174.61, 220, 261.63],
    [261.63, 329.63, 392],
    [196, 246.94, 293.66],
];

function padNote(freq, dur) {
    const c = getCtx();
    const t = c.currentTime;

    // Dua osilator sedikit berbeda nada = suara lebih tebal dan terdengar jelas
    [0, 4].forEach((detune) => {
        const osc = c.createOscillator();
        const g = c.createGain();
        osc.type = 'triangle';
        osc.frequency.value = freq;
        osc.detune.value = detune;
        g.gain.setValueAtTime(0.0001, t);
        g.gain.linearRampToValueAtTime(0.12, t + 1.2); // volume dasar musik
        g.gain.linearRampToValueAtTime(0.0001, t + dur);
        osc.connect(g).connect(musicBus);
        osc.start(t);
        osc.stop(t + dur + 0.1);
    });
}

function playChord() {
    CHORDS[chordIndex % CHORDS.length].forEach((f) => padNote(f, 5));
    chordIndex++;
}

function startMusic() {
    if (musicTimer || !isOn()) return;
    playChord();
    musicTimer = setInterval(playChord, 4000);
}

function stopMusic() {
    clearInterval(musicTimer);
    musicTimer = null;
}

function renderToggle() {
    document.querySelectorAll('[data-sound-label]').forEach((el) => {
        el.textContent = isOn() ? 'Nyala' : 'Mati';
    });
}

// Dipanggil setiap kali halaman (atau adegan) baru tampil
function applyPage() {
    renderToggle();
    const root = gameRoot();
    if (!root) { stopMusic(); return; }

    if (root.dataset.music === 'off') stopMusic();
    else if (ctx && ctx.state === 'running') startMusic();

    const sfx = root.dataset.sfx;
    if (sfx) play(sfx);
}

// ---------- Muat pertama kali ----------
async function activate() {
    if (!gameRoot() || !isOn()) return;
    if (await unlock()) {
        if (pendingSfx) { play(pendingSfx); pendingSfx = null; }
        applyPage();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    renderToggle();
    const root = gameRoot();
    if (!root) return;

    pendingSfx = root.dataset.sfx || null;
    ['pointerdown', 'keydown'].forEach((evt) =>
        document.addEventListener(evt, activate, { once: true }));
    activate();
});

// ---------- Tombol suara dan klik pilihan ----------
document.addEventListener('click', async (event) => {
    const toggle = event.target.closest('[data-sound-toggle]');
    if (toggle) {
        const turnOn = !isOn();
        save(turnOn);
        renderToggle();
        if (turnOn) {
            await unlock();
            applyPage();
            play('click');
        } else {
            stopMusic();
        }
        return;
    }
    if (event.target.closest('[data-choice]')) play('click');
});

// ---------- Kirim pilihan tanpa memuat ulang halaman ----------
document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-choice-form]')) return;

    event.preventDefault();
    await unlock();

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) throw new Error('gagal');

        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');

        document.title = doc.title;
        document.body.innerHTML = doc.body.innerHTML;
        history.pushState({}, '', res.url);
        window.scrollTo(0, 0);
        applyPage();
    } catch {
        form.submit();
    }
});

window.addEventListener('popstate', () => location.reload());
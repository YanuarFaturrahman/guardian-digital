<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\BuildsScenarios;
use Illuminate\Database\Seeder;

class InvestmentScenarioSeeder extends Seeder
{
    use BuildsScenarios;

    public function run(): void
    {
        $this->buildScenario([
            'slug' => 'robot-trading',
            'category' => 'investasi',
            'title' => 'Robot Trading Kakak Kelas',
            'synopsis' => 'Alumni yang kamu kenal memamerkan profit 30 persen per minggu dari '
                . '"robot trading" dan mengajakmu bergabung. Uang jajanmu dipertaruhkan.',
            'channel' => 'WhatsApp',
            'difficulty' => 2,
            'xp' => 150,
            'nodes' => [
                'start' => [
                    'start' => true,
                    'speaker' => 'Kak Reza (alumni)',
                    'title' => 'Cuan cepat dari robot',
                    'body' => "Kak Reza, alumni sekolahmu, mengirim screenshot saldo yang naik terus:\n\n"
                        . "\"Modal 500 ribu jadi 1,5 juta cuma seminggu! Pakai robot trading, "
                        . "profit 30% per minggu, DIJAMIN. Ajak 3 teman, kamu dapat bonus. "
                        . "Slot terbatas, hari ini terakhir!\"",
                    'choices' => [
                        ['label' => 'Langsung transfer 500 ribu dari tabungan supaya tidak ketinggalan.',
                            'to' => 'trap', 'points' => -15,
                            'feedback' => 'Rasa takut ketinggalan (FOMO) adalah pemicu utama korban investasi bodong.'],
                        ['label' => 'Tolak dengan sopan, tanpa bertanya lebih lanjut.',
                            'to' => 'end_neutral', 'points' => 5,
                            'feedback' => 'Keputusan aman. Sayangnya kamu belum memastikan apakah ajakan ini berbahaya bagi orang lain.'],
                        ['label' => 'Tanya izin perusahaannya, lalu cek legalitas lewat kanal resmi OJK sebelum memutuskan.',
                            'to' => 'cek', 'points' => 30,
                            'feedback' => 'Tepat! Prinsip 2L: pastikan Legal (berizin) dan Logis (keuntungan masuk akal).'],
                    ],
                ],
                'trap' => [
                    'title' => 'Dashboard yang menggoda',
                    'body' => "Setelah transfer, dashboard menampilkan saldo naik tiap hari. Lalu admin "
                        . "berkata: \"Top up lagi 1 juta untuk naik level VIP dan ajak 3 temanmu "
                        . "agar profit digandakan!\"",
                    'choices' => [
                        ['label' => 'Ajak 3 teman agar dapat bonus dan naik level.',
                            'to' => 'end_bad', 'points' => -15,
                            'feedback' => 'Mengajak teman membuat kerugian menyebar ke orang yang kamu sayangi.'],
                        ['label' => 'Berhenti top up, jangan ajak teman, coba tarik dana, dan laporkan ke kanal resmi.',
                            'to' => 'end_neutral', 'points' => 10,
                            'feedback' => 'Berhenti sebelum rugi lebih besar adalah keputusan berani.'],
                    ],
                ],
                'cek' => [
                    'title' => 'Hasil pengecekanmu',
                    'body' => "Hasilnya: nama platform tidak tercantum sebagai entitas berizin, alamat "
                        . "kantor tidak jelas, dan janji untung \"dijamin\" 30% seminggu tidak masuk "
                        . "akal. Reza sendiri ternyata hanya ikut-ikutan diajak.",
                    'choices' => [
                        ['label' => 'Coba dulu 50 ribu, sekadar tes.',
                            'to' => 'end_bad', 'points' => -10,
                            'feedback' => 'Setoran kecil membuka pintu untuk setoran yang lebih besar.'],
                        ['label' => 'Biarkan saja Reza, itu urusannya.',
                            'to' => 'end_neutral', 'points' => 0,
                            'feedback' => 'Kamu aman, tetapi Reza dan temannya masih dalam bahaya.'],
                        ['label' => 'Ceritakan hasil cek ke orang tua atau guru, lalu ajak Reza berhenti dan jangan merekrut siapa pun.',
                            'to' => 'aksi', 'points' => 30,
                            'feedback' => 'Hebat! Melibatkan orang dewasa tepercaya membuat tindakanmu lebih kuat.'],
                    ],
                ],
                'aksi' => [
                    'title' => 'Grup "Cuan Squad"',
                    'body' => "Reza ragu tetapi mengaku grup \"Cuan Squad\" sudah berisi 40 orang, "
                        . "sebagian teman sekelasmu, dan admin masih terus merekrut.",
                    'choices' => [
                        ['label' => 'Keluar dari grup saja.',
                            'to' => 'end_neutral', 'points' => 15,
                            'feedback' => 'Menyelamatkan diri itu penting, tetapi puluhan orang masih berisiko.'],
                        ['label' => 'Simpan bukti, keluar grup, laporkan ke OJK (Kontak 157 atau sipasti.ojk.go.id), dan beri tahu teman yang sudah ikut agar berhenti setor.',
                            'to' => 'end_good', 'points' => 40,
                            'feedback' => 'Luar biasa! Laporan dini membantu menghentikan penawaran ilegal.'],
                    ],
                ],
                'end_good' => [
                    'kind' => 'good',
                    'title' => 'Misi Berhasil: Penjaga Dompet Teman',
                    'body' => 'Uangmu aman dan kamu membantu teman-temanmu keluar dari skema bodong. '
                        . 'Laporanmu menjadi bahan bagi petugas untuk menindak.',
                    'lesson' => 'Keuntungan tinggi dan pasti adalah tanda bahaya. Selalu cek 2L: '
                        . 'Legal (izin resmi) dan Logis (masuk akal).',
                ],
                'end_neutral' => [
                    'kind' => 'neutral',
                    'title' => 'Uang selamat, tapi teman belum',
                    'body' => 'Kamu terhindar dari rugi besar, namun ada langkah penting yang terlewat: '
                        . 'memberi tahu orang dewasa dan melapor.',
                    'lesson' => 'Skema ajak-teman hidup dari korban baru. Menghentikan satu rantai '
                        . 'berarti menyelamatkan banyak orang.',
                ],
                'end_bad' => [
                    'kind' => 'bad',
                    'title' => 'Platform Menghilang',
                    'body' => 'Suatu pagi situs tidak bisa dibuka, grup dibubarkan, dan admin '
                        . 'memblokirmu. Uangmu dan uang teman yang kamu ajak ikut lenyap. '
                        . '(Ini hanya simulasi.)',
                    'lesson' => 'Jangan setor uang ke penawaran yang tidak berizin dan jangan '
                        . 'merekrut teman. Jika terlanjur, segera laporkan dan simpan bukti transfer.',
                ],
            ],
            'red_flags' => [
                ['Untung tinggi dan "dijamin"', 'Investasi sah selalu menyebut risiko. Keuntungan pasti dan tinggi adalah jebakan.'],
                ['Bonus mengajak teman', 'Pola rekrutmen berjenjang adalah ciri skema piramida atau ponzi.'],
                ['Slot terbatas dan hari ini terakhir', 'Tekanan waktu mencegahmu melakukan pengecekan.'],
                ['Tidak berizin atau tidak jelas kantornya', 'Cek izin lewat OJK; entitas tanpa izin tidak boleh menawarkan investasi.'],
                ['Pamer screenshot saldo', 'Screenshot mudah dipalsukan dan bukan bukti legalitas.'],
            ],
        ]);
    }
}

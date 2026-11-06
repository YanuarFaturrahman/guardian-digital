<?php

namespace App\Console\Commands;

use App\Support\MailStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTest extends Command
{
    protected $signature = 'mail:test {to : Alamat email tujuan}';

    protected $description = 'Kirim email percobaan untuk memastikan SMTP berfungsi';

    public function handle(): int
    {
        $this->line(MailStatus::describe());

        if (! MailStatus::isDeliverable()) {
            $this->error('Mailer saat ini tidak mengirim email sungguhan. Atur MAIL_MAILER=smtp dan kredensial SMTP.');

            return self::FAILURE;
        }

        try {
            Mail::raw('Ini email percobaan dari Guardian Digital. Jika kamu menerimanya, SMTP sudah benar.', function ($m) {
                $m->to($this->argument('to'))->subject('Tes email Guardian Digital');
            });
        } catch (\Throwable $e) {
            $this->error('Gagal mengirim: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info('Email percobaan terkirim ke ' . $this->argument('to') . '. Periksa kotak masuk dan folder spam.');

        return self::SUCCESS;
    }
}

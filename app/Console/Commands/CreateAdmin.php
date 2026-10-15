<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
class CreateAdmin extends Command
{
    protected $signature='voucher:admin';
    protected $description='Buat akun operator bernama; kata sandi dimasukkan secara tersembunyi.';
    public function handle(): int
    {
        $name=$this->ask('Nama operator'); $email=strtolower(trim((string)$this->ask('Email')));
        $password=$this->secret('Kata sandi (minimal 12 karakter)'); $repeat=$this->secret('Ulangi kata sandi');
        $validator=Validator::make(['name'=>$name,'email'=>$email,'password'=>$password,'password_confirmation'=>$repeat],[
            'name'=>'required|string|max:100','email'=>'required|email|max:255|unique:users,email','password'=>'required|string|min:12|max:128|confirmed',
        ]);
        if ($validator->fails()) { foreach ($validator->errors()->all() as $error) { $this->error($error); } return self::FAILURE; }
        User::create(['name'=>$name,'email'=>$email,'password'=>$password]);
        $this->info('Operator dibuat. Tidak ada registrasi publik.'); return self::SUCCESS;
    }
}

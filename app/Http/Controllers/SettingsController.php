<?php
namespace App\Http\Controllers;

use App\Models\{Branch,QrisAsset,Setting,User,VoucherPolicy};
use App\Services\VoucherActions;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache,DB,Storage};
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function qris(Request $request,VoucherActions $actions)
    {
        $data=$request->validate(['qris'=>'required|image|mimes:png,jpg,jpeg|mimetypes:image/png,image/jpeg|max:5120|dimensions:min_width=200,min_height=200,max_width=5000,max_height=5000',
            'merchant_name'=>'required|string|max:150|not_regex:/^\s*$/u','receiver_key'=>'required|regex:/^[A-Za-z0-9._\-]{1,100}$/D','merchant_checked'=>'accepted']);
        $file=$request->file('qris'); $mime=$file->getMimeType();
        $name=hash_file('sha256',$file->getRealPath()).'-'.Str::uuid().($mime==='image/png'?'.png':'.jpg');
        $path=$file->storeAs('qris',$name,'local');
        try {
            DB::transaction(function () use ($request,$data,$path,$mime,$actions) {
                $actions->activeActor($request->user());
                $asset=QrisAsset::create(['path'=>$path,'mime'=>$mime,'merchant_name'=>trim($data['merchant_name']),
                    'receiver_key'=>strtoupper($data['receiver_key']),'uploaded_by'=>$request->user()->id]);
                Setting::put('qris_id',(string)$asset->id);
                $actions->audit($request->user(),'qris.changed','qris',$asset->id);
            });
        } catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
        return redirect()->route('admin.settings')->with('settings_section','pembayaran')->with('success','QRIS disimpan untuk pesanan baru. Pesanan lama mempertahankan gambar sebelumnya.');
    }

    public function branch(Request $request,VoucherActions $actions)
    {
        $data=$request->validate(['id'=>'nullable|integer|exists:branches,id','name'=>'required|string|max:100|not_regex:/^\s*$/u','address'=>'required|string|max:500','active'=>'sometimes|boolean']);
        DB::transaction(function () use ($request,$data,$actions) {
            $actions->activeActor($request->user());
            $branch=isset($data['id'])?Branch::lockForUpdate()->findOrFail($data['id']):new Branch;
            $branch->fill(['name'=>trim($data['name']),'address'=>trim($data['address']),'active'=>$request->boolean('active')])->save();
            Cache::forget('branches:public:v1'); $actions->audit($request->user(),'branch.saved','branch',$branch->id);
        });
        return redirect()->route('admin.settings')->with('settings_section','cabang')->with('success','Cabang disimpan.')->with('feedback','toast');
    }

    public function support(Request $request)
    {
        $data=$request->validate(['phone'=>'nullable|string|max:30']);
        Setting::put('support_phone',empty($data['phone'])?'':Phone::normalize($data['phone']));
        return redirect()->route('admin.settings')->with('settings_section','pembayaran')->with('success','Kontak bantuan disimpan.')->with('feedback','toast');
    }

    public function policy(Request $request,VoucherActions $actions)
    {
        $data=$request->validate(['price'=>'required|integer|min:1|max:10000000','minimum_order'=>'required|integer|min:1|max:1000000000',
            'discount_30'=>'required|integer|min:1|max:10000000','discount_60'=>'required|integer|min:1|max:10000000',
            'discount_90'=>'required|integer|min:1|max:10000000','discount_365'=>'required|integer|min:1|max:10000000','policy_confirmed'=>'accepted']);
        $discounts=array_map(fn($end)=>(int)$data['discount_'.$end],[30,60,90,365]);
        if ($discounts!==collect($discounts)->sortDesc()->values()->all() || max($discounts)>=(int)$data['minimum_order']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['policy'=>'Potongan harus menurun/tetap dan di bawah minimum belanja.']);
        }
        DB::transaction(function () use ($request,$data,$actions) {
            $actions->activeActor($request->user());
            $policy=VoucherPolicy::create(['price'=>$data['price'],'minimum_order'=>$data['minimum_order'],'valid_days'=>365,
                'tiers'=>array_map(fn($end)=>['end'=>$end,'discount'=>(int)$data['discount_'.$end]],[30,60,90,365])]);
            Setting::put('policy_id',(string)$policy->id); $actions->audit($request->user(),'policy.created','policy',$policy->id);
        });
        return redirect()->route('admin.settings')->with('settings_section','aturan')->with('success','Versi aturan baru disimpan. Janji pada pesanan/voucher lama tetap berlaku.');
    }

    public function user(Request $request,User $user,VoucherActions $actions)
    {
        $data=$request->validate(['active'=>'required|boolean']);
        abort_if($user->id===$request->user()->id,422,'Gunakan akun operator lain untuk menonaktifkan akun Anda.');
        DB::transaction(function () use ($request,$user,$data,$actions) {
            $actions->activeActor($request->user());
            $user->active=$data['active']; $user->save(); $actions->audit($request->user(),'user.status','user',$user->id,['active'=>$data['active']]);
        });
        return redirect()->route('admin.settings')->with('settings_section','operator')->with('success','Status operator diperbarui.');
    }
}

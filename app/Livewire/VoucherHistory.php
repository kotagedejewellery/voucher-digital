<?php

namespace App\Livewire;

use App\Models\Voucher;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class VoucherHistory extends Component
{
    use WithPagination;

    #[Url] public string $search='';
    #[Url] public string $lifecycle='';
    #[Url(as: 'paid_from')] public string $paidFrom='';
    #[Url(as: 'paid_to')] public string $paidTo='';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedLifecycle(): void { $this->resetPage(); }
    public function updatedPaidFrom(): void { $this->resetPage(); }
    public function updatedPaidTo(): void { $this->resetPage(); }
    public function resetFilters(): void { $this->search=''; $this->lifecycle=''; $this->paidFrom=''; $this->paidTo=''; $this->resetPage(); }

    public function detailUrl(string $code): string
    {
        return route('admin.voucher', array_filter([
            'code'=>$code, 'from_history'=>1, 'history_search'=>$this->search,
            'history_lifecycle'=>$this->lifecycle, 'history_paid_from'=>$this->paidFrom,
            'history_paid_to'=>$this->paidTo,
        ], fn ($value) => $value !== ''));
    }

    public function render()
    {
        abort_unless(auth()->user()?->active,403);
        $this->validate([
            'search'=>'string|max:150', 'lifecycle'=>'in:,AKTIF,TERPAKAI,KEDALUWARSA,PERLU_DIPERIKSA',
            'paidFrom'=>'nullable|date_format:Y-m-d|before_or_equal:paidTo',
            'paidTo'=>'nullable|date_format:Y-m-d|after_or_equal:paidFrom',
        ]);

        $today=Carbon::now('Asia/Jakarta')->toDateString();
        $expiresOn="((payment_orders.paid_at AT TIME ZONE 'Asia/Jakarta')::date + (voucher_policies.valid_days - 1))";
        $query=Voucher::query()->select('vouchers.*')
            ->join('payment_orders','payment_orders.id','=','vouchers.payment_order_id')
            ->join('voucher_policies','voucher_policies.id','=','payment_orders.policy_id')
            ->with(['order.policy','redemption.branch','redemption.actor'])
            ->withCount([
                'deliveries as whatsapp_delivery_attention_count'=>fn($q)=>$q->needsAttention()->where('kind','WhatsApp'),
                'deliveries as meta_delivery_attention_count'=>fn($q)=>$q->needsAttention()->where('kind','!=','WhatsApp'),
            ]);

        if ($this->search!=='') {
            $term=trim($this->search);
            $query->where(fn($q)=>$q->where('vouchers.code',strtoupper($term))->orWhere('payment_orders.reference',$term)->orWhere('payment_orders.merchant_ref',$term));
        }

        if ($this->paidFrom!=='') {
            $query->where('payment_orders.paid_at','>=',Carbon::createFromFormat('Y-m-d',$this->paidFrom,'Asia/Jakarta')->startOfDay()->utc());
        }
        if ($this->paidTo!=='') {
            $query->where('payment_orders.paid_at','<',Carbon::createFromFormat('Y-m-d',$this->paidTo,'Asia/Jakarta')->addDay()->startOfDay()->utc());
        }

        match($this->lifecycle) {
            'TERPAKAI'=>$query->where('vouchers.status','TERPAKAI'),
            'AKTIF'=>$query->where('vouchers.status','AKTIF')->whereRaw("$expiresOn >= ?",[$today]),
            'KEDALUWARSA'=>$query->where('vouchers.status','AKTIF')->whereRaw("$expiresOn < ?",[$today]),
            'PERLU_DIPERIKSA'=>$query->whereHas('deliveries',fn($q)=>$q->needsAttention()),
            default=>null,
        };

        return view('livewire.voucher-history',['vouchers'=>$query->latest('vouchers.id')->paginate(20)]);
    }
}

<?php
namespace App\Livewire;

use App\Models\{Export,PaymentOrder};
use App\Jobs\BuildExport;
use Illuminate\Support\Facades\Queue;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class Orders extends Component
{
    use WithPagination;
    #[Url] public string $search='';
    #[Url] public string $status='MENUNGGU_VERIFIKASI';
    public function updatedSearch(): void
    {
        if ($this->search!=='') { $this->status=''; }
        $this->resetPage();
    }
    public function updatedStatus(): void { $this->resetPage(); }
    public function resetFilters(): void { $this->search=''; $this->status=''; $this->resetPage(); }
    public function export(): void
    {
        abort_unless(auth()->user()?->active,403);
        $this->validate(['search'=>'string|max:150','status'=>'in:,MENUNGGU_PEMBAYARAN,MENUNGGU_VERIFIKASI,PERLU_PERBAIKAN,PAID']);
        $export=Export::create(['user_id'=>auth()->id(),'filters'=>['search'=>$this->search,'status'=>$this->status],'expires_at'=>now()->addDay()]);
        Queue::connection('database')->push(new BuildExport($export->id));
        session()->flash('success','Ekspor diproses. Unduh setelah status siap.');
    }
    public function render()
    {
        abort_unless(auth()->user()?->active,403);
        $this->validate(['search'=>'string|max:150','status'=>'in:,MENUNGGU_PEMBAYARAN,MENUNGGU_VERIFIKASI,PERLU_PERBAIKAN,PAID']);
        $query=PaymentOrder::with(['voucher.order.policy'])->when($this->status,fn($q)=>$q->where('status',$this->status));
        if ($this->search!=='') {
            $term=trim($this->search);
            $query->where(fn($q)=>$q->where('reference',$term)->orWhere('merchant_ref',$term)->orWhere('campaign',$term)->orWhereHas('voucher',fn($v)=>$v->where('code',strtoupper($term))));
        }
        if ($this->status==='MENUNGGU_VERIFIKASI') { $query->orderByRaw('customer_confirmed_at asc nulls last')->orderBy('id'); }
        else { $query->latest('id'); }
        return view('livewire.orders',['orders'=>$query->paginate(20),
            'exports'=>Export::where('user_id',auth()->id())->where('expires_at','>',now())->latest('id')->limit(5)->get()]);
    }
}

<?php
namespace App\Http\Controllers;
use App\Models\Export;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class ExportController extends Controller
{
    public function download(Request $request,Export $export)
    {
        abort_unless($export->user_id===$request->user()->id,403);
        abort_unless($export->status==='READY' && $export->expires_at->isFuture(),410,'File belum siap atau sudah kedaluwarsa.');
        abort_unless(Storage::disk('local')->exists($export->path),404);
        return Storage::disk('local')->download($export->path,'voucher-'.$export->id.'.csv',['Cache-Control'=>'private, no-store','Content-Type'=>'text/csv; charset=UTF-8']);
    }
}

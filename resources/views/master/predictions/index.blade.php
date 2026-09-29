@extends('layouts.app')
@section('title','Versi Model Prediksi')
@section('content')
<div class="page feature-page">
 @include('features.page-heading',['eyebrow'=>'PENGELOLAAN MODEL','title'=>'Versi Model Prediksi','description'=>'Kelola hasil eksperimen sebelum dipublikasikan kepada masyarakat.'])
 @include('master.partials.alerts')
 <section class="panel master-panel">
  <div class="panel-head"><div><h2>Riwayat model</h2><p>Hanya hasil yang selesai dan lolos validasi yang menggantikan versi aktif.</p></div><div class="master-actions"><form method="POST" action="{{ route('admin-predictions.auto') }}">@csrf<button class="primary-button" type="submit">Jalankan prediksi otomatis</button></form><a class="secondary-button" href="{{ route('admin-predictions.create') }}">Impor manual</a></div></div>
  <div class="table-wrap"><table><thead><tr><th>Versi</th><th>Data terakhir</th><th>Prediksi</th><th>Status hasil</th><th>Dibuat oleh</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
  @forelse($runs as $run)<tr><td><strong>{{ $run->version }}</strong><small>{{ $run->generated_at?->translatedFormat('d M Y H:i') ?? ($run->current_stage ?: 'Waktu model belum tercatat') }}</small>@if($run->error_message)<small class="import-error">{{ $run->error_message }}</small>@endif</td><td>{{ $run->data_last_date?->translatedFormat('d M Y') }}</td><td>{{ number_format($run->prediction_count) }}<small>@if(in_array($run->status,['queued','processing'])){{ $run->progress }}% · {{ $run->current_stage }}@elseif($run->runtime_seconds)Selesai dalam {{ gmdate('H:i:s',$run->runtime_seconds) }}@endif</small></td><td>@foreach(($run->status_summary ?? []) as $status=>$count)<small>{{ $status }}: {{ $count }}</small>@endforeach</td><td>{{ $run->creator?->name ?? 'Sistem' }}<small>{{ $run->trigger_type === 'automatic' ? 'Otomatis' : 'Manual' }}</small></td><td><span class="status-badge {{ $run->status==='active'?'active':'inactive' }}">{{ strtoupper($run->status) }}</span></td><td>@if($run->status==='archived')<form method="POST" action="{{ route('admin-predictions.activate',$run) }}">@csrf @method('PATCH')<button class="secondary-button">Aktifkan</button></form>@elseif($run->status==='active')<span>Dipublikasikan</span>@elseif(in_array($run->status,['queued','processing']))<span>Sedang diproses</span>@else<span>Tidak dipublikasikan</span>@endif</td></tr>@empty<tr><td colspan="7">Belum ada versi model.</td></tr>@endforelse
  </tbody></table></div>{{ $runs->links('partials.pagination') }}
 </section>
</div>
@endsection

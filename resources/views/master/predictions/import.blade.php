@extends('layouts.app')
@section('title','Impor Prediksi')
@section('content')
<div class="page feature-page">
 @include('features.page-heading',['eyebrow'=>'IMPORT MODEL','title'=>'Publikasikan Hasil Prediksi','description'=>'Unggah keluaran Colab. Sistem memeriksa status, MAPE, serta pembanding Naive sebelum hasil diaktifkan.'])
 @include('master.partials.alerts')
 <form class="panel form-panel" method="POST" enctype="multipart/form-data" action="{{ route('admin-predictions.store') }}">@csrf
  <div class="form-grid"><label>Versi model *<input name="version" value="{{ old('version','V3_FINAL_'.now()->format('Ymd_Hi')) }}" required></label>
  <label>Prediksi dengan status *<input type="file" name="prediction_file" accept=".csv" required><small>prediksi_harga..._dengan_status.csv</small></label>
  <label>Profil kualitas komoditas *<input type="file" name="profile_file" accept=".csv" required><small>quality_profile_komoditas....csv</small></label>
  <label>Evaluasi per horizon<input type="file" name="evaluation_file" accept=".csv"><small>Disarankan agar MAE Naive dan alasan penahanan dapat dijelaskan.</small></label></div>
  <div class="form-actions"><a href="{{ route('admin-predictions.index') }}">Batal</a><button class="primary-button">Periksa, normalisasi, dan aktifkan</button></div>
 </form>
</div>
@endsection

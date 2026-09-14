@extends('layouts.admin')
@section('title', 'Ajouter une adhésion')
@section('page-title', 'Ajouter une adhésion')
@section('content')
<div class="max-w-4xl mt-5">
    <p class="text-sm text-gray-500 mb-5">Ajoutez une adhésion reçue hors ligne. Le statut est initialisé à « En attente de paiement ».</p>
    @include('admin.adhesions._form')
</div>
@endsection

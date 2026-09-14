@extends('layouts.admin')
@section('title', "Modifier l'adhésion")
@section('page-title', "Modifier l'adhésion")
@section('content')
<div class="max-w-4xl mt-5">
    <p class="text-sm text-gray-500 mb-5">Modifiez l'identité, les coordonnées, la photo et les informations de suivi de {{ $adhesion->prenom }} {{ $adhesion->nom }}.</p>
    @include('admin.adhesions._form')
</div>
@endsection

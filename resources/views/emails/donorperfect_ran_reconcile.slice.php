@extends('layouts.email')
@section('content')
Hello,
<br />
<p>The DonorPerfect shadow database for the {{  $this->environment->getTeamName() }} team has a total of <b>{{ $shadow_count }}</b> records for the table <b>{{ $table }}</b>. The data source has a total of <b>{{ $source_count }}</b> records. The difference is 
<b>{{ $difference }}</b>.</p>

@endsection
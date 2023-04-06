@extends('layouts.email')

@section('content')
Hello,
<br />
<p>The DonorDrive integration ran for table {{ $table }} in team {{ $teamname }}.
We synced a total of <b>{{ $donordrive_count }}</b> records from the DonorDrive API. 
The shadow database contains <b>{{ $shadow_count }}</b> of the <b>{{ $donordrive_count }}</b> records found this sync. 
The difference between the two is <b>{{ $difference }}</b>.</p>
@endsection
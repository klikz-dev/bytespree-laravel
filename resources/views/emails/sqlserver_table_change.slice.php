@extends('layouts.email')

@section('content')
Hello,
<br />
<p>We noticed a change in the table {{ $table }} for the {{ $database }} SQL Server shadow database in the {{ $this->environment->getTeamName() }} team.</p>
<p>The following columns were added:</p>
    <ul>
        @foreach ($added as $column)
            <li>{{ $column }}</li>    
        @endforeach
    </ul>
<p>The following columns were removed:</p>
    <ul>
        @foreach ($removed as $column)
            <li>{{ $column }}</li>   
        @endforeach
    </ul>
<p>We automatically rebuilt and re-synced this table, and all is well. You may want to check your mappings to make sure there are no additional actions needed.</p>
<p>Bytespree indicates that the following fields had mapping attached to them:</p>
    <ul>
        @foreach ($conversions as $column)
            <li>{{ $column }}</li>    
        @endforeach
    </ul>
    <br />
@endsection

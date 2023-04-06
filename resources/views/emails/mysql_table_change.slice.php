@extends('layouts.email')

@section('content')
Hello,
<br />
<p>We noticed a change in the table {{ $table }} for the {{ $database }} MySQL shadow database in the {{  $this->environment->getTeamName() }} team.</p>
<p>The following columns were added in MySQL:</p>
    <ul>
        @foreach ($added as $column)
            <li>{{ $column }}</li>    
        @endforeach
    </ul>
<p>The following columns were removed in MySQL:</p>
    <ul>
        @foreach ($removed as $column)
            <li>{{ $column }}</li>   
        @endforeach
    </ul>
<p>We automatically rebuilt and re-synced this table, and all is well. You may want to check the conversion programming to make sure there are no additional actions needed.</p>
<p>Blueprint indicates that the following fields are used in conversion:</p>
    <ul>
        @foreach ($conversions as $column)
            <li>{{ $column }}</li>    
        @endforeach
    </ul>
    <br />
@endsection

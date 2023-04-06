@extends('layouts.email')

@section('content')
Hello,
<br />
<p>We noticed a change in the <b>{{ $database }}</b> Salesforce database to the table <b>{{ $table }}</b> in the {{ $this->environment->getTeamName() }} team.</p>
@if(count($added) > 0)
    <p>The following columns were added in Salesforce:</p>
    <ul>
        @foreach ($added as $column)
            <li>{{ $column }}</li>    
        @endforeach
    </ul>
@endif
@if(count($removed) > 0)
    <p>The following columns were removed in Salesforce:</p>
    <ul>
        @foreach ($removed as $column)
            <li>{{ $column }}</li>   
        @endforeach
    </ul>
@endif
<p>We automatically altered this table's structure, and all is well. You may want to check the conversion programming to make sure there are no additional actions needed.</p>
@if(count($conversions) > 0)
    <p>Blueprint indicates that the following fields are used in conversion:</p>
    <ul>
        @foreach ($conversions as $column)
            <li>{{ $column }}</li>    
        @endforeach
    </ul>
@endif
@endsection

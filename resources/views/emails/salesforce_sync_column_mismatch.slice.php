@extends('layouts.email')

@section('content')
Hello,
<br />
<p>The salesforce sync ran for table {{ $table }} on {{ date("F j, Y h:i A") }}, and there was a column mistmatch. Salesforce has {{ count($sf) }} columns and DMI has {{ count($dmi) }} columns in the {{ $this->environment->getTeamName() }} team.</p>

@if(count($sf) < count($dmi))
    <p>Because DMI has more columns than Salesforce, you are <i><b>REQUIRED</b></i> to rebuild and resync this table in the shadow database.</p>
@else
    <p>Because Salesforce has more columns than DMI, you are **ADVISED** to rebuild and resync this table in the shadow database.</p>
@endif
@endsection

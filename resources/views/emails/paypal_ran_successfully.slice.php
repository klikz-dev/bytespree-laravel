@extends('layouts.email')

@section('content')
Hello,
<br />
<p>The PayPal sync for {{ $member }} just ran successfully for the date range <b>{{ $start }}</b> to <b>{{ $end }}</b>. A total of <b>{{ $count }} transactions</b> were created/updated in the {{  $this->environment->getTeamName() }} team.</p>
@endsection

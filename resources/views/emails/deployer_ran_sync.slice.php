@extends('layouts.email')

@section('content')
Hello,
<br />
<p>The Deployer Sync ran for the <b>{{  $this->environment->getTeamName() }}</b> team from <b>{{ $start_date }}</b> to <b>{{ $end_date }}</b> and cloned a total of <b>{{ $deployer_count }}</b> records from the Deployer API.</p>

@endsection

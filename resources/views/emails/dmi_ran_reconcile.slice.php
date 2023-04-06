@extends('layouts.email')

@section('content')
Hello,
<br />
<p>The DMI integration ran for {{ $database }} on the {{  $this->environment->getTeamName() }} team. <b>{{ $source_count }}</b> out of <b>{{ $shadow_count }}</b> records were synced to table <b>{{ $table }}</b>. The difference was 
<b>{{ $difference }}</b>.</p>

<p>After completing the sync, there were a total of <b>{{ $source_total }}</b> records in the source table and <b>{{ $shadow_total }}</b> records in the shadow table. The difference was <b>{{ $difference_total }}</b>.</p>

@endsection
@extends('layouts.email')

@section('content')
Hello,
<br />
<p>The PostgreSQL database <b>{{ $database }}</b> was synchronized to the data lake in the {{  $this->environment->getTeamName() }} team.</p>

<table style="border-collapse: collapse; width: 100%; margin-bottom: 20px;">
    <thead>
        <tr>
            <th style="border: 1px solid black;" colspan="4">Summary</th>
        </tr>
        <tr>
            <th style="width: 25%; text-align: right; border: 1px solid black;">Table</th>
            <th style="width: 25%; text-align: right; border: 1px solid black;">Received in Last Run</th>
            <th style="width: 25%; text-align: right; border: 1px solid black;">Source Count</th>
            <th style="width: 25%; text-align: right; border: 1px solid black;">Shadow Count</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($tables as $table)
        <tr>
            <td style="width: 25%; text-align: right; border: 1px solid black;">
                {{ $table['name'] }}
            </td>
            <td style="width: 25%; text-align: right; border: 1px solid black;">
                {{ $table['last_run_count'] }}
            </td>
            <td style="width: 25%; text-align: right; border: 1px solid black;">
                {{ $table['origin_total_rows'] }}
            </td>
            <td style="width: 25%; text-align: right; border: 1px solid black;">
                {{ $table['clone_total_rows'] }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<p>Here is a reconciliation report broken down by table and day. This shows the quantities of records that were synced during this particular run on <b>{{ $date }}</b>.</p>

@foreach ($daydata as $key => $value)
    <table style="border-collapse: collapse; width: 100%; margin-bottom: 20px;">
        <thead>
            <tr>
                <th style="border: 1px solid black;" colspan="4">{{ $key }}</th>
            </tr>
            <tr>
                <th style="width: 20%; border: 1px solid black;">Source Count</th>
                <th style="width: 20%; border: 1px solid black;">Shadow Count</th>
                <th style="width: 20%; border: 1px solid black;">Difference</th>
                <th style="width: 20%; border: 1px solid black;">Created/Modified</th>
            </tr>
        </thead>
        <tbody>
            @foreach($value as $run)
                <tr>
                    <td style="width: 25%; text-align: right; border: 1px solid black;">
                        {{ $run['api_count'] }}
                    </td>
                    <td style="width: 25%; text-align: right; border: 1px solid black;">
                        {{ $run['shadow_count'] }}
                    </td>
                    @if($tabledata["difference"] >0)
                        <td style="color: red; width: 25%; text-align: right; border: 1px solid black;">
                            {{ $run['difference'] }}
                        </td>
                    @else
                        <td style="width: 25%; text-align: right; border: 1px solid black;">
                            {{ $run['difference'] }}
                        </td>
                    @endif
                    <td style="width: 25%; text-align: right; border: 1px solid black;">
                        {{ $run['date'] }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach
@endsection

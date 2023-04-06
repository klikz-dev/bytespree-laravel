@extends('layouts.email')

@section('content')
Hello,
<br />
<p>We received a total of <b>{{ $cardpointe_count }}</b> records from the CardPointe API from the {{  $this->environment->getTeamName() }} team for this date. The shadow database now has <b>{{ $shadow_count }}</b> records for this date range. The difference between the two is <b>{{ $difference }}</b>.</p>
<br />
<table style="border-collapse: collapse;">
    <thead>
        <tr>
            <th style="width: 20%; border: 1px solid black;">Date</th>
            <th style="width: 20%; border: 1px solid black;">CardPointe Amount</th>
            <th style="width: 20%; border: 1px solid black;">CardPointe Count</th>
            <th style="width: 20%; border: 1px solid black;">Shadow Database Count</th>
            <th style="width: 20%; border: 1px solid black;">Difference</th>
        </tr>
    </thead>
    <tbody>  
        @foreach ($table as $key => $daydata)
            <tr>
                <td style="width: 20%; border: 1px solid black;">{{ $daydata["date"] }}</td>
                <td style="width: 20%; text-align: right; border: 1px solid black;">
                    @php
                        setlocale(LC_MONETARY,"en_US.UTF-8");
                        echo money_format("%n", $daydata["cardpointe_amount"]);
                    @endphp
                </td>
                <td style="width: 20%; text-align: right; border: 1px solid black;">{{ $daydata["cardpointe_count"] }}</td>
                <td style="width: 20%; text-align: right; border: 1px solid black;">{{ $daydata["shadow_count"] }}</td>
                @if($daydata["difference"] === 0)
                    <td style="width: 20%; text-align: right; border: 1px solid black;">{{ $daydata["difference"] }}</td>
                @else
                    <td  style="color: red; width: 20%; text-align: right; border: 1px solid black;">{{ $daydata["difference"] }}</td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
@endsection

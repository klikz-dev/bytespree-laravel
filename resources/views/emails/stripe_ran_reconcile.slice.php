@extends('layouts.email')

@section('content')
Hello,
<br />
<p>In the table <b>{{ $table_name }}</b> in the {{  $this->environment->getTeamName() }} team we received a total of <b>{{ $stripe_count }}</b> records from the Stripe API for this date range. The shadow database now has <b>{{ $shadow_count }}</b> records for this date range. The difference between the two is <b>{{ $difference }}</b>.</p>
<br />
<table style="border-collapse: collapse;">
    <thead>
        <tr>
            <th style="width: 20%; border: 1px solid black;">Date</th>
            <th style="width: 20%; border: 1px solid black;">Stripe Amount (For Charges)</th>
            <th style="width: 20%; border: 1px solid black;">Stripe Count</th>
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
                        echo money_format("%n", $daydata["stripe_amount"]);
                    @endphp
                </td>
                <td style="width: 20%; text-align: right; border: 1px solid black;">{{ $daydata["stripe_count"] }}</td>
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

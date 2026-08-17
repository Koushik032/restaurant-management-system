<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>
        {{ ucfirst($type) }} Report
    </title>

    <style>

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #eeeeee;
            font-weight: bold;
        }

        th,
        td {
            border: 1px solid #999;
            padding: 7px;
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .amount {
            text-align: right;
        }

    </style>

</head>


<body>

    <h2>

        {{ ucfirst($type) }} Report

    </h2>


    @if(!empty($data))

        <table>

            <thead>

                <tr>

                    @foreach(array_keys($data[0]) as $header)

                        <th>

                            {{ $header }}

                        </th>

                    @endforeach

                </tr>

            </thead>


            <tbody>

                @foreach($data as $row)

                    <tr>

                        @foreach($row as $value)

                            <td>

                                {{ $value ?? '-' }}

                            </td>

                        @endforeach

                    </tr>

                @endforeach

            </tbody>

        </table>

    @else

        <p class="text-center">

            No data found.

        </p>

    @endif


</body>

</html>
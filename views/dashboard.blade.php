<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>開放デー一覧</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-end mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">スポーツセンター開放デー一覧</h1>
            </div>
            <div class="text-right">
                <p class="text-gray-500 text-sm mb-2">最終更新: {{ $schedules[0]->updatedAt ?? 'なし' }}</p>
                <form action="{{ $basePath }}/update" method="POST">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
                        情報を更新する
                    </button>
                </form>
            </div>
        </div>

        @if(empty($schedules))
            <div class="bg-white p-6 rounded-lg shadow text-center text-gray-600">
                スケジュール情報がありません。
            </div>
        @else
            <div class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer" onclick="sortTable(0)">
                                スポーツセンター
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer" onclick="sortTable(1)">
                                日付＆時間帯
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider cursor-pointer" onclick="sortTable(2)">
                                内容
                            </th>
                        </tr>
                    </thead>
                    <tbody id="scheduleTableBody">
                        @foreach($schedules as $s)
                        @php
                            $dateObj = \Carbon\Carbon::parse($s->date);
                            $isPast = $dateObj->isPast() && !$dateObj->isToday();
                            $hasTableTennis = mb_strpos($s->activities, '卓球') !== false;
                            $rowClass = ($isPast || !$hasTableTennis) ? 'bg-gray-200 text-gray-500' : 'bg-white text-gray-900';
                            $days = ['日', '月', '火', '水', '木', '金', '土'];
                            $dayOfWeek = $days[$dateObj->dayOfWeek];
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="px-5 py-5 border-b border-gray-200 text-sm">
                                <a href="{{ $s->centerUrl }}" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
                                    {{ $s->centerName }}
                                </a>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 text-sm" data-order="{{ $s->date }} {{ $s->timeSlot }}">
                                {{ $s->date }} ({{ $dayOfWeek }}) {{ $s->timeSlot }}
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 text-sm">
                                {{ $s->activities }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>

    <script>
        function sortTable(n) {
            const table = document.getElementById("scheduleTableBody");
            let rows = Array.from(table.rows);
            let switching = true;
            let dir = "asc";
            let switchcount = 0;

            while (switching) {
                switching = false;
                for (var i = 0; i < (rows.length - 1); i++) {
                    let shouldSwitch = false;
                    let x = rows[i].getElementsByTagName("TD")[n];
                    let y = rows[i + 1].getElementsByTagName("TD")[n];

                    let xValue = x.getAttribute("data-order") || x.innerText.toLowerCase();
                    let yValue = y.getAttribute("data-order") || y.innerText.toLowerCase();

                    if (dir == "asc") {
                        if (xValue > yValue) {
                            shouldSwitch = true;
                            break;
                        }
                    } else if (dir == "desc") {
                        if (xValue < yValue) {
                            shouldSwitch = true;
                            break;
                        }
                    }
                }
                if (shouldSwitch) {
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    switchcount++;
                    rows = Array.from(table.rows);
                } else {
                    if (switchcount == 0 && dir == "asc") {
                        dir = "desc";
                        switching = true;
                    }
                }
            }
        }
    </script>
</body>
</html>

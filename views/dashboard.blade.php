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
                <h1 class="text-3xl font-bold text-gray-800">開放デー一覧</h1>
            </div>
            <div class="text-right">
                <p class="text-gray-500 text-sm mb-2">最終更新: {{ $schedules[0]->updatedAt ?? 'なし' }}</p>
                <div class="flex space-x-2 justify-end">
                    <a href="{{ $basePath }}/targets" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded shadow">
                        管理
                    </a>
                    <form action="{{ $basePath }}/update" method="POST" onsubmit="return showLoading(this)">
                        <button type="submit" id="updateButton" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow flex items-center">
                            <span id="buttonText">情報を更新</span>
                            <svg id="loadingIcon" class="animate-spin ml-2 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @if(empty($schedules))
            <div class="bg-white p-6 rounded-lg shadow text-center text-gray-600">
                スケジュール情報がありません。
            </div>
        @else
            <div class="mb-4 flex justify-between items-center bg-white p-4 rounded-lg shadow">
                <label class="inline-flex items-center cursor-pointer text-sm text-gray-700 font-medium">
                    <input type="checkbox" id="toggleGrayRows" class="form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 mr-2" onchange="toggleGrayRows()">
                    <span>過去分・他スポーツを表示する</span>
                </label>
            </div>

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
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                ツール
                            </th>
                        </tr>
                    </thead>
                    <tbody id="scheduleTableBody">
                        @foreach($schedules as $s)
                        @php
                            $dateObj = \Carbon\Carbon::parse($s->date);
                            $isPast = $dateObj->isPast() && !$dateObj->isToday();
                            $hasTableTennis = mb_strpos($s->activities, '卓球') !== false;
                            $isGray = $isPast || !$hasTableTennis;
                            $rowClass = $isGray ? 'bg-gray-200 text-gray-500 gray-row hidden' : 'bg-white text-gray-900';
                            $days = ['日', '月', '火', '水', '木', '金', '土'];
                            $dayOfWeek = $days[$dateObj->dayOfWeek];

                            // Google Calendar URL generation
                            $startTime = '';
                            $endTime = '';
                            // UTF-8 の ～ を確実にマッチさせるため、\x{ff5e} を使用するか、マルチバイト対応の正規表現を検討
                            if (preg_match('/(\d{2}):(\d{2}).*?(\d{2}):(\d{2})/', $s->timeSlot, $matches)) {
                                $dateStr = str_replace('-', '', $s->date);
                                $startTime = $dateStr . 'T' . $matches[1] . $matches[2] . '00';
                                $endTime = $dateStr . 'T' . $matches[3] . $matches[4] . '00';
                            }
                            $calendarUrl = "https://www.google.com/calendar/render?action=TEMPLATE"
                                . "&text=" . urlencode("【開放デー】" . $s->centerName)
                                . "&dates=" . $startTime . "/" . $endTime
                                . "&details=" . urlencode("内容: " . $s->activities . "\nURL: " . $s->centerUrl)
                                . "&location=" . urlencode($s->centerName . " " . $s->location);
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
                            <td class="px-5 py-5 border-b border-gray-200 text-sm">
                                @if($startTime && $endTime)
                                <a href="{{ $calendarUrl }}" target="_blank" class="text-xs bg-green-500 hover:bg-green-600 text-white py-1 px-2 rounded shadow inline-block whitespace-nowrap">
                                    📅登録
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>

    <script>
        function toggleGrayRows() {
            const checkbox = document.getElementById('toggleGrayRows');
            if (!checkbox) return;
            const show = checkbox.checked;
            const grayRows = document.querySelectorAll('.gray-row');
            grayRows.forEach(row => {
                if (show) {
                    row.classList.remove('hidden');
                } else {
                    row.classList.add('hidden');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            toggleGrayRows();
        });

        function showLoading(form) {
            const btn = document.getElementById('updateButton');
            const text = document.getElementById('buttonText');
            const icon = document.getElementById('loadingIcon');

            setTimeout(() => {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
            }, 0);
            text.innerText = '更新中...';
            icon.classList.remove('hidden');
            return true;
        }

        let currentSortColumn = -1;
        let currentSortDir = 'asc';

        function sortTable(n) {
            const tableBody = document.getElementById("scheduleTableBody");
            const rows = Array.from(tableBody.rows);

            if (currentSortColumn === n) {
                currentSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortDir = 'asc';
                currentSortColumn = n;
            }

            rows.sort((a, b) => {
                const x = a.getElementsByTagName("TD")[n];
                const y = b.getElementsByTagName("TD")[n];

                const xValue = (x.getAttribute("data-order") || x.innerText.trim()).toLowerCase();
                const yValue = (y.getAttribute("data-order") || y.innerText.trim()).toLowerCase();

                if (xValue < yValue) return currentSortDir === 'asc' ? -1 : 1;
                if (xValue > yValue) return currentSortDir === 'asc' ? 1 : -1;
                return 0;
            });

            tableBody.append(...rows);
        }
    </script>
</body>
</html>

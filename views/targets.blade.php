<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>スポーツセンター管理</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">スポーツセンター管理</h1>
            <a href="{{ $basePath }}/" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded shadow">
                戻る
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <form action="{{ $basePath }}/targets" method="POST">
                <div class="overflow-x-auto">
                    <table class="min-w-full leading-normal mb-4">
                        <thead>
                            <tr>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    ID
                                </th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    名称
                                </th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    URL
                                </th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    操作
                                </th>
                            </tr>
                        </thead>
                        <tbody id="targetTableBody">
                            @foreach($targets as $index => $t)
                            <tr>
                                <td class="px-5 py-5 border-b border-gray-200 text-sm">
                                    <input type="text" name="targets[{{ $index }}][id]" value="{{ $t->id }}" class="w-full border rounded px-2 py-1" required>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 text-sm">
                                    <input type="text" name="targets[{{ $index }}][name]" value="{{ $t->name }}" class="w-full border rounded px-2 py-1" required>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 text-sm">
                                    <input type="url" name="targets[{{ $index }}][url]" value="{{ $t->url }}" class="w-full border rounded px-2 py-1" required>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 text-sm">
                                    <button type="button" onclick="removeRow(this)" class="text-red-600 hover:text-red-800">削除</button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center mt-4">
                    <button type="button" onclick="addRow()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow">
                        行を追加
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
                        保存
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function addRow() {
            const tbody = document.getElementById('targetTableBody');
            const index = tbody.rows.length;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-5 py-5 border-b border-gray-200 text-sm">
                    <input type="text" name="targets[${index}][id]" class="w-full border rounded px-2 py-1" required>
                </td>
                <td class="px-5 py-5 border-b border-gray-200 text-sm">
                    <input type="text" name="targets[${index}][name]" class="w-full border rounded px-2 py-1" required>
                </td>
                <td class="px-5 py-5 border-b border-gray-200 text-sm">
                    <input type="url" name="targets[${index}][url]" class="w-full border rounded px-2 py-1" required>
                </td>
                <td class="px-5 py-5 border-b border-gray-200 text-sm">
                    <button type="button" onclick="removeRow(this)" class="text-red-600 hover:text-red-800">削除</button>
                </td>
            `;
            tbody.appendChild(tr);
        }

        function removeRow(btn) {
            const row = btn.closest('tr');
            row.remove();
            // Re-index names to ensure consecutive indices for PHP array parsing
            const tbody = document.getElementById('targetTableBody');
            Array.from(tbody.rows).forEach((row, index) => {
                const inputs = row.querySelectorAll('input');
                inputs.forEach(input => {
                    const name = input.getAttribute('name');
                    input.setAttribute('name', name.replace(/targets\[\d+\]/, `targets[${index}]`));
                });
            });
        }
    </script>
</body>
</html>

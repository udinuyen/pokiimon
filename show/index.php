<?php
// index.php
// Lưu ý: server cần bật curl và allow_url_fopen = On (thường mặc định trên XAMPP/Laragon)
// Xử lý form khi submit
$pokemon = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = trim($_POST['query'] ?? '');
    if ($query === '') {
        $error = "Vui lòng nhập tên hoặc ID của Pokémon.";
    } else {
        // chuẩn hóa: lowercase, remove spaces
        $slug = strtolower($query);
        $slug = preg_replace('/\s+/', '-', $slug);

        $url = "https://pokeapi.co/api/v2/pokemon/" . urlencode($slug);

        // cURL request
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'PHP-PokeAPI-Client/1.0'
        ]);
        $resp = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            $error = "Lỗi kết nối: $curl_err";
        } elseif ($http_code !== 200) {
            if ($http_code === 404) $error = "Không tìm thấy Pokémon: " . htmlspecialchars($query);
            else $error = "Lỗi từ API (HTTP $http_code).";
        } else {
            $data = json_decode($resp, true);
            if (!is_array($data)) {
                $error = "Không thể đọc dữ liệu trả về.";
            } else {
                // Lấy các thông tin chính
                $pokemon = [
                    'name' => $data['name'] ?? '',
                    'id' => $data['id'] ?? '',
                    'height' => $data['height'] ?? '',
                    'weight' => $data['weight'] ?? '',
                    'sprites' => $data['sprites'] ?? [],
                    'types' => array_map(fn($t)=>$t['type']['name'], $data['types'] ?? []),
                    'abilities' => array_map(fn($a)=>$a['ability']['name'], $data['abilities'] ?? []),
                    'stats' => array_map(fn($s)=>['name'=>$s['stat']['name'],'base'=>$s['base_stat']], $data['stats'] ?? []),
                ];
            }
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Pokémon Lookup — PHP + Tailwind</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-start justify-center py-12">
  <div class="w-full max-w-3xl px-4">
    <header class="mb-6 text-center">
      <h1 class="text-3xl font-bold">Pokémon Lookup</h1>
      <p class="text-sm text-slate-600">Nhập tên (ví dụ: pikachu) hoặc ID (ví dụ: 25)</p>
    </header>

    <main>
      <form method="post" class="mb-6 bg-white p-6 rounded-2xl shadow">
        <div class="flex gap-3">
          <input name="query" required
                 value="<?= isset($query) ? htmlspecialchars($query) : '' ?>"
                 placeholder="Tên hoặc ID Pokémon..."
                 class="flex-1 px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300" />
          <button type="submit" class="px-5 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
            Tìm
          </button>
        </div>
        <?php if ($error): ?>
          <p class="mt-3 text-sm text-red-600"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
      </form>

      <?php if ($pokemon): ?>
        <section class="bg-white p-6 rounded-2xl shadow flex flex-col md:flex-row gap-6">
          <!-- Left: image & basic -->
          <div class="w-full md:w-1/3 flex flex-col items-center gap-4">
            <?php
              // ưu tiên sprite chính
              $sprite = $pokemon['sprites']['other']['official-artwork']['front_default']
                        ?? $pokemon['sprites']['front_default']
                        ?? $pokemon['sprites']['front_shiny']
                        ?? null;
            ?>
            <?php if ($sprite): ?>
              <img src="<?= htmlspecialchars($sprite) ?>" alt="<?= htmlspecialchars($pokemon['name']) ?>"
                   class="w-40 h-40 object-contain" />
            <?php else: ?>
              <div class="w-40 h-40 bg-slate-100 rounded flex items-center justify-center text-slate-400">
                No image
              </div>
            <?php endif; ?>

            <div class="text-center">
              <h2 class="text-xl font-semibold capitalize"><?= htmlspecialchars($pokemon['name']) ?></h2>
              <p class="text-sm text-slate-500">ID: <?= htmlspecialchars($pokemon['id']) ?></p>
            </div>

            <div class="w-full mt-2">
              <div class="text-sm text-slate-600">Types</div>
              <div class="flex gap-2 flex-wrap mt-2">
                <?php foreach ($pokemon['types'] as $t): ?>
                  <span class="px-3 py-1 rounded-full bg-slate-100 text-sm capitalize"><?= htmlspecialchars($t) ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Right: details -->
          <div class="w-full md:w-2/3">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="p-4 bg-slate-50 rounded">
                <h3 class="font-medium text-slate-700">Abilities</h3>
                <ul class="mt-2 list-disc pl-5 text-sm">
                  <?php foreach ($pokemon['abilities'] as $ab): ?>
                    <li class="capitalize"><?= htmlspecialchars($ab) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <div class="p-4 bg-slate-50 rounded">
                <h3 class="font-medium text-slate-700">Measurements</h3>
                <p class="text-sm mt-2">Height: <?= htmlspecialchars($pokemon['height']) ?> (decimetres)</p>
                <p class="text-sm">Weight: <?= htmlspecialchars($pokemon['weight']) ?> (hectograms)</p>
              </div>
            </div>

            <div class="mt-4 p-4 bg-slate-50 rounded">
              <h3 class="font-medium text-slate-700">Stats</h3>
              <div class="mt-3 space-y-3">
                <?php foreach ($pokemon['stats'] as $stat): ?>
                  <div>
                    <div class="flex justify-between text-sm mb-1 capitalize">
                      <span><?= htmlspecialchars($stat['name']) ?></span>
                      <span class="font-semibold"><?= htmlspecialchars($stat['base']) ?></span>
                    </div>
                    <div class="w-full bg-white rounded-full h-2">
                      <div style="width: <?= min(100, ($stat['base'] / 2)) ?>%;" class="h-2 rounded-full bg-indigo-400"></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="mt-4 text-sm text-slate-500">
              <p>Kết quả lấy từ <a href="https://pokeapi.co" class="text-indigo-600 underline" target="_blank" rel="noopener noreferrer">PokéAPI</a>.</p>
            </div>
          </div>
        </section>
      <?php endif; ?>
    </main>

    <footer class="mt-8 text-center text-xs text-slate-400">
      Mã nguồn ví dụ — PHP cURL + Tailwind. Chạy trên local hoặc server hỗ trợ PHP.
    </footer>
  </div>
</body>
</html>

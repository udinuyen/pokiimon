<?php
// index.php — Hiển thị toàn bộ danh sách Pokémon (không cần form)
$limit = 151; // bạn có thể đổi thành 1000 nếu muốn hiển thị hết
$url = "https://pokeapi.co/api/v2/pokemon?limit=$limit";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_USERAGENT => 'PHP-PokeAPI-Client/1.0'
]);
$response = curl_exec($ch);
curl_close($ch);

$pokemonList = [];
if ($response) {
    $data = json_decode($response, true);
    if (isset($data['results'])) {
        foreach ($data['results'] as $p) {
            $name = ucfirst($p['name']);
            // Lấy ID từ URL (số nằm giữa /pokemon/{id}/)
            preg_match('/\/pokemon\/(\d+)\//', $p['url'], $matches);
            $id = $matches[1] ?? null;
            if ($id) {
                // Link ảnh Pokémon chính thức
                $img = "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/{$id}.png";
                $pokemonList[] = [
                    'id' => $id,
                    'name' => $name,
                    'img' => $img,
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
  <title>Pokémon Gallery — PHP + Tailwind</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">
  <header class="text-center py-8">
    <h1 class="text-3xl font-bold text-indigo-600">Pokémon Gallery</h1>
    <p class="text-slate-500 mt-2">Hiển thị <?= count($pokemonList) ?> Pokémon đầu tiên</p>
  </header>

  <main class="max-w-7xl mx-auto px-4 pb-10">
    <div class="grid gap-6 grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8">
      <?php foreach ($pokemonList as $p): ?>
        <div class="bg-white rounded-2xl shadow hover:shadow-lg transition overflow-hidden text-center p-4">
          <img src="<?= htmlspecialchars($p['img']) ?>"
               alt="<?= htmlspecialchars($p['name']) ?>"
               class="w-28 h-28 object-contain mx-auto mb-3">
          <h2 class="font-semibold text-slate-800 capitalize"><?= htmlspecialchars($p['name']) ?></h2>
          <p class="text-xs text-slate-400">#<?= htmlspecialchars($p['id']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </main>

  <footer class="text-center text-xs text-slate-400 py-6">
    Nguồn dữ liệu: <a href="https://pokeapi.co" target="_blank" class="text-indigo-500">PokéAPI</a>
  </footer>
</body>
</html>

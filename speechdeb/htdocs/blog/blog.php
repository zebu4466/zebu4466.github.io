<?php
// === CONFIG ===
// Root of this blog directory on disk (this file lives in /blog/)
$blogRoot = __DIR__;

// Helper: get numeric subdirectories (years, months, days)
function getNumericSubdirs($dir) {
    if (!is_dir($dir)) return [];
    $items = scandir($dir);
    $dirs = [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($full) && ctype_digit($item)) {
            $dirs[] = $item;
        }
    }
    sort($dirs, SORT_NUMERIC); // oldest → newest
    return $dirs;
}

// Helper: pretty month name
function monthName($monthNumber) {
    return date('F', mktime(0, 0, 0, (int)$monthNumber, 1));
}

// Find latest post as per your folder convention: /blog/YYYY/MM/DD/slug.html
function findLatestPost($root) {
    $years = getNumericSubdirs($root);
    if (empty($years)) return null;
    $latestYear = end($years); // newest year

    $yearPath = $root . DIRECTORY_SEPARATOR . $latestYear;
    $months = getNumericSubdirs($yearPath);
    if (empty($months)) return null;
    $latestMonth = end($months); // newest month

    $monthPath = $yearPath . DIRECTORY_SEPARATOR . $latestMonth;
    $days = getNumericSubdirs($monthPath);
    if (empty($days)) return null;
    $latestDay = end($days); // newest day

        $dayPath = $monthPath . DIRECTORY_SEPARATOR . $latestDay;

    // Find latest .php file in that day folder
    $files = glob($dayPath . DIRECTORY_SEPARATOR . '*.php');
    if (empty($files)) return null;

    // Pick the most recently modified PHP file
    usort($files, function($a, $b) {
        return filemtime($a) <=> filemtime($b);
    });
    $latestFile = end($files);
    $filename   = basename($latestFile);

    // Build URL path: /blog/YYYY/MM/DD/filename.php
    $urlPath = "/blog/{$latestYear}/{$latestMonth}/{$latestDay}/{$filename}";

    // Base URL (https://yourdomain.com)
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $scheme . '://' . $host;

    // Fetch the rendered HTML so PHP runs
    $title = $filename; // fallback
    $html  = @file_get_contents($baseUrl . $urlPath);
    if ($html !== false) {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $titleTags = $dom->getElementsByTagName('title');
        if ($titleTags->length > 0) {
            $title = $titleTags->item(0)->textContent;
        }
    }

    // Human-date like "29 December 2025"
    $dateObj = DateTime::createFromFormat('Y-m-d', "{$latestYear}-{$latestMonth}-{$latestDay}");
    $humanDate = $dateObj ? $dateObj->format('j F Y') : "{$latestDay} " . monthName($latestMonth) . " {$latestYear}";

    return [
        'year'       => $latestYear,
        'month'      => $latestMonth,
        'day'        => $latestDay,
        'date_human' => $humanDate,
        'url'        => $urlPath,
        'title'      => $title,
    ];
}

$latestPost = findLatestPost($blogRoot);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Speechdeb Blog</title>
  <link rel="stylesheet" href="../styles.css" />

  <!-- Import shared UI elements -->
  <script type="module" src="../common.js"></script>
    <style>
#supportPopup {
  display: block;
}
    </style>
</head>
<body>
  <!-- Main content wrapper -->
  <main style="display: flex; justify-content: center; align-items: flex-start;">
    <!-- Blog box -->
    <div id="supportPopup"
         style="width: 50%; height: auto; padding: 30px; border: 1px solid #ccc;
                border-radius: 10px; background-color: #fdfdfd; margin-top: 20px;">
      
      <div class="headingContainer"
           style="display: flex; flex-direction: column; align-items: flex-start;">
        <h1 style="margin: 0;">Speechdeb Blog</h1>
      </div>

      <hr />
      <p>
        Welcome to the official blog of The Speechdeb Team! We regularly post updates here
        about new features we plan to add (or may have already added)! Stay tuned for more info,
        and be sure to look at our recent posts below.
      </p>
      <br>

      <?php if ($latestPost): ?>
        <!-- Latest post section -->
        <section>
          <h2><?= htmlspecialchars($latestPost['date_human']) ?></h2>
          <hr>
          <a href="<?= htmlspecialchars($latestPost['url']) ?>">
            <?= htmlspecialchars($latestPost['title']) ?>
          </a>
        </section>
      <?php else: ?>
        <p><em>No posts found yet. Once a new post is written, it’ll appear here.</em></p>
      <?php endif; ?>

    </div>
  </main>
</body>
</html>
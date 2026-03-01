<?php
// 1) Set your post title ONCE here:
$postTitle = "Introducing Guest Mode!";  // ← change this per post

// 2) Infer the date from the folder structure /blog/YYYY/MM/DD/
$pathParts   = explode(DIRECTORY_SEPARATOR, __DIR__);
$partsCount  = count($pathParts);

$day   = $pathParts[$partsCount - 1] ?? null;
$month = $pathParts[$partsCount - 2] ?? null;
$year  = $pathParts[$partsCount - 3] ?? null;

$updatedText = "Unknown date";

if ($year && $month && $day && ctype_digit($year) && ctype_digit($month) && ctype_digit($day)) {
    $dt = DateTime::createFromFormat('!Y-n-j', "$year-$month-$day");
    if ($dt !== false) {
        // Example: "29 Dec 2025"
        $updatedText = $dt->format('j M Y');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($postTitle) ?></title>
  <link rel="stylesheet" href="https://speechdeb.infy.uk/styles.css" />

  <!-- Import shared UI elements -->
  <script type="module" src="https://speechdeb.infy.uk/common.js"></script>
<style>
  /* Make the page a vertical stack: header → main → footer */
  html, body {
    margin: 0;
    padding: 0;
    height: 100%;
  }

  body {
    display: flex;
    flex-direction: column;
  }

  /* Header (version banner) stays at the top */
  #versionBannerMenu {
    flex-shrink: 0;
  }

  /* Main area with the blog box */
  main.blog-layout {
    flex: 1 0 auto;                /* sit between header & footer */
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 30px 0;               /* space between header/footer and box */
    box-sizing: border-box;
  }

/* Blog pages: use supportPopup as a normal content box */
.blog-page #supportPopup {
  position: static;
  top: auto;
  left: auto;
  transform: none;
  display: block;
  width: 50%;
  margin: 40px auto;
  border-radius: 12px;
}

/* Center the screenshot nicely */
.blog-page #supportPopup img {
  max-width: 100%;
  height: 350px;
  display: block;
  margin: 20px auto 0;
}

  /* Footer should come right after main, not overlay content */
  #footer {
    position: static !important;   /* kill any fixed/sticky rules */
    bottom: auto !important;
    flex-shrink: 0;
    margin-top: 20px;              /* little space under the box */
  }
</style>
</head>
<body class="blog-page">
  <!-- Main content wrapper -->
  <main class="blog-layout">
    <!-- Blog box -->
    <div id="supportPopup">
      
      <div class="headingContainer"
           style="display: flex; flex-direction: column; align-items: flex-start;">
        
        <!-- 🔙 Back link above the title -->
        <a href="../../../blog.php"
           style="color:#007ACC; text-decoration:none; font-weight:bold; margin-bottom:10px;">
          ← Back to list
        </a>

        <!-- Title automatically matches <title> -->
        <h2 style="margin: 0;"><?= htmlspecialchars($postTitle) ?></h2>
        <em style="margin: 4px 0 0 0;">
          Updated <?= htmlspecialchars($updatedText) ?>
        </em>
      </div>

      <hr />
      <p>We're excited to introduce Guest Mode, a new feature that will allow you to write speeches without the hassle of creating an account! To activate this feature, simply click the "stay logged out" option as in the screenshot below, and you'll be taken to the editor to start a new speech!</p>
        <p>A few things to keep in mind:</p>
        <ul>
            <li>Currently, the website only supports one guest account (i.e. Guest Mode user) on a device.</li>
            <li>Any speeches created using Guest Mode, by nature, will be restricted to your device only, and will not be shareable with anyone else.</li>
            <li>There is currently no option to lock a guest account via password, so anyone using your device will be able to access your speeches. Please be careful!</li>
            <li>The Speechdeb Team will be unable to restore any deleted Guest Mode speeches.</li>
        </ul>
      <em>Please note, this feature is currently still in beta testing, so there may be glitches. If you experience any issues, we insist you contact our support team. Additionally, our blog will be updated when any additions to the feature are made.</em><br>
		<img src="./example.png" class="blog-image" alt="Guest mode example">
    </div>
  </main>
</body>
</html>
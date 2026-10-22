<?php
$dbFile = 'database.json';
$uploadDir = 'uploads/';

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!file_exists($dbFile)) {
    $initialData = [
        'apks' => [],
        'courses' => [],
        'logo' => ''
    ];
    file_put_contents($dbFile, json_encode($initialData, JSON_PRETTY_PRINT));
}

// AJAX Backend Handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $rawJSON = file_get_contents($dbFile);
    $data = json_decode($rawJSON, true) ?: ['apks' => [], 'courses' => [], 'logo' => ''];
    $action = $_POST['action'];

    if ($action === 'save_item') {
        $type = $_POST['type'] ?? 'apk';
        $title = trim($_POST['title'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $desc = trim($_POST['desc'] ?? '');
        $imagePath = '';

        // Handle file upload safely to prevent server errors
        if (isset($_FILES['imageFile']) && $_FILES['imageFile']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['imageFile']['tmp_name'];
            $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9._]/", "", basename($_FILES['imageFile']['name']));
            $destPath = $uploadDir . $fileName;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $imagePath = $destPath;
            }
        }

        if (empty($imagePath)) {
            echo json_encode(['status' => 'error', 'message' => 'Image upload failed. Check file size!']);
            exit;
        }

        $newItem = [
            'id' => 'item-' . time() . '-' . rand(100, 999),
            'title' => $title,
            'image' => $imagePath,
            'link' => $link,
            'desc' => $desc,
            'date' => date('d M Y')
        ];

        if ($type === 'apk') {
            array_unshift($data['apks'], $newItem);
        } else {
            array_unshift($data['courses'], $newItem);
        }

        if (file_put_contents($dbFile, json_encode($data, JSON_PRETTY_PRINT))) {
            echo json_encode(['status' => 'success', 'message' => 'Published successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to write database.json!']);
        }
        exit;
    }

    if ($action === 'delete_item') {
        $type = $_POST['type'] ?? '';
        $id = $_POST['id'] ?? '';
        if ($type === 'apk') {
            $data['apks'] = array_values(array_filter($data['apks'], function($item) use ($id) { return $item['id'] !== $id; }));
        } else {
            $data['courses'] = array_values(array_filter($data['courses'], function($item) use ($id) { return $item['id'] !== $id; }));
        }
        file_put_contents($dbFile, json_encode($data, JSON_PRETTY_PRINT));
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action === 'save_logo') {
        $logoPath = '';
        if (isset($_FILES['logoFile']) && $_FILES['logoFile']['error'] === UPLOAD_ERR_OK) {
            $fileName = 'logo_' . time() . '_' . basename($_FILES['logoFile']['name']);
            $destPath = $uploadDir .$fileName;
            if (move_uploaded_file($_FILES['logoFile']['tmp_name'],$destPath)) {
                $logoPath =$destPath;
            }
        }
        if ($logoPath) {
            $data['logo'] =$logoPath;
            file_put_contents($dbFile, json_encode($data, JSON_PRETTY_PRINT));
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Logo upload failed']);
        }
        exit;
    }
}

if (isset($_GET['get_data'])) {
    header('Content-Type: application/json');
    echo file_get_contents($dbFile);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TANOLI STUDIO — Pro Editing Vault</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
            display: ['"Space Grotesk"', 'sans-serif']
          },
          colors: {
            brand: {
              50: '#F0FDF4',
              100: '#DCFCE7',
              500: '#10B981',
              600: '#059669',
              700: '#047857',
              900: '#064E3B'
            }
          }
        }
      }
    }
  </script>

  <style>
    body {
      background: radial-gradient(circle at 50% 10%, #EDF8F2 0%, #D8EDE1 45%, #C8E5D3 95%);
      color: #1E293B;
      min-height: 100vh;
      overflow-x: hidden;
    }
    
    /* Heavy Glassmorphism with High Blur */
    .pro-glass {
      background: rgba(255, 255, 255, 0.82);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid rgba(255, 255, 255, 0.95);
      box-shadow: 0 20px 40px -10px rgba(6, 78, 59, 0.12);
    }

    .pro-card {
      background: rgba(255, 255, 255, 0.88);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.95);
      box-shadow: 0 16px 32px -8px rgba(6, 78, 59, 0.1);
      transition: all 0.3s ease;
    }
    .pro-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 22px 40px -10px rgba(6, 78, 59, 0.18);
    }

    .btn-gradient {
      background: linear-gradient(135deg, #10B981 0%, #059669 100%);
      box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.4);
      transition: all 0.2s ease;
    }
    .btn-gradient:hover {
      transform: translateY(-1px);
      opacity: 0.95;
    }

    .drawer-open { transform: translateX(0%); }
    .drawer-closed { transform: translateX(-100%); }

    /* Page Loading Overlay */
    #pageLoader {
      transition: opacity 0.25s ease;
    }
  </style>
</head>

<body class="font-sans antialiased selection:bg-brand-500 selection:text-white flex flex-col justify-between min-h-screen">

  <!-- PAGE LOADING OVERLAY -->
  <div id="pageLoader" class="fixed inset-0 bg-white/70 backdrop-blur-md z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity">
    <div class="pro-card p-6 rounded-3xl flex items-center gap-3 shadow-2xl border border-brand-200">
      <i data-lucide="loader-2" class="w-6 h-6 animate-spin text-brand-600"></i>
      <span class="text-xs font-bold text-slate-800 tracking-wide uppercase">Loading Vault...</span>
    </div>
  </div>

  <!-- HEADER -->
  <header class="w-full sticky top-0 z-40 pro-glass border-b border-brand-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-8 py-3.5 flex items-center justify-between gap-4">
      
      <a href="javascript:void(0)" onclick="navigateTo('dashboard')" class="flex items-center gap-3 group">
        <div id="siteLogoContainer" class="w-10 h-10 rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 flex items-center justify-center text-white font-display font-bold text-xl shadow-md overflow-hidden shrink-0">
          T
        </div>
        <div class="flex flex-col">
          <span class="font-display font-extrabold text-lg tracking-tight text-slate-900 leading-none">
            TANOLI <span class="text-brand-600">STUDIO</span>
          </span>
          <span class="text-[9px] uppercase tracking-widest font-bold text-brand-700 mt-0.5">Pro Editing Vault</span>
        </div>
      </a>

      <nav class="hidden md:flex items-center gap-1 bg-white/60 p-1 rounded-2xl border border-brand-200 shadow-sm">
        <button onclick="navigateTo('dashboard')" class="nav-btn px-4 py-2 rounded-xl text-xs font-bold transition-all bg-brand-600 text-white shadow-xs" data-page="dashboard">Dashboard</button>
        <button onclick="navigateTo('apks')" class="nav-btn px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:text-slate-900 hover:bg-white/80 transition-all" data-page="apks">Pro APKs</button>
        <button onclick="navigateTo('courses')" class="nav-btn px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:text-slate-900 hover:bg-white/80 transition-all" data-page="courses">Masterclasses</button>
      </nav>

      <div class="flex items-center gap-3">
        <div class="relative hidden sm:block w-52 lg:w-64">
          <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
          <input 
            type="text" 
            placeholder="Search resources..." 
            oninput="handleSearch(this.value)"
            class="w-full bg-white/80 border border-brand-200 focus:border-brand-500 focus:bg-white rounded-2xl pl-9 pr-4 py-2 text-xs text-slate-800 placeholder-slate-400 outline-none transition-all shadow-inner"
          />
        </div>

        <div id="desktopAuthContainer"></div>

        <button onclick="toggleMobileDrawer()" class="md:hidden p-2.5 rounded-2xl pro-glass text-slate-700 hover:bg-white">
          <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
      </div>

    </div>
  </header>

  <!-- SIDE MENU / MOBILE DRAWER -->
  <div id="mobileDrawerBackdrop" onclick="toggleMobileDrawer()" class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 hidden"></div>
  <aside id="mobileDrawer" class="fixed top-0 left-0 bottom-0 w-72 bg-white/95 backdrop-blur-2xl z-50 p-6 shadow-2xl flex flex-col justify-between drawer-closed transition-transform duration-300 border-r border-brand-100 md:hidden">
    <div>
      <div class="flex items-center justify-between pb-6 border-b border-brand-100">
        <span class="font-display font-bold text-lg text-slate-900">TANOLI <span class="text-brand-600">STUDIO</span></span>
        <button onclick="toggleMobileDrawer()" class="p-2 rounded-xl bg-slate-100 text-slate-600">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>

      <nav class="mt-6 space-y-1.5 text-xs font-bold text-slate-700">
        <button onclick="mobileNav('dashboard')" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-brand-50 hover:text-brand-800">
          <i data-lucide="layout-grid" class="w-4 h-4 text-brand-600"></i> Dashboard
        </button>
        <button onclick="mobileNav('apks')" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-brand-50 hover:text-brand-800">
          <i data-lucide="download" class="w-4 h-4 text-brand-600"></i> Pro APKs
        </button>
        <button onclick="mobileNav('courses')" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-brand-50 hover:text-brand-800">
          <i data-lucide="film" class="w-4 h-4 text-brand-600"></i> Masterclasses
        </button>
        <button onclick="mobileNav('login')" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-brand-50 hover:text-brand-800">
          <i data-lucide="log-in" class="w-4 h-4 text-brand-600"></i> Login Page
        </button>
        <button onclick="mobileNav('signup')" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-brand-50 hover:text-brand-800">
          <i data-lucide="user-plus" class="w-4 h-4 text-brand-600"></i> Sign Up Page
        </button>
        <button onclick="mobileNav('privacy')" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-brand-50 hover:text-brand-800">
          <i data-lucide="shield" class="w-4 h-4 text-brand-600"></i> Privacy Policy
        </button>
        <button onclick="mobileNav('contact')" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-brand-50 hover:text-brand-800">
          <i data-lucide="mail" class="w-4 h-4 text-brand-600"></i> Contact Us
        </button>
      </nav>
    </div>

    <div id="mobileAuthContainer" class="pt-4 border-t border-brand-100"></div>
  </aside>

  <!-- DASHBOARD -->
  <main id="page-dashboard" class="page-view w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex-grow">
    
    <div class="pro-card rounded-[32px] p-8 sm:p-12 mb-10 relative overflow-hidden">
      <div class="max-w-2xl space-y-4">
        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-brand-100 border border-brand-200 text-brand-900 text-xs font-bold shadow-xs">
          <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
          Direct Unlocked Studio Tools
        </span>

        <h1 class="font-display font-extrabold text-3xl sm:text-5xl text-slate-900 leading-tight tracking-tight">
          Next-Level Editing Tools & Pro Ecosystem.
        </h1>

        <p class="text-slate-600 text-xs sm:text-sm leading-relaxed">
          Access high-performance unlocked video editing APKs, cinematic grading tools, and curated masterclasses with zero ads.
        </p>

        <div class="flex flex-wrap items-center gap-3.5 pt-2">
          <button onclick="navigateTo('apks')" class="btn-gradient px-7 py-3 rounded-2xl text-white font-bold text-xs tracking-wide inline-flex items-center gap-2">
            <i data-lucide="download" class="w-4 h-4"></i> Explore Pro APKs
          </button>
          <button onclick="navigateTo('courses')" class="px-6 py-3 rounded-2xl bg-white hover:bg-brand-50 text-slate-800 font-bold text-xs border border-brand-200 transition-all inline-flex items-center gap-2 shadow-xs">
            <i data-lucide="play" class="w-4 h-4 text-brand-600"></i> Masterclasses
          </button>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <div class="pro-card rounded-[28px] p-6">
        <div class="flex items-center justify-between pb-4 border-b border-brand-100">
          <div class="flex items-center gap-2.5">
            <div class="p-2.5 rounded-xl bg-brand-100 text-brand-700">
              <i data-lucide="download" class="w-5 h-5"></i>
            </div>
            <div>
              <h3 class="font-display font-bold text-base text-slate-900">Latest Pro APKs</h3>
              <p class="text-[11px] text-slate-500">Direct studio builds</p>
            </div>
          </div>
          <button onclick="navigateTo('apks')" class="text-xs font-bold text-brand-700 hover:underline">View Vault →</button>
        </div>
        <div id="homeApkPreview" class="mt-4 space-y-3"></div>
      </div>

      <div class="pro-card rounded-[28px] p-6">
        <div class="flex items-center justify-between pb-4 border-b border-brand-100">
          <div class="flex items-center gap-2.5">
            <div class="p-2.5 rounded-xl bg-blue-100 text-blue-700">
              <i data-lucide="film" class="w-5 h-5"></i>
            </div>
            <div>
              <h3 class="font-display font-bold text-base text-slate-900">Video Masterclasses</h3>
              <p class="text-[11px] text-slate-500">Color grading & VFX guides</p>
            </div>
          </div>
          <button onclick="navigateTo('courses')" class="text-xs font-bold text-brand-700 hover:underline">View Vault →</button>
        </div>
        <div id="homeCoursePreview" class="mt-4 space-y-3"></div>
      </div>
    </div>
  </main>

  <!-- PRO APKS SECTION -->
  <section id="page-apks" class="page-view hidden max-w-7xl mx-auto px-4 sm:px-8 py-10 w-full flex-grow">
    <div class="flex items-center justify-between gap-4 mb-8">
      <div>
        <h2 class="font-display font-bold text-2xl text-slate-900">Pro APK Downloads</h2>
        <p class="text-slate-600 text-xs mt-1">Direct unlocked editing applications</p>
      </div>
      <button onclick="navigateTo('dashboard')" class="px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold border border-brand-200 shadow-xs">← Dashboard</button>
    </div>
    <div id="apksGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>
  </section>

  <!-- COURSES SECTION -->
  <section id="page-courses" class="page-view hidden max-w-7xl mx-auto px-4 sm:px-8 py-10 w-full flex-grow">
    <div class="flex items-center justify-between gap-4 mb-8">
      <div>
        <h2 class="font-display font-bold text-2xl text-slate-900">Video Masterclasses</h2>
        <p class="text-slate-600 text-xs mt-1">Cinematic color curves, VFX & pacing guides</p>
      </div>
      <button onclick="navigateTo('dashboard')" class="px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold border border-brand-200 shadow-xs">← Dashboard</button>
    </div>
    <div id="coursesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>
  </section>

  <!-- LOGIN PAGE -->
  <section id="page-login" class="page-view hidden max-w-md mx-auto px-4 py-16 w-full flex-grow">
    <div class="pro-card rounded-[32px] p-8 border border-brand-200 shadow-xl">
      <div class="flex items-center justify-between mb-6">
        <button onclick="navigateTo('dashboard')" class="text-xs font-bold text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
          <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </button>
        <span class="text-[10px] font-extrabold text-brand-700 bg-brand-100 px-2.5 py-1 rounded-full uppercase">Secure Login</span>
      </div>
      <h2 class="font-display font-bold text-2xl text-slate-900 mb-6 text-center">Member Login</h2>
      <form onsubmit="handleLoginSubmit(event)" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Username</label>
          <input type="text" id="loginUsername" required placeholder="Enter username" class="w-full bg-slate-50 border border-brand-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 font-semibold focus:outline-none focus:border-brand-500" />
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Password</label>
          <input type="password" id="loginPassword" required placeholder="••••••••" class="w-full bg-slate-50 border border-brand-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 font-semibold focus:outline-none focus:border-brand-500" />
        </div>
        <button type="submit" class="btn-gradient w-full py-3 rounded-xl text-white font-bold text-xs uppercase tracking-wider">Log In</button>
      </form>
      <div class="mt-4 text-center text-xs text-slate-600">
        Don't have an account? <button onclick="navigateTo('signup')" class="font-bold text-brand-700 hover:underline">Sign Up</button>
      </div>
    </div>
  </section>

  <!-- SIGN UP PAGE -->
  <section id="page-signup" class="page-view hidden max-w-md mx-auto px-4 py-16 w-full flex-grow">
    <div class="pro-card rounded-[32px] p-8 border border-brand-200 shadow-xl">
      <div class="flex items-center justify-between mb-6">
        <button onclick="navigateTo('dashboard')" class="text-xs font-bold text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
          <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </button>
        <span class="text-[10px] font-extrabold text-brand-700 bg-brand-100 px-2.5 py-1 rounded-full uppercase">New Account</span>
      </div>
      <h2 class="font-display font-bold text-2xl text-slate-900 mb-6 text-center">Create Account</h2>
      <form onsubmit="handleSignupSubmit(event)" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Username</label>
          <input type="text" id="signupUsername" required placeholder="Choose username" class="w-full bg-slate-50 border border-brand-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 font-semibold focus:outline-none focus:border-brand-500" />
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Email Address</label>
          <input type="email" id="signupEmail" required placeholder="creator@studio.com" class="w-full bg-slate-50 border border-brand-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 font-semibold focus:outline-none focus:border-brand-500" />
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Password</label>
          <input type="password" id="signupPassword" required placeholder="At least 6 characters" class="w-full bg-slate-50 border border-brand-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 font-semibold focus:outline-none focus:border-brand-500" />
        </div>
        <button type="submit" class="btn-gradient w-full py-3 rounded-xl text-white font-bold text-xs uppercase tracking-wider">Sign Up</button>
      </form>
      <div class="mt-4 text-center text-xs text-slate-600">
        Already have an account? <button onclick="navigateTo('login')" class="font-bold text-brand-700 hover:underline">Log In</button>
      </div>
    </div>
  </section>

  <!-- ADMIN CONTROL PANEL -->
  <section id="page-admin" class="page-view hidden max-w-4xl mx-auto px-4 py-10 w-full flex-grow">
    <div class="pro-card rounded-[32px] p-6 sm:p-8 border-2 border-brand-400">
      
      <div class="flex items-center justify-between pb-6 border-b border-brand-100">
        <div class="flex items-center gap-3">
          <div class="p-2.5 rounded-2xl bg-brand-600 text-white font-bold shadow-md">
            <i data-lucide="shield-check" class="w-6 h-6"></i>
          </div>
          <div>
            <h3 class="font-display font-bold text-xl text-slate-900">Admin Control Suite</h3>
            <p class="text-xs text-brand-700 font-semibold">Publish updates live to database.json</p>
          </div>
        </div>
        <button onclick="navigateTo('dashboard')" class="text-xs font-bold text-slate-500 hover:text-slate-800">← Dashboard</button>
      </div>

      <!-- Website Logo Uploader -->
      <div class="mt-6 p-5 rounded-2xl bg-white/70 border border-brand-200">
        <h4 class="font-bold text-xs text-slate-900 mb-2 flex items-center gap-2">
          <i data-lucide="image" class="w-4 h-4 text-brand-600"></i> Update Website Brand Logo
        </h4>
        <div class="flex items-center gap-4">
          <div id="adminLogoPreview" class="w-12 h-12 rounded-xl bg-slate-100 border border-brand-200 flex items-center justify-center overflow-hidden shrink-0 font-bold text-slate-700">T</div>
          <input type="file" id="logoFileInput" accept="image/*" class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand-600 file:text-white cursor-pointer" onchange="handleAdminLogoUpload(event)" />
        </div>
      </div>

      <!-- Publish Form (Fixed with multipart/form-data upload) -->
      <div class="mt-6 p-6 rounded-2xl bg-white/70 border border-brand-200 shadow-sm">
        <h4 class="font-bold text-sm text-slate-900 mb-4 flex items-center gap-2">
          <i data-lucide="plus-circle" class="w-4 h-4 text-brand-600"></i> Publish New Resource Live
        </h4>
        
        <form id="adminPublishForm" onsubmit="handleAdminCreateItem(event)" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Type</label>
            <select id="adminType" class="w-full bg-slate-50 border border-brand-200 rounded-xl px-3 py-2 text-xs text-slate-800 font-semibold outline-none focus:border-brand-500">
              <option value="apk">Premium APK Build</option>
              <option value="course">Video Editing Masterclass</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Title</label>
            <input type="text" id="adminTitle" required placeholder="e.g. CapCut Pro VIP Mod" class="w-full bg-slate-50 border border-brand-200 rounded-xl px-3 py-2 text-xs text-slate-800 outline-none focus:border-brand-500" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Thumbnail / App Icon File</label>
            <input type="file" id="adminItemImageFile" accept="image/*" required class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-900 file:text-white cursor-pointer" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Download / Video Link</label>
            <input type="text" id="adminLink" required placeholder="https://drive.google.com/..." class="w-full bg-slate-50 border border-brand-200 rounded-xl px-3 py-2 text-xs text-slate-800 outline-none focus:border-brand-500" />
          </div>
          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Description</label>
            <textarea id="adminDesc" rows="2" required placeholder="Resource details..." class="w-full bg-slate-50 border border-brand-200 rounded-xl px-3 py-2 text-xs text-slate-800 outline-none focus:border-brand-500"></textarea>
          </div>
          
          <div class="md:col-span-2 flex justify-end">
            <button type="submit" id="publishBtn" class="btn-gradient px-7 py-3 rounded-xl text-white font-bold text-xs flex items-center gap-2">
              <i data-lucide="send" class="w-4 h-4"></i> Publish Now
            </button>
          </div>
        </form>
      </div>

      <!-- Published Inventory -->
      <div class="mt-8">
        <h4 class="font-bold text-xs text-slate-600 mb-3">Live Items in database.json</h4>
        <div class="overflow-x-auto rounded-2xl border border-brand-200 bg-white/50">
          <table class="w-full text-left text-xs">
            <thead class="bg-brand-50 text-slate-700 font-bold border-b border-brand-200">
              <tr>
                <th class="p-3">Thumbnail</th>
                <th class="p-3">Title</th>
                <th class="p-3">Type</th>
                <th class="p-3 text-right">Action</th>
              </tr>
            </thead>
            <tbody id="adminInventoryTable" class="divide-y divide-brand-100"></tbody>
          </table>
        </div>
      </div>

    </div>
  </section>

  <!-- PRIVACY POLICY -->
  <section id="page-privacy" class="page-view hidden max-w-4xl mx-auto px-4 py-12 w-full flex-grow">
    <div class="pro-card rounded-[32px] p-8 space-y-4 text-xs text-slate-700 leading-relaxed">
      <h2 class="font-display font-bold text-xl text-slate-900 pb-3 border-b border-brand-100">Privacy Policy</h2>
      <p>At TANOLI STUDIO, accessible from our website, user privacy is paramount. This document outlines information collected and used.</p>
      <h3 class="font-bold text-slate-900 pt-2">Google AdSense Cookies</h3>
      <p>Google uses cookies (including DART cookies) to serve targeted ads based on web visits. Manage ad preferences at: <a href="https://policies.google.com/technologies/ads" class="text-brand-600 underline" target="_blank">Google Ads Policy</a>.</p>
      <p class="pt-4 border-t border-brand-100">Contact: <strong>factronix.business@gmail.com</strong></p>
    </div>
  </section>

  <!-- CONTACT US -->
  <section id="page-contact" class="page-view hidden max-w-md mx-auto px-4 py-16 w-full flex-grow text-center">
    <div class="pro-card rounded-[32px] p-8 space-y-4 shadow-xl">
      <div class="w-12 h-12 rounded-2xl bg-brand-100 text-brand-700 flex items-center justify-center mx-auto">
        <i data-lucide="mail" class="w-6 h-6"></i>
      </div>
      <h2 class="font-display font-bold text-xl text-slate-900">Contact Studio Support</h2>
      <p class="text-xs text-slate-500">For inquiries, partnerships, or assistance:</p>
      <div class="p-4 rounded-xl bg-slate-50 border border-brand-200 text-brand-700 font-bold text-sm select-all">
        factronix.business@gmail.com
      </div>
    </div>
  </section>

  <!-- TOAST NOTIFICATION -->
  <div id="toast" class="fixed bottom-6 right-6 z-50 transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none pro-glass rounded-2xl p-4 shadow-2xl flex items-center gap-3 border border-brand-200 max-w-sm">
    <div class="p-2 rounded-xl bg-brand-600 text-white shrink-0">
      <i data-lucide="check" class="w-4 h-4"></i>
    </div>
    <div>
      <div id="toastTitle" class="text-xs font-bold text-slate-900">Notification</div>
      <div id="toastMsg" class="text-[11px] text-slate-600">Success.</div>
    </div>
  </div>

  <!-- FOOTER -->
  <footer class="w-full py-6 px-4 text-center text-xs text-slate-600 border-t border-brand-200 mt-10 pro-glass">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <span class="font-display font-bold text-slate-900">TANOLI STUDIO</span>
        <span>• High-Performance Resource Hub</span>
      </div>
      <div class="flex gap-4 font-semibold">
        <button onclick="navigateTo('privacy')" class="hover:text-brand-700">Privacy Policy</button>
        <button onclick="navigateTo('contact')" class="hover:text-brand-700">Contact</button>
      </div>
    </div>
  </footer>

  <!-- JAVASCRIPT -->
  <script>
    let globalDB = { apks: [], courses: [], logo: '' };

    function fetchDBData(callback) {
      fetch('?get_data=1&t=' + Date.now())
        .then(res => res.json())
        .then(data => {
          globalDB = data;
          if (callback) callback(data);
        })
        .catch(err => console.error('Fetch error:', err));
    }

    function loadSiteLogo() {
      fetchDBData(data => {
        const customLogo = data.logo;
        const logoContainers = [
          document.getElementById('siteLogoContainer'),
          document.getElementById('adminLogoPreview')
        ];
        logoContainers.forEach(container => {
          if (!container) return;
          if (customLogo) {
            container.innerHTML = `<img src="${customLogo}" alt="Logo" class="w-full h-full object-cover" />`;
          } else {
            container.innerHTML = `T`;
          }
        });
      });
    }

    function handleAdminLogoUpload(e) {
      const file = e.target.files[0];
      if (!file) return;
      const formData = new FormData();
      formData.append('action', 'save_logo');
      formData.append('logoFile', file);

      fetch('', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(() => {
          loadSiteLogo();
          showToast('Logo Updated', 'Logo changed globally across site!');
        });
    }

    function navigateTo(pageId) {
      // Show loading overlay
      const loader = document.getElementById('pageLoader');
      loader.classList.remove('opacity-0', 'pointer-events-none');

      setTimeout(() => {
        document.querySelectorAll('.page-view').forEach(view => view.classList.add('hidden'));
        const target = document.getElementById(`page-${pageId}`);
        if (target) {
          target.classList.remove('hidden');
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        fetchDBData(data => {
          if (pageId === 'dashboard') renderHomePreviews(data);
          if (pageId === 'apks' || pageId === 'courses') renderCards(data);
          if (pageId === 'admin') renderAdminInventory(data);
          
          // Hide loading overlay smoothly
          loader.classList.add('opacity-0', 'pointer-events-none');
        });
      }, 200);
    }

    function toggleMobileDrawer() {
      const drawer = document.getElementById('mobileDrawer');
      const backdrop = document.getElementById('mobileDrawerBackdrop');
      drawer.classList.toggle('drawer-closed');
      drawer.classList.toggle('drawer-open');
      backdrop.classList.toggle('hidden');
    }

    function mobileNav(page) {
      toggleMobileDrawer();
      navigateTo(page);
    }

    function renderNavAuth() {
      const desktopAuth = document.getElementById('desktopAuthContainer');
      const mobileAuth = document.getElementById('mobileAuthContainer');
      const user = getCurrentUser();

      if (user) {
        desktopAuth.innerHTML = `
          <div class="flex items-center gap-2">
            ${user.isAdmin ? `<button onclick="navigateTo('admin')" class="px-3 py-1.5 rounded-xl bg-amber-100 text-amber-800 border border-amber-300 font-bold text-xs shadow-xs">Admin Panel</button>` : ''}
            <span class="text-xs font-bold text-slate-800 px-2.5 py-1 bg-white rounded-xl border border-brand-200 shadow-xs">${user.username}</span>
            <button onclick="handleLogout()" class="p-2 rounded-xl bg-red-50 text-red-600 hover:bg-red-100" title="Logout"><i data-lucide="log-out" class="w-4 h-4"></i></button>
          </div>
        `;
      } else {
        desktopAuth.innerHTML = `
          <div class="flex items-center gap-2">
            <button onclick="navigateTo('login')" class="px-4 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-800 text-xs font-bold border border-brand-200 shadow-xs">Log In</button>
            <button onclick="navigateTo('signup')" class="btn-gradient px-4 py-2 rounded-xl text-white text-xs font-bold shadow-xs">Sign Up</button>
          </div>
        `;
      }
      lucide.createIcons();
    }

    function getCurrentUser() {
      const data = localStorage.getItem('tanoli_auth_user');
      return data ? JSON.parse(data) : null;
    }

    function handleLoginSubmit(e) {
      e.preventDefault();
      const userVal = document.getElementById('loginUsername').value.trim();
      const passVal = document.getElementById('loginPassword').value.trim();

      if (userVal === 'Tanoli' && passVal === 'tanoli101') {
        const adminUser = { username: 'Tanoli', isAdmin: true };
        localStorage.setItem('tanoli_auth_user', JSON.stringify(adminUser));
        renderNavAuth();
        navigateTo('admin');
        showToast('Admin Mode', 'Logged in as Admin!');
        return;
      }

      const userObj = { username: userVal, isAdmin: false };
      localStorage.setItem('tanoli_auth_user', JSON.stringify(userObj));
      renderNavAuth();
      navigateTo('dashboard');
      showToast('Welcome', `Logged in as ${userVal}`);
    }

    function handleSignupSubmit(e) {
      e.preventDefault();
      const username = document.getElementById('signupUsername').value.trim();
      const newUser = { username, isAdmin: false };
      localStorage.setItem('tanoli_auth_user', JSON.stringify(newUser));
      renderNavAuth();
      navigateTo('dashboard');
      showToast('Success', `Account created for ${username}!`);
    }

    function handleLogout() {
      localStorage.removeItem('tanoli_auth_user');
      renderNavAuth();
      navigateTo('dashboard');
      showToast('Signed Out', 'Logged out safely.');
    }

    // FIXED ADMIN PUBLISH HANDLER (Multipart form data file upload)
    function handleAdminCreateItem(e) {
      e.preventDefault();

      const btn = document.getElementById('publishBtn');
      btn.disabled = true;
      btn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Publishing...`;
      lucide.createIcons();

      const type = document.getElementById('adminType').value;
      const title = document.getElementById('adminTitle').value.trim();
      const link = document.getElementById('adminLink').value.trim();
      const desc = document.getElementById('adminDesc').value.trim();
      const imageFile = document.getElementById('adminItemImageFile').files[0];

      if (!imageFile) {
        showToast('Error', 'Please attach an icon/thumbnail file!');
        btn.disabled = false;
        btn.innerHTML = `<i data-lucide="send" class="w-4 h-4"></i> Publish Now`;
        lucide.createIcons();
        return;
      }

      const formData = new FormData();
      formData.append('action', 'save_item');
      formData.append('type', type);
      formData.append('title', title);
      formData.append('imageFile', imageFile);
      formData.append('link', link);
      formData.append('desc', desc);

      fetch('', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
          btn.disabled = false;
          btn.innerHTML = `<i data-lucide="send" class="w-4 h-4"></i> Publish Now`;
          lucide.createIcons();

          if (res.status === 'success') {
            document.getElementById('adminPublishForm').reset();
            fetchDBData(data => {
              renderAdminInventory(data);
              renderCards(data);
              renderHomePreviews(data);
            });
            showToast('Success', `"${title}" published live to database.json!`);
          } else {
            showToast('Error', res.message || 'Error saving file.');
          }
        })
        .catch(err => {
          btn.disabled = false;
          btn.innerHTML = `<i data-lucide="send" class="w-4 h-4"></i> Publish Now`;
          lucide.createIcons();
          showToast('Server Error', 'Failed to save to server.');
        });
    }

    function adminDeleteItem(type, id) {
      const formData = new FormData();
      formData.append('action', 'delete_item');
      formData.append('type', type);
      formData.append('id', id);

      fetch('', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(() => {
          fetchDBData(data => {
            renderAdminInventory(data);
            renderCards(data);
            renderHomePreviews(data);
          });
          showToast('Deleted', 'Resource removed from server.');
        });
    }

    function renderCards(data) {
      const apksContainer = document.getElementById('apksGrid');
      const apks = data.apks || [];
      if (apks.length === 0) {
        apksContainer.innerHTML = `<p class="col-span-full text-center text-slate-500 py-12">No Pro APKs uploaded yet.</p>`;
      } else {
        apksContainer.innerHTML = apks.map(app => `
          <div class="pro-card rounded-[28px] p-5 flex flex-col justify-between">
            <div>
              <div class="h-44 rounded-2xl overflow-hidden mb-4 bg-slate-100 border border-brand-200">
                <img src="${app.image}" class="w-full h-full object-cover">
              </div>
              <h3 class="font-display font-bold text-base text-slate-900 mb-1">${app.title}</h3>
              <p class="text-xs text-slate-600 line-clamp-2">${app.desc}</p>
            </div>
            <div class="pt-4 mt-4 border-t border-brand-100 flex items-center justify-between">
              <a href="${app.link}" target="_blank" class="btn-gradient px-4 py-2 rounded-xl text-white font-bold text-xs inline-flex items-center gap-1.5"><i data-lucide="download" class="w-3.5 h-3.5"></i> Download</a>
              <span class="text-[10px] text-slate-500 font-bold">${app.date || 'Direct Build'}</span>
            </div>
          </div>
        `).join('');
      }

      const coursesContainer = document.getElementById('coursesGrid');
      const courses = data.courses || [];
      if (courses.length === 0) {
        coursesContainer.innerHTML = `<p class="col-span-full text-center text-slate-500 py-12">No Masterclasses uploaded yet.</p>`;
      } else {
        coursesContainer.innerHTML = courses.map(course => `
          <div class="pro-card rounded-[28px] p-5 flex flex-col justify-between">
            <div>
              <div class="h-44 rounded-2xl overflow-hidden mb-4 bg-slate-100 border border-brand-200">
                <img src="${course.image}" class="w-full h-full object-cover">
              </div>
              <h3 class="font-display font-bold text-base text-slate-900 mb-1">${course.title}</h3>
              <p class="text-xs text-slate-600 line-clamp-2">${course.desc}</p>
            </div>
            <div class="pt-4 mt-4 border-t border-brand-100 flex items-center justify-between">
              <a href="${course.link}" target="_blank" class="btn-gradient px-4 py-2 rounded-xl text-white font-bold text-xs inline-flex items-center gap-1.5"><i data-lucide="play" class="w-3.5 h-3.5"></i> Watch Guide</a>
              <span class="text-[10px] text-slate-500 font-bold">${course.date || 'Studio Class'}</span>
            </div>
          </div>
        `).join('');
      }
      lucide.createIcons();
    }

    function renderHomePreviews(data) {
      const apkPreview = document.getElementById('homeApkPreview');
      const coursePreview = document.getElementById('homeCoursePreview');
      const apks = data.apks || [];
      const courses = data.courses || [];

      apkPreview.innerHTML = apks.length === 0 ? `<p class="text-xs text-slate-500 py-2">No APKs uploaded.</p>` : apks.slice(0, 3).map(a => `
        <div onclick="navigateTo('apks')" class="p-3 rounded-2xl bg-white/70 hover:bg-white border border-brand-200 flex items-center justify-between cursor-pointer transition-all shadow-xs">
          <div class="flex items-center gap-3"><img src="${a.image}" class="w-9 h-9 rounded-xl object-cover" /><div class="text-xs font-bold text-slate-800">${a.title}</div></div>
          <i data-lucide="download" class="w-4 h-4 text-brand-600"></i>
        </div>
      `).join('');

      coursePreview.innerHTML = courses.length === 0 ? `<p class="text-xs text-slate-500 py-2">No Masterclasses uploaded.</p>` : courses.slice(0, 3).map(c => `
        <div onclick="navigateTo('courses')" class="p-3 rounded-2xl bg-white/70 hover:bg-white border border-brand-200 flex items-center justify-between cursor-pointer transition-all shadow-xs">
          <div class="flex items-center gap-3"><img src="${c.image}" class="w-9 h-9 rounded-xl object-cover" /><div class="text-xs font-bold text-slate-800">${c.title}</div></div>
          <i data-lucide="play" class="w-4 h-4 text-blue-600"></i>
        </div>
      `).join('');
      lucide.createIcons();
    }

    function renderAdminInventory(data) {
      const user = getCurrentUser();
      if (!user || !user.isAdmin) { navigateTo('dashboard'); return; }
      const table = document.getElementById('adminInventoryTable');
      const apks = data.apks || [];
      const courses = data.courses || [];
      let rows = '';

      if (apks.length === 0 && courses.length === 0) {
        table.innerHTML = `<tr><td colspan="4" class="p-4 text-center text-slate-500">No items found in database.json.</td></tr>`;
        return;
      }

      apks.forEach(app => {
        rows += `<tr class="hover:bg-brand-50"><td class="p-3"><img src="${app.image}" class="w-8 h-8 rounded-lg object-cover" /></td><td class="p-3 font-bold text-slate-800">${app.title}</td><td class="p-3 text-brand-700 font-bold">APK</td><td class="p-3 text-right"><button onclick="adminDeleteItem('apk', '${app.id}')" class="text-red-500 hover:text-red-700 font-bold">Delete</button></td></tr>`;
      });
      courses.forEach(c => {
        rows += `<tr class="hover:bg-brand-50"><td class="p-3"><img src="${c.image}" class="w-8 h-8 rounded-lg object-cover" /></td><td class="p-3 font-bold text-slate-800">${c.title}</td><td class="p-3 text-blue-700 font-bold">Masterclass</td><td class="p-3 text-right"><button onclick="adminDeleteItem('course', '${c.id}')" class="text-red-500 hover:text-red-700 font-bold">Delete</button></td></tr>`;
      });
      table.innerHTML = rows;
    }

    function handleSearch(query) {
      const q = query.toLowerCase();
      fetchDBData(data => {
        if (!q) { renderCards(data); return; }
        navigateTo('apks');
        const filtered = (data.apks || []).filter(a => a.title.toLowerCase().includes(q));
        const apksContainer = document.getElementById('apksGrid');
        apksContainer.innerHTML = filtered.length === 0 ? `<p class="col-span-full text-center text-xs text-slate-500 py-8">No results matching "${query}"</p>` : filtered.map(app => `
          <div class="pro-card rounded-[28px] p-5">
            <h3 class="font-bold text-slate-900">${app.title}</h3>
            <p class="text-xs text-slate-600 mt-1">${app.desc}</p>
            <a href="${app.link}" target="_blank" class="btn-gradient px-4 py-2 mt-4 rounded-xl text-white font-bold text-xs inline-block">Download APK</a>
          </div>
        `).join('');
      });
    }

    function showToast(title, msg) {
      const toast = document.getElementById('toast');
      document.getElementById('toastTitle').innerText = title;
      document.getElementById('toastMsg').innerText = msg;
      toast.classList.remove('translate-y-20', 'opacity-0');
      setTimeout(() => toast.classList.add('translate-y-20', 'opacity-0'), 3500);
    }

    // Init App
    loadSiteLogo();
    renderNavAuth();
    fetchDBData(data => {
      renderHomePreviews(data);
      renderCards(data);
    });
    lucide.createIcons();
  </script>
</body>
</html>

<?php
session_start();
$role = $_SESSION['user_role'] ?? null;
$name = $_SESSION['user_name'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Smart Waste Aggregation System</title>

  <style>
    /* KEEP ALL YOUR EXISTING CSS HERE (same as your index.html) */
    :root {
      --bg: #d9d9d9;
      --panel: #f4f4f4;
      --panel-dark: #cfcfcf;
      --border: #666;
      --text: #111;
      --accent: #003366;
      --accent-2: #990000;
      --link: #0000cc;
      --shadow: #999;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Tahoma, Verdana, Arial, sans-serif;
      background: linear-gradient(#f0f0f0, #cfcfcf);
      color: var(--text);
      font-size: 14px;
    }
    a { color: var(--link); }
    a:hover { color: var(--accent-2); }

    .topbar { background: #ececec; border-bottom: 3px solid #888; padding: 8px 12px; text-align: center; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; }
    .wrapper { width: 960px; margin: 18px auto; background: #fff; border: 4px ridge #999; box-shadow: 5px 5px 0 var(--shadow); }
    .header { background: linear-gradient(#224466, #0f2a44); color: #fff; padding: 18px 20px; border-bottom: 4px solid #001122; position: relative; }
    .header h1 { margin: 0; font-size: 28px; letter-spacing: 1px; text-transform: uppercase; }
    .header p { margin: 8px 0 0; font-size: 13px; color: #d8e6f7; }
    .badge { position: absolute; right: 16px; top: 16px; background: #ffcc00; color: #000; border: 2px solid #996600; padding: 6px 10px; font-weight: bold; font-size: 12px; text-transform: uppercase; box-shadow: 2px 2px 0 #333; }

    .nav { background: #d4d4d4; border-bottom: 1px solid #888; padding: 8px; text-align: center; }
    .nav a { display: inline-block; margin: 3px 6px; padding: 6px 10px; background: #efefef; border: 1px outset #999; text-decoration: none; color: #111; font-size: 13px; }
    .nav a:hover { background: #fff8cc; border-style: inset; }

    .content { display: table; width: 100%; background: #fff; }
    .sidebar, .main { display: table-cell; vertical-align: top; }
    .sidebar { width: 240px; background: #efefef; border-right: 2px solid #aaa; padding: 14px; }
    .main { padding: 16px; background: #fff; }

    .box { border: 1px solid #888; background: #f8f8f8; margin-bottom: 14px; }
    .box h2, .box h3 { margin: 0; padding: 8px 10px; background: #c9d7e6; border-bottom: 1px solid #888; font-size: 14px; text-transform: uppercase; }
    .box .body { padding: 10px; line-height: 1.5; }

    .hero { border: 2px solid #777; background: linear-gradient(#ffffff, #e7eef6); padding: 16px; margin-bottom: 16px; }
    .hero h2 { margin: 0 0 8px; color: var(--accent); font-size: 22px; }
    .hero p { margin: 0 0 12px; max-width: 620px; }

    .cta { display: inline-block; padding: 10px 14px; background: #ffdd66; border: 2px outset #b38b00; color: #111; text-decoration: none; font-weight: bold; margin-right: 8px; }
    .cta.secondary { background: #dbe8f5; }

    .stats { width: 100%; border-collapse: collapse; margin: 14px 0; }
    .stats td { border: 1px solid #888; padding: 10px; background: #f5f5f5; text-align: center; font-weight: bold; }
    .stats small { display: block; font-weight: normal; color: #444; margin-top: 4px; }

    .feature-list { margin: 0; padding-left: 20px; }
    .feature-list li { margin-bottom: 8px; }

    .notice { background: #fff6bf; border: 1px solid #d0b000; padding: 10px; font-size: 13px; margin-top: 12px; }
    .footer { background: #d9d9d9; border-top: 2px solid #888; text-align: center; padding: 12px; font-size: 12px; color: #333; }
    .marquee { background: #000; color: #00ff66; padding: 6px 10px; font-family: "Courier New", monospace; font-size: 12px; overflow: hidden; white-space: nowrap; border-bottom: 2px solid #333; }
    .bulletin { background: #fff; border: 1px dashed #777; padding: 10px; margin-top: 12px; font-size: 13px; }

    .login-box input { width: 100%; padding: 6px; margin-bottom: 8px; border: 1px solid #777; font-family: inherit; font-size: 13px; }
    .login-box button { width: 100%; padding: 8px; background: #dfe8f7; border: 2px outset #999; font-weight: bold; cursor: pointer; }
    .login-box button:hover { border-style: inset; }

    @media (max-width: 980px) {
      .wrapper { width: 95%; }
      .content, .sidebar, .main { display: block; width: auto; }
      .sidebar { border-right: 0; border-bottom: 2px solid #aaa; }
      .badge { position: static; display: inline-block; margin-top: 10px; }
    }
  </style>
</head>

<body>
  <div class="topbar">Best viewed in a desktop browser • 1024 x 768 recommended</div>

  <div class="wrapper">
    <header class="header">
      <div class="badge">NEW 2010 PORTAL</div>
      <h1>Smart Waste Aggregation System</h1>
      <p>Citizen reporting • Collector dispatch • Admin oversight</p>
    </header>

    <nav class="nav">
      <a href="#home">Home</a>
      <a href="#about">About</a>
      <a href="#features">Features</a>
      <a href="#login">Login</a>
      <a href="#contact">Contact</a>
    </nav>

    <div class="marquee">WELCOME TO THE SMART WASTE AGGREGATION SYSTEM — KEEP YOUR COMMUNITY CLEAN, ORDERLY, AND INFORMED.</div>

    <section class="content" id="home">
      <aside class="sidebar">

        <div class="box" id="login">
          <h3>Member Login</h3>
          <div class="body login-box">
            <?php if (!$role): ?>
              <!-- Real login form -> sends to your working PHP login page -->
              <form method="POST" action="auth/login.php">
                <input type="email" name="email" placeholder="Email" required />
                <input type="password" name="password" placeholder="Password" required />
                <button type="submit">Sign In</button>
              </form>
              <p style="margin:8px 0 0;">
                <a href="auth/register.php">New user? Register</a>
              </p>
            <?php else: ?>
              <p>Welcome, <b><?php echo htmlspecialchars($name); ?></b></p>
              <p>Role: <b><?php echo htmlspecialchars($role); ?></b></p>
              <p><a href="auth/logout.php">Logout</a></p>

              <?php if ($role === 'admin'): ?>
                <p><a href="admin/index.php">Go to Admin Dashboard</a></p>
              <?php elseif ($role === 'collector'): ?>
                <p><a href="collector/index.php">Go to Collector Dashboard</a></p>
              <?php else: ?>
                <p><a href="citizen/index.php">Go to Citizen Dashboard</a></p>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="box">
          <h3>Quick Links</h3>
          <div class="body">
            <ul class="feature-list">
              <li><a href="citizen/submit.php">Submit Report</a></li>
              <li><a href="citizen/index.php">Track Status</a></li>
              <li><a href="collector/index.php">Collector Dashboard</a></li>
              <li><a href="admin/index.php">Admin Panel</a></li>
            </ul>
          </div>
        </div>

        <div class="box">
          <h3>System Notice</h3>
          <div class="body">
            Scheduled maintenance every Sunday, 1:00 AM - 3:00 AM.
          </div>
        </div>

      </aside>

      <main class="main">
        <div class="hero">
          <h2>Modern Cleanup, Classic Web Style</h2>
          <p>
            Report waste issues quickly, route tasks to collectors, and keep administrators informed with a simple
            role-based system built for everyday community use.
          </p>
          <a class="cta" href="#features">View Features</a>
          <a class="cta secondary" href="#about">Learn More</a>
        </div>

        <table class="stats" aria-label="site stats">
          <tr>
            <td>128<small>Reports Logged</small></td>
            <td>34<small>Collectors Active</small></td>
            <td>9<small>Areas Covered</small></td>
            <td>24/7<small>Monitoring</small></td>
          </tr>
        </table>

        <div class="box" id="about">
          <h2>About The System</h2>
          <div class="body">
            This portal helps citizens report waste buildup, collectors manage assigned jobs, and administrators oversee the full workflow.
            The interface follows a classic early-2010s web aesthetic with strong borders, shaded panels, and a straightforward layout.
          </div>
        </div>

        <div class="box" id="features">
          <h2>Key Features</h2>
          <div class="body">
            <ul class="feature-list">
              <li>Citizen waste reports with category, description, location, and photo upload</li>
              <li>Collector job list with status updates and collection notes</li>
              <li>Admin dashboard for monitoring reports, users, and system activity</li>
              <li>Role-based access for citizens, collectors, and administrators</li>
            </ul>
          </div>
        </div>

        <div class="bulletin">
          <strong>Announcement:</strong> The system is currently in development. Design and layout are intentionally kept classic and lightweight for broad compatibility.
        </div>

        <div class="notice" id="contact">
          <strong>Contact:</strong> support@waste-system.local | Office Hours: Mon - Fri, 9:00 AM - 5:00 PM
        </div>
      </main>
    </section>

    <footer class="footer">
      &copy; 2010 Smart Waste Aggregation System. All rights reserved.
    </footer>
  </div>
</body>
</html>
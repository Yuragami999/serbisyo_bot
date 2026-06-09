<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/lang.php';

$categories = get_service_categories();
$lang       = CURRENT_LANG;

$cat_icons = [
    'civil-registry'     => '📋',
    'business-permit'    => '🏢',
    'tricycle-franchise' => '🛺',
    'scholarships'       => '🎓',
    'barangay-clearance' => '🏘️',
    'cedula'             => '📄',
    'default'            => '📑',
];

function get_cat_icon(string $code): string {
    global $cat_icons;
    return $cat_icons[$code] ?? $cat_icons['default'];
}

$quick_reply_queries = [
    'en'  => [
        'What are the requirements for a Birth Certificate?',
        'How do I get a Business Permit in Marawi?',
        'What do I need for a Tricycle Franchise?',
        'How to apply for a City Scholarship?',
        'How to get a Barangay Clearance?',
        'How to get a Community Tax Certificate?',
    ],
    'fil' => [
        'Ano ang mga kinakailangan para sa Sertipiko ng Kapanganakan?',
        'Paano makakuha ng Permit sa Negosyo sa Marawi?',
        'Ano ang kailangan para sa Frantsisa ng Tricycle?',
        'Paano mag-apply para sa Iskolarship sa Lungsod?',
        'Paano kumuha ng Barangay Clearance?',
        'Paano kumuha ng Cedula o Community Tax Certificate?',
    ],

    //================================================== paki translate nga akoo netoo HAHAHAHAAAHAHAHA
    'mrw' => [
        'Antona so kinaangayan para sa Birth Certificate?',
        'Mapapalano ko Business Permit sa Marawi?',
        'Antona so kinaangayan para sa Tricycle Franchise?',
        'Mapapalano a mag-apply ko Iskolarship?',
        'Mapapalano a makaromog ko Barangay Clearance?',
        'Mapapalano a makaromog ko Cedula?',
    ],
];

$quick_replies         = t_array('quick_replies');
$quick_reply_qrs       = $quick_reply_queries[$lang] ?? $quick_reply_queries['fil'];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" data-lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="SerbisyoBot — AI-powered government services assistant for Marawi City, BARMM, Philippines. Find document requirements 24/7.">
  <title>SerbisyoBot — <?= htmlspecialchars(t('app_tagline')) ?> | Marawi City</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">

  <!-- ========================================================================================================================================= -->
    <link rel="icon" href="assets/img/712045113_1315379467330870_8130851167054288653_n-removebg-preview.png" >
  <!-- ========================================================================================================================================= -->


</head>
<body>

  <style>
    .btn-alter{
      display: inline-flex; align-items: center; gap: 0.5rem;
      background: var(--gold-400);
      color: var(--green-900) !important;
      font-weight: 700;
      border: none;
      border-radius: var(--radius-md);
      padding: 0.85rem 2rem;
      font-size: 1rem;
      font-family: var(--font-body);
      cursor: pointer;
      text-decoration: none;
      transition: all .25s;
      box-shadow: 0 4px 20px rgba(200,154,32,.35);
    }
    .btn-alter:hover{
      background: var(--gold-300);
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(200,154,32,.45);
    }
        .sb-hero::before {
      content: '';          /* ← add this, it was missing! */
      position: absolute; inset: 0;
      z-index: 0;
      pointer-events: none;
      background: repeating-linear-gradient(...);
    }
  </style>

<!-- =========================================================================== warning notice if ever the pass key is not commited -->
<div class="demo-banner" id="demoBanner">
  ⚡ <strong>Demo Mode:</strong> Configure your AWS credentials in <code>includes/config.php</code> to enable live Claude AI responses.
  <a href="https://docs.aws.amazon.com/bedrock/latest/userguide/getting-started.html" target="_blank" rel="noopener">Learn more →</a>
</div>
<!-- ====================================================================================================== -->



<nav class="sb-navbar navbar">
  <div class="container d-flex align-items-center justify-content-between">

    <a class="navbar-brand" href="index.php">
      <img src="assets/img/712045113_1315379467330870_8130851167054288653_n-removebg-preview.png" style="height:48px; margin-right:8px" alt="">
      <span><?= htmlspecialchars(t('app_name')) ?></span>
    </a>

    <div class="lang-switcher" role="group" aria-label="Language switcher">
      <?php foreach (SUPPORTED_LANGUAGES as $code => $label): ?>
      <a href="?lang=<?= $code ?>"
         class="lang-btn <?= CURRENT_LANG === $code ? 'active' : '' ?>"
         data-lang-btn="<?= $code ?>"
         aria-current="<?= CURRENT_LANG === $code ? 'true' : 'false' ?>"
      ><?= t('lang_' . $code) ?></a>
      <?php endforeach; ?>
    </div>

  </div>
</nav>

<!-- the section itself has a design contraint which limits the action of other options like the call to action button -->
<section class="sb-hero" aria-labelledby="heroTitle">
  <div class="geometric-ornament" aria-hidden="true">﷽</div>
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-7">

          <div class="hero-badge">
            <span class="dot"></span>
            <?= htmlspecialchars(LGU_NAME . ', ' . LGU_REGION) ?>
          </div>

          <h1 id="heroTitle">
            <?= htmlspecialchars(t('app_name')) ?><br>
            <span><?= htmlspecialchars(t('app_tagline')) ?></span>
          </h1>

          <p class="subtitle"><?= htmlspecialchars(t('hero_description')) ?></p>
        

            <div class="hero-stats">

                      <div class="hero-stat">
                        <strong>24/7</strong>
                        <span><?= $lang === 'fil' ? 'Available' : ($lang === 'mrw' ? 'Iandam' : 'Available') ?></span>
                      </div>

                      <div class="hero-stat">
                        <strong>3</strong>
                        <span><?= $lang === 'fil' ? 'Wika' : ($lang === 'mrw' ? 'Kalagon' : 'Languages') ?></span>
                      </div>

                      <div class="hero-stat">
                        <strong>6+</strong>
                        <span><?= $lang === 'fil' ? 'Kategorya ng Serbisyo' : ($lang === 'mrw' ? 'Klase a Serbisyo' : 'Service Categories') ?></span>
                      </div>

                      <div class="hero-stat">
                        <strong>AI</strong>
                        <span><?= $lang === 'fil' ? 'Pinapatakbo' : ($lang === 'mrw' ? 'Powered' : 'Powered') ?></span>
                      </div>
        </div>
      </div>
    </div>
  </div>
</section>

   <!-- ================================================================== configure (unsolved bug) -->
            <!-- <div>
              <button class="hero-cta-btn" id="heroCta" aria-expanded="false" aria-controls="chatWindow">
                <i class="bi bi-chat-dots-fill"></i>
                <?= htmlspecialchars(t('hero_cta')) ?>
              </button>
            </div> -->

          <!-- =========================================================================================== -->

          <div style="background: var(--white); border-bottom: 1px solid var(--border); padding: 1.1rem 0;">
          <div class="container d-flex align-items-center justify-content: space-between gap-3 flex-wrap">
            
            <p class="mb-0 text-muted" style="font-size: .9rem;">
              <i class="bi bi-shield-check text-green me-1"></i>
              <?= $lang === 'fil' ? 'Handa kaming tumulong 24/7' : ($lang === 'mrw' ? 'Iandam ami a tumulong 24/7' : 'Ready to assist you 24/7') ?>
            </p>
            <button class="hero-cta-btn" id="heroCta"
                    aria-expanded="false"
                    aria-controls="chatWindow"
                    style="padding: .65rem 1.5rem; font-size: .9rem;">
              <i class="bi bi-chat-dots-fill"></i>
              <?= htmlspecialchars(t('hero_cta')) ?>
            </button>
          </div>
        </div>

<!-- ================================================================================================================= -->


<section class="sb-services" aria-labelledby="servicesTitle">
  <div class="container">

    <div class="row align-items-end mb-3">
      <div class="col-md-7 section-header mb-2 mb-md-0">
        <h2 id="servicesTitle"><?= htmlspecialchars(t('services_title')) ?></h2>
        <p class="mb-0"><?= htmlspecialchars(t('services_subtitle')) ?></p>
      </div>
      <div class="col-md-5">
        <div class="search-wrap">
          <span class="search-icon"><i class="bi bi-search"></i></span>
          <input type="search"
                 id="serviceSearch"
                 class="form-control"
                 placeholder="<?= htmlspecialchars(t('search_placeholder')) ?>"
                 aria-label="<?= htmlspecialchars(t('search_placeholder')) ?>">
        </div>
      </div>
    </div>

    <div id="servicesContainer">
      <?php if (empty($categories)): ?>
        <p class="text-muted"><?= htmlspecialchars(t('no_results')) ?></p>
      <?php else: ?>
        <?php foreach ($categories as $cat): ?>
        <div class="cat-section" data-cat="<?= htmlspecialchars($cat['code']) ?>">
          <div class="category-heading">
            <div class="cat-icon" aria-hidden="true"><?= get_cat_icon($cat['code']) ?></div>
            <?= htmlspecialchars($cat['name']) ?>
          </div>
          <div class="row g-2">
            <?php if (empty($cat['services'])): ?>
              <div class="col-12">
                <p class="text-muted small ps-2"><?= $lang === 'fil' ? 'Walang serbisyo sa kategoryang ito.' : 'No services in this category yet.' ?></p>
              </div>
            <?php else: ?>
              <?php foreach ($cat['services'] as $svc): ?>
              <?php
                $ask_q_templates = [
                  'en'  => 'What are the requirements for ' . $svc['title'] . '?',
                  'fil' => 'Ano ang mga kinakailangan para sa ' . $svc['title'] . '?',
                  'mrw' => 'Antona so kinaangayan para sa ' . $svc['title'] . '?',
                ];
                $ask_query = htmlspecialchars($ask_q_templates[$lang] ?? $ask_q_templates['fil']);
                $fee_label = (float)($svc['fees'] ?? 0) == 0 ? t('free') : 'PHP ' . number_format((float)$svc['fees'], 2);
              ?>
              <div class="col-12 col-md-6">
                <article class="svc-card"
                         data-search="<?= strtolower(htmlspecialchars($svc['title'] . ' ' . $cat['name'])) ?>"
                         aria-label="<?= htmlspecialchars($svc['title']) ?>">
                  <div class="svc-card-body">
                    <div class="svc-card-title" title="<?= htmlspecialchars($svc['title']) ?>">
                      <?= htmlspecialchars($svc['title']) ?>
                    </div>
                    <div class="svc-meta">
                      <?php if ($svc['processing_time']): ?>
                      <span class="svc-badge time">
                        <i class="bi bi-clock" aria-hidden="true"></i>
                        <?= htmlspecialchars($svc['processing_time']) ?>
                      </span>
                      <?php endif; ?>
                      <span class="svc-badge fee">
                        <i class="bi bi-cash" aria-hidden="true"></i>
                        <?= htmlspecialchars($fee_label) ?>
                      </span>
                    </div>
                  </div>
                  <button class="svc-ask-btn"
                          data-query="<?= $ask_query ?>"
                          aria-label="<?= t('view_details') . ': ' . htmlspecialchars($svc['title']) ?>">
                    <i class="bi bi-chat-dots" aria-hidden="true"></i>
                    <?= htmlspecialchars(t('view_details')) ?>
                  </button>
                </article>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <div id="noResults" class="no-results" style="display:none;">
        <div class="nr-icon" aria-hidden="true">🔍</div>
        <p><?= htmlspecialchars(t('no_results')) ?></p>
      </div>
    </div>

  </div>
</section>


<!-- ================================================================================ -->
<!-- <button id="chatFab"
        aria-label="<?= htmlspecialchars(t('chat_title')) ?>"
        aria-controls="chatWindow"
        aria-expanded="false">
  <span role="img" aria-hidden="true">💬</span>
  <span class="fab-notif" aria-label="New">1</span>
</button> -->

  <button id="chatFab"
          aria-label="<?= htmlspecialchars(t('chat_title')) ?>"
          aria-controls="chatWindow"
          aria-expanded="false">
    <i id="fabIcon" class="bi bi-chat-dots-fill" aria-hidden="true"></i>
    <span class="fab-notif" aria-label="New">1</span>
  </button>

<!-- ================================================================================== -->

<div id="chatWindow" class="hidden" role="dialog" aria-modal="true" aria-label="<?= htmlspecialchars(t('chat_title')) ?>">

  <div class="chat-header">
    <div class="chat-header-left">
      <div class="chat-avatar" aria-hidden="true">
        <img src="assets/img/712045113_1315379467330870_8130851167054288653_n-removebg-preview.png" alt="SerbisyoBot Avatar" style="width:100%; object-fit:cover; border-radius:50%;">
      </div>
      <div>
        <div class="chat-name"><?= htmlspecialchars(t('chat_title')) ?></div>
        <div class="chat-status">
          <span class="dot-online" aria-hidden="true"></span>
          <?= htmlspecialchars(t('chat_subtitle')) ?>
        </div>
      </div>
    </div>

    
    <div class="chat-header-right">
      <?php foreach (SUPPORTED_LANGUAGES as $code => $label): ?>
      <button class="chat-lang-btn <?= CURRENT_LANG === $code ? 'active' : '' ?>"
              data-lang-btn="<?= $code ?>"
              aria-pressed="<?= CURRENT_LANG === $code ? 'true' : 'false' ?>"
              title="Switch to <?= htmlspecialchars($label) ?>">
        <?= t('lang_' . $code) ?>
      </button>
      <?php endforeach; ?>
      <button id="chatCloseBtn" class="chat-close-btn" aria-label="Close chat">
        <i class="bi bi-x-lg" aria-hidden="true"></i>
      </button>
    </div>
  </div>

  <div class="quick-replies" role="list" aria-label="Quick questions">
    <?php foreach ($quick_replies as $idx => $qr): ?>
    <button class="qr-pill"
            role="listitem"
            data-query="<?= htmlspecialchars($quick_reply_qrs[$idx] ?? $qr) ?>"
            aria-label="<?= htmlspecialchars($qr) ?>">
      <?= htmlspecialchars($qr) ?>
    </button>
    <?php endforeach; ?>
  </div>

  <div id="chatMessages" class="chat-messages" aria-live="polite" aria-label="Chat messages">


  </div>

  <div class="chat-input-area">
    <textarea id="chatInput"
              rows="1"
              placeholder="<?= htmlspecialchars(t('chat_placeholder')) ?>"
              aria-label="<?= htmlspecialchars(t('chat_placeholder')) ?>"
              maxlength="1000"></textarea>

    <button id="chatSendBtn" aria-label="<?= htmlspecialchars(t('chat_send')) ?>">
      <i class="bi bi-send-fill" aria-hidden="true"></i>
    </button>
  </div>

</div>

<!-- ==================================================================================================================== -->

<footer class="sb-footer">
  <img src="assets/img/712045113_1315379467330870_8130851167054288653_n-removebg-preview.png" alt="footer Logo" class="footer-logo-img" style="height: 46px;">
  <?= htmlspecialchars(t('footer_text')) ?><br>
  <!-- <small>
    &copy; <?= date('Y') ?> <?= htmlspecialchars(LGU_NAME) ?> &mdash;
    <?= $lang === 'fil' ? 'Lahat ng karapatan ay nakalaan.' : ($lang === 'mrw' ? 'All rights reserved.' : 'All rights reserved.') ?>
  </small> -->

  <small>
    &copy;<?= date('Y') ?> <?= htmlspecialchars(LGU_NAME) ?> &mdash;
    <?= $lang === 'fil' ? 'Gawa ng team LARPers MSU main.' : ($lang === 'mrw' ? 'Made by team LARPers MSU main.' : 'Made by team LARPers MSU main.') ?>
  </small>
</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>

</body>
</html>

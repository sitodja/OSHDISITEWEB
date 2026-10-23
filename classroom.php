<?php
$page_title = "Visioconférence Professionnelle & Classe Virtuelle";
$page_description = "Participez aux visioconférences en direct OSHDI : audio et vidéo HD WebRTC, partage d'écran, tableau blanc collaboratif, modération d'intercession et chat temps réel.";
$page_keywords = "visioconférence OSHDI, WebRTC PHP, Google Meet clone, Zoom PHP, classe virtuelle, direct prière";
$extra_css = "assets/css/classroom.css";
$extra_js = "assets/js/classroom.js";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Paramètres de session et de salle
$roomId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['room'] ?? 'OSHDI-ROOM-702');
if (empty($roomId)) $roomId = 'OSHDI-ROOM-702';

$userName = trim($_GET['name'] ?? ($_SESSION['user_nom'] ?? 'Apprenant OSHDI'));
if (empty($userName)) $userName = 'Apprenant OSHDI';

$userRole = $_GET['role'] ?? ($_SESSION['user_role'] ?? 'participant');
$isHost = ($userRole === 'host' || (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'formateur', 'moderateur'])));
$autoJoin = isset($_GET['autojoin']) && $_GET['autojoin'] == '1';

// URL pour test immédiat d'un deuxième participant
$secondPeerName = 'Participant_' . chr(rand(66, 75)); // Nom aléatoire (Participant_B, C, etc.)
$secondPeerUrl = "classroom.php?room=" . urlencode($roomId) . "&name=" . urlencode($secondPeerName) . "&role=participant&autojoin=1";
?>
<script>
window.CURRENT_ROOM_ID = <?= json_encode($roomId) ?>;
window.CURRENT_USER_NAME = <?= json_encode($userName) ?>;
window.CURRENT_USER_ROLE = <?= json_encode($isHost ? 'host' : 'participant') ?>;
window.AUTO_JOIN = <?= json_encode($autoJoin) ?>;
</script>

<!-- ==============================================================
     1. SALLE DE PRÉ-CONNEXION / GREEN ROOM LOBBY (Style Google Meet)
     ============================================================== -->
<div id="lobbyOverlay" class="lobby-overlay" style="<?= $autoJoin ? 'display:none;' : '' ?>">
    <div class="lobby-card">
        <!-- Côté Gauche : Aperçu Caméra & Test Microphone -->
        <div class="lobby-preview-side">
            <div class="lobby-video-wrap">
                <video id="lobbyVideoPreview" autoplay playsinline muted></video>
                <div id="lobbyVideoPlaceholder" class="video-placeholder" style="display:none; position:absolute;">
                    <div class="avatar-circle" id="lobbyAvatarCircle"><?= strtoupper(substr($userName, 0, 1)) ?></div>
                    <span style="font-size:0.85rem; color:#CBD5E1;" id="lobbyPlaceholderName"><?= htmlspecialchars($userName) ?></span>
                </div>
                
                <!-- Commandes rapides sous l'aperçu -->
                <div class="lobby-preview-controls">
                    <button type="button" id="btnLobbyToggleMic" class="lobby-ctrl-btn" title="Activer / Couper le micro">
                        <i class="fa-solid fa-microphone" id="lobbyMicIcon"></i>
                    </button>
                    <button type="button" id="btnLobbyToggleCam" class="lobby-ctrl-btn" title="Activer / Couper la caméra">
                        <i class="fa-solid fa-video" id="lobbyCamIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Vumètre LED de test audio -->
            <div class="lobby-meter-wrapper" style="width:100%;">
                <div style="display:flex; justify-content:space-between; font-size:0.75rem; color:#94A3B8;">
                    <span><i class="fa-solid fa-microphone-lines"></i> Test du Microphone :</span>
                    <strong id="lobbyMicStatusTxt" style="color:#10B981;">Prêt (Parlez)</strong>
                </div>
                <div class="lobby-meter-bar">
                    <div id="lobbyMeterFill" class="lobby-meter-fill"></div>
                </div>
            </div>
        </div>

        <!-- Côté Droit : Identité, Informations et Entrée en Réunion -->
        <div class="lobby-info-side">
            <div>
                <div class="lobby-brand">
                    <img src="assets/images/logo.png" alt="OSHDI" onerror="this.style.display='none'">
                    <span style="font-weight:800; color:#FFFFFF; font-size:1.1rem; letter-spacing:0.5px;">OSHDI <span style="color:#F59E0B;">VISIOPRO</span></span>
                </div>
                <div class="lobby-room-badge">
                    <i class="fa-solid fa-shield-halved"></i> Salle Sécurisée : <strong id="lobbyRoomBadge"><?= htmlspecialchars($roomId) ?></strong>
                </div>

                <h3 style="color:#FFFFFF; margin:0 0 8px; font-size:1.25rem;">Prêt(e) à participer ?</h3>
                <p style="color:#94A3B8; font-size:0.82rem; line-height:1.45; margin-bottom:16px;">
                    Vérifiez votre audio et votre caméra avant de rejoindre vos pairs dans cette session interactive en direct.
                </p>

                <!-- Champ de saisie du Nom d'affichage -->
                <div style="margin-bottom:14px;">
                    <label style="display:block; font-size:0.8rem; font-weight:700; color:#CBD5E1; margin-bottom:4px;">
                        Votre Nom d'affichage :
                    </label>
                    <input type="text" id="lobbyUserNameInput" value="<?= htmlspecialchars($userName) ?>" class="form-control" style="background:rgba(255,255,255,0.08); border-color:rgba(255,255,255,0.18); color:#FFFFFF; padding:8px 12px; font-size:0.88rem; border-radius:6px; width:100%;">
                </div>

                <!-- Sélection rapide du micro et de la caméra -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:14px;">
                    <div>
                        <label style="font-size:0.75rem; color:#94A3B8;">Microphone :</label>
                        <select id="lobbyMicSelect" style="width:100%; padding:6px; background:#1E293B; color:#FFFFFF; border:1px solid rgba(255,255,255,0.15); border-radius:4px; font-size:0.75rem;"></select>
                    </div>
                    <div>
                        <label style="font-size:0.75rem; color:#94A3B8;">Caméra :</label>
                        <select id="lobbyCamSelect" style="width:100%; padding:6px; background:#1E293B; color:#FFFFFF; border:1px solid rgba(255,255,255,0.15); border-radius:4px; font-size:0.75rem;"></select>
                    </div>
                </div>
            </div>

            <div>
                <button type="button" id="btnEnterMeeting" class="btn btn-gold" style="width:100%; padding:12px; font-size:0.95rem; font-weight:700; display:flex; align-items:center; justify-content:center; gap:8px; border-radius:8px;">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Rejoindre la Réunion
                </button>
                <div style="margin-top:10px; display:flex; justify-content:space-between; align-items:center;">
                    <small style="color:#64748B; font-size:0.72rem;">Cryptage WebRTC P2P direct</small>
                    <a href="<?= $secondPeerUrl ?>" target="_blank" class="btn-quick-peer-test" style="font-size:0.7rem; padding:3px 8px;" title="Ouvrir un 2ème participant dans un nouvel onglet pour tester la visioconférence WebRTC">
                        <i class="fa-solid fa-user-plus"></i> Tester avec un 2e onglet
                    </a>
                </div>
                <button type="button" id="btnLobbyOpenDashboard" class="btn btn-outline btn-sm" style="width:100%; margin-top:8px; padding:6px; font-size:0.75rem;">
                    <i class="fa-solid fa-calendar-plus" style="color:#F59E0B;"></i> Tableau de Bord des Réunions (Créer / Rejoindre)
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container" style="margin-top:8px; max-width: 1440px;">
    <!-- En-tête de la session avec outils de contrôle rapide -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:8px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <span class="live-indicator"><div class="live-dot"></div> DIRECT ACTIF</span>
                <span style="font-size:0.82rem; color:#64748B;">Salon Sécurisé : <strong id="currentRoomTag" style="color:#0A192F;">#<?= htmlspecialchars($roomId) ?></strong></span>
                <button type="button" id="btnCopyInvite" class="btn btn-outline btn-sm" style="padding:2px 8px; font-size:0.75rem;" title="Copier le lien d'invitation">
                    <i class="fa-solid fa-link"></i> Inviter
                </button>
                <a href="<?= $secondPeerUrl ?>" target="_blank" class="btn-quick-peer-test" title="Ouvrir un second participant dans un autre onglet pour tester l'appel WebRTC direct">
                    <i class="fa-solid fa-users-viewfinder"></i> +1 Participant Test (WebRTC)
                </a>
            </div>
            <h2 style="font-size:1.35rem; color:#0A192F; margin-top:2px; margin-bottom:0;">
                Session Interactive : Restauration Spirituelle & Autonomie Sociale
            </h2>
        </div>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <button type="button" id="btnOpenDashboard" class="btn btn-outline btn-sm" title="Ouvrir le Tableau de Bord des Réunions">
                <i class="fa-solid fa-chart-pie" style="color:#3B82F6;"></i> Tableau de Bord
            </button>
            <button type="button" id="btnHeaderAttendance" class="btn btn-gold btn-sm" onclick="openAttendanceModal()" title="Présence, Heures d'Entrée/Sortie, Durée & Rapports de Réunion" style="font-weight:700;">
                <i class="fa-solid fa-clipboard-user"></i> Présence & Rapports
            </button>
            <span class="network-badge" id="netQualityBadge" title="Qualité du réseau WebRTC">
                <i class="fa-solid fa-signal"></i> 1080p HD
            </span>
            <button type="button" id="btnToggleAudioOnly" class="btn btn-outline btn-sm" onclick="toggleAudioOnlyMode()" title="Mode Audio Uniquement (Économie de bande passante et données mobiles sur réseau faible)">
                <i class="fa-solid fa-headphones"></i> <span id="lblAudioOnly">Audio Seul</span>
            </button>
            <button type="button" id="btnToggleRole" class="btn btn-outline btn-sm" title="Basculer entre Mode Hôte et Mode Participant pour tester les permissions de modération">
                <i class="fa-solid fa-user-shield" style="color:var(--oshdi-gold);"></i> <span id="lblCurrentRole"><?= $isHost ? 'Mode : Hôte / Modérateur' : 'Mode : Participant' ?></span>
            </button>
            <button type="button" id="btnOpenRecordingsVault" class="btn btn-outline btn-sm" onclick="openRecordingsVault()" title="Consulter et télécharger les enregistrements sécurisés de la réunion">
                <i class="fa-solid fa-photo-film" style="color:#F59E0B;"></i> Enregistrements
            </button>
            <button type="button" id="btnHeaderBreakout" class="btn btn-outline btn-sm" onclick="openBreakoutRoomsModal()" title="Salles de Sous-Groupes (Ateliers de travail)">
                <i class="fa-solid fa-shapes" style="color:#06B6D4;"></i> Sous-Groupes
            </button>
            <button type="button" id="btnHeaderTranscript" class="btn btn-outline btn-sm" onclick="openTranscriptModal()" title="Sous-Titres en Direct & Résumé Intelligent IA">
                <i class="fa-solid fa-closed-captioning" style="color:#10B981;"></i> Sous-Titres & Résumé
            </button>
            <a href="lecteur_cours.php?id=1" class="btn btn-outline btn-sm" title="Consulter le support de formation">
                <i class="fa-solid fa-file-pdf"></i> Support PDF
            </a>
            <button type="button" id="btnHeaderRaiseHand" class="btn btn-gold btn-sm" title="Demander la parole à l'enseignant">
                <i class="fa-solid fa-hand"></i> <span id="lblHeaderRaiseHand">Lever la Main</span>
            </button>
            <button type="button" id="btnToggleLayout" class="btn btn-outline btn-sm" title="Changer la disposition (Vue Intervenant / Grille Galerie)">
                <i class="fa-solid fa-table-cells"></i> <span id="lblLayoutMode">Vue Grille</span>
            </button>
            <button type="button" id="btnHeaderFullscreen" class="btn btn-outline btn-sm" title="Mode Plein Écran">
                <i class="fa-solid fa-expand"></i>
            </button>
        </div>
    </div>

    <!-- Application Classe Virtuelle & Visioconférence -->
    <div class="classroom-app" id="classroomAppContainer">
        <!-- Scène Principale (Vidéos, Vocal, Tableau Blanc) -->
        <div class="classroom-stage" id="classroomStage">
            <div class="stage-header">
                <div class="stage-title">
                    <i class="fa-solid fa-chalkboard-user" style="color:#F59E0B;"></i>
                    <strong style="font-size:0.92rem;">En direct avec Dr. K. N'Guessan & Collège Pastoral</strong>
                </div>
                <div style="display:flex; align-items:center; gap:14px; font-size:0.8rem; color:#94A3B8;">
                    <span id="meetingDurationTimer" style="background:rgba(255,255,255,0.08); padding:2px 8px; border-radius:4px; font-family:monospace; color:#E2E8F0;">
                        <i class="fa-regular fa-clock"></i> <span id="timerVal">00:00</span>
                    </span>
                    <span id="recordingBadge" style="display:none; color:#EF4444; font-weight:700;">
                        <i class="fa-solid fa-circle" style="animation:pulseRed 1s infinite;"></i> ENR <span id="recordingTimer">00:00</span>
                    </span>
                    <span id="roomLockBadge" style="display:none; color:#F59E0B;" title="Cette salle est verrouillée par l'hôte">
                        <i class="fa-solid fa-lock"></i> Verrouillée
                    </span>
                    <span><i class="fa-solid fa-users" style="color:#3B82F6;"></i> <span id="connectedCountBadge">1</span> En ligne</span>
                </div>
            </div>

            <!-- Bannière d'état de Partage d'écran -->
            <div id="screenShareActiveBanner" style="display:none; background:#2563EB; color:#FFFFFF; padding:6px 14px; font-size:0.8rem; align-items:center; justify-content:space-between; z-index:25;">
                <span><i class="fa-solid fa-desktop"></i> Vous partagez actuellement votre écran avec les participants.</span>
                <button type="button" id="btnStopScreenShareBanner" class="btn btn-outline btn-sm" style="color:#FFFFFF; border-color:#FFFFFF; padding:2px 8px; font-size:0.72rem;">
                    Arrêter le partage
                </button>
            </div>

            <!-- Bannière d'Atelier de Sous-Groupe Actif (Vue Participant & Hôte) -->
            <div id="breakoutActiveBanner" style="display:none; background:linear-gradient(90deg, #0891B2, #0284C7); color:#FFFFFF; padding:8px 16px; font-size:0.82rem; align-items:center; justify-content:space-between; z-index:26; box-shadow:0 4px 12px rgba(0,0,0,0.3); flex-wrap:wrap; gap:8px;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="background:rgba(255,255,255,0.2); padding:3px 8px; border-radius:4px; font-weight:700;">
                        <i class="fa-solid fa-shapes"></i> <span id="lblBreakoutCurrentRoom">Atelier de Sous-Groupe</span>
                    </span>
                    <span style="font-size:0.78rem; opacity:0.95;">
                        <i class="fa-regular fa-clock"></i> Temps restant : <strong id="breakoutCountdownTimer" style="font-family:monospace; font-size:0.9rem;">15:00</strong>
                    </span>
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                    <button type="button" id="btnBreakoutAskHelp" class="btn btn-outline btn-sm" onclick="askHostBreakoutHelp()" style="color:#FFFFFF; border-color:#FFFFFF; padding:3px 10px; font-size:0.75rem;" title="Demander au formateur d'entrer dans notre salle pour nous aider">
                        <i class="fa-solid fa-hand-holding-hand"></i> Appeler l'hôte à l'aide
                    </button>
                    <button type="button" id="btnBreakoutLeaveEarly" class="btn btn-outline btn-sm" onclick="leaveBreakoutRoomPrompt()" style="color:#FFFFFF; border-color:rgba(255,255,255,0.6); padding:3px 10px; font-size:0.75rem;" title="Quitter cet atelier et retourner dans la salle principale">
                        <i class="fa-solid fa-arrow-left"></i> Revenir à la salle principale
                    </button>
                </div>
            </div>

            <!-- Bannière Compte à Rebours Avant Clôture des Sous-Groupes (Préavis 60s) -->
            <div id="breakoutClosingWarningBanner" style="display:none; background:#DC2626; color:#FFFFFF; padding:6px 14px; font-size:0.8rem; align-items:center; justify-content:space-between; z-index:27;">
                <span>
                    <i class="fa-solid fa-triangle-exclamation"></i> <strong>Avis :</strong> Les salles de sous-groupes vont fermer dans <strong id="breakoutClosingSeconds" style="font-family:monospace;">60</strong> secondes. Concluez vos échanges.
                </span>
                <button type="button" onclick="leaveBreakoutRoomPrompt()" class="btn btn-outline btn-sm" style="color:#FFFFFF; border-color:#FFFFFF; padding:2px 8px; font-size:0.72rem;">
                    Revenir maintenant
                </button>
            </div>

            <!-- Bannière de Message Global Diffusé par l'Hôte -->
            <div id="breakoutBroadcastBanner" style="display:none; background:#7C3AED; color:#FFFFFF; padding:8px 14px; font-size:0.82rem; align-items:center; justify-content:space-between; z-index:28;">
                <span id="breakoutBroadcastText"><i class="fa-solid fa-bullhorn"></i> <strong>Message de l'Hôte :</strong> ...</span>
                <button type="button" onclick="this.parentElement.style.display='none'" class="btn btn-outline btn-sm" style="color:#FFFFFF; border-color:rgba(255,255,255,0.4); padding:2px 6px; font-size:0.7rem;">
                    &times;
                </button>
            </div>

            <!-- Bannière Flottante d'Admission d'un Participant (Vue Hôte) -->
            <div id="hostAdmissionBanner" class="host-admission-banner" style="display:none;">
                <div class="banner-avatar" id="bannerAvatar">JD</div>
                <div class="banner-text">
                    <strong id="bannerUserName">Jean David</strong>
                    <small id="bannerUserLoc">Abidjan, Côte d'Ivoire &bull; Demande d'accès</small>
                </div>
                <div class="banner-actions">
                    <button type="button" id="btnBannerAdmit" class="btn-admit"><i class="fa-solid fa-check"></i> Admettre</button>
                    <button type="button" id="btnBannerReject" class="btn-reject"><i class="fa-solid fa-xmark"></i> Refuser</button>
                </div>
            </div>

            <!-- Calque Salle d'Attente & Permission d'Accès (Vue Participant) -->
            <div id="waitingRoomOverlay" class="waiting-room-overlay" style="display:none;">
                <div class="waiting-box">
                    <div class="radar-pulse-wrapper">
                        <div class="radar-circle circle-1"></div>
                        <div class="radar-circle circle-2"></div>
                        <div class="radar-circle circle-3"></div>
                        <img src="assets/images/logo.png" alt="OSHDI" class="radar-logo" onerror="this.style.display='none'">
                    </div>
                    
                    <span class="waiting-badge"><i class="fa-solid fa-shield-halved"></i> SALLE D'ATTENTE SÉCURISÉE OSHDI</span>
                    <h3 style="color:#FFFFFF; margin:12px 0 6px; font-size:1.3rem;">Demande d'autorisation d'accès en cours</h3>
                    <p style="color:#94A3B8; font-size:0.85rem; max-width:440px; margin:0 auto 16px; line-height:1.5;">
                        Veuillez patienter quelques instants. L'hôte de la visioconférence (Dr. K. N'Guessan / Modérateur) a été notifié de votre présence et va vous admettre dans la salle.
                    </p>

                    <!-- Aperçu Vidéo et Micro du participant en attendant -->
                    <div class="waiting-preview-card">
                        <video id="waitingVideoPreview" autoplay playsinline muted></video>
                        <div class="waiting-preview-tag">
                            <span id="waitingMicStatus"><i class="fa-solid fa-microphone" style="color:#10B981;"></i> Micro Prêt</span>
                        </div>
                    </div>

                    <div class="waiting-status-alert">
                        <i class="fa-solid fa-circle-notch fa-spin" style="color:#F59E0B;"></i>
                        <span id="waitingStatusText">En attente de l'approbation du modérateur...</span>
                    </div>

                    <div style="display:flex; gap:10px; justify-content:center; margin-top:16px; flex-wrap:wrap;">
                        <button type="button" id="btnSimulateHostAccept" class="btn btn-gold btn-sm" title="Simuler l'acceptation immédiate par l'hôte">
                            <i class="fa-solid fa-check-double"></i> M'admettre directement (Test)
                        </button>
                        <button type="button" id="btnRetryPermission" class="btn btn-outline btn-sm">
                            <i class="fa-solid fa-arrow-rotate-right"></i> Relancer la demande
                        </button>
                        <button type="button" id="btnLeaveWaiting" class="btn btn-outline btn-sm" style="color:#EF4444; border-color:#EF4444;">
                            <i class="fa-solid fa-door-open"></i> Quitter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Conteneur d'émojis flottants animés -->
            <div id="floatingReactionsContainer" class="floating-reactions-container"></div>

            <!-- ==============================================================
                 GRILLE VIDÉO DYNAMIQUE WEBRTC (Google Meet / Zoom)
                 ============================================================== -->
            <div class="video-grid layout-2" id="videoGrid">
                
                <!-- 1. Carte Enseignant / Modérateur Principal OSHDI -->
                <div class="video-card teacher-card" id="cardTeacher" data-peer-id="teacher_host">
                    <img src="assets/images/hero_banner.jpg" alt="Enseignant OSHDI" style="width:100%; height:100%; object-fit:cover; opacity:0.95;">
                    <div class="video-overlay-actions">
                        <button type="button" class="btn-pin-tile" onclick="togglePinCard('cardTeacher')" title="Épingler cette vidéo en grand">
                            <i class="fa-solid fa-thumbtack"></i>
                        </button>
                        <button type="button" class="video-action-btn" onclick="toggleCardFullscreen('cardTeacher')" title="Plein écran">
                            <i class="fa-solid fa-expand"></i>
                        </button>
                    </div>
                    <div class="video-name-tag">
                        <button type="button" class="video-mic-btn" id="btnTeacherMic" onclick="toggleTeacherMic()" title="Activer / Couper le micro du formateur">
                            <i class="fa-solid fa-microphone" id="teacherMicIcon" style="color:#10B981;"></i>
                        </button>
                        <span>Dr. K. N'Guessan (Formateur Principal)</span>
                    </div>
                    <canvas id="teacherWaveform" class="waveform-canvas" width="100" height="30"></canvas>
                </div>

                <!-- 2. Votre Vidéo Locale (Webcam Réelle WebRTC) -->
                <div class="video-card" id="localVideoContainer" data-peer-id="local_user">
                    <video id="myLocalVideo" autoplay playsinline muted></video>
                    <!-- Badges contextuels -->
                    <div id="localHandBadge" class="hand-raised-badge" style="display:none;">
                        <i class="fa-solid fa-hand"></i> Main Levée
                    </div>
                    <div id="localPttBadge" class="ptt-badge" style="display:none;">
                        <i class="fa-solid fa-headset"></i> PUSH-TO-TALK (ESPACE)
                    </div>
                    <!-- Actions d'incrustation et plein écran -->
                    <div class="video-overlay-actions">
                        <button type="button" class="btn-pin-tile" onclick="togglePinCard('localVideoContainer')" title="Épingler votre vidéo">
                            <i class="fa-solid fa-thumbtack"></i>
                        </button>
                        <button type="button" class="video-action-btn" id="btnPipLocal" title="Mode Incrustation (Picture-in-Picture)">
                            <i class="fa-solid fa-images"></i>
                        </button>
                        <button type="button" class="video-action-btn" onclick="toggleCardFullscreen('localVideoContainer')" title="Agrandir">
                            <i class="fa-solid fa-expand"></i>
                        </button>
                    </div>
                    <!-- Placeholder si caméra coupée -->
                    <div id="localVideoPlaceholder" class="video-placeholder" style="display:none; position:absolute;">
                        <div class="avatar-circle"><?= strtoupper(substr($userName, 0, 1)) ?></div>
                        <span style="font-size:0.85rem; color:#CBD5E1;"><?= htmlspecialchars($userName) ?></span>
                    </div>
                    <div class="video-name-tag">
                        <button type="button" class="video-mic-btn" id="btnLocalMicBadge" onclick="toggleLocalMic()" title="Activer / Couper votre micro">
                            <i class="fa-solid fa-microphone" id="localMicIndicator" style="color:#10B981;"></i>
                        </button>
                        <span>Vous (<?= htmlspecialchars($userName) ?>)</span>
                    </div>
                    <canvas id="localWaveform" class="waveform-canvas" width="100" height="30"></canvas>
                </div>

                <!-- 3. Conteneur des Pairs WebRTC distants connectés en direct -->
                <div id="remoteVideosWrapper" style="display:contents;">
                    <!-- Les cartes vidéo des pairs WebRTC distants (connectés depuis un autre navigateur ou appareil) seront insérées ici dynamiquement -->
                </div>

            </div>

            <!-- Calque Tableau Blanc Collaboratif Interactif -->
            <div id="whiteboardLayer" class="whiteboard-layer">
                <div class="whiteboard-toolbar">
                    <span style="font-weight:700; font-size:0.9rem; color:#0A192F;">
                        <i class="fa-solid fa-pen-ruler" style="color:#D97706;"></i> Tableau Blanc Collaboratif
                    </span>
                    <div style="display:flex; gap:6px; margin-left:auto; align-items:center; flex-wrap:wrap;">
                        <!-- Outils -->
                        <button type="button" class="wb-tool-btn active" data-tool="pen" title="Pinceau"><i class="fa-solid fa-pen"></i></button>
                        <button type="button" class="wb-tool-btn" data-tool="highlighter" title="Surligneur"><i class="fa-solid fa-highlighter"></i></button>
                        <button type="button" class="wb-tool-btn" data-tool="eraser" title="Gomme"><i class="fa-solid fa-eraser"></i></button>
                        
                        <!-- Épaisseur -->
                        <select id="wbStrokeWidth" style="padding:3px 6px; border-radius:4px; border:1px solid #CBD5E1; font-size:0.75rem;">
                            <option value="2">Fin (2px)</option>
                            <option value="5" selected>Moyen (5px)</option>
                            <option value="12">Épais (12px)</option>
                        </select>

                        <!-- Couleurs -->
                        <button type="button" class="wb-color-btn active" data-color="#0F172A" style="width:20px; height:20px; border-radius:50%; background:#0F172A; border:none; cursor:pointer;" title="Noir"></button>
                        <button type="button" class="wb-color-btn" data-color="#2563EB" style="width:20px; height:20px; border-radius:50%; background:#2563EB; border:none; cursor:pointer;" title="Bleu"></button>
                        <button type="button" class="wb-color-btn" data-color="#EF4444" style="width:20px; height:20px; border-radius:50%; background:#EF4444; border:none; cursor:pointer;" title="Rouge"></button>
                        <button type="button" class="wb-color-btn" data-color="#10B981" style="width:20px; height:20px; border-radius:50%; background:#10B981; border:none; cursor:pointer;" title="Vert"></button>
                        <button type="button" class="wb-color-btn" data-color="#F59E0B" style="width:20px; height:20px; border-radius:50%; background:#F59E0B; border:none; cursor:pointer;" title="Or OSHDI"></button>
                        
                        <button type="button" id="wbDownloadBtn" class="btn btn-outline btn-sm" title="Télécharger le dessin en PNG"><i class="fa-solid fa-download"></i></button>
                        <button type="button" id="wbClearBtn" class="btn btn-outline btn-sm" style="color:#EF4444;" title="Effacer tout">Effacer</button>
                        <button type="button" id="wbCloseBtn" class="btn btn-outline btn-sm" style="color:#64748B;"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                </div>
                <canvas id="wbCanvas"></canvas>
            </div>

            <!-- Barre popup de réactions rapides -->
            <div id="reactionsPopup" class="reactions-popup" style="display:none;">
                <button type="button" class="rx-btn" onclick="sendReaction('❤️')">❤️</button>
                <button type="button" class="rx-btn" onclick="sendReaction('🙏')">🙏</button>
                <button type="button" class="rx-btn" onclick="sendReaction('👏')">👏</button>
                <button type="button" class="rx-btn" onclick="sendReaction('💡')">💡</button>
                <button type="button" class="rx-btn" onclick="sendReaction('🔥')">🔥</button>
                <button type="button" class="rx-btn" onclick="sendReaction('👍')">👍</button>
                <button type="button" class="rx-btn" onclick="sendReaction('⭐')">⭐</button>
            </div>

            <!-- Bandeau Flottant de Sous-Titres en Direct (Closed Captions - CC) -->
            <div id="liveCaptionsContainer" class="live-captions-container" style="display:none;">
                <div class="caption-header">
                    <span class="caption-live-badge"><i class="fa-solid fa-closed-captioning"></i> SOUS-TITRES EN DIRECT (IA)</span>
                    <span id="captionLanguageBadge" style="font-size:0.7rem; color:#94A3B8; font-weight:700;">fr-FR</span>
                </div>
                <div class="caption-content">
                    <span id="captionSpeakerName" class="caption-speaker">Dr. K. N'Guessan :</span>
                    <span id="captionSpeakerText" class="caption-text">En écoute des interventions...</span>
                </div>
            </div>

            <!-- ==============================================================
                 BARRE DE CONTRÔLES MÉDIA ENRICHIE (Dock Style Meet / Teams)
                 ============================================================== -->
            <div class="stage-controls">
                <button type="button" id="btnToggleMic" class="ctrl-btn" title="Microphone (Activer / Couper - Raccourci Espace pour Push-to-Talk)">
                    <i class="fa-solid fa-microphone"></i>
                </button>
                <button type="button" id="btnToggleCam" class="ctrl-btn" title="Caméra (Activer / Couper)">
                    <i class="fa-solid fa-video"></i>
                </button>
                <button type="button" id="btnScreenShare" class="ctrl-btn" title="Partager mon Écran en Direct (WebRTC HD)">
                    <i class="fa-solid fa-desktop"></i>
                </button>
                <button type="button" id="btnToggleWhiteboard" class="ctrl-btn" title="Ouvrir le Tableau Blanc Collaboratif">
                    <i class="fa-solid fa-pen-fancy"></i>
                </button>
                <button type="button" id="btnRaiseHandCtrl" class="ctrl-btn" title="Lever la main">
                    <i class="fa-solid fa-hand"></i>
                </button>
                <button type="button" id="btnTriggerReaction" class="ctrl-btn" title="Envoyer une Réaction">
                    <i class="fa-solid fa-face-smile"></i>
                </button>
                <button type="button" id="btnRecordSession" class="ctrl-btn" title="Enregistrer la Réunion (Audio + Vidéo en .webm)">
                    <i class="fa-solid fa-record-vinyl"></i>
                </button>
                <button type="button" id="btnBreakoutRooms" class="ctrl-btn" onclick="openBreakoutRoomsModal()" title="Salles de Sous-Groupes (Ateliers de travail interactifs)">
                    <i class="fa-solid fa-shapes"></i>
                </button>
                <button type="button" id="btnToggleCaptions" class="ctrl-btn" onclick="toggleLiveCaptions()" title="Activer / Désactiver les sous-titres en direct (Reconnaissance vocale CC)">
                    <i class="fa-solid fa-closed-captioning"></i>
                </button>
                <button type="button" id="btnOpenTranscriptVault" class="ctrl-btn" onclick="openTranscriptModal()" title="Transcription intégrale, Recherche dans les échanges & Résumé IA">
                    <i class="fa-solid fa-file-waveform"></i>
                </button>
                <button type="button" id="btnAttendanceCtrl" class="ctrl-btn" onclick="openAttendanceModal()" title="Feuille d'Émargement, Durée & Rapports de Présence">
                    <i class="fa-solid fa-clipboard-user"></i>
                </button>
                <button type="button" id="btnSettingsModal" class="ctrl-btn" title="Paramètres Audio & Vidéo">
                    <i class="fa-solid fa-gear"></i>
                </button>
                <button type="button" id="btnLeaveRoom" class="ctrl-btn danger" title="Quitter la Visioconférence">
                    <i class="fa-solid fa-phone-slash"></i>
                </button>
            </div>
        </div>

        <!-- ==============================================================
             VOLET LATÉRAL : CHAT, PARTICIPANTS & SONDAGES DIRECTS
             ============================================================== -->
        <div class="classroom-sidebar">
            <div class="sidebar-tabs">
                <button type="button" id="tabBtnChat" class="s-tab active">
                    <i class="fa-solid fa-comments"></i> Chat
                </button>
                <button type="button" id="tabBtnParticipants" class="s-tab">
                    <i class="fa-solid fa-users"></i> Membres (<span id="tabMembersCount">1</span>)
                </button>
                <button type="button" id="tabBtnTranscript" class="s-tab">
                    <i class="fa-solid fa-file-waveform"></i> Transcription
                </button>
                <button type="button" id="tabBtnPoll" class="s-tab">
                    <i class="fa-solid fa-square-poll-vertical"></i> Sondage
                </button>
            </div>

            <!-- Onglet 1 : Chat Direct & Vocal Réel -->
            <div id="chatPane" class="chat-pane">
                <div class="chat-reactions-quick">
                    <span style="font-size:0.75rem; color:#94A3B8; margin-right:4px;">Réactions :</span>
                    <button type="button" class="quick-rx-btn" onclick="sendReaction('🙏')">🙏</button>
                    <button type="button" class="quick-rx-btn" onclick="sendReaction('❤️')">❤️</button>
                    <button type="button" class="quick-rx-btn" onclick="sendReaction('👏')">👏</button>
                    <button type="button" class="quick-rx-btn" onclick="sendReaction('🔥')">🔥</button>
                    <button type="button" class="quick-rx-btn" onclick="sendReaction('💡')">💡</button>
                </div>

                <div id="chatMessagesContainer" class="chat-messages">
                    <div class="chat-msg">
                        <div class="chat-msg-header">
                            <span>Modérateur OSHDI</span>
                            <span>14:30</span>
                        </div>
                        <div>Soyez les bienvenus dans la visioconférence OSHDI. Vous pouvez échanger par écrit, poser vos questions en direct ou envoyer une note vocale !</div>
                    </div>
                </div>

                <!-- Indicateur d'enregistrement vocal réel -->
                <div id="voiceRecordingStatus" class="voice-recording-status" style="display:none;">
                    <div class="live-dot" style="background:#EF4444;"></div>
                    <span id="voiceRecordingTimer">Enregistrement audio : 00:00</span>
                    <button type="button" id="btnCancelVoiceRec" style="background:none; border:none; color:#EF4444; margin-left:auto; cursor:pointer;" title="Annuler">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>

                <!-- Sélecteur de Destinataire (Public / Privé / Q&R) -->
                <div style="display:flex; justify-content:space-between; align-items:center; gap:6px; margin-bottom:6px; padding:0 2px;">
                    <div style="display:flex; align-items:center; gap:6px; flex:1;">
                        <span style="font-size:0.72rem; color:#94A3B8;"><i class="fa-solid fa-paper-plane"></i> À :</span>
                        <select id="chatRecipientSelect" style="flex:1; background:#1E293B; color:#FFFFFF; border:1px solid rgba(255,255,255,0.15); border-radius:4px; font-size:0.75rem; padding:3px 6px;">
                            <option value="all">🌐 Tout le monde (Public)</option>
                            <option value="host">👑 Hôte / Formateur (Direct)</option>
                        </select>
                    </div>
                    <button type="button" id="btnToggleQuestionMode" class="btn btn-outline btn-sm" style="font-size:0.7rem; padding:2px 6px; border-color:rgba(59,130,246,0.5); color:#60A5FA;" title="Poser une Question au Formateur (Mode Q&R)">
                        <i class="fa-solid fa-circle-question"></i> Mode Q&R
                    </button>
                </div>

                <!-- Barre de Saisie Chat & Vocal Réel -->
                <div class="chat-input-bar">
                    <button type="button" id="btnRecordVoice" class="voice-record-btn" title="Enregistrer une note vocale réelle">
                        <i class="fa-solid fa-microphone-lines"></i>
                    </button>
                    <input type="text" id="chatMsgInput" placeholder="Écrire un message à la classe...">
                    <button type="button" id="btnSendChat" class="voice-record-btn" style="color:#F59E0B;" title="Envoyer le message">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
            </div>

            <!-- Onglet 2 : Participants & Contrôles Hôte -->
            <div id="participantsPane" class="participants-pane">
                <button type="button" class="btn btn-gold btn-sm" onclick="openAttendanceModal()" style="width:100%; margin-bottom:10px; font-size:0.78rem; font-weight:700; display:flex; align-items:center; justify-content:center; gap:8px; padding:7px 12px; border-radius:6px;" title="Consulter la feuille d'émargement et les rapports de présence">
                    <i class="fa-solid fa-clipboard-user"></i> Feuille d'Émargement & Rapports
                </button>
                <div style="margin-bottom:10px; position:relative;">
                    <input type="text" id="searchParticipantInput" placeholder="Rechercher un participant..." style="width:100%; padding:8px 12px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); border-radius:6px; color:#FFFFFF; font-size:0.8rem;">
                </div>

                <!-- Section Salle d'Attente & Permissions pour l'Hôte -->
                <div id="hostWaitingRoomSection" class="host-waiting-room-section" style="margin-bottom:12px; background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.25); border-radius:8px; padding:10px; <?= $isHost ? '' : 'display:none;' ?>">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <span style="font-size:0.78rem; font-weight:700; color:#F59E0B; text-transform:uppercase;">
                            <i class="fa-solid fa-user-clock"></i> Salle d'Attente (<strong id="waitingRoomCount">0</strong>)
                        </span>
                        <button type="button" id="btnAdmitAll" class="btn btn-gold btn-sm" style="font-size:0.7rem; padding:2px 8px; display:none;">
                            Admettre Tous
                        </button>
                    </div>
                    <div id="waitingRoomListContainer" style="display:flex; flex-direction:column; gap:6px;">
                        <small id="emptyWaitingMsg" style="color:#64748B; font-size:0.75rem; font-style:italic;">Aucune demande en attente.</small>
                    </div>
                    <button type="button" id="btnSimulateIncoming" class="btn btn-outline btn-sm" style="width:100%; margin-top:8px; font-size:0.72rem; padding:4px 8px; border-style:dashed;">
                        <i class="fa-solid fa-user-plus"></i> Simuler une demande d'accès
                    </button>
                </div>

                <!-- Barre de Modération Complète Hôte -->
                <div class="moderator-audio-bar" style="display:flex; gap:6px; margin-bottom:12px; align-items:center; justify-content:space-between; flex-wrap:wrap; <?= $isHost ? '' : 'display:none;' ?>">
                    <button type="button" id="btnMuteAllMembers" class="btn btn-outline btn-sm" style="font-size:0.72rem; padding:4px 8px; color:#EF4444; border-color:rgba(239,68,68,0.4);" title="Couper le microphone de tous les participants">
                        <i class="fa-solid fa-microphone-slash"></i> Mute Tous
                    </button>
                    <button type="button" id="btnToggleRoomLock" class="btn btn-outline btn-sm" style="font-size:0.72rem; padding:4px 8px; color:#F59E0B; border-color:rgba(245,158,11,0.4);" title="Verrouiller ou déverrouiller la salle de réunion">
                        <i class="fa-solid fa-lock" id="iconRoomLock"></i> Verrouiller
                    </button>
                    <button type="button" id="btnToggleScreenLock" class="btn btn-outline btn-sm" style="font-size:0.72rem; padding:4px 8px; color:#3B82F6; border-color:rgba(59,130,246,0.4);" title="Autoriser ou restreindre le partage d'écran aux participants">
                        <i class="fa-solid fa-desktop" id="iconScreenLock"></i> Partage Libre
                    </button>
                    <button type="button" id="btnToggleChatLock" class="btn btn-outline btn-sm" style="font-size:0.72rem; padding:4px 8px; color:#10B981; border-color:rgba(16,185,129,0.4);" title="Autoriser ou suspendre les messages du chat">
                        <i class="fa-solid fa-comments" id="iconChatLock"></i> Chat Libre
                    </button>
                    <button type="button" id="btnSideBreakout" onclick="openBreakoutRoomsModal()" class="btn btn-outline btn-sm" style="font-size:0.72rem; padding:4px 8px; color:#06B6D4; border-color:rgba(6,182,212,0.4);" title="Répartir les participants en sous-groupes">
                        <i class="fa-solid fa-shapes"></i> Sous-Groupes
                    </button>
                </div>

                <div id="participantsListContainer">
                    <!-- Participant Formateur Référent -->
                    <div class="participant-row" id="rowTeacher">
                        <div class="participant-info">
                            <div class="p-avatar" style="background:#D97706;">DR</div>
                            <div>
                                <strong>Dr. K. N'Guessan</strong>
                                <small style="display:block; color:#F59E0B;">Formateur Habilité (Hôte)</small>
                            </div>
                        </div>
                        <button type="button" class="p-mic-btn" id="pRowTeacherMic" onclick="toggleTeacherMic()" title="Activer / Couper le micro du formateur">
                            <i class="fa-solid fa-microphone" id="pRowTeacherMicIcon" style="color:#10B981;"></i>
                        </button>
                    </div>

                    <!-- Participant Utilisateur Local -->
                    <div class="participant-row" id="rowLocalUser">
                        <div class="participant-info">
                            <div class="p-avatar"><?= strtoupper(substr($userName, 0, 2)) ?></div>
                            <div>
                                <strong>Vous (<?= htmlspecialchars($userName) ?>)</strong>
                                <small style="display:block; color:#94A3B8;" id="localUserSubtitle"><?= $isHost ? 'Hôte / Modérateur' : 'Participant' ?></small>
                            </div>
                        </div>
                        <button type="button" class="p-mic-btn" id="rowLocalMicBtn" onclick="toggleLocalMic()" title="Activer / Couper votre microphone">
                            <span id="rowLocalMicIcon"><i class="fa-solid fa-microphone" style="color:#10B981;"></i></span>
                        </button>
                    </div>

                    <!-- Liste dynamique des autres pairs connectés en direct -->
                    <div id="remoteParticipantsListWrapper"></div>
                </div>
            </div>

            <!-- Onglet 3 : Sondage en Direct (Live Poll) -->
            <div id="pollPane" class="poll-pane" style="display:none; padding:16px;">
                <div class="poll-box">
                    <span style="font-size:0.75rem; color:#D97706; font-weight:700; text-transform:uppercase;">
                        <i class="fa-solid fa-bolt"></i> Sondage d'Intercession OSHDI
                    </span>
                    <h4 style="color:#FFFFFF; font-size:1rem; margin:8px 0 14px;">
                        Quelle priorité spirituelle et humanitaire devons-nous porter ensemble lors de la prochaine célébration ?
                    </h4>
                    <div class="poll-options">
                        <button type="button" class="poll-opt-btn" onclick="voteClassroomPoll(1)">
                            <div class="poll-opt-label">
                                <span>Aide aux veuves et orphelinats (OSHDI CHARITÉ)</span>
                                <strong id="pollPct1">48%</strong>
                            </div>
                            <div class="poll-bar"><div class="poll-fill" id="pollFill1" style="width:48%;"></div></div>
                        </button>

                        <button type="button" class="poll-opt-btn" onclick="voteClassroomPoll(2)">
                            <div class="poll-opt-label">
                                <span>Prise en charge médicale des malades (REMDIS)</span>
                                <strong id="pollPct2">32%</strong>
                            </div>
                            <div class="poll-bar"><div class="poll-fill" id="pollFill2" style="width:32%;"></div></div>
                        </button>

                        <button type="button" class="poll-opt-btn" onclick="voteClassroomPoll(3)">
                            <div class="poll-opt-label">
                                <span>Bourses d'excellence scolaire (Leader Universel)</span>
                                <strong id="pollPct3">20%</strong>
                            </div>
                            <div class="poll-bar"><div class="poll-fill" id="pollFill3" style="width:20%;"></div></div>
                        </button>
                    </div>
                    <div style="margin-top:14px; font-size:0.75rem; color:#94A3B8; text-align:center;">
                        <i class="fa-solid fa-check-double" style="color:#10B981;"></i> Votre vote est comptabilisé en direct.
                    </div>
                </div>
            </div>

            <!-- Onglet 4 : Transcription en Direct & Recherche dans les Échanges -->
            <div id="transcriptPane" class="transcript-pane" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:4px;">
                    <span style="font-size:0.72rem; color:#F59E0B; font-weight:700; text-transform:uppercase;">
                        <i class="fa-solid fa-microphone-lines"></i> Paroles & IA Vocale
                    </span>
                    <div style="display:flex; align-items:center; gap:4px;">
                        <select id="selectCaptionLanguage" onchange="changeCaptionLanguage(this.value)" style="background:#1E293B; color:#FFFFFF; border:1px solid rgba(255,255,255,0.15); border-radius:4px; font-size:0.68rem; padding:2px 4px;">
                            <option value="fr-FR" selected>🇫🇷 FR</option>
                            <option value="en-US">🇬🇧 EN</option>
                            <option value="es-ES">🇪🇸 ES</option>
                        </select>
                        <button type="button" class="btn btn-outline btn-sm" onclick="toggleLiveCaptions()" id="btnSidebarCcToggle" style="font-size:0.68rem; padding:2px 6px; color:#10B981; border-color:rgba(16,185,129,0.4);" title="Activer / Couper la reconnaissance vocale et les sous-titres">
                            <i class="fa-solid fa-closed-captioning"></i> CC Actif
                        </button>
                    </div>
                </div>

                <!-- Barre de recherche instantanée dans les échanges -->
                <div class="transcript-search-bar">
                    <i class="fa-solid fa-magnifying-glass transcript-search-icon"></i>
                    <input type="text" id="searchTranscriptInput" class="transcript-search-input" placeholder="Rechercher dans les paroles prononcées..." oninput="filterTranscriptList(this.value)">
                </div>

                <!-- Flux dynamique chronologique des transcriptions -->
                <div id="sidebarTranscriptList" class="transcript-list">
                    <div style="color:#64748B; font-size:0.78rem; font-style:italic; text-align:center; padding:16px;">
                        <i class="fa-solid fa-headphones fa-bounce" style="color:#38BDF8; font-size:1.2rem; display:block; margin-bottom:6px;"></i>
                        En attente des interventions orales. Les sous-titres et la transcription s'afficheront ici en direct.
                    </div>
                </div>

                <!-- Boutons d'actions rapides -->
                <div style="display:flex; gap:6px; margin-top:auto; padding-top:8px; border-top:1px solid rgba(255,255,255,0.08);">
                    <button type="button" class="btn btn-gold btn-sm" onclick="openTranscriptModal('summary')" style="flex:1; font-size:0.72rem; padding:6px 6px; font-weight:700;">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Résumé IA
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="exportTranscriptAction('txt')" style="font-size:0.72rem; padding:6px 8px;" title="Exporter en document texte">
                        <i class="fa-solid fa-download"></i> TXT
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="exportTranscriptAction('vtt')" style="font-size:0.72rem; padding:6px 8px;" title="Exporter en sous-titres WebVTT">
                        <i class="fa-solid fa-closed-captioning"></i> VTT
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Paramètres Audio & Vidéo -->
<div id="avSettingsModal" class="av-modal" style="display:none;">
    <div class="av-modal-content">
        <div class="av-modal-header">
            <h3><i class="fa-solid fa-gear" style="color:#F59E0B;"></i> Paramètres Audio & Vidéo</h3>
            <button type="button" class="av-close-btn" onclick="closeAvSettings()">&times;</button>
        </div>
        <div class="av-modal-body">
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:0.85rem; font-weight:700; margin-bottom:6px; color:#0A192F;">
                    Microphone :
                </label>
                <select id="selectMicDevice" class="form-control" style="padding:10px;"></select>
                <div style="margin-top:8px;">
                    <small style="color:#64748B;">Niveau d'entrée du microphone (Parlez pour tester) :</small>
                    <div style="width:100%; height:10px; background:#E2E8F0; border-radius:5px; overflow:hidden; margin-top:4px;">
                        <div id="micLevelMeter" style="width:0%; height:100%; background:#10B981; transition:width 0.1s;"></div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:0.85rem; font-weight:700; margin-bottom:6px; color:#0A192F;">
                    Caméra :
                </label>
                <select id="selectCamDevice" class="form-control" style="padding:10px;"></select>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:0.85rem; font-weight:700; margin-bottom:6px; color:#0A192F;">
                    Haut-parleurs & Sortie Audio :
                </label>
                <select id="selectAudioOutputDevice" class="form-control" style="padding:10px; margin-bottom:8px;"></select>
                <button type="button" id="btnTestSound" class="btn btn-outline btn-sm" style="width:100%; padding:8px;">
                    <i class="fa-solid fa-volume-high"></i> Tester la sortie audio (Carillon OSHDI)
                </button>
            </div>

            <!-- Traitement Numérique de la Voix (DSP) -->
            <div style="margin-bottom:16px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:10px;">
                <span style="font-size:0.75rem; font-weight:700; color:#0A192F; display:block; margin-bottom:6px;">
                    <i class="fa-solid fa-wand-magic-sparkles" style="color:#F59E0B;"></i> Traitements Audio Intelligents :
                </span>
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    <span style="font-size:0.72rem; background:#ECFDF5; color:#059669; border:1px solid #A7F3D0; padding:3px 8px; border-radius:4px; display:inline-flex; align-items:center; gap:4px;">
                        <i class="fa-solid fa-check"></i> Annulation d'Écho Acoustique (AEC)
                    </span>
                    <span style="font-size:0.72rem; background:#ECFDF5; color:#059669; border:1px solid #A7F3D0; padding:3px 8px; border-radius:4px; display:inline-flex; align-items:center; gap:4px;">
                        <i class="fa-solid fa-check"></i> Suppression de Bruit (Noise Suppression)
                    </span>
                    <span style="font-size:0.72rem; background:#ECFDF5; color:#059669; border:1px solid #A7F3D0; padding:3px 8px; border-radius:4px; display:inline-flex; align-items:center; gap:4px;">
                        <i class="fa-solid fa-check"></i> Contrôle Automatique du Gain (AGC)
                    </span>
                </div>
            </div>

            <!-- Mode Audio Uniquement (Connexions faibles / Économie de data) -->
            <div style="margin-bottom:16px; background:rgba(245, 158, 11, 0.08); border:1px solid rgba(245, 158, 11, 0.25); border-radius:8px; padding:12px; display:flex; align-items:center; justify-content:space-between; gap:10px;">
                <div>
                    <strong style="display:block; font-size:0.82rem; color:#B45309;">
                        <i class="fa-solid fa-bolt"></i> Mode Audio Uniquement (Réseau faible)
                    </strong>
                    <small style="color:#64748B; font-size:0.75rem;">
                        Désactive la réception des flux vidéo distants pour économiser jusqu'à 95% de bande passante et garantir une voix cristalline.
                    </small>
                </div>
                <input type="checkbox" id="chkAudioOnlyMode" onchange="toggleAudioOnlyMode(this.checked)" style="width:20px; height:20px; accent-color:#F59E0B; cursor:pointer;">
            </div>
        </div>
        <div class="av-modal-footer">
            <button type="button" class="btn btn-gold" onclick="closeAvSettings()" style="width:100%;">
                Valider & Appliquer
            </button>
        </div>
    </div>
</div>

<!-- Modal Tableau de Bord des Réunions OSHDI -->
<div id="meetingDashboardModal" class="dashboard-modal" style="display:none;">
    <div class="dashboard-modal-content">
        <div class="dashboard-modal-header">
            <h3><i class="fa-solid fa-chalkboard-user" style="color:#F59E0B;"></i> Tableau de Bord de la Visioconférence OSHDI</h3>
            <button type="button" class="dashboard-close-btn" onclick="closeMeetingDashboard()">&times;</button>
        </div>
        <div class="dashboard-modal-body">
            <!-- Statistiques de la réunion courante -->
            <div class="dash-stats-grid">
                <div class="dash-stat-card">
                    <small><i class="fa-solid fa-hashtag" style="color:#F59E0B;"></i> Salon Actuel</small>
                    <strong style="font-size:1.05rem;" id="dashCurrentRoomCode"><?= htmlspecialchars($roomId) ?></strong>
                </div>
                <div class="dash-stat-card">
                    <small><i class="fa-solid fa-users" style="color:#3B82F6;"></i> Connectés en Direct</small>
                    <strong id="dashConnectedCount">1</strong>
                </div>
                <div class="dash-stat-card">
                    <small><i class="fa-regular fa-clock" style="color:#10B981;"></i> Durée Session</small>
                    <strong id="dashDurationVal">00:00</strong>
                </div>
                <div class="dash-stat-card">
                    <small><i class="fa-solid fa-shield-halved" style="color:#8B5CF6;"></i> Cryptage & Sécurité</small>
                    <strong style="font-size:0.95rem; color:#10B981;">WebRTC SRTP HD</strong>
                </div>
            </div>

            <!-- Navigation par Onglets Module F -->
            <div class="dash-tabs-bar" style="display:flex; gap:8px; margin-bottom:16px; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:10px; flex-wrap:wrap;">
                <button type="button" id="tabDashAgenda" class="dash-tab-btn active" onclick="switchDashboardView('agenda')" style="background:rgba(245,158,11,0.2); color:#F59E0B; border:1px solid rgba(245,158,11,0.4); padding:6px 14px; border-radius:6px; font-size:0.8rem; font-weight:600; cursor:pointer;">
                    <i class="fa-solid fa-calendar-days"></i> Calendrier & Sessions
                </button>
                <button type="button" id="tabDashPlan" class="dash-tab-btn" onclick="switchDashboardView('plan')" style="background:transparent; color:#94A3B8; border:1px solid rgba(255,255,255,0.1); padding:6px 14px; border-radius:6px; font-size:0.8rem; font-weight:600; cursor:pointer;">
                    <i class="fa-solid fa-calendar-plus"></i> Programmer & Récurrence
                </button>
                <button type="button" id="tabDashInvites" class="dash-tab-btn" onclick="switchDashboardView('invites')" style="background:transparent; color:#94A3B8; border:1px solid rgba(255,255,255,0.1); padding:6px 14px; border-radius:6px; font-size:0.8rem; font-weight:600; cursor:pointer;">
                    <i class="fa-solid fa-envelope-open-text"></i> Invitations E-mail & RSVP
                </button>
                <button type="button" id="tabDashAttendance" class="dash-tab-btn" onclick="openAttendanceModal()" style="background:rgba(16,185,129,0.12); color:#10B981; border:1px solid rgba(16,185,129,0.35); padding:6px 14px; border-radius:6px; font-size:0.8rem; font-weight:600; cursor:pointer;">
                    <i class="fa-solid fa-clipboard-user"></i> Émargement & Présences
                </button>
            </div>

            <!-- VUE 1 : CALENDRIER DES RÉUNIONS & HISTORIQUE -->
            <div id="viewDashAgenda">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                    <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                        <button type="button" class="btn btn-outline btn-sm filter-status-btn active" onclick="filterDashboardRooms('all')" style="font-size:0.72rem; padding:3px 8px;">Toutes</button>
                        <button type="button" class="btn btn-outline btn-sm filter-status-btn" onclick="filterDashboardRooms('scheduled')" style="font-size:0.72rem; padding:3px 8px; color:#F59E0B; border-color:rgba(245,158,11,0.4);"><i class="fa-solid fa-calendar"></i> Programmées</button>
                        <button type="button" class="btn btn-outline btn-sm filter-status-btn" onclick="filterDashboardRooms('active')" style="font-size:0.72rem; padding:3px 8px; color:#10B981; border-color:rgba(16,185,129,0.4);"><i class="fa-solid fa-circle-dot"></i> En direct</button>
                        <button type="button" class="btn btn-outline btn-sm filter-status-btn" onclick="filterDashboardRooms('ended')" style="font-size:0.72rem; padding:3px 8px; color:#64748B;"><i class="fa-solid fa-clock-rotate-left"></i> Historique</button>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:0.72rem; color:#94A3B8;"><i class="fa-solid fa-globe"></i> Fuseau :</span>
                        <select id="dashDisplayTimezone" onchange="loadDashboardRooms()" style="background:#0F172A; border:1px solid rgba(255,255,255,0.15); color:#F8FAFC; font-size:0.72rem; padding:3px 8px; border-radius:4px;">
                            <option value="Africa/Abidjan">Abidjan, Dakar (GMT)</option>
                            <option value="Europe/Paris">Paris, Bruxelles (CET/CEST)</option>
                            <option value="America/New_York">New York, Montréal (EST/EDT)</option>
                            <option value="UTC">UTC (Temps Universel)</option>
                        </select>
                        <button type="button" id="btnRefreshRoomsList" class="btn btn-outline btn-sm" style="font-size:0.72rem; padding:3px 8px;">
                            <i class="fa-solid fa-rotate"></i>
                        </button>
                    </div>
                </div>

                <div id="dashboardRoomsListContainer" style="display:flex; flex-direction:column; gap:10px;">
                    <small style="color:#64748B;">Chargement des sessions...</small>
                </div>
            </div>

            <!-- VUE 2 : PLANIFICATION, RÉCURRENCE & FUSEAU HORAIRE -->
            <div id="viewDashPlan" style="display:none;">
                <div style="display:grid; grid-template-columns:1.2fr 0.8fr; gap:16px;">
                    <!-- Formulaire de Planification Complète -->
                    <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:16px;">
                        <h4 style="color:#FFFFFF; font-size:0.95rem; margin:0 0 12px; display:flex; align-items:center; gap:6px;">
                            <i class="fa-solid fa-calendar-plus" style="color:#F59E0B;"></i> Programmer une Nouvelle Réunion
                        </h4>
                        <div style="margin-bottom:10px;">
                            <label style="font-size:0.75rem; color:#94A3B8;">Titre de la réunion :</label>
                            <input type="text" id="newMeetingTitle" placeholder="Ex: Cours d'Intercession & Restauration Spirituelle" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;">
                        </div>
                        <div style="margin-bottom:10px;">
                            <label style="font-size:0.75rem; color:#94A3B8;">Description / Ordre du jour (facultatif) :</label>
                            <textarea id="newMeetingDesc" rows="2" placeholder="Ex: Thème : Les fondements de la foi agissante selon Jacques 2:14" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;"></textarea>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px;">
                            <div>
                                <label style="font-size:0.75rem; color:#94A3B8;">Date & Heure de début :</label>
                                <input type="datetime-local" id="newMeetingScheduled" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;">
                            </div>
                            <div>
                                <label style="font-size:0.75rem; color:#94A3B8;">Date & Heure de fin :</label>
                                <input type="datetime-local" id="newMeetingScheduledEnd" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;">
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px;">
                            <div>
                                <label style="font-size:0.75rem; color:#94A3B8;"><i class="fa-solid fa-repeat" style="color:#F59E0B;"></i> Récurrence :</label>
                                <select id="newMeetingRecurrence" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;">
                                    <option value="none">Aucune (Réunion unique)</option>
                                    <option value="daily">Tous les jours (Quotidienne)</option>
                                    <option value="weekly">Toutes les semaines (Hebdomadaire - ex: CSD)</option>
                                    <option value="monthly">Tous les mois (Mensuelle)</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:0.75rem; color:#94A3B8;"><i class="fa-solid fa-clock" style="color:#3B82F6;"></i> Fuseau horaire :</label>
                                <select id="newMeetingTimezone" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;">
                                    <option value="Africa/Abidjan">Africa/Abidjan (GMT/UTC)</option>
                                    <option value="Europe/Paris">Europe/Paris (CET/CEST)</option>
                                    <option value="America/New_York">America/New_York (EST/EDT)</option>
                                    <option value="UTC">UTC (Standard International)</option>
                                </select>
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px;">
                            <div>
                                <label style="font-size:0.75rem; color:#94A3B8;"><i class="fa-solid fa-bell" style="color:#10B981;"></i> Rappel automatique :</label>
                                <select id="newMeetingReminder" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;">
                                    <option value="15">15 minutes avant</option>
                                    <option value="30">30 minutes avant</option>
                                    <option value="60">1 heure avant</option>
                                    <option value="1440">1 jour avant</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:0.75rem; color:#94A3B8;">Mot de passe (facultatif) :</label>
                                <input type="password" id="newMeetingPassword" placeholder="Laisser vide si public" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;">
                            </div>
                        </div>
                        <div style="margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" id="newMeetingWaitingRoom" checked style="accent-color:#F59E0B; cursor:pointer;">
                            <label for="newMeetingWaitingRoom" style="font-size:0.75rem; color:#CBD5E1; cursor:pointer; margin:0;">
                                Exiger la validation préalable en salle d'attente
                            </label>
                        </div>
                        <button type="button" id="btnCreateMeetingSubmit" class="btn btn-gold btn-sm" style="width:100%; padding:9px; font-weight:700;">
                            <i class="fa-solid fa-calendar-check"></i> Enregistrer & Programmer la Réunion
                        </button>
                    </div>

                    <!-- Rejoindre instantanément -->
                    <div style="display:flex; flex-direction:column; gap:14px;">
                        <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:16px;">
                            <h4 style="color:#FFFFFF; font-size:0.95rem; margin:0 0 8px; display:flex; align-items:center; gap:6px;">
                                <i class="fa-solid fa-right-to-bracket" style="color:#3B82F6;"></i> Rejoindre par Code
                            </h4>
                            <p style="font-size:0.75rem; color:#94A3B8; margin-bottom:10px;">
                                Saisissez le code alphanumérique de la réunion (ex: OSHDI-ROOM-702).
                            </p>
                            <input type="text" id="joinMeetingCodeInput" placeholder="Ex: OSHDI-ROOM-702" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%; margin-bottom:10px;">
                            <button type="button" id="btnJoinMeetingSubmit" class="btn btn-outline btn-sm" style="width:100%; padding:8px; border-color:#3B82F6; color:#60A5FA;">
                                <i class="fa-solid fa-arrow-right-to-bracket"></i> Rejoindre la Salle
                            </button>
                        </div>

                        <!-- Badge Détection Fuseau Local -->
                        <div style="background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.25); border-radius:10px; padding:12px; font-size:0.75rem; color:#CBD5E1;">
                            <strong style="color:#10B981; display:flex; align-items:center; gap:6px; margin-bottom:4px;">
                                <i class="fa-solid fa-earth-africa"></i> Fuseau Horaire Détecté
                            </strong>
                            <span id="lblUserDetectedTimezone">Détection en cours...</span>
                            <small style="display:block; color:#94A3B8; margin-top:4px;">
                                Les heures des réunions s'adaptent automatiquement à votre fuseau local pour fluidifier les sessions de la diaspora.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VUE 3 : INVITATIONS PAR E-MAIL & LISTE RSVP -->
            <div id="viewDashInvites" style="display:none;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <!-- Formulaire d'envoi d'invitations e-mail -->
                    <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:16px;">
                        <h4 style="color:#FFFFFF; font-size:0.95rem; margin:0 0 10px; display:flex; align-items:center; gap:6px;">
                            <i class="fa-solid fa-envelope" style="color:#F59E0B;"></i> Envoyer une Invitation Officielle par E-mail
                        </h4>
                        <div style="margin-bottom:8px;">
                            <label style="font-size:0.75rem; color:#94A3B8;">Réunion concernée :</label>
                            <select id="inviteSelectMeetingCode" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;">
                                <option value="<?= htmlspecialchars($roomId) ?>">Salle Actuelle (<?= htmlspecialchars($roomId) ?>)</option>
                            </select>
                        </div>
                        <div style="margin-bottom:8px;">
                            <label style="font-size:0.75rem; color:#94A3B8;">Adresse E-mail de l'invité :</label>
                            <input type="email" id="inviteGuestEmail" placeholder="invité@exemple.org" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;">
                        </div>
                        <div style="display:grid; grid-template-columns:1.2fr 0.8fr; gap:8px; margin-bottom:12px;">
                            <div>
                                <label style="font-size:0.75rem; color:#94A3B8;">Nom complet de l'invité :</label>
                                <input type="text" id="inviteGuestName" placeholder="Ex: Pasteur David B." class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;">
                            </div>
                            <div>
                                <label style="font-size:0.75rem; color:#94A3B8;">Rôle assigné :</label>
                                <select id="inviteGuestRole" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;">
                                    <option value="participant">Participant</option>
                                    <option value="presentateur">Présentateur</option>
                                    <option value="cohost">Coorganisateur</option>
                                </select>
                            </div>
                        </div>
                        <button type="button" id="btnSendInviteEmailSubmit" class="btn btn-gold btn-sm" style="width:100%; padding:8px;">
                            <i class="fa-solid fa-paper-plane"></i> Transmettre l'Invitation par E-mail
                        </button>
                    </div>

                    <!-- Liste des Confirmations RSVP -->
                    <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <h4 style="color:#FFFFFF; font-size:0.95rem; margin:0; display:flex; align-items:center; gap:6px;">
                                <i class="fa-solid fa-clipboard-check" style="color:#10B981;"></i> Confirmations de Présence (RSVP)
                            </h4>
                            <button type="button" class="btn btn-outline btn-sm" onclick="loadMeetingInvitationsList()" style="font-size:0.7rem; padding:2px 8px;">
                                <i class="fa-solid fa-rotate"></i>
                            </button>
                        </div>
                        <div id="rsvpListContainer" style="max-height:260px; overflow-y:auto; display:flex; flex-direction:column; gap:6px;">
                            <small style="color:#64748B;">Sélectionnez une réunion pour voir les confirmations RSVP.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="dashboard-modal-footer">
            <button type="button" class="btn btn-outline btn-sm" onclick="closeMeetingDashboard()">Fermer</button>
        </div>
    </div>
</div>



<!-- Modal Modification de Réunion (Module F) -->
<div id="editMeetingModal" class="dashboard-modal" style="display:none; z-index:99999;">
    <div class="dashboard-modal-content" style="max-width:560px;">
        <div class="dashboard-modal-header">
            <h3><i class="fa-solid fa-pen-to-square" style="color:#F59E0B;"></i> Modifier la Réunion</h3>
            <button type="button" class="dashboard-close-btn" onclick="closeEditMeetingModal()">&times;</button>
        </div>
        <div class="dashboard-modal-body">
            <input type="hidden" id="editMeetingCode">
            <div style="margin-bottom:8px;">
                <label style="font-size:0.75rem; color:#94A3B8;">Titre :</label>
                <input type="text" id="editMeetingTitle" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;">
            </div>
            <div style="margin-bottom:8px;">
                <label style="font-size:0.75rem; color:#94A3B8;">Description :</label>
                <textarea id="editMeetingDesc" rows="2" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;"></textarea>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
                <div>
                    <label style="font-size:0.75rem; color:#94A3B8;">Date & Heure :</label>
                    <input type="datetime-local" id="editMeetingScheduled" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;">
                </div>
                <div>
                    <label style="font-size:0.75rem; color:#94A3B8;">Récurrence :</label>
                    <select id="editMeetingRecurrence" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;">
                        <option value="none">Aucune</option>
                        <option value="daily">Quotidienne</option>
                        <option value="weekly">Hebdomadaire</option>
                        <option value="monthly">Mensuelle</option>
                    </select>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:12px;">
                <div>
                    <label style="font-size:0.75rem; color:#94A3B8;">Fuseau horaire :</label>
                    <select id="editMeetingTimezone" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;">
                        <option value="Africa/Abidjan">Africa/Abidjan (GMT)</option>
                        <option value="Europe/Paris">Europe/Paris (CET/CEST)</option>
                        <option value="America/New_York">America/New_York (EST)</option>
                        <option value="UTC">UTC</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.75rem; color:#94A3B8;">Rappel :</label>
                    <select id="editMeetingReminder" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:6px 10px; font-size:0.8rem; border-radius:6px; width:100%;">
                        <option value="15">15 min avant</option>
                        <option value="30">30 min avant</option>
                        <option value="60">1 heure avant</option>
                        <option value="1440">1 jour avant</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                <input type="checkbox" id="editMeetingWaitingRoom" style="accent-color:#F59E0B; cursor:pointer;">
                <label for="editMeetingWaitingRoom" style="font-size:0.75rem; color:#CBD5E1; cursor:pointer; margin:0;">
                    Exiger la salle d'attente
                </label>
            </div>
            <div style="display:flex; justify-content:space-between; gap:10px;">
                <button type="button" class="btn btn-gold btn-sm" onclick="submitEditMeetingForm()" style="flex:1;">
                    <i class="fa-solid fa-floppy-disk"></i> Enregistrer les Modifications
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="cancelMeetingPrompt()" style="color:#EF4444; border-color:rgba(239,68,68,0.4);">
                    <i class="fa-solid fa-ban"></i> Annuler la Réunion
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==============================================================
     MODALS FONCTIONNALITÉ AVANCÉE : ENREGISTREMENT & RÉTENTION
     ============================================================== -->

<!-- 1. Modal Consentement Explicite d'Enregistrement (RGPD & Droits d'Accès) -->
<div id="recordingConsentModal" class="dashboard-modal" style="display:none; z-index:999999;">
    <div class="dashboard-modal-content" style="max-width:500px; text-align:center; border:1px solid rgba(239,68,68,0.4); box-shadow:0 20px 40px rgba(0,0,0,0.8);">
        <div style="width:64px; height:64px; background:rgba(239,68,68,0.15); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:14px; border:2px solid #EF4444;">
            <i class="fa-solid fa-record-vinyl" style="font-size:1.8rem; color:#EF4444; animation:pulseRed 1.2s infinite;"></i>
        </div>
        <h3 style="color:#FFFFFF; font-size:1.2rem; margin:0 0 8px;">
            Avis d'Enregistrement de la Réunion
        </h3>
        <p style="color:#CBD5E1; font-size:0.85rem; line-height:1.5; margin-bottom:14px;">
            L'organisateur ou le modérateur a activé l'<strong>enregistrement audio & vidéo</strong> de cette visioconférence.
            Le flux enregistré sera stocké de manière sécurisée dans le <strong>coffre-fort OSHDI</strong> avec une politique de suppression programmée.
        </p>
        <div style="background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.25); border-radius:8px; padding:10px; text-align:left; font-size:0.75rem; color:#F59E0B; margin-bottom:18px;">
            <i class="fa-solid fa-shield-halved"></i> <strong>Consentement Explicite :</strong> En continuant, vous acceptez que votre voix, votre image ou vos messages partagés soient inclus dans cet enregistrement.
        </div>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <button type="button" id="btnConsentAccept" class="btn btn-gold" onclick="acceptRecordingConsent()" style="width:100%; padding:10px; font-weight:700;">
                <i class="fa-solid fa-check"></i> J'accepte & je continue la réunion
            </button>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                <button type="button" id="btnConsentMuteMe" class="btn btn-outline btn-sm" onclick="muteAndStayConsent()" style="color:#94A3B8; border-color:rgba(255,255,255,0.2);" title="Couper ma caméra et mon micro">
                    <i class="fa-solid fa-video-slash"></i> Rester en sourdine
                </button>
                <button type="button" id="btnConsentLeave" class="btn btn-outline btn-sm" onclick="leaveMeetingConsent()" style="color:#EF4444; border-color:rgba(239,68,68,0.4);" title="Quitter la réunion">
                    <i class="fa-solid fa-door-open"></i> Quitter la réunion
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 2. Modal Sauvegarde & Politique de Rétention Programmée -->
<div id="saveRecordingModal" class="dashboard-modal" style="display:none; z-index:999999;">
    <div class="dashboard-modal-content" style="max-width:540px;">
        <div class="dashboard-modal-header">
            <h3><i class="fa-solid fa-cloud-arrow-up" style="color:#10B981;"></i> Finalisation de l'Enregistrement</h3>
            <button type="button" class="dashboard-close-btn" onclick="closeSaveRecordingModal()">&times;</button>
        </div>
        <div class="dashboard-modal-body">
            <div style="background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.25); border-radius:8px; padding:12px; margin-bottom:14px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <span style="font-size:0.75rem; color:#94A3B8; display:block;">Durée capturée</span>
                    <strong style="font-size:1.1rem; color:#10B981;" id="saveRecDuration">00:00</strong>
                </div>
                <div>
                    <span style="font-size:0.75rem; color:#94A3B8; display:block;">Poids</span>
                    <strong style="font-size:1.1rem; color:#FFFFFF;" id="saveRecSize">0 Mo</strong>
                </div>
                <div>
                    <span style="font-size:0.75rem; color:#94A3B8; display:block;">Format</span>
                    <strong style="font-size:1.1rem; color:#3B82F6;">WebM HD</strong>
                </div>
            </div>

            <div style="margin-bottom:12px;">
                <label style="font-size:0.78rem; color:#94A3B8; display:block; margin-bottom:4px;">Nom de l'enregistrement :</label>
                <input type="text" id="saveRecTitleInput" class="form-control" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:8px 10px; font-size:0.85rem; border-radius:6px; width:100%;">
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-size:0.78rem; color:#94A3B8; display:block; margin-bottom:4px;">
                    <i class="fa-solid fa-hourglass-half" style="color:#F59E0B;"></i> Politique de suppression programmée (Rétention) :
                </label>
                <select id="saveRecRetentionSelect" class="form-control" style="background:#0F172A; border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:8px 10px; font-size:0.85rem; border-radius:6px; width:100%;">
                    <option value="7">7 jours (Session courte / Répétition pastorale)</option>
                    <option value="30" selected>30 jours (Standard recommandé OSHDI)</option>
                    <option value="60">60 jours (Enseignement fondamental)</option>
                    <option value="90">90 jours (Archive trimestrielle)</option>
                </select>
                <small style="color:#64748B; font-size:0.72rem; display:block; margin-top:4px;">
                    Passé ce délai, le fichier sera automatiquement et irréversiblement purgé du serveur.
                </small>
            </div>

            <div id="uploadProgressWrapper" style="display:none; margin-bottom:14px;">
                <div style="display:flex; justify-content:space-between; font-size:0.75rem; margin-bottom:4px;">
                    <span style="color:#94A3B8;">Transfert sécurisé vers le coffre-fort OSHDI...</span>
                    <span id="uploadProgressPercent" style="color:#10B981; font-weight:700;">0%</span>
                </div>
                <div style="width:100%; height:8px; background:rgba(255,255,255,0.1); border-radius:4px; overflow:hidden;">
                    <div id="uploadProgressBar" style="width:0%; height:100%; background:#10B981; transition:width 0.2s;"></div>
                </div>
            </div>

            <div style="display:flex; flex-direction:column; gap:8px;">
                <button type="button" id="btnConfirmUploadVault" class="btn btn-gold" onclick="submitUploadRecordingVault()" style="width:100%; padding:10px; font-weight:700;">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Sauvegarder dans le Coffre-Fort Sécurisé
                </button>
                <button type="button" id="btnDownloadLocalOnly" class="btn btn-outline btn-sm" onclick="downloadLocalRecordingOnly()" style="color:#60A5FA; border-color:rgba(96,165,250,0.4);">
                    <i class="fa-solid fa-download"></i> Télécharger sur mon appareil uniquement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 3. Modal Coffre-Fort des Enregistrements OSHDI & Lecteur Vidéo Intégré -->
<div id="recordingsListModal" class="dashboard-modal" style="display:none; z-index:99999;">
    <div class="dashboard-modal-content" style="max-width:800px;">
        <div class="dashboard-modal-header">
            <h3><i class="fa-solid fa-photo-film" style="color:#F59E0B;"></i> Coffre-Fort des Enregistrements & Rétention</h3>
            <button type="button" class="dashboard-close-btn" onclick="closeRecordingsVault()">&times;</button>
        </div>
        <div class="dashboard-modal-body">
            <!-- Lecteur Vidéo de Prévisualisation Intégré -->
            <div id="recordingsPlayerContainer" style="display:none; background:#000000; border-radius:8px; overflow:hidden; margin-bottom:16px; border:1px solid rgba(255,255,255,0.15);">
                <video id="vaultPreviewVideo" controls style="width:100%; max-height:360px; display:block; background:#000000;"></video>
                <div style="padding:8px 12px; background:rgba(15,23,42,0.9); display:flex; justify-content:space-between; align-items:center;">
                    <span id="vaultPreviewTitle" style="color:#FFFFFF; font-size:0.85rem; font-weight:600;">Lecture vidéo</span>
                    <button type="button" class="btn btn-outline btn-sm" onclick="closeVaultPreview()" style="font-size:0.7rem; padding:2px 8px;">
                        <i class="fa-solid fa-xmark"></i> Fermer le lecteur
                    </button>
                </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                <span style="font-size:0.8rem; color:#94A3B8;">
                    Enregistrements de la session <strong style="color:#FFFFFF;"><?= htmlspecialchars($roomId) ?></strong>
                </span>
                <button type="button" class="btn btn-outline btn-sm" onclick="loadRecordingsVaultList()" style="font-size:0.75rem; padding:3px 10px;">
                    <i class="fa-solid fa-rotate"></i> Actualiser
                </button>
            </div>

            <div id="recordingsVaultListWrapper" style="display:flex; flex-direction:column; gap:8px; max-height:320px; overflow-y:auto;">
                <small style="color:#64748B;">Chargement des enregistrements...</small>
            </div>
        </div>
        <div class="dashboard-modal-footer">
            <button type="button" class="btn btn-outline btn-sm" onclick="closeRecordingsVault()">Fermer</button>
        </div>
    </div>
</div>

<!-- 4. Modal Gestion des Salles de Sous-Groupes (Breakout Rooms) -->
<div id="breakoutRoomsModal" class="dashboard-modal" style="display:none; z-index:999999;">
    <div class="dashboard-modal-content" style="max-width:760px;">
        <div class="dashboard-modal-header">
            <h3><i class="fa-solid fa-shapes" style="color:#06B6D4;"></i> Salles de Sous-Groupes & Ateliers de Travail</h3>
            <button type="button" class="dashboard-close-btn" onclick="closeBreakoutRoomsModal()">&times;</button>
        </div>
        <div class="dashboard-modal-body">
            
            <!-- VUE 1 : CONFIGURATION & CRÉATION (AVANT LANCEMENT) -->
            <div id="breakoutSetupView">
                <p style="color:#CBD5E1; font-size:0.85rem; margin-bottom:14px; line-height:1.5;">
                    Divisez la session en petits comités interactifs pour favoriser les ateliers de réflexion, la prière fraternelle en groupe restreint ou l'étude collaborative.
                </p>

                <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:8px; padding:14px; margin-bottom:16px;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                        <div>
                            <label style="font-size:0.78rem; color:#94A3B8; display:block; margin-bottom:4px;">
                                Nombre de sous-groupes à créer :
                            </label>
                            <select id="breakoutRoomCountSelect" class="form-control" style="background:#0F172A; border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:8px 10px; font-size:0.85rem; border-radius:6px; width:100%;">
                                <option value="2" selected>2 Salles</option>
                                <option value="3">3 Salles</option>
                                <option value="4">4 Salles</option>
                                <option value="5">5 Salles</option>
                                <option value="6">6 Salles</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:0.78rem; color:#94A3B8; display:block; margin-bottom:4px;">
                                <i class="fa-regular fa-clock" style="color:#F59E0B;"></i> Durée de l'atelier :
                            </label>
                            <select id="breakoutDurationSelect" class="form-control" style="background:#0F172A; border:1px solid rgba(255,255,255,0.15); color:#FFFFFF; padding:8px 10px; font-size:0.85rem; border-radius:6px; width:100%;">
                                <option value="5">5 minutes (Échange éclair)</option>
                                <option value="10">10 minutes</option>
                                <option value="15" selected>15 minutes (Standard recommandé)</option>
                                <option value="20">20 minutes</option>
                                <option value="30">30 minutes (Étude approfondie)</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom:14px;">
                        <label style="font-size:0.78rem; color:#94A3B8; display:block; margin-bottom:6px;">Mode de répartition des participants :</label>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <label style="display:flex; align-items:center; gap:8px; font-size:0.82rem; color:#FFFFFF; cursor:pointer;">
                                <input type="radio" name="breakoutAssignMode" value="auto" checked style="accent-color:#06B6D4;">
                                <span><strong>Automatique :</strong> Répartir équitablement les participants connectés</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; font-size:0.82rem; color:#FFFFFF; cursor:pointer;">
                                <input type="radio" name="breakoutAssignMode" value="manual" style="accent-color:#06B6D4;">
                                <span><strong>Manuel :</strong> Choisir l'atelier assigné à chaque participant</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; font-size:0.82rem; color:#FFFFFF; cursor:pointer;">
                                <input type="radio" name="breakoutAssignMode" value="choice" style="accent-color:#06B6D4;">
                                <span><strong>Libre choix :</strong> Laisser chaque participant choisir son atelier thématique</span>
                            </label>
                        </div>
                    </div>

                    <div style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" id="chkBreakoutWarning60s" checked style="accent-color:#06B6D4; cursor:pointer;">
                        <label for="chkBreakoutWarning60s" style="font-size:0.78rem; color:#CBD5E1; cursor:pointer; margin:0;">
                            Avertir avec un compte à rebours de 60 secondes avant le rapatriement automatique
                        </label>
                    </div>
                </div>

                <div style="display:flex; justify-content:space-between; gap:10px;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="closeBreakoutRoomsModal()">Annuler</button>
                    <button type="button" class="btn btn-gold btn-sm" onclick="startBreakoutRoomsAction()" style="background:#0891B2; border-color:#06B6D4; color:#FFFFFF; padding:8px 18px; font-weight:700;">
                        <i class="fa-solid fa-play"></i> Lancer les Salles de Sous-Groupes
                    </button>
                </div>
            </div>

            <!-- VUE 2 : GESTION EN DIRECT (PENDANT QUE LES SOUS-GROUPES SONT ACTIFS) -->
            <div id="breakoutActiveView" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; background:rgba(6,182,212,0.1); border:1px solid rgba(6,182,212,0.25); border-radius:8px; padding:10px 14px; flex-wrap:wrap; gap:8px;">
                    <div>
                        <span style="font-size:0.75rem; color:#06B6D4; font-weight:700; text-transform:uppercase;">
                            <i class="fa-solid fa-circle-dot fa-fade"></i> Sous-groupes en cours d'activité
                        </span>
                        <div style="font-size:0.85rem; color:#FFFFFF; margin-top:2px;">
                            Temps restant : <strong id="modalBreakoutTimer" style="font-family:monospace; color:#38BDF8;">15:00</strong>
                        </div>
                    </div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <button type="button" class="btn btn-outline btn-sm" onclick="promptBroadcastBreakoutMessage()" style="font-size:0.75rem; padding:4px 10px; color:#A78BFA; border-color:rgba(167,139,250,0.4);" title="Envoyer un message qui apparaîtra dans tous les ateliers">
                            <i class="fa-solid fa-bullhorn"></i> Diffuser un message
                        </button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="warnAndEndBreakoutRooms()" style="font-size:0.75rem; padding:4px 10px; color:#EF4444; border-color:rgba(239,68,68,0.4);" title="Rappeler tous les participants">
                            <i class="fa-solid fa-door-open"></i> Fermer toutes les salles
                        </button>
                    </div>
                </div>

                <!-- Liste des salles et des participants avec réassignation et déplacement en direct -->
                <div id="breakoutRoomsLiveGrid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:12px; max-height:360px; overflow-y:auto; margin-bottom:12px;">
                    <!-- Généré dynamiquement -->
                </div>
            </div>

        </div>
        <div class="dashboard-modal-footer">
            <button type="button" class="btn btn-outline btn-sm" onclick="closeBreakoutRoomsModal()">Fermer</button>
        </div>
    </div>
</div>

<!-- 5. Modal Transcription Intégrale, Recherche & Résumé Intelligent (IA Vocale) -->
<div id="transcriptModal" class="dashboard-modal" style="display:none; z-index:999999;">
    <div class="dashboard-modal-content" style="max-width:880px;">
        <div class="dashboard-modal-header">
            <h3><i class="fa-solid fa-file-waveform" style="color:#10B981;"></i> Transcription, Recherche & Résumé de Réunion</h3>
            <button type="button" class="dashboard-close-btn" onclick="closeTranscriptModal()">&times;</button>
        </div>

        <div style="display:flex; border-bottom:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.02); padding:0 20px;">
            <button type="button" id="tabTranscriptViewFlow" onclick="switchTranscriptModalTab('flow')" style="padding:12px 18px; border-bottom:2px solid #10B981; color:#FFFFFF; background:none; border-top:none; border-left:none; border-right:none; font-weight:700; cursor:pointer;">
                <i class="fa-solid fa-list-ul"></i> Journal des Échanges & Recherche
            </button>
            <button type="button" id="tabTranscriptViewSummary" onclick="switchTranscriptModalTab('summary')" style="padding:12px 18px; color:#94A3B8; background:none; border:none; font-weight:700; cursor:pointer;">
                <i class="fa-solid fa-wand-magic-sparkles" style="color:#F59E0B;"></i> Résumé & Compte-Rendu IA
            </button>
        </div>

        <div class="dashboard-modal-body">
            <!-- VUE 1 : JOURNAL DES ÉCHANGES AVEC RECHERCHE EN DIRECT -->
            <div id="modalTranscriptFlowView">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:14px; flex-wrap:wrap;">
                    <div style="position:relative; flex:1; min-width:260px;">
                        <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#64748B;"></i>
                        <input type="text" id="modalSearchTranscriptInput" placeholder="Rechercher un mot, une idée ou un intervenant..." oninput="filterModalTranscriptList(this.value)" style="width:100%; padding:8px 12px 8px 34px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.14); border-radius:8px; color:#FFFFFF; font-size:0.85rem;">
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button type="button" class="btn btn-outline btn-sm" onclick="exportTranscriptAction('txt')">
                            <i class="fa-solid fa-file-arrow-down"></i> Télécharger (.TXT)
                        </button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="exportTranscriptAction('vtt')">
                            <i class="fa-solid fa-closed-captioning"></i> Sous-Titres (.VTT)
                        </button>
                    </div>
                </div>

                <div id="modalTranscriptListContainer" style="display:flex; flex-direction:column; gap:8px; max-height:420px; overflow-y:auto; padding-right:6px;">
                    <!-- Rempli dynamiquement -->
                </div>
            </div>

            <!-- VUE 2 : COMPTE-RENDU & SYNTHÈSE IA -->
            <div id="modalTranscriptSummaryView" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.25); border-radius:8px; padding:10px 14px; flex-wrap:wrap; gap:8px;">
                    <div>
                        <span style="font-size:0.75rem; color:#F59E0B; font-weight:700; text-transform:uppercase;">
                            <i class="fa-solid fa-bolt"></i> Synthèse Automatisée de Réunion
                        </span>
                        <div style="font-size:0.82rem; color:#CBD5E1; margin-top:2px;">
                            Généré d'après les interventions réelles et la reconnaissance vocale de la session.
                        </div>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button type="button" class="btn btn-gold btn-sm" onclick="generateAiSummaryAction()" style="font-size:0.75rem; padding:6px 12px; font-weight:700;">
                            <i class="fa-solid fa-rotate"></i> Actualiser le Résumé IA
                        </button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="copySummaryToClipboard()" style="font-size:0.75rem; padding:6px 12px;">
                            <i class="fa-solid fa-copy"></i> Copier
                        </button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="printMeetingSummary()" style="font-size:0.75rem; padding:6px 12px;">
                            <i class="fa-solid fa-print"></i> Imprimer
                        </button>
                    </div>
                </div>

                <div id="summaryReportContainer" class="summary-report-wrapper">
                    <!-- Rempli dynamiquement par generateAiSummaryAction -->
                </div>
            </div>
        </div>

        <div class="dashboard-modal-footer">
            <button type="button" class="btn btn-outline btn-sm" onclick="closeTranscriptModal()">Fermer</button>
        </div>
    </div>
</div>

<!-- ==============================================================
     6. MODAL PRÉSENCE & RAPPORTS DE RÉUNION (ÉMARGEMENT, HEURES D'ENTRÉE/SORTIE, DURÉE & EXPORTS CSV/PDF)
     ============================================================== -->
<div id="attendanceReportsModal" class="dashboard-modal" style="display:none; z-index:999999;">
    <div class="dashboard-modal-content" style="max-width:960px;">
        <div class="dashboard-modal-header">
            <div>
                <h3 style="margin:0; font-size:1.15rem; color:#FFFFFF; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-clipboard-user" style="color:#10B981;"></i> Présence & Rapports de Session
                </h3>
                <small style="color:#94A3B8; font-size:0.75rem;">
                    Feuille d'émargement officielle &bull; Session <strong id="attModalRoomCode" style="color:#F59E0B;"><?= htmlspecialchars($roomId) ?></strong>
                </small>
            </div>
            <div style="display:flex; gap:8px; align-items:center;">
                <button type="button" class="btn btn-outline btn-sm" onclick="exportAttendanceCSV()" style="font-size:0.75rem; padding:4px 10px; color:#10B981; border-color:rgba(16,185,129,0.4);" title="Télécharger le fichier CSV formaté pour Microsoft Excel">
                    <i class="fa-solid fa-file-csv"></i> Export CSV
                </button>
                <button type="button" class="btn btn-gold btn-sm" onclick="exportAttendancePDF()" style="font-size:0.75rem; padding:4px 10px;" title="Imprimer ou enregistrer le rapport officiel d'émargement en PDF">
                    <i class="fa-solid fa-file-pdf"></i> Imprimer / PDF
                </button>
                <button type="button" class="dashboard-close-btn" onclick="closeAttendanceModal()" title="Fermer">&times;</button>
            </div>
        </div>

        <!-- Cartes Statistiques KPI en haut du modal -->
        <div style="background:rgba(255,255,255,0.02); padding:16px 20px; border-bottom:1px solid rgba(255,255,255,0.08);">
            <div class="dash-stats-grid" style="margin-bottom:0;">
                <div class="dash-stat-card">
                    <small><i class="fa-solid fa-users" style="color:#3B82F6;"></i> Total Participants</small>
                    <strong id="attKpiTotal">0</strong>
                </div>
                <div class="dash-stat-card">
                    <small><i class="fa-solid fa-circle-dot fa-fade" style="color:#10B981;"></i> Présents en Direct</small>
                    <strong id="attKpiOnline" style="color:#10B981;">0</strong>
                </div>
                <div class="dash-stat-card">
                    <small><i class="fa-regular fa-clock" style="color:#F59E0B;"></i> Durée Moyenne</small>
                    <strong id="attKpiAvgDuration">0 min</strong>
                </div>
                <div class="dash-stat-card">
                    <small><i class="fa-solid fa-chart-line" style="color:#8B5CF6;"></i> Taux d'Assiduité Moyen</small>
                    <strong id="attKpiAvgRate" style="color:#8B5CF6;">100%</strong>
                </div>
            </div>
        </div>

        <!-- Onglets internes du Modal -->
        <div style="display:flex; border-bottom:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.01); padding:0 20px;">
            <button type="button" id="tabAttViewRoster" onclick="switchAttendanceModalTab('roster')" class="active" style="padding:10px 16px; border-bottom:2px solid #10B981; color:#FFFFFF; background:none; border-top:none; border-left:none; border-right:none; font-weight:700; font-size:0.82rem; cursor:pointer;">
                <i class="fa-solid fa-list-check"></i> Feuille d'Émargement Détaillée
            </button>
            <button type="button" id="tabAttViewSummary" onclick="switchAttendanceModalTab('summary')" style="padding:10px 16px; color:#94A3B8; background:none; border:none; font-weight:700; font-size:0.82rem; cursor:pointer;">
                <i class="fa-solid fa-chart-pie"></i> Synthèse & Rapport de Réunion
            </button>
        </div>

        <div class="dashboard-modal-body" style="padding:16px 20px;">
            <!-- VUE 1 : FEUILLE D'ÉMARGEMENT DÉTAILLÉE -->
            <div id="viewAttRoster">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:12px; flex-wrap:wrap;">
                    <!-- Barre de Recherche -->
                    <div style="position:relative; flex:1; min-width:220px;">
                        <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#64748B; font-size:0.8rem;"></i>
                        <input type="text" id="attSearchInput" placeholder="Rechercher par nom, rôle..." oninput="filterAttendanceTable(this.value)" style="width:100%; padding:6px 10px 6px 30px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.15); border-radius:6px; color:#FFFFFF; font-size:0.8rem;">
                    </div>
                    <!-- Filtres par statut -->
                    <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                        <button type="button" class="btn btn-outline btn-sm filter-att-btn active" onclick="filterAttendanceStatus('all')" style="font-size:0.7rem; padding:3px 8px;">Tous</button>
                        <button type="button" class="btn btn-outline btn-sm filter-att-btn" onclick="filterAttendanceStatus('online')" style="font-size:0.7rem; padding:3px 8px; color:#10B981; border-color:rgba(16,185,129,0.4);">En direct</button>
                        <button type="button" class="btn btn-outline btn-sm filter-att-btn" onclick="filterAttendanceStatus('completed')" style="font-size:0.7rem; padding:3px 8px; color:#3B82F6; border-color:rgba(59,130,246,0.4);">Complets (>=70%)</button>
                        <button type="button" class="btn btn-outline btn-sm filter-att-btn" onclick="filterAttendanceStatus('partial')" style="font-size:0.7rem; padding:3px 8px; color:#F59E0B; border-color:rgba(245,158,11,0.4);">Partiels</button>
                        <button type="button" class="btn btn-outline btn-sm" onclick="loadAttendanceReport()" style="font-size:0.7rem; padding:3px 8px;" title="Actualiser les données">
                            <i class="fa-solid fa-rotate"></i>
                        </button>
                    </div>
                </div>

                <!-- Tableau Réactif d'Émargement -->
                <div style="overflow-x:auto; border-radius:8px; border:1px solid rgba(255,255,255,0.08); background:rgba(15,23,42,0.4); max-height:360px;">
                    <table id="attendanceTable" style="width:100%; border-collapse:collapse; font-size:0.8rem; text-align:left;">
                        <thead>
                            <tr style="background:#0A192F; color:#CBD5E1; border-bottom:1px solid rgba(255,255,255,0.12); position:sticky; top:0; z-index:10;">
                                <th style="padding:10px 12px; font-weight:700;">Participant</th>
                                <th style="padding:10px 12px; font-weight:700;">Rôle</th>
                                <th style="padding:10px 12px; font-weight:700;"><i class="fa-solid fa-arrow-right-to-bracket" style="color:#10B981;"></i> Entrée</th>
                                <th style="padding:10px 12px; font-weight:700;"><i class="fa-solid fa-arrow-right-from-bracket" style="color:#EF4444;"></i> Sortie</th>
                                <th style="padding:10px 12px; font-weight:700;"><i class="fa-regular fa-clock" style="color:#F59E0B;"></i> Durée</th>
                                <th style="padding:10px 12px; font-weight:700;"><i class="fa-solid fa-percent" style="color:#8B5CF6;"></i> Assiduité</th>
                                <th style="padding:10px 12px; font-weight:700;">Statut</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceTableBody">
                            <tr>
                                <td colspan="7" style="text-align:center; padding:20px; color:#64748B;">
                                    <i class="fa-solid fa-circle-notch fa-spin"></i> Chargement des données d'émargement...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VUE 2 : SYNTHÈSE & RAPPORT DE RÉUNION -->
            <div id="viewAttSummary" style="display:none;">
                <div style="display:grid; grid-template-columns:1.2fr 0.8fr; gap:16px;">
                    <!-- Détails Officiels de la Session -->
                    <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:16px;">
                        <h4 style="color:#FFFFFF; font-size:0.95rem; margin:0 0 12px; display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-award" style="color:#F59E0B;"></i> Fiche de Session OSHDI VisioPro
                        </h4>
                        <div style="display:flex; flex-direction:column; gap:8px; font-size:0.8rem;">
                            <div style="display:flex; justify-content:space-between; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:6px;">
                                <span style="color:#94A3B8;">Code de Réunion :</span>
                                <strong id="attRepCode" style="color:#FFFFFF;">-</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:6px;">
                                <span style="color:#94A3B8;">Intitulé du Cours / Culte :</span>
                                <strong id="attRepTitle" style="color:#FFFFFF; text-align:right; max-width:60%;">-</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:6px;">
                                <span style="color:#94A3B8;">Formateur Référent / Hôte :</span>
                                <strong id="attRepHost" style="color:#F59E0B;">-</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:6px;">
                                <span style="color:#94A3B8;">Date & Horodatage d'Émission :</span>
                                <strong id="attRepDate" style="color:#CBD5E1;">-</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; border-bottom:1px solid rgba(255,255,255,0.06); padding-bottom:6px;">
                                <span style="color:#94A3B8;">Pic d'Affluence Simultanée :</span>
                                <strong id="attRepPeak" style="color:#10B981;">-</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; padding-top:4px;">
                                <span style="color:#94A3B8;">Certification de Présence :</span>
                                <strong style="color:#10B981;"><i class="fa-solid fa-check-double"></i> Conforme aux normes d'assiduité OSHDI</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Cartes d'Actions et Téléchargements -->
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <div style="background:linear-gradient(135deg, rgba(16,185,129,0.1), rgba(6,182,212,0.1)); border:1px solid rgba(16,185,129,0.3); border-radius:10px; padding:14px;">
                            <strong style="color:#10B981; font-size:0.85rem; display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                                <i class="fa-solid fa-file-excel"></i> Exportation Microsoft Excel (.CSV)
                            </strong>
                            <p style="font-size:0.75rem; color:#CBD5E1; margin-bottom:10px; line-height:1.4;">
                                Téléchargez la feuille de présence complète encodée en UTF-8 avec séparateur point-virgule pour une compatibilité parfaite avec Excel.
                            </p>
                            <button type="button" class="btn btn-outline btn-sm" onclick="exportAttendanceCSV()" style="width:100%; border-color:#10B981; color:#10B981; font-weight:700;">
                                <i class="fa-solid fa-download"></i> Télécharger le Fichier .CSV
                            </button>
                        </div>

                        <div style="background:linear-gradient(135deg, rgba(245,158,11,0.1), rgba(217,119,6,0.1)); border:1px solid rgba(245,158,11,0.3); border-radius:10px; padding:14px;">
                            <strong style="color:#F59E0B; font-size:0.85rem; display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                                <i class="fa-solid fa-file-pdf"></i> Rapport Imprimable & PDF Officiel
                            </strong>
                            <p style="font-size:0.75rem; color:#CBD5E1; margin-bottom:10px; line-height:1.4;">
                                Générez la feuille d'émargement officielle avec en-tête OSHDI, signatures et mentions légales prête à être imprimée ou sauvegardée en PDF.
                            </p>
                            <button type="button" class="btn btn-gold btn-sm" onclick="exportAttendancePDF()" style="width:100%; font-weight:700;">
                                <i class="fa-solid fa-print"></i> Ouvrir la Version PDF A4
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-modal-footer" style="display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:0.72rem; color:#64748B;">
                <i class="fa-solid fa-shield-halved"></i> Horodatages synchronisés par le serveur OSHDI VisioPro.
            </span>
            <div style="display:flex; gap:8px;">
                <button type="button" class="btn btn-outline btn-sm" onclick="exportAttendanceCSV()" style="color:#10B981; border-color:rgba(16,185,129,0.3);">
                    <i class="fa-solid fa-file-csv"></i> CSV
                </button>
                <button type="button" class="btn btn-gold btn-sm" onclick="exportAttendancePDF()">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeAttendanceModal()">Fermer</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


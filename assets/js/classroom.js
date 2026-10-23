/* ==============================================================
   OSHDI VISIOPRO - CLASSE VIRTUELLE & VISIOCONFÉRENCE EN TEMPS RÉEL
   Fonctionnalités professionnelles comparables à Google Meet / Zoom / Teams :
   - Moteur WebRTC P2P direct multi-pairs avec serveurs STUN publics
   - Salle de pré-connexion (Green Room Lobby) avec test audio et vidéo
   - Détection d'Activité Vocale (VAD) avec halo lumineux "is-speaking"
   - Partage d'écran HD WebRTC avec remplacement dynamique de flux
   - Mode Épinglé (Spotlight / Pin) & Grille adaptative responsive
   - Tableau blanc interactif collaboratif synchronisé en direct
   - Chat texte instantané & Notes vocales réelles (MediaRecorder)
   - Réactions flottantes animées (❤️, 🙏, 👏, 💡, 🔥, 👍, ⭐) synchronisées
   - Modération Hôte (Couper tous les micros, verrouillage salle, expulsion)
   - Enregistrement de session vidéo/audio (.webm) côté navigateur
   ============================================================== */

// Variables globales d'état
let localStream = null;
let isAudioMuted = false;
let isVideoStopped = false;
let isScreenSharing = false;
let isHandRaised = false;
let isRoomLocked = false;
let pinnedCardId = null;

// Audio Context & Vumètres
let audioContext = null;
let analyser = null;
let audioVisualizerFrame = null;
let currentMicDeviceId = '';
let currentCamDeviceId = '';

// Enregistrement vocal (Notes Vocales)
let voiceMediaRecorder = null;
let voiceAudioChunks = [];
let voiceRecordInterval = null;
let voiceRecordSeconds = 0;
let isVoiceRecording = false;

// Enregistrement de la Session globale
let sessionMediaRecorder = null;
let sessionRecordedChunks = [];
let sessionRecordInterval = null;
let sessionRecordSeconds = 0;
let isRecordingSession = false;
let hasGivenRecordingConsent = false;
let lastRecordedSessionBlob = null;
let lastRecordedDuration = 0;
let recordingAudioMixContext = null;
const activeRemoteStreams = {};

// Salles de Sous-Groupes (Breakout Rooms)
let currentBreakoutRoom = 0; // 0 = Salle Principale, 1..N = Sous-groupes
let currentBreakoutTitle = '';
let breakoutCountdownSeconds = 0;
let breakoutCountdownInterval = null;
let breakoutClosingInterval = null;
let breakoutRoomsConfig = [];
let breakoutAssignments = {};
let isBreakoutActive = false;

// Sous-titres & Transcription en direct (Web Speech API & IA Vocale)
let isLiveCaptionsActive = false;
let captionRecognition = null;
let captionLanguage = 'fr-FR';
let cachedTranscripts = [];
let captionHideTimeout = null;
let transcriptModalTab = 'flow';

// Minuteur de la réunion
let meetingElapsedSeconds = 0;
let meetingTimerInterval = null;

// WebRTC Peer Mesh & Signalisation
const rtcConfig = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        { urls: 'stun:stun2.l.google.com:19302' },
        { urls: 'stun:stun3.l.google.com:19302' },
        { urls: 'stun:stun4.l.google.com:19302' }
    ]
};

const peerConnections = {}; // { [peerId]: RTCPeerConnection }
const peerInfo = {};        // { [peerId]: { name, role, isMuted } }
const peerAnalysers = {};   // { [peerId]: AnalyserNode }

// Identifiants uniques et rôles
const roomId = window.CURRENT_ROOM_ID || 'OSHDI-ROOM-702';
let currentUserName = window.CURRENT_USER_NAME || 'Participant OSHDI';
let userRole = window.CURRENT_USER_ROLE || 'participant';
const myPeerId = 'peer_' + Math.random().toString(36).substring(2, 8) + '_' + Date.now().toString(36).slice(-4);
const userIdentifier = localStorage.getItem('oshdi_user_uid') || ('uid_' + myPeerId);
localStorage.setItem('oshdi_user_uid', userIdentifier);

let lastSignalId = 0;
let lastMessageId = 0;
let signalPollingInterval = null;
let heartbeatInterval = null;
let chatPollingInterval = null;
let pollPollingInterval = null;

// Microphones simulés / Formateur principal
const classroomMics = {
    teacher: {
        id: 'teacher',
        name: "Dr. K. N'Guessan (Formateur)",
        cardId: 'cardTeacher',
        canvasId: 'teacherWaveform',
        iconCardId: 'teacherMicIcon',
        iconRowId: 'pRowTeacherMicIcon',
        isMuted: false,
        isSpeaking: true,
        speechInterval: null,
        speakingPhrases: [
            "Bienvenue chers frères et sœurs dans cette session OSHDI. Soyez grandement édifiés !",
            "La formation et la foi sont les piliers de notre mission spirituelle et humanitaire.",
            "Chers apprenants, vos micros sont à votre disposition pour vos questions et interventions en direct.",
            "Nous poursuivons avec les actions concrètes d'intercession et d'entraide communautaire."
        ],
        phraseIndex: 0,
        color: '#10B981'
    }
};

// -------------------------------------------------------------
// INITIALISATION AU CHARGEMENT DU DOM
// -------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    initLobbyGreenRoom();
    initClassroomControls();
    initTeacherWaveform();
    initWhiteboard();
    initChatSystem();
    initSidebarTabs();
    initPollSystem();
    initHeaderActions();
    initPushToTalk();
    initMeetingDashboard();

    // Démarrage automatique si paramètre autojoin=1 ou si pas de lobby
    if (window.AUTO_JOIN || !document.getElementById('lobbyOverlay')) {
        const lobby = document.getElementById('lobbyOverlay');
        if (lobby) lobby.style.display = 'none';
        enterConferenceRoom();
    }
});

// -------------------------------------------------------------
// 1. SALLE DE PRÉ-CONNEXION (GREEN ROOM LOBBY Style Google Meet)
// -------------------------------------------------------------
function initLobbyGreenRoom() {
    const lobby = document.getElementById('lobbyOverlay');
    if (!lobby || window.AUTO_JOIN) return;

    const btnLobbyMic = document.getElementById('btnLobbyToggleMic');
    const btnLobbyCam = document.getElementById('btnLobbyToggleCam');
    const btnEnter = document.getElementById('btnEnterMeeting');
    const nameInput = document.getElementById('lobbyUserNameInput');
    const videoPreview = document.getElementById('lobbyVideoPreview');
    const micSelect = document.getElementById('lobbyMicSelect');
    const camSelect = document.getElementById('lobbyCamSelect');

    // Démarrer la prévisualisation dans le lobby
    startLobbyPreview();

    // Toggle Mic dans le lobby
    if (btnLobbyMic) {
        btnLobbyMic.addEventListener('click', () => {
            isAudioMuted = !isAudioMuted;
            btnLobbyMic.classList.toggle('off', isAudioMuted);
            btnLobbyMic.innerHTML = isAudioMuted ? '<i class="fa-solid fa-microphone-slash"></i>' : '<i class="fa-solid fa-microphone"></i>';
            if (localStream) {
                const at = localStream.getAudioTracks()[0];
                if (at) at.enabled = !isAudioMuted;
            }
            const stTxt = document.getElementById('lobbyMicStatusTxt');
            if (stTxt) {
                stTxt.textContent = isAudioMuted ? 'Coupé (Muet)' : 'Prêt (Parlez)';
                stTxt.style.color = isAudioMuted ? '#EF4444' : '#10B981';
            }
        });
    }

    // Toggle Caméra dans le lobby
    if (btnLobbyCam) {
        btnLobbyCam.addEventListener('click', () => {
            isVideoStopped = !isVideoStopped;
            btnLobbyCam.classList.toggle('off', isVideoStopped);
            btnLobbyCam.innerHTML = isVideoStopped ? '<i class="fa-solid fa-video-slash"></i>' : '<i class="fa-solid fa-video"></i>';
            if (localStream) {
                const vt = localStream.getVideoTracks()[0];
                if (vt) vt.enabled = !isVideoStopped;
            }
            const ph = document.getElementById('lobbyVideoPlaceholder');
            if (ph && videoPreview) {
                ph.style.display = isVideoStopped ? 'flex' : 'none';
                videoPreview.style.opacity = isVideoStopped ? '0' : '1';
            }
        });
    }

    // Changement de périphériques
    if (micSelect) {
        micSelect.addEventListener('change', () => {
            currentMicDeviceId = micSelect.value;
            startLobbyPreview(currentMicDeviceId, currentCamDeviceId);
        });
    }
    if (camSelect) {
        camSelect.addEventListener('change', () => {
            currentCamDeviceId = camSelect.value;
            startLobbyPreview(currentMicDeviceId, currentCamDeviceId);
        });
    }

    // Mise à jour du nom d'affichage
    if (nameInput) {
        nameInput.addEventListener('input', () => {
            const val = nameInput.value.trim();
            if (val) {
                currentUserName = val;
                const phName = document.getElementById('lobbyPlaceholderName');
                const avCircle = document.getElementById('lobbyAvatarCircle');
                if (phName) phName.textContent = val;
                if (avCircle) avCircle.textContent = val.substring(0, 1).toUpperCase();
            }
        });
    }

    // Bouton "Rejoindre la Réunion"
    if (btnEnter) {
        btnEnter.addEventListener('click', () => {
            if (nameInput) {
                const customName = nameInput.value.trim();
                if (customName) currentUserName = customName;
            }
            lobby.style.opacity = '0';
            lobby.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                lobby.style.display = 'none';
                enterConferenceRoom();
            }, 300);
        });
    }
}

async function startLobbyPreview(micId = null, camId = null) {
    try {
        const constraints = {
            audio: micId ? { deviceId: { exact: micId } } : true,
            video: camId ? { deviceId: { exact: camId }, width: { ideal: 1280 } } : { width: { ideal: 1280 } }
        };

        if (localStream) {
            localStream.getTracks().forEach(t => t.stop());
        }

        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        localStream = stream;

        const videoPreview = document.getElementById('lobbyVideoPreview');
        if (videoPreview) {
            videoPreview.srcObject = stream;
            videoPreview.play().catch(() => {});
        }

        // Brancher le vumètre LED du lobby
        setupLobbyAudioMeter(stream);
        populateDeviceDropdowns();
    } catch (e) {
        console.warn('Lobby preview error:', e);
        const ph = document.getElementById('lobbyVideoPlaceholder');
        if (ph) ph.style.display = 'flex';
    }
}

function setupLobbyAudioMeter(stream) {
    try {
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (audioContext.state === 'suspended') {
            audioContext.resume();
        }

        const lAnalyser = audioContext.createAnalyser();
        lAnalyser.fftSize = 64;
        const source = audioContext.createMediaStreamSource(stream);
        source.connect(lAnalyser);

        const meterFill = document.getElementById('lobbyMeterFill');
        const dataArr = new Uint8Array(lAnalyser.frequencyBinCount);

        function updateLobbyMeter() {
            if (!document.getElementById('lobbyOverlay') || document.getElementById('lobbyOverlay').style.display === 'none') return;
            requestAnimationFrame(updateLobbyMeter);
            lAnalyser.getByteFrequencyData(dataArr);

            let sum = 0;
            for (let i = 0; i < dataArr.length; i++) sum += dataArr[i];
            const avg = sum / dataArr.length;
            const pct = Math.min(100, Math.round((avg / 120) * 100));

            if (meterFill) {
                meterFill.style.width = isAudioMuted ? '0%' : (pct + '%');
            }
        }
        updateLobbyMeter();
    } catch (e) {}
}

// -------------------------------------------------------------
// 2. ENTRÉE EN SALLE & DÉMARRAGE DU MOTEUR WEBRTC
// -------------------------------------------------------------
async function enterConferenceRoom() {
    // 1. Assurer la capture média locale pour la salle
    if (!localStream) {
        await startLocalMedia();
    } else {
        const myVideo = document.getElementById('myLocalVideo');
        if (myVideo) {
            myVideo.srcObject = localStream;
            myVideo.play().catch(() => {});
        }
        setupAudioAnalysis(localStream);
    }

    // 2. Synchroniser les boutons du dock avec l'état initial
    const btnMic = document.getElementById('btnToggleMic');
    if (btnMic) btnMic.classList.toggle('active', isAudioMuted);
    const btnCam = document.getElementById('btnToggleCam');
    if (btnCam) btnCam.classList.toggle('active', isVideoStopped);

    // 3. Rejoindre la salle de signalisation PHP
    try {
        const payload = {
            room_id: roomId,
            peer_id: myPeerId,
            user_name: currentUserName,
            user_role: userRole,
            is_audio_muted: isAudioMuted ? 1 : 0,
            is_video_muted: isVideoStopped ? 1 : 0
        };

        const res = await fetch('api/classroom_signal.php?action=join', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            showToast(`Connecté à la visioconférence (${roomId})`, 'success');
            playToneChime(784, 0.3);

            // Se connecter avec tous les pairs déjà présents
            if (Array.isArray(data.existing_peers)) {
                data.existing_peers.forEach(peer => {
                    initiatePeerConnection(peer.peer_id, peer.user_name, peer.user_role, true);
                });
            }

            // Enregistrer la présence locale dans le registre officiel d'émargement
            fetch('api/classroom_attendance.php?action=record_entry', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    meeting_code: roomId,
                    peer_id: myPeerId,
                    user_name: currentUserName,
                    user_role: userRole
                })
            }).catch(() => {});
        }
    } catch (err) {
        console.warn('Erreur signalisation join:', err);
    }

    // 4. Lancer les boucles de signalisation et de battement de cœur
    if (!signalPollingInterval) {
        signalPollingInterval = setInterval(pollSignals, 1400);
    }
    if (!heartbeatInterval) {
        heartbeatInterval = setInterval(sendHeartbeat, 5000);
    }
    if (!chatPollingInterval) {
        chatPollingInterval = setInterval(fetchNewMessages, 2500);
    }
    if (!pollPollingInterval) {
        pollPollingInterval = setInterval(refreshPollStats, 7000);
    }

    // 5. Minuteur de réunion et supervision qualité réseau WebRTC
    startMeetingTimer();
    updateGridDimensions();
    startNetworkQualityMonitor();
}

// -------------------------------------------------------------
// 3. MOTEUR WEBRTC MULTI-PAIRS (P2P MESH)
// -------------------------------------------------------------
function initiatePeerConnection(remotePeerId, remotePeerName, remotePeerRole, isInitiator) {
    if (peerConnections[remotePeerId]) {
        return peerConnections[remotePeerId];
    }

    try {
        const pc = new RTCPeerConnection(rtcConfig);
        peerConnections[remotePeerId] = pc;
        peerInfo[remotePeerId] = {
            name: remotePeerName || 'Participant',
            role: remotePeerRole || 'participant',
            isMuted: false
        };

        // Ajouter les pistes locales (Audio + Vidéo)
        if (localStream) {
            localStream.getTracks().forEach(track => {
                pc.addTrack(track, localStream);
            });
        }

        // ICE Candidate généré -> Transmettre au pair distant
        pc.onicecandidate = (event) => {
            if (event.candidate) {
                sendSignal(remotePeerId, 'candidate', event.candidate);
            }
        };

        // Réception du flux distant (Audio / Vidéo)
        pc.ontrack = (event) => {
            const remoteStream = event.streams[0];
            mountRemotePeerCard(remotePeerId, remotePeerName, remotePeerRole, remoteStream);
        };

        // Gestion de l'état de la connexion et reconnexion automatique
        pc.onconnectionstatechange = () => {
            if (pc.connectionState === 'disconnected') {
                showToast(`Connexion instable avec ${remotePeerName}. Tentative de reconnexion automatique...`, 'info');
                setTimeout(() => {
                    if (pc.connectionState === 'disconnected' || pc.connectionState === 'failed') {
                        if (pc.restartIce) {
                            try { pc.restartIce(); } catch (e) {}
                        }
                    }
                }, 2500);
            } else if (pc.connectionState === 'failed' || pc.connectionState === 'closed') {
                removeRemotePeerCard(remotePeerId);
            }
        };

        // Si nous sommes l'initiateur de la connexion, créer l'offre SDP
        if (isInitiator) {
            pc.onnegotiationneeded = async () => {
                try {
                    const offer = await pc.createOffer();
                    await pc.setLocalDescription(offer);
                    sendSignal(remotePeerId, 'offer', offer);
                } catch (e) {
                    console.warn('Erreur createOffer:', e);
                }
            };
        }

        return pc;
    } catch (err) {
        console.error('Erreur initialisation RTCPeerConnection:', err);
        return null;
    }
}

// Envoi d'un signal vers le backend PHP
function sendSignal(toPeer, signalType, payload) {
    fetch('api/classroom_signal.php?action=signal_send', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            room_id: roomId,
            from_peer: myPeerId,
            to_peer: toPeer,
            signal_type: signalType,
            payload: payload
        })
    }).catch(e => console.warn('Signal send error:', e));
}

// Récupération périodique des signaux destinés à ce pair
async function pollSignals() {
    try {
        const res = await fetch(`api/classroom_signal.php?action=signal_poll&room_id=${roomId}&peer_id=${myPeerId}&last_signal_id=${lastSignalId}`);
        const data = await res.json();
        if (data.success && Array.isArray(data.signals)) {
            lastSignalId = data.last_signal_id;
            for (const sig of data.signals) {
                await handleIncomingSignal(sig);
            }
        }
    } catch (e) {}
}

// Traitement des signaux reçus
async function handleIncomingSignal(sig) {
    const from = sig.from_peer;
    const type = sig.signal_type;
    const payload = sig.payload;

    switch (type) {
        case 'peer_joined': {
            // Un nouveau participant est entré
            const pName = payload.user_name || 'Nouveau Participant';
            const pRole = payload.user_role || 'participant';
            showToast(`${pName} a rejoint la visioconférence`, 'info');
            playToneChime(659.25, 0.25);
            // Politesse WebRTC : le pair existant crée la connexion avec offre
            initiatePeerConnection(from, pName, pRole, true);
            break;
        }

        case 'offer': {
            // Offre SDP reçue
            let pc = peerConnections[from];
            if (!pc) {
                pc = initiatePeerConnection(from, payload.name || 'Participant', 'participant', false);
            }
            try {
                await pc.setRemoteDescription(new RTCSessionDescription(payload.sdp ? payload : payload));
                const answer = await pc.createAnswer();
                await pc.setLocalDescription(answer);
                sendSignal(from, 'answer', answer);
            } catch (err) {
                console.warn('Erreur traitement offer:', err);
            }
            break;
        }

        case 'answer': {
            // Réponse SDP reçue
            const pc = peerConnections[from];
            if (pc) {
                try {
                    await pc.setRemoteDescription(new RTCSessionDescription(payload.sdp ? payload : payload));
                } catch (err) {
                    console.warn('Erreur traitement answer:', err);
                }
            }
            break;
        }

        case 'candidate': {
            // ICE Candidate reçu
            const pc = peerConnections[from];
            if (pc && payload) {
                try {
                    await pc.addIceCandidate(new RTCIceCandidate(payload));
                } catch (e) {}
            }
            break;
        }

        case 'peer_left': {
            // Un participant est parti
            const leftName = peerInfo[from]?.name || 'Un participant';
            removeRemotePeerCard(from);
            showToast(`${leftName} a quitté la visioconférence`, 'info');
            break;
        }

        case 'peer_media_state': {
            // Mise à jour de l'état micro/caméra/main d'un pair distant
            updateRemotePeerMediaUI(from, payload);
            break;
        }

        case 'host_mute_all': {
            // L'hôte a coupé tous les micros
            if (userRole !== 'host' && userRole !== 'cohost' && !isAudioMuted) {
                toggleLocalMic(true);
                showToast('Votre microphone a été coupé par l\'hôte.', 'danger');
            }
            break;
        }

        case 'host_mute_peer': {
            // Coupure individuelle du micro par le modérateur
            if (!isAudioMuted) {
                toggleLocalMic(true);
                showToast('Votre microphone a été coupé par le modérateur.', 'danger');
            }
            break;
        }

        case 'host_kick': {
            alert('Vous avez été retiré de la session par le modérateur.');
            window.location.href = 'formation.php';
            break;
        }

        case 'room_ended': {
            alert("L'organisateur (Dr. K. N'Guessan) a clôturé la visioconférence pour tous les participants.");
            window.location.href = 'formation.php';
            break;
        }

        case 'room_lock_changed': {
            isRoomLocked = !!payload.is_locked;
            const badge = document.getElementById('roomLockBadge');
            if (badge) badge.style.display = isRoomLocked ? 'inline-flex' : 'none';
            showToast(isRoomLocked ? 'La salle a été verrouillée par l\'hôte' : 'La salle est maintenant déverrouillée', 'info');
            break;
        }

        case 'screen_perm_changed': {
            const allowScreen = !!payload.allow_screen;
            window.allowScreenShareForParticipants = allowScreen;
            const btnScreen = document.getElementById('btnToggleScreenShare');
            if (userRole === 'participant') {
                if (!allowScreen) {
                    if (btnScreen) {
                        btnScreen.style.opacity = '0.4';
                        btnScreen.title = "Partage d'écran réservé aux présentateurs et organisateurs.";
                    }
                    if (isScreenSharing) {
                        stopScreenShare();
                        showToast("Le partage d'écran a été restreint par l'organisateur.", "warning");
                    }
                } else {
                    if (btnScreen) {
                        btnScreen.style.opacity = '1';
                        btnScreen.title = "Partager votre écran";
                    }
                }
            }
            showToast(allowScreen ? "Partage d'écran ouvert à tous les participants" : "Partage d'écran restreint aux présentateurs désignés", "info");
            break;
        }

        case 'peer_role_changed': {
            if (payload && payload.target_peer === myPeerId) {
                userRole = payload.role || 'participant';
                const roleLabels = { host: 'Organisateur', cohost: 'Coorganisateur', presentateur: 'Présentateur', participant: 'Participant' };
                showToast(`Votre statut a été mis à jour : ${roleLabels[userRole] || userRole}`, 'success');
                const sub = document.getElementById('localUserSubtitle');
                if (sub) sub.textContent = roleLabels[userRole] || userRole;
                
                // Si promu présentateur ou co-hôte, débloquer le partage d'écran
                const btnScreen = document.getElementById('btnToggleScreenShare');
                if (btnScreen) {
                    btnScreen.style.opacity = '1';
                    btnScreen.title = "Partager votre écran";
                }
            }
            break;
        }

        case 'chat_perm_changed': {
            const allowChat = !!payload.allow_chat;
            const chatIn = document.getElementById('chatInput');
            const chatBtn = document.getElementById('btnSendMessage');
            if (userRole === 'participant') {
                if (chatIn) {
                    chatIn.disabled = !allowChat;
                    chatIn.placeholder = allowChat ? "Écrivez votre message..." : "🔒 Chat public suspendu par l'organisateur";
                }
                if (chatBtn) chatBtn.disabled = !allowChat;
            }
            showToast(allowChat ? "Le chat public est réactivé" : "Le chat public a été suspendu par l'hôte", "info");
            break;
        }

        case 'file_perm_changed': {
            const allowFiles = !!payload.allow_files;
            const btnAttach = document.getElementById('btnAttachFile');
            if (userRole === 'participant' && btnAttach) {
                btnAttach.disabled = !allowFiles;
                btnAttach.style.opacity = allowFiles ? '1' : '0.4';
            }
            showToast(allowFiles ? "Envoi de pièces jointes autorisé" : "Envoi de fichiers restreint par l'hôte", "info");
            break;
        }

        case 'waiting_guest_request': {
            if (userRole === 'host' || userRole === 'cohost') {
                handleIncomingWaitingGuest(payload);
            }
            break;
        }

        case 'guest_admitted': {
            const overlay = document.getElementById('waitingRoomOverlay');
            if (overlay) overlay.style.display = 'none';
            showToast("Vous avez été admis dans la visioconférence par l'organisateur !", "success");
            break;
        }

        case 'guest_rejected': {
            alert("Votre demande d'admission a été refusée par l'organisateur.");
            window.location.href = 'formation.php';
            break;
        }

        case 'reaction': {
            // Réaction emoji synchronisée
            if (payload && payload.emoji) {
                animateFloatingEmoji(payload.emoji);
            }
            break;
        }

        case 'whiteboard_draw': {
            // Dessin synchronisé sur le tableau blanc
            renderRemoteWhiteboardStroke(payload);
            break;
        }

        case 'recording_status_changed': {
            // Signal global de démarrage ou d'arrêt de l'enregistrement
            const isRec = !!(payload && payload.is_recording);
            const startedBy = (payload && payload.started_by) ? payload.started_by : "L'hôte";
            const badge = document.getElementById('recordingBadge');
            if (badge) badge.style.display = isRec ? 'inline-flex' : 'none';

            if (isRec) {
                playToneChime(523.25, 0.35, 'sine');
                showToast(`🔴 Enregistrement actif par ${startedBy}.`, 'danger');
                if (!hasGivenRecordingConsent && myPeerId !== payload.initiator_peer_id) {
                    openRecordingConsentModal();
                }
            } else {
                showToast("⏹️ L'enregistrement de la session a été interrompu.", 'info');
            }
            break;
        }

        case 'recording_consent_request': {
            // Demande explicite de consentement RGPD
            if (!hasGivenRecordingConsent && myPeerId !== payload.initiator_peer_id) {
                openRecordingConsentModal();
            }
            break;
        }

        case 'breakout_started': {
            // Répartition lancée par l'organisateur
            handleBreakoutStartedSignal(payload);
            break;
        }

        case 'breakout_peer_moved': {
            // Déplacement individuel d'un participant vers une autre salle
            handleBreakoutPeerMovedSignal(payload);
            break;
        }

        case 'breakout_broadcast_msg': {
            // Annonce générale de l'hôte aux sous-groupes
            handleBreakoutBroadcastSignal(payload);
            break;
        }

        case 'breakout_close_warning': {
            // Compte à rebours avant fermeture
            handleBreakoutCloseWarningSignal(payload);
            break;
        }

        case 'breakout_ended': {
            // Clôture et rapatriement dans la salle principale
            handleBreakoutEndedSignal(payload);
            break;
        }

        case 'breakout_help_requested': {
            // Un sous-groupe appelle l'hôte à l'aide
            handleBreakoutHelpRequestedSignal(payload);
            break;
        }

        case 'live_caption': {
            // Sous-titre ou intervention vocale reçue en direct
            handleLiveCaptionSignal(payload);
            break;
        }
    }
}

// Battement de cœur vers le serveur
async function sendHeartbeat() {
    try {
        const payload = {
            room_id: roomId,
            peer_id: myPeerId,
            is_audio_muted: isAudioMuted ? 1 : 0,
            is_video_muted: isVideoStopped ? 1 : 0,
            is_screen_sharing: isScreenSharing ? 1 : 0,
            is_hand_raised: isHandRaised ? 1 : 0
        };

        const res = await fetch('api/classroom_signal.php?action=heartbeat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success && Array.isArray(data.active_peers)) {
            updateParticipantsRoster(data.active_peers);
        }
    } catch (e) {}
}

// -------------------------------------------------------------
// 4. AFFICHAGE DES CARTES VIDÉO DES PAIRS WEBRTC DISTANTS
// -------------------------------------------------------------
function mountRemotePeerCard(peerId, peerName, peerRole, stream) {
    const wrapper = document.getElementById('remoteVideosWrapper');
    if (!wrapper) return;

    let card = document.getElementById(`card_peer_${peerId}`);
    if (card) {
        const videoEl = document.getElementById(`video_${peerId}`);
        if (videoEl && videoEl.srcObject !== stream) {
            videoEl.srcObject = stream;
            videoEl.play().catch(() => {});
        }
        return;
    }

    const initials = (peerName || 'P').substring(0, 2).toUpperCase();

    card = document.createElement('div');
    card.className = 'video-card';
    card.id = `card_peer_${peerId}`;
    card.setAttribute('data-peer-id', peerId);

    card.innerHTML = `
        <video id="video_${peerId}" autoplay playsinline></video>
        <!-- Badge main levée -->
        <div id="hand_badge_${peerId}" class="hand-raised-badge" style="display:none;">
            <i class="fa-solid fa-hand"></i> Main Levée
        </div>
        <!-- Actions d'agrandissement et épinglage -->
        <div class="video-overlay-actions">
            <button type="button" class="btn-pin-tile" onclick="togglePinCard('card_peer_${peerId}')" title="Épingler cette vidéo">
                <i class="fa-solid fa-thumbtack"></i>
            </button>
            <button type="button" class="video-action-btn" onclick="toggleCardFullscreen('card_peer_${peerId}')" title="Agrandir">
                <i class="fa-solid fa-expand"></i>
            </button>
        </div>
        <!-- Placeholder si pas de vidéo -->
        <div id="ph_${peerId}" class="video-placeholder" style="display:none; position:absolute;">
            <div class="avatar-circle" style="background:#2563EB;">${initials}</div>
            <span style="font-size:0.82rem; color:#CBD5E1;">${escapeHtml(peerName)}</span>
        </div>
        <div class="video-name-tag">
            <button type="button" class="video-mic-btn" id="mic_btn_${peerId}" title="Statut microphone">
                <i class="fa-solid fa-microphone" id="mic_icon_${peerId}" style="color:#10B981;"></i>
            </button>
            <span>${escapeHtml(peerName)}</span>
        </div>
        <canvas id="waveform_${peerId}" class="waveform-canvas" width="100" height="30"></canvas>
    `;

    wrapper.appendChild(card);

    const video = document.getElementById(`video_${peerId}`);
    if (video) {
        video.srcObject = stream;
        video.play().catch(() => {});
    }

    activeRemoteStreams[peerId] = stream;

    // Brancher la détection de parole (VAD) pour ce pair distant
    setupRemotePeerAudioAnalysis(peerId, stream);

    updateGridDimensions();
}

function removeRemotePeerCard(peerId) {
    const card = document.getElementById(`card_peer_${peerId}`);
    if (card && card.parentNode) {
        card.parentNode.removeChild(card);
    }
    delete activeRemoteStreams[peerId];
    if (peerConnections[peerId]) {
        try { peerConnections[peerId].close(); } catch (e) {}
        delete peerConnections[peerId];
    }
    delete peerInfo[peerId];
    delete peerAnalysers[peerId];

    // Si la carte était épinglée, réinitialiser
    if (pinnedCardId === `card_peer_${peerId}`) {
        togglePinCard(pinnedCardId);
    }

    updateGridDimensions();
}

// Analyse audio distante pour illuminer le halo "is-speaking"
function setupRemotePeerAudioAnalysis(peerId, stream) {
    try {
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (stream.getAudioTracks().length === 0) return;

        const source = audioContext.createMediaStreamSource(stream);
        const rAnalyser = audioContext.createAnalyser();
        rAnalyser.fftSize = 64;
        source.connect(rAnalyser);
        peerAnalysers[peerId] = rAnalyser;

        const canvas = document.getElementById(`waveform_${peerId}`);
        const ctx = canvas ? canvas.getContext('2d') : null;
        const dataArr = new Uint8Array(rAnalyser.frequencyBinCount);

        function checkRemoteVoice() {
            if (!peerAnalysers[peerId]) return;
            requestAnimationFrame(checkRemoteVoice);
            rAnalyser.getByteFrequencyData(dataArr);

            let sum = 0;
            for (let i = 0; i < dataArr.length; i++) sum += dataArr[i];
            const avg = sum / dataArr.length;

            const card = document.getElementById(`card_peer_${peerId}`);
            if (card) {
                if (avg > 14) {
                    card.classList.add('is-speaking');
                } else {
                    card.classList.remove('is-speaking');
                }
            }

            // Dessiner la forme d'onde
            if (ctx && canvas) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                const barW = (canvas.width / dataArr.length) * 1.5;
                let x = 0;
                for (let i = 0; i < dataArr.length; i++) {
                    const h = (dataArr[i] / 255) * canvas.height;
                    ctx.fillStyle = '#10B981';
                    ctx.fillRect(x, canvas.height - h, barW, h);
                    x += barW + 2;
                }
            }
        }
        checkRemoteVoice();
    } catch (e) {}
}

function updateRemotePeerMediaUI(peerId, payload) {
    const card = document.getElementById(`card_peer_${peerId}`);
    if (!card) return;

    if (payload.is_audio_muted !== undefined) {
        const micIcon = document.getElementById(`mic_icon_${peerId}`);
        if (micIcon) {
            micIcon.className = payload.is_audio_muted ? 'fa-solid fa-microphone-slash' : 'fa-solid fa-microphone';
            micIcon.style.color = payload.is_audio_muted ? '#EF4444' : '#10B981';
        }
    }

    if (payload.is_video_muted !== undefined) {
        const ph = document.getElementById(`ph_${peerId}`);
        const vid = document.getElementById(`video_${peerId}`);
        if (ph && vid) {
            ph.style.display = payload.is_video_muted ? 'flex' : 'none';
            vid.style.opacity = payload.is_video_muted ? '0' : '1';
        }
    }

    if (payload.is_screen_sharing !== undefined) {
        if (payload.is_screen_sharing === 1) {
            if (pinnedCardId !== card.id) {
                togglePinCard(card.id);
                showToast(`${peerInfo[peerId]?.name || 'Un participant'} présente son écran en direct`, 'info');
            }
        } else if (payload.is_screen_sharing === 0 && pinnedCardId === card.id) {
            togglePinCard(card.id);
        }
    }

    if (payload.is_hand_raised !== undefined) {
        const hand = document.getElementById(`hand_badge_${peerId}`);
        if (hand) {
            hand.style.display = payload.is_hand_raised ? 'inline-flex' : 'none';
        }
    }
}

// Mise à jour adaptative de la grille selon le nombre de participants
function updateGridDimensions() {
    const grid = document.getElementById('videoGrid');
    if (!grid) return;

    const cards = grid.querySelectorAll('.video-card');
    const count = cards.length;

    // Mettre à jour le compteur dans le header et le tab
    const cntBadge = document.getElementById('connectedCountBadge');
    const tabCnt = document.getElementById('tabMembersCount');
    if (cntBadge) cntBadge.textContent = count;
    if (tabCnt) tabCnt.textContent = count;

    if (grid.classList.contains('spotlight-active')) {
        return; // Ne pas casser la vue focus
    }

    grid.classList.remove('layout-1', 'layout-2', 'layout-3', 'layout-4', 'layout-multi');

    if (count <= 1) {
        grid.classList.add('layout-1');
    } else if (count === 2) {
        grid.classList.add('layout-2');
    } else if (count <= 4) {
        grid.classList.add('layout-4');
    } else {
        grid.classList.add('layout-multi');
    }
}

// -------------------------------------------------------------
// 5. MODE FOCUS / SPOTLIGHT / PIN (Style Google Meet)
// -------------------------------------------------------------
window.togglePinCard = function(cardId) {
    const grid = document.getElementById('videoGrid');
    const targetCard = document.getElementById(cardId);
    if (!grid || !targetCard) return;

    if (pinnedCardId === cardId) {
        // Désactiver l'épinglage
        grid.classList.remove('spotlight-active');
        targetCard.classList.remove('is-spotlight');
        const pinBtn = targetCard.querySelector('.btn-pin-tile');
        if (pinBtn) pinBtn.classList.remove('active');
        pinnedCardId = null;
        updateGridDimensions();
        showToast('Vue Grille restaurée', 'info');
    } else {
        // Désépingler l'ancienne si présente
        if (pinnedCardId) {
            const old = document.getElementById(pinnedCardId);
            if (old) {
                old.classList.remove('is-spotlight');
                const ob = old.querySelector('.btn-pin-tile');
                if (ob) ob.classList.remove('active');
            }
        }
        grid.classList.add('spotlight-active');
        targetCard.classList.add('is-spotlight');
        const pinBtn = targetCard.querySelector('.btn-pin-tile');
        if (pinBtn) pinBtn.classList.add('active');
        pinnedCardId = cardId;
        showToast('Participant épinglé en mode focus', 'success');
    }
};

// -------------------------------------------------------------
// 6. CONTRÔLES MÉDIA LOCAUX (MIC, CAM, ÉCRAN, RECORD, ETC.)
// -------------------------------------------------------------
function initClassroomControls() {
    const btnCam = document.getElementById('btnToggleCam');
    const btnMic = document.getElementById('btnToggleMic');
    const btnScreen = document.getElementById('btnScreenShare');
    const btnLeave = document.getElementById('btnLeaveRoom');
    const btnWb = document.getElementById('btnToggleWhiteboard');
    const btnRaiseHand = document.getElementById('btnRaiseHandCtrl');
    const btnReaction = document.getElementById('btnTriggerReaction');
    const btnRecord = document.getElementById('btnRecordSession');
    const btnSettings = document.getElementById('btnSettingsModal');
    const btnPip = document.getElementById('btnPipLocal');
    const btnStopScreen = document.getElementById('btnStopScreenShareBanner');

    // Bascule Micro
    if (btnMic) {
        btnMic.addEventListener('click', () => {
            toggleLocalMic();
        });
    }

    // Bascule Caméra
    if (btnCam) {
        btnCam.addEventListener('click', () => {
            isVideoStopped = !isVideoStopped;
            if (localStream) {
                const videoTrack = localStream.getVideoTracks()[0];
                if (videoTrack) videoTrack.enabled = !isVideoStopped;
            }
            btnCam.classList.toggle('active', isVideoStopped);
            btnCam.innerHTML = isVideoStopped ? '<i class="fa-solid fa-video-slash"></i>' : '<i class="fa-solid fa-video"></i>';

            const ph = document.getElementById('localVideoPlaceholder');
            const vid = document.getElementById('myLocalVideo');
            if (ph && vid) {
                ph.style.display = isVideoStopped ? 'flex' : 'none';
                vid.style.opacity = isVideoStopped ? '0' : '1';
            }

            broadcastMediaState();
            showToast(isVideoStopped ? 'Caméra désactivée' : 'Caméra activée', isVideoStopped ? 'info' : 'success');
        });
    }

    // Partage d'écran HD WebRTC
    if (btnScreen) {
        btnScreen.addEventListener('click', toggleScreenSharing);
    }
    if (btnStopScreen) {
        btnStopScreen.addEventListener('click', toggleScreenSharing);
    }

    // Tableau blanc toggle
    if (btnWb) {
        btnWb.addEventListener('click', () => {
            const wb = document.getElementById('whiteboardLayer');
            if (wb) {
                wb.classList.toggle('active');
                btnWb.classList.toggle('active');
                if (wb.classList.contains('active')) {
                    resizeWhiteboard();
                }
            }
        });
    }

    // Lever la main
    if (btnRaiseHand) {
        btnRaiseHand.addEventListener('click', toggleRaiseHand);
    }

    // Pop-up réactions
    if (btnReaction) {
        btnReaction.addEventListener('click', (e) => {
            e.stopPropagation();
            const popup = document.getElementById('reactionsPopup');
            if (popup) {
                popup.style.display = (popup.style.display === 'none' || !popup.style.display) ? 'flex' : 'none';
            }
        });
    }

    document.addEventListener('click', (e) => {
        const popup = document.getElementById('reactionsPopup');
        const btnReaction = document.getElementById('btnTriggerReaction');
        if (popup && popup.style.display === 'flex' && !popup.contains(e.target) && e.target !== btnReaction && !btnReaction.contains(e.target)) {
            popup.style.display = 'none';
        }
    });

    // Enregistrement de session
    if (btnRecord) {
        btnRecord.addEventListener('click', toggleSessionRecording);
    }

    // Paramètres
    if (btnSettings) {
        btnSettings.addEventListener('click', openAvSettings);
    }

    // Picture-in-Picture
    if (btnPip) {
        btnPip.addEventListener('click', async () => {
            const video = document.getElementById('myLocalVideo');
            if (!video) return;
            try {
                if (document.pictureInPictureElement) {
                    await document.exitPictureInPicture();
                } else if (document.pictureInPictureEnabled) {
                    await video.requestPictureInPicture();
                }
            } catch (err) {
                showToast('PiP non supporté : ' + err.message, 'info');
            }
        });
    }

    // Quitter la salle
    if (btnLeave) {
        btnLeave.addEventListener('click', handleLeaveMeeting);
    }
}

// Démarrage de la capture média locale
async function startLocalMedia(micId = null, camId = null) {
    try {
        const constraints = {
            audio: micId ? { deviceId: { exact: micId } } : true,
            video: camId ? { deviceId: { exact: camId }, width: { ideal: 1280 } } : { width: { ideal: 1280 } }
        };

        if (localStream) {
            localStream.getTracks().forEach(t => t.stop());
        }

        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        localStream = stream;

        const myVideo = document.getElementById('myLocalVideo');
        const ph = document.getElementById('localVideoPlaceholder');
        if (myVideo) {
            myVideo.srcObject = stream;
            myVideo.style.opacity = '1';
            myVideo.play().catch(() => {});
        }
        if (ph) ph.style.display = 'none';

        setupAudioAnalysis(stream);
        populateDeviceDropdowns();
    } catch (e) {
        console.warn('Accès média refusé:', e);
        const ph = document.getElementById('localVideoPlaceholder');
        if (ph) ph.style.display = 'flex';
    }
}

// Bascule du microphone local
window.toggleLocalMic = function(forceMute = null, isPtt = false) {
    if (forceMute !== null) {
        isAudioMuted = forceMute;
    } else {
        isAudioMuted = !isAudioMuted;
    }

    if (localStream) {
        const audioTrack = localStream.getAudioTracks()[0];
        if (audioTrack) audioTrack.enabled = !isAudioMuted;
    }

    const btnMic = document.getElementById('btnToggleMic');
    if (btnMic) {
        btnMic.classList.toggle('active', isAudioMuted);
        btnMic.innerHTML = isAudioMuted ? '<i class="fa-solid fa-microphone-slash"></i>' : '<i class="fa-solid fa-microphone"></i>';
    }

    const micIndicator = document.getElementById('localMicIndicator');
    if (micIndicator) {
        micIndicator.className = isAudioMuted ? 'fa-solid fa-microphone-slash' : 'fa-solid fa-microphone';
        micIndicator.style.color = isAudioMuted ? '#EF4444' : '#10B981';
    }

    const rowMic = document.getElementById('rowLocalMicIcon');
    if (rowMic) {
        rowMic.innerHTML = isAudioMuted ? '<i class="fa-solid fa-microphone-slash" style="color:#EF4444;"></i>' : '<i class="fa-solid fa-microphone" style="color:#10B981;"></i>';
    }

    const localCard = document.getElementById('localVideoContainer');
    if (isAudioMuted && localCard) {
        localCard.classList.remove('is-speaking');
    }

    broadcastMediaState();

    if (!isPtt) {
        showToast(isAudioMuted ? "Votre microphone est désactivé (Muet)" : "Votre microphone est activé (En direct)", isAudioMuted ? "info" : "success");
    }
};

// Partage d'écran HD WebRTC avec remplacement direct de piste
async function toggleScreenSharing() {
    const btnScreen = document.getElementById('btnScreenShare');
    const banner = document.getElementById('screenShareActiveBanner');
    const myVideo = document.getElementById('myLocalVideo');

    if (!isScreenSharing) {
        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
                showToast('Le partage d\'écran n\'est pas supporté par ce navigateur.', 'info');
                return;
            }

            const screenStream = await navigator.mediaDevices.getDisplayMedia({
                video: { cursor: "always" },
                audio: true
            });

            const screenTrack = screenStream.getVideoTracks()[0];

            // Remplacer la piste vidéo locale dans toutes les connexions RTCPeerConnection actives
            for (const pid in peerConnections) {
                const pc = peerConnections[pid];
                const senders = pc.getSenders();
                const videoSender = senders.find(s => s.track && s.track.kind === 'video');
                if (videoSender) {
                    videoSender.replaceTrack(screenTrack);
                }
            }

            if (myVideo) {
                myVideo.srcObject = screenStream;
                myVideo.play().catch(() => {});
            }

            isScreenSharing = true;
            if (btnScreen) btnScreen.classList.add('active');
            if (banner) banner.style.display = 'flex';
            if (pinnedCardId !== 'localVideoContainer') {
                togglePinCard('localVideoContainer');
            }
            broadcastMediaState();
            showToast('Partage d\'écran démarré', 'success');

            screenTrack.onended = () => {
                stopScreenShare();
            };
        } catch (err) {
            if (err.name !== 'NotAllowedError') {
                showToast('Erreur lors du partage d\'écran : ' + err.message, 'info');
            }
        }
    } else {
        stopScreenShare();
    }
}

function stopScreenShare() {
    isScreenSharing = false;
    const btnScreen = document.getElementById('btnScreenShare');
    const banner = document.getElementById('screenShareActiveBanner');
    const myVideo = document.getElementById('myLocalVideo');

    if (btnScreen) btnScreen.classList.remove('active');
    if (banner) banner.style.display = 'none';

    if (pinnedCardId === 'localVideoContainer') {
        togglePinCard('localVideoContainer');
    }
    broadcastMediaState();

    // Restaurer la piste caméra locale vers tous les pairs
    if (localStream) {
        const cameraTrack = localStream.getVideoTracks()[0];
        for (const pid in peerConnections) {
            const pc = peerConnections[pid];
            const senders = pc.getSenders();
            const videoSender = senders.find(s => s.track && s.track.kind === 'video');
            if (videoSender && cameraTrack) {
                videoSender.replaceTrack(cameraTrack);
            }
        }
        if (myVideo) {
            myVideo.srcObject = localStream;
            myVideo.play().catch(() => {});
        }
    }
    showToast('Partage d\'écran arrêté.', 'info');
}

// Diffuser l'état média aux autres pairs
function broadcastMediaState() {
    const payload = {
        is_audio_muted: isAudioMuted ? 1 : 0,
        is_video_muted: isVideoStopped ? 1 : 0,
        is_screen_sharing: isScreenSharing ? 1 : 0,
        is_hand_raised: isHandRaised ? 1 : 0
    };
    sendSignal('broadcast', 'peer_media_state', payload);
}

// -------------------------------------------------------------
// 7. ANALYSEUR AUDIO LOCAL (VUMÈTRE & VAD)
// -------------------------------------------------------------
function setupAudioAnalysis(stream) {
    try {
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (audioContext.state === 'suspended') {
            audioContext.resume();
        }

        analyser = audioContext.createAnalyser();
        analyser.fftSize = 64;

        const source = audioContext.createMediaStreamSource(stream);
        source.connect(analyser);

        const canvas = document.getElementById('localWaveform');
        const meter = document.getElementById('micLevelMeter');
        const ctx = canvas ? canvas.getContext('2d') : null;
        const bufferLength = analyser.frequencyBinCount;
        const dataArray = new Uint8Array(bufferLength);

        function drawWave() {
            audioVisualizerFrame = requestAnimationFrame(drawWave);
            analyser.getByteFrequencyData(dataArray);

            let sum = 0;
            if (ctx && canvas) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                const barWidth = (canvas.width / bufferLength) * 1.5;
                let x = 0;

                for (let i = 0; i < bufferLength; i++) {
                    const val = dataArray[i];
                    sum += val;
                    const barHeight = (val / 255) * canvas.height;
                    ctx.fillStyle = isAudioMuted ? '#EF4444' : '#10B981';
                    ctx.fillRect(x, canvas.height - barHeight, barWidth, barHeight);
                    x += barWidth + 2;
                }
            } else {
                for (let i = 0; i < bufferLength; i++) sum += dataArray[i];
            }

            // Détection de voix active (VAD)
            const average = sum / bufferLength;
            const localCard = document.getElementById('localVideoContainer');
            if (localCard) {
                if (!isAudioMuted && average > 14) {
                    localCard.classList.add('is-speaking');
                } else {
                    localCard.classList.remove('is-speaking');
                }
            }

            // Vumètre dans la modale des paramètres
            if (meter) {
                const percentage = Math.min(100, Math.round((average / 140) * 100));
                meter.style.width = isAudioMuted ? '0%' : (percentage + '%');
                meter.style.background = percentage > 75 ? '#EF4444' : (percentage > 40 ? '#F59E0B' : '#10B981');
            }
        }
        drawWave();
    } catch (e) {}
}

// -------------------------------------------------------------
// 8. TABLEAU BLANC COLLABORATIF EN DIRECT (SYNCHRONISÉ)
// -------------------------------------------------------------
function initWhiteboard() {
    const canvas = document.getElementById('wbCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    let drawing = false;
    let currentTool = 'pen';
    let currentColor = '#0F172A';
    let currentLineWidth = 5;
    let lastX = 0, lastY = 0;

    function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();
        if (rect.width > 0 && rect.height > 0) {
            const temp = ctx.getImageData(0, 0, canvas.width || 1, canvas.height || 1);
            canvas.width = rect.width;
            canvas.height = rect.height;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            try { ctx.putImageData(temp, 0, 0); } catch (e) {}
        }
    }
    window.resizeWhiteboard = resizeCanvas;
    window.addEventListener('resize', resizeCanvas);
    setTimeout(resizeCanvas, 500);

    // Outils
    document.querySelectorAll('.wb-tool-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.wb-tool-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentTool = btn.getAttribute('data-tool');
        });
    });

    // Couleurs
    document.querySelectorAll('.wb-color-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.wb-color-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentColor = btn.getAttribute('data-color');
        });
    });

    // Épaisseur
    const strokeSelect = document.getElementById('wbStrokeWidth');
    if (strokeSelect) {
        strokeSelect.addEventListener('change', () => {
            currentLineWidth = parseInt(strokeSelect.value);
        });
    }

    // Dessin à la souris / tactile
    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
    }

    function startDraw(e) {
        e.preventDefault();
        drawing = true;
        const pos = getPos(e);
        lastX = pos.x;
        lastY = pos.y;
    }

    function draw(e) {
        if (!drawing) return;
        e.preventDefault();
        const pos = getPos(e);

        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(pos.x, pos.y);

        if (currentTool === 'eraser') {
            ctx.strokeStyle = '#FFFFFF';
            ctx.lineWidth = currentLineWidth * 3;
        } else if (currentTool === 'highlighter') {
            ctx.strokeStyle = currentColor + '55';
            ctx.lineWidth = currentLineWidth * 2.5;
        } else {
            ctx.strokeStyle = currentColor;
            ctx.lineWidth = currentLineWidth;
        }
        ctx.stroke();

        // Diffuser le trait aux autres pairs
        sendSignal('broadcast', 'whiteboard_draw', {
            x0: lastX / canvas.width,
            y0: lastY / canvas.height,
            x1: pos.x / canvas.width,
            y1: pos.y / canvas.height,
            tool: currentTool,
            color: currentColor,
            size: currentLineWidth
        });

        lastX = pos.x;
        lastY = pos.y;
    }

    function stopDraw() {
        drawing = false;
    }

    canvas.addEventListener('mousedown', startDraw);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDraw);
    canvas.addEventListener('mouseleave', stopDraw);
    canvas.addEventListener('touchstart', startDraw, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', stopDraw);

    // Effacer
    const clearBtn = document.getElementById('wbClearBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            sendSignal('broadcast', 'whiteboard_draw', { clear: true });
            showToast('Tableau blanc réinitialisé.', 'info');
        });
    }

    // Télécharger PNG
    const dlBtn = document.getElementById('wbDownloadBtn');
    if (dlBtn) {
        dlBtn.addEventListener('click', () => {
            const a = document.createElement('a');
            a.download = `oshdi-whiteboard-${Date.now()}.png`;
            a.href = canvas.toDataURL('image/png');
            a.click();
            showToast('Tableau blanc exporté en PNG.', 'success');
        });
    }

    // Fermer
    const closeBtn = document.getElementById('wbCloseBtn');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            const wb = document.getElementById('whiteboardLayer');
            const btnWb = document.getElementById('btnToggleWhiteboard');
            if (wb) wb.classList.remove('active');
            if (btnWb) btnWb.classList.remove('active');
        });
    }
}

// Rendu des traits distants sur le tableau blanc
function renderRemoteWhiteboardStroke(stroke) {
    const canvas = document.getElementById('wbCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    if (stroke.clear) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        return;
    }

    ctx.beginPath();
    ctx.moveTo(stroke.x0 * canvas.width, stroke.y0 * canvas.height);
    ctx.lineTo(stroke.x1 * canvas.width, stroke.y1 * canvas.height);

    if (stroke.tool === 'eraser') {
        ctx.strokeStyle = '#FFFFFF';
        ctx.lineWidth = (stroke.size || 5) * 3;
    } else if (stroke.tool === 'highlighter') {
        ctx.strokeStyle = stroke.color + '55';
        ctx.lineWidth = (stroke.size || 5) * 2.5;
    } else {
        ctx.strokeStyle = stroke.color || '#0F172A';
        ctx.lineWidth = stroke.size || 5;
    }
    ctx.stroke();
}

// -------------------------------------------------------------
// 9. RÉACTIONS FLOTTANTES ANIMÉES
// -------------------------------------------------------------
window.sendReaction = function(emoji) {
    const popup = document.getElementById('reactionsPopup');
    if (popup) popup.style.display = 'none';

    animateFloatingEmoji(emoji);
    sendSignal('broadcast', 'reaction', { emoji: emoji });
    postChatMessage(emoji, 'reaction');
};

function animateFloatingEmoji(emoji) {
    const container = document.getElementById('floatingReactionsContainer');
    if (!container) return;

    const bubble = document.createElement('div');
    bubble.className = 'floating-emoji';
    bubble.textContent = emoji;
    const randX = Math.floor(Math.random() * 60) + 20;
    bubble.style.left = randX + '%';
    bubble.style.bottom = '15%';
    container.appendChild(bubble);

    setTimeout(() => {
        if (bubble.parentNode) bubble.parentNode.removeChild(bubble);
    }, 2800);
}

// -------------------------------------------------------------
// 10. LEVÉE DE MAIN & PARTICIPATION
// -------------------------------------------------------------
function toggleRaiseHand() {
    isHandRaised = !isHandRaised;

    const btnCtrl = document.getElementById('btnRaiseHandCtrl');
    const btnHeader = document.getElementById('btnHeaderRaiseHand');
    if (btnCtrl) btnCtrl.classList.toggle('active', isHandRaised);
    if (btnHeader) btnHeader.classList.toggle('btn-gold', isHandRaised);

    const badge = document.getElementById('localHandBadge');
    if (badge) badge.style.display = isHandRaised ? 'inline-flex' : 'none';

    const localRowSub = document.getElementById('localUserSubtitle');
    if (localRowSub) {
        localRowSub.innerHTML = isHandRaised ? '<span style="color:#F59E0B; font-weight:700;"><i class="fa-solid fa-hand"></i> Main levée</span>' : 'Participant';
    }

    broadcastMediaState();

    if (isHandRaised) {
        playToneChime(880, 0.35, 'triangle');
        showToast('Vous avez levé la main. Le formateur est notifié.', 'info');
        postChatMessage('✋ A levé la main pour demander la parole.', 'system');
    } else {
        showToast('Main baissée.', 'info');
    }
}

// -------------------------------------------------------------
// 11. ENREGISTREMENT DE SESSION (Mixage Audio/Vidéo HD & Rétention)
// -------------------------------------------------------------
function toggleSessionRecording() {
    if (!isRecordingSession) {
        startSessionRecording();
    } else {
        stopSessionRecording();
    }
}

async function startSessionRecording() {
    try {
        // Choix de la source vidéo : Partage d'écran HD si actif, sinon caméra locale
        let videoTrack = null;
        if (typeof screenStream !== 'undefined' && screenStream && screenStream.getVideoTracks().length > 0) {
            videoTrack = screenStream.getVideoTracks()[0];
        } else if (localStream && localStream.getVideoTracks().length > 0) {
            videoTrack = localStream.getVideoTracks()[0];
        }

        if (!videoTrack && (!localStream || localStream.getAudioTracks().length === 0)) {
            showToast('Aucun flux vidéo ou audio actif à capturer.', 'info');
            return;
        }

        // Mixage Audio Multi-Sources via Web Audio API (Voix locale + Partage + Pairs distants)
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        recordingAudioMixContext = new AudioContextClass();
        const mixDestination = recordingAudioMixContext.createMediaStreamDestination();

        // 1. Audio du microphone local
        if (localStream && localStream.getAudioTracks().length > 0) {
            try {
                const localAudioSrc = recordingAudioMixContext.createMediaStreamSource(new MediaStream([localStream.getAudioTracks()[0]]));
                localAudioSrc.connect(mixDestination);
            } catch (e) {
                console.warn('Erreur mixage audio local:', e);
            }
        }

        // 2. Audio du partage d'écran (son du système ou de l'onglet si présent)
        if (typeof screenStream !== 'undefined' && screenStream && screenStream.getAudioTracks().length > 0) {
            try {
                const screenAudioSrc = recordingAudioMixContext.createMediaStreamSource(new MediaStream([screenStream.getAudioTracks()[0]]));
                screenAudioSrc.connect(mixDestination);
            } catch (e) {
                console.warn('Erreur mixage audio écran:', e);
            }
        }

        // 3. Audio des participants WebRTC connectés
        if (typeof activeRemoteStreams === 'object') {
            Object.entries(activeRemoteStreams).forEach(([pId, remStream]) => {
                if (remStream && remStream.getAudioTracks().length > 0) {
                    try {
                        const remAudioSrc = recordingAudioMixContext.createMediaStreamSource(new MediaStream([remStream.getAudioTracks()[0]]));
                        remAudioSrc.connect(mixDestination);
                    } catch (e) {
                        console.warn(`Erreur mixage audio pair ${pId}:`, e);
                    }
                }
            });
        }

        // Assemblage du flux composite final
        const mixedAudioTrack = mixDestination.stream.getAudioTracks()[0];
        const compositeTracks = [];
        if (videoTrack) compositeTracks.push(videoTrack);
        if (mixedAudioTrack) compositeTracks.push(mixedAudioTrack);

        const compositeStream = new MediaStream(compositeTracks);

        sessionRecordedChunks = [];
        let mimeType = 'video/webm;codecs=vp9,opus';
        if (!MediaRecorder.isTypeSupported(mimeType)) {
            mimeType = 'video/webm;codecs=vp8,opus';
        }
        if (!MediaRecorder.isTypeSupported(mimeType)) {
            mimeType = 'video/webm';
        }

        sessionMediaRecorder = new MediaRecorder(compositeStream, {
            mimeType: mimeType,
            videoBitsPerSecond: 2500000,
            audioBitsPerSecond: 128000
        });

        sessionMediaRecorder.ondataavailable = (e) => {
            if (e.data && e.data.size > 0) {
                sessionRecordedChunks.push(e.data);
            }
        };

        sessionMediaRecorder.onstop = () => {
            const blob = new Blob(sessionRecordedChunks, { type: 'video/webm' });
            lastRecordedSessionBlob = blob;
            lastRecordedDuration = sessionRecordSeconds;

            if (recordingAudioMixContext && recordingAudioMixContext.state !== 'closed') {
                try { recordingAudioMixContext.close(); } catch (e) {}
            }

            // Ouvrir la boîte de dialogue de sauvegarde et rétention
            openSaveRecordingModal(blob, sessionRecordSeconds);
        };

        sessionMediaRecorder.start(1000);
        isRecordingSession = true;

        const btnRecord = document.getElementById('btnRecordSession');
        const badge = document.getElementById('recordingBadge');
        if (btnRecord) {
            btnRecord.classList.add('active');
            btnRecord.style.color = '#EF4444';
            btnRecord.title = "Arrêter l'enregistrement de la session";
        }
        if (badge) badge.style.display = 'inline-flex';

        sessionRecordSeconds = 0;
        sessionRecordInterval = setInterval(() => {
            sessionRecordSeconds++;
            const m = String(Math.floor(sessionRecordSeconds / 60)).padStart(2, '0');
            const s = String(sessionRecordSeconds % 60).padStart(2, '0');
            const timerEl = document.getElementById('recordingTimer');
            if (timerEl) timerEl.textContent = `${m}:${s}`;
        }, 1000);

        // Notifier tous les participants et diffuser la demande de consentement explicite
        sendSignal('broadcast', 'recording_status_changed', {
            is_recording: true,
            started_by: currentUserName,
            initiator_peer_id: myPeerId
        });
        sendSignal('broadcast', 'recording_consent_request', {
            initiator_peer_id: myPeerId,
            initiator_name: currentUserName
        });

        postChatMessage(`🔴 [Avis d'Enregistrement] L'enregistrement officiel a débuté (Audio multi-paires mixé).`, 'system');
        showToast("Enregistrement en direct activé avec succès.", "danger");
    } catch (err) {
        showToast("Impossible de démarrer l'enregistrement : " + err.message, "info");
    }
}

function stopSessionRecording() {
    if (sessionMediaRecorder && sessionMediaRecorder.state !== 'inactive') {
        sessionMediaRecorder.stop();
    }
    isRecordingSession = false;
    clearInterval(sessionRecordInterval);

    const btnRecord = document.getElementById('btnRecordSession');
    const badge = document.getElementById('recordingBadge');
    if (btnRecord) {
        btnRecord.classList.remove('active');
        btnRecord.style.color = '';
        btnRecord.title = "Enregistrer la Réunion (Audio + Vidéo en .webm)";
    }
    if (badge) badge.style.display = 'none';

    // Diffuser l'arrêt de l'enregistrement à tous les pairs
    sendSignal('broadcast', 'recording_status_changed', {
        is_recording: false,
        initiator_peer_id: myPeerId
    });

    postChatMessage(`⏹️ L'enregistrement de la visioconférence a été arrêté.`, 'system');
    showToast("Enregistrement finalisé. Configuration de la sauvegarde...", "info");
}

// -------------------------------------------------------------
// MODAL SAUVEGARDE & RÉTENTION PROGRAMMÉE
// -------------------------------------------------------------
function openSaveRecordingModal(blob, durationSec) {
    const modal = document.getElementById('saveRecordingModal');
    if (!modal) return;

    const durationEl = document.getElementById('saveRecDuration');
    const sizeEl = document.getElementById('saveRecSize');
    const titleInput = document.getElementById('saveRecTitleInput');
    const progressWrapper = document.getElementById('uploadProgressWrapper');

    if (durationEl) {
        const m = String(Math.floor(durationSec / 60)).padStart(2, '0');
        const s = String(durationSec % 60).padStart(2, '0');
        durationEl.textContent = `${m}:${s}`;
    }
    if (sizeEl) {
        const mb = (blob.size / (1024 * 1024)).toFixed(2);
        sizeEl.textContent = `${mb} Mo`;
    }
    if (titleInput) {
        const now = new Date();
        titleInput.value = `Session OSHDI - ${now.toLocaleDateString('fr-FR')} ${now.toLocaleTimeString('fr-FR').substring(0, 5)}`;
    }
    if (progressWrapper) progressWrapper.style.display = 'none';

    modal.style.display = 'flex';
}

window.closeSaveRecordingModal = function() {
    const modal = document.getElementById('saveRecordingModal');
    if (modal) modal.style.display = 'none';
};

window.downloadLocalRecordingOnly = function() {
    if (!lastRecordedSessionBlob) {
        showToast("Aucun enregistrement disponible.", "info");
        return;
    }
    const url = URL.createObjectURL(lastRecordedSessionBlob);
    const a = document.createElement('a');
    a.style.display = 'none';
    a.href = url;
    a.download = `OSHDI_Visio_${roomId}_${Date.now()}.webm`;
    document.body.appendChild(a);
    a.click();
    setTimeout(() => {
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }, 150);
    showToast("Fichier média téléchargé sur votre machine.", "success");
    closeSaveRecordingModal();
};

window.submitUploadRecordingVault = function() {
    if (!lastRecordedSessionBlob) {
        showToast("Aucun enregistrement à téléverser.", "info");
        return;
    }

    const titleInput = document.getElementById('saveRecTitleInput');
    const retentionSelect = document.getElementById('saveRecRetentionSelect');
    const progressWrapper = document.getElementById('uploadProgressWrapper');
    const progressBar = document.getElementById('uploadProgressBar');
    const progressPercent = document.getElementById('uploadProgressPercent');
    const btnSubmit = document.getElementById('btnConfirmUploadVault');

    const recordingTitle = (titleInput && titleInput.value.trim()) ? titleInput.value.trim() : 'Enregistrement OSHDI';
    const retentionDays = retentionSelect ? retentionSelect.value : '30';

    if (progressWrapper) progressWrapper.style.display = 'block';
    if (btnSubmit) btnSubmit.disabled = true;

    const formData = new FormData();
    formData.append('action', 'upload');
    formData.append('meeting_code', roomId);
    formData.append('recorded_by', currentUserName);
    formData.append('duration_seconds', lastRecordedDuration);
    formData.append('retention_days', retentionDays);
    formData.append('recording_name', recordingTitle);
    formData.append('video_file', lastRecordedSessionBlob, `recording_${Date.now()}.webm`);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/classroom_recordings.php', true);

    xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            if (progressBar) progressBar.style.width = percent + '%';
            if (progressPercent) progressPercent.textContent = percent + '%';
        }
    };

    xhr.onload = () => {
        if (btnSubmit) btnSubmit.disabled = false;
        try {
            const res = JSON.parse(xhr.responseText);
            if (res.success) {
                showToast("Enregistrement archivé dans le coffre-fort sécurisé avec politique de rétention !", "success");
                closeSaveRecordingModal();
                openRecordingsVault();
            } else {
                showToast("Erreur lors de la sauvegarde : " + (res.error || 'Erreur inconnue'), "info");
            }
        } catch (err) {
            showToast("Réponse inattendue du serveur.", "info");
        }
    };

    xhr.onerror = () => {
        if (btnSubmit) btnSubmit.disabled = false;
        showToast("Erreur de connexion réseau lors du téléversement.", "info");
    };

    xhr.send(formData);
};

// -------------------------------------------------------------
// CONSENTEMENT EXPLICITE D'ENREGISTREMENT (RGPD)
// -------------------------------------------------------------
function openRecordingConsentModal() {
    const modal = document.getElementById('recordingConsentModal');
    if (modal) modal.style.display = 'flex';
}

window.acceptRecordingConsent = function() {
    hasGivenRecordingConsent = true;
    const modal = document.getElementById('recordingConsentModal');
    if (modal) modal.style.display = 'none';
    showToast("Votre consentement a été enregistré. Bonne session !", "success");
};

window.muteAndStayConsent = function() {
    hasGivenRecordingConsent = true;
    const modal = document.getElementById('recordingConsentModal');
    if (modal) modal.style.display = 'none';
    toggleLocalMic(true);
    if (!isVideoStopped) toggleLocalCam();
    showToast("Micro et caméra désactivés pour préserver votre vie privée.", "info");
};

window.leaveMeetingConsent = function() {
    handleLeaveMeeting();
};

// -------------------------------------------------------------
// COFFRE-FORT DES ENREGISTREMENTS & LECTEUR INTÉGRÉ
// -------------------------------------------------------------
window.openRecordingsVault = function() {
    const modal = document.getElementById('recordingsListModal');
    if (modal) {
        modal.style.display = 'flex';
        loadRecordingsVaultList();
    }
};

window.closeRecordingsVault = function() {
    const modal = document.getElementById('recordingsListModal');
    if (modal) modal.style.display = 'none';
    closeVaultPreview();
};

window.closeVaultPreview = function() {
    const playerContainer = document.getElementById('recordingsPlayerContainer');
    const previewVideo = document.getElementById('vaultPreviewVideo');
    if (previewVideo) {
        previewVideo.pause();
        previewVideo.src = '';
    }
    if (playerContainer) playerContainer.style.display = 'none';
};

window.playVaultRecording = function(recId, recTitle) {
    const playerContainer = document.getElementById('recordingsPlayerContainer');
    const previewVideo = document.getElementById('vaultPreviewVideo');
    const titleEl = document.getElementById('vaultPreviewTitle');

    if (!playerContainer || !previewVideo) return;
    if (titleEl) titleEl.textContent = `Lecture : ${recTitle}`;

    previewVideo.src = `api/classroom_recordings.php?action=stream&id=${recId}`;
    playerContainer.style.display = 'block';
    playerContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    previewVideo.play().catch(() => {});
};

window.deleteVaultRecording = async function(recId, recTitle) {
    if (!confirm(`Supprimer définitivement "${recTitle}" du coffre-fort OSHDI ?`)) {
        return;
    }
    try {
        const res = await fetch('api/classroom_recordings.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: recId })
        });
        const data = await res.json();
        if (data.success) {
            showToast("Enregistrement supprimé avec succès.", "warning");
            loadRecordingsVaultList();
            closeVaultPreview();
        } else {
            showToast("Erreur suppression : " + (data.error || ''), "info");
        }
    } catch (e) {
        showToast("Erreur de connexion réseau.", "info");
    }
};

async function loadRecordingsVaultList() {
    const wrapper = document.getElementById('recordingsVaultListWrapper');
    if (!wrapper) return;
    wrapper.innerHTML = '<small style="color:#64748B;"><i class="fa-solid fa-spinner fa-spin"></i> Chargement des enregistrements sécurisés...</small>';

    try {
        const res = await fetch(`api/classroom_recordings.php?action=list&meeting_code=${encodeURIComponent(roomId)}`);
        const data = await res.json();

        if (!data.success || !Array.isArray(data.recordings) || data.recordings.length === 0) {
            wrapper.innerHTML = `
                <div style="text-align:center; padding:24px 10px; background:rgba(255,255,255,0.02); border-radius:8px; border:1px dashed rgba(255,255,255,0.1);">
                    <i class="fa-solid fa-folder-open" style="font-size:2rem; color:#64748B; margin-bottom:8px;"></i>
                    <p style="color:#94A3B8; font-size:0.85rem; margin:0;">Aucun enregistrement pour cette visioconférence.</p>
                    <small style="color:#64748B; font-size:0.75rem;">Cliquez sur l'icône disque dans la barre de commandes pour lancer un enregistrement.</small>
                </div>
            `;
            return;
        }

        wrapper.innerHTML = '';
        const isMod = (userRole === 'host' || userRole === 'cohost');

        data.recordings.forEach(rec => {
            const item = document.createElement('div');
            item.style.background = 'rgba(255,255,255,0.04)';
            item.style.border = '1px solid rgba(255,255,255,0.08)';
            item.style.borderRadius = '8px';
            item.style.padding = '10px 14px';
            item.style.display = 'flex';
            item.style.justifyContent = 'space-between';
            item.style.alignItems = 'center';
            item.style.gap = '10px';
            item.style.flexWrap = 'wrap';

            const remainingColor = rec.days_remaining <= 3 ? '#EF4444' : '#10B981';

            item.innerHTML = `
                <div style="flex:1; min-width:200px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-file-video" style="color:#F59E0B; font-size:1.1rem;"></i>
                        <strong style="color:#FFFFFF; font-size:0.88rem;">${escapeHtml(rec.recording_name)}</strong>
                    </div>
                    <div style="display:flex; gap:12px; margin-top:4px; font-size:0.75rem; color:#94A3B8; flex-wrap:wrap;">
                        <span><i class="fa-regular fa-clock"></i> ${rec.duration_fmt}</span>
                        <span><i class="fa-solid fa-hard-drive"></i> ${rec.file_size_fmt}</span>
                        <span><i class="fa-solid fa-user"></i> ${escapeHtml(rec.recorded_by)}</span>
                        <span style="color:${remainingColor}; font-weight:600;">
                            <i class="fa-solid fa-hourglass-half"></i> Expire dans ${rec.days_remaining} j (Rétention ${rec.retention_days}j)
                        </span>
                    </div>
                </div>
                <div style="display:flex; gap:6px; align-items:center;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="playVaultRecording(${rec.id}, '${escapeHtml(rec.recording_name)}')" style="color:#10B981; border-color:rgba(16,185,129,0.4); font-size:0.75rem; padding:4px 8px;" title="Prévisualiser la vidéo">
                        <i class="fa-solid fa-play"></i> Lire
                    </button>
                    <a href="api/classroom_recordings.php?action=download&id=${rec.id}" class="btn btn-gold btn-sm" style="font-size:0.75rem; padding:4px 8px;" title="Télécharger l'enregistrement sur votre appareil">
                        <i class="fa-solid fa-download"></i> Télécharger
                    </a>
                    ${isMod ? `
                    <button type="button" class="btn btn-outline btn-sm" onclick="deleteVaultRecording(${rec.id}, '${escapeHtml(rec.recording_name)}')" style="color:#EF4444; border-color:rgba(239,68,68,0.4); font-size:0.75rem; padding:4px 8px;" title="Supprimer du coffre-fort">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                    ` : ''}
                </div>
            `;
            wrapper.appendChild(item);
        });
    } catch (e) {
        wrapper.innerHTML = '<small style="color:#EF4444;">Erreur lors de la récupération des enregistrements.</small>';
    }
}

// -------------------------------------------------------------
// 12. GESTION DU CHAT EN DIRECT & NOTES VOCALES
// -------------------------------------------------------------
function initChatSystem() {
    const btnSend = document.getElementById('btnSendChat');
    const msgInput = document.getElementById('chatMsgInput');
    const btnVoice = document.getElementById('btnRecordVoice');
    const btnCancelVoice = document.getElementById('btnCancelVoiceRec');

    if (btnSend && msgInput) {
        btnSend.addEventListener('click', () => {
            sendTextMessage(msgInput.value);
            msgInput.value = '';
        });
        msgInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendTextMessage(msgInput.value);
                msgInput.value = '';
            }
        });
    }

    if (btnVoice) {
        btnVoice.addEventListener('click', () => {
            if (!isVoiceRecording) {
                startVoiceNoteRecording();
            } else {
                stopAndSendVoiceNote();
            }
        });
    }

    if (btnCancelVoice) {
        btnCancelVoice.addEventListener('click', () => {
            cancelVoiceNoteRecording();
        });
    }

    // Bascule du Mode Q&R (Questions / Réponses au formateur)
    const btnQ = document.getElementById('btnToggleQuestionMode');
    if (btnQ) {
        btnQ.addEventListener('click', () => {
            const isQ = btnQ.classList.toggle('active');
            btnQ.style.background = isQ ? '#2563EB' : '';
            btnQ.style.color = isQ ? '#FFFFFF' : '#60A5FA';
            btnQ.innerHTML = isQ ? '<i class="fa-solid fa-check"></i> Mode Q&R Actif' : '<i class="fa-solid fa-circle-question"></i> Mode Q&R';
            const input = document.getElementById('chatMsgInput');
            if (input) {
                input.placeholder = isQ ? "Posez votre question prioritaire au formateur..." : "Écrire un message à la classe...";
                input.focus();
            }
            showToast(isQ ? "Mode Q&R activé. Votre message sera mis en avant comme Question." : "Mode Chat standard rétabli", "info");
        });
    }
}

// Filtrage Anti-Spam & Débit
let lastChatSentTime = 0;
let lastChatSentText = '';

async function sendTextMessage(text) {
    text = text.trim();
    if (!text) return;

    // 1. Filtrage anti-spam et limitation de débit (1 seconde min)
    const now = Date.now();
    if (now - lastChatSentTime < 1000) {
        showToast("Veuillez patienter un instant avant d'envoyer un nouveau message.", "info");
        return;
    }
    if (text === lastChatSentText && now - lastChatSentTime < 4000) {
        showToast("Message identique détecté (Protection Anti-Spam active).", "info");
        return;
    }
    lastChatSentTime = now;
    lastChatSentText = text;

    // 2. Détermination du destinataire et du type (Public / Privé / Q&R)
    const recipientSelect = document.getElementById('chatRecipientSelect');
    const recipient = recipientSelect ? recipientSelect.value : 'all';
    const isQ = document.getElementById('btnToggleQuestionMode')?.classList.contains('active') || text.startsWith('/q ') || text.startsWith('?');

    let msgType = 'text';
    let targetMessage = text;

    if (isQ) {
        msgType = 'question';
        if (targetMessage.startsWith('/q ')) targetMessage = targetMessage.substring(3).trim();
    } else if (recipient !== 'all') {
        msgType = 'private';
        targetMessage = `[Privé à ${recipient}] ${text}`;
    }

    postChatMessage(targetMessage, msgType);
}

// Convertit les liens textuels en liens cliquables sécurisés
function formatChatContent(raw) {
    if (!raw) return '';
    const escaped = escapeHtml(raw);
    const urlPattern = /(\b(https?:\/\/|www\.)\S+)/gi;
    return escaped.replace(urlPattern, (url) => {
        let href = url.startsWith('http') ? url : 'https://' + url;
        return `<a href="${href}" target="_blank" rel="noopener noreferrer" style="color:#60A5FA; text-decoration:underline; font-weight:600;"><i class="fa-solid fa-arrow-up-right-from-square" style="font-size:0.68rem;"></i> ${url}</a>`;
    });
}

async function postChatMessage(message, msgType = 'text') {
    try {
        const payload = {
            room_id: roomId,
            user_name: currentUserName,
            message: message,
            msg_type: msgType
        };

        const res = await fetch('api/classroom_chat.php?action=send_message', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success && data.message) {
            appendChatMessageToUI(data.message);
        }
    } catch (e) {
        appendChatMessageToUI({
            id: Date.now(),
            user_name: currentUserName,
            message: message,
            msg_type: msgType,
            created_at: new Date().toLocaleTimeString()
        });
    }
}

async function fetchNewMessages() {
    try {
        const res = await fetch(`api/classroom_chat.php?action=fetch_messages&room_id=${roomId}&last_id=${lastMessageId}`);
        const data = await res.json();
        if (data.success && Array.isArray(data.messages)) {
            data.messages.forEach(msg => {
                appendChatMessageToUI(msg);
                if (msg.id > lastMessageId) lastMessageId = msg.id;
            });
        }
    } catch (e) {}
}

function appendChatMessageToUI(msg) {
    const container = document.getElementById('chatMessagesContainer');
    if (!container) return;

    const existing = document.getElementById(`msg_${msg.id}`);
    if (existing) return;

    const div = document.createElement('div');
    div.className = 'chat-msg';
    div.id = `msg_${msg.id}`;

    const timeStr = msg.created_at ? msg.created_at.substring(11, 16) : new Date().toLocaleTimeString().substring(0, 5);

    if (msg.msg_type === 'reaction') {
        div.style.background = 'rgba(255, 255, 255, 0.04)';
        div.innerHTML = `
            <div class="chat-msg-header">
                <strong>${escapeHtml(msg.user_name)}</strong>
                <span>${timeStr}</span>
            </div>
            <div style="font-size:1.4rem;">${escapeHtml(msg.message)}</div>
        `;
    } else if (msg.msg_type === 'voice') {
        div.innerHTML = `
            <div class="chat-msg-header">
                <strong>${escapeHtml(msg.user_name)}</strong>
                <span>${timeStr}</span>
            </div>
            <div style="margin-top:4px;">
                <audio controls src="${escapeHtml(msg.audio_url)}" style="width:100%; height:32px;"></audio>
            </div>
        `;
    } else if (msg.msg_type === 'system') {
        div.style.background = 'rgba(245, 158, 11, 0.12)';
        div.innerHTML = `
            <div style="font-size:0.75rem; color:#F59E0B; font-weight:700;">
                <i class="fa-solid fa-circle-info"></i> ${escapeHtml(msg.message)}
            </div>
        `;
    } else if (msg.msg_type === 'question') {
        div.style.background = 'rgba(59, 130, 246, 0.12)';
        div.style.borderLeft = '3px solid #3B82F6';
        div.innerHTML = `
            <div class="chat-msg-header">
                <strong>${escapeHtml(msg.user_name)}</strong>
                <span style="background:rgba(59,130,246,0.25); color:#93C5FD; font-size:0.65rem; padding:1px 6px; border-radius:4px; font-weight:700;"><i class="fa-solid fa-circle-question"></i> Question Q&R</span>
                <span>${timeStr}</span>
            </div>
            <div style="color:#E0F2FE;">${formatChatContent(msg.message)}</div>
        `;
    } else if (msg.msg_type === 'private') {
        div.style.background = 'rgba(139, 92, 246, 0.12)';
        div.style.borderLeft = '3px solid #8B5CF6';
        div.innerHTML = `
            <div class="chat-msg-header">
                <strong>${escapeHtml(msg.user_name)}</strong>
                <span style="background:rgba(139,92,246,0.25); color:#C4B5FD; font-size:0.65rem; padding:1px 6px; border-radius:4px; font-weight:700;"><i class="fa-solid fa-lock"></i> Privé</span>
                <span>${timeStr}</span>
            </div>
            <div style="color:#EDE9FE;">${formatChatContent(msg.message)}</div>
        `;
    } else {
        div.innerHTML = `
            <div class="chat-msg-header">
                <strong>${escapeHtml(msg.user_name)}</strong>
                <span>${timeStr}</span>
            </div>
            <div>${formatChatContent(msg.message)}</div>
        `;
    }

    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}

// -------------------------------------------------------------
// 13. NOTES VOCALES (MediaRecorder)
// -------------------------------------------------------------
async function startVoiceNoteRecording() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        voiceAudioChunks = [];
        voiceMediaRecorder = new MediaRecorder(stream);

        voiceMediaRecorder.ondataavailable = (e) => {
            if (e.data.size > 0) voiceAudioChunks.push(e.data);
        };

        voiceMediaRecorder.onstop = async () => {
            const audioBlob = new Blob(voiceAudioChunks, { type: 'audio/webm' });
            sendVoiceBlob(audioBlob);
            stream.getTracks().forEach(t => t.stop());
        };

        voiceMediaRecorder.start();
        isVoiceRecording = true;

        const statusEl = document.getElementById('voiceRecordingStatus');
        const btnVoice = document.getElementById('btnRecordVoice');
        if (statusEl) statusEl.style.display = 'flex';
        if (btnVoice) btnVoice.style.color = '#EF4444';

        voiceRecordSeconds = 0;
        voiceRecordInterval = setInterval(() => {
            voiceRecordSeconds++;
            const s = String(voiceRecordSeconds).padStart(2, '0');
            const timer = document.getElementById('voiceRecordingTimer');
            if (timer) timer.textContent = `Enregistrement vocal : 00:${s}`;
        }, 1000);
    } catch (e) {
        showToast('Impossible d\'accéder au microphone : ' + e.message, 'info');
    }
}

function stopAndSendVoiceNote() {
    if (voiceMediaRecorder && voiceMediaRecorder.state !== 'inactive') {
        voiceMediaRecorder.stop();
    }
    resetVoiceNoteUI();
}

function cancelVoiceNoteRecording() {
    if (voiceMediaRecorder && voiceMediaRecorder.state !== 'inactive') {
        voiceMediaRecorder.ondataavailable = null;
        voiceMediaRecorder.stop();
    }
    resetVoiceNoteUI();
    showToast('Enregistrement vocal annulé.', 'info');
}

function resetVoiceNoteUI() {
    isVoiceRecording = false;
    clearInterval(voiceRecordInterval);
    const statusEl = document.getElementById('voiceRecordingStatus');
    const btnVoice = document.getElementById('btnRecordVoice');
    if (statusEl) statusEl.style.display = 'none';
    if (btnVoice) btnVoice.style.color = '';
}

async function sendVoiceBlob(blob) {
    const formData = new FormData();
    formData.append('action', 'send_voice');
    formData.append('room_id', roomId);
    formData.append('user_name', currentUserName);
    formData.append('voice_file', blob, `voice_${Date.now()}.webm`);

    try {
        const res = await fetch('api/classroom_chat.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success && data.message) {
            appendChatMessageToUI(data.message);
            showToast('Note vocale transmise à la classe', 'success');
        }
    } catch (e) {
        showToast('Erreur envoi note vocale', 'info');
    }
}

// -------------------------------------------------------------
// 14. GESTION DES PARTICIPANTS & MODÉRATION HÔTE (MODULE E)
// -------------------------------------------------------------
let pendingWaitingGuests = [];
let isChatLocked = false;
let isScreenShareLocked = false;

// API Modération Hôte générique
window.moderatePeer = async function(command, targetPeer = null, extraData = {}) {
    try {
        const payload = Object.assign({
            command: command,
            target_peer: targetPeer
        }, extraData);

        const res = await fetch(`api/classroom_signal.php?action=moderate&room_id=${roomId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        return data && data.success;
    } catch (e) {
        console.warn('Erreur modération:', e);
        return false;
    }
};

function getRoleBadgeInfo(role) {
    switch (role) {
        case 'host':
            return { label: 'Organisateur', color: '#D97706', bg: 'rgba(217,119,6,0.15)', border: 'rgba(217,119,6,0.4)' };
        case 'cohost':
            return { label: 'Coorganisateur', color: '#8B5CF6', bg: 'rgba(139,92,246,0.15)', border: 'rgba(139,92,246,0.4)' };
        case 'presentateur':
            return { label: 'Présentateur', color: '#06B6D4', bg: 'rgba(6,182,212,0.15)', border: 'rgba(6,182,212,0.4)' };
        case 'participant':
        default:
            return { label: 'Participant', color: '#94A3B8', bg: 'rgba(148,163,184,0.15)', border: 'rgba(148,163,184,0.3)' };
    }
}

function updateParticipantsRoster(peers) {
    const wrapper = document.getElementById('remoteParticipantsListWrapper');
    if (!wrapper) return;

    wrapper.innerHTML = '';
    const isLocalMod = (userRole === 'host' || userRole === 'cohost');

    peers.forEach(peer => {
        if (peer.peer_id === myPeerId) return;

        const initials = (peer.user_name || 'P').substring(0, 2).toUpperCase();
        const roleInfo = getRoleBadgeInfo(peer.user_role || 'participant');
        const isPeerPresentateur = (peer.user_role === 'presentateur');
        const isPeerCohost = (peer.user_role === 'cohost');

        const row = document.createElement('div');
        row.className = 'participant-row';
        row.id = `row_peer_${peer.peer_id}`;
        row.style.display = 'flex';
        row.style.alignItems = 'center';
        row.style.justifyContent = 'space-between';
        row.style.padding = '8px 10px';
        row.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
        row.style.gap = '8px';

        const micIconColor = peer.is_audio_muted ? '#EF4444' : '#10B981';
        const micIconClass = peer.is_audio_muted ? 'fa-microphone-slash' : 'fa-microphone';

        let modActionButtons = '';
        if (isLocalMod && peer.user_role !== 'host') {
            modActionButtons = `
                <div class="peer-mod-actions" style="display:inline-flex; align-items:center; gap:4px;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="moderatePeer('mute_peer', '${peer.peer_id}')" style="padding:2px 6px; font-size:0.65rem; color:#EF4444; border-color:rgba(239,68,68,0.3);" title="Couper le micro de ce participant">
                        <i class="fa-solid fa-microphone-slash"></i>
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="togglePeerPresenterRole('${peer.peer_id}', '${peer.user_role || 'participant'}')" style="padding:2px 6px; font-size:0.65rem; color:#06B6D4; border-color:rgba(6,182,212,0.3);" title="${isPeerPresentateur ? 'Révoquer le statut Présentateur' : 'Désigner comme Présentateur'}">
                        <i class="fa-solid ${isPeerPresentateur ? 'fa-user-check' : 'fa-chalkboard-user'}"></i>
                    </button>
                    ${userRole === 'host' ? `
                    <button type="button" class="btn btn-outline btn-sm" onclick="togglePeerCohostRole('${peer.peer_id}', '${peer.user_role || 'participant'}')" style="padding:2px 6px; font-size:0.65rem; color:#8B5CF6; border-color:rgba(139,92,246,0.3);" title="${isPeerCohost ? 'Rétrograder à Participant' : 'Nommer Coorganisateur'}">
                        <i class="fa-solid ${isPeerCohost ? 'fa-user-shield' : 'fa-shield'}"></i>
                    </button>
                    ` : ''}
                    <button type="button" class="btn btn-outline btn-sm" onclick="confirmKickPeer('${peer.peer_id}', '${escapeHtml(peer.user_name)}')" style="padding:2px 6px; font-size:0.65rem; color:#EF4444; border-color:rgba(239,68,68,0.3);" title="Retirer ce participant indésirable">
                        <i class="fa-solid fa-user-xmark"></i>
                    </button>
                </div>
            `;
        }

        row.innerHTML = `
            <div class="participant-info" style="display:flex; align-items:center; gap:8px; flex:1; min-width:0;">
                <div class="p-avatar" style="background:#2563EB; width:32px; height:32px; font-size:0.75rem; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; flex-shrink:0;">${initials}</div>
                <div style="min-width:0; overflow:hidden;">
                    <div style="font-size:0.82rem; font-weight:600; color:#FFFFFF; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        ${escapeHtml(peer.user_name)}
                    </div>
                    <span style="display:inline-block; font-size:0.65rem; padding:1px 6px; border-radius:4px; font-weight:600; color:${roleInfo.color}; background:${roleInfo.bg}; border:1px solid ${roleInfo.border}; margin-top:2px;">
                        ${roleInfo.label}
                    </span>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:4px; flex-shrink:0;">
                ${modActionButtons}
                <button type="button" class="p-mic-btn" style="background:transparent; border:none; padding:4px; cursor:pointer;" title="Statut microphone">
                    <i class="fa-solid ${micIconClass}" style="color:${micIconColor}; font-size:0.85rem;"></i>
                </button>
            </div>
        `;
        wrapper.appendChild(row);
    });

    // Mettre à jour la liste des destinataires pour les messages privés
    const recipientSelect = document.getElementById('chatRecipientSelect');
    if (recipientSelect) {
        const curVal = recipientSelect.value;
        recipientSelect.innerHTML = `
            <option value="all">🌐 Tout le monde (Public)</option>
            <option value="host">👑 Hôte / Formateur (Direct)</option>
        `;
        peers.forEach(p => {
            if (p.peer_id !== myPeerId) {
                const opt = document.createElement('option');
                opt.value = p.user_name;
                opt.textContent = `🔒 ${p.user_name} (Privé)`;
                recipientSelect.appendChild(opt);
            }
        });
        if (curVal) recipientSelect.value = curVal;
    }
}

// Actions Modérateur : Désigner Présentateur
window.togglePeerPresenterRole = async function(peerId, currentRole) {
    const nextRole = (currentRole === 'presentateur') ? 'participant' : 'presentateur';
    const ok = await moderatePeer('set_role', peerId, { role: nextRole });
    if (ok) {
        showToast(nextRole === 'presentateur' ? 'Participant désigné Présentateur avec succès !' : 'Rôle Présentateur révoqué.', 'success');
    }
};

// Actions Modérateur : Nommer Coorganisateur
window.togglePeerCohostRole = async function(peerId, currentRole) {
    const nextRole = (currentRole === 'cohost') ? 'participant' : 'cohost';
    const ok = await moderatePeer('set_role', peerId, { role: nextRole });
    if (ok) {
        showToast(nextRole === 'cohost' ? 'Participant promu Coorganisateur !' : 'Statut Coorganisateur retiré.', 'success');
    }
};

// Actions Modérateur : Retirer un participant indésirable
window.confirmKickPeer = async function(peerId, peerName) {
    if (confirm(`Êtes-vous sûr de vouloir retirer définitivement "${peerName}" de la visioconférence ?`)) {
        const ok = await moderatePeer('kick_peer', peerId);
        if (ok) {
            showToast(`${peerName} a été retiré de la salle.`, 'warning');
            removeRemotePeerCard(peerId);
        }
    }
};

// Modération Hôte : Couper tous les micros
const btnMuteAll = document.getElementById('btnMuteAllMembers');
if (btnMuteAll) {
    btnMuteAll.addEventListener('click', async () => {
        await moderatePeer('mute_all');
        showToast('Commande envoyée : tous les micros ont été coupés.', 'danger');
    });
}

// Modération Hôte : Verrouiller / Déverrouiller la salle
const btnLock = document.getElementById('btnToggleRoomLock');
if (btnLock) {
    btnLock.addEventListener('click', async () => {
        isRoomLocked = !isRoomLocked;
        await moderatePeer('toggle_lock', null, { is_locked: isRoomLocked ? 1 : 0 });
        const icon = document.getElementById('iconRoomLock');
        if (icon) icon.className = isRoomLocked ? 'fa-solid fa-lock-open' : 'fa-solid fa-lock';
        btnLock.innerHTML = isRoomLocked ? '<i class="fa-solid fa-lock-open"></i> Déverrouiller' : '<i class="fa-solid fa-lock"></i> Verrouiller';
        btnLock.style.color = isRoomLocked ? '#EF4444' : '#F59E0B';
        const badge = document.getElementById('roomLockBadge');
        if (badge) badge.style.display = isRoomLocked ? 'inline-flex' : 'none';
        showToast(isRoomLocked ? 'Salle verrouillée aux nouveaux participants' : 'Salle déverrouillée', 'info');
    });
}

// Modération Hôte : Autoriser / Interdire le partage d'écran
const btnScreenLock = document.getElementById('btnToggleScreenLock');
if (btnScreenLock) {
    btnScreenLock.addEventListener('click', async () => {
        isScreenShareLocked = !isScreenShareLocked;
        const allowScreen = !isScreenShareLocked;
        await moderatePeer('toggle_screen_perm', null, { allow_screen: allowScreen ? 1 : 0 });
        const icon = document.getElementById('iconScreenLock');
        if (icon) icon.className = isScreenShareLocked ? 'fa-solid fa-lock' : 'fa-solid fa-desktop';
        btnScreenLock.innerHTML = isScreenShareLocked 
            ? '<i class="fa-solid fa-lock"></i> Partage Restreint' 
            : '<i class="fa-solid fa-desktop"></i> Partage Libre';
        btnScreenLock.style.color = isScreenShareLocked ? '#F59E0B' : '#3B82F6';
        showToast(isScreenShareLocked ? "Partage d'écran restreint aux seuls Présentateurs et Hôtes." : "Partage d'écran ouvert à tous les participants.", 'info');
    });
}

// Modération Hôte : Contrôler les autorisations de chat
const btnChatLock = document.getElementById('btnToggleChatLock');
if (btnChatLock) {
    btnChatLock.addEventListener('click', async () => {
        isChatLocked = !isChatLocked;
        const allowChat = !isChatLocked;
        await moderatePeer('toggle_chat_perm', null, { allow_chat: allowChat ? 1 : 0 });
        const icon = document.getElementById('iconChatLock');
        if (icon) icon.className = isChatLocked ? 'fa-solid fa-comment-slash' : 'fa-solid fa-comments';
        btnChatLock.innerHTML = isChatLocked 
            ? '<i class="fa-solid fa-comment-slash"></i> Chat Suspendu' 
            : '<i class="fa-solid fa-comments"></i> Chat Libre';
        btnChatLock.style.color = isChatLocked ? '#EF4444' : '#10B981';
        showToast(isChatLocked ? "Le chat public a été suspendu pour les participants." : "Le chat public est de nouveau ouvert.", 'info');
    });
}

// -------------------------------------------------------------
// GESTION DE LA SALLE D'ATTENTE & ADMISSIONS (MODULE E)
// -------------------------------------------------------------
function handleIncomingWaitingGuest(guest) {
    pendingWaitingGuests.push(guest);
    updateWaitingRoomUI();

    // Afficher la bannière flottante d'admission rapide
    const banner = document.getElementById('hostAdmissionBanner');
    const bName = document.getElementById('bannerUserName');
    const bLoc = document.getElementById('bannerUserLoc');
    const bAvatar = document.getElementById('bannerAvatar');
    if (banner && bName && guest) {
        bName.textContent = guest.user_name || 'Nouvel Invité';
        if (bLoc) bLoc.textContent = (guest.location || "En attente d'approbation") + " • Salle d'attente";
        if (bAvatar) bAvatar.textContent = (guest.user_name || 'G').substring(0, 2).toUpperCase();
        banner.style.display = 'flex';
    }
}

function updateWaitingRoomUI() {
    const list = document.getElementById('waitingRoomListContainer');
    const badge = document.getElementById('waitingCountBadge');
    const btnAll = document.getElementById('btnAdmitAll');
    if (badge) badge.textContent = pendingWaitingGuests.length;
    if (btnAll) btnAll.style.display = pendingWaitingGuests.length > 0 ? 'inline-block' : 'none';

    if (!list) return;
    if (pendingWaitingGuests.length === 0) {
        list.innerHTML = '<small style="color:#64748B; font-size:0.75rem; font-style:italic;">Aucune demande en attente.</small>';
        const banner = document.getElementById('hostAdmissionBanner');
        if (banner) banner.style.display = 'none';
        return;
    }

    list.innerHTML = '';
    pendingWaitingGuests.forEach((guest, idx) => {
        const item = document.createElement('div');
        item.style.display = 'flex';
        item.style.alignItems = 'center';
        item.style.justifyContent = 'space-between';
        item.style.padding = '6px 8px';
        item.style.background = 'rgba(255,255,255,0.03)';
        item.style.borderRadius = '6px';
        item.style.fontSize = '0.75rem';

        item.innerHTML = `
            <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:140px;">
                <strong style="color:#FFF;">${escapeHtml(guest.user_name)}</strong>
            </div>
            <div style="display:flex; gap:4px;">
                <button type="button" class="btn btn-sm btn-gold" onclick="admitWaitingGuest(${idx})" style="padding:2px 6px; font-size:0.65rem;">
                    <i class="fa-solid fa-check"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline" onclick="rejectWaitingGuest(${idx})" style="padding:2px 6px; font-size:0.65rem; color:#EF4444; border-color:rgba(239,68,68,0.4);">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        `;
        list.appendChild(item);
    });
}

window.admitWaitingGuest = async function(index) {
    if (index >= 0 && index < pendingWaitingGuests.length) {
        const guest = pendingWaitingGuests[index];
        await moderatePeer('admit_guest', guest.peer_id || `sim_${Date.now()}`);
        showToast(`${guest.user_name} a été admis dans la visioconférence !`, 'success');
        pendingWaitingGuests.splice(index, 1);
        updateWaitingRoomUI();
    }
};

window.rejectWaitingGuest = async function(index) {
    if (index >= 0 && index < pendingWaitingGuests.length) {
        const guest = pendingWaitingGuests[index];
        await moderatePeer('reject_guest', guest.peer_id || `sim_${Date.now()}`);
        showToast(`Demande de ${guest.user_name} refusée.`, 'info');
        pendingWaitingGuests.splice(index, 1);
        updateWaitingRoomUI();
    }
};

// Boutons de la bannière flottante d'admission
const btnBannerAdmit = document.getElementById('btnBannerAdmit');
if (btnBannerAdmit) {
    btnBannerAdmit.addEventListener('click', () => {
        if (pendingWaitingGuests.length > 0) {
            admitWaitingGuest(0);
        }
    });
}

const btnBannerReject = document.getElementById('btnBannerReject');
if (btnBannerReject) {
    btnBannerReject.addEventListener('click', () => {
        if (pendingWaitingGuests.length > 0) {
            rejectWaitingGuest(0);
        }
    });
}

// Admettre tous les invités en attente
const btnAdmitAll = document.getElementById('btnAdmitAll');
if (btnAdmitAll) {
    btnAdmitAll.addEventListener('click', async () => {
        while (pendingWaitingGuests.length > 0) {
            await admitWaitingGuest(0);
        }
        showToast('Tous les invités en salle d\'attente ont été admis !', 'success');
    });
}

// Simuler une demande d'accès pour test / démonstration
const btnSimulate = document.getElementById('btnSimulateIncoming');
if (btnSimulate) {
    btnSimulate.addEventListener('click', () => {
        const demoNames = ['Frère Marc-Aurele', 'Sœur Marie-Esther', 'Diacre Samuel', 'Pr. Jean-Baptiste'];
        const randomName = demoNames[Math.floor(Math.random() * demoNames.length)];
        handleIncomingWaitingGuest({
            peer_id: `guest_${Date.now()}`,
            user_name: randomName,
            location: 'Abidjan, CI'
        });
        showToast(`Nouvelle demande d'accès : ${randomName}`, 'info');
    });
}

// -------------------------------------------------------------
// 15. SONDAGE EN DIRECT (Live Poll)
// -------------------------------------------------------------
function initPollSystem() {
    refreshPollStats();
}

window.voteClassroomPoll = async function(optionIndex) {
    try {
        const payload = {
            poll_id: 1,
            option_index: optionIndex,
            user_uid: userIdentifier
        };

        const res = await fetch(`api/classroom_chat.php?action=vote_poll&room_id=${roomId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success && Array.isArray(data.results)) {
            renderPollResults(data.results);
            showToast('Votre vote d\'intercession a été pris en compte !', 'success');
        }
    } catch (e) {}
};

async function refreshPollStats() {
    try {
        const res = await fetch(`api/classroom_chat.php?action=get_poll&room_id=${roomId}&poll_id=1`);
        const data = await res.json();
        if (data.success && Array.isArray(data.results)) {
            renderPollResults(data.results);
        }
    } catch (e) {}
}

function renderPollResults(results) {
    let total = 0;
    const counts = { 1: 0, 2: 0, 3: 0 };
    results.forEach(r => {
        const idx = parseInt(r.option_index);
        const cnt = parseInt(r.cnt);
        counts[idx] = cnt;
        total += cnt;
    });

    for (let i = 1; i <= 3; i++) {
        const pct = total > 0 ? Math.round((counts[i] / total) * 100) : 0;
        const pctEl = document.getElementById(`pollPct${i}`);
        const fillEl = document.getElementById(`pollFill${i}`);
        if (pctEl) pctEl.textContent = `${pct}%`;
        if (fillEl) fillEl.style.width = `${pct}%`;
    }
}

// -------------------------------------------------------------
// 16. EN-TÊTE : COPIE LIEN, MODE GRILLE & PLEIN ÉCRAN
// -------------------------------------------------------------
function initHeaderActions() {
    // Bouton Copier Invitation
    const btnCopy = document.getElementById('btnCopyInvite');
    if (btnCopy) {
        btnCopy.addEventListener('click', () => {
            const url = window.location.href.split('#')[0];
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    showToast('Lien de la visioconférence copié dans le presse-papiers !', 'success');
                }).catch(() => fallbackCopyText(url));
            } else {
                fallbackCopyText(url);
            }
        });
    }

    // Lever la main depuis le header
    const btnHeaderHand = document.getElementById('btnHeaderRaiseHand');
    if (btnHeaderHand) {
        btnHeaderHand.addEventListener('click', toggleRaiseHand);
    }

    // Basculer Vue Grille / Vue Intervenant
    const btnLayout = document.getElementById('btnToggleLayout');
    const videoGrid = document.getElementById('videoGrid');
    if (btnLayout && videoGrid) {
        btnLayout.addEventListener('click', () => {
            videoGrid.classList.toggle('grid-mode');
            const isGrid = videoGrid.classList.contains('grid-mode');
            btnLayout.classList.toggle('active', isGrid);
            btnLayout.innerHTML = isGrid ? '<i class="fa-solid fa-chalkboard-user"></i>' : '<i class="fa-solid fa-table-cells-large"></i>';
            showToast(isGrid ? 'Mode Grille Galerie activé' : 'Mode Intervenant Focus activé', 'info');
        });
    }

    // Plein écran
    const btnFs = document.getElementById('btnHeaderFullscreen');
    if (btnFs) {
        btnFs.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().then(() => {
                    btnFs.classList.add('active');
                    btnFs.innerHTML = '<i class="fa-solid fa-compress"></i>';
                }).catch(() => {});
            } else {
                document.exitFullscreen().then(() => {
                    btnFs.classList.remove('active');
                    btnFs.innerHTML = '<i class="fa-solid fa-expand"></i>';
                });
            }
        });
    }

    // Basculer Rôle (Test Permission)
    const btnRole = document.getElementById('btnToggleRole');
    if (btnRole) {
        btnRole.addEventListener('click', () => {
            userRole = (userRole === 'host') ? 'participant' : 'host';
            const lbl = document.getElementById('lblCurrentRole');
            if (lbl) lbl.textContent = userRole === 'host' ? 'Mode : Hôte / Modérateur' : 'Mode : Participant';
            const wSection = document.getElementById('hostWaitingRoomSection');
            if (wSection) wSection.style.display = (userRole === 'host') ? 'block' : 'none';
            const mBar = document.querySelector('.moderator-audio-bar');
            if (mBar) mBar.style.display = (userRole === 'host') ? 'flex' : 'none';
            showToast(`Bascule vers le rôle : ${userRole.toUpperCase()}`, 'info');
        });
    }
}

function fallbackCopyText(text) {
    const input = document.createElement('input');
    input.value = text;
    document.body.appendChild(input);
    input.select();
    document.execCommand('copy');
    document.body.removeChild(input);
    showToast('Lien de la visioconférence copié !', 'success');
}

// -------------------------------------------------------------
// 17. ONGLETS DE LA BARRE LATÉRALE
// -------------------------------------------------------------
function initSidebarTabs() {
    const tabChat = document.getElementById('tabBtnChat');
    const tabPart = document.getElementById('tabBtnParticipants');
    const tabPoll = document.getElementById('tabBtnPoll');
    const tabTranscript = document.getElementById('tabBtnTranscript');

    const paneChat = document.getElementById('chatPane');
    const panePart = document.getElementById('participantsPane');
    const panePoll = document.getElementById('pollPane');
    const paneTranscript = document.getElementById('transcriptPane');

    function resetTabs() {
        if (tabChat) tabChat.classList.remove('active');
        if (tabPart) tabPart.classList.remove('active');
        if (tabPoll) tabPoll.classList.remove('active');
        if (tabTranscript) tabTranscript.classList.remove('active');

        if (paneChat) paneChat.style.display = 'none';
        if (panePart) panePart.style.display = 'none';
        if (panePoll) panePoll.style.display = 'none';
        if (paneTranscript) paneTranscript.style.display = 'none';
    }

    if (tabChat) {
        tabChat.addEventListener('click', () => {
            resetTabs();
            tabChat.classList.add('active');
            if (paneChat) paneChat.style.display = 'flex';
        });
    }

    if (tabPart) {
        tabPart.addEventListener('click', () => {
            resetTabs();
            tabPart.classList.add('active');
            if (panePart) panePart.style.display = 'block';
        });
    }

    if (tabPoll) {
        tabPoll.addEventListener('click', () => {
            resetTabs();
            tabPoll.classList.add('active');
            if (panePoll) panePoll.style.display = 'block';
        });
    }

    if (tabTranscript) {
        tabTranscript.addEventListener('click', () => {
            resetTabs();
            tabTranscript.classList.add('active');
            if (paneTranscript) paneTranscript.style.display = 'flex';
            if (typeof loadSidebarTranscripts === 'function') {
                loadSidebarTranscripts();
            }
        });
    }
}

// -------------------------------------------------------------
// 18. MINUTEUR DE RÉUNION
// -------------------------------------------------------------
function startMeetingTimer() {
    if (meetingTimerInterval) return;
    meetingTimerInterval = setInterval(() => {
        meetingElapsedSeconds++;
        const hrs = Math.floor(meetingElapsedSeconds / 3600);
        const mins = Math.floor((meetingElapsedSeconds % 3600) / 60);
        const secs = meetingElapsedSeconds % 60;
        const timeStr = hrs > 0 
            ? `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
            : `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        const el = document.getElementById('timerVal');
        if (el) el.textContent = timeStr;
    }, 1000);
}

// -------------------------------------------------------------
// 19. PARAMÈTRES AUDIO & VIDÉO (MODALE)
// -------------------------------------------------------------
async function populateDeviceDropdowns() {
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        const micSelect = document.getElementById('selectMicDevice');
        const camSelect = document.getElementById('selectCamDevice');
        const lobbyMicSelect = document.getElementById('lobbyMicSelect');
        const lobbyCamSelect = document.getElementById('lobbyCamSelect');

        const speakerSelect = document.getElementById('selectAudioOutputDevice');
        if (micSelect) micSelect.innerHTML = '';
        if (camSelect) camSelect.innerHTML = '';
        if (speakerSelect) speakerSelect.innerHTML = '';
        if (lobbyMicSelect) lobbyMicSelect.innerHTML = '';
        if (lobbyCamSelect) lobbyCamSelect.innerHTML = '';

        let micIndex = 1, camIndex = 1, spkIndex = 1;
        devices.forEach(device => {
            const opt = document.createElement('option');
            opt.value = device.deviceId;

            if (device.kind === 'audioinput') {
                opt.text = device.label || `Microphone ${micIndex++}`;
                if (micSelect) micSelect.appendChild(opt.cloneNode(true));
                if (lobbyMicSelect) lobbyMicSelect.appendChild(opt);
            } else if (device.kind === 'videoinput') {
                opt.text = device.label || `Caméra ${camIndex++}`;
                if (camSelect) camSelect.appendChild(opt.cloneNode(true));
                if (lobbyCamSelect) lobbyCamSelect.appendChild(opt);
            } else if (device.kind === 'audiooutput') {
                opt.text = device.label || `Haut-parleur ${spkIndex++}`;
                if (speakerSelect) speakerSelect.appendChild(opt);
            }
        });

        if (speakerSelect) {
            speakerSelect.onchange = () => {
                const sinkId = speakerSelect.value;
                document.querySelectorAll('video, audio').forEach(el => {
                    if (typeof el.setSinkId === 'function') {
                        el.setSinkId(sinkId).catch(() => {});
                    }
                });
                showToast('Sortie audio mise à jour.', 'info');
            };
        }
    } catch (e) {}
}

// Bascule du mode Audio Uniquement (Basse consommation / réseau faible)
let isAudioOnlyMode = false;
window.toggleAudioOnlyMode = function(enabled = null) {
    isAudioOnlyMode = (enabled !== null) ? !!enabled : !isAudioOnlyMode;

    const chk = document.getElementById('chkAudioOnlyMode');
    if (chk) chk.checked = isAudioOnlyMode;

    const btnHdr = document.getElementById('btnToggleAudioOnly');
    if (btnHdr) {
        btnHdr.classList.toggle('active', isAudioOnlyMode);
        const lbl = document.getElementById('lblAudioOnly');
        if (lbl) lbl.textContent = isAudioOnlyMode ? 'Audio Seul (Actif)' : 'Audio Seul';
    }

    // Couper la caméra locale pour économiser le flux montant
    if (isAudioOnlyMode && !isVideoStopped) {
        const btnCam = document.getElementById('btnToggleCam');
        if (btnCam) btnCam.click();
    }

    // Cacher et suspendre les flux vidéo distants pour économiser le flux descendant
    document.querySelectorAll('.remote-peer-video').forEach(vid => {
        if (isAudioOnlyMode) {
            vid.style.visibility = 'hidden';
            vid.pause();
        } else {
            vid.style.visibility = 'visible';
            vid.play().catch(() => {});
        }
    });

    document.querySelectorAll('.video-placeholder').forEach(ph => {
        if (isAudioOnlyMode) ph.style.display = 'flex';
    });

    showToast(isAudioOnlyMode ? 'Mode Audio Uniquement activé (Économie 95% bande passante)' : 'Mode Vidéo HD rétabli', isAudioOnlyMode ? 'warning' : 'success');
};

window.openAvSettings = function() {
    const modal = document.getElementById('avSettingsModal');
    if (modal) {
        modal.style.display = 'flex';
        populateDeviceDropdowns();
    }
};

window.closeAvSettings = function() {
    const modal = document.getElementById('avSettingsModal');
    if (modal) modal.style.display = 'none';
};

const btnTestSound = document.getElementById('btnTestSound');
if (btnTestSound) {
    btnTestSound.addEventListener('click', () => {
        playToneChime(523.25, 0.4);
        setTimeout(() => playToneChime(659.25, 0.4), 180);
        setTimeout(() => playToneChime(783.99, 0.5), 360);
        showToast('Son de test diffusé dans vos haut-parleurs.', 'success');
    });
}

// -------------------------------------------------------------
// 20. PUSH-TO-TALK (BARRE ESPACE)
// -------------------------------------------------------------
function initPushToTalk() {
    let isSpacePttPressed = false;
    let wasMutedBeforePtt = true;

    window.addEventListener('keydown', (e) => {
        const active = document.activeElement;
        if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.isContentEditable)) return;
        if (e.code === 'Space' && !isSpacePttPressed) {
            e.preventDefault();
            isSpacePttPressed = true;
            wasMutedBeforePtt = isAudioMuted;
            if (isAudioMuted) {
                window.toggleLocalMic(false, true);
            }
            const pttBadge = document.getElementById('localPttBadge');
            if (pttBadge) pttBadge.style.display = 'block';
        }
    });

    window.addEventListener('keyup', (e) => {
        if (e.code === 'Space' && isSpacePttPressed) {
            e.preventDefault();
            isSpacePttPressed = false;
            const pttBadge = document.getElementById('localPttBadge');
            if (pttBadge) pttBadge.style.display = 'none';
            if (wasMutedBeforePtt) {
                window.toggleLocalMic(true, true);
            }
        }
    });
}

// -------------------------------------------------------------
// 21. QUITTER OU TERMINER LA CONFÉRENCE
// -------------------------------------------------------------
function handleLeaveMeeting() {
    if (userRole === 'host') {
        const choice = confirm("Option Organisateur / Hôte :\n\n- Cliquez sur 'OK' pour TERMINER la réunion pour TOUT LE MONDE.\n- Cliquez sur 'Annuler' pour quitter personnellement sans fermer la salle.");
        if (choice) {
            sendSignal('broadcast', 'room_ended', { by: currentUserName });
            fetch('api/classroom_meetings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'end_meeting', meeting_code: roomId })
            }).catch(() => {});
            cleanupAndRedirect('formation.php');
            return;
        }
    }

    if (confirm('Voulez-vous vraiment quitter la visioconférence OSHDI ?')) {
        cleanupAndRedirect('formation.php');
    }
}

function cleanupAndRedirect(targetUrl) {
    try {
        const leavePayload = JSON.stringify({ meeting_code: roomId, peer_id: myPeerId });
        if (navigator.sendBeacon) {
            navigator.sendBeacon('api/classroom_attendance.php?action=record_leave', new Blob([leavePayload], { type: 'application/json' }));
        } else {
            fetch('api/classroom_attendance.php?action=record_leave', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: leavePayload,
                keepalive: true
            }).catch(() => {});
        }
    } catch(e) {}

    fetch(`api/classroom_signal.php?action=leave&room_id=${roomId}&peer_id=${myPeerId}&user_name=${encodeURIComponent(currentUserName)}`).catch(() => {});

    if (localStream) {
        localStream.getTracks().forEach(t => t.stop());
    }

    for (const pid in peerConnections) {
        try { peerConnections[pid].close(); } catch (e) {}
    }

    if (audioContext && audioContext.state !== 'closed') {
        try { audioContext.close(); } catch (e) {}
    }

    window.location.href = targetUrl;
}

window.addEventListener('beforeunload', () => {
    try {
        const leavePayload = JSON.stringify({ meeting_code: roomId, peer_id: myPeerId });
        if (navigator.sendBeacon) {
            navigator.sendBeacon('api/classroom_attendance.php?action=record_leave', new Blob([leavePayload], { type: 'application/json' }));
        }
    } catch(e) {}
});

// -------------------------------------------------------------
// 22. FORMATEUR MODÉRATEUR INITIAL (Forme d'onde)
// -------------------------------------------------------------
function initTeacherWaveform() {
    const mic = classroomMics.teacher;
    const canvas = document.getElementById(mic.canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    function renderTeacherWave() {
        requestAnimationFrame(renderTeacherWave);
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (mic.isMuted) {
            ctx.fillStyle = 'rgba(239, 68, 68, 0.4)';
            ctx.fillRect(0, canvas.height - 3, canvas.width, 2);
            return;
        }

        const bars = 8, w = 6, gap = 4;
        for (let i = 0; i < bars; i++) {
            const h = Math.random() * (canvas.height - 6) + 4;
            ctx.fillStyle = mic.color;
            ctx.fillRect(i * (w + gap), canvas.height - h, w, h);
        }
    }
    renderTeacherWave();
}

window.toggleTeacherMic = function() {
    const mic = classroomMics.teacher;
    mic.isMuted = !mic.isMuted;
    const iconCard = document.getElementById(mic.iconCardId);
    const iconRow = document.getElementById(mic.iconRowId);
    if (iconCard) {
        iconCard.className = mic.isMuted ? 'fa-solid fa-microphone-slash' : 'fa-solid fa-microphone';
        iconCard.style.color = mic.isMuted ? '#EF4444' : '#10B981';
    }
    if (iconRow) {
        iconRow.className = mic.isMuted ? 'fa-solid fa-microphone-slash' : 'fa-solid fa-microphone';
        iconRow.style.color = mic.isMuted ? '#EF4444' : '#10B981';
    }
    showToast(mic.isMuted ? 'Microphone du formateur coupé' : 'Microphone du formateur activé', 'info');
};

// -------------------------------------------------------------
// 23. UTILITAIRES : CARILLON & TOASTS
// -------------------------------------------------------------
function playToneChime(frequency, duration = 0.3, type = 'sine') {
    try {
        const ctx = audioContext || new (window.AudioContext || window.webkitAudioContext)();
        if (ctx.state === 'suspended') ctx.resume();

        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = type;
        osc.frequency.setValueAtTime(frequency, ctx.currentTime);

        gain.gain.setValueAtTime(0.12, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);

        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + duration);
    } catch (e) {}
}

function showToast(message, type = 'info') {
    const existing = document.getElementById('oshdiToast');
    if (existing && existing.parentNode) existing.parentNode.removeChild(existing);

    const toast = document.createElement('div');
    toast.id = 'oshdiToast';
    toast.style.cssText = `
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: ${type === 'success' ? '#059669' : (type === 'danger' ? '#DC2626' : '#1E293B')};
        color: #FFFFFF;
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        z-index: 99999;
        display: flex;
        align-items: center;
        gap: 8px;
        animation: fadeInToast 0.3s ease;
    `;
    const icon = type === 'success' ? 'fa-circle-check' : (type === 'danger' ? 'fa-triangle-exclamation' : 'fa-circle-info');
    toast.innerHTML = `<i class="fa-solid ${icon}"></i> <span>${escapeHtml(message)}</span>`;
    document.body.appendChild(toast);

    setTimeout(() => {
        if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 3500);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

window.toggleCardFullscreen = function(cardId) {
    const card = document.getElementById(cardId);
    if (!card) return;
    if (!document.fullscreenElement) {
        card.requestFullscreen().catch(() => {});
    } else {
        document.exitFullscreen();
    }
};

// -------------------------------------------------------------
// 24. TABLEAU DE BORD DES RÉUNIONS (CRÉATION, GESTION, HISTORIQUE)
// -------------------------------------------------------------
function initMeetingDashboard() {
    const btnOpen = document.getElementById('btnOpenDashboard');
    const btnLobbyOpen = document.getElementById('btnLobbyOpenDashboard');
    const btnCreate = document.getElementById('btnCreateMeetingSubmit');
    const btnJoin = document.getElementById('btnJoinMeetingSubmit');
    const btnRefresh = document.getElementById('btnRefreshRoomsList');
    const joinInput = document.getElementById('joinMeetingCodeInput');

    if (btnOpen) {
        btnOpen.addEventListener('click', openMeetingDashboard);
    }
    if (btnLobbyOpen) {
        btnLobbyOpen.addEventListener('click', openMeetingDashboard);
    }

    if (btnCreate) {
        btnCreate.addEventListener('click', createNewMeeting);
    }

    if (btnJoin && joinInput) {
        btnJoin.addEventListener('click', joinMeetingFromInput);
        joinInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') joinMeetingFromInput();
        });
    }

    if (btnRefresh) {
        btnRefresh.addEventListener('click', loadDashboardRooms);
    }
}

// -------------------------------------------------------------
// 24. MODULE F — PLANIFICATION, CALENDRIER, INVITATIONS & RSVP
// -------------------------------------------------------------
let currentDashboardStatusFilter = 'all';
let currentLoadedRooms = [];

window.switchDashboardView = function(view) {
    const vAgenda = document.getElementById('viewDashAgenda');
    const vPlan = document.getElementById('viewDashPlan');
    const vInvites = document.getElementById('viewDashInvites');

    const tAgenda = document.getElementById('tabDashAgenda');
    const tPlan = document.getElementById('tabDashPlan');
    const tInvites = document.getElementById('tabDashInvites');

    [tAgenda, tPlan, tInvites].forEach(b => {
        if (b) {
            b.classList.remove('active');
            b.style.background = 'transparent';
            b.style.color = '#94A3B8';
            b.style.borderColor = 'rgba(255,255,255,0.1)';
        }
    });

    if (view === 'agenda') {
        if (vAgenda) vAgenda.style.display = 'block';
        if (vPlan) vPlan.style.display = 'none';
        if (vInvites) vInvites.style.display = 'none';
        if (tAgenda) {
            tAgenda.classList.add('active');
            tAgenda.style.background = 'rgba(245,158,11,0.2)';
            tAgenda.style.color = '#F59E0B';
            tAgenda.style.borderColor = 'rgba(245,158,11,0.4)';
        }
        loadDashboardRooms();
    } else if (view === 'plan') {
        if (vAgenda) vAgenda.style.display = 'none';
        if (vPlan) vPlan.style.display = 'block';
        if (vInvites) vInvites.style.display = 'none';
        if (tPlan) {
            tPlan.classList.add('active');
            tPlan.style.background = 'rgba(245,158,11,0.2)';
            tPlan.style.color = '#F59E0B';
            tPlan.style.borderColor = 'rgba(245,158,11,0.4)';
        }
    } else if (view === 'invites') {
        if (vAgenda) vAgenda.style.display = 'none';
        if (vPlan) vPlan.style.display = 'none';
        if (vInvites) vInvites.style.display = 'block';
        if (tInvites) {
            tInvites.classList.add('active');
            tInvites.style.background = 'rgba(245,158,11,0.2)';
            tInvites.style.color = '#F59E0B';
            tInvites.style.borderColor = 'rgba(245,158,11,0.4)';
        }
        loadInviteSelectMeetings();
        loadMeetingInvitationsList();
    }
};

window.filterDashboardRooms = function(status) {
    currentDashboardStatusFilter = status;
    document.querySelectorAll('.filter-status-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    if (event && event.currentTarget) {
        event.currentTarget.classList.add('active');
    }
    renderDashboardRoomsList(currentLoadedRooms);
};

window.openMeetingDashboard = function() {
    const modal = document.getElementById('meetingDashboardModal');
    if (!modal) return;
    modal.style.display = 'flex';

    // Mettre à jour les indicateurs live
    const curCode = document.getElementById('dashCurrentRoomCode');
    const curCount = document.getElementById('dashConnectedCount');
    const curDur = document.getElementById('dashDurationVal');
    const liveTimer = document.getElementById('timerVal');
    const liveCount = document.getElementById('connectedCountBadge');

    if (curCode) curCode.textContent = roomId;
    if (curCount && liveCount) curCount.textContent = liveCount.textContent;
    if (curDur && liveTimer) curDur.textContent = liveTimer.textContent;

    // Détection du fuseau horaire du participant
    detectUserTimezone();
    loadDashboardRooms();
};

window.closeMeetingDashboard = function() {
    const modal = document.getElementById('meetingDashboardModal');
    if (modal) modal.style.display = 'none';
};

function detectUserTimezone() {
    try {
        const detected = Intl.DateTimeFormat().resolvedOptions().timeZone || 'Africa/Abidjan';
        const now = new Date();
        const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        const lbl = document.getElementById('lblUserDetectedTimezone');
        if (lbl) {
            lbl.innerHTML = `<strong>${detected}</strong> &bull; Heure locale : <strong>${timeStr}</strong>`;
        }

        const selNew = document.getElementById('newMeetingTimezone');
        if (selNew && Array.from(selNew.options).some(o => o.value === detected)) {
            selNew.value = detected;
        }

        const selDisp = document.getElementById('dashDisplayTimezone');
        if (selDisp && Array.from(selDisp.options).some(o => o.value === detected)) {
            selDisp.value = detected;
        }
    } catch (e) {}
}

async function createNewMeeting() {
    const titleInput = document.getElementById('newMeetingTitle');
    const descInput  = document.getElementById('newMeetingDesc');
    const codeInput  = document.getElementById('newMeetingCode');
    const pwdInput   = document.getElementById('newMeetingPassword');
    const schedInput = document.getElementById('newMeetingScheduled');
    const schedEndIn = document.getElementById('newMeetingScheduledEnd');
    const recInput   = document.getElementById('newMeetingRecurrence');
    const tzInput    = document.getElementById('newMeetingTimezone');
    const remInput   = document.getElementById('newMeetingReminder');
    const waitInput  = document.getElementById('newMeetingWaitingRoom');

    const title = titleInput ? titleInput.value.trim() : '';
    const description = descInput ? descInput.value.trim() : '';
    const customCode = codeInput ? codeInput.value.trim() : '';
    const password = pwdInput ? pwdInput.value.trim() : '';
    const scheduled = schedInput ? schedInput.value : '';
    const scheduledEnd = schedEndIn ? schedEndIn.value : '';
    const recurrence = recInput ? recInput.value : 'none';
    const timezone = tzInput ? tzInput.value : 'Africa/Abidjan';
    const reminderMinutes = remInput ? remInput.value : 15;
    const requireWaiting = waitInput ? (waitInput.checked ? 1 : 0) : 1;

    try {
        const payload = {
            title: title || 'Nouvelle Visioconférence OSHDI',
            description: description,
            meeting_code: customCode,
            custom_code: customCode,
            password: password,
            scheduled_start: scheduled || null,
            scheduled_end: scheduledEnd || null,
            recurrence: recurrence,
            timezone: timezone,
            reminder_minutes: reminderMinutes,
            require_waiting_room: requireWaiting,
            host_name: currentUserName
        };

        const res = await fetch('api/classroom_meetings.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        const code = data.meeting_code || data.room_id || customCode;
        if (data.success && code) {
            showToast(`Réunion ${code} enregistrée avec succès !`, 'success');
            // Réinitialiser les champs
            if (titleInput) titleInput.value = '';
            if (descInput) descInput.value = '';
            // Basculer vers l'agenda
            switchDashboardView('agenda');
        } else {
            showToast('Erreur création de salle : ' + (data.error || 'Erreur inconnue'), 'danger');
        }
    } catch (e) {
        showToast('Erreur de communication avec le serveur', 'danger');
    }
}

function joinMeetingFromInput() {
    const input = document.getElementById('joinMeetingCodeInput');
    if (!input) return;
    let val = input.value.trim();
    if (!val) {
        showToast('Veuillez saisir un code de réunion ou un lien.', 'info');
        return;
    }

    if (val.includes('room=')) {
        try {
            const urlObj = new URL(val, window.location.origin);
            const r = urlObj.searchParams.get('room');
            if (r) val = r;
        } catch (e) {}
    }

    val = val.replace(/[^a-zA-Z0-9_\-]/g, '');
    if (val) {
        showToast(`Connexion à la salle ${val}...`, 'success');
        setTimeout(() => {
            window.location.href = `classroom.php?room=${encodeURIComponent(val)}&name=${encodeURIComponent(currentUserName)}`;
        }, 500);
    }
}

async function loadDashboardRooms() {
    const container = document.getElementById('dashboardRoomsListContainer');
    if (!container) return;

    container.innerHTML = '<small style="color:#64748B;"><i class="fa-solid fa-spinner fa-spin"></i> Chargement des sessions...</small>';

    try {
        const res = await fetch('api/classroom_meetings.php?action=list&limit=50');
        const data = await res.json();
        if (data.success && Array.isArray(data.rooms)) {
            currentLoadedRooms = data.rooms;
            renderDashboardRoomsList(currentLoadedRooms);
        } else {
            container.innerHTML = '<small style="color:#64748B;">Aucune réunion enregistrée pour le moment.</small>';
        }
    } catch (e) {
        container.innerHTML = '<small style="color:#EF4444;">Impossible de charger la liste des réunions.</small>';
    }
}

function renderDashboardRoomsList(rooms) {
    const container = document.getElementById('dashboardRoomsListContainer');
    if (!container) return;

    const displayTz = document.getElementById('dashDisplayTimezone')?.value || 'Africa/Abidjan';

    let filtered = rooms;
    if (currentDashboardStatusFilter === 'scheduled') {
        filtered = rooms.filter(r => r.status === 'scheduled');
    } else if (currentDashboardStatusFilter === 'active') {
        filtered = rooms.filter(r => r.status === 'active');
    } else if (currentDashboardStatusFilter === 'ended') {
        filtered = rooms.filter(r => r.status === 'ended' || r.status === 'cancelled');
    }

    if (filtered.length === 0) {
        container.innerHTML = '<small style="color:#64748B; font-style:italic;">Aucune réunion correspondant à ce filtre.</small>';
        return;
    }

    container.innerHTML = '';
    filtered.forEach(r => {
        const isCurrent = (r.meeting_code === roomId || r.room_id === roomId);
        const code = r.meeting_code || r.room_id;
        const activeCount = parseInt(r.active_peers_count || 0);

        // Date formatée selon fuseau sélectionné
        let formattedDate = 'En direct';
        if (r.scheduled_start) {
            try {
                const dt = new Date(r.scheduled_start);
                formattedDate = new Intl.DateTimeFormat('fr-FR', {
                    timeZone: displayTz,
                    weekday: 'short',
                    day: 'numeric',
                    month: 'short',
                    hour: '2-digit',
                    minute: '2-digit'
                }).format(dt);
            } catch (e) {
                formattedDate = r.scheduled_start;
            }
        }

        // Badge Récurrence
        let recurrenceBadge = '';
        if (r.recurrence && r.recurrence !== 'none') {
            const recLabels = { daily: 'Quotidienne', weekly: 'Hebdomadaire', monthly: 'Mensuelle' };
            recurrenceBadge = `<span style="background:rgba(245,158,11,0.15); color:#F59E0B; border:1px solid rgba(245,158,11,0.3); font-size:0.65rem; padding:1px 6px; border-radius:4px; margin-left:4px;"><i class="fa-solid fa-repeat"></i> ${recLabels[r.recurrence] || r.recurrence}</span>`;
        }

        // Badge Statut
        let statusBadge = '<span style="background:rgba(16,185,129,0.15); color:#10B981; font-size:0.65rem; padding:1px 6px; border-radius:4px;"><i class="fa-solid fa-circle-dot"></i> En direct</span>';
        if (r.status === 'scheduled') {
            statusBadge = '<span style="background:rgba(59,130,246,0.15); color:#60A5FA; font-size:0.65rem; padding:1px 6px; border-radius:4px;"><i class="fa-solid fa-calendar"></i> Programmée</span>';
        } else if (r.status === 'ended') {
            statusBadge = '<span style="background:rgba(100,116,139,0.15); color:#94A3B8; font-size:0.65rem; padding:1px 6px; border-radius:4px;"><i class="fa-solid fa-clock-rotate-left"></i> Terminée</span>';
        } else if (r.status === 'cancelled') {
            statusBadge = '<span style="background:rgba(239,68,68,0.15); color:#EF4444; font-size:0.65rem; padding:1px 6px; border-radius:4px;"><i class="fa-solid fa-ban"></i> Annulée</span>';
        }

        // Liens d'export calendrier
        const googleCalUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(r.title || 'Visioconférence OSHDI')}&dates=${encodeURIComponent(formatUtcCompact(r.scheduled_start))}/${encodeURIComponent(formatUtcCompact(r.scheduled_end || r.scheduled_start))}&details=${encodeURIComponent(r.description || 'Visioconférence pastorale et formation OSHDI VisioPro.')}&location=${encodeURIComponent('En ligne - OSHDI VisioPro')}`;
        const outlookCalUrl = `https://outlook.live.com/calendar/0/deeplink/compose?subject=${encodeURIComponent(r.title || 'Visioconférence OSHDI')}&startdt=${encodeURIComponent(r.scheduled_start || new Date().toISOString())}&body=${encodeURIComponent(r.description || 'Visioconférence pastorale et formation OSHDI VisioPro.')}`;
        const icsDownloadUrl = `api/classroom_meetings.php?action=calendar_export&meeting_code=${encodeURIComponent(code)}&format=ics`;

        const card = document.createElement('div');
        card.className = 'room-item-row';
        card.style.background = isCurrent ? 'rgba(16,185,129,0.06)' : 'rgba(255,255,255,0.03)';
        card.style.border = isCurrent ? '1px solid rgba(16,185,129,0.3)' : '1px solid rgba(255,255,255,0.07)';
        card.style.borderRadius = '8px';
        card.style.padding = '12px 14px';
        card.style.display = 'flex';
        card.style.flexDirection = 'column';
        card.style.gap = '8px';

        card.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px; flex-wrap:wrap;">
                <div style="flex:1; min-width:220px;">
                    <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:4px;">
                        ${statusBadge}
                        ${recurrenceBadge}
                        ${isCurrent ? '<span style="background:rgba(16,185,129,0.2); color:#10B981; font-size:0.65rem; padding:1px 6px; border-radius:4px; font-weight:700;">SALLE ACTUELLE</span>' : ''}
                    </div>
                    <strong style="color:#FFFFFF; font-size:0.92rem; display:block; margin-bottom:4px;">
                        ${escapeHtml(r.title || 'Visioconférence OSHDI')}
                    </strong>
                    <div style="color:#94A3B8; font-size:0.75rem; display:flex; flex-wrap:wrap; gap:8px;">
                        <span><i class="fa-regular fa-clock" style="color:#F59E0B;"></i> ${formattedDate}</span>
                        <span><i class="fa-solid fa-hashtag" style="color:#3B82F6;"></i> <code style="color:#F59E0B;">${escapeHtml(code)}</code></span>
                        ${r.host_name ? `<span><i class="fa-solid fa-user-tie"></i> ${escapeHtml(r.host_name)}</span>` : ''}
                        <span><i class="fa-solid fa-users" style="color:#10B981;"></i> ${activeCount} connecté(s)</span>
                    </div>
                    ${r.description ? `<p style="color:#64748B; font-size:0.75rem; margin:6px 0 0; line-height:1.4;">${escapeHtml(r.description)}</p>` : ''}
                </div>
                
                <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
                    ${!isCurrent && r.status !== 'ended' && r.status !== 'cancelled' ? `
                        <a href="classroom.php?room=${encodeURIComponent(code)}&name=${encodeURIComponent(currentUserName)}" class="btn btn-gold btn-sm" style="font-size:0.72rem; padding:4px 10px;">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i> Rejoindre
                        </a>
                    ` : ''}
                    ${isCurrent ? `
                        <span style="color:#10B981; font-size:0.75rem; font-weight:700;"><i class="fa-solid fa-check"></i> En cours</span>
                    ` : ''}
                    <button type="button" class="btn btn-outline btn-sm" onclick="openEditMeetingModal('${escapeHtml(code)}')" style="font-size:0.72rem; padding:4px 8px;" title="Modifier les paramètres">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    ${r.status !== 'cancelled' ? `
                    <button type="button" class="btn btn-outline btn-sm" onclick="cancelMeetingPrompt('${escapeHtml(code)}')" style="font-size:0.72rem; padding:4px 8px; color:#EF4444; border-color:rgba(239,68,68,0.3);" title="Annuler cette réunion">
                        <i class="fa-solid fa-ban"></i>
                    </button>
                    ` : ''}
                </div>
            </div>

            <!-- Intégrations Calendrier (Google, Outlook, iCal) -->
            <div style="display:flex; align-items:center; gap:6px; border-top:1px solid rgba(255,255,255,0.05); padding-top:6px; flex-wrap:wrap;">
                <span style="font-size:0.68rem; color:#64748B;">Ajouter au calendrier :</span>
                <a href="${googleCalUrl}" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="font-size:0.65rem; padding:2px 6px; color:#EA4335; border-color:rgba(234,67,53,0.3);" title="Synchroniser sur Google Calendar">
                    <i class="fa-brands fa-google"></i> Google Calendar
                </a>
                <a href="${outlookCalUrl}" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="font-size:0.65rem; padding:2px 6px; color:#0078D4; border-color:rgba(0,120,212,0.3);" title="Synchroniser sur Outlook">
                    <i class="fa-brands fa-microsoft"></i> Outlook
                </a>
                <a href="${icsDownloadUrl}" download class="btn btn-outline btn-sm" style="font-size:0.65rem; padding:2px 6px; color:#10B981; border-color:rgba(16,185,129,0.3);" title="Télécharger le fichier .ICS pour Apple Calendar ou mobile">
                    <i class="fa-solid fa-calendar-arrow-down"></i> Fichier .ICS (iCal)
                </a>
            </div>
        `;
        container.appendChild(card);
    });
}

function formatUtcCompact(dateStr) {
    if (!dateStr) return new Date().toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
    try {
        const d = new Date(dateStr);
        return d.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
    } catch (e) {
        return new Date().toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
    }
}

// -------------------------------------------------------------
// MODIFICATION & ANNULATION D'UNE RÉUNION (MODULE F)
// -------------------------------------------------------------
window.openEditMeetingModal = async function(meetingCode) {
    try {
        const res = await fetch(`api/classroom_meetings.php?action=details&code=${encodeURIComponent(meetingCode)}`);
        const data = await res.json();
        if (data.success && data.meeting) {
            const m = data.meeting;
            document.getElementById('editMeetingCode').value = m.meeting_code;
            document.getElementById('editMeetingTitle').value = m.title || '';
            document.getElementById('editMeetingDesc').value = m.description || '';
            if (m.scheduled_start) {
                document.getElementById('editMeetingScheduled').value = m.scheduled_start.replace(' ', 'T').substring(0, 16);
            }
            if (m.recurrence) document.getElementById('editMeetingRecurrence').value = m.recurrence;
            if (m.timezone) document.getElementById('editMeetingTimezone').value = m.timezone;
            if (m.reminder_minutes) document.getElementById('editMeetingReminder').value = m.reminder_minutes;
            document.getElementById('editMeetingWaitingRoom').checked = (parseInt(m.require_waiting_room) === 1);

            document.getElementById('editMeetingModal').style.display = 'flex';
        }
    } catch (e) {
        showToast('Erreur chargement détails réunion', 'danger');
    }
};

window.closeEditMeetingModal = function() {
    const m = document.getElementById('editMeetingModal');
    if (m) m.style.display = 'none';
};

window.submitEditMeetingForm = async function() {
    const code = document.getElementById('editMeetingCode').value;
    const payload = {
        meeting_code: code,
        title: document.getElementById('editMeetingTitle').value.trim(),
        description: document.getElementById('editMeetingDesc').value.trim(),
        scheduled_start: document.getElementById('editMeetingScheduled').value || null,
        recurrence: document.getElementById('editMeetingRecurrence').value,
        timezone: document.getElementById('editMeetingTimezone').value,
        reminder_minutes: document.getElementById('editMeetingReminder').value,
        require_waiting_room: document.getElementById('editMeetingWaitingRoom').checked ? 1 : 0
    };

    try {
        const res = await fetch('api/classroom_meetings.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            showToast('Réunion modifiée avec succès !', 'success');
            closeEditMeetingModal();
            loadDashboardRooms();
        } else {
            showToast('Erreur modification : ' + (data.error || 'Erreur inconnue'), 'danger');
        }
    } catch (e) {
        showToast('Erreur de communication', 'danger');
    }
};

window.cancelMeetingPrompt = async function(meetingCode = null) {
    const code = meetingCode || document.getElementById('editMeetingCode').value;
    if (confirm(`Êtes-vous sûr de vouloir annuler définitivement la réunion "${code}" ?`)) {
        try {
            const res = await fetch('api/classroom_meetings.php?action=cancel', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ meeting_code: code })
            });
            const data = await res.json();
            if (data.success) {
                showToast(`La réunion ${code} a été annulée.`, 'warning');
                closeEditMeetingModal();
                loadDashboardRooms();
            }
        } catch (e) {
            showToast('Erreur annulation', 'danger');
        }
    }
};

// -------------------------------------------------------------
// INVITATIONS PAR E-MAIL & CONFIRMATIONS RSVP (MODULE F)
// -------------------------------------------------------------
function loadInviteSelectMeetings() {
    const sel = document.getElementById('inviteSelectMeetingCode');
    if (!sel) return;
    sel.innerHTML = `<option value="${escapeHtml(roomId)}">Salle Actuelle (${escapeHtml(roomId)})</option>`;
    currentLoadedRooms.forEach(r => {
        const code = r.meeting_code || r.room_id;
        if (code !== roomId) {
            const opt = document.createElement('option');
            opt.value = code;
            opt.textContent = `${r.title || 'Visioconférence'} (${code})`;
            sel.appendChild(opt);
        }
    });
}

// Envoi d'une invitation par courriel
const btnSendInvite = document.getElementById('btnSendInviteEmailSubmit');
if (btnSendInvite) {
    btnSendInvite.addEventListener('click', async () => {
        const meetingCode = document.getElementById('inviteSelectMeetingCode')?.value || roomId;
        const email = document.getElementById('inviteGuestEmail')?.value.trim();
        const name = document.getElementById('inviteGuestName')?.value.trim();
        const role = document.getElementById('inviteGuestRole')?.value || 'participant';

        if (!email) {
            showToast('Veuillez saisir une adresse e-mail valide.', 'info');
            return;
        }

        try {
            const payload = {
                meeting_code: meetingCode,
                email: email,
                recipient_name: name,
                role: role
            };

            const res = await fetch('api/classroom_meetings.php?action=send_email_invite', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                showToast(`Invitation officielle transmise à ${email} !`, 'success');
                document.getElementById('inviteGuestEmail').value = '';
                document.getElementById('inviteGuestName').value = '';
                loadMeetingInvitationsList();
            } else {
                showToast('Erreur envoi invitation : ' + (data.error || 'Erreur inconnue'), 'danger');
            }
        } catch (e) {
            showToast('Erreur lors de la transmission de l\'invitation', 'danger');
        }
    });
}

// Chargement de la liste des invitations et confirmations RSVP
window.loadMeetingInvitationsList = async function() {
    const container = document.getElementById('rsvpListContainer');
    const meetingCode = document.getElementById('inviteSelectMeetingCode')?.value || roomId;
    if (!container) return;

    container.innerHTML = '<small style="color:#64748B;"><i class="fa-solid fa-spinner fa-spin"></i> Chargement des RSVP...</small>';

    try {
        const res = await fetch(`api/classroom_meetings.php?action=list_invitations&meeting_code=${encodeURIComponent(meetingCode)}`);
        const data = await res.json();
        if (data.success && Array.isArray(data.invitations)) {
            if (data.invitations.length === 0) {
                container.innerHTML = '<small style="color:#64748B; font-style:italic;">Aucune invitation émise pour cette réunion.</small>';
                return;
            }

            container.innerHTML = '';
            data.invitations.forEach(inv => {
                const row = document.createElement('div');
                row.style.display = 'flex';
                row.style.alignItems = 'center';
                row.style.justifyContent = 'space-between';
                row.style.padding = '8px 10px';
                row.style.background = 'rgba(255,255,255,0.03)';
                row.style.borderRadius = '6px';
                row.style.fontSize = '0.75rem';
                row.style.gap = '8px';

                let rsvpBadge = '<span style="color:#F59E0B; background:rgba(245,158,11,0.15); padding:1px 6px; border-radius:4px;"><i class="fa-regular fa-clock"></i> En attente</span>';
                if (inv.status === 'accepted') {
                    rsvpBadge = '<span style="color:#10B981; background:rgba(16,185,129,0.15); padding:1px 6px; border-radius:4px;"><i class="fa-solid fa-check"></i> Confirmé</span>';
                } else if (inv.status === 'declined') {
                    rsvpBadge = '<span style="color:#EF4444; background:rgba(239,68,68,0.15); padding:1px 6px; border-radius:4px;"><i class="fa-solid fa-xmark"></i> Décliné</span>';
                } else if (inv.status === 'attended') {
                    rsvpBadge = '<span style="color:#3B82F6; background:rgba(59,130,246,0.15); padding:1px 6px; border-radius:4px;"><i class="fa-solid fa-user-check"></i> Présent</span>';
                }

                const fullUrl = `${window.location.origin}${window.location.pathname}?room=${encodeURIComponent(inv.meeting_code)}&invite=${encodeURIComponent(inv.invitation_token)}`;

                row.innerHTML = `
                    <div style="min-width:0; overflow:hidden;">
                        <strong style="color:#FFF; display:block; white-space:nowrap; text-overflow:ellipsis; overflow:hidden;">
                            ${escapeHtml(inv.recipient_name || inv.recipient_email || 'Invité')}
                        </strong>
                        <small style="color:#94A3B8; display:block;">${escapeHtml(inv.recipient_email || 'Lien direct')} &bull; Rôle : ${escapeHtml(inv.role)}</small>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
                        ${rsvpBadge}
                        <button type="button" class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText('${fullUrl}').then(() => showToast('Lien copié !', 'success'))" style="font-size:0.65rem; padding:2px 6px;" title="Copier le lien d'invitation personnalisé">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                `;
                container.appendChild(row);
            });
        }
    } catch (e) {
        container.innerHTML = '<small style="color:#EF4444;">Erreur chargement des confirmations.</small>';
    }
};

// -------------------------------------------------------------
// RAPPELS AVANT LE DÉBUT D'UNE RÉUNION (MODULE F)
// -------------------------------------------------------------
let reminderCheckedMeetings = {};

function startMeetingReminderWatchdog() {
    setInterval(async () => {
        try {
            const res = await fetch('api/classroom_meetings.php?action=list&status=scheduled');
            const data = await res.json();
            if (data.success && Array.isArray(data.rooms)) {
                const now = Date.now();
                data.rooms.forEach(m => {
                    if (!m.scheduled_start) return;
                    const startTime = new Date(m.scheduled_start).getTime();
                    const remMinutes = parseInt(m.reminder_minutes || 15);
                    const diffMinutes = Math.round((startTime - now) / 60000);

                    // Si la réunion commence dans les remMinutes (entre 0 et remMinutes) et pas encore notifiée
                    if (diffMinutes > 0 && diffMinutes <= remMinutes && !reminderCheckedMeetings[m.meeting_code]) {
                        reminderCheckedMeetings[m.meeting_code] = true;
                        playToneChime(587.33, 0.4);
                        showToast(`⏰ Rappel : La réunion "${m.title}" commence dans ${diffMinutes} minute(s) !`, 'warning');
                    }
                });
            }
        } catch (e) {}
    }, 60000); // Vérification chaque minute
}
startMeetingReminderWatchdog();

// -------------------------------------------------------------
// 25. SUPERVISION ET ADAPTATION DE LA QUALITÉ DU RÉSEAU (WebRTC Stats)
// -------------------------------------------------------------
let networkMonitorInterval = null;

function startNetworkQualityMonitor() {
    if (networkMonitorInterval) return;

    networkMonitorInterval = setInterval(async () => {
        const badge = document.getElementById('netQualityBadge');
        if (!badge) return;

        const pids = Object.keys(peerConnections);
        if (pids.length === 0) {
            badge.innerHTML = '<i class="fa-solid fa-signal" style="color:#10B981;"></i> 1080p HD';
            badge.title = 'Flux HD local actif';
            return;
        }

        let totalRtt = 0;
        let count = 0;

        for (const pid of pids) {
            const pc = peerConnections[pid];
            if (!pc || pc.connectionState !== 'connected') continue;

            try {
                const stats = await pc.getStats();
                stats.forEach(report => {
                    if (report.type === 'candidate-pair' && report.state === 'succeeded' && typeof report.currentRoundTripTime === 'number') {
                        totalRtt += report.currentRoundTripTime * 1000;
                        count++;
                    }
                });
            } catch (e) {}
        }

        if (count > 0) {
            const avgRtt = Math.round(totalRtt / count);
            if (avgRtt < 130) {
                badge.innerHTML = `<i class="fa-solid fa-signal" style="color:#10B981;"></i> 1080p HD (${avgRtt}ms)`;
                badge.style.borderColor = 'rgba(16, 185, 129, 0.4)';
                badge.title = `Qualité optimale. Latence RTT : ${avgRtt} ms`;
            } else if (avgRtt < 280) {
                badge.innerHTML = `<i class="fa-solid fa-signal" style="color:#3B82F6;"></i> 720p HD (${avgRtt}ms)`;
                badge.style.borderColor = 'rgba(59, 130, 246, 0.4)';
                badge.title = `Connexion stable. Latence RTT : ${avgRtt} ms`;
            } else {
                badge.innerHTML = `<i class="fa-solid fa-triangle-exclamation" style="color:#F59E0B;"></i> Débit réduit (${avgRtt}ms)`;
                badge.style.borderColor = 'rgba(245, 158, 11, 0.4)';
                badge.title = `Réseau instable (${avgRtt} ms). Priorité donnée à la fluidité audio.`;
            }
        } else {
            badge.innerHTML = '<i class="fa-solid fa-signal" style="color:#10B981;"></i> 1080p HD';
        }
    }, 4000);
}

// -------------------------------------------------------------
// 15. SALLES DE SOUS-GROUPES (BREAKOUT ROOMS) - GESTION COMPLÈTE
// -------------------------------------------------------------

function openBreakoutRoomsModal() {
    const modal = document.getElementById('breakoutRoomsModal');
    if (!modal) return;

    const setupView = document.getElementById('breakoutSetupView');
    const activeView = document.getElementById('breakoutActiveView');

    if (isBreakoutActive) {
        if (setupView) setupView.style.display = 'none';
        if (activeView) activeView.style.display = 'block';
        renderBreakoutLiveGrid();
    } else {
        if (setupView) setupView.style.display = 'block';
        if (activeView) activeView.style.display = 'none';
    }

    modal.style.display = 'flex';
}

function closeBreakoutRoomsModal() {
    const modal = document.getElementById('breakoutRoomsModal');
    if (modal) modal.style.display = 'none';
}

async function startBreakoutRoomsAction() {
    const roomCountSelect = document.getElementById('breakoutRoomCountSelect');
    const durationSelect = document.getElementById('breakoutDurationSelect');
    const assignModeRadio = document.querySelector('input[name="breakoutAssignMode"]:checked');

    const roomCount = parseInt(roomCountSelect?.value || '2', 10);
    const durationMinutes = parseInt(durationSelect?.value || '15', 10);
    const assignMode = assignModeRadio?.value || 'auto';

    // Construire la liste des ateliers
    const rooms = [];
    for (let i = 1; i <= roomCount; i++) {
        rooms.push({ index: i, title: `Atelier ${i} : Travail Collaboratif` });
    }

    // Récupérer tous les participants disponibles
    const participantList = [];
    
    // Ajouter les pairs distants connectés
    for (const pId in peerInfo) {
        if (pId && peerInfo[pId]) {
            participantList.push({ id: pId, name: peerInfo[pId].name || 'Participant' });
        }
    }
    for (const pId in peerConnections) {
        if (!participantList.some(p => p.id === pId)) {
            participantList.push({ id: pId, name: `Participant (${pId.substring(0, 6)})` });
        }
    }

    // Si aucun pair distant connecté (ex: session en cours de test), générer des participants de simulation
    if (participantList.length === 0) {
        participantList.push({ id: 'sim_peer_1', name: 'Jean David (Abidjan)' });
        participantList.push({ id: 'sim_peer_2', name: 'Marie Claire (Bouaké)' });
        participantList.push({ id: 'sim_peer_3', name: 'Pasteur Émile (Yamoussoukro)' });
        participantList.push({ id: 'sim_peer_4', name: 'Sarah Koffi (San Pedro)' });
    }

    const assignments = {};

    if (assignMode === 'auto') {
        // Répartition équitable
        participantList.forEach((p, index) => {
            const assignedRoomIdx = (index % roomCount) + 1;
            assignments[p.id] = {
                room_index: assignedRoomIdx,
                user_name: p.name
            };
        });
    } else if (assignMode === 'choice') {
        // Libre choix : initialement au premier atelier
        participantList.forEach((p) => {
            assignments[p.id] = {
                room_index: 1,
                user_name: p.name
            };
        });
    } else {
        // Manuel : distribué par défaut pour être réorganisé
        participantList.forEach((p, index) => {
            const assignedRoomIdx = (index % roomCount) + 1;
            assignments[p.id] = {
                room_index: assignedRoomIdx,
                user_name: p.name
            };
        });
    }

    try {
        const payload = {
            room_id: roomId,
            peer_id: myPeerId,
            user_name: currentUserName,
            command: 'breakout_start',
            rooms: rooms,
            assignments: assignments,
            duration_minutes: durationMinutes
        };

        const res = await fetch('api/classroom_signal.php?action=send_moderation', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            breakoutRoomsConfig = rooms;
            breakoutAssignments = assignments;
            isBreakoutActive = true;

            const setupView = document.getElementById('breakoutSetupView');
            const activeView = document.getElementById('breakoutActiveView');
            if (setupView) setupView.style.display = 'none';
            if (activeView) activeView.style.display = 'block';

            renderBreakoutLiveGrid();
            startBreakoutTimerCountdown(durationMinutes);
            playToneChime(659.25, 0.45, 'sine');
            showToast(`🚀 Les ${roomCount} salles de sous-groupes (${durationMinutes} min) ont été lancées !`, 'success');
        } else {
            showToast('Erreur lors du lancement des sous-groupes : ' + (data.error || 'Erreur réseau'), 'danger');
        }
    } catch (e) {
        console.error('breakout start error:', e);
        showToast('Erreur de connexion au serveur de sous-groupes.', 'danger');
    }
}

function renderBreakoutLiveGrid() {
    const grid = document.getElementById('breakoutRoomsLiveGrid');
    if (!grid) return;

    grid.innerHTML = '';

    if (!Array.isArray(breakoutRoomsConfig) || breakoutRoomsConfig.length === 0) {
        grid.innerHTML = '<div style="color:#94A3B8; font-size:0.85rem; padding:12px;">Aucun atelier actif.</div>';
        return;
    }

    breakoutRoomsConfig.forEach(room => {
        const roomCard = document.createElement('div');
        roomCard.style.cssText = 'background:rgba(15,23,42,0.85); border:1px solid rgba(6,182,212,0.3); border-radius:8px; padding:12px; display:flex; flex-direction:column; gap:8px;';

        // Filtrer les participants affectés à cette salle
        const roomMembers = [];
        for (const pId in breakoutAssignments) {
            const item = breakoutAssignments[pId];
            const rIdx = (typeof item === 'object') ? item.room_index : parseInt(item, 10);
            if (rIdx === room.index) {
                const pName = (typeof item === 'object') ? item.user_name : (peerInfo[pId]?.name || 'Participant');
                roomMembers.push({ id: pId, name: pName });
            }
        }

        const isHostInThisRoom = (currentBreakoutRoom === room.index);

        roomCard.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:6px;">
                <div>
                    <strong style="color:#38BDF8; font-size:0.88rem;">
                        <i class="fa-solid fa-shapes"></i> ${escapeHtml(room.title)}
                    </strong>
                    <span style="font-size:0.75rem; color:#94A3B8; margin-left:6px;">(${roomMembers.length} membre${roomMembers.length > 1 ? 's' : ''})</span>
                </div>
                <div>
                    ${isHostInThisRoom ? 
                        '<span style="background:#10B981; color:#FFFFFF; font-size:0.7rem; padding:2px 8px; border-radius:12px; font-weight:700;"><i class="fa-solid fa-eye"></i> Vous y êtes</span>' :
                        `<button type="button" class="btn btn-outline btn-sm" onclick="joinBreakoutAsHost(${room.index}, '${escapeHtml(room.title)}')" style="font-size:0.7rem; padding:2px 8px; color:#06B6D4; border-color:#06B6D4;" title="Rejoindre cet atelier en tant qu'observateur"><i class="fa-solid fa-right-to-bracket"></i> Rejoindre</button>`
                    }
                </div>
            </div>
            <div style="display:flex; flex-direction:column; gap:6px; min-height:60px; max-height:160px; overflow-y:auto; padding-right:4px;">
                ${roomMembers.length === 0 ? 
                    '<small style="color:#64748B; font-style:italic; font-size:0.78rem;">Aucun participant affecté.</small>' :
                    roomMembers.map(m => `
                        <div style="display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.04); border-radius:4px; padding:4px 8px;">
                            <span style="font-size:0.8rem; color:#FFFFFF; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:140px;" title="${escapeHtml(m.name)}">
                                <i class="fa-solid fa-user" style="font-size:0.7rem; color:#94A3B8; margin-right:4px;"></i> ${escapeHtml(m.name)}
                            </span>
                            <div style="display:flex; align-items:center; gap:4px;">
                                <label style="font-size:0.7rem; color:#94A3B8; margin:0;">Déplacer :</label>
                                <select onchange="moveParticipantToRoom('${m.id}', '${escapeHtml(m.name)}', parseInt(this.value, 10))" style="background:#1E293B; color:#38BDF8; border:1px solid rgba(255,255,255,0.15); border-radius:4px; font-size:0.72rem; padding:2px 4px;">
                                    ${breakoutRoomsConfig.map(rc => `
                                        <option value="${rc.index}" ${rc.index === room.index ? 'selected' : ''}>${escapeHtml(rc.title)}</option>
                                    `).join('')}
                                    <option value="0">Salle Principale</option>
                                </select>
                            </div>
                        </div>
                    `).join('')
                }
            </div>
        `;

        grid.appendChild(roomCard);
    });
}

async function moveParticipantToRoom(targetPeerId, targetName, newRoomIndex) {
    if (!targetPeerId) return;

    let targetTitle = 'Salle Principale';
    if (newRoomIndex > 0) {
        const found = breakoutRoomsConfig.find(r => r.index === newRoomIndex);
        targetTitle = found ? found.title : (`Atelier ` + newRoomIndex);
    }

    try {
        const payload = {
            room_id: roomId,
            peer_id: myPeerId,
            user_name: currentUserName,
            command: 'breakout_move_peer',
            target_peer: targetPeerId,
            target_name: targetName,
            room_index: newRoomIndex,
            room_title: targetTitle
        };

        const res = await fetch('api/classroom_signal.php?action=send_moderation', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            breakoutAssignments[targetPeerId] = {
                room_index: newRoomIndex,
                user_name: targetName
            };

            renderBreakoutLiveGrid();

            // Si c'est l'utilisateur lui-même qui est déplacé
            if (targetPeerId === myPeerId) {
                enterBreakoutRoom(newRoomIndex, targetTitle);
            }

            showToast(`${targetName} a été transféré vers ${targetTitle}.`, 'info');
        }
    } catch (e) {
        console.error('move peer error:', e);
    }
}

function joinBreakoutAsHost(roomIndex, roomTitle) {
    enterBreakoutRoom(roomIndex, roomTitle);
    closeBreakoutRoomsModal();
    showToast(`Vous êtes maintenant dans l'${roomTitle} en tant qu'observateur.`, 'success');
}

function enterBreakoutRoom(roomIndex, roomTitle, durationMinutes) {
    currentBreakoutRoom = roomIndex;
    currentBreakoutTitle = roomTitle || (roomIndex === 0 ? 'Salle Principale' : `Atelier ${roomIndex}`);

    const banner = document.getElementById('breakoutActiveBanner');
    const lblRoom = document.getElementById('lblBreakoutCurrentRoom');

    if (roomIndex > 0) {
        if (banner) banner.style.display = 'flex';
        if (lblRoom) lblRoom.textContent = currentBreakoutTitle;
        filterBreakoutVideoGrid(roomIndex);
        if (durationMinutes && !breakoutCountdownInterval) {
            startBreakoutTimerCountdown(durationMinutes);
        }
    } else {
        if (banner) banner.style.display = 'none';
        restoreMainRoomVideoGrid();
    }

    playToneChime(523.25, 0.3, 'sine');
}

function leaveBreakoutRoomPrompt() {
    if (confirm("Voulez-vous quitter cet atelier de sous-groupe et revenir dans la salle principale ?")) {
        enterBreakoutRoom(0, 'Salle Principale');
        showToast("Vous êtes de retour dans la salle principale.", "info");
    }
}

function filterBreakoutVideoGrid(roomIndex) {
    // Masquer les tuiles des participants situés dans un autre sous-groupe
    for (const pId in peerConnections) {
        const card = document.getElementById(`card_peer_${pId}`);
        if (card) {
            const assign = breakoutAssignments[pId];
            const pRoom = assign ? ((typeof assign === 'object') ? assign.room_index : parseInt(assign, 10)) : 0;
            if (pRoom === roomIndex) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        }
    }

    // Carte du formateur référent (Dr. K. N'Guessan)
    const cardTeacher = document.getElementById('cardTeacher');
    if (cardTeacher) {
        cardTeacher.style.display = (userRole === 'host' || currentBreakoutRoom === roomIndex) ? 'flex' : 'none';
    }

    // Votre propre vidéo locale reste visible
    const localCard = document.getElementById('localVideoContainer');
    if (localCard) localCard.style.display = 'flex';

    if (typeof updateGridDimensions === 'function') {
        updateGridDimensions();
    }
}

function restoreMainRoomVideoGrid() {
    // Rétablir toutes les cartes vidéo pour la salle plénière principale
    for (const pId in peerConnections) {
        const card = document.getElementById(`card_peer_${pId}`);
        if (card) card.style.display = 'flex';
    }

    const cardTeacher = document.getElementById('cardTeacher');
    if (cardTeacher) cardTeacher.style.display = 'flex';

    const localCard = document.getElementById('localVideoContainer');
    if (localCard) localCard.style.display = 'flex';

    if (typeof updateGridDimensions === 'function') {
        updateGridDimensions();
    }
}

function startBreakoutTimerCountdown(minutes) {
    if (breakoutCountdownInterval) {
        clearInterval(breakoutCountdownInterval);
        breakoutCountdownInterval = null;
    }

    if (breakoutCountdownSeconds <= 0) {
        breakoutCountdownSeconds = (minutes || 15) * 60;
    }

    function updateDisplay() {
        const m = Math.floor(breakoutCountdownSeconds / 60);
        const s = breakoutCountdownSeconds % 60;
        const str = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;

        const timerEl = document.getElementById('breakoutCountdownTimer');
        const modalTimerEl = document.getElementById('modalBreakoutTimer');

        if (timerEl) timerEl.textContent = str;
        if (modalTimerEl) modalTimerEl.textContent = str;
    }

    updateDisplay();

    breakoutCountdownInterval = setInterval(() => {
        breakoutCountdownSeconds--;
        updateDisplay();

        // Avertissement de 60 secondes avant clôture
        if (breakoutCountdownSeconds === 60) {
            handleBreakoutCloseWarningSignal({ countdown_seconds: 60 });
        }

        // Fin du décompte : rapatriement automatique
        if (breakoutCountdownSeconds <= 0) {
            clearInterval(breakoutCountdownInterval);
            breakoutCountdownInterval = null;
            handleBreakoutEndedSignal({ by: 'Minuteur Automatique' });
        }
    }, 1000);
}

async function promptBroadcastBreakoutMessage() {
    const msg = prompt("Message d'annonce à diffuser simultanément dans tous les ateliers de sous-groupes :");
    if (!msg || !msg.trim()) return;

    try {
        const payload = {
            room_id: roomId,
            peer_id: myPeerId,
            user_name: currentUserName,
            command: 'breakout_broadcast_msg',
            message: msg.trim()
        };

        const res = await fetch('api/classroom_signal.php?action=send_moderation', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            showToast("📢 Annonce générale diffusée à tous les sous-groupes !", "info");
        }
    } catch (e) {
        console.error('broadcast error:', e);
    }
}

async function warnAndEndBreakoutRooms() {
    if (confirm("Fermer toutes les salles de sous-groupes et faire revenir tous les participants dans la salle principale maintenant ?")) {
        try {
            const payload = {
                room_id: roomId,
                peer_id: myPeerId,
                user_name: currentUserName,
                command: 'breakout_end'
            };

            await fetch('api/classroom_signal.php?action=send_moderation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            handleBreakoutEndedSignal({ by: currentUserName });
            closeBreakoutRoomsModal();
        } catch (e) {
            console.error('end breakout error:', e);
        }
    }
}

async function askHostBreakoutHelp() {
    if (currentBreakoutRoom <= 0) {
        showToast("Vous êtes déjà dans la salle principale avec l'hôte.", "info");
        return;
    }

    try {
        const payload = {
            room_id: roomId,
            peer_id: myPeerId,
            user_name: currentUserName,
            command: 'breakout_ask_help',
            room_index: currentBreakoutRoom,
            room_title: currentBreakoutTitle || (`Atelier ` + currentBreakoutRoom)
        };

        const res = await fetch('api/classroom_signal.php?action=send_moderation', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            playToneChime(587.33, 0.4, 'sine');
            showToast("🔔 Votre demande d'aide a été envoyée à l'hôte. Il va bientôt rejoindre votre atelier.", "success");
        }
    } catch (e) {
        console.error('ask help error:', e);
    }
}

// -------------------------------------------------------------
// GESTIONNAIRES DE SIGNAUX WEBRTC DES SOUS-GROUPES
// -------------------------------------------------------------

function handleBreakoutStartedSignal(payload) {
    breakoutRoomsConfig = payload.rooms || [];
    breakoutAssignments = payload.assignments || {};
    isBreakoutActive = true;

    // Vérifier si le pair local est assigné
    if (userRole !== 'host') {
        const myAssignment = breakoutAssignments[myPeerId];
        if (myAssignment) {
            const rIdx = (typeof myAssignment === 'object') ? myAssignment.room_index : parseInt(myAssignment, 10);
            const foundRoom = breakoutRoomsConfig.find(r => r.index === rIdx);
            const rTitle = foundRoom ? foundRoom.title : (`Atelier ` + rIdx);
            enterBreakoutRoom(rIdx, rTitle, payload.duration_minutes);
            showToast(`🚀 Vous rejoignez l'${rTitle}. Bon travail d'équipe !`, 'info');
        } else {
            const firstRoom = breakoutRoomsConfig[0];
            if (firstRoom) {
                enterBreakoutRoom(firstRoom.index, firstRoom.title, payload.duration_minutes);
                showToast(`🚀 Sous-groupes ouverts : Vous êtes dans l'${firstRoom.title}.`, 'info');
            }
        }
    } else {
        // L'hôte est informé et peut superviser/visiter depuis le modal
        showToast(`🚀 Les sous-groupes sont ouverts. Vous pouvez superviser et visiter chaque atelier.`, 'info');
        const setupView = document.getElementById('breakoutSetupView');
        const activeView = document.getElementById('breakoutActiveView');
        if (setupView) setupView.style.display = 'none';
        if (activeView) activeView.style.display = 'block';
        renderBreakoutLiveGrid();
        startBreakoutTimerCountdown(payload.duration_minutes);
    }
}

function handleBreakoutPeerMovedSignal(payload) {
    if (!payload) return;

    breakoutAssignments[payload.target_peer] = {
        room_index: payload.room_index,
        user_name: payload.target_name
    };

    if (payload.target_peer === myPeerId) {
        enterBreakoutRoom(payload.room_index, payload.room_title);
        playToneChime(587.33, 0.45, 'triangle');
        showToast(`🔄 Vous avez été transféré vers ${payload.room_title} par l'animateur.`, 'warning');
    }

    if (userRole === 'host') {
        renderBreakoutLiveGrid();
    }
}

function handleBreakoutBroadcastSignal(payload) {
    if (!payload || !payload.message) return;

    const banner = document.getElementById('breakoutBroadcastBanner');
    const textEl = document.getElementById('breakoutBroadcastText');

    if (banner && textEl) {
        textEl.innerHTML = `<i class="fa-solid fa-bullhorn"></i> <strong>Annonce de l'Hôte (${escapeHtml(payload.from || 'Animateur')}) :</strong> ${escapeHtml(payload.message)}`;
        banner.style.display = 'flex';
        playToneChime(659.25, 0.4, 'sine');

        // Disparaît après 14 secondes
        setTimeout(() => {
            banner.style.display = 'none';
        }, 14000);
    }
}

function handleBreakoutCloseWarningSignal(payload) {
    const banner = document.getElementById('breakoutClosingWarningBanner');
    const secEl = document.getElementById('breakoutClosingSeconds');

    let sec = (payload && payload.countdown_seconds) ? payload.countdown_seconds : 60;

    if (banner && secEl) {
        secEl.textContent = sec;
        banner.style.display = 'flex';
        playToneChime(440, 0.4, 'triangle');

        if (breakoutClosingInterval) clearInterval(breakoutClosingInterval);

        breakoutClosingInterval = setInterval(() => {
            sec--;
            if (secEl) secEl.textContent = sec;
            if (sec <= 0) {
                clearInterval(breakoutClosingInterval);
                breakoutClosingInterval = null;
                banner.style.display = 'none';
            }
        }, 1000);
    }
}

function handleBreakoutEndedSignal(payload) {
    if (breakoutCountdownInterval) {
        clearInterval(breakoutCountdownInterval);
        breakoutCountdownInterval = null;
    }
    if (breakoutClosingInterval) {
        clearInterval(breakoutClosingInterval);
        breakoutClosingInterval = null;
    }

    breakoutCountdownSeconds = 0;
    isBreakoutActive = false;
    currentBreakoutRoom = 0;
    currentBreakoutTitle = '';

    const activeBanner = document.getElementById('breakoutActiveBanner');
    const warnBanner = document.getElementById('breakoutClosingWarningBanner');
    const bcastBanner = document.getElementById('breakoutBroadcastBanner');

    if (activeBanner) activeBanner.style.display = 'none';
    if (warnBanner) warnBanner.style.display = 'none';
    if (bcastBanner) bcastBanner.style.display = 'none';

    restoreMainRoomVideoGrid();

    const setupView = document.getElementById('breakoutSetupView');
    const activeView = document.getElementById('breakoutActiveView');
    if (setupView) setupView.style.display = 'block';
    if (activeView) activeView.style.display = 'none';

    playToneChime(523.25, 0.5, 'sine');
    showToast("✨ Fin des ateliers : Tous les participants sont de retour dans la salle principale !", "success");
}

function handleBreakoutHelpRequestedSignal(payload) {
    if (userRole === 'host') {
        playToneChime(880, 0.6, 'square');
        const rTitle = payload.room_title || (`Atelier ` + (payload.room_index || ''));
        const uName = payload.user_name || 'Un participant';

        showToast(`🚨 ${escapeHtml(uName)} demande de l'aide dans ${escapeHtml(rTitle)} !`, 'danger');

        if (confirm(`🚨 Appel à l'aide !\n\n${uName} sollicite votre assistance dans ${rTitle}.\n\nSouhaitez-vous rejoindre cet atelier immédiatement ?`)) {
            joinBreakoutAsHost(payload.room_index, rTitle);
        }
    }
}

// Exportation globale pour les attributs HTML inline
window.openBreakoutRoomsModal = openBreakoutRoomsModal;
window.closeBreakoutRoomsModal = closeBreakoutRoomsModal;
window.startBreakoutRoomsAction = startBreakoutRoomsAction;
window.renderBreakoutLiveGrid = renderBreakoutLiveGrid;
window.moveParticipantToRoom = moveParticipantToRoom;
window.joinBreakoutAsHost = joinBreakoutAsHost;
window.enterBreakoutRoom = enterBreakoutRoom;
window.leaveBreakoutRoomPrompt = leaveBreakoutRoomPrompt;
window.promptBroadcastBreakoutMessage = promptBroadcastBreakoutMessage;
window.warnAndEndBreakoutRooms = warnAndEndBreakoutRooms;
window.askHostBreakoutHelp = askHostBreakoutHelp;

// -------------------------------------------------------------
// 16. TRANSCRIPTION EN DIRECT, SOUS-TITRES & RÉSUMÉ INTELLIGENT DE RÉUNION (IA VOCALE)
// -------------------------------------------------------------

function initSpeechRecognition() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        console.warn("Web Speech API n'est pas supportée nativement par ce navigateur. Les sous-titres reçus du réseau fonctionneront.");
        return null;
    }

    try {
        const recognition = new SpeechRecognition();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.lang = captionLanguage;
        recognition.maxAlternatives = 1;

        recognition.onstart = () => {
            console.log("SpeechRecognition démarrée avec succès.");
        };

        recognition.onresult = (event) => {
            let interimTranscript = '';
            let finalTranscript = '';

            for (let i = event.resultIndex; i < event.results.length; ++i) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalTranscript += transcript;
                } else {
                    interimTranscript += transcript;
                }
            }

            // Affichage intermédiaire immédiat
            if (interimTranscript) {
                showLiveCaptionOverlay(currentUserName, interimTranscript, true, (userRole === 'host'));
            }

            // Envoi de la phrase finale validée
            if (finalTranscript.trim()) {
                const cleanedText = finalTranscript.trim();
                showLiveCaptionOverlay(currentUserName, cleanedText, false, (userRole === 'host'));
                saveTranscriptEntryToServer(cleanedText);
            }
        };

        recognition.onerror = (event) => {
            if (event.error === 'not-allowed') {
                showToast("Accès microphone refusé pour la reconnaissance vocale.", "warning");
                isLiveCaptionsActive = false;
                updateCaptionsUIState();
            } else if (event.error !== 'no-speech') {
                console.log("SpeechRecognition error:", event.error);
            }
        };

        recognition.onend = () => {
            // Relancer automatiquement si les sous-titres sont toujours activés
            if (isLiveCaptionsActive) {
                setTimeout(() => {
                    try {
                        if (isLiveCaptionsActive) recognition.start();
                    } catch (e) {}
                }, 400);
            }
        };

        return recognition;
    } catch (e) {
        console.error("Erreur initSpeechRecognition:", e);
        return null;
    }
}

function updateCaptionsUIState() {
    const btnDock = document.getElementById('btnToggleCaptions');
    const btnSidebar = document.getElementById('btnSidebarCcToggle');
    const container = document.getElementById('liveCaptionsContainer');

    if (btnDock) {
        btnDock.classList.toggle('active', isLiveCaptionsActive);
        btnDock.style.color = isLiveCaptionsActive ? '#10B981' : '';
    }

    if (btnSidebar) {
        btnSidebar.innerHTML = isLiveCaptionsActive ? '<i class="fa-solid fa-closed-captioning"></i> CC Actif' : '<i class="fa-solid fa-closed-captioning"></i> CC Coupé';
        btnSidebar.style.color = isLiveCaptionsActive ? '#10B981' : '#94A3B8';
        btnSidebar.style.borderColor = isLiveCaptionsActive ? 'rgba(16,185,129,0.4)' : 'rgba(255,255,255,0.15)';
    }

    if (!isLiveCaptionsActive && container) {
        container.style.display = 'none';
    }
}

function toggleLiveCaptions() {
    isLiveCaptionsActive = !isLiveCaptionsActive;
    updateCaptionsUIState();

    if (isLiveCaptionsActive) {
        if (!captionRecognition) {
            captionRecognition = initSpeechRecognition();
        }

        if (captionRecognition) {
            try {
                captionRecognition.start();
            } catch (e) {
                // Déjà démarré
            }
        }

        showLiveCaptionOverlay("Système OSHDI", "Sous-titres en direct activés. Parlez dans votre micro...", false, false);
        playToneChime(659.25, 0.3, 'sine');
        showToast("🎙️ Sous-titres en direct et transcription activés.", "success");
    } else {
        if (captionRecognition) {
            try {
                captionRecognition.stop();
            } catch (e) {}
        }
        showToast("Sous-titres en direct désactivés.", "info");
    }
}

function changeCaptionLanguage(newLang) {
    captionLanguage = newLang;
    const badge = document.getElementById('captionLanguageBadge');
    if (badge) badge.textContent = newLang;

    if (captionRecognition) {
        captionRecognition.lang = newLang;
        if (isLiveCaptionsActive) {
            try {
                captionRecognition.stop();
            } catch (e) {}
        }
    }
    showToast(`Langue des sous-titres : ${newLang}`, "info");
}

function showLiveCaptionOverlay(speakerName, text, isInterim = false, isHost = false) {
    if (!isLiveCaptionsActive) return;

    const container = document.getElementById('liveCaptionsContainer');
    const speakerEl = document.getElementById('captionSpeakerName');
    const textEl = document.getElementById('captionSpeakerText');

    if (!container || !speakerEl || !textEl) return;

    speakerEl.textContent = `${speakerName} :`;
    speakerEl.style.color = isHost ? '#F59E0B' : '#38BDF8';

    textEl.textContent = text;
    textEl.className = isInterim ? 'caption-text interim' : 'caption-text';

    container.style.display = 'block';

    if (captionHideTimeout) {
        clearTimeout(captionHideTimeout);
    }

    if (!isInterim) {
        captionHideTimeout = setTimeout(() => {
            if (container) container.style.display = 'none';
        }, 6000);
    }
}

async function saveTranscriptEntryToServer(content) {
    try {
        const payload = {
            meeting_code: roomId,
            peer_id: myPeerId,
            speaker_name: currentUserName,
            speaker_role: userRole,
            content: content,
            timestamp_sec: meetingElapsedSeconds,
            language: captionLanguage,
            is_final: 1
        };

        const res = await fetch('api/classroom_transcripts.php?action=add_entry', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            appendTranscriptEntryLocally({
                id: data.id,
                speaker_name: currentUserName,
                speaker_role: userRole,
                content: content,
                timestamp_sec: meetingElapsedSeconds,
                formatted_time: data.formatted_time || '00:00',
                created_at: new Date().toLocaleTimeString()
            });
        }
    } catch (e) {
        console.error("Erreur sauvegarde transcription:", e);
    }
}

function handleLiveCaptionSignal(payload) {
    if (!payload) return;

    // Afficher dans le bandeau de sous-titres
    showLiveCaptionOverlay(
        payload.speaker_name, 
        payload.content, 
        false, 
        (payload.speaker_role === 'host')
    );

    // Ajouter au flux local
    appendTranscriptEntryLocally({
        id: payload.id || Date.now(),
        speaker_name: payload.speaker_name,
        speaker_role: payload.speaker_role,
        content: payload.content,
        timestamp_sec: payload.timestamp_sec || 0,
        formatted_time: payload.formatted_time || '00:00',
        created_at: new Date().toLocaleTimeString()
    });
}

function appendTranscriptEntryLocally(entry) {
    if (!entry || !entry.content) return;

    // Éviter les doublons stricts
    if (cachedTranscripts.some(t => t.id && entry.id && t.id === entry.id)) return;
    cachedTranscripts.push(entry);

    // 1. Mise à jour de la liste latérale
    const sidebarList = document.getElementById('sidebarTranscriptList');
    if (sidebarList) {
        const emptyMsg = sidebarList.querySelector('div[style*="font-style:italic"]');
        if (emptyMsg) emptyMsg.remove();

        const isHost = (entry.speaker_role === 'host' || entry.speaker_name.includes('Dr.'));
        const item = document.createElement('div');
        item.className = 'transcript-item' + (isHost ? ' host-item' : '');
        item.setAttribute('data-content', entry.content.toLowerCase());
        item.setAttribute('data-speaker', (entry.speaker_name || '').toLowerCase());

        item.innerHTML = `
            <div class="transcript-meta">
                <span class="transcript-speaker ${isHost ? 'host' : ''}">
                    <i class="fa-solid fa-user" style="font-size:0.65rem; margin-right:3px;"></i>
                    ${escapeHtml(entry.speaker_name)}
                </span>
                <span class="transcript-time">${escapeHtml(entry.formatted_time || '00:00')}</span>
            </div>
            <div class="transcript-text">${escapeHtml(entry.content)}</div>
        `;

        sidebarList.appendChild(item);
        sidebarList.scrollTop = sidebarList.scrollHeight;
    }

    // 2. Mise à jour du conteneur dans la modale si ouvert
    const modalContainer = document.getElementById('modalTranscriptListContainer');
    if (modalContainer && document.getElementById('transcriptModal')?.style.display === 'flex') {
        renderModalTranscriptsList(cachedTranscripts);
    }
}

async function loadSidebarTranscripts() {
    try {
        const res = await fetch(`api/classroom_transcripts.php?action=list_entries&meeting_code=${roomId}`);
        const data = await res.json();
        if (data.success && Array.isArray(data.entries)) {
            cachedTranscripts = data.entries;
            const sidebarList = document.getElementById('sidebarTranscriptList');
            if (sidebarList) {
                sidebarList.innerHTML = '';
                if (cachedTranscripts.length === 0) {
                    sidebarList.innerHTML = `
                        <div style="color:#64748B; font-size:0.78rem; font-style:italic; text-align:center; padding:16px;">
                            <i class="fa-solid fa-headphones fa-bounce" style="color:#38BDF8; font-size:1.2rem; display:block; margin-bottom:6px;"></i>
                            Aucune parole transcrite pour le moment.
                        </div>
                    `;
                } else {
                    cachedTranscripts.forEach(entry => appendTranscriptEntryLocally(entry));
                }
            }
        }
    } catch (e) {
        console.error("loadSidebarTranscripts error:", e);
    }
}

function filterTranscriptList(query) {
    const list = document.getElementById('sidebarTranscriptList');
    if (!list) return;

    const q = query.trim().toLowerCase();
    const items = list.querySelectorAll('.transcript-item');

    items.forEach(item => {
        const content = item.getAttribute('data-content') || '';
        const speaker = item.getAttribute('data-speaker') || '';
        if (!q || content.includes(q) || speaker.includes(q)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function filterModalTranscriptList(query) {
    const q = query.trim().toLowerCase();
    if (!q) {
        renderModalTranscriptsList(cachedTranscripts);
        return;
    }

    const filtered = cachedTranscripts.filter(t => 
        (t.content || '').toLowerCase().includes(q) || 
        (t.speaker_name || '').toLowerCase().includes(q)
    );

    renderModalTranscriptsList(filtered, q);
}

function renderModalTranscriptsList(entries, highlightQuery = '') {
    const container = document.getElementById('modalTranscriptListContainer');
    if (!container) return;

    container.innerHTML = '';

    if (!Array.isArray(entries) || entries.length === 0) {
        container.innerHTML = `
            <div style="text-align:center; padding:32px; color:#64748B;">
                <i class="fa-solid fa-magnifying-glass" style="font-size:1.8rem; margin-bottom:8px; display:block; opacity:0.6;"></i>
                Aucun résultat correspondant à votre recherche.
            </div>
        `;
        return;
    }

    entries.forEach(t => {
        const isHost = (t.speaker_role === 'host' || (t.speaker_name || '').includes('Dr.'));
        const row = document.createElement('div');
        row.className = 'transcript-item' + (isHost ? ' host-item' : '');
        row.style.marginBottom = '6px';

        let contentHtml = escapeHtml(t.content || '');
        if (highlightQuery) {
            const regex = new RegExp(`(${escapeRegex(highlightQuery)})`, 'gi');
            contentHtml = contentHtml.replace(regex, '<span class="transcript-highlight">$1</span>');
        }

        row.innerHTML = `
            <div class="transcript-meta">
                <span class="transcript-speaker ${isHost ? 'host' : ''}">
                    <i class="fa-solid ${isHost ? 'fa-crown' : 'fa-user'}" style="font-size:0.7rem; margin-right:4px;"></i>
                    ${escapeHtml(t.speaker_name || 'Participant')}
                    <small style="color:#94A3B8; font-weight:normal; margin-left:4px;">(${escapeHtml(t.speaker_role || 'participant')})</small>
                </span>
                <span class="transcript-time">
                    <i class="fa-regular fa-clock" style="font-size:0.65rem;"></i> ${escapeHtml(t.formatted_time || '00:00')}
                </span>
            </div>
            <div class="transcript-text" style="font-size:0.88rem; line-height:1.5;">${contentHtml}</div>
        `;

        container.appendChild(row);
    });
}

function escapeRegex(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function openTranscriptModal(initialTab = 'flow') {
    const modal = document.getElementById('transcriptModal');
    if (!modal) return;

    modal.style.display = 'flex';
    switchTranscriptModalTab(initialTab);
}

function closeTranscriptModal() {
    const modal = document.getElementById('transcriptModal');
    if (modal) modal.style.display = 'none';
}

function switchTranscriptModalTab(tab) {
    transcriptModalTab = tab;
    const btnFlow = document.getElementById('tabTranscriptViewFlow');
    const btnSummary = document.getElementById('tabTranscriptViewSummary');
    const flowView = document.getElementById('modalTranscriptFlowView');
    const summaryView = document.getElementById('modalTranscriptSummaryView');

    if (tab === 'flow') {
        if (btnFlow) {
            btnFlow.style.borderBottom = '2px solid #10B981';
            btnFlow.style.color = '#FFFFFF';
        }
        if (btnSummary) {
            btnSummary.style.borderBottom = 'none';
            btnSummary.style.color = '#94A3B8';
        }
        if (flowView) flowView.style.display = 'block';
        if (summaryView) summaryView.style.display = 'none';

        loadModalTranscripts();
    } else {
        if (btnSummary) {
            btnSummary.style.borderBottom = '2px solid #F59E0B';
            btnSummary.style.color = '#FFFFFF';
        }
        if (btnFlow) {
            btnFlow.style.borderBottom = 'none';
            btnFlow.style.color = '#94A3B8';
        }
        if (flowView) flowView.style.display = 'none';
        if (summaryView) summaryView.style.display = 'block';

        loadOrGenerateAiSummary();
    }
}

async function loadModalTranscripts() {
    try {
        const res = await fetch(`api/classroom_transcripts.php?action=list_entries&meeting_code=${roomId}`);
        const data = await res.json();
        if (data.success && Array.isArray(data.entries)) {
            cachedTranscripts = data.entries;
            renderModalTranscriptsList(cachedTranscripts);
        }
    } catch (e) {
        console.error("loadModalTranscripts error:", e);
    }
}

async function loadOrGenerateAiSummary() {
    const container = document.getElementById('summaryReportContainer');
    if (!container) return;

    container.innerHTML = `
        <div style="text-align:center; padding:32px; color:#94A3B8;">
            <i class="fa-solid fa-circle-notch fa-spin" style="font-size:2rem; color:#F59E0B; margin-bottom:12px; display:block;"></i>
            Génération et analyse intelligente du compte-rendu en cours...
        </div>
    `;

    try {
        // Tenter d'abord de récupérer le résumé existant
        const getRes = await fetch(`api/classroom_transcripts.php?action=get_summary&meeting_code=${roomId}`);
        const getData = await getRes.json();

        if (getData.success && getData.summary) {
            renderSummaryReportUI(getData.summary);
            return;
        }

        // Sinon, le générer automatiquement
        generateAiSummaryAction();
    } catch (e) {
        generateAiSummaryAction();
    }
}

async function generateAiSummaryAction() {
    const container = document.getElementById('summaryReportContainer');
    if (container) {
        container.innerHTML = `
            <div style="text-align:center; padding:32px; color:#94A3B8;">
                <i class="fa-solid fa-wand-magic-sparkles fa-spin" style="font-size:2rem; color:#F59E0B; margin-bottom:12px; display:block;"></i>
                Synthèse IA vocale : Analyse des transcriptions et extraction des résolutions...
            </div>
        `;
    }

    try {
        const res = await fetch(`api/classroom_transcripts.php?action=generate_summary&meeting_code=${roomId}&user_name=${encodeURIComponent(currentUserName)}`, {
            method: 'POST'
        });
        const data = await res.json();

        if (data.success && data.summary) {
            renderSummaryReportUI(data.summary);
            playToneChime(587.33, 0.4, 'sine');
            showToast("✨ Compte-rendu et résumé IA générés avec succès !", "success");
        } else {
            if (container) {
                container.innerHTML = '<div style="color:#EF4444; padding:20px;">Erreur lors de la génération du résumé.</div>';
            }
        }
    } catch (e) {
        if (container) {
            container.innerHTML = '<div style="color:#EF4444; padding:20px;">Erreur réseau lors de la génération.</div>';
        }
    }
}

function renderSummaryReportUI(summary) {
    const container = document.getElementById('summaryReportContainer');
    if (!container || !summary) return;

    // Sauvegarde en variable globale pour copie
    window._lastGeneratedSummary = summary;

    const stats = Array.isArray(summary.speakers_stats) ? summary.speakers_stats : [];
    const points = Array.isArray(summary.key_points) ? summary.key_points : [];
    const actions = Array.isArray(summary.action_items) ? summary.action_items : [];

    container.innerHTML = `
        <!-- Carte Titre & Synthèse Exécutive -->
        <div class="summary-hero-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                <h4 style="color:#FFFFFF; margin:0; font-size:1.15rem; font-weight:800;">
                    <i class="fa-solid fa-graduation-cap" style="color:#F59E0B;"></i> ${escapeHtml(summary.title || 'Session OSHDI')}
                </h4>
                <span style="font-size:0.75rem; color:#94A3B8; background:rgba(255,255,255,0.06); padding:3px 8px; border-radius:4px;">
                    <i class="fa-regular fa-calendar"></i> ${escapeHtml(summary.date || 'Séance en direct')}
                </span>
            </div>
            <div class="summary-section-title">
                <i class="fa-solid fa-align-left"></i> Synthèse Exécutive
            </div>
            <p style="color:#E2E8F0; font-size:0.88rem; line-height:1.6; margin:0;">
                ${escapeHtml(summary.summary_text || '')}
            </p>
        </div>

        <!-- Répartition de la prise de parole -->
        ${stats.length > 0 ? `
        <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:14px;">
            <div class="summary-section-title" style="color:#38BDF8;">
                <i class="fa-solid fa-chart-pie"></i> Répartition de la Prise de Parole
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px;">
                ${stats.map(s => `
                    <div>
                        <div style="display:flex; justify-content:space-between; font-size:0.78rem; margin-bottom:3px;">
                            <span style="color:#FFFFFF; font-weight:600;">${escapeHtml(s.speaker)}</span>
                            <span style="color:#38BDF8; font-family:monospace;">${s.percentage}%</span>
                        </div>
                        <div style="height:6px; background:rgba(255,255,255,0.08); border-radius:3px; overflow:hidden;">
                            <div style="width:${s.percentage}%; height:100%; background:linear-gradient(90deg, #0284C7, #38BDF8);"></div>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
        ` : ''}

        <!-- Points Clés & Débats -->
        <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:14px;">
            <div class="summary-section-title" style="color:#10B981;">
                <i class="fa-solid fa-list-check"></i> Points Clés & Thématiques Majeures
            </div>
            <ul class="summary-keypoints-list">
                ${points.map(pt => `<li>${escapeHtml(pt)}</li>`).join('')}
            </ul>
        </div>

        <!-- Plan d'Actions & Décisions -->
        <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:10px; padding:14px;">
            <div class="summary-section-title" style="color:#A78BFA;">
                <i class="fa-solid fa-clipboard-check"></i> Décisions Prises & Plan de Suivi
            </div>
            <div style="overflow-x:auto;">
                <table class="action-items-table">
                    <thead>
                        <tr>
                            <th>Action / Engagement</th>
                            <th>Responsable</th>
                            <th>Échéance</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${actions.map(act => {
                            let pillCls = 'encours';
                            if (act.status === 'Validé') pillCls = 'valide';
                            if (act.status === 'Prévu') pillCls = 'prevu';
                            return `
                                <tr>
                                    <td style="font-weight:600;">${escapeHtml(act.task)}</td>
                                    <td style="color:#38BDF8;">${escapeHtml(act.owner)}</td>
                                    <td style="color:#94A3B8; font-family:monospace;">${escapeHtml(act.deadline)}</td>
                                    <td><span class="action-status-pill ${pillCls}">${escapeHtml(act.status)}</span></td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function copySummaryToClipboard() {
    const s = window._lastGeneratedSummary;
    if (!s) {
        showToast("Aucun résumé disponible à copier.", "warning");
        return;
    }

    const lines = [];
    lines.push(`=======================================================`);
    lines.push(`COMPTE-RENDU OFFICIEL OSHDI : ${s.title || 'Session Interactive'}`);
    lines.push(`Date : ${s.date || new Date().toLocaleDateString()}`);
    lines.push(`=======================================================\n`);
    lines.push(`1. SYNTHÈSE EXÉCUTIVE :`);
    lines.push(s.summary_text || '');
    lines.push(`\n2. POINTS CLÉS :`);
    (s.key_points || []).forEach(pt => lines.push(`- ${pt}`));
    lines.push(`\n3. PLAN D'ACTIONS & DÉCISIONS :`);
    (s.action_items || []).forEach(a => lines.push(`- [${a.status}] ${a.task} (Responsable: ${a.owner}, Échéance: ${a.deadline})`));

    const textToCopy = lines.join('\n');

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(textToCopy).then(() => {
            showToast("📋 Compte-rendu copié dans le presse-papiers !", "success");
        }).catch(() => fallbackCopyText(textToCopy));
    } else {
        fallbackCopyText(textToCopy);
    }
}

function printMeetingSummary() {
    const s = window._lastGeneratedSummary;
    if (!s) {
        showToast("Veuillez d'abord générer le résumé.", "warning");
        return;
    }

    const printWin = window.open('', '_blank');
    if (!printWin) return;

    printWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Compte-Rendu OSHDI - ${s.meeting_code}</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 40px; color: #1E293B; line-height: 1.6; }
                h1 { color: #0A192F; border-bottom: 2px solid #D97706; padding-bottom: 8px; font-size: 20px; }
                h2 { color: #1E3A8A; font-size: 15px; margin-top: 24px; text-transform: uppercase; }
                p { font-size: 14px; }
                ul { font-size: 14px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
                th, td { border: 1px solid #CBD5E1; padding: 8px 12px; text-align: left; }
                th { background: #F1F5F9; }
                .footer { margin-top: 50px; border-top: 1px solid #E2E8F0; padding-top: 10px; font-size: 11px; color: #64748B; text-align: center; }
            </style>
        </head>
        <body>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <strong>ORGANISATION DE SECOURS HUMANITAIRE POUR LE DÉVELOPPEMENT INTÉGRÉ</strong>
                <span style="color:#D97706; font-weight:bold;">VISIOPRO</span>
            </div>
            <h1>Compte-Rendu de Réunion : ${escapeHtml(s.title || '')}</h1>
            <p><strong>Session :</strong> ${escapeHtml(s.meeting_code)} &bull; <strong>Date :</strong> ${escapeHtml(s.date || '')} &bull; <strong>Rapporteur :</strong> ${escapeHtml(s.generated_by || '')}</p>
            
            <h2>1. Synthèse Exécutive</h2>
            <p>${escapeHtml(s.summary_text || '')}</p>

            <h2>2. Points Clés & Débats</h2>
            <ul>
                ${(s.key_points || []).map(p => `<li>${escapeHtml(p)}</li>`).join('')}
            </ul>

            <h2>3. Décisions Prises & Plan de Suivi</h2>
            <table>
                <tr>
                    <th>Action</th>
                    <th>Responsable</th>
                    <th>Échéance</th>
                    <th>Statut</th>
                </tr>
                ${(s.action_items || []).map(a => `
                    <tr>
                        <td><strong>${escapeHtml(a.task)}</strong></td>
                        <td>${escapeHtml(a.owner)}</td>
                        <td>${escapeHtml(a.deadline)}</td>
                        <td>${escapeHtml(a.status)}</td>
                    </tr>
                `).join('')}
            </table>

            <div class="footer">
                Document officiel généré automatiquement par la plateforme OSHDI VisioPro.
            </div>
            <script>
                window.onload = function() { window.print(); };
            <\/script>
        </body>
        </html>
    `);
    printWin.document.close();
}

function exportTranscriptAction(format) {
    const url = `api/classroom_transcripts.php?action=export&meeting_code=${encodeURIComponent(roomId)}&format=${encodeURIComponent(format)}`;
    window.open(url, '_blank');
    showToast(`Téléchargement de la transcription (.${format.toUpperCase()}) en cours...`, "info");
}

// Exportation globale pour les attributs HTML inline
window.toggleLiveCaptions = toggleLiveCaptions;
window.changeCaptionLanguage = changeCaptionLanguage;
window.openTranscriptModal = openTranscriptModal;
window.closeTranscriptModal = closeTranscriptModal;
window.switchTranscriptModalTab = switchTranscriptModalTab;
window.filterTranscriptList = filterTranscriptList;
window.filterModalTranscriptList = filterModalTranscriptList;
window.generateAiSummaryAction = generateAiSummaryAction;
window.copySummaryToClipboard = copySummaryToClipboard;
window.printMeetingSummary = printMeetingSummary;
window.exportTranscriptAction = exportTranscriptAction;

// ==============================================================
// 26. GESTION DES PRÉSENCES & RAPPORTS DE RÉUNION (MODULE ATTENDANCE)
// ==============================================================
let cachedAttendanceData = null;
let attendanceLiveRefreshInterval = null;
let currentAttendanceFilter = 'all';
let currentAttendanceSearchQuery = '';

window.openAttendanceModal = function(initialTab = 'roster') {
    const modal = document.getElementById('attendanceReportsModal');
    if (!modal) return;

    modal.style.display = 'flex';
    switchAttendanceModalTab(initialTab);
    loadAttendanceReport();

    // Actualisation automatique en direct toutes les 6 secondes
    if (attendanceLiveRefreshInterval) clearInterval(attendanceLiveRefreshInterval);
    attendanceLiveRefreshInterval = setInterval(() => {
        if (modal.style.display === 'flex') {
            loadAttendanceReport(true);
        } else {
            clearInterval(attendanceLiveRefreshInterval);
            attendanceLiveRefreshInterval = null;
        }
    }, 6000);
};

window.closeAttendanceModal = function() {
    const modal = document.getElementById('attendanceReportsModal');
    if (modal) modal.style.display = 'none';
    if (attendanceLiveRefreshInterval) {
        clearInterval(attendanceLiveRefreshInterval);
        attendanceLiveRefreshInterval = null;
    }
};

window.switchAttendanceModalTab = function(tab) {
    const viewRoster = document.getElementById('viewAttRoster');
    const viewSummary = document.getElementById('viewAttSummary');
    const tabRoster = document.getElementById('tabAttViewRoster');
    const tabSummary = document.getElementById('tabAttViewSummary');

    if (tab === 'summary') {
        if (viewRoster) viewRoster.style.display = 'none';
        if (viewSummary) viewSummary.style.display = 'block';
        if (tabRoster) {
            tabRoster.classList.remove('active');
            tabRoster.style.borderBottom = 'none';
            tabRoster.style.color = '#94A3B8';
        }
        if (tabSummary) {
            tabSummary.classList.add('active');
            tabSummary.style.borderBottom = '2px solid #10B981';
            tabSummary.style.color = '#FFFFFF';
        }
    } else {
        if (viewRoster) viewRoster.style.display = 'block';
        if (viewSummary) viewSummary.style.display = 'none';
        if (tabRoster) {
            tabRoster.classList.add('active');
            tabRoster.style.borderBottom = '2px solid #10B981';
            tabRoster.style.color = '#FFFFFF';
        }
        if (tabSummary) {
            tabSummary.classList.remove('active');
            tabSummary.style.borderBottom = 'none';
            tabSummary.style.color = '#94A3B8';
        }
    }
};

window.loadAttendanceReport = async function(isSilent = false) {
    const tbody = document.getElementById('attendanceTableBody');
    if (!tbody) return;

    if (!isSilent && (!cachedAttendanceData || !cachedAttendanceData.records)) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align:center; padding:30px; color:#64748B;">
                    <i class="fa-solid fa-circle-notch fa-spin fa-2x" style="color:#10B981; display:block; margin-bottom:10px;"></i>
                    Chargement de la feuille d'émargement et synchronisation des flux...
                </td>
            </tr>
        `;
    }

    try {
        const res = await fetch(`api/classroom_attendance.php?action=get_report&meeting_code=${encodeURIComponent(roomId)}`);
        const data = await res.json();

        if (data && data.success) {
            cachedAttendanceData = data;
            renderAttendanceUI();
        } else {
            if (!isSilent) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align:center; padding:20px; color:#EF4444;">
                            <i class="fa-solid fa-triangle-exclamation"></i> Impossible de récupérer la feuille de présence.
                        </td>
                    </tr>
                `;
            }
        }
    } catch (e) {
        console.warn('Erreur rapport présence:', e);
    }
};

window.renderAttendanceUI = function() {
    if (!cachedAttendanceData) return;

    const summary = cachedAttendanceData.summary || {};
    const records = cachedAttendanceData.records || [];

    // 1. Mettre à jour les indicateurs KPI
    const kpiTotal = document.getElementById('attKpiTotal');
    const kpiOnline = document.getElementById('attKpiOnline');
    const kpiAvgDur = document.getElementById('attKpiAvgDuration');
    const kpiAvgRate = document.getElementById('attKpiAvgRate');

    if (kpiTotal) kpiTotal.textContent = summary.total_participants ?? records.length;
    if (kpiOnline) kpiOnline.textContent = summary.online_now ?? 0;
    if (kpiAvgDur) kpiAvgDur.textContent = summary.avg_duration_fmt ?? '0 min';
    if (kpiAvgRate) kpiAvgRate.textContent = summary.avg_attendance_rate ?? '100%';

    // 2. Mettre à jour les informations de session dans la vue Rapport
    const repCode = document.getElementById('attRepCode');
    const repTitle = document.getElementById('attRepTitle');
    const repHost = document.getElementById('attRepHost');
    const repDate = document.getElementById('attRepDate');
    const repPeak = document.getElementById('attRepPeak');

    if (repCode) repCode.textContent = summary.meeting_code || roomId;
    if (repTitle) repTitle.textContent = summary.title || "Session Interactive OSHDI VisioPro";
    if (repHost) repHost.textContent = summary.host_name || "Dr. K. N'Guessan";
    if (repDate) repDate.textContent = summary.timestamp || (summary.date ? `${summary.date} à ${new Date().toLocaleTimeString()}` : new Date().toLocaleString());
    if (repPeak) repPeak.textContent = `${summary.peak_attendance || Math.max(records.length, 8)} participants simultanés`;

    // 3. Filtrer et afficher les enregistrements du tableau
    const tbody = document.getElementById('attendanceTableBody');
    if (!tbody) return;

    const q = currentAttendanceSearchQuery.toLowerCase().trim();
    const filtered = records.filter(r => {
        // Filtre de recherche
        const nameMatch = (r.user_name || '').toLowerCase().includes(q);
        const roleMatch = (r.user_role || '').toLowerCase().includes(q);
        const stMatch = (r.status || '').toLowerCase().includes(q);
        const matchesQuery = !q || nameMatch || roleMatch || stMatch;

        if (!matchesQuery) return false;

        // Filtre de statut
        const isOnline = (r.status === 'online' || !r.left_at_fmt || r.left_at_fmt === 'En direct');
        if (currentAttendanceFilter === 'online') {
            return isOnline;
        } else if (currentAttendanceFilter === 'completed') {
            return !isOnline && ((r.attendance_percentage || 0) >= 70 || r.status === 'completed');
        } else if (currentAttendanceFilter === 'partial') {
            return !isOnline && ((r.attendance_percentage || 0) < 70 || r.status === 'partial');
        }
        return true;
    });

    if (filtered.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align:center; padding:24px; color:#94A3B8; font-style:italic;">
                    <i class="fa-solid fa-users-slash" style="font-size:1.4rem; display:block; margin-bottom:8px; opacity:0.6;"></i>
                    Aucun participant ne correspond aux critères sélectionnés.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filtered.map((r, idx) => {
        const initials = (r.user_name || 'Participant').substring(0, 2).toUpperCase();
        const role = (r.user_role || 'participant').toLowerCase();
        const isOnline = (r.status === 'online' || !r.left_at_fmt || r.left_at_fmt === 'En direct');
        const pct = parseInt(r.attendance_percentage || 100);

        // Couleur d'avatar
        const avColors = ['#D97706', '#2563EB', '#10B981', '#8B5CF6', '#06B6D4', '#EC4899'];
        const avColor = (role === 'host') ? '#D97706' : (role === 'cohost' ? '#8B5CF6' : (role === 'presentateur' ? '#06B6D4' : avColors[Math.abs(r.id || idx) % avColors.length]));

        // Badge de Rôle
        let roleBadge = `<span style="font-size:0.7rem; padding:2px 8px; border-radius:4px; font-weight:600; background:rgba(148,163,184,0.15); color:#CBD5E1; border:1px solid rgba(148,163,184,0.3);">Participant</span>`;
        if (role === 'host') {
            roleBadge = `<span style="font-size:0.7rem; padding:2px 8px; border-radius:4px; font-weight:700; background:rgba(245,158,11,0.2); color:#F59E0B; border:1px solid rgba(245,158,11,0.5);"><i class="fa-solid fa-crown"></i> Hôte</span>`;
        } else if (role === 'cohost') {
            roleBadge = `<span style="font-size:0.7rem; padding:2px 8px; border-radius:4px; font-weight:700; background:rgba(139,92,246,0.2); color:#A78BFA; border:1px solid rgba(139,92,246,0.5);"><i class="fa-solid fa-shield"></i> Co-hôte</span>`;
        } else if (role === 'presentateur') {
            roleBadge = `<span style="font-size:0.7rem; padding:2px 8px; border-radius:4px; font-weight:700; background:rgba(6,182,212,0.2); color:#38BDF8; border:1px solid rgba(6,182,212,0.5);"><i class="fa-solid fa-chalkboard-user"></i> Présentateur</span>`;
        }

        // Heure de sortie / En direct
        let exitHtml = `<span style="color:#CBD5E1; font-family:monospace;">${escapeHtml(r.left_at_fmt || '-')}</span>`;
        if (isOnline) {
            exitHtml = `
                <span style="display:inline-flex; align-items:center; gap:5px; font-size:0.72rem; font-weight:700; color:#10B981; background:rgba(16,185,129,0.15); border:1px solid rgba(16,185,129,0.4); padding:2px 8px; border-radius:12px;">
                    <span style="width:6px; height:6px; border-radius:50%; background:#10B981; box-shadow:0 0 6px #10B981; animation:pulseRed 1.2s infinite;"></span>
                    En direct
                </span>
            `;
        }

        // Taux d'assiduité avec jauge
        const barColor = pct >= 80 ? '#10B981' : (pct >= 60 ? '#F59E0B' : '#EF4444');

        // Badge Statut
        let statusBadge = '';
        if (isOnline) {
            statusBadge = `<span style="font-size:0.7rem; font-weight:700; color:#10B981;">Connecté</span>`;
        } else if (pct >= 70) {
            statusBadge = `<span style="font-size:0.7rem; font-weight:600; color:#3B82F6;">Séance validée</span>`;
        } else {
            statusBadge = `<span style="font-size:0.7rem; font-weight:600; color:#F59E0B;">Départ anticipé</span>`;
        }

        return `
            <tr style="border-bottom:1px solid rgba(255,255,255,0.06); transition:background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.04)'" onmouseout="this.style.background='transparent'">
                <td style="padding:10px 12px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:34px; height:34px; border-radius:50%; background:${avColor}; color:#FFF; font-weight:700; font-size:0.78rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            ${initials}
                        </div>
                        <div>
                            <strong style="color:#FFFFFF; font-size:0.83rem; display:block;">${escapeHtml(r.user_name)}</strong>
                            <small style="color:#64748B; font-size:0.7rem;">IP : ${escapeHtml(r.ip_address || '127.0.0.1')}</small>
                        </div>
                    </div>
                </td>
                <td style="padding:10px 12px;">${roleBadge}</td>
                <td style="padding:10px 12px; color:#CBD5E1; font-family:monospace; font-size:0.8rem;">
                    <i class="fa-solid fa-arrow-right-to-bracket" style="color:#10B981; font-size:0.7rem; margin-right:4px;"></i> ${escapeHtml(r.joined_at_fmt || '-')}
                </td>
                <td style="padding:10px 12px;">${exitHtml}</td>
                <td style="padding:10px 12px; font-weight:600; color:#F8FAFC; font-family:monospace;">
                    ${escapeHtml(r.duration_fmt || '0 min')}
                </td>
                <td style="padding:10px 12px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-weight:700; font-size:0.75rem; color:${barColor}; min-width:32px;">${pct}%</span>
                        <div style="width:55px; height:6px; background:rgba(255,255,255,0.1); border-radius:3px; overflow:hidden;">
                            <div style="width:${pct}%; height:100%; background:${barColor}; border-radius:3px;"></div>
                        </div>
                    </div>
                </td>
                <td style="padding:10px 12px;">${statusBadge}</td>
            </tr>
        `;
    }).join('');
};

window.filterAttendanceTable = function(query) {
    currentAttendanceSearchQuery = query || '';
    renderAttendanceUI();
};

window.filterAttendanceStatus = function(status) {
    currentAttendanceFilter = status || 'all';
    document.querySelectorAll('.filter-att-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    const targetBtn = event && event.target ? event.target.closest('button') : null;
    if (targetBtn) targetBtn.classList.add('active');
    renderAttendanceUI();
};

window.exportAttendanceCSV = function() {
    const url = `api/classroom_attendance.php?action=export_csv&meeting_code=${encodeURIComponent(roomId)}`;
    window.location.href = url;
    showToast("Génération & téléchargement du fichier CSV Excel...", "success");
};

window.exportAttendancePDF = function(autoprint = false) {
    const url = `api/classroom_attendance.php?action=export_pdf&meeting_code=${encodeURIComponent(roomId)}${autoprint ? '&autoprint=1' : ''}`;
    window.open(url, '_blank');
    showToast("Ouverture de la feuille d'émargement officielle certifiée PDF...", "info");
};

// Exportations globales supplémentaires
window.openAttendanceModal = openAttendanceModal;
window.closeAttendanceModal = closeAttendanceModal;
window.switchAttendanceModalTab = switchAttendanceModalTab;
window.loadAttendanceReport = loadAttendanceReport;
window.filterAttendanceTable = filterAttendanceTable;
window.filterAttendanceStatus = filterAttendanceStatus;
window.exportAttendanceCSV = exportAttendanceCSV;
window.exportAttendancePDF = exportAttendancePDF;



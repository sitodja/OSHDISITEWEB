/* ==============================================================
   OSHDI - JAVASCRIPT GLOBAL (Gestion des Paiements, Modal, Toast)
   ============================================================== */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Toggle Cross-Browser & Touch-Friendly
    const mobileBtn = document.querySelector('.mobile-toggle');
    const navMenu = document.querySelector('.nav-menu');
    if (mobileBtn && navMenu) {
        const toggleIcon = mobileBtn.querySelector('i');

        function toggleMenu(e) {
            if (e) e.stopPropagation();
            const isOpen = navMenu.classList.toggle('active');
            if (toggleIcon) {
                if (isOpen) {
                    toggleIcon.classList.remove('fa-bars');
                    toggleIcon.classList.add('fa-xmark');
                } else {
                    toggleIcon.classList.remove('fa-xmark');
                    toggleIcon.classList.add('fa-bars');
                }
            }
            // Bloquer le scroll d'arrière-plan sur mobile quand le menu est ouvert
            if (window.innerWidth <= 768) {
                document.body.style.overflow = isOpen ? 'hidden' : '';
            }
        }

        function closeMenu() {
            if (navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
                if (toggleIcon) {
                    toggleIcon.classList.remove('fa-xmark');
                    toggleIcon.classList.add('fa-bars');
                }
                document.body.style.overflow = '';
            }
        }

        mobileBtn.addEventListener('click', toggleMenu);

        // Fermer le menu lors du clic sur un lien
        navMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', closeMenu);
        });

        // Fermer le menu lors d'un clic à l'extérieur
        document.addEventListener('click', (e) => {
            if (!navMenu.contains(e.target) && !mobileBtn.contains(e.target)) {
                closeMenu();
            }
        });

        // Rétablir l'overflow si redimensionnement vers écran large
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                closeMenu();
            }
        });
    }

    // 2. Initialisation du Modal de Paiement Universel
    initPaymentModal();

    // 3. Gestionnaire des dons rapides sur la page d'accueil
    initQuickDonation();
});

// Système de notification Toast
function showToast(message, type = 'info') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icon = type === 'success' ? 'fa-circle-check' : (type === 'error' ? 'fa-circle-exclamation' : 'fa-bell');
    toast.innerHTML = `<i class="fa-solid ${icon}"></i> <span>${message}</span>`;
    
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 4500);
}

// Modal de Paiement
function initPaymentModal() {
    const modalOverlay = document.getElementById('universalPaymentModal');
    if (!modalOverlay) return;

    const closeBtn = modalOverlay.querySelector('.modal-close-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => closePaymentModal());
    }

    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) closePaymentModal();
    });

    // Sélection du moyen de paiement
    const methodCards = modalOverlay.querySelectorAll('.pay-method-card');
    const methodInput = document.getElementById('selectedPaymentMethod');
    const detailsContainer = document.getElementById('paymentMethodDetails');

    methodCards.forEach(card => {
        card.addEventListener('click', () => {
            methodCards.forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            const method = card.getAttribute('data-method');
            if (methodInput) methodInput.value = method;
            updatePaymentFields(method, detailsContainer);
        });
    });

    // Formulaire de soumission du paiement
    const payForm = document.getElementById('universalPaymentForm');
    if (payForm) {
        payForm.addEventListener('submit', (e) => {
            e.preventDefault();
            processPaymentSubmission(payForm);
        });
    }
}

function openPaymentModal(options) {
    const modal = document.getElementById('universalPaymentModal');
    if (!modal) return;

    // Remplir les données du modal
    document.getElementById('modalPaymentTitle').innerText = options.title || 'Paiement Sécurisé OSHDI';
    document.getElementById('modalPaymentAmount').innerText = options.amountFormatted || (options.amount + ' FCFA');
    document.getElementById('modalPaymentItemName').innerText = options.itemName || 'Contribution OSHDI';
    
    document.getElementById('payInputAmount').value = options.amount || 0;
    document.getElementById('payInputType').value = options.type || 'don';
    document.getElementById('payInputItemId').value = options.itemId || 0;
    document.getElementById('payInputItemTitle').value = options.itemName || '';

    modal.classList.add('active');
}

function closePaymentModal() {
    const modal = document.getElementById('universalPaymentModal');
    if (modal) modal.classList.remove('active');
}

function updatePaymentFields(method, container) {
    if (!container) return;
    
    let html = '';
    if (['wave', 'orange', 'mtn', 'moov'].includes(method)) {
        const labels = {
            wave: 'Numéro Wave (Côte d\'Ivoire, Sénégal...)',
            orange: 'Numéro Orange Money (Ex: 07 XX XX XX XX)',
            mtn: 'Numéro MTN Mobile Money (Ex: 05 XX XX XX XX)',
            moov: 'Numéro Moov Money (Ex: 01 XX XX XX XX)'
        };
        const badges = {
            wave: 'Application Wave ou QR Code',
            orange: 'Code secret #144*82# ou Push USSD',
            mtn: 'Push Notification *133# sur votre mobile',
            moov: 'Notification Push ou *155#'
        };
        html = `
            <div style="margin-top:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <label style="font-size:0.85rem; font-weight:600;">${labels[method]}</label>
                    <span style="font-size:0.75rem; color:#D97706; font-weight:600;"><i class="fa-solid fa-bolt"></i> ${badges[method]}</span>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-mobile-screen"></i>
                    <input type="tel" name="payer_phone" required class="form-control" placeholder="+225 07 00 00 00 00">
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:6px; font-size:0.75rem; color:#64748B;">
                    <span><i class="fa-solid fa-shield-halved" style="color:#10B981;"></i> Compte Récepteur Officiel : <strong style="color:#0A192F;">+225 07 47 16 64 61</strong></span>
                    <span style="background:#FEF3C7; color:#92400E; padding:2px 6px; border-radius:4px; font-weight:600;">OSHDI</span>
                </div>
            </div>
        `;
    } else if (method === 'card') {
        html = `
            <div style="margin-top:16px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:16px;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                    <i class="fa-solid fa-credit-card" style="font-size:1.6rem; color:#2563EB;"></i>
                    <div>
                        <strong style="display:block; font-size:0.9rem; color:#0A192F;">Paiement Sécurisé Carte Bancaire (Visa / Mastercard)</strong>
                        <span style="font-size:0.75rem; color:#64748B;">Protocole 3D-Secure avec chiffrement bancaire SSL</span>
                    </div>
                </div>
                <p style="font-size:0.82rem; color:#475569; margin:0; line-height:1.45;">
                    Vous serez redirigé vers le guichet de paiement bancaire certifié pour finaliser votre règlement par carte (Visa, Mastercard, Carte Bleue).
                </p>
            </div>
        `;
    } else if (method === 'paypal') {
        html = `
            <div style="margin-top:16px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:16px; text-align:center;">
                <i class="fa-brands fa-paypal" style="font-size:2rem; color:#003087;"></i>
                <strong style="display:block; margin-top:6px; color:#0A192F; font-size:0.9rem;">Passerelle Internationale PayPal</strong>
                <p style="font-size:0.82rem; color:#475569; margin-top:4px;">
                    Règlement rapide en EUR ou USD avec votre compte PayPal ou carte internationale. Conversion automatique garantie.
                </p>
            </div>
        `;
    } else if (method === 'crypto') {
        html = `
            <div style="margin-top:16px; background:#0F172A; color:#FFFFFF; border-radius:10px; padding:16px; font-size:0.85rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <strong style="color:#F59E0B;"><i class="fa-brands fa-bitcoin"></i> Choix de la Cryptomonnaie</strong>
                    <span style="color:#10B981; font-size:0.75rem;"><i class="fa-solid fa-shield-halved"></i> Décentralisé & Sans Frais</span>
                </div>
                <div style="margin-bottom:12px;">
                    <select name="crypto_coin" id="selectCryptoCoin" class="form-control" style="background:#1E293B; color:#FFFFFF; border:1px solid #334155;">
                        <option value="USDT_TRC20" selected>Tether USDT (Réseau TRC-20 Tron) - Recommandé</option>
                        <option value="BTC">Bitcoin (BTC)</option>
                        <option value="ETH">Ethereum (ETH ERC-20)</option>
                    </select>
                </div>
                <div style="background:rgba(255,255,255,0.08); padding:10px; border-radius:6px; font-size:0.78rem; color:#CBD5E1;">
                    <i class="fa-solid fa-circle-info" style="color:#F59E0B;"></i> Le QR code officiel et l'adresse de transfert du portefeuille certifié OSHDI s'afficheront dès validation.
                </div>
            </div>
        `;
    }

    container.innerHTML = html;
}

let activePaymentPollInterval = null;

function processPaymentSubmission(form) {
    const btnSubmit = form.querySelector('button[type="submit"]');
    const originalText = btnSubmit.innerHTML;
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Connexion à la passerelle...';

    const formData = new FormData(form);

    fetch('api/initiate_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = originalText;

        if (data.success) {
            handlePaymentActionResponse(data, form);
        } else {
            showToast(data.message || 'Erreur lors du paiement.', 'error');
        }
    })
    .catch(err => {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = originalText;
        showToast('Erreur de connexion : ' + err.message, 'error');
    });
}

function handlePaymentActionResponse(data, form) {
    const modalBody = form;

    // Cas 1 : Redirection directe (Wave, Stripe Carte, PayPal)
    if (data.action === 'redirect') {
        showToast('Redirection vers la passerelle sécurisée...', 'info');
        setTimeout(() => {
            window.location.href = data.redirect_url;
        }, 800);
        return;
    }

    // Cas 2 : Demande USSD en attente (MTN MoMo, Orange Money, Moov Money)
    if (data.action === 'ussd_pending') {
        modalBody.innerHTML = `
            <div style="text-align:center; padding:20px 10px;">
                <div style="width:64px; height:64px; border-radius:50%; background:#FEF3C7; color:#D97706; display:flex; align-items:center; justify-content:center; font-size:1.8rem; margin:0 auto 16px;">
                    <i class="fa-solid fa-mobile-screen-button fa-beat"></i>
                </div>
                <h3 style="font-size:1.3rem; color:#0A192F; margin-bottom:8px;">Autorisation Requise sur votre Téléphone</h3>
                <p style="color:#475569; font-size:0.9rem; margin-bottom:16px;">
                    Une demande de débit de <strong>${data.phone || 'votre mobile'}</strong> a été transmise.
                </p>

                <div style="background:#F1F5F9; border-radius:12px; padding:14px; margin-bottom:16px; border:1px solid #CBD5E1;">
                    <span style="font-size:0.75rem; color:#64748B; text-transform:uppercase; font-weight:700;">Instruction :</span>
                    <p style="margin:4px 0 0; font-size:0.95rem; font-weight:700; color:#0A192F;">
                        ${data.instructions || 'Validez le paiement en tapant votre code PIN sur votre téléphone.'}
                    </p>
                </div>

                <div style="display:flex; align-items:center; justify-content:center; gap:8px; color:#D97706; font-size:0.85rem; margin-bottom:20px;">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <span>En attente de votre validation mobile...</span>
                </div>

                <div style="display:flex; flex-direction:column; gap:10px;">
                    <a href="${data.redirect_url}" class="btn btn-gold" style="width:100%; padding:12px;">
                        <i class="fa-solid fa-circle-check"></i> J'ai confirmé sur mon téléphone
                    </a>
                    <button type="button" class="btn btn-outline" style="width:100%;" onclick="closePaymentModal(); location.reload();">
                        Annuler / Fermer
                    </button>
                </div>
            </div>
        `;

        // Polling automatique de vérification d'état
        if (activePaymentPollInterval) clearInterval(activePaymentPollInterval);
        activePaymentPollInterval = setInterval(() => {
            fetch('api/verify_payment.php?ref=' + encodeURIComponent(data.reference))
            .then(r => r.json())
            .then(res => {
                if (res.status === 'valide') {
                    clearInterval(activePaymentPollInterval);
                    showToast('Paiement approuvé avec succès !', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 1000);
                }
            })
            .catch(() => {});
        }, 2500);

        return;
    }

    // Cas 3 : Instructions Cryptomonnaie (USDT, BTC, ETH)
    if (data.action === 'crypto_instructions') {
        modalBody.innerHTML = `
            <div style="padding:10px 0;">
                <div style="text-align:center; margin-bottom:16px;">
                    <span style="background:#0F172A; color:#F59E0B; padding:4px 12px; border-radius:20px; font-weight:700; font-size:0.8rem;">
                        <i class="fa-brands fa-bitcoin"></i> ${data.crypto_name}
                    </span>
                    <h3 style="font-size:1.25rem; color:#0A192F; margin:10px 0 4px;">Transférez vos Cryptomonnaies</h3>
                    <p style="color:#64748B; font-size:0.85rem; margin:0;">Règlement décentralisé certifié OSHDI</p>
                </div>

                <div style="text-align:center; margin-bottom:16px;">
                    <img src="${data.qr_code_url}" alt="QR Code Crypto" style="width:160px; height:160px; border-radius:12px; border:2px solid #E2E8F0; padding:6px; background:#FFFFFF;">
                    <div style="margin-top:8px;">
                        <span style="font-size:0.85rem; color:#64748B;">Montant exact à envoyer :</span>
                        <div style="font-size:1.4rem; font-weight:800; color:#059669; font-family:'Outfit', sans-serif;">
                            ${data.crypto_amount} ${data.crypto_symbol}
                        </div>
                        <span style="font-size:0.75rem; color:#94A3B8;">(≈ $${data.usd_amount} USD)</span>
                    </div>
                </div>

                <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:12px; margin-bottom:16px;">
                    <span style="font-size:0.75rem; color:#64748B; font-weight:700; display:block; margin-bottom:4px;">
                        Adresse Officielle du Portefeuille OSHDI :
                    </span>
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                        <code style="font-size:0.75rem; color:#0A192F; word-break:break-all; font-weight:700;">${data.wallet_address}</code>
                        <button type="button" class="btn btn-outline" style="padding:6px 10px; font-size:0.75rem;" onclick="navigator.clipboard.writeText('${data.wallet_address}'); showToast('Adresse copiée !', 'success');">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                </div>

                <form id="cryptoSubmitTxForm" onsubmit="submitCryptoTxHash(event, '${data.reference}', '${data.crypto_coin}', '${data.redirect_url}')">
                    <label style="display:block; font-size:0.8rem; font-weight:700; margin-bottom:6px; color:#0A192F;">
                        Entrez le Hash de Transaction (TxID) après transfert :
                    </label>
                    <div class="input-group" style="margin-bottom:14px;">
                        <i class="fa-solid fa-receipt"></i>
                        <input type="text" id="inputCryptoTxHash" required class="form-control" placeholder="Ex: e617c0a98b42f...">
                    </div>
                    <button type="submit" id="btnConfirmCryptoTx" class="btn btn-gold" style="width:100%; padding:14px; margin-bottom:10px;">
                        <i class="fa-solid fa-circle-check"></i> Confirmer mon Don & Voir mon Reçu
                    </button>
                    <a href="${data.whatsapp_proof_url || 'https://wa.me/2250747166461'}" target="_blank" class="btn" style="width:100%; padding:10px; background:#25D366; color:#FFFFFF; font-size:0.85rem; font-weight:600; text-align:center; display:block;">
                        <i class="fa-brands fa-whatsapp"></i> Envoyer preuve WhatsApp (+225 07 47 16 64 61)
                    </a>
                </form>
            </div>
        `;
        return;
    }

    // Fallback standard
    showToast('Transaction approuvée !', 'success');
    setTimeout(() => {
        window.location.href = data.redirect_url || 'recu_don.php?ref=' + data.reference;
    }, 1000);
}

function submitCryptoTxHash(e, reference, coin, redirectUrl) {
    e.preventDefault();
    const btn = document.getElementById('btnConfirmCryptoTx');
    const hash = document.getElementById('inputCryptoTxHash').value.trim();
    if (!hash) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Validation du hash...';

    const fd = new FormData();
    fd.append('reference', reference);
    fd.append('tx_hash', hash);
    fd.append('crypto_coin', coin);

    fetch('api/crypto_submit_hash.php', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast('Hash validé ! Génération de votre reçu...', 'success');
            setTimeout(() => {
                window.location.href = res.redirect_url || redirectUrl;
            }, 1000);
        } else {
            btn.disabled = false;
            btn.innerHTML = 'Confirmer mon Don & Voir mon Reçu';
            showToast(res.message || 'Erreur lors de la validation.', 'error');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = 'Confirmer mon Don & Voir mon Reçu';
        window.location.href = redirectUrl;
    });
}

// Widget Don Rapide Hero
function initQuickDonation() {
    const amountBtns = document.querySelectorAll('.amount-btn');
    const customInput = document.querySelector('.custom-amount-input');
    const btnDonateHero = document.getElementById('btnHeroDonate');

    if (!btnDonateHero) return;

    let selectedAmount = 10000;

    amountBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            amountBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedAmount = parseInt(btn.getAttribute('data-val'), 10);
            if (customInput) customInput.value = '';
        });
    });

    if (customInput) {
        customInput.addEventListener('input', () => {
            amountBtns.forEach(b => b.classList.remove('active'));
            selectedAmount = parseInt(customInput.value, 10) || 0;
        });
    }

    btnDonateHero.addEventListener('click', () => {
        const amt = customInput && customInput.value ? parseInt(customInput.value, 10) : selectedAmount;
        if (!amt || amt < 500) {
            showToast('Veuillez entrer un montant valide (minimum 500 FCFA)', 'error');
            return;
        }

        const activeTab = document.querySelector('.quick-tab.active');
        const donationLabel = activeTab ? activeTab.innerText : 'Offrande de Souveraineté';

        openPaymentModal({
            title: 'Soutien - ' + donationLabel,
            itemName: donationLabel,
            amount: amt,
            amountFormatted: amt.toLocaleString('fr-FR') + ' FCFA',
            type: 'don'
        });
    });

    // Onglets types de don
    const tabs = document.querySelectorAll('.quick-tab');
    tabs.forEach(t => {
        t.addEventListener('click', () => {
            tabs.forEach(tab => tab.classList.remove('active'));
            t.classList.add('active');
        });
    });
}

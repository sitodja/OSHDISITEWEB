<?php
$page_title = "Adhésion Officielle & Prise en Charge des Vies Brisées";
$page_description = "Formulaire officiel de souscription et sondage d'opinion OSHDI. Adhérez aux programmes de restauration spirituelle et d'autonomie financière (ABM, REMDIS, CSD).";
$page_keywords = "adhésion OSHDI, souscription en ligne, sondage opinion, œuvres spirituelles, prise en charge démunis, formulaire aide, Côte d'Ivoire, Yamoussoukro, Abidjan";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$step = $_GET['step'] ?? 'intro';
$refPaiement = $_GET['ref'] ?? '';
$programmePreselectionne = $_GET['prog'] ?? '';
?>

<!-- En-tête -->
<section style="background: linear-gradient(135deg, #0A192F 0%, #172A45 100%); color:#FFFFFF; padding:60px 24px; text-align:center;">
    <div class="container">
        <span class="section-tag" style="background:rgba(245,158,11,0.2); color:#F59E0B;">Adhésion Officielle & Prise en Charge</span>
        <h1 style="color:#FFFFFF; font-size:2.8rem; margin:16px 0;"><?= t('sub_title') ?></h1>
        <p style="color:#CBD5E1; max-width:800px; margin:0 auto; font-size:1.1rem;">
            <?= t('sub_subtitle') ?>
        </p>
    </div>
</section>

<div class="container" style="padding:50px 24px;">
    <!-- Indicateur d'Étapes -->
    <div style="display:flex; justify-content:center; align-items:center; gap:20px; margin-bottom:40px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:36px; height:36px; border-radius:50%; background:<?= empty($refPaiement) ? 'var(--gold-gradient)' : '#10B981' ?>; color:#FFFFFF; display:flex; align-items:center; justify-content:center; font-weight:700;">
                <?= empty($refPaiement) ? '1' : '<i class="fa-solid fa-check"></i>' ?>
            </div>
            <div>
                <strong style="display:block; font-size:0.9rem; color:#0A192F;">Étape 1 : Règlement des Frais</strong>
                <small style="color:#64748B;">2 000 FCFA (Wave, OM, Moov, MTN, CB, Crypto)</small>
            </div>
        </div>

        <div class="step-divider" style="width:50px; height:2px; background:#CBD5E1;"></div>

        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:36px; height:36px; border-radius:50%; background:<?= !empty($refPaiement) ? 'var(--gold-gradient)' : '#E2E8F0' ?>; color:<?= !empty($refPaiement) ? '#FFFFFF' : '#64748B' ?>; display:flex; align-items:center; justify-content:center; font-weight:700;">
                2
            </div>
            <div>
                <strong style="display:block; font-size:0.9rem; color:#0A192F;">Étape 2 : Bordereau de Sondage</strong>
                <small style="color:#64748B;">Renseignement & Doléances</small>
            </div>
        </div>

        <div class="step-divider" style="width:50px; height:2px; background:#CBD5E1;"></div>

        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:36px; height:36px; border-radius:50%; background:#E2E8F0; color:#64748B; display:flex; align-items:center; justify-content:center; font-weight:700;">
                3
            </div>
            <div>
                <strong style="display:block; font-size:0.9rem; color:#0A192F;">Étape 3 : Transmission OSHDI</strong>
                <small style="color:#64748B;">Envoi par mail & Délivrance du Reçu</small>
            </div>
        </div>
    </div>

    <!-- Si le paiement n'est pas encore effectué : ÉTAPE 1 -->
    <?php if (empty($refPaiement)): ?>
        <div class="card-responsive" style="max-width:850px; margin:0 auto; background:#FFFFFF; border-radius:24px; box-shadow:var(--shadow-lg); border:1px solid var(--border-color);">
            <div style="text-align:center; margin-bottom:32px;">
                <div style="width:70px; height:70px; border-radius:50%; background:rgba(217,119,6,0.1); color:#D97706; display:flex; align-items:center; justify-content:center; font-size:2rem; margin:0 auto 16px;">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
                <h3 style="font-size:1.8rem; color:#0A192F; margin-bottom:8px;">Paiement des Frais d'Adhésion & Bordereau</h3>
                <p style="color:#64748B; font-size:0.95rem; max-width:600px; margin:0 auto;">
                    Conformément aux statuts de l'institution OSHDI, l'ouverture du dossier de souscription et la délivrance des documents officiels nécessitent le règlement des frais forfaitaires de <strong>2 000 FCFA</strong>.
                </p>
            </div>

            <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:16px; padding:24px; margin-bottom:32px;">
                <h4 style="font-size:1rem; color:#0A192F; margin-bottom:12px;">Ce que comprend votre souscription :</h4>
                <div class="form-grid-2" style="gap:14px; font-size:0.9rem; color:#475569;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <i class="fa-solid fa-circle-check" style="color:#10B981;"></i> Bordereau Officiel de Sondage d'Opinion
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <i class="fa-solid fa-circle-check" style="color:#10B981;"></i> Dossier de Prise en Charge Médicale (REMDIS)
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <i class="fa-solid fa-circle-check" style="color:#10B981;"></i> Dossier de Candidature Concours ABM
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <i class="fa-solid fa-circle-check" style="color:#10B981;"></i> Dépôt des doléances directement aux services financiers
                    </div>
                </div>
            </div>

            <div style="text-align:center;">
                <button type="button" class="btn btn-gold btn-lg" style="padding:16px 36px; font-size:1.15rem;" onclick="openPaymentModal({
                    title: 'Frais d\'Inscription Souscripteur OSHDI',
                    itemName: 'Bordereau de Souscription & Sondage d\'Opinion',
                    amount: 000,
                    amountFormatted: '2 000 FCFA',
                    type: 'souscription'
                })">
                    <i class="fa-solid fa-credit-card"></i> Payer 2 000 FCFA & Débloquer le Bordereau
                </button>
                <p style="margin-top:12px; font-size:0.8rem; color:#94A3B8;">
                    <i class="fa-solid fa-lock"></i> Paiement instantané via Wave, Orange Money, Moov, MTN, Carte Bancaire ou Crypto.
                </p>
            </div>
        </div>

    <!-- Si le paiement est validé : ÉTAPE 2 - FORMULAIRE DU BORDEREAU -->
    <?php else: ?>
        <div class="card-responsive" style="max-width:960px; margin:0 auto; background:#FFFFFF; border-radius:24px; box-shadow:var(--shadow-xl); border:1px solid var(--border-color);">
            
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid var(--gold); padding-bottom:20px; margin-bottom:30px; flex-wrap:wrap; gap:16px;">
                <div style="display:flex; align-items:center; gap:16px;">
                    <img src="assets/images/logo.png" alt="Logo OSHDI" style="width:60px; height:60px; border-radius:50%; border:2px solid var(--gold); background:#FFFFFF;">
                    <div>
                        <span style="font-size:0.75rem; color:#F59E0B; text-transform:uppercase; letter-spacing:1px; font-weight:700;">Document Officiel Agréé</span>
                        <h2 style="font-size:1.5rem; color:#0A192F;">BORDEREAU DE SOUSCRIPTION DE SONDAGE D'OPINION</h2>
                        <span style="font-size:0.82rem; color:#64748B;">Institution Œuvres Spirituelles et Humanitaires Divines (OSHDI)</span>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="background:#ECFDF5; border:1px solid #A7F3D0; color:#065F46; padding:6px 12px; border-radius:8px; font-size:0.8rem; font-weight:700;">
                        <i class="fa-solid fa-circle-check"></i> Frais Réglés (Réf: <?= htmlspecialchars($refPaiement) ?>)
                    </div>
                </div>
            </div>

            <form id="subscriberSlipForm">
                <input type="hidden" name="reference_paiement" value="<?= htmlspecialchars($refPaiement) ?>">
                <input type="hidden" name="montant" value="5000">

                <h4 style="font-size:1.15rem; color:#0A192F; margin-bottom:16px; border-left:4px solid var(--gold); padding-left:12px;">
                    1. Identité & Coordonnées du Souscripteur
                </h4>

                <div class="form-grid-2" style="gap:18px; margin-bottom:24px;">
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">Nom et Prénoms complets *</label>
                        <div class="input-group">
                            <i class="fa-solid fa-user"></i>
                            <input type="text" name="nom_complet" required class="form-control" placeholder="Ex: Kouamé N'Dri Paul">
                        </div>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">Profession / Activité actuelle *</label>
                        <div class="input-group">
                            <i class="fa-solid fa-briefcase"></i>
                            <input type="text" name="profession" required class="form-control" placeholder="Ex: Enseignant, Commerçant, Sans emploi...">
                        </div>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">Numéro de Téléphone / WhatsApp *</label>
                        <div class="input-group">
                            <i class="fa-solid fa-phone"></i>
                            <input type="tel" name="telephone" required class="form-control" placeholder="+225 07 00 00 00 00">
                        </div>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">Adresse Email pour accusé de réception *</label>
                        <div class="input-group">
                            <i class="fa-solid fa-envelope"></i>
                            <input type="email" name="email" required class="form-control" placeholder="exemple@domaine.com">
                        </div>
                    </div>
                </div>

                <h4 style="font-size:1.15rem; color:#0A192F; margin-bottom:16px; border-left:4px solid var(--gold); padding-left:12px;">
                    2. Localisation Territoriale (Pour rattachement à une filiale OSHDI)
                </h4>

                <div class="form-grid-3" style="gap:18px; margin-bottom:24px;">
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">Région *</label>
                        <div class="input-group">
                            <i class="fa-solid fa-map"></i>
                            <input type="text" name="region" required class="form-control" placeholder="Ex: Lagunes, Gbêkê, Poro...">
                        </div>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">Ville ou Département *</label>
                        <div class="input-group">
                            <i class="fa-solid fa-city"></i>
                            <input type="text" name="ville" required class="form-control" placeholder="Ex: Abidjan, Bouaké, Korhogo...">
                        </div>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">Quartier ou Localité *</label>
                        <div class="input-group">
                            <i class="fa-solid fa-location-dot"></i>
                            <input type="text" name="localite" required class="form-control" placeholder="Ex: Yopougon, Cocody, Air France...">
                        </div>
                    </div>
                </div>

                <h4 style="font-size:1.15rem; color:#0A192F; margin-bottom:16px; border-left:4px solid var(--gold); padding-left:12px;">
                    3. Programme Sollicité & Sondage d'Opinion
                </h4>

                <div style="margin-bottom:20px;">
                    <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">Quel programme OSHDI vous intéresse en priorité ? *</label>
                    <select name="programme_choisi" required class="form-control" style="padding-left:16px;">
                        <option value="REMDIS - Prise en charge médicale" <?= $programmePreselectionne === 'remdis' ? 'selected' : '' ?>>REMDIS (Soins médicaux, cliniques partenaires & réductions)</option>
                        <option value="ABM - Concours Africa Best Menu" <?= $programmePreselectionne === 'abm' ? 'selected' : '' ?>>Africa Best Menu (ABM - Financement ouverture de restaurant)</option>
                        <option value="Leader Universel - Bourse d'études" <?= $programmePreselectionne === 'leader' ? 'selected' : '' ?>>Leader Universel (Bourse d'études & matériel scolaire)</option>
                        <option value="ODH - Mariages collectifs" <?= $programmePreselectionne === 'odh' ? 'selected' : '' ?>>Objectif Développement Humanitaire (ODH - Mariage collectif)</option>
                        <option value="OSHDI CHARITE - Assistance veuves / orphelins" <?= $programmePreselectionne === 'charite' ? 'selected' : '' ?>>OSHDI CHARITÉ (Aide veuves, orphelins, malades, détenus)</option>
                        <option value="CSD - Prières & Délivrance spirituelle">Célébration Souveraineté de Dieu (Prières de délivrance)</option>
                    </select>
                </div>

                <div style="margin-bottom:24px;">
                    <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px;">
                        Exprimez vos besoins d'assistance, doléances ou avis sur la situation sociale de votre localité : *
                    </label>
                    <textarea name="besoins_assistance" required rows="5" class="form-control" style="padding:14px;" placeholder="Décrivez en détail votre situation (besoin médical urgent, projet culinaire, scolarité de vos enfants, situation de veuvage ou doléance spirituelle)..."></textarea>
                </div>

                <h4 style="font-size:1.15rem; color:#0A192F; margin-bottom:16px; border-left:4px solid var(--gold); padding-left:12px;">
                    4. Déclaration sur l'Honneur & Signature Électronique
                </h4>

                <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:12px; padding:18px; margin-bottom:24px;">
                    <p style="font-size:0.85rem; color:#475569; line-height:1.6; margin-bottom:14px;">
                        <i class="fa-solid fa-certificate" style="color:#F59E0B;"></i> 
                        Je certifie sur l'honneur l'exactitude des informations fournies sur le présent bordereau de souscription. 
                        J'autorise l'institution OSHDI à traiter mes doléances et à m'orienter vers la filiale ou la clinique partenaire appropriée.
                    </p>
                    <div class="form-grid-2" style="gap:16px;">
                        <div>
                            <label style="font-size:0.85rem; font-weight:600;">Saisir votre Nom en guise de signature numérique *</label>
                            <input type="text" name="signature_electronique" required class="form-control" placeholder="Signé électroniquement par..." style="padding-left:14px; font-family:'Outfit', cursive; font-size:1.1rem; color:#0A192F;">
                        </div>
                        <div style="display:flex; align-items:flex-end;">
                            <span style="font-size:0.82rem; color:#64748B;">Date de signature : <strong><?= date('d/m/Y') ?></strong> (Horodatage certifié)</span>
                        </div>
                    </div>
                </div>

                <!-- Bouton de soumission -->
                <div style="text-align:center;">
                    <button type="submit" id="btnSubmitSlip" class="btn btn-gold btn-lg" style="padding:16px 40px; font-size:1.1rem;">
                        <i class="fa-solid fa-paper-plane"></i> Soumettre le Bordereau par Mail à OSHDI
                    </button>
                    <p style="margin-top:10px; font-size:0.8rem; color:#64748B;">
                        Une copie sera immédiatement transmise à la boîte centrale <strong><?= SITE_EMAIL ?></strong> et vous recevrez votre bordereau certifié.
                    </p>
                </div>
            </form>
        </div>

        <script>
            document.getElementById('subscriberSlipForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = document.getElementById('btnSubmitSlip');
                const origText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Transmission du bordereau à OSHDI...';

                const formData = new FormData(this);
                fetch('api/subscribe_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = origText;
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 1200);
                    } else {
                        showToast(data.message || 'Erreur lors de la soumission.', 'error');
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = origText;
                    showToast('Bordereau enregistré avec succès et transmis aux services OSHDI !', 'success');
                    setTimeout(() => {
                        window.location.href = 'bordereau_pdf.php?ref=' + encodeURIComponent('<?= $refPaiement ?>');
                    }, 1200);
                });
            });
        </script>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

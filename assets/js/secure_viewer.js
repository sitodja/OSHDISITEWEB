/* ==============================================================
   OSHDI - LECTEUR SÉCURISÉ VIDÉO & PDF (NON TÉLÉCHARGEABLE)
   Protection anti-copie, anti-impression, canvas sécurisé et filigrane
   ============================================================== */

document.addEventListener('DOMContentLoaded', () => {
    initAntiCopyProtection();
    initSecureCanvasViewer();
});

// Protection contre le clic droit, inspection et touches de raccourci
function initAntiCopyProtection() {
    // 1. Désactiver clic droit
    document.addEventListener('contextmenu', (e) => {
        if (e.target.closest('.secure-viewer-container') || e.target.closest('.secure-protected-area')) {
            e.preventDefault();
            showToast('Document protégé par OSHDI. Clic droit et copie désactivés.', 'error');
            return false;
        }
    });

    // 2. Bloquer les raccourcis clavier de capture, enregistrement et impression
    document.addEventListener('keydown', (e) => {
        // Ctrl+S (Enregistrer), Ctrl+P (Imprimer), Ctrl+U (Code source), Ctrl+Shift+I / F12 (Inspecteur)
        if (
            (e.ctrlKey && (e.key === 's' || e.key === 'S' || e.key === 'p' || e.key === 'P' || e.key === 'u' || e.key === 'U')) ||
            (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'i' || e.key === 'C' || e.key === 'c' || e.key === 'J' || e.key === 'j')) ||
            e.key === 'F12'
        ) {
            e.preventDefault();
            e.stopPropagation();
            showToast('Action non autorisée. Ce contenu OSHDI est strictement confidentiel et protégé en lecture seule.', 'error');
            return false;
        }
    });

    // 3. Masquer le contenu si la fenêtre perd le focus (tentative de capture externe)
    window.addEventListener('blur', () => {
        const overlay = document.getElementById('securityBlurOverlay');
        if (overlay) overlay.style.display = 'flex';
    });

    window.addEventListener('focus', () => {
        const overlay = document.getElementById('securityBlurOverlay');
        if (overlay) overlay.style.display = 'none';
    });
}

// Rendu sécurisé sur Canvas HTML5 sans exposition du fichier binaire brut
function initSecureCanvasViewer() {
    const canvas = document.getElementById('pdfSecureCanvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let currentPage = 1;
    const totalPages = 5;

    // Données des pages de formation protégées
    const samplePages = [
        {
            title: "CHAPITRE 1 : FONDEMENTS SPIRITUELS & HUMANITAIRES",
            sections: [
                "1.1 LA VOCATION D'OSHDI DANS LA SOCIÉTÉ CONTEMPORAINE",
                "Les Oeuvres Spirituelles et Humanitaires Divines (OSHDI) ont pour mandat divin la restauration intégrale de la dignité humaine. Face aux crises multifactorielles, notre mission transcende le simple secours matériel.",
                "1.2 LES PILIERS DE LA RÉHABILITATION SPIRITUELLE",
                "Toute vie brisée par la maladie, le deuil (veuves et orphelins), l'incarcération ou l'indigence extrême porte une étincelle divine susceptible d'être ravivée par la prière d'intercession et l'amour en action.",
                "1.3 PROTOCOLE D'ÉCOUTE ET DE CONSEIL SOCIO-SPIRITUEL",
                "- Accueil bienveillant sans discrimination de race ou de condition.",
                "- Évaluation confidentielle des besoins médicaux (Réseau REMDIS) et économiques.",
                "- Accompagnement dans la prière de foi et délivrance des oppressions."
            ]
        },
        {
            title: "CHAPITRE 2 : RESTAURATION DES VEUVES ET ORPHELINS",
            sections: [
                "2.1 OBLIGATION MORALE ET BIBLIQUE DE PROTECTION",
                "Le programme OSHDI CHARITÉ s'érige en bouclier pour les catégories les plus fragilisées. L'autonomie économique est l'aboutissement recherché pour chaque foyer accompagné.",
                "2.2 PROGRAMME DE BOURSES LEADER UNIVERSEL",
                "La scolarisation des orphelins et des enfants démunis constitue le vecteur premier d'émancipation. Les meilleurs élèves sont sélectionnés pour bénéficier d'un suivi académique renforcé.",
                "2.3 KITS DE PREMIÈRE NÉCESSITÉ ET RÉINSERTION",
                "Distribution semestrielle de denrées alimentaires, vêtements et prise en charge sanitaire complète."
            ]
        },
        {
            title: "CHAPITRE 3 : LES CONCOURS AFRICA BEST MENU (ABM)",
            sections: [
                "3.1 VALORISATION DES TALENTS CULINAIRES COMME VECTEUR D'AUTONOMIE",
                "ABM n'est pas qu'une simple compétition gastronomique. C'est un tremplin économique conçu pour financer l'installation d'artisans restaurateurs.",
                "3.2 CRITÈRES D'ÉVALUATION ET DOTATIONS",
                "- Respect des normes d'hygiène HACCP et nutrition saine.",
                "- Saveurs authentiques et créativité africaine.",
                "- Présentation et faisabilité du modèle économique de restaurant solidaire."
            ]
        },
        {
            title: "CHAPITRE 4 : OBJECTIF DÉVELOPPEMENT HUMANITAIRE (ODH)",
            sections: [
                "4.1 RESTAURATION FAMILIALE PAR LES MARIAGES COLLECTIFS",
                "Légalisation coutumière et civile des unions de couples démunis dans la dignité.",
                "4.2 STABILITÉ DU FOYER ET PAIX SOCIALE",
                "Formations pré-matrimoniales, soutien psychologique et conseils en gestion budgétaire familiale."
            ]
        },
        {
            title: "CHAPITRE 5 : PROTOCOLE DU RÉSEAU MÉDICAL REMDIS",
            sections: [
                "5.1 COLLABORATION HOSPITALIÈRE ET SOINS SOLIDAIRES",
                "Conventions exclusives avec les cliniques partenaires pour des réductions substantielles allant de 40% à 70% sur les consultations, examens de laboratoire et chirurgies.",
                "5.2 GESTION DES BONS DE PRISE EN CHARGE MÉDICALE",
                "Chaque bénéficiaire enregistré reçoit une fiche REMDIS numérotée garantissant la prise en charge immédiate sans avance de frais insupportables."
            ]
        }
    ];

    function renderPage(pageNum) {
        const pageData = samplePages[pageNum - 1];
        if (!pageData) return;

        // Configuration dimensions Canvas A4
        canvas.width = 800;
        canvas.height = 1100;

        // Fond blanc document
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // En-tête officiel document
        ctx.fillStyle = '#0A192F';
        ctx.fillRect(0, 0, canvas.width, 90);

        ctx.fillStyle = '#F59E0B';
        ctx.font = 'bold 20px "Outfit", sans-serif';
        ctx.fillText("OSHDI - ŒUVRES SPIRITUELLES ET HUMANITAIRES DIVINES", 50, 42);

        ctx.fillStyle = '#FFFFFF';
        ctx.font = '12px "Plus Jakarta Sans", sans-serif';
        ctx.fillText("MANUEL OFFICIEL DE FORMATION ET CERTIFICATION - LECTURE SEULE PROTÉGÉE", 50, 68);

        // Ligne dorée
        ctx.fillStyle = '#D97706';
        ctx.fillRect(0, 88, canvas.width, 3);

        // Titre Chapitre
        ctx.fillStyle = '#0F172A';
        ctx.font = 'bold 22px "Outfit", sans-serif';
        ctx.fillText(pageData.title, 50, 140);

        // Contenu texte
        let y = 180;
        ctx.fillStyle = '#334155';
        ctx.font = '14px "Plus Jakarta Sans", sans-serif';

        pageData.sections.forEach(sec => {
            if (sec.startsWith('1.') || sec.startsWith('2.') || sec.startsWith('3.') || sec.startsWith('4.') || sec.startsWith('5.')) {
                ctx.fillStyle = '#2563EB';
                ctx.font = 'bold 16px "Outfit", sans-serif';
                y += 15;
                ctx.fillText(sec, 50, y);
                y += 25;
                ctx.fillStyle = '#334155';
                ctx.font = '14px "Plus Jakarta Sans", sans-serif';
            } else {
                // Découpage automatique des lignes (text wrap)
                const words = sec.split(' ');
                let line = '';
                const maxWidth = 700;

                for (let n = 0; n < words.length; n++) {
                    const testLine = line + words[n] + ' ';
                    const metrics = ctx.measureText(testLine);
                    if (metrics.width > maxWidth && n > 0) {
                        ctx.fillText(line, 50, y);
                        line = words[n] + ' ';
                        y += 24;
                    } else {
                        line = testLine;
                    }
                }
                ctx.fillText(line, 50, y);
                y += 28;
            }
        });

        // Filigrane de sécurité dynamique
        const userName = window.OSHDI_USER_NAME || 'Membre Souscripteur OSHDI';
        const userEmail = window.OSHDI_USER_EMAIL || 'souscripteur@oshdi.org';
        const timestamp = new Date().toLocaleString('fr-FR');

        ctx.save();
        ctx.translate(canvas.width / 2, canvas.height / 2);
        ctx.rotate(-Math.PI / 4);
        ctx.font = 'bold 22px "Outfit", sans-serif';
        ctx.fillStyle = 'rgba(217, 119, 6, 0.14)';
        ctx.textAlign = 'center';
        ctx.fillText("OSHDI - LECTURE SEULE STRICTEMENT PROTÉGÉE", 0, -40);
        ctx.fillText("SESSION : " + userName + " (" + userEmail + ")", 0, 0);
        ctx.fillText("ACCÈS AUTORISÉ LE : " + timestamp, 0, 40);
        ctx.restore();

        // Pied de page
        ctx.fillStyle = '#94A3B8';
        ctx.font = '11px "Plus Jakarta Sans", sans-serif';
        ctx.fillText("Page " + pageNum + " sur " + totalPages + " - Document non transférable et non téléchargeable - © OSHDI", 50, 1060);

        // Mise à jour de l'indicateur de page
        const pageIndicator = document.getElementById('securePageIndicator');
        if (pageIndicator) pageIndicator.innerText = `${pageNum} / ${totalPages}`;
    }

    // Navigation entre les pages
    const btnPrev = document.getElementById('btnPrevPage');
    const btnNext = document.getElementById('btnNextPage');

    if (btnPrev) {
        btnPrev.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderPage(currentPage);
            }
        });
    }

    if (btnNext) {
        btnNext.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderPage(currentPage);
            }
        });
    }

    // Premier rendu
    renderPage(1);
}

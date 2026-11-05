import puppeteer from 'puppeteer';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const screenshotsDir = path.join(__dirname, 'screenshots');

const BASE_URL = 'http://127.0.0.1:8000';
const EDGE_PATH = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';

const scenes = [];

async function capture(page, filename, title, description, badge = 'Démonstration') {
    const fullPath = path.join(screenshotsDir, filename);
    await page.screenshot({ path: fullPath, fullPage: false });
    scenes.push({
        file: filename,
        title,
        description,
        badge
    });
    console.log(`[CAPTURED] ${filename}: ${title}`);
}

async function run() {
    console.log('--- Lancement du parcours complet Health Authorization Platform ---');

    const browser = await puppeteer.launch({
        executablePath: EDGE_PATH,
        headless: true,
        args: ['--no-sandbox', '--disable-gpu', '--window-size=1440,900']
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 900 });

    try {
        // SCÈNE 1 : Page d'accueil (Landing Page)
        await page.goto(`${BASE_URL}/`, { waitUntil: 'networkidle0' });
        await capture(
            page,
            '01_landing_page.png',
            'Accueil · Plateforme Santé & Assurances',
            'Portail moderne de tiers-payant dématérialisé connectant hôpitaux et compagnies d’assurance.',
            'Portail Public'
        );

        // SCÈNE 2 : Page de Connexion
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle0' });
        await capture(
            page,
            '02_login_page.png',
            'Espace d\'authentification sécurisé',
            'Connexion professionnelle sécurisée avec badges de confiance et sélecteur de comptes de test par rôle.',
            'Sécurité'
        );

        // SCÈNE 3 : Connexion en tant qu'Agent d'Établissement (Hôpital Démo A)
        await page.type('#email', 'agent.a@demo.test');
        await page.type('#password', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('button[type="submit"]')
        ]);
        await capture(
            page,
            '03_facility_dashboard.png',
            'Tableau de bord Établissement Hospitalier',
            'Vue personnalisée pour l\'Agent hospitalier : émission de demandes, suivi des dossiers et accès aux bons.',
            'Hôpital'
        );

        // SCÈNE 4 : Liste "Mes demandes" de l'établissement
        await page.goto(`${BASE_URL}/facility/requests`, { waitUntil: 'networkidle0' });
        await capture(
            page,
            '04_facility_requests_list.png',
            'Suivi des dossiers de prise en charge',
            'Gestion des demandes émises par l\'hôpital, statuts en temps réel et horodatage.',
            'Hôpital'
        );

        // SCÈNE 5 : Formulaire Nouvelle demande - Étape 1 : Recherche
        await page.goto(`${BASE_URL}/facility/requests/create`, { waitUntil: 'networkidle0' });
        await capture(
            page,
            '05_create_request_search.png',
            'Étape 1 · Recherche de la couverture patient',
            'Sélection de l\'assureur conventionné et recherche temps-réel du patient par nom ou matricule.',
            'Hôpital'
        );

        // Lancement effectif de la recherche avec résultats
        await page.goto(`${BASE_URL}/facility/requests/create?insurer_id=3&search=ALPHA`, { waitUntil: 'networkidle0' });
        await capture(
            page,
            '06_create_request_acts.png',
            'Étapes 2 & 3 · Sélection du contrat et saisie des actes',
            'Contrôle des droits du bénéficiaire, sélection du taux et devis d\'actes avec montants en FCFA.',
            'Hôpital'
        );

        // Sélection de la couverture patient
        const coverageOption = await page.$eval('#coverage_selection option:not([value=""])', el => el.value);
        if (coverageOption) {
            await page.select('#coverage_selection', coverageOption);
        }

        // Choix du premier acte médical et montant
        const actOption = await page.$eval('#act_0 option:not([value=""])', el => el.value);
        if (actOption) {
            await page.select('#act_0', actOption);
            await page.type('#amount_0', '5000');
        }

        // Création du brouillon
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('form[action*="/requests"] button[type="submit"]')
        ]);

        // SCÈNE 7 : Détail de la demande créée (Brouillon)
        await capture(
            page,
            '07_request_draft_view.png',
            'Dossier de prise en charge (Brouillon)',
            'Décomposition automatique : part couverte par l\'assureur, ticket modérateur patient et téléversement des pièces.',
            'Hôpital'
        );

        // SCÈNE 8 : Affichage du bon de prise en charge officiel existant
        await page.goto(`${BASE_URL}/authorizations/5`, { waitUntil: 'networkidle0' });
        await capture(
            page,
            '08_official_guarantee_voucher.png',
            'Bon de prise en charge officiel (Garantie tiers-payant)',
            'Document officiel certifié avec dates de validité contractuelle, décomposition tarifaire et format prêt à l\'impression.',
            'Bon Officiel'
        );

        // Déconnexion de l'Agent
        await page.goto(`${BASE_URL}/dashboard`, { waitUntil: 'networkidle0' });
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('form[action*="/logout"] button')
        ]);

        // SCÈNE 9 : Connexion Gestionnaire Assureur (Assurance Démo Alpha)
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle0' });
        await page.type('#email', 'manager.alpha@demo.test');
        await page.type('#password', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('button[type="submit"]')
        ]);
        await capture(
            page,
            '09_insurer_dashboard.png',
            'Tableau de bord Gestionnaire Assureur',
            'Cockpit de gestion pour la compagnie d\'assurance : suivi des demandes reçues et du portefeuille souscripteurs.',
            'Assurance'
        );

        // SCÈNE 10 : File des demandes reçues par l'assureur
        await page.goto(`${BASE_URL}/insurer/requests`, { waitUntil: 'networkidle0' });
        await capture(
            page,
            '10_insurer_inbox.png',
            'File d\'instruction des demandes reçues',
            'Filtres par statut (Toutes, Envoyées, Informations demandées, Approuvées, Refusées) et liste des demandes des cliniques.',
            'Assurance'
        );

        // SCÈNE 11 : Portefeuille Assuré
        await page.goto(`${BASE_URL}/coverage-roster`, { waitUntil: 'networkidle0' });
        await capture(
            page,
            '11_coverage_roster.png',
            'Portefeuille assuré & Import CSV',
            'Répertoire des assurés, bénéficiaires affiliés, polices d\'assurance et module d\'importation sécurisée par fichier CSV.',
            'Assurance'
        );

        // Déconnexion Assureur
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('form[action*="/logout"] button')
        ]);

        // SCÈNE 12 : Connexion Administrateur Plateforme
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle0' });
        await page.type('#email', 'admin@demo.test');
        await page.type('#password', 'password');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('button[type="submit"]')
        ]);

        // SCÈNE 13 : Journal d'audit
        await page.goto(`${BASE_URL}/admin/audit-logs`, { waitUntil: 'networkidle0' });
        await capture(
            page,
            '12_admin_audit_logs.png',
            'Journal d\'audit et traçabilité réglementaire',
            'Traçabilité exhaustive de toutes les actions sensibles (création, téléversement, instruction, décision).',
            'Conformité'
        );

        // SCÈNE 14 : Partenariats Hôpitaux-Assureurs
        await page.goto(`${BASE_URL}/admin/facility-insurers`, { waitUntil: 'networkidle0' });
        await capture(
            page,
            '13_admin_partnerships.png',
            'Gestion des conventions & partenariats',
            'Matrice de configuration bilatérale entre chaque établissement de santé et les assureurs partenaires.',
            'Administration'
        );

        // SCÈNE 15 : Catalogue des actes et tarifs datés
        await page.goto(`${BASE_URL}/admin/medical-catalog`, { waitUntil: 'networkidle0' });
        await capture(
            page,
            '14_admin_medical_catalog.png',
            'Référentiel des actes et historique tarifaire',
            'Catalogue commun des actes médicaux avec gestion de l\'historique des tarifs officiels conventionnés.',
            'Administration'
        );

        // SCÈNE 16 : Gestion des organisations
        await page.goto(`${BASE_URL}/admin/organizations`, { waitUntil: 'networkidle0' });
        await capture(
            page,
            '15_admin_organizations.png',
            'Répertoire des organisations de santé',
            'Gestion centralisée des cliniques, hôpitaux et compagnies d\'assurances conventionnées.',
            'Administration'
        );

        // SCÈNE 17 : Comptes utilisateurs
        await page.goto(`${BASE_URL}/admin/users`, { waitUntil: 'networkidle0' });
        await capture(
            page,
            '16_admin_users.png',
            'Gestion des comptes et des permissions',
            'Attribution stricte des rôles et rattachements organisationnels (séparation des pouvoirs métier).',
            'Administration'
        );

        console.log(`\nSuccès total ! ${scenes.length} scènes capturées en haute résolution.`);

        fs.writeFileSync(
            path.join(__dirname, 'scenes.json'),
            JSON.stringify(scenes, null, 2),
            'utf-8'
        );

    } catch (err) {
        console.error('Erreur pendant le parcours:', err);
    } finally {
        await browser.close();
    }
}

run();

-- =============================================================================
-- Touche pas au klaxon — Script d'alimentation (jeu d'essais)
-- -----------------------------------------------------------------------------
-- À exécuter après 01_schema.sql.
--
-- Contenu :
--   - 12 agences (référentiel des sites) ;
--   - 20 employés extraits du SI RH ;
--   - 1 compte administrateur ;
--   - 16 trajets de démonstration.
--
-- Comptes de démonstration (environnement de développement uniquement) :
--   - employés       : mot de passe commun « Covoiturage#2026 »
--   - administrateur : admin@touche-pas-au-klaxon.fr / « Admin#Klaxon2026 »
-- Les mots de passe sont stockés sous forme d'empreinte bcrypt (coût 12).
--
-- Les dates des trajets sont calculées à partir de la date d'exécution du
-- script : le jeu d'essais contient donc toujours des trajets à venir, des
-- trajets passés et des trajets complets, quel que soit le jour de l'import.
-- =============================================================================

USE touche_pas_au_klaxon;

SET NAMES utf8mb4;

DELETE FROM trajet;
DELETE FROM utilisateur;
DELETE FROM agence;

-- -----------------------------------------------------------------------------
-- Agences
-- -----------------------------------------------------------------------------
INSERT INTO agence (id, nom) VALUES
    (1, 'Paris'),
    (2, 'Lyon'),
    (3, 'Marseille'),
    (4, 'Toulouse'),
    (5, 'Nice'),
    (6, 'Nantes'),
    (7, 'Strasbourg'),
    (8, 'Montpellier'),
    (9, 'Bordeaux'),
    (10, 'Lille'),
    (11, 'Rennes'),
    (12, 'Reims');

ALTER TABLE agence AUTO_INCREMENT = 13;

-- -----------------------------------------------------------------------------
-- Employés (SI RH) et administrateur
-- -----------------------------------------------------------------------------
SET @mdp_employe = '$2y$12$h1AV33ZpwCt5XuDUylStIuw0RLPzMNac6Glm6VOswZekzSmk9F8HK';
SET @mdp_admin = '$2y$12$PSoG58VuId6EiGclxb5tv.lyKcSfSHpLDVdPIPh7Nm1eMjhCfvfLO';

INSERT INTO utilisateur (id, nom, prenom, telephone, email, mot_de_passe, role) VALUES
    (1, 'Martin', 'Alexandre', '0612345678', 'alexandre.martin@email.fr', @mdp_employe, 'user'),
    (2, 'Dubois', 'Sophie', '0698765432', 'sophie.dubois@email.fr', @mdp_employe, 'user'),
    (3, 'Bernard', 'Julien', '0622446688', 'julien.bernard@email.fr', @mdp_employe, 'user'),
    (4, 'Moreau', 'Camille', '0611223344', 'camille.moreau@email.fr', @mdp_employe, 'user'),
    (5, 'Lefèvre', 'Lucie', '0777889900', 'lucie.lefevre@email.fr', @mdp_employe, 'user'),
    (6, 'Leroy', 'Thomas', '0655443322', 'thomas.leroy@email.fr', @mdp_employe, 'user'),
    (7, 'Roux', 'Chloé', '0633221199', 'chloe.roux@email.fr', @mdp_employe, 'user'),
    (8, 'Petit', 'Maxime', '0766778899', 'maxime.petit@email.fr', @mdp_employe, 'user'),
    (9, 'Garnier', 'Laura', '0688776655', 'laura.garnier@email.fr', @mdp_employe, 'user'),
    (10, 'Dupuis', 'Antoine', '0744556677', 'antoine.dupuis@email.fr', @mdp_employe, 'user'),
    (11, 'Lefebvre', 'Emma', '0699887766', 'emma.lefebvre@email.fr', @mdp_employe, 'user'),
    (12, 'Fontaine', 'Louis', '0655667788', 'louis.fontaine@email.fr', @mdp_employe, 'user'),
    (13, 'Chevalier', 'Clara', '0788990011', 'clara.chevalier@email.fr', @mdp_employe, 'user'),
    (14, 'Robin', 'Nicolas', '0644332211', 'nicolas.robin@email.fr', @mdp_employe, 'user'),
    (15, 'Gauthier', 'Marine', '0677889922', 'marine.gauthier@email.fr', @mdp_employe, 'user'),
    (16, 'Fournier', 'Pierre', '0722334455', 'pierre.fournier@email.fr', @mdp_employe, 'user'),
    (17, 'Girard', 'Sarah', '0688665544', 'sarah.girard@email.fr', @mdp_employe, 'user'),
    (18, 'Lambert', 'Hugo', '0611223366', 'hugo.lambert@email.fr', @mdp_employe, 'user'),
    (19, 'Masson', 'Julie', '0733445566', 'julie.masson@email.fr', @mdp_employe, 'user'),
    (20, 'Henry', 'Arthur', '0666554433', 'arthur.henry@email.fr', @mdp_employe, 'user'),
    (21, 'Administrateur', 'Klaxon', '0100000000', 'admin@touche-pas-au-klaxon.fr', @mdp_admin, 'admin');

ALTER TABLE utilisateur AUTO_INCREMENT = 22;

-- -----------------------------------------------------------------------------
-- Trajets de démonstration
-- Colonnes : départ, arrivée, jour relatif, heures, places totales/disponibles,
-- auteur. Résultat attendu sur la page d'accueil : 12 trajets
-- (16 - 2 complets - 2 passés).
-- -----------------------------------------------------------------------------
INSERT INTO trajet (id, agence_depart_id, agence_arrivee_id, date_heure_depart, date_heure_arrivee,
                    places_totales, places_disponibles, auteur_id) VALUES
    -- À venir, places disponibles
    (1, 1, 2, TIMESTAMP(CURDATE() + INTERVAL 2 DAY, '08:00:00'), TIMESTAMP(CURDATE() + INTERVAL 2 DAY, '12:30:00'), 4, 3, 1),
    (2, 2, 3, TIMESTAMP(CURDATE() + INTERVAL 3 DAY, '07:30:00'), TIMESTAMP(CURDATE() + INTERVAL 3 DAY, '11:00:00'), 3, 2, 2),
    (3, 3, 5, TIMESTAMP(CURDATE() + INTERVAL 3 DAY, '14:00:00'), TIMESTAMP(CURDATE() + INTERVAL 3 DAY, '16:45:00'), 4, 1, 3),
    (4, 4, 9, TIMESTAMP(CURDATE() + INTERVAL 4 DAY, '09:00:00'), TIMESTAMP(CURDATE() + INTERVAL 4 DAY, '11:30:00'), 3, 3, 1),
    (5, 6, 11, TIMESTAMP(CURDATE() + INTERVAL 5 DAY, '08:15:00'), TIMESTAMP(CURDATE() + INTERVAL 5 DAY, '09:45:00'), 4, 2, 5),
    (6, 7, 12, TIMESTAMP(CURDATE() + INTERVAL 6 DAY, '06:45:00'), TIMESTAMP(CURDATE() + INTERVAL 6 DAY, '10:15:00'), 2, 1, 6),
    (7, 10, 1, TIMESTAMP(CURDATE() + INTERVAL 7 DAY, '17:30:00'), TIMESTAMP(CURDATE() + INTERVAL 7 DAY, '20:00:00'), 4, 4, 7),
    (9, 9, 6, TIMESTAMP(CURDATE() + INTERVAL 9 DAY, '07:00:00'), TIMESTAMP(CURDATE() + INTERVAL 9 DAY, '11:00:00'), 4, 2, 9),
    (10, 1, 10, TIMESTAMP(CURDATE() + INTERVAL 10 DAY, '18:00:00'), TIMESTAMP(CURDATE() + INTERVAL 10 DAY, '20:30:00'), 3, 1, 1),
    (11, 11, 1, TIMESTAMP(CURDATE() + INTERVAL 12 DAY, '06:30:00'), TIMESTAMP(CURDATE() + INTERVAL 12 DAY, '10:30:00'), 4, 3, 10),
    (12, 5, 3, TIMESTAMP(CURDATE() + INTERVAL 14 DAY, '15:00:00'), TIMESTAMP(CURDATE() + INTERVAL 14 DAY, '17:30:00'), 2, 2, 11),
    (16, 4, 8, TIMESTAMP(CURDATE() + INTERVAL 20 DAY, '09:30:00'), TIMESTAMP(CURDATE() + INTERVAL 20 DAY, '12:00:00'), 4, 1, 13),
    -- À venir, complets (non affichés sur la page d'accueil)
    (8, 8, 4, TIMESTAMP(CURDATE() + INTERVAL 8 DAY, '10:00:00'), TIMESTAMP(CURDATE() + INTERVAL 8 DAY, '12:30:00'), 3, 0, 8),
    (13, 12, 7, TIMESTAMP(CURDATE() + INTERVAL 2 DAY, '16:00:00'), TIMESTAMP(CURDATE() + INTERVAL 2 DAY, '19:30:00'), 3, 0, 12),
    -- Passés (non affichés sur la page d'accueil)
    (14, 2, 1, TIMESTAMP(CURDATE() - INTERVAL 3 DAY, '08:00:00'), TIMESTAMP(CURDATE() - INTERVAL 3 DAY, '12:30:00'), 4, 2, 2),
    (15, 10, 9, TIMESTAMP(CURDATE() - INTERVAL 10 DAY, '07:00:00'), TIMESTAMP(CURDATE() - INTERVAL 10 DAY, '15:00:00'), 3, 1, 1);

ALTER TABLE trajet AUTO_INCREMENT = 17;

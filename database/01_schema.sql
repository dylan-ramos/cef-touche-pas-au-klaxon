-- =============================================================================
-- Touche pas au klaxon — Script de création de la base de données
-- -----------------------------------------------------------------------------
-- SGBD cible : MySQL 8.4 (compatible MariaDB 11.4)
-- Encodage   : utf8mb4 / utf8mb4_unicode_ci
--
-- Le script est rejouable : il supprime puis recrée les tables dans l'ordre
-- imposé par les clés étrangères. Les données sont chargées ensuite par
-- 02_seed.sql.
-- =============================================================================

CREATE DATABASE IF NOT EXISTS touche_pas_au_klaxon
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE touche_pas_au_klaxon;

SET NAMES utf8mb4;

DROP TABLE IF EXISTS trajet;
DROP TABLE IF EXISTS utilisateur;
DROP TABLE IF EXISTS agence;

-- -----------------------------------------------------------------------------
-- agence : sites géographiques de l'entreprise (villes).
-- Gérées exclusivement par l'administrateur.
-- -----------------------------------------------------------------------------
CREATE TABLE agence (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nom        VARCHAR(100)  NOT NULL,
    created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_agence PRIMARY KEY (id),
    CONSTRAINT uq_agence_nom UNIQUE (nom),
    CONSTRAINT ck_agence_nom_non_vide CHECK (CHAR_LENGTH(TRIM(nom)) > 0)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- utilisateur : employés issus du SI RH et administrateur de l'application.
-- Aucune création ni modification depuis l'application.
-- -----------------------------------------------------------------------------
CREATE TABLE utilisateur (
    id           INT UNSIGNED          NOT NULL AUTO_INCREMENT,
    nom          VARCHAR(100)          NOT NULL,
    prenom       VARCHAR(100)          NOT NULL,
    telephone    VARCHAR(20)           NOT NULL,
    email        VARCHAR(255)          NOT NULL,
    mot_de_passe VARCHAR(255)          NOT NULL COMMENT 'Empreinte password_hash() (bcrypt ou argon2)',
    role         ENUM ('user','admin') NOT NULL DEFAULT 'user',
    created_at   DATETIME              NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_utilisateur PRIMARY KEY (id),
    CONSTRAINT uq_utilisateur_email UNIQUE (email),
    CONSTRAINT ck_utilisateur_email CHECK (email LIKE '%_@_%._%'),
    CONSTRAINT ck_utilisateur_telephone CHECK (telephone REGEXP '^[0-9]{10}$')
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- trajet : trajet proposé par un employé entre deux agences.
-- La personne à contacter est l'auteur du trajet.
-- -----------------------------------------------------------------------------
CREATE TABLE trajet (
    id                 INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    agence_depart_id   INT UNSIGNED     NOT NULL,
    agence_arrivee_id  INT UNSIGNED     NOT NULL,
    date_heure_depart  DATETIME         NOT NULL,
    date_heure_arrivee DATETIME         NOT NULL,
    places_totales     TINYINT UNSIGNED NOT NULL,
    places_disponibles TINYINT UNSIGNED NOT NULL,
    auteur_id          INT UNSIGNED     NOT NULL,
    created_at         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_trajet PRIMARY KEY (id),
    CONSTRAINT fk_trajet_agence_depart FOREIGN KEY (agence_depart_id)
        REFERENCES agence (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_trajet_agence_arrivee FOREIGN KEY (agence_arrivee_id)
        REFERENCES agence (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_trajet_auteur FOREIGN KEY (auteur_id)
        REFERENCES utilisateur (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT ck_trajet_agences_distinctes CHECK (agence_depart_id <> agence_arrivee_id),
    CONSTRAINT ck_trajet_chronologie CHECK (date_heure_arrivee > date_heure_depart),
    CONSTRAINT ck_trajet_places_totales CHECK (places_totales BETWEEN 1 AND 9),
    CONSTRAINT ck_trajet_places_disponibles CHECK (places_disponibles <= places_totales)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- Page d'accueil : trajets à venir disposant de places, triés par départ.
CREATE INDEX idx_trajet_depart_places ON trajet (date_heure_depart, places_disponibles);
